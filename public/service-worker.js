/* CameLog – Service Worker
 * Strategia: cache-first dla statyki, network-first dla API (fallback do cache).
 * Offline fallback: /offline.html dla nawigacji.
 */
const CACHE_VERSION = 'camelog-v9-api-detail-fix';
const STATIC_CACHE = `${CACHE_VERSION}-static`;

const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/offline.html',
  '/manifest.json',
  '/assets/css/app.css?v=20260606-api-detail-fix',
  '/assets/js/api.js?v=20260606-api-detail-fix',
  '/assets/js/auth.js',
  '/assets/js/router.js',
  '/assets/js/ui.js?v=20260606-api-detail-fix',
  '/assets/js/plants.js',
  '/assets/js/plant-form.js?v=20260606-api-detail-fix',
  '/assets/js/species.js?v=20260606-api-detail-fix',
  '/assets/js/tasks.js?v=20260606-api-detail-fix',
  '/assets/js/notifications.js',
  '/assets/js/stats.js',
  '/assets/js/admin.js',
  '/assets/js/app.js?v=20260606-api-detail-fix',
  '/assets/images/logo.svg',
  '/assets/images/logo-mark.svg',
  '/assets/images/plant-placeholder.svg',
  '/assets/images/icon-192.png',
  '/assets/images/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => !k.startsWith(CACHE_VERSION)).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  // API – network-first
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(
      fetch(req).catch(() => caches.match(req).then((c) => c || new Response(JSON.stringify({offline: true}), { headers: { 'Content-Type': 'application/json' } })))
    );
    return;
  }

  // Nawigacja – network-first, offline fallback
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/offline.html'))
    );
    return;
  }

  // Statyka – cache-first
  event.respondWith(
    caches.match(req).then((cached) => {
      if (cached) return cached;
      return fetch(req).then((resp) => {
        const copy = resp.clone();
        if (resp.ok) {
          caches.open(STATIC_CACHE).then((c) => c.put(req, copy));
        }
        return resp;
      }).catch(() => cached);
    })
  );
});
