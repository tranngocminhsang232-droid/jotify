/**
 * sw.js — JOTIFY Service Worker v21 (Offline-First Phase 1)
 * ──────────────────────────────────────────────────────────
 * Strategies:
 *   Stale-While-Revalidate → JS, CSS bundles (users get fast load + fresh assets)
 *   Cache-First            → static assets (fonts, images, icons)
 *   Network-First          → API calls (JSON error fallback when offline)
 *   App Shell              → /notes/* navigation when offline returns cached /notes
 *
 * Key features:
 *   - Navigation Preload for faster online navigation
 *   - App shell pattern: /notes/* → cached /notes page (client-side router takes over)
 *   - Non-notes offline navigation → "This page requires internet" message
 *   - Pre-cache ONLY static files (no auth-required routes)
 *   - Origin check uses self.location.origin (works on any domain)
 * ──────────────────────────────────────────────────────────
 */

const CACHE_VER   = 'jotify-v22';
const ASSET_CACHE = 'jotify-assets-v22';

// ── Only pre-cache static files that DON'T require authentication ────────────
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.json',
    '/jotify-logo.png',
];

// ── Resolve a path to a full URL on this origin ──────────────────────────────
function fullUrl(path) {
    return new URL(path, self.location.origin).href;
}

// ── Inline HTML: "This page requires internet" for non-notes routes ──────────
function requiresInternetShell(pathname) {
    return new Response(
        `<!DOCTYPE html><html><head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Offline — JOTIFY</title>
        <style>
            body{font-family:system-ui,sans-serif;background:#071910;color:#d1fae5;
                 display:flex;flex-direction:column;align-items:center;justify-content:center;
                 min-height:100vh;margin:0;gap:1rem;text-align:center;padding:1rem;}
            h1{font-size:1.5rem;margin:0}p{color:#a3e6bb;margin:0}
            a{color:#22c55e;text-decoration:underline;font-weight:600}
            .icon{font-size:3rem;margin-bottom:0.5rem;opacity:0.7}
            .path{font-size:0.8rem;opacity:0.5;word-break:break-all;margin-top:0.5rem}
        </style></head><body>
        <div class="icon">🌐</div>
        <h1>This page requires internet</h1>
        <p>The page <strong>${pathname}</strong> is not available offline.</p>
        <p>Only your notes are accessible without a connection.</p>
        <a href="/notes">← Back to Notes</a>
        <div class="path">${pathname}</div>
        </body></html>`,
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

// ══════════════════════════════════════════════════════════════════════════════
// INSTALL — pre-cache static files
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('install', (event) => {
    console.log('[SW] Installing', CACHE_VER);
    event.waitUntil(
        caches.open(CACHE_VER).then(cache => {
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
// ACTIVATE — clean old caches + claim clients + enable Navigation Preload
// ══════════════════════════════════════════════════════════════════════════════
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating', CACHE_VER);
    event.waitUntil(
        Promise.all([
            // Clean old caches
            caches.keys().then(keys => {
                const old = keys.filter(k => k !== CACHE_VER && k !== ASSET_CACHE);
                if (old.length) console.log('[SW] Deleting old caches:', old);
                return Promise.all(old.map(k => caches.delete(k)));
            }),
            // Enable Navigation Preload (reduces latency for online navigations)
            self.registration.navigationPreload
                ? self.registration.navigationPreload.enable()
                    .then(() => console.log('[SW] Navigation Preload enabled'))
                    .catch(() => {})
                : Promise.resolve(),
            // Claim all clients immediately
            self.clients.claim().then(() => console.log('[SW] Claiming clients')),
        ])
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
                            cache.put(url, res.clone());
                            console.log('[SW] Cached (msg):', path, '→', res.status);
                            // Also store /notes as the app shell
                            if (path === '/notes') {
                                cache.put(fullUrl('/__app_shell__'), res.clone());
                                console.log('[SW] App shell cached from /notes');
                            }
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
    if (url.origin !== self.location.origin) return;

    // Skip Pusher/WebSocket auth
    if (url.pathname.includes('broadcasting/auth')) return;

    // ── Stale-While-Revalidate: JS/CSS bundles ──────────────────────────
    // Users get the cached version immediately while we fetch a fresh copy
    // in the background. Prevents stale assets after deployment.
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.match(/\.(css|js)$/)
    ) {
        event.respondWith(staleWhileRevalidate(req));
        return;
    }

    // ── Cache-First: static assets (fonts, images, icons) ───────────────
    if (
        url.pathname.startsWith('/storage/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.match(/\.(woff2?|ttf|svg|png|jpg|jpeg|ico|webp|gif)$/)
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // ── Network-First: pages + API ───────────────────────────────────────
    event.respondWith(networkFirst(req, event));
});

// ══════════════════════════════════════════════════════════════════════════════
// STALE-WHILE-REVALIDATE — for JS/CSS bundles
// Serve from cache immediately, then update cache in background.
// ══════════════════════════════════════════════════════════════════════════════
async function staleWhileRevalidate(req) {
    const cached = await caches.match(req);

    // Fire-and-forget: fetch fresh version and update cache
    const fetchPromise = fetch(req)
        .then(async (res) => {
            if (res.ok) {
                const cache = await caches.open(ASSET_CACHE);
                await cache.put(req, res.clone());
            }
            return res;
        })
        .catch(() => null);

    // Return cached version immediately if available, otherwise wait for network
    if (cached) {
        return cached;
    }

    const networkRes = await fetchPromise;
    if (networkRes) return networkRes;

    return new Response('', { status: 503, statusText: 'Asset unavailable offline' });
}

// ══════════════════════════════════════════════════════════════════════════════
// CACHE-FIRST — for static assets (fonts, images, icons)
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
        return new Response('', { status: 503, statusText: 'Asset unavailable offline' });
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// NETWORK-FIRST — for pages + API
// ── APP SHELL PATTERN for /notes routes ─────────────────────────────────────
//
// When offline, ALL /notes/* navigation requests get the cached /notes page
// (the "app shell"). The client-side router then reads the URL, loads the
// right note from IndexedDB, and renders it.
//
// For non-notes routes when offline → "This page requires internet" message.
// ══════════════════════════════════════════════════════════════════════════════
async function networkFirst(req, event) {
    const url = new URL(req.url);

    try {
        // Use Navigation Preload response if available (faster online navigations)
        const preloadResponse = event.preloadResponse ? await event.preloadResponse : null;
        const res = preloadResponse || await fetch(req);

        // Cache successful, non-redirected responses
        if (res.ok && !res.redirected) {
            const cache = await caches.open(CACHE_VER);
            cache.put(req, res.clone());

            // ★ Also cache /notes as the app shell
            if (url.pathname === '/notes') {
                cache.put(fullUrl('/__app_shell__'), res.clone());
            }
        }

        return res;
    } catch {
        // ── Offline fallback logic ───────────────────────────────────────

        // ★ APP SHELL: /notes/* routes → return cached /notes page
        // The client-side router will render the correct view from IDB.
        if (/^\/notes(\/.*)?$/.test(url.pathname)) {
            // Try the dedicated app shell first
            const shell = await caches.match(fullUrl('/__app_shell__'));
            if (shell) return shell.clone();
            // Fallback: try the exact /notes cache
            const notesPage = await caches.match(fullUrl('/notes'));
            if (notesPage) return notesPage.clone();
        }

        // Try to find this exact URL in cache
        const cached = await caches.match(req);
        if (cached) return cached;

        // ★ Navigation to non-notes routes → "requires internet" message
        if (req.mode === 'navigate') {
            return requiresInternetShell(url.pathname);
        }

        // Non-navigation (API/JSON) — return JSON error
        return new Response(
            JSON.stringify({ error: 'You are offline.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}
