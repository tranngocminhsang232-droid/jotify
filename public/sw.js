/**
 * sw.js — JOTIFY Service Worker v17
 * ──────────────────────────────────────────────────────────
 * PRODUCTION-READY for Railway (HTTPS, reverse proxy)
 *
 * Strategies:
 *   Cache-First  → static assets (CSS, JS, fonts, images)
 *   Network-First → pages + API — with offline HTML fallback
 *
 * Key design decisions:
 *   - Install pre-caches ONLY static files (no auth-required routes)
 *   - Auth pages (/notes, /profile) are cached on-the-fly via fetch handler
 *   - All cache keys use full URLs to avoid mismatch between
 *     cache.put(string) and caches.match(Request)
 *   - Origin check uses self.location.origin (works on any domain)
 * ──────────────────────────────────────────────────────────
 */

const CACHE_VER   = 'jotify-v19';
const ASSET_CACHE = 'jotify-assets-v19';

// ── Only pre-cache static files that DON'T require authentication ────────────
// Auth routes (/notes, /profile) would 302 → /login during install,
// which silently caches the login page as if it were the notes page.
// Those routes are cached on-the-fly when the user navigates while online.
const PRECACHE_URLS = [
    '/offline-note.html',
    '/offline.html',
    '/manifest.json',
    '/jotify-logo.png',
];

// ── Resolve a path to a full URL on this origin ──────────────────────────────
function fullUrl(path) {
    return new URL(path, self.location.origin).href;
}

// ── Minimal offline shell (inline HTML) ──────────────────────────────────────
function offlineShell(pathname) {
    return new Response(
        `<!DOCTYPE html><html><head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Offline — JOTIFY</title>
        <style>
            body{font-family:system-ui,sans-serif;background:#071910;color:#d1fae5;
                 display:flex;flex-direction:column;align-items:center;justify-content:center;
                 min-height:100vh;margin:0;gap:1rem;text-align:center;padding:1rem;}
            h1{font-size:1.5rem;margin:0}p{color:#a3e6bb;margin:0}
            a{color:#22c55e;text-decoration:underline}
            button{margin-top:1rem;background:#22c55e;color:#fff;border:none;padding:0.7rem 1.5rem;
                   border-radius:0.75rem;font-size:0.9rem;font-weight:700;cursor:pointer;}
        </style></head><body>
        <h1>📴 You are offline</h1>
        <p>The page <strong>${pathname}</strong> hasn't been cached yet.</p>
        <p>Please <a href="/notes">go back to Notes</a> and try again when you're online.</p>
        <button onclick="location.reload()">Retry</button>
        </body></html>`,
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

// ══════════════════════════════════════════════════════════════════════════════
// INSTALL — pre-cache static files (robust: one failure does NOT block others)
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('install', (event) => {
    console.log('[SW] Installing', CACHE_VER);
    event.waitUntil(
        caches.open(CACHE_VER).then(cache => {
            // Fetch each URL independently — one failure won't break the rest
            return Promise.all(
                PRECACHE_URLS.map(path => {
                    const url = fullUrl(path);
                    return fetch(new Request(url, { cache: 'no-store' }))
                        .then(res => {
                            if (res.ok) {
                                console.log('[SW] Pre-cached:', path, '→', res.status);
                                return cache.put(url, res);
                            }
                            console.warn('[SW] Pre-cache skip (not ok):', path, '→', res.status);
                        })
                        .catch(err => {
                            console.warn('[SW] Pre-cache failed:', path, '→', err.message);
                        });
                })
            );
        }).then(() => {
            console.log('[SW] Install complete, skipWaiting');
            return self.skipWaiting();
        })
    );
});

// ══════════════════════════════════════════════════════════════════════════════
// ACTIVATE — clean old caches + claim all clients immediately
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating', CACHE_VER);
    event.waitUntil(
        caches.keys().then(keys => {
            const old = keys.filter(k => k !== CACHE_VER && k !== ASSET_CACHE);
            if (old.length) console.log('[SW] Deleting old caches:', old);
            return Promise.all(old.map(k => caches.delete(k)));
        }).then(() => {
            console.log('[SW] Claiming clients');
            return self.clients.claim();
        })
    );
});

// ══════════════════════════════════════════════════════════════════════════════
// MESSAGE — CACHE_PAGES (from page JS) + SKIP_WAITING
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('message', (event) => {
    if (!event.data) return;

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    // Page asks us to cache specific URLs (used after login to warm auth pages)
    if (event.data.type === 'CACHE_PAGES' && Array.isArray(event.data.pages)) {
        console.log('[SW] CACHE_PAGES received:', event.data.pages);
        caches.open(CACHE_VER).then(cache => {
            event.data.pages.forEach(path => {
                const url = fullUrl(path);
                fetch(new Request(url, { credentials: 'include', cache: 'no-store' }))
                    .then(res => {
                        if (res.ok && !res.redirected) {
                            // Only cache if we got the actual page, NOT a redirect to /login
                            cache.put(url, res);
                            console.log('[SW] Cached (msg):', path, '→', res.status);
                        } else {
                            console.warn('[SW] Skip cache (msg):', path,
                                '→ status:', res.status, 'redirected:', res.redirected);
                        }
                    })
                    .catch(err => {
                        console.warn('[SW] Cache failed (msg):', path, '→', err.message);
                    });
            });
        });
    }
});

// ══════════════════════════════════════════════════════════════════════════════
// FETCH — intercept ALL same-origin GET requests
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle GET
    if (req.method !== 'GET') return;

    // Skip chrome-extension, blob, data URLs
    if (url.protocol === 'chrome-extension:' || url.protocol === 'blob:' ||
        url.protocol === 'data:') return;

    // ★ Skip cross-origin requests (CDN fonts, Pusher, external APIs)
    //   This replaces the old localhost filter — works on ANY domain.
    if (url.origin !== self.location.origin) return;

    // Skip Pusher/WebSocket auth
    if (url.pathname.includes('broadcasting/auth')) return;

    // ── Cache-First: static assets ───────────────────────────────────────
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname.startsWith('/js/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.match(/\.(css|js|woff2?|ttf|svg|png|jpg|jpeg|ico|webp|gif)$/)
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // ── Network-First: pages + API ───────────────────────────────────────
    event.respondWith(networkFirst(req));
});

// ══════════════════════════════════════════════════════════════════════════════
// CACHE-FIRST — for versioned/static assets (CSS, JS, fonts, images)
// ══════════════════════════════════════════════════════════════════════════════
async function cacheFirst(req) {
    const cached = await caches.match(req);
    if (cached) return cached;

    try {
        const res = await fetch(req);
        if (res.ok) {
            const cache = await caches.open(ASSET_CACHE);
            cache.put(req, res.clone());
        }
        return res;
    } catch {
        // Asset unavailable offline — return transparent fallback
        return new Response('', { status: 503, statusText: 'Asset unavailable offline' });
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// NETWORK-FIRST — for pages + API (cache on success, serve cache when offline)
// ══════════════════════════════════════════════════════════════════════════════
async function networkFirst(req) {
    const url = new URL(req.url);

    try {
        const res = await fetch(req);

        // Cache successful, non-redirected responses
        // (don't cache 302→login responses under the original URL)
        if (res.ok && !res.redirected) {
            const cache = await caches.open(CACHE_VER);
            cache.put(req, res.clone());
        }

        return res;
    } catch {
        // ── Offline fallback logic ───────────────────────────────────────

        // Note editor routes → ALWAYS serve the IDB-backed offline shell
        // regardless of request mode (navigate OR AJAX fetch).
        // This prevents stale cached HTML from showing empty note content.
        if (/^\/notes\/\d+(\/edit)?$/.test(url.pathname)) {
            const shell = await caches.match(fullUrl('/offline-note.html'));
            if (shell) return shell.clone();
        }

        // Try to find this exact URL in cache
        const cached = await caches.match(req);
        if (cached) return cached;

        // Navigation: return branded offline page
        if (req.mode === 'navigate') {
            // Try the pre-cached offline.html first
            const offlinePage = await caches.match(fullUrl('/offline.html'));
            if (offlinePage) return offlinePage;
            // Last resort: inline offline shell
            return offlineShell(url.pathname);
        }

        // Non-navigation (API/JSON) — return JSON error
        return new Response(
            JSON.stringify({ error: 'You are offline.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}
