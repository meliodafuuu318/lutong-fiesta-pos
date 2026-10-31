// Order notifications: the bell in the header turns web push on or off for this browser.
const button = document.querySelector('[data-push-toggle]');
const publicKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
const endpointUrl = document.querySelector('meta[name="push-subscriptions-url"]')?.content;
const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

if (button && publicKey && endpointUrl && supported) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const toKey = (base64) => {
        const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(padded), (char) => char.charCodeAt(0));
    };

    const send = (method, body) => fetch(endpointUrl, {
        method,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(body),
    }).then((response) => {
        if (!response.ok) throw new Error(`Push subscription request failed (${response.status})`);
    });

    const describe = (subscription) => {
        const json = subscription.toJSON();

        return {
            endpoint: json.endpoint,
            keys: json.keys,
            contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        };
    };

    const render = (subscription) => {
        const on = Boolean(subscription) && Notification.permission === 'granted';
        const blocked = Notification.permission === 'denied';

        button.hidden = false;
        button.disabled = blocked;
        button.setAttribute('aria-pressed', String(on));
        button.setAttribute('aria-label', on ? 'Turn off order notifications' : 'Turn on order notifications');
        button.title = blocked
            ? 'Notifications are blocked in your browser settings'
            : on ? 'Order notifications are on' : 'Turn on order notifications';
        button.querySelector('[data-push-icon-on]').classList.toggle('hidden', !on);
        button.querySelector('[data-push-icon-off]').classList.toggle('hidden', on);
    };

    navigator.serviceWorker.register('/sw.js').then(async () => {
        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        // Whoever is signed in on this device now owns the subscription (it matters on shared tablets).
        if (subscription && Notification.permission === 'granted') {
            send('POST', describe(subscription)).catch(console.error);
        }

        render(subscription);

        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                if (subscription) {
                    await send('DELETE', { endpoint: subscription.endpoint });
                    await subscription.unsubscribe();
                    subscription = null;
                } else {
                    // Has to be asked for straight from the click, or browsers refuse to show the prompt.
                    if ((await Notification.requestPermission()) !== 'granted') return;

                    subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: toKey(publicKey),
                    });

                    try {
                        await send('POST', describe(subscription));
                    } catch (error) {
                        await subscription.unsubscribe();
                        subscription = null;
                        throw error;
                    }
                }
            } catch (error) {
                console.error(error);
                window.alert(
                    error?.name === 'AbortError'
                        ? "This browser couldn't reach its push service. In Brave, turn on \"Use Google Services for Push Messaging\" at brave://settings/privacy, then restart the browser and try again."
                        : "Couldn't change notification settings. Please try again.",
                );
            } finally {
                render(subscription);
            }
        });
    }).catch(console.error);
}
