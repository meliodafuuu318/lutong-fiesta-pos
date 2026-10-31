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

// Loading feedback: every request that leaves the page shows it is being sent
const progressBar = document.querySelector('[data-progress]');
let progressTimer;

function showProgress() {
    if (!progressBar) return;

    progressBar.hidden = false;
    // Safety net: if the browser never navigates (a download, a cancelled load) don't leave the bar running
    clearTimeout(progressTimer);
    progressTimer = setTimeout(hideProgress, 20000);
}

function hideProgress() {
    if (progressBar) progressBar.hidden = true;
}

function startLoading(button) {
    if (!button || button.dataset.loading) return;

    const spinner = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    spinner.setAttribute('viewBox', '0 0 24 24');
    spinner.setAttribute('fill', 'none');
    spinner.setAttribute('aria-hidden', 'true');
    spinner.setAttribute('class', 'mr-2 -ml-1 inline-block size-[1.1em] animate-spin align-[-0.2em]');
    spinner.innerHTML = '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3.5" opacity="0.25"/>'
        + '<path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>';

    button.dataset.loading = '1';
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    button.prepend(spinner);
}

function stopLoading(button) {
    if (!button.dataset.loading) return;

    delete button.dataset.loading;
    button.removeAttribute('aria-busy');
    button.disabled = false;
    button.querySelector(':scope > svg.animate-spin')?.remove();
}

// A form that is really being submitted (not held back for a confirmation) puts its buttons
// in a loading state, along with the confirm button of the dialog that sent it.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (event.defaultPrevented || !(form instanceof HTMLFormElement)) return;
    if (form.target === '_blank' || form.hasAttribute('data-no-loading')) return;

    const buttons = new Set([
        event.submitter,
        ...form.querySelectorAll('button[type="submit"]'),
        ...(form.id ? document.querySelectorAll(`button[form="${form.id}"]`) : []),
        ...document.querySelectorAll('dialog[open] button:not([data-dialog-close])'),
    ]);

    showProgress();
    // After the browser has built the form data, so the buttons being disabled can't change it
    setTimeout(() => buttons.forEach((button) => button && startLoading(button)), 0);
});

// Moving to another page of the app
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if (!link || event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if ((link.target && link.target !== '_self') || link.hasAttribute('download')) return;

    const url = new URL(link.href, location.href);
    const samePage = url.pathname === location.pathname && url.search === location.search;

    if (url.origin !== location.origin || (samePage && url.hash)) return;

    showProgress();
});

// Coming back with the back button must not show a page that still looks busy
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;

    hideProgress();
    document.querySelectorAll('button[data-loading]').forEach(stopLoading);
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
        toggle.setAttribute('aria-busy', 'true');
        toggle.classList.add('animate-pulse');

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
            toggle.removeAttribute('aria-busy');
            toggle.classList.remove('animate-pulse');
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

// Dashboard: pending orders (filter, add payment, mark delivered)
const pendingRoot = document.querySelector('[data-pending]');

if (pendingRoot) {
    const peso = (cents) => '₱' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const toCents = (value) => Math.round(Number(value) * 100) || 0;

    // Filter: all / deliveries / payments
    const chips = [...pendingRoot.querySelectorAll('[data-pending-filter]')];
    const cards = [...pendingRoot.querySelectorAll('[data-pending-card]')];
    const filterEmpty = pendingRoot.querySelector('[data-pending-filter-empty]');

    cards.forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, textarea, dialog')) return;

            if (card.dataset.href) window.location.href = card.dataset.href;
        });
    });

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

    // Order items (admin and rider dashboards, cashier pending payments): the dialog is already in the card, so this never touches the network
    pendingRoot.querySelectorAll('[data-order-items-dialog]').forEach((dialog) => {
        wireDialog(dialog);

        dialog.closest('[data-pending-card]')
            ?.querySelector('[data-order-items-open]')
            ?.addEventListener('click', () => dialog.showModal());
    });

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

    // A negative amount is money coming in: shown with a minus sign
    const peso = (value) => {
        const amount = Number(value);
        return (amount < 0 ? '−' : '') + '₱' + Math.abs(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

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

    amountField.addEventListener('input', () => amountField.setCustomValidity(''));

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

        // Any amount but zero: negative amounts are money coming in
        amountField.setCustomValidity(Number(amountField.value) === 0 ? 'Enter an amount other than zero.' : '');
        if (!form.reportValidity()) return;

        expenseConfirmDialog.querySelector('[data-confirm-money-in]').hidden = Number(amountField.value) >= 0;
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
                deleteExpenseDialog.querySelector('[data-delete-expense-amount]').textContent = amount;
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

// Transaction details: destructive actions require confirmation.
const transactionCancelDialog = document.getElementById('transaction-cancel-dialog');
const transactionRefundDialog = document.getElementById('transaction-refund-dialog');

[
    [transactionCancelDialog, '[data-transaction-cancel-open]'],
    [transactionRefundDialog, '[data-transaction-refund-open]'],
].forEach(([dialog, openerSelector]) => {
    if (!dialog) return;

    wireDialog(dialog);
    document.querySelector(openerSelector)?.addEventListener('click', () => dialog.showModal());

    const form = dialog.querySelector('[data-transaction-action-form]');
    form.addEventListener('submit', () => {
        form.querySelector('button[type="submit"]').disabled = true;
    });
});

// Create transaction (cashier dashboard and admin page): build the receipt from the menu
// (tap an item, pick a quantity or a custom serving), then create the transaction
const orderForm = document.getElementById('order-form');
const quantityDialog = document.getElementById('quantity-dialog');
const orderConfirmDialog = document.getElementById('create-transaction-confirm-dialog');

if (orderForm && quantityDialog && orderConfirmDialog) {
    [quantityDialog, orderConfirmDialog].forEach(wireDialog);

    const MAX_QUANTITY = 99;
    const peso = (cents) => '₱' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const toCents = (value) => Math.round(Number(value) * 100) || 0;
    const clampQuantity = (value) => Math.min(MAX_QUANTITY, Math.max(1, Math.floor(Number(value)) || 1));
    const el = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const row = (left, right) => {
        const line = el('div', 'flex items-start justify-between gap-2');
        line.append(el('p', 'text-ink', left), el('p', 'font-semibold text-ink', right));
        return line;
    };

    // The menu, and the receipt: line key => {key, id, mode, quantity | price}. A line is either a number of
    // the usual servings ('quantity') or a custom serving sold for an amount of money ('price', in cents).
    const picks = [...orderForm.querySelectorAll('[data-pick-item]')];
    const menu = new Map(picks.map((button) => [button.dataset.id, {
        id: button.dataset.id,
        name: button.dataset.name,
        price: toCents(button.dataset.price),
        button,
    }]));
    const lines = new Map();
    let nextLineKey = 1;
    const addLine = (line) => {
        const key = String(nextLineKey++);
        lines.set(key, { key, ...line });
        return key;
    };

    const typeRadios = [...orderForm.querySelectorAll('input[name="type"]')];
    const sections = [...orderForm.querySelectorAll('[data-section]')];
    const { payment_method: methodField, payment_amount: amountField, address: addressField, note: noteField } = orderForm.elements;
    const lineInputs = orderForm.querySelector('[data-line-inputs]');
    const itemsError = orderForm.querySelector('[data-items-error]');
    const receipt = {
        empty: orderForm.querySelector('[data-receipt-empty]'),
        items: orderForm.querySelector('[data-receipt-items]'),
        totals: document.querySelectorAll('[data-receipt-total]'),
        count: document.querySelector('[data-receipt-count]'),
        payment: orderForm.querySelector('[data-receipt-payment]'),
        method: orderForm.querySelector('[data-receipt-method]'),
        amount: orderForm.querySelector('[data-receipt-amount]'),
        diff: orderForm.querySelector('[data-receipt-diff]'),
        diffLabel: orderForm.querySelector('[data-receipt-diff-label]'),
        diffValue: orderForm.querySelector('[data-receipt-diff-value]'),
        delivery: orderForm.querySelector('[data-receipt-delivery]'),
        address: orderForm.querySelector('[data-receipt-address]'),
    };

    const currentType = () => typeRadios.find((radio) => radio.checked)?.value ?? 'WALK IN';

    // A walk in always pays. A delivery only does when it was pre-paid, i.e. an amount is entered.
    const paymentOptionalHints = [...document.querySelectorAll('[data-payment-optional]')];
    const isPaying = () => currentType() === 'WALK IN' || amountField.value.trim() !== '';
    const syncPaymentFields = () => {
        const walkIn = currentType() === 'WALK IN';

        methodField.required = amountField.required = walkIn;
        // An unpaid delivery sends no payment at all (a method without an amount is rejected)
        methodField.disabled = !isPaying();
        paymentOptionalHints.forEach((hint) => (hint.hidden = walkIn));
    };
    // Each line with its menu item, what it adds to the total, and how it reads on the receipt
    const receiptLines = () => [...lines.values()].map((line) => {
        const item = menu.get(line.id);
        const custom = line.mode === 'price';

        return {
            ...line,
            name: item.name,
            custom,
            amount: custom ? line.price : item.price * line.quantity,
            label: custom ? `${item.name} (custom)` : `x${line.quantity} ${item.name}`,
            detail: custom ? 'Custom serving' : `${peso(item.price)} each`,
            // How many servings it counts for in the badges (a custom serving counts as one)
            count: custom ? 1 : line.quantity,
        };
    });
    const totalOf = (list) => list.reduce((sum, line) => sum + line.amount, 0);

    // Paid vs total: change when it's more, what's still owed when it's less
    const paymentDiff = (total) => {
        if (amountField.value === '') return null;
        const paid = toCents(amountField.value);
        return paid >= total
            ? { label: 'Change', value: peso(paid - total) }
            : { label: 'Balance Owed', value: peso(total - paid) };
    };

    const hidden = (name, value) => {
        const input = el('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    };

    const render = () => {
        const list = receiptLines();
        const total = totalOf(list);
        const type = currentType();
        const itemCount = list.reduce((sum, line) => sum + line.count, 0);

        // Receipt lines: tap the name to change the quantity, × to take it off
        receipt.empty.hidden = list.length > 0;
        receipt.items.replaceChildren(...list.map((line) => {
            const item = el('li', 'flex items-start gap-2');

            const edit = el('button', 'min-w-0 flex-1 cursor-pointer rounded text-left hover:bg-cream-100');
            edit.type = 'button';
            edit.setAttribute('aria-label', `Change ${line.name} on the receipt`);
            edit.addEventListener('click', () => openQuantity(line.id, 'edit', line.key));
            edit.append(
                el('span', 'block break-words text-sm text-ink', line.label),
                el('span', 'block text-xs text-ink-soft', line.detail),
            );

            const remove = el('button', 'flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-lg font-bold leading-none text-ink-soft hover:bg-danger/10 hover:text-danger', '×');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Remove ${line.name}`);
            remove.addEventListener('click', () => {
                lines.delete(line.key);
                render();
            });

            item.append(edit, el('p', 'text-sm font-semibold text-ink', peso(line.amount)), remove);
            return item;
        }));

        // What gets sent: transaction_items[n][daily_item_id] plus [quantity], or [unit_price] for a custom serving
        lineInputs.replaceChildren(...list.flatMap((line, index) => [
            hidden(`transaction_items[${index}][daily_item_id]`, line.id),
            line.custom
                ? hidden(`transaction_items[${index}][unit_price]`, (line.price / 100).toFixed(2))
                : hidden(`transaction_items[${index}][quantity]`, line.quantity),
        ]));

        // Menu cards show how many are on the receipt
        menu.forEach(({ id, button }) => {
            const quantity = list.filter((line) => line.id === id).reduce((sum, line) => sum + line.count, 0);
            const badge = button.querySelector('[data-line-badge]');
            button.dataset.selected = String(quantity > 0);
            badge.hidden = quantity === 0;
            badge.textContent = quantity;
        });

        receipt.totals.forEach((node) => (node.textContent = peso(total)));
        if (receipt.count) receipt.count.textContent = itemCount === 0 ? 'No items' : `${itemCount} ${itemCount === 1 ? 'item' : 'items'}`;

        syncPaymentFields();
        const paying = isPaying();

        receipt.payment.hidden = !paying;
        receipt.delivery.hidden = type !== 'DELIVERY';

        if (paying) {
            receipt.method.textContent = methodField.selectedOptions[0]?.textContent ?? '';
            receipt.amount.textContent = amountField.value === '' ? '—' : peso(toCents(amountField.value));

            const diff = paymentDiff(total);
            receipt.diff.hidden = diff === null;
            if (diff) {
                receipt.diffLabel.textContent = diff.label;
                receipt.diffValue.textContent = diff.value;
            }
        }

        if (type === 'DELIVERY') {
            receipt.address.textContent = addressField.value.trim() || '—';
        }
    };

    // Only the fields for the chosen type are shown and sent
    const applyType = () => {
        const type = currentType();

        sections.forEach((section) => {
            const active = section.dataset.section === type;
            section.hidden = !active;
            section.querySelectorAll('input, select, textarea').forEach((control) => (control.disabled = !active));
        });

        render();
    };

    // Quantity dialog. 'add' puts more of an item on the receipt, 'edit' changes a line.
    // Each can be by quantity (the usual servings) or a custom serving sold for an amount of money.
    const quantityInput = quantityDialog.querySelector('[data-quantity-input]');
    const priceInput = quantityDialog.querySelector('[data-serving-price-input]');
    const servingError = quantityDialog.querySelector('[data-serving-error]');
    const quantityTotal = quantityDialog.querySelector('[data-quantity-total]');
    const quantityExisting = quantityDialog.querySelector('[data-quantity-existing]');
    const quantityConfirm = quantityDialog.querySelector('[data-quantity-confirm]');
    const modeRadios = [...quantityDialog.querySelectorAll('[data-serving-mode]')];
    const modePanels = [...quantityDialog.querySelectorAll('[data-serving-panel]')];
    let quantityTarget = null;

    const servingMode = () => modeRadios.find((radio) => radio.checked)?.value ?? 'quantity';
    const customPrice = () => toCents(priceInput.value);

    const updateQuantityTotal = () => {
        quantityTotal.textContent = servingMode() === 'price'
            ? peso(customPrice())
            : peso(quantityTarget.item.price * clampQuantity(quantityInput.value));
    };

    const applyServingMode = () => {
        const mode = servingMode();

        modePanels.forEach((panel) => (panel.hidden = panel.dataset.servingPanel !== mode));
        servingError.hidden = true;
        updateQuantityTotal();
    };

    // How many of this item are already on the receipt by quantity (only those can be added to)
    const quantityLineOf = (id) => [...lines.values()].find((line) => line.id === id && line.mode === 'quantity');

    function openQuantity(id, mode, key = null) {
        const item = menu.get(id);
        const line = key ? lines.get(key) : null;
        const existing = quantityLineOf(id)?.quantity ?? 0;
        quantityTarget = { item, mode, key };

        quantityDialog.querySelector('[data-quantity-name]').textContent = item.name;
        quantityDialog.querySelector('[data-quantity-price]').textContent = `${peso(item.price)} each`;
        quantityConfirm.textContent = mode === 'edit' ? 'Update Receipt' : 'Add to Receipt';
        quantityExisting.hidden = mode === 'edit' || existing === 0;
        quantityExisting.textContent = `Already on the receipt: ${existing}`;

        const custom = line?.mode === 'price';
        modeRadios.forEach((radio) => (radio.checked = radio.value === (custom ? 'price' : 'quantity')));
        quantityInput.value = line && !custom ? line.quantity : 1;
        priceInput.value = custom ? (line.price / 100).toFixed(2) : '';

        applyServingMode();
        quantityDialog.showModal();
        (custom ? priceInput : quantityInput).select();
    }

    const confirmQuantity = () => {
        const { item, mode, key } = quantityTarget;

        if (servingMode() === 'price') {
            const price = customPrice();

            if (price <= 0) {
                servingError.hidden = false;
                priceInput.focus();
                return;
            }

            // A custom serving is its own line: replace the one being edited, or add a new one
            if (mode === 'edit') lines.set(key, { key, id: item.id, mode: 'price', price });
            else addLine({ id: item.id, mode: 'price', price });
        } else {
            const quantity = clampQuantity(quantityInput.value);
            const sameItem = quantityLineOf(item.id);

            if (mode === 'edit') lines.set(key, { key, id: item.id, mode: 'quantity', quantity });
            else if (sameItem) sameItem.quantity = Math.min(MAX_QUANTITY, sameItem.quantity + quantity);
            else addLine({ id: item.id, mode: 'quantity', quantity });
        }

        itemsError.hidden = true;
        quantityDialog.close();
        render();
    };

    picks.forEach((button) => button.addEventListener('click', () => openQuantity(button.dataset.id, 'add')));

    const stepQuantity = (step) => {
        quantityInput.value = clampQuantity((Number(quantityInput.value) || 1) + step);
        updateQuantityTotal();
    };

    modeRadios.forEach((radio) => radio.addEventListener('change', () => {
        applyServingMode();
        (servingMode() === 'price' ? priceInput : quantityInput).select();
    }));
    quantityDialog.querySelector('[data-quantity-minus]').addEventListener('click', () => stepQuantity(-1));
    quantityDialog.querySelector('[data-quantity-plus]').addEventListener('click', () => stepQuantity(1));
    quantityInput.addEventListener('input', updateQuantityTotal);
    quantityInput.addEventListener('blur', () => {
        quantityInput.value = clampQuantity(quantityInput.value);
        updateQuantityTotal();
    });
    priceInput.addEventListener('input', () => {
        servingError.hidden = true;
        updateQuantityTotal();
    });
    [quantityInput, priceInput].forEach((input) => input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        confirmQuantity();
    }));
    quantityConfirm.addEventListener('click', confirmQuantity);

    // Order details
    typeRadios.forEach((radio) => radio.addEventListener('change', applyType));
    [methodField, amountField, addressField].forEach((control) => {
        control.addEventListener('input', render);
        control.addEventListener('change', render);
    });

    orderForm.querySelector('[data-use-total]').addEventListener('click', () => {
        amountField.value = (totalOf(receiptLines()) / 100).toFixed(2);
        render();
    });

    // Creating the transaction asks for confirmation first
    orderForm.addEventListener('submit', (event) => {
        if (orderForm.dataset.confirmed) return;

        event.preventDefault();

        const list = receiptLines();
        if (list.length === 0) {
            itemsError.textContent = 'Add at least one item to the transaction.';
            itemsError.hidden = false;
            itemsError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const dialog = orderConfirmDialog;
        const type = currentType();
        const total = totalOf(list);

        dialog.querySelector('[data-confirm-type]').textContent = type === 'WALK IN' ? 'Walk In' : 'Delivery';
        dialog.querySelector('[data-confirm-items]').replaceChildren(...list.map((line) => row(line.label, peso(line.amount))));
        dialog.querySelector('[data-confirm-total]').textContent = peso(total);

        const payment = dialog.querySelector('[data-confirm-payment]');
        const delivery = dialog.querySelector('[data-confirm-delivery]');
        const paying = isPaying();
        payment.hidden = !paying;
        delivery.hidden = type !== 'DELIVERY';

        if (paying) {
            const diff = paymentDiff(total);
            dialog.querySelector('[data-confirm-method]').textContent = methodField.selectedOptions[0].textContent;
            dialog.querySelector('[data-confirm-amount]').textContent = peso(toCents(amountField.value));
            dialog.querySelector('[data-confirm-diff-label]').textContent = diff.label;
            dialog.querySelector('[data-confirm-diff-value]').textContent = diff.value;
        }

        if (type === 'DELIVERY') {
            dialog.querySelector('[data-confirm-address]').textContent = addressField.value.trim();
        }

        const note = noteField.value.trim();
        dialog.querySelector('[data-confirm-note-wrap]').hidden = note === '';
        dialog.querySelector('[data-confirm-note]').textContent = note;

        dialog.showModal();
    });

    const createConfirm = orderConfirmDialog.querySelector('[data-create-transaction-confirm]');

    createConfirm.addEventListener('click', () => {
        orderForm.dataset.confirmed = '1';
        orderForm.requestSubmit();
    });

    // A back/forward trip must not leave the form half-confirmed
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        delete orderForm.dataset.confirmed;
        createConfirm.disabled = false;
    });

    // Start from whatever the server sent back (validation errors) or an empty receipt
    try {
        JSON.parse(orderForm.dataset.initialLines || '[]').forEach((line) => {
            if (!menu.has(line.id)) return;

            if (line.unit_price !== undefined) {
                const price = toCents(line.unit_price);
                if (price > 0) addLine({ id: line.id, mode: 'price', price });
            } else {
                addLine({ id: line.id, mode: 'quantity', quantity: clampQuantity(line.quantity) });
            }
        });
    } catch {
        // No usable saved lines: start empty
    }

    applyType();
}
