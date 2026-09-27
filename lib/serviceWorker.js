importScripts('https://storage.googleapis.com/workbox-cdn/releases/5.1.4/workbox-sw.js');

const CACHE_NAME = "pwabuilder-cache-v3";
const OFFLINE_FALLBACK_PAGE = "/oops.html";
precached = ["/modeler", "/mod/new/index.js", "/mod/new/index.css", "/mod/new/index.html", "/ajax/user.php?ajax=true", "/list.php", "/list.php?sort=all", "/list", "/list?sort=all"]

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.add(OFFLINE_FALLBACK_PAGE);
      return cache.addAll(precached);
    })
  );
  self.skipWaiting();
});

if (workbox.navigationPreload.isSupported()) {
  workbox.navigationPreload.enable();
}

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      (async () => {
        try {
          const preloadResp = await event.preloadResponse;
          if (preloadResp) return preloadResp;

          return await fetch(event.request);
        } catch (error) {
          const cache = await caches.open(CACHE_NAME);
          return cache.match(OFFLINE_FALLBACK_PAGE);
        }
      })()
    );
  }
});

self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});