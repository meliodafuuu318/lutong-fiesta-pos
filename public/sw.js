// Service worker: shows order notifications pushed by the server and opens the order when tapped.

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(payload.title || 'Lutong Piyesta', {
        body: payload.body,
        icon: payload.icon || '/images/apple-touch-icon.png',
        badge: payload.badge,
        tag: payload.tag,
        data: payload.data || {},
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const open = windows.find((client) => 'focus' in client);

        if (open) {
            await open.focus();
            if ('navigate' in open) await open.navigate(target);
        } else {
            await self.clients.openWindow(target);
        }
    })());
});
