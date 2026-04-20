/**
 * sw.js — JOTIFY Service Worker (PWA)
 * Strategy:
 *   - Precache: app shell (offline.html, manifest.json)
 *   - Asset cache: CSS/JS/fonts → Cache-First
 *   - API/HTML → Network-First with IDB fallback
 *   - Background Sync: queue offline writes (create/update note)
 */

const CACHE_VERSION  = 'jotify-v3';
const ASSET_CACHE    = 'jotify-assets-v3';
const OFFLINE_URL    = '/offline.html';

// Assets to precache on install (app shell)
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.json',
];

// ────────────────────────────────────────────────────────────
// INSTALL — precache app shell
// ────────────────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => {
            return cache.addAll(PRECACHE_URLS);
        })
    );
    self.skipWaiting();
});

// ────────────────────────────────────────────────────────────
// ACTIVATE — clean up old caches
// ────────────────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter(k => k !== CACHE_VERSION && k !== ASSET_CACHE)
                    .map(k => caches.delete(k))
            );
        })
    );
    self.clients.claim();
});

// ────────────────────────────────────────────────────────────
// FETCH — route-based strategy
// ────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Skip non-GET, chrome-extension, Pusher WebSocket, broadcasting/auth
    if (req.method !== 'GET') return;
    if (req.url.startsWith('chrome-extension://')) return;
    if (req.url.includes('ws.pusherapp.com')) return;
    if (req.url.includes('pusher.com')) return;
    if (req.url.includes('broadcasting/auth')) return;

    const url = new URL(req.url);

    // ── Strategy 1: Cache-First for static assets (CSS/JS/fonts/images) ──
    if (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/storage/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com') ||
        url.hostname.includes('fonts.bunny.net')
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // ── Strategy 2: Network-First for everything else (HTML pages, API) ──
    event.respondWith(networkFirst(req));
});

// ────────────────────────────────────────────────────────────
// Cache-First strategy
// ────────────────────────────────────────────────────────────
async function cacheFirst(req) {
    const cached = await caches.match(req);
    if (cached) return cached;

    try {
        const response = await fetch(req);
        if (response.status === 200) {
            const cache = await caches.open(ASSET_CACHE);
            cache.put(req, response.clone());
        }
        return response;
    } catch {
        return new Response('Asset unavailable offline', { status: 503 });
    }
}

// ────────────────────────────────────────────────────────────
// Network-First strategy
// ────────────────────────────────────────────────────────────
async function networkFirst(req) {
    try {
        const response = await fetch(req);
        if (response.status === 200) {
            const cache = await caches.open(CACHE_VERSION);
            cache.put(req, response.clone());
        }
        return response;
    } catch {
        // Try cache
        const cached = await caches.match(req);
        if (cached) return cached;

        // For page navigations → offline page
        if (req.mode === 'navigate') {
            const offline = await caches.match(OFFLINE_URL);
            if (offline) return offline;
        }

        return new Response(
            JSON.stringify({ error: 'offline', message: 'You are offline' }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

// ────────────────────────────────────────────────────────────
// BACKGROUND SYNC — replay queued offline note saves
// ────────────────────────────────────────────────────────────
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-notes') {
        event.waitUntil(syncOfflineQueue());
    }
});

async function syncOfflineQueue() {
    // Read queued items from Cache Storage (simple queue mechanism)
    const cache = await caches.open('jotify-sync-queue');
    const keys  = await cache.keys();

    for (const req of keys) {
        try {
            const cloned = await cache.match(req);
            const body   = await cloned.json();

            const response = await fetch(req.url, {
                method:  'PUT',
                headers: { 'Content-Type': 'application/json', 'X-From-SW': '1' },
                body:    JSON.stringify(body),
            });

            if (response.ok) {
                await cache.delete(req);
            }
        } catch {
            // Keep in queue, retry next sync
        }
    }
}

// ────────────────────────────────────────────────────────────
// MESSAGE — allow main thread to queue offline saves
// ────────────────────────────────────────────────────────────
self.addEventListener('message', async (event) => {
    if (event.data?.type === 'QUEUE_SAVE') {
        const { url, body } = event.data;
        const cache = await caches.open('jotify-sync-queue');
        await cache.put(
            new Request(url),
            new Response(JSON.stringify(body), {
                headers: { 'Content-Type': 'application/json' }
            })
        );
    }
});
