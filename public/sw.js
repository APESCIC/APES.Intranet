/**
 * Push-only service worker for APES Intranet.
 * Does not open Cache Storage or cache any responses (no offline mode).
 */
/* eslint-disable no-restricted-globals */

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let title = 'APES Intranet';
    let body = 'You have a new notice. Open the intranet to view it.';
    let data = {};

    if (event.data) {
        try {
            const payload = event.data.json();
            if (payload && typeof payload === 'object') {
                if (typeof payload.title === 'string' && payload.title !== '') {
                    title = payload.title;
                }
                if (typeof payload.body === 'string' && payload.body !== '') {
                    body = payload.body;
                }
                if (payload.data && typeof payload.data === 'object') {
                    data = payload.data;
                }
            }
        } catch (error) {
            const text = event.data.text();
            if (text) {
                body = text;
            }
        }
    }

    event.waitUntil(
        self.registration.showNotification(title, {
            body,
            data,
            icon: '/icons/icon-192.png',
            badge: '/brand/apes-favicon-32.png',
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    if ('navigate' in client) {
                        client.navigate(target);
                    }
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(target);
            }

            return undefined;
        }),
    );
});

/**
 * Ask open pages to re-subscribe. The page has the CSRF cookie and session,
 * which this worker does not.
 */
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            clients.forEach((client) => {
                client.postMessage({
                    type: 'pushsubscriptionchange',
                    oldEndpoint: event.oldSubscription ? event.oldSubscription.endpoint : null,
                });
            });
        }),
    );
});
