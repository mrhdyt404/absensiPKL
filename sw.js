// sw.js - Service Worker for PWA
const CACHE_NAME = 'absensi-pkl-ptpn4-v1.0';
const STATIC_ASSETS = [
  './',
  './index.php',
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/js/gps.js',
  './assets/js/camera.js',
  './assets/js/pwa.js',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/icons/apple-touch-icon.png',
  './manifest.webmanifest'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[ServiceWorker] Caching app shell & static assets');
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('[ServiceWorker] Some assets failed to cache:', err);
      });
    })
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[ServiceWorker] Removing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Fetch Event - Network first for API, Cache first for assets
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // For API or POST requests, bypass cache
  if (url.pathname.includes('/api/') || event.request.method !== 'GET') {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(
          JSON.stringify({ success: false, message: 'Anda sedang offline. Mohon periksa koneksi internet.' }),
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // Network First falling back to Cache
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // If valid response, clone and update cache
        if (response && response.status === 200 && response.type === 'basic') {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          if (event.request.mode === 'navigate') {
            return caches.match('./index.php');
          }
        });
      })
  );
});
