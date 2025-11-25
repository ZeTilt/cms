// Service Worker pour PWA Club Subaquatique des Vénètes
// Version 5 - Custom CSS sans Tailwind
const CACHE_NAME = 'csv-plongee-v5';
const STATIC_CACHE = 'csv-static-v5';

// Assets statiques à mettre en cache (images, CSS, JS, fonts)
const staticAssets = [
  '/manifest.json',
  '/assets/logo sans fond.png',
  '/assets/favicon.ico',
  '/pwa-icons/icon-72x72.png',
  '/pwa-icons/icon-96x96.png',
  '/pwa-icons/icon-128x128.png',
  '/pwa-icons/icon-144x144.png',
  '/pwa-icons/icon-152x152.png',
  '/pwa-icons/icon-192x192.png',
  '/pwa-icons/icon-384x384.png',
  '/pwa-icons/icon-512x512.png'
];

// Installation du service worker
self.addEventListener('install', function(event) {
  console.log('Service Worker v5: Installing...');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(function(cache) {
        console.log('Cache statique ouvert');
        return Promise.allSettled(
          staticAssets.map(url =>
            cache.add(url).catch(err => {
              console.warn('Failed to cache:', url, err);
              return null;
            })
          )
        );
      })
      .then(() => {
        console.log('Service Worker v5: Installation complete');
        return self.skipWaiting();
      })
  );
});

// Activation du service worker
self.addEventListener('activate', function(event) {
  console.log('Service Worker v5: Activating...');
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          // Supprimer tous les anciens caches
          if (cacheName !== CACHE_NAME && cacheName !== STATIC_CACHE) {
            console.log('Suppression ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker v5: Activation complete');
      return self.clients.claim();
    })
  );
});

// Fonction pour déterminer si c'est un asset statique
function isStaticAsset(url) {
  return /\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|pdf|webp)$/i.test(url);
}

// Interception des requêtes avec stratégie Network First pour HTML
self.addEventListener('fetch', function(event) {
  // Ignorer les requêtes non-HTTP
  if (!event.request.url.startsWith('http')) {
    return;
  }

  const url = new URL(event.request.url);

  // STRATÉGIE 1: Cache First pour les assets statiques
  if (isStaticAsset(url.pathname)) {
    event.respondWith(
      caches.match(event.request).then(function(response) {
        if (response) {
          return response;
        }
        return fetch(event.request).then(function(response) {
          if (response && response.status === 200) {
            const responseToCache = response.clone();
            caches.open(STATIC_CACHE).then(function(cache) {
              cache.put(event.request, responseToCache);
            });
          }
          return response;
        });
      })
    );
    return;
  }

  // STRATÉGIE 2: Network First pour les pages HTML et les API
  event.respondWith(
    fetch(event.request)
      .then(function(response) {
        // Si la réponse est OK, on la retourne directement
        if (response && response.status === 200) {
          // On peut mettre en cache les pages HTML pour le mode offline
          if (event.request.destination === 'document') {
            const responseToCache = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(event.request, responseToCache);
            });
          }
          return response;
        }
        return response;
      })
      .catch(function() {
        // En cas d'erreur réseau (offline), on utilise le cache
        console.log('Network failed, using cache for:', event.request.url);
        return caches.match(event.request).then(function(response) {
          if (response) {
            return response;
          }
          // Si pas de cache non plus, retourner la page d'accueil en fallback
          if (event.request.destination === 'document') {
            return caches.match('/');
          }
        });
      })
  );
});

// Gestion des notifications push
self.addEventListener('push', function(event) {
  console.log('🔔 [SW] Push event received!', event);

  if (!event.data) {
    console.error('❌ [SW] Push event has no data!');
    return;
  }

  try {
    const rawData = event.data.text();
    console.log('📦 [SW] Raw push data:', rawData);

    const data = JSON.parse(rawData);
    console.log('✅ [SW] Parsed push data:', data);

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

    console.log('🔔 [SW] Showing notification with options:', options);

    event.waitUntil(
      self.registration.showNotification(data.title, options)
        .then(() => {
          console.log('✅ [SW] Notification displayed successfully');
        })
        .catch((error) => {
          console.error('❌ [SW] Error displaying notification:', error);
        })
    );
  } catch (error) {
    console.error('❌ [SW] Error processing push event:', error);

    // Afficher une notification de secours en cas d'erreur
    event.waitUntil(
      self.registration.showNotification('Club des Vénètes', {
        body: 'Nouvelle notification (erreur de traitement)',
        icon: '/pwa-icons/icon-192x192.png',
        tag: 'csv-notification-error'
      })
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