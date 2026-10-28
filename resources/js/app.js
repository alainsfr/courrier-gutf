import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './session-idle';

// Prevent a second click while a normal form submission is in progress.
document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.dataset.originalLabel = button.innerHTML;
            button.disabled = true;
            if (button.dataset.busyLabel) button.textContent = button.dataset.busyLabel;
        });
    });
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('button[data-original-label]').forEach((button) => {
            button.innerHTML = button.dataset.originalLabel;
            button.disabled = false;
        });
    });
});
