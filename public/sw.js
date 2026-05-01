/**
 * sw.js — JOTIFY Service Worker v11
 * - Cache-First: static assets (CSS/JS/fonts/images)
 * - Network-First: pages + API calls
 * - No offline fallback page — just let the browser handle errors naturally
 */

const CACHE_VER   = 'jotify-v11';
const ASSET_CACHE = 'jotify-assets-v11';

// ── INSTALL ───────────────────────────────────────────────────────────────────
self.addEventListener('install', () => {
    self.skipWaiting();
});

// ── ACTIVATE — delete all old caches ─────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_VER && k !== ASSET_CACHE)
                    .map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
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
    try {
        const res = await fetch(req);
        if (res.ok) {
            const cache = await caches.open(CACHE_VER);
            cache.put(req, res.clone());
        }
        return res;
    } catch {
        // Try cache — if miss, let browser show its own error (no offline.html redirect)
        const cached = await caches.match(req);
        if (cached) return cached;
        // Return network error so browser handles it naturally
        throw new Error('Network unavailable');
    }
}
