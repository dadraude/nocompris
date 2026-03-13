const STATIC_CACHE = 'nocompris-static-v1';
const RUNTIME_CACHE = 'nocompris-runtime-v1';
const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/favicon.svg',
    '/apple-touch-icon.png',
    '/pwa-192x192.png',
    '/pwa-512x512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS))
    );

    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => Promise.all(
            cacheNames
                .filter((cacheName) => ! [STATIC_CACHE, RUNTIME_CACHE].includes(cacheName))
                .map((cacheName) => caches.delete(cacheName))
        ))
    );

    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(handleDocumentRequest(request));

        return;
    }

    if (['style', 'script', 'image', 'font', 'worker'].includes(request.destination)) {
        event.respondWith(handleAssetRequest(request));
    }
});

async function handleDocumentRequest(request) {
    const cache = await caches.open(RUNTIME_CACHE);

    try {
        const response = await fetch(request);

        if (response.ok) {
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return caches.match('/offline.html');
    }
}

async function handleAssetRequest(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cachedResponse = await cache.match(request);

    const networkResponsePromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => null);

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await networkResponsePromise;

    if (networkResponse) {
        return networkResponse;
    }

    return Response.error();
}
