const dialog = document.querySelector('[data-idle-session]');

if (dialog) {
    let expiresAt = Number(dialog.dataset.expiresAt) * 1000;
    let serverOffset = Number(dialog.dataset.serverNow) * 1000 - Date.now();
    const serverNow = () => Date.now() + serverOffset;
    const warningMs = Math.max(1, Number(dialog.dataset.warningSeconds)) * 1000;
    const storageKey = dialog.dataset.storageKey;
    const continueButton = dialog.querySelector('[data-idle-continue]');
    const countdown = dialog.querySelector('[data-idle-countdown]');
    const error = dialog.querySelector('[data-idle-error]');
    let lastSentAt = Date.now();
    let pending = false;
    let leaving = false;
    let checkingExpiry = false;

    const leave = () => {
        if (leaving) return;
        leaving = true;
        // Masquer les informations même si le navigateur est ensuite hors ligne.
        document.documentElement.classList.add('session-expired');
        window.location.replace(dialog.dataset.loginUrl);
    };
    const applyExpiry = (seconds, broadcast = false) => {
        if (!Number.isFinite(seconds) || seconds <= 0) return;
        expiresAt = seconds * 1000;
        if (expiresAt - serverNow() > warningMs && dialog.open) dialog.close();
        if (broadcast) {
            try { localStorage.setItem(storageKey, String(seconds)); } catch (_) {}
        }
    };
    const request = async (activity = false) => {
        if (pending || leaving) return;
        pending = true;
        continueButton.disabled = true;
        try {
            const response = await fetch(activity ? dialog.dataset.activityUrl : dialog.dataset.statusUrl, {
                method: activity ? 'POST' : 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                signal: AbortSignal.timeout(8000),
                headers: {
                    'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            if ([401, 419].includes(response.status) || response.redirected) { leave(); return; }
            if (!response.ok) throw new Error('Session check failed');
            const data = await response.json();
            if (Number.isFinite(Number(data.server_now))) serverOffset = Number(data.server_now) * 1000 - Date.now();
            applyExpiry(Number(data.expires_at), activity);
            if (activity) lastSentAt = Date.now();
            error.hidden = true;
            return true;
        } catch (_) {
            if (dialog.open) error.hidden = false;
            return false;
        } finally {
            pending = false;
            continueButton.disabled = false;
        }
    };
    const verifyExpiry = async () => {
        if (pending || checkingExpiry || leaving) return;
        checkingExpiry = true;
        document.documentElement.classList.add('session-expired');
        // Une activité dans un autre onglet peut avoir repoussé l'échéance.
        if (await request() && serverNow() < expiresAt) {
            document.documentElement.classList.remove('session-expired');
            checkingExpiry = false;
        } else {
            leave();
        }
    };
    const tick = () => {
        const remaining = expiresAt - serverNow();
        if (remaining <= 0) { verifyExpiry(); return; }
        const seconds = Math.ceil(remaining / 1000);
        countdown.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
        if (remaining <= warningMs && !dialog.open) {
            dialog.showModal();
            continueButton.focus();
            request();
        }
    };

    // Aucun ping périodique d'activité : seul un geste réel peut repousser le délai.
    const onActivity = (event) => {
        if (!event.isTrusted || dialog.open || document.visibilityState !== 'visible') return;
        if (serverNow() >= expiresAt) { verifyExpiry(); return; }
        if (Date.now() - lastSentAt >= 30000) request(true);
    };
    ['pointerdown', 'pointermove', 'keydown', 'input', 'wheel', 'touchstart'].forEach((name) => {
        document.addEventListener(name, onActivity, {passive: true});
    });
    continueButton.addEventListener('click', () => request(true));
    dialog.addEventListener('cancel', (event) => event.preventDefault());
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey && event.newValue) applyExpiry(Number(event.newValue));
    });
    window.addEventListener('pageshow', () => {
        if (leaving) { window.location.replace(dialog.dataset.loginUrl); return; }
        if (serverNow() >= expiresAt) verifyExpiry();
        else request();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            if (serverNow() >= expiresAt) verifyExpiry();
            else request();
        }
    });
    applyExpiry(Number(dialog.dataset.expiresAt), true);
    setInterval(tick, 1000);
    tick();
}
