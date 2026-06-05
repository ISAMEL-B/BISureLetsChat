// service-worker.js - BISURE Chat PWA
const CACHE_VERSION = 'v2';
const STATIC_CACHE = `bisure-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `bisure-dynamic-${CACHE_VERSION}`;

// Log startup
console.log('🔧 Service Worker script loaded - Version:', CACHE_VERSION);

// ============ INSTALL EVENT ============
self.addEventListener('install', (event) => {
    console.log('📦 Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('📦 Pre-caching critical files for offline use');
                return cache.addAll([
                    '/',
                    '/?source=pwa',
                    '/manifest.json',
                    '/offline',                  // ⭐ Changed to extension-less
                    '/offline.php',              // ⭐ Also cache the actual file
                    '/assets/icons/icon-192x192.png',
                    '/assets/icons/icon-512x512.png',
                    '/assets/icons/favicon.png'
                ]).then(() => {
                    console.log('✅ All critical files cached successfully');
                }).catch(err => {
                    console.warn('⚠️ Some files failed to cache:', err);
                    // Continue anyway - don't block installation
                });
            })
            .then(() => {
                console.log('✅ Installation complete - Forcing activation');
                return self.skipWaiting();
            })
    );
});

// ============ ACTIVATE EVENT ============
self.addEventListener('activate', (event) => {
    console.log('🚀 Service Worker activating...');
    
    const validCaches = [STATIC_CACHE, DYNAMIC_CACHE];
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (!validCaches.includes(cacheName)) {
                        console.log('🗑️ Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('✅ Activation complete - Taking control of all clients');
            return self.clients.claim();
        })
    );
});

// ============ FETCH EVENT - Smart Caching with Offline Support ============
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip chrome extensions
    if (url.protocol === 'chrome-extension:') return;
    
    // STRATEGY 1: HTML/PHP pages - Network first, then cache, then offline page
    if (request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache the latest version for future offline use
                    const responseClone = response.clone();
                    caches.open(DYNAMIC_CACHE).then(cache => {
                        cache.put(request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // Network failed - try cache first
                    return caches.match(request)
                        .then(cachedResponse => {
                            if (cachedResponse) {
                                console.log('📄 Serving from cache:', request.url);
                                return cachedResponse;
                            }
                            // If not in cache, show offline page
                            console.log('📄 Serving offline page for:', request.url);
                            
                            // Try extension-less URL first
                            return caches.match('/offline')
                                .then(offlineResponse => {
                                    if (offlineResponse) {
                                        return offlineResponse;
                                    }
                                    // Fallback to .php version
                                    return caches.match('/offline.php');
                                });
                        });
                })
        );
        return;
    }
    
    // STRATEGY 2: Offline page - Cache first (both /offline and /offline.php)
    if (url.pathname === '/offline' || url.pathname === '/offline.php') {
        event.respondWith(
            caches.match(url.pathname)
                .then(cachedResponse => {
                    if (cachedResponse) {
                        console.log('📄 Serving offline page from cache:', url.pathname);
                        return cachedResponse;
                    }
                    // Try the other version
                    const alternatePath = url.pathname === '/offline' ? '/offline.php' : '/offline';
                    return caches.match(alternatePath)
                        .then(altResponse => {
                            if (altResponse) {
                                console.log('📄 Serving alternate offline page:', alternatePath);
                                return altResponse;
                            }
                            // Last resort - fetch from network
                            return fetch(request);
                        });
                })
        );
        return;
    }
    
    // STRATEGY 3: API calls - Network only with JSON error
    if (url.pathname.includes('/api/') || url.pathname.includes('/auth/')) {
        event.respondWith(
            fetch(request)
                .then(response => response)
                .catch(() => {
                    return new Response(
                        JSON.stringify({ 
                            error: 'You are offline',
                            offline: true,
                            timestamp: Date.now()
                        }),
                        { 
                            status: 503, 
                            headers: { 'Content-Type': 'application/json' }
                        }
                    );
                })
        );
        return;
    }
    
    // STRATEGY 4: Static assets - Cache first with network update
    event.respondWith(
        caches.match(request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    // Update cache in background (stale-while-revalidate)
                    fetch(request).then(networkResponse => {
                        if (networkResponse.ok) {
                            caches.open(DYNAMIC_CACHE).then(cache => {
                                cache.put(request, networkResponse);
                            });
                        }
                    }).catch(() => {
                        // Can't update, that's fine - use cached version
                    });
                    
                    return cachedResponse;
                }
                
                // Not in cache, get from network
                return fetch(request)
                    .then(networkResponse => {
                        if (networkResponse.ok) {
                            const responseClone = networkResponse.clone();
                            caches.open(DYNAMIC_CACHE).then(cache => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => {
                        // Return placeholder for images
                        if (url.pathname.match(/\.(jpg|png|gif|svg|ico)$/)) {
                            return new Response(
                                `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200">
                                    <rect fill="#075E54" width="200" height="200"/>
                                    <text fill="white" x="100" y="110" text-anchor="middle" font-size="60" font-family="Arial">B</text>
                                </svg>`,
                                { headers: { 'Content-Type': 'image/svg+xml' } }
                            );
                        }
                        
                        // For CSS/JS, return empty response
                        if (url.pathname.match(/\.(css|js)$/)) {
                            return new Response('', { 
                                status: 200, 
                                headers: { 'Content-Type': 'text/plain' } 
                            });
                        }
                    });
            })
    );
});

// ============ PUSH NOTIFICATIONS (Optional) ============
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    try {
        const data = event.data.json();
        const options = {
            body: data.body || 'New notification',
            icon: '/assets/icons/icon-192x192.png',
            badge: '/assets/icons/favicon.png',
            vibrate: [200, 100, 200],
            data: data.data || {},
            actions: [
                { action: 'open', title: 'Open' },
                { action: 'close', title: 'Close' }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(
                data.title || 'BISURE Chat',
                options
            )
        );
    } catch (e) {
        console.error('Push notification error:', e);
    }
});

// ============ NOTIFICATION CLICK ============
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'close') return;
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/');
            }
        })
    );
});

// ============ MESSAGE HANDLER ============
self.addEventListener('message', (event) => {
    console.log('📨 Service Worker received message:', event.data);
    
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data?.type === 'CHECK_INSTALLED') {
        event.ports[0]?.postMessage({
            installed: true,
            timestamp: Date.now()
        });
    }
});

console.log('✅ Service Worker fully initialized and ready for offline use');