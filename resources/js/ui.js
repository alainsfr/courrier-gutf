// Progressive enhancement: native forms and confirmations remain usable without JS.
// Clear one-time secrets before a browser can preserve the page in its back/forward cache.
window.addEventListener('pagehide', () => {
    document.querySelectorAll('[data-recovery-secret]').forEach((element) => { element.textContent = '•••• •••• •••• ••••'; });
});
document.querySelectorAll('[data-ui-dialog-open]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById(button.dataset.uiDialogOpen)?.showModal());
});
document.querySelectorAll('[data-ui-dialog-close]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
});
const confirmDialog = document.querySelector('[data-ui-confirm-dialog]');
let resolveConfirmation = null;
const closeConfirmation = (accepted) => {
    const resolve = resolveConfirmation;
    resolveConfirmation = null;
    confirmDialog?.close();
    resolve?.(accepted);
};
window.uiConfirmMessage = (message) => {
    if (!confirmDialog?.showModal) return Promise.resolve(window.confirm(message));
    if (resolveConfirmation) return Promise.resolve(false);
    return new Promise((resolve) => {
        resolveConfirmation = resolve;
        confirmDialog.querySelector('[data-ui-confirm-message]').textContent = message;
        confirmDialog.showModal();
        confirmDialog.querySelector('[data-ui-confirm-cancel]').focus();
    });
};
if (confirmDialog?.showModal) {
    confirmDialog.querySelector('[data-ui-confirm-cancel]').addEventListener('click', () => closeConfirmation(false));
    confirmDialog.querySelector('[data-ui-confirm-accept]').addEventListener('click', () => closeConfirmation(true));
    confirmDialog.addEventListener('cancel', (event) => { event.preventDefault(); closeConfirmation(false); });
    const approved = new WeakSet();
    document.querySelectorAll('form[data-confirm-message]').forEach((form) => {
        form.onsubmit = null; // The inline native confirmation remains the no-JS fallback.
        if (form.hasAttribute('data-delete-message')) return; // AJAX deletion asks once in its delegated handler.
        form.addEventListener('submit', async (event) => {
            if (approved.delete(form)) return;
            event.preventDefault();
            const submitter = event.submitter;
            if (await window.uiConfirmMessage(form.dataset.confirmMessage)) {
                approved.add(form);
                form.requestSubmit(submitter || undefined);
            }
        });
    });
}

// Make overflowing result tables discoverable and usable with a keyboard.
document.querySelectorAll('.ui-table-scroll').forEach((container) => {
    const hint = document.createElement('p');
    hint.className = 'ui-scroll-hint';
    hint.textContent = document.body.dataset.tableHint || '';
    container.after(hint);
    const update = () => {
        const overflow = container.scrollWidth > container.clientWidth + 1;
        hint.hidden = !overflow;
        container.tabIndex = overflow ? 0 : -1;
        if (overflow) {
            container.setAttribute('role', 'region');
            container.setAttribute('aria-label', document.body.dataset.tableRegion || '');
        } else {
            container.removeAttribute('role');
            container.removeAttribute('aria-label');
        }
    };
    new ResizeObserver(update).observe(container);
    update();
});

// Error summaries should lead directly to the fields that need attention.
const errorFields = JSON.parse(document.body.dataset.errorFields || '[]');
document.querySelectorAll('.ui-input[name]').forEach((input) => {
    if (errorFields.includes(input.name)) input.setAttribute('aria-invalid', 'true');
});
if (errorFields.length) {
    const summary = document.querySelector('.ui-alert-error[role="alert"]');
    summary?.setAttribute('tabindex', '-1');
    summary?.focus();
}
