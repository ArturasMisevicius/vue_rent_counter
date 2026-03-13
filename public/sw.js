// Service Worker for Mobile Reading Collection Offline Support
const CACHE_NAME = 'mobile-readings-v1';
const OFFLINE_URL = '/offline';

// Files to cache for offline functionality
const CACHE_FILES = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline',
    // Add other essential assets
];

// Install event - cache essential files
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching essential files');
                return cache.addAll(CACHE_FILES);
            })
            .then(() => {
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Try to fetch from network
                return fetch(event.request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response for caching
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                        
                        // Return a generic offline response for other requests
                        return new Response('Offline', {
                            status: 503,
                            statusText: 'Service Unavailable',
                            headers: new Headers({
                                'Content-Type': 'text/plain'
                            })
                        });
                    });
            })
    );
});

// Background sync for offline readings
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-readings') {
        console.log('Background sync triggered for readings');
        event.waitUntil(syncOfflineReadings());
    }
});

// Sync offline readings when connection is restored
async function syncOfflineReadings() {
    try {
        // Get offline readings from IndexedDB or localStorage
        const offlineReadings = await getOfflineReadings();
        
        if (offlineReadings.length === 0) {
            return;
        }

        // Send each reading to the server
        for (const reading of offlineReadings) {
            try {
                const response = await fetch('/api/meter-readings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(reading)
                });

                if (response.ok) {
                    // Remove successfully synced reading
                    await removeOfflineReading(reading.id);
                    console.log('Synced reading:', reading.id);
                }
            } catch (error) {
                console.error('Failed to sync reading:', reading.id, error);
            }
        }

        // Notify the main thread about sync completion
        const clients = await self.clients.matchAll();
        clients.forEach(client => {
            client.postMessage({
                type: 'SYNC_COMPLETE',
                syncedCount: offlineReadings.length
            });
        });

    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Helper functions for offline storage
async function getOfflineReadings() {
    // This would typically use IndexedDB for more robust storage
    // For simplicity, we'll use a message to the main thread
    return [];
}

async function removeOfflineReading(id) {
    // Remove the reading from offline storage
    console.log('Removing offline reading:', id);
}

// Push notification support (future enhancement)
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        
        const options = {
            body: data.body,
            icon: '/icon-192x192.png',
            badge: '/badge-72x72.png',
            tag: 'meter-reading-notification',
            requireInteraction: true,
            actions: [
                {
                    action: 'view',
                    title: 'View Reading'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss'
                }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/mobile-reading-collection')
        );
    }
});