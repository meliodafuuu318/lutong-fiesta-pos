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

    // Coming back to the tab: catch up on what was held back while it was hidden.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && timer) schedule();
    });

    const channel = window.Echo.private('pos');
    events.forEach((name) => channel.listen(`.${name}`, schedule));

    // Pings sent while the connection was down are gone, so reload once it is back.
    const connection = window.Echo.connector?.pusher?.connection;
    let dropped = false;

    connection?.bind('state_change', ({ current }) => {
        if (['unavailable', 'failed', 'disconnected'].includes(current)) {
            dropped = true;
        } else if (current === 'connected' && dropped) {
            dropped = false;
            schedule();
        }
    });
}

// Modules run before the page's own scripts finish, so wait until window.Echo has been created.
if (document.readyState === 'complete') {
    init();
} else {
    window.addEventListener('DOMContentLoaded', init);
}
