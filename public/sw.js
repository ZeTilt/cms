// Service Worker pour PWA Club Subaquatique des Vénètes
const CACHE_NAME = 'csv-plongee-v1';
const urlsToCache = [
  '/',
  '/manifest.json'
];

// Installation du service worker
self.addEventListener('install', function(event) {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Cache ouvert');
        // Mettre en cache les URLs une par une pour éviter l'échec total
        return Promise.allSettled(
          urlsToCache.map(url =>
            cache.add(url).catch(err => {
              console.warn('Failed to cache:', url, err);
              return null;
            })
          )
        );
      })
      .then(() => {
        console.log('Service Worker: Installation complete');
        // Forcer l'activation immédiate
        return self.skipWaiting();
      })
  );
});

// Activation du service worker
self.addEventListener('activate', function(event) {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          if (cacheName !== CACHE_NAME) {
            console.log('Suppression ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Activation complete');
      // Prendre le contrôle immédiatement
      return self.clients.claim();
    })
  );
});

// Interception des requêtes
self.addEventListener('fetch', function(event) {
  // Ignorer les requêtes non-HTTP (chrome-extension://, etc.)
  if (!event.request.url.startsWith('http')) {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(function(response) {
        // Retourne la réponse en cache si elle existe
        if (response) {
          return response;
        }

        // Sinon, effectue la requête réseau
        return fetch(event.request).then(function(response) {
          // Vérifie si la réponse est valide
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }

          // Clone la réponse
          var responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(function(cache) {
              // Vérifier que la requête est bien HTTP avant de la mettre en cache
              if (event.request.url.startsWith('http')) {
                cache.put(event.request, responseToCache);
              }
            });

          return response;
        }).catch(function() {
          // En cas d'erreur réseau, retourne une page offline basique
          if (event.request.destination === 'document') {
            return caches.match('/');
          }
        });
      }
    )
  );
});

// Gestion des notifications push
self.addEventListener('push', function(event) {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: data.icon || '/pwa-icons/icon-192x192.png',
      badge: data.badge || '/pwa-icons/icon-72x72.png',
      tag: data.tag || 'csv-notification',
      data: {
        url: data.url || '/',
        timestamp: Date.now()
      },
      vibrate: [200, 100, 200],
      requireInteraction: data.requireInteraction || false,
      actions: data.actions || []
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Gestion du clic sur une notification
self.addEventListener('notificationclick', function(event) {
  event.notification.close();

  // Gérer les actions personnalisées si présentes
  if (event.action) {
    console.log('Action clicked:', event.action);

    // Si l'action est 'close', on ne fait rien de plus
    if (event.action === 'close') {
      return;
    }
  }

  // Ouvrir l'URL associée à la notification
  const urlToOpen = event.notification.data.url;

  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(function(clientList) {
      // Chercher si une fenêtre est déjà ouverte
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url.includes(urlToOpen) && 'focus' in client) {
          return client.focus();
        }
      }

      // Si aucune fenêtre n'est ouverte sur cette URL, en ouvrir une nouvelle
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});

// Gestion de la fermeture d'une notification
self.addEventListener('notificationclose', function(event) {
  console.log('Notification closed:', event.notification.tag);
});