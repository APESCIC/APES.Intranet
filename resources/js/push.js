/**
 * Web Push helpers for APES Intranet.
 * Registering the service worker does not ask for permission.
 * subscribeToPush() must only run after a user gesture (button press).
 */

const SERVICE_WORKER_URL = '/sw.js';

export function pushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

export function initPushPreferences() {
    const enable = document.querySelector('[data-push-enable]');
    const disable = document.querySelector('[data-push-disable]');
    const status = document.getElementById('push-status');
    if (!enable && !disable) {
        return;
    }

    enable?.addEventListener('click', async () => {
        if (status) {
            status.textContent = 'Asking the browser for permission…';
        }
        try {
            await subscribeToPush();
            if (status) {
                status.textContent = 'Browser notifications are on for this device. Save your category preferences below.';
            }
        } catch (error) {
            if (status) {
                status.textContent = error instanceof Error ? error.message : 'Could not turn on browser notifications.';
            }
        }
    });

    disable?.addEventListener('click', async () => {
        if (status) {
            status.textContent = 'Turning off on this device…';
        }
        try {
            await unsubscribeFromPush();
            if (status) {
                status.textContent = 'Browser notifications are off on this device.';
            }
        } catch (error) {
            if (status) {
                status.textContent = error instanceof Error ? error.message : 'Could not turn off browser notifications.';
            }
        }
    });
}

export async function registerPushServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    const registration = await navigator.serviceWorker.register(SERVICE_WORKER_URL, { scope: '/' });
    listenForSubscriptionChange();
    return registration;
}

/**
 * Ask the browser for notification permission and store the subscription.
 * Call only from a click (or similar) handler — never on page load.
 */
export async function subscribeToPush() {
    if (!pushSupported()) {
        throw new Error('This browser does not support Web Push.');
    }

    const registration = await registerPushServiceWorker();
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Browser notifications were not allowed.');
    }

    const publicKey = await fetchVapidPublicKey();
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicKey),
    });

    await saveSubscription(subscription);
    return subscription;
}

export async function unsubscribeFromPush() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
        return;
    }

    await removeSubscription(subscription.endpoint);
    await subscription.unsubscribe();
}

async function fetchVapidPublicKey() {
    const response = await fetch('/push/vapid-public-key', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
        throw new Error('Web Push is not configured on this server yet.');
    }
    const payload = await response.json();
    if (typeof payload.publicKey !== 'string' || payload.publicKey === '') {
        throw new Error('Web Push is not configured on this server yet.');
    }
    return payload.publicKey;
}

async function saveSubscription(subscription) {
    const response = await fetch('/push-subscriptions', {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify(subscription.toJSON()),
    });
    if (!response.ok) {
        throw new Error('Could not save the push subscription.');
    }
}

async function removeSubscription(endpoint) {
    const response = await fetch('/push-subscriptions', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ endpoint }),
    });
    if (!response.ok) {
        throw new Error('Could not remove the push subscription.');
    }
}

function jsonHeaders() {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const csrf = readCookie('XSRF-TOKEN');
    if (csrf) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(csrf);
    }
    return headers;
}

function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? match[1] : null;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

let listeningForChange = false;

function listenForSubscriptionChange() {
    if (listeningForChange || !('serviceWorker' in navigator)) {
        return;
    }
    listeningForChange = true;
    navigator.serviceWorker.addEventListener('message', async (event) => {
        if (!event.data || event.data.type !== 'pushsubscriptionchange') {
            return;
        }
        try {
            if (event.data.oldEndpoint) {
                await removeSubscription(event.data.oldEndpoint);
            }
            if (Notification.permission === 'granted') {
                await subscribeToPush();
            }
        } catch (error) {
            // Prefer quiet failure: the person can re-enable on the preferences page.
        }
    });
}
