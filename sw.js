const CACHE_NAME = 'mh-rafi-dl-v1';

const CORE_ASSETS = [
    './',
    './downloader.html',
    './manifest.json'
];

self.addEventListener('install', event => {

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(CORE_ASSETS))
            .catch(() => {
                // Ignore individual asset failures so
                // install still succeeds.
            })
    );

    self.skipWaiting();

});

self.addEventListener('activate', event => {

    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(key => key !== CACHE_NAME)
                    .map(key => caches.delete(key))
            )
        )
    );

    self.clients.claim();

});

self.addEventListener('fetch', event => {

    // Only handle same-origin GET requests for the
    // app shell — everything else (the media API,
    // video CDN links) goes straight to the network
    // untouched.
    if (
        event.request.method !== 'GET' ||
        new URL(event.request.url).origin !== self.location.origin
    ) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(cached =>
            cached ||
            fetch(event.request).catch(() =>
                caches.match('./Yt.html')
            )
        )
    );

});
