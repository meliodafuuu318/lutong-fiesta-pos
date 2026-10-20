// Show / hide password
document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    const input = document.getElementById(button.dataset.togglePassword);
    if (!input) return;

    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(show));
    });
});

// Side menu: open / close
const shell = document.querySelector('[data-shell]');
const menuToggle = document.querySelector('[data-menu-toggle]');

if (shell && menuToggle) {
    const setMenu = (open) => {
        shell.dataset.menu = open ? 'open' : 'closed';
        menuToggle.setAttribute('aria-expanded', String(open));
    };

    menuToggle.addEventListener('click', () => setMenu(shell.dataset.menu !== 'open'));

    document.querySelectorAll('[data-menu-close]').forEach((el) => {
        el.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.dataset.menu === 'open') {
            setMenu(false);
            menuToggle.focus();
        }
    });
}

// Tabs
let selectTabById = () => {};

document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tabs = [...root.querySelectorAll('[role="tab"]')];

    const select = (tab, focus = false) => {
        tabs.forEach((t) => {
            const active = t === tab;
            t.setAttribute('aria-selected', String(active));
            t.tabIndex = active ? 0 : -1;
            document.getElementById(t.getAttribute('aria-controls')).hidden = !active;
        });
        if (focus) tab.focus();
    };

    selectTabById = (id) => {
        const tab = tabs.find((t) => t.id === id);
        if (tab) select(tab);
    };

    // Land on the tab named in the URL hash (e.g. after saving an item)
    selectTabById(location.hash.slice(1));

    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => select(tab));

        tab.addEventListener('keydown', (event) => {
            const target = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[event.key];
            if (target === undefined) return;
            event.preventDefault();
            select(tabs[(target + tabs.length) % tabs.length], true);
        });
    });
});

// Shared dialog behaviour: close buttons + click on the dimmed backdrop
function wireDialog(dialog) {
    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
}

// Notifications fade out on their own
document.querySelectorAll('[data-flash]').forEach((el) => {
    setTimeout(() => {
        el.classList.add('opacity-0');
        setTimeout(() => el.remove(), 600);
    }, Number(el.dataset.flash) || 4000);
});

// Edit / remove modal for menu items
const itemDialog = document.getElementById('item-dialog');
const confirmDialog = document.getElementById('confirm-dialog');

if (itemDialog && confirmDialog) {
    const updateForm = itemDialog.querySelector('[data-update-form]');
    const deleteForm = confirmDialog.querySelector('[data-delete-form]');
    const priceInput = updateForm.elements.price;
    const statusRadios = [...updateForm.querySelectorAll('input[name="status"]')];

    const fill = (card, { price, status } = {}) => {
        const { id, name } = card.dataset;
        itemDialog.querySelector('[data-dialog-name]').textContent = name;
        confirmDialog.querySelector('[data-confirm-name]').textContent = name;
        updateForm.action = updateForm.dataset.url.replace('__ID__', id);
        deleteForm.action = deleteForm.dataset.url.replace('__ID__', id);
        updateForm.elements.daily_item_id.value = id;
        priceInput.value = price || card.dataset.price;
        const currentStatus = status || card.dataset.status;
        statusRadios.forEach((radio) => (radio.checked = radio.value === currentStatus));
    };

    document.querySelectorAll('[data-daily-item]').forEach((card) => {
        card.addEventListener('click', () => {
            fill(card);
            itemDialog.showModal();
        });
    });

    itemDialog.querySelector('[data-confirm-open]').addEventListener('click', () => confirmDialog.showModal());

    [itemDialog, confirmDialog].forEach(wireDialog);

    // The server sent us back with validation errors: reopen the same item
    const reopenId = itemDialog.dataset.reopen;
    if (reopenId) {
        const card = document.querySelector(`[data-daily-item][data-id="${reopenId}"]`);
        if (card) {
            const panel = card.closest('[role="tabpanel"]');
            if (panel) selectTabById(panel.id.replace('panel-', 'tab-'));
            fill(card, { price: itemDialog.dataset.oldPrice, status: itemDialog.dataset.oldStatus });
            itemDialog.showModal();
        }
    }
}

// Add item to menu
const addDialog = document.getElementById('add-dialog');
const addConfirmDialog = document.getElementById('add-confirm-dialog');

if (addDialog && addConfirmDialog) {
    const addForm = addDialog.querySelector('[data-add-form]');
    const itemSelect = addForm.elements.item_id; // missing when every item is already on the menu
    const priceField = addForm.elements.price;

    [addDialog, addConfirmDialog].forEach(wireDialog);

    document.querySelector('[data-add-item-open]').addEventListener('click', () => {
        if (itemSelect) {
            itemSelect.value = '';
            priceField.value = '';
        }
        addDialog.showModal();
    });

    if (itemSelect) {
        // Picking an item pre-fills its default price, which stays editable
        itemSelect.addEventListener('change', () => {
            priceField.value = itemSelect.selectedOptions[0]?.dataset.price ?? '';
        });

        // Saving asks for confirmation first
        addForm.addEventListener('submit', (event) => {
            if (addForm.dataset.confirmed) return;

            event.preventDefault();
            if (!addForm.reportValidity()) return;

            addConfirmDialog.querySelector('[data-add-confirm-name]').textContent = itemSelect.selectedOptions[0].dataset.name;
            addConfirmDialog.querySelector('[data-add-confirm-price]').textContent = '₱' + Number(priceField.value).toFixed(2);
            addConfirmDialog.showModal();
        });

        addConfirmDialog.querySelector('[data-add-confirm]').addEventListener('click', (event) => {
            event.currentTarget.disabled = true;
            addForm.dataset.confirmed = '1';
            addForm.requestSubmit();
        });

        // The server sent us back with validation errors: reopen with what was entered
        if (addDialog.dataset.reopen) {
            itemSelect.value = addDialog.dataset.oldItem;
            priceField.value = addDialog.dataset.oldPrice;
            addDialog.showModal();
        }
    }
}
