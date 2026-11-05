// Live updates: the server pings the "pos" channel when something changes, and the page that
// shows that data reloads itself. The pings carry ids only; the reload goes through the normal routes.

const TRANSACTIONS = ['TransactionCreated', 'TransactionUpdated'];
const MENU = ['MenuUpdated'];
const ITEMS = ['ItemCreated', 'ItemUpdated', 'ItemDeleted'];
const EXPENSES = ['ExpenseCreated', 'ExpenseUpdated', 'ExpenseDeleted'];

// Which events each page cares about, by route name (see the realtime-route meta tag in the layout).
const PAGES = {
    'dashboard': [...TRANSACTIONS, ...MENU, 'ItemUpdated', 'ItemDeleted', ...EXPENSES],
    'pending-payments': TRANSACTIONS,
    'transactions.index': TRANSACTIONS,
    'transactions.show': TRANSACTIONS,
    'payments.index': TRANSACTIONS,
    'transactions.create': [...MENU, 'ItemUpdated', 'ItemDeleted'],
    'transactions.edit': [...MENU, 'ItemUpdated', 'ItemDeleted'],
    'analytics.items': [...TRANSACTIONS, ...MENU, ...ITEMS],
    'menu': [...MENU, ...ITEMS],
    'items.index': [...ITEMS],
    'expenses.index': EXPENSES,
};

function init() {
    const route = document.querySelector('meta[name="realtime-route"]')?.content;
    const events = PAGES[route];

    if (!events || !window.Echo) return;

    const progressBar = document.querySelector('[data-progress]');
    let timer = null;
    let leaving = false;

    window.addEventListener('beforeunload', () => { leaving = true; });
    window.addEventListener('pagehide', () => { leaving = true; });

    // A reload must never throw away what someone is in the middle of: an open dialog, a field
    // being typed in, an order being built, a request on its way, or a message still on screen.
    const busy = () => {
        if (document.hidden || leaving) return true;
        if (document.querySelector('dialog[open]')) return true;
        if (progressBar && !progressBar.hidden) return true;
        if (document.querySelector('[data-flash]')) return true;
        if (document.querySelector('#order-form [data-line-inputs] input')) return true;

        const active = document.activeElement;
        return !!active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
    };

    const refresh = () => {
        timer = null;

        if (busy()) {
            timer = setTimeout(refresh, 2000);
            return;
        }

        window.location.reload();
    };

    // Several pings in a row (an order and its payment) become one reload.
    const schedule = () => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(refresh, 500);
    };

    // Coming back to the tab: catch up on what was held back while it was hidden, and on anything
    // missed while a phone had the tab frozen (its socket may have been closed without a word).
    let hiddenAt = null;

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hiddenAt = Date.now();
            return;
        }

        if (timer || (hiddenAt && Date.now() - hiddenAt > 60_000)) schedule();
        hiddenAt = null;
    });

    const channel = window.Echo.private('pos');
    events.forEach((name) => channel.listen(`.${name}`, schedule));

    // Re-subscribing to a private channel calls /broadcasting/auth again. If the session was renewed in
    // the meantime (remember-me after the session lifetime) the page's CSRF token is stale and that call
    // fails with 419; if the user was signed out it is 401/403. The socket still says "connected" but
    // the channel never subscribes, so the page goes deaf. A reload picks up the new session and token
    // (or lands on the login page).
    channel.error((error) => {
        const status = typeof error === 'number' ? error : error?.status;

        if (![401, 403, 419].includes(status)) return;

        // Never loop: if auth is genuinely broken, reload at most once every 30 seconds
        try {
            const last = Number(sessionStorage.getItem('realtime-auth-reload') || 0);
            if (Date.now() - last < 30_000) return;
            sessionStorage.setItem('realtime-auth-reload', String(Date.now()));
        } catch {
            // sessionStorage unavailable: reload anyway, once per page load
            if (window.__realtimeAuthReloaded) return;
            window.__realtimeAuthReloaded = true;
        }

        if (!leaving) window.location.reload();
    });

    // Pings sent while the connection was down are gone. A short drop goes connecting -> connected
    // without ever being "unavailable", so catch up on every reconnect, not just after long outages.
    const connection = window.Echo.connector?.pusher?.connection;
    let everConnected = connection?.state === 'connected';

    connection?.bind('state_change', ({ current }) => {
        if (current !== 'connected') return;

        if (everConnected) schedule();
        everConnected = true;
    });
}

// Modules run before the page's own scripts finish, so wait until window.Echo has been created.
if (document.readyState === 'complete') {
    init();
} else {
    window.addEventListener('DOMContentLoaded', init);
}
