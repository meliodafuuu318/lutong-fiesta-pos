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

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Toast for things that happen without a page load (same look as the server-side notifications)
function showToast(message, kind = 'status') {
    const toast = document.createElement('p');
    toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    toast.className = kind === 'error'
        ? 'pointer-events-none fixed left-1/2 top-20 z-40 w-[min(32rem,calc(100%-2rem))] -translate-x-1/2 rounded-lg border-2 border-danger bg-cream-50 px-4 py-3 font-semibold text-danger shadow-lg transition-opacity duration-500'
        : 'pointer-events-none fixed left-1/2 top-20 z-40 w-[min(32rem,calc(100%-2rem))] -translate-x-1/2 rounded-lg border-2 border-teal-500 bg-teal-100 px-4 py-3 font-semibold text-teal-900 shadow-lg transition-opacity duration-500';
    toast.textContent = message;
    toast.dataset.flash = kind === 'error' ? '8000' : '4000';
    document.querySelectorAll('[data-flash]').forEach((old) => old.remove());
    document.body.append(toast);
    scheduleFade(toast);
}

function scheduleFade(el) {
    setTimeout(() => {
        el.classList.add('opacity-0');
        setTimeout(() => el.remove(), 600);
    }, Number(el.dataset.flash) || 4000);
}

// Notifications fade out on their own
document.querySelectorAll('[data-flash]').forEach(scheduleFade);

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

    // Saving changes asks for confirmation first
    const updateConfirmDialog = document.getElementById('update-confirm-dialog');

    if (updateConfirmDialog) {
        wireDialog(updateConfirmDialog);

        const updateConfirmButton = updateConfirmDialog.querySelector('[data-update-confirm]');

        updateForm.addEventListener('submit', (event) => {
            if (updateForm.dataset.confirmed) return;

            event.preventDefault();
            if (!updateForm.reportValidity()) return;

            const status = statusRadios.find((radio) => radio.checked)?.value;
            updateConfirmDialog.querySelector('[data-update-confirm-name]').textContent = itemDialog.querySelector('[data-dialog-name]').textContent;
            updateConfirmDialog.querySelector('[data-update-confirm-price]').textContent = '₱' + Number(priceInput.value).toFixed(2);
            updateConfirmDialog.querySelector('[data-update-confirm-status]').textContent = status === 'SOLD OUT' ? 'sold out' : 'available';
            updateConfirmDialog.showModal();
        });

        updateConfirmButton.addEventListener('click', () => {
            updateConfirmButton.disabled = true;
            updateForm.dataset.confirmed = '1';
            updateForm.requestSubmit();
        });

        window.addEventListener('pageshow', (event) => {
            if (!event.persisted) return;
            delete updateForm.dataset.confirmed;
            updateConfirmButton.disabled = false;
        });
    }

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

// Items page: active toggle (saves right away)
document.querySelectorAll('[data-item-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', async () => {
        const next = toggle.getAttribute('aria-checked') !== 'true';
        const { url, name } = toggle.dataset;

        toggle.setAttribute('aria-checked', String(next));
        toggle.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ is_active: next }),
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            showToast(`${name} is now ${next ? 'active' : 'inactive'}.`);
        } catch {
            toggle.setAttribute('aria-checked', String(!next));
            showToast(`Couldn't update ${name}. Please try again.`, 'error');
        } finally {
            toggle.disabled = false;
        }
    });
});

// Items page: delete with confirmation
const deleteItemDialog = document.getElementById('delete-item-dialog');

if (deleteItemDialog) {
    const deleteItemForm = deleteItemDialog.querySelector('[data-delete-item-form]');

    wireDialog(deleteItemDialog);

    document.querySelectorAll('[data-item-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            deleteItemForm.action = button.dataset.url;
            deleteItemDialog.querySelector('[data-delete-item-name]').textContent = button.dataset.name;
            deleteItemDialog.showModal();
        });
    });
}

// Items page: add / edit item (one modal, two modes)
const itemFormDialog = document.getElementById('item-form-dialog');
const itemFormConfirmDialog = document.getElementById('item-form-confirm-dialog');

if (itemFormDialog && itemFormConfirmDialog) {
    const form = itemFormDialog.querySelector('[data-item-form]');
    const {
        _method: methodField,
        item_id: itemIdField,
        name: nameField,
        default_price: priceField,
        category_id: categoryField,
    } = form.elements;

    const copy = {
        add: { title: 'Add Item', submit: 'Save Item', confirmTitle: 'Save Item?', verb: 'added to' },
        edit: { title: 'Edit Item', submit: 'Save Changes', confirmTitle: 'Save Changes?', verb: 'saved in' },
    };

    let mode = 'add';

    const setMode = (next) => {
        mode = next;
        const text = copy[next];

        itemFormDialog.querySelector('[data-form-title]').textContent = text.title;
        itemFormDialog.querySelector('[data-form-submit]').textContent = text.submit;
        itemFormConfirmDialog.querySelector('[data-confirm-title]').textContent = text.confirmTitle;
        itemFormConfirmDialog.querySelector('[data-confirm-verb]').textContent = text.verb;
        itemFormConfirmDialog.querySelector('[data-item-form-confirm]').textContent = text.submit;

        // Editing: PUT to the item's URL, and the name can't be changed
        methodField.disabled = next !== 'edit';
        nameField.readOnly = next === 'edit';
        if (next === 'add') {
            form.action = form.dataset.storeUrl;
            itemIdField.value = '';
        }
    };

    const openAdd = () => {
        setMode('add');
        nameField.value = '';
        priceField.value = '';

        // Start on the category whose tab is currently showing
        const activeTab = document.querySelector('[role="tab"][aria-selected="true"]');
        categoryField.value = activeTab ? activeTab.id.replace('tab-', '') : '';

        itemFormDialog.showModal();
    };

    const openEdit = (button, overrides = {}) => {
        setMode('edit');
        form.action = button.dataset.url;
        itemIdField.value = button.dataset.id;
        nameField.value = button.dataset.name;
        priceField.value = overrides.price || button.dataset.price;
        categoryField.value = overrides.category || button.dataset.category;

        itemFormDialog.showModal();
    };

    [itemFormDialog, itemFormConfirmDialog].forEach(wireDialog);

    document.querySelector('[data-item-add-open]').addEventListener('click', openAdd);
    document.querySelectorAll('[data-item-edit]').forEach((button) => {
        button.addEventListener('click', () => openEdit(button));
    });

    // Saving asks for confirmation first
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed) return;

        event.preventDefault();
        if (!form.reportValidity()) return;

        itemFormConfirmDialog.querySelector('[data-confirm-name]').textContent = nameField.value.trim();
        itemFormConfirmDialog.querySelector('[data-confirm-category]').textContent = categoryField.selectedOptions[0].textContent;
        itemFormConfirmDialog.querySelector('[data-confirm-price]').textContent = '₱' + Number(priceField.value).toFixed(2);
        itemFormConfirmDialog.showModal();
    });

    itemFormConfirmDialog.querySelector('[data-item-form-confirm]').addEventListener('click', (event) => {
        event.currentTarget.disabled = true;
        form.dataset.confirmed = '1';
        form.requestSubmit();
    });

    // The server sent us back with validation errors: reopen with what was entered
    if (itemFormDialog.dataset.reopen) {
        const { oldItemId, oldName, oldPrice, oldCategory } = itemFormDialog.dataset;
        const editButton = oldItemId && document.querySelector(`[data-item-edit][data-id="${oldItemId}"]`);

        if (editButton) {
            openEdit(editButton, { price: oldPrice, category: oldCategory });
        } else {
            setMode('add');
            nameField.value = oldName;
            priceField.value = oldPrice;
            categoryField.value = oldCategory;
            itemFormDialog.showModal();
        }
    }
}

// Create transaction page
const transactionForm = document.getElementById('create-transaction-form');
const transactionConfirmDialog = document.getElementById('create-transaction-confirm-dialog');

if (transactionForm && transactionConfirmDialog) {
    wireDialog(transactionConfirmDialog);

    const peso = (cents) => '₱' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const toCents = (value) => Math.round(Number(value) * 100) || 0;
    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const row = (left, right, rightClass = 'font-semibold text-ink') => {
        const line = el('div', 'flex items-start justify-between gap-2');
        line.append(el('p', 'text-ink', left), el('p', rightClass, right));
        return line;
    };

    const cards = [...transactionForm.querySelectorAll('[data-menu-item]')];
    const typeRadios = [...transactionForm.querySelectorAll('input[name="type"]')];
    const sections = [...transactionForm.querySelectorAll('[data-section]')];
    const { payment_method: methodField, payment_amount: amountField, address: addressField } = transactionForm.elements;
    const itemsError = transactionForm.querySelector('[data-items-error]');
    const summary = {
        empty: document.querySelector('[data-summary-empty]'),
        items: document.querySelector('[data-summary-items]'),
        totals: document.querySelectorAll('[data-total]'),
        payment: document.querySelector('[data-summary-payment]'),
        methodLabel: document.querySelector('[data-payment-method-label]'),
        amount: document.querySelector('[data-payment-amount]'),
        diff: document.querySelector('[data-payment-diff]'),
        diffLabel: document.querySelector('[data-payment-diff-label]'),
        diffValue: document.querySelector('[data-payment-diff-value]'),
        delivery: document.querySelector('[data-summary-delivery]'),
        address: document.querySelector('[data-delivery-address]'),
    };

    const currentType = () => typeRadios.find((radio) => radio.checked)?.value ?? 'WALK IN';

    const selectedLines = () => cards
        .map((card) => ({
            name: card.dataset.name,
            price: toCents(card.dataset.price),
            quantity: Number(card.querySelector('[data-qty]').value) || 0,
        }))
        .filter((line) => line.quantity > 0);

    const totalOf = (lines) => lines.reduce((sum, line) => sum + line.price * line.quantity, 0);

    // Paid vs total: change when it's more, what's still owed when it's less
    const paymentDiff = (total) => {
        if (amountField.value === '') return null;
        const paid = toCents(amountField.value);
        return paid >= total
            ? { label: 'Change', value: peso(paid - total) }
            : { label: 'Balance Owed', value: peso(total - paid) };
    };

    const render = () => {
        const lines = selectedLines();
        const total = totalOf(lines);
        const type = currentType();

        summary.empty.hidden = lines.length > 0;
        summary.items.replaceChildren(...lines.map((line) => row(`x${line.quantity} ${line.name}`, peso(line.price * line.quantity))));
        summary.totals.forEach((node) => (node.textContent = peso(total)));

        summary.payment.hidden = type !== 'WALK IN';
        summary.delivery.hidden = type !== 'DELIVERY';

        if (type === 'WALK IN') {
            summary.methodLabel.textContent = methodField.selectedOptions[0]?.textContent ?? '';
            summary.amount.textContent = amountField.value === '' ? '—' : peso(toCents(amountField.value));

            const diff = paymentDiff(total);
            summary.diff.hidden = diff === null;
            if (diff) {
                summary.diffLabel.textContent = diff.label;
                summary.diffValue.textContent = diff.value;
            }
        } else {
            summary.address.textContent = addressField.value.trim() || '—';
        }
    };

    const setQuantity = (card, value) => {
        const quantity = Math.min(99, Math.max(0, Math.floor(Number(value)) || 0));
        const [idInput, quantityInput] = card.querySelectorAll('input[type="hidden"]');

        card.querySelector('[data-qty]').value = quantity;
        idInput.disabled = quantityInput.disabled = quantity === 0;
        quantityInput.value = quantity;
        card.dataset.selected = String(quantity > 0);

        if (quantity > 0) itemsError.hidden = true;
        render();
    };

    // Only the section for the chosen type is shown and sent
    const applyType = () => {
        const type = currentType();

        sections.forEach((section) => {
            const active = section.dataset.section === type;
            section.hidden = !active;
            section.querySelectorAll('input, select, textarea').forEach((control) => (control.disabled = !active));
        });

        render();
    };

    cards.forEach((card) => {
        const input = card.querySelector('[data-qty]');
        const current = () => Number(input.value) || 0;

        card.querySelector('[data-qty-minus]').addEventListener('click', () => setQuantity(card, current() - 1));
        card.querySelector('[data-qty-plus]').addEventListener('click', () => setQuantity(card, current() + 1));
        input.addEventListener('input', () => setQuantity(card, input.value));
        input.addEventListener('blur', () => setQuantity(card, input.value));
    });

    typeRadios.forEach((radio) => radio.addEventListener('change', applyType));
    [methodField, amountField, addressField].forEach((control) => {
        control.addEventListener('input', render);
        control.addEventListener('change', render);
    });

    transactionForm.querySelector('[data-use-total]').addEventListener('click', () => {
        amountField.value = (totalOf(selectedLines()) / 100).toFixed(2);
        render();
    });

    // Saving asks for confirmation first
    transactionForm.addEventListener('submit', (event) => {
        if (transactionForm.dataset.confirmed) return;

        event.preventDefault();

        const lines = selectedLines();
        if (lines.length === 0) {
            itemsError.textContent = 'Add at least one item to the transaction.';
            itemsError.hidden = false;
            itemsError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const dialog = transactionConfirmDialog;
        const type = currentType();
        const total = totalOf(lines);

        dialog.querySelector('[data-confirm-type]').textContent = type === 'WALK IN' ? 'Walk In' : 'Delivery';
        dialog.querySelector('[data-confirm-items]').replaceChildren(...lines.map((line) => row(`x${line.quantity} ${line.name}`, peso(line.price * line.quantity))));
        dialog.querySelector('[data-confirm-total]').textContent = peso(total);

        const payment = dialog.querySelector('[data-confirm-payment]');
        const delivery = dialog.querySelector('[data-confirm-delivery]');
        payment.hidden = type !== 'WALK IN';
        delivery.hidden = type !== 'DELIVERY';

        if (type === 'WALK IN') {
            const diff = paymentDiff(total);
            dialog.querySelector('[data-confirm-method]').textContent = methodField.selectedOptions[0].textContent;
            dialog.querySelector('[data-confirm-amount]').textContent = peso(toCents(amountField.value));
            dialog.querySelector('[data-confirm-diff-label]').textContent = diff.label;
            dialog.querySelector('[data-confirm-diff-value]').textContent = diff.value;
        } else {
            dialog.querySelector('[data-confirm-address]').textContent = addressField.value.trim();
        }

        dialog.showModal();
    });

    transactionConfirmDialog.querySelector('[data-create-transaction-confirm]').addEventListener('click', (event) => {
        event.currentTarget.disabled = true;
        transactionForm.dataset.confirmed = '1';
        transactionForm.requestSubmit();
    });

    // Start from whatever the server sent back (validation errors) or the defaults
    cards.forEach((card) => setQuantity(card, card.dataset.initial));
    applyType();
}

// Dashboard: pending orders (filter, add payment, mark delivered)
const pendingRoot = document.querySelector('[data-pending]');

if (pendingRoot) {
    const peso = (cents) => '₱' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const toCents = (value) => Math.round(Number(value) * 100) || 0;

    // Filter: all / deliveries / payments
    const chips = [...pendingRoot.querySelectorAll('[data-pending-filter]')];
    const cards = [...pendingRoot.querySelectorAll('[data-pending-card]')];
    const filterEmpty = pendingRoot.querySelector('[data-pending-filter-empty]');

    const applyFilter = (kind) => {
        let shown = 0;
        chips.forEach((chip) => chip.setAttribute('aria-pressed', String(chip.dataset.pendingFilter === kind)));
        cards.forEach((card) => {
            const match = kind === 'all' || card.dataset[kind] === '1';
            card.hidden = !match;
            if (match) shown++;
        });
        if (filterEmpty) filterEmpty.hidden = shown > 0;
    };

    chips.forEach((chip) => chip.addEventListener('click', () => applyFilter(chip.dataset.pendingFilter)));

    // Add payment
    const paymentDialog = document.getElementById('payment-dialog');

    if (paymentDialog) {
        wireDialog(paymentDialog);

        const form = paymentDialog.querySelector('[data-payment-form]');
        const amountInput = form.elements.payment_amount;
        const methodSelect = form.elements.payment_method;
        const hint = paymentDialog.querySelector('[data-payment-hint]');
        let dueCents = 0;

        const updateHint = () => {
            const entered = toCents(amountInput.value);
            if (entered <= 0) {
                hint.textContent = '';
                return;
            }
            const diff = entered - dueCents;
            if (diff > 0) hint.textContent = `Change to give back: ${peso(diff)}. This pays the order in full.`;
            else if (diff === 0) hint.textContent = 'This pays the order in full.';
            else hint.textContent = `${peso(-diff)} will still be due after this payment.`;
        };

        const openPayment = (button, old = {}) => {
            const { id, reference, total, paid, due } = button.dataset;
            dueCents = toCents(due);

            form.action = form.dataset.url.replace('__ID__', id);
            form.elements.transaction_id.value = id;
            paymentDialog.querySelector('[data-payment-reference]').textContent = reference;
            paymentDialog.querySelector('[data-payment-total]').textContent = peso(toCents(total));
            paymentDialog.querySelector('[data-payment-paid]').textContent = peso(toCents(paid));
            paymentDialog.querySelector('[data-payment-due]').textContent = peso(dueCents);

            methodSelect.value = old.method || 'CASH';
            amountInput.value = old.amount ?? '';
            updateHint();
            paymentDialog.showModal();
            amountInput.focus();
        };

        pendingRoot.querySelectorAll('[data-pending-pay]').forEach((button) => {
            button.addEventListener('click', () => openPayment(button));
        });

        amountInput.addEventListener('input', updateHint);

        paymentDialog.querySelector('[data-payment-exact]').addEventListener('click', () => {
            amountInput.value = (dueCents / 100).toFixed(2);
            updateHint();
            amountInput.focus();
        });

        // Recording asks for confirmation first
        const paymentConfirmDialog = document.getElementById('payment-confirm-dialog');
        const paymentSubmit = form.querySelector('button[type="submit"]');
        const paymentConfirmButton = paymentConfirmDialog.querySelector('[data-payment-confirm]');

        wireDialog(paymentConfirmDialog);

        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed) {
                paymentSubmit.disabled = true;
                return;
            }

            event.preventDefault();
            if (!form.reportValidity()) return;

            paymentConfirmDialog.querySelector('[data-payment-confirm-amount]').textContent = peso(toCents(amountInput.value));
            paymentConfirmDialog.querySelector('[data-payment-confirm-method]').textContent = methodSelect.selectedOptions[0].textContent;
            paymentConfirmDialog.querySelector('[data-payment-confirm-reference]').textContent = paymentDialog.querySelector('[data-payment-reference]').textContent;
            paymentConfirmDialog.querySelector('[data-payment-confirm-note]').textContent = hint.textContent;
            paymentConfirmDialog.showModal();
        });

        paymentConfirmButton.addEventListener('click', () => {
            paymentConfirmButton.disabled = true;
            form.dataset.confirmed = '1';
            form.requestSubmit();
        });

        window.addEventListener('pageshow', (event) => {
            if (!event.persisted) return;
            delete form.dataset.confirmed;
            paymentSubmit.disabled = false;
            paymentConfirmButton.disabled = false;
        });

        // Back with errors: open the same order again with what was typed
        if (paymentDialog.dataset.reopen) {
            const button = pendingRoot.querySelector(`[data-pending-pay][data-id="${paymentDialog.dataset.oldTransaction}"]`);
            if (button) {
                openPayment(button, { method: paymentDialog.dataset.oldMethod, amount: paymentDialog.dataset.oldAmount });
            }
        }
    }

    // Mark delivered
    const deliverDialog = document.getElementById('deliver-dialog');

    if (deliverDialog) {
        wireDialog(deliverDialog);

        const form = deliverDialog.querySelector('[data-deliver-form]');

        pendingRoot.querySelectorAll('[data-pending-deliver]').forEach((button) => {
            button.addEventListener('click', () => {
                const { id, reference, address } = button.dataset;
                form.action = form.dataset.url.replace('__ID__', id);
                deliverDialog.querySelector('[data-deliver-reference]').textContent = reference;
                deliverDialog.querySelector('[data-deliver-address]').textContent = address;
                deliverDialog.showModal();
            });
        });
    }

    // Mark delivered: one submit per click, and a back/forward trip must not leave the button disabled
    // (the payment form does the same after its confirmation, above)
    const submitButtons = [deliverDialog]
        .filter(Boolean)
        .map((dialog) => {
            const submit = dialog.querySelector('button[type="submit"]');
            submit.form.addEventListener('submit', () => { submit.disabled = true; });
            return submit;
        });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) submitButtons.forEach((button) => { button.disabled = false; });
    });
}

// Expenses page: add / edit expense (one modal, two modes), with confirmations
const expenseDialog = document.getElementById('expense-dialog');
const expenseConfirmDialog = document.getElementById('expense-confirm-dialog');
const deleteExpenseDialog = document.getElementById('delete-expense-dialog');

if (expenseDialog && expenseConfirmDialog) {
    [expenseDialog, expenseConfirmDialog, deleteExpenseDialog].filter(Boolean).forEach(wireDialog);

    const peso = (value) => '₱' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const form = expenseDialog.querySelector('[data-expense-form]');
    const amountField = form.elements.amount;
    const descriptionField = form.elements.description;
    const methodField = form.elements._method;
    const idField = form.elements.expense_id;
    const submit = form.querySelector('[data-form-submit]');
    const confirmButton = expenseConfirmDialog.querySelector('[data-expense-confirm]');
    let editing = false;

    const setMode = (mode, date = '') => {
        editing = mode === 'edit';
        expenseDialog.querySelector('[data-form-title]').textContent = editing ? 'Edit Expense' : 'Add Expense';
        expenseDialog.querySelector('[data-form-subtitle]').textContent = editing
            ? `Recorded on ${date}`
            : `Recorded for today, ${expenseDialog.dataset.today}`;
        submit.textContent = editing ? 'Save Changes' : 'Save Expense';
        methodField.disabled = !editing;
        if (!editing) {
            form.action = form.dataset.storeUrl;
            idField.value = '';
        }
    };

    const openAdd = (old = {}) => {
        setMode('add');
        amountField.value = old.amount ?? '';
        descriptionField.value = old.description ?? '';
        expenseDialog.showModal();
        amountField.focus();
    };

    const openEdit = (button, old = {}) => {
        const { id, url, amount, description, date } = button.dataset;
        setMode('edit', date);
        form.action = url;
        idField.value = id;
        amountField.value = old.amount ?? amount;
        descriptionField.value = old.description ?? description;
        expenseDialog.showModal();
        amountField.focus();
    };

    document.querySelectorAll('[data-expense-add-open], [data-add-expense]').forEach((button) => {
        button.addEventListener('click', () => openAdd());
    });

    document.querySelectorAll('[data-expense-edit]').forEach((button) => {
        button.addEventListener('click', () => openEdit(button));
    });

    // Saving asks for confirmation first
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed) {
            submit.disabled = true;
            return;
        }

        event.preventDefault();
        if (!form.reportValidity()) return;

        expenseConfirmDialog.querySelector('[data-confirm-title]').textContent = editing ? 'Save Changes?' : 'Add Expense?';
        expenseConfirmDialog.querySelector('[data-confirm-verb]').textContent = editing ? 'This expense will be changed to' : 'Record';
        expenseConfirmDialog.querySelector('[data-confirm-amount]').textContent = peso(amountField.value);
        expenseConfirmDialog.querySelector('[data-confirm-description]').textContent = descriptionField.value.trim();
        expenseConfirmDialog.showModal();
    });

    confirmButton.addEventListener('click', () => {
        confirmButton.disabled = true;
        form.dataset.confirmed = '1';
        form.requestSubmit();
    });

    // Delete with confirmation
    if (deleteExpenseDialog) {
        const deleteForm = deleteExpenseDialog.querySelector('[data-delete-expense-form]');

        document.querySelectorAll('[data-expense-delete]').forEach((button) => {
            button.addEventListener('click', () => {
                const { url, amount, description, date } = button.dataset;
                deleteForm.action = url;
                deleteExpenseDialog.querySelector('[data-delete-expense-amount]').textContent = '₱' + amount;
                deleteExpenseDialog.querySelector('[data-delete-expense-description]').textContent = description;
                deleteExpenseDialog.querySelector('[data-delete-expense-date]').textContent = date;
                deleteExpenseDialog.showModal();
            });
        });

        deleteForm.addEventListener('submit', () => {
            deleteForm.querySelector('button[type="submit"]').disabled = true;
        });
    }

    // A back/forward trip must not leave anything disabled or half-confirmed
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        delete form.dataset.confirmed;
        submit.disabled = false;
        confirmButton.disabled = false;
        if (deleteExpenseDialog) {
            deleteExpenseDialog.querySelector('[data-delete-expense-form] button[type="submit"]').disabled = false;
        }
    });

    // The server sent us back with validation errors: reopen with what was entered
    if (expenseDialog.dataset.reopen) {
        const { oldExpenseId, oldAmount, oldDescription } = expenseDialog.dataset;
        const editButton = oldExpenseId && document.querySelector(`[data-expense-edit][data-id="${oldExpenseId}"]`);
        const old = { amount: oldAmount, description: oldDescription };

        if (editButton) openEdit(editButton, old);
        else openAdd(old);
    }
}
