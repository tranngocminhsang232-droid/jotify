/**
 * sw.js — JOTIFY Service Worker v12
 * - Cache-First : static assets (CSS/JS/fonts/images)
 * - Network-First: pages + API — with offline HTML fallback for navigation
 * - Pre-caches profile pages so /profile works offline
 */

const CACHE_VER   = 'jotify-v13';
const ASSET_CACHE = 'jotify-assets-v13';

// Pages to pre-cache at install so navigation works offline
const PRECACHE_PAGES = [
    '/profile',
    '/profile/edit',
    '/offline-note.html',  // shell for dynamic note routes
];

// Minimal offline shell returned when page is not in cache and network fails
function offlineShell(url) {
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
        </style></head><body>
        <h1>📴 You are offline</h1>
        <p>The page <strong>${url}</strong> hasn't been cached yet.</p>
        <p>Please <a href="/notes">go back to Notes</a> and try again when you're online.</p>
        </body></html>`,
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

// ── INSTALL — pre-cache profile pages ────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VER).then(cache =>
            Promise.all(
                PRECACHE_PAGES.map(url =>
                    fetch(url, { credentials: 'include', cache: 'no-store' })
                        .then(r => { if (r.ok) cache.put(url, r); })
                        .catch(() => {}) // skip if server unreachable at install time
                )
            )
        ).then(() => self.skipWaiting())
    );
});

// ── ACTIVATE — delete old caches ─────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_VER && k !== ASSET_CACHE)
                    .map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── MESSAGE — CACHE_PAGES / SKIP_WAITING ─────────────────────────────────────
self.addEventListener('message', (event) => {
    if (!event.data) return;

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    if (event.data.type === 'CACHE_PAGES' && Array.isArray(event.data.pages)) {
        caches.open(CACHE_VER).then(cache => {
            event.data.pages.forEach(url => {
                fetch(url, { cache: 'no-store' })
                    .then(r => { if (r.ok) cache.put(url, r); })
                    .catch(() => {});
            });
        });
    }
});

// ── FETCH ─────────────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle GET
    if (req.method !== 'GET') return;

    // Skip external, chrome-ext, websockets
    if (url.protocol === 'chrome-extension:') return;
    if (!url.hostname.includes('localhost') && !url.hostname.includes('127.0.0.1')) return;
    if (req.url.includes('pusher') || req.url.includes('broadcasting/auth')) return;

    // Cache-First: static assets
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname.match(/\.(css|js|woff2?|ttf|svg|png|jpg|ico|webp)$/)
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // Network-First: everything else (pages, API)
    event.respondWith(networkFirst(req));
});

// ── Cache-First ───────────────────────────────────────────────────────────────
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
        return new Response('Asset unavailable', { status: 503 });
    }
}

// ── Network-First ─────────────────────────────────────────────────────────────
async function networkFirst(req) {
    const url = new URL(req.url);
    try {
        const res = await fetch(req);
        if (res.ok) {
            // Cache successful page responses for offline fallback
            const cache = await caches.open(CACHE_VER);
            cache.put(req, res.clone());
        }
        return res;
    } catch {
        // Offline — try cache first
        const cached = await caches.match(req);
        if (cached) return cached;

        if (req.mode === 'navigate') {
            // Dynamic note routes → serve offline editor shell
            if (/^\/notes\/\d+(\/edit)?$/.test(url.pathname)) {
                const shell = await caches.match('/offline-note.html');
                if (shell) return shell;
            }
            // All other navigation: branded offline page
            return offlineShell(url.pathname);
        }

        // Non-navigation (API/JSON) — return JSON error
        return new Response(
            JSON.stringify({ error: 'You are offline.' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}
