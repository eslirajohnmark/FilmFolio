<script>
    // Toasts dispatched from Livewire components: $this->dispatch('notify', message: '...')
    window.addEventListener('notify', (e) => {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = e.detail.message ?? (e.detail[0]?.message ?? '');
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    });

    // Hidden admin entrance: Ctrl+Shift+L
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            window.location.href = @json(auth('admin')->check() ? route('admin.dashboard') : route('admin.login'));
        }
    });

    @if(request()->routeIs('admin.*'))
        // Admin pages: Ctrl+Shift+U goes back to the member side (the admin stays signed in)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'u') {
                e.preventDefault();
                window.location.href = @json(route('home'));
            }
        });
    @endif

    // "Show" / "Hide" toggles on password fields
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.toggle-password');
        if (! toggle) return;
        const input = document.getElementById(toggle.dataset.target);
        const hidden = input.type === 'password';
        input.dataset.wasPassword = '1';
        input.type = hidden ? 'text' : 'password';
        toggle.textContent = hidden ? 'Hide' : 'Show';
    });

    // Close the account menu when clicking elsewhere
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.nav-menu[open]').forEach((menu) => {
            if (! menu.contains(e.target)) menu.removeAttribute('open');
        });
    });

    // Forms marked data-submit-once: disable the button after the first submit
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (! form.matches('[data-submit-once]')) return;
        if (form.dataset.submitted) { e.preventDefault(); return; }
        form.dataset.submitted = '1';
        const button = form.querySelector('[type="submit"]');
        if (button) {
            button.dataset.idleText = button.textContent;
            button.disabled = true;
            if (button.dataset.loadingText) button.textContent = button.dataset.loadingText;
        }
    });
    // Coming back with the browser's Back button restores the page as it was; re-enable it
    window.addEventListener('pageshow', (e) => {
        // Restored from the back/forward cache instead of loaded: ask the server again, so a
        // signed-in visitor going Back to a login page is sent home and never sees that page.
        if (e.persisted) {
            window.location.reload();
            return;
        }

        // Never show what was typed into sign-in and sign-up forms again (browsers like to
        // refill them on Back): reset to the page's own values and blank every password.
        document.querySelectorAll('form[data-clear-on-back]').forEach((form) => {
            form.reset();
            form.querySelectorAll('input[type="password"], input[data-was-password]').forEach((input) => {
                input.value = '';
                if (input.dataset.wasPassword) input.type = 'password';
            });
            form.querySelectorAll('.toggle-password').forEach((toggle) => { toggle.textContent = 'Show'; });
        });

        document.querySelectorAll('form[data-submit-once][data-submitted]').forEach((form) => {
            delete form.dataset.submitted;
            const button = form.querySelector('[type="submit"]');
            if (button) {
                button.disabled = false;
                if (button.dataset.idleText) button.textContent = button.dataset.idleText;
            }
        });
    });

    // Navbar search: the magnifier opens the field; clicking it again with text searches
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.nav-search-toggle');
        if (! toggle) return;
        const form = toggle.closest('.nav-search');
        const input = form.querySelector('input');
        if (form.classList.contains('open') && input.value.trim() !== '') {
            form.submit();
            return;
        }
        form.classList.add('open');
        input.focus();
    });
    document.addEventListener('focusout', (e) => {
        const form = e.target.closest?.('.nav-search');
        if (! form || e.target.value?.trim()) return;
        setTimeout(() => {
            if (! form.contains(document.activeElement)) form.classList.remove('open');
        }, 150);
    });

    // ---- Top banner: dismiss button, and it fades away on its own ----
    document.querySelectorAll('[data-banner]').forEach((banner) => {
        const hide = () => {
            banner.classList.add('is-hiding');
            setTimeout(() => banner.remove(), 300);
        };
        banner.querySelector('.banner-close')?.addEventListener('click', hide);
        setTimeout(hide, banner.classList.contains('banner-error') ? 7000 : 5000);
    });

    // ---- Sign-in / sign-up buttons: gray and unclickable until every required field has input ----
    const isFilled = (input) => {
        if (input.value.trim() !== '') return true;
        try { return input.matches(':autofill'); } catch { try { return input.matches(':-webkit-autofill'); } catch { return false; } }
    };
    const updateSubmitGate = (form) => {
        if (! form || form.dataset.submitted) return;
        const button = form.querySelector('[type="submit"]');
        if (! button) return;
        const ready = [...form.querySelectorAll('input[required]')].every(isFilled);
        button.disabled = ! ready;
        button.classList.toggle('is-ready', ready);
    };
    const gateAll = () => document.querySelectorAll('form[data-require-filled]').forEach(updateSubmitGate);
    gateAll();
    window.addEventListener('pageshow', gateAll);
    [300, 1000].forEach((delay) => setTimeout(gateAll, delay)); // browser autofill fills in late
    document.addEventListener('input', (e) => updateSubmitGate(e.target.closest?.('form[data-require-filled]')));
    document.addEventListener('change', (e) => updateSubmitGate(e.target.closest?.('form[data-require-filled]')));

    // Required fields left empty turn red ("This field is required.")
    document.addEventListener('focusout', (e) => {
        const input = e.target;
        if (! input.matches?.('form[data-require-filled] input[required]')) return;
        input.closest('.field')?.classList.toggle('missing', ! isFilled(input));
    });
    document.addEventListener('input', (e) => {
        if (e.target.matches?.('input[required]') && isFilled(e.target)) {
            e.target.closest('.field')?.classList.remove('missing');
        }
    });

    // ---- New passwords: tick off each requirement as it's met ----
    const passwordRules = {
        length: (v) => [...v].length >= 8,
        upper: (v) => /\p{Lu}/u.test(v),
        lower: (v) => /\p{Ll}/u.test(v),
        number: (v) => /\p{N}/u.test(v),
        symbol: (v) => /[\p{Z}\p{S}\p{P}]/u.test(v),
    };
    const updatePasswordRules = (input) => {
        const list = document.getElementById(input.dataset.passwordRules);
        if (! list) return;
        list.classList.toggle('is-active', input.value !== '');
        list.querySelectorAll('[data-rule]').forEach((item) => {
            item.classList.toggle('met', passwordRules[item.dataset.rule](input.value));
        });
    };
    const updatePasswordMatch = (confirm) => {
        const output = document.getElementById(confirm.id + '-match');
        const password = document.getElementById(confirm.dataset.matches);
        if (! output || ! password) return;
        if (confirm.value === '') {
            output.textContent = '';
            output.className = 'pw-match';
            return;
        }
        const same = confirm.value === password.value;
        output.textContent = same ? '✓ Passwords match' : '✗ Passwords do not match';
        output.className = 'pw-match ' + (same ? 'ok' : 'bad');
    };
    document.addEventListener('input', (e) => {
        const input = e.target;
        if (input.dataset?.passwordRules) {
            updatePasswordRules(input);
            document.querySelectorAll(`[data-matches="${input.id}"]`).forEach(updatePasswordMatch);
        }
        if (input.dataset?.matches) updatePasswordMatch(input);
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-password-rules]').forEach(updatePasswordRules);
        document.querySelectorAll('[data-matches]').forEach(updatePasswordMatch);
    });

    // ---- Live updates: when an admin adds a film or changes a list, open pages update on their own ----
    // The page asks /live for the content version every few seconds (every minute in a background
    // tab, which also keeps the member's "last seen" current). When it changes, the page fetches
    // itself and swaps in the fresh <main>, keeping the scroll position. It never swaps while
    // someone is typing, has unsaved changes or has a menu open; it simply tries again next time.
    (() => {
        const live = { url: @json(route('live')), version: @json(app(\App\Services\LiveUpdates::class)->version()) };
        const CHECK_EVERY_MS = 5000;
        const CHECK_EVERY_HIDDEN_MS = 60000;
        let lastCheck = 0;
        let checking = false;
        let unsaved = false;

        // Typing in a plain form means unsaved changes. Livewire fields keep their state on the server.
        document.addEventListener('input', (e) => { if (! e.target.closest?.('[wire\\:id]')) unsaved = true; });
        document.addEventListener('submit', () => { unsaved = false; });
        const userIsBusy = () => unsaved
            || document.activeElement?.matches?.('input, textarea, select, [contenteditable="true"]')
            || document.querySelector('details[open], .navbar.open, dialog[open]') !== null;

        // Scripts inserted with new HTML don't run by themselves; recreate them so they do.
        const runScripts = (root) => root.querySelectorAll('script').forEach((old) => {
            const script = document.createElement('script');
            [...old.attributes].forEach((attribute) => script.setAttribute(attribute.name, attribute.value));
            script.textContent = old.textContent;
            old.replaceWith(script);
        });

        const refreshContent = async () => {
            const response = await fetch(location.href, {
                headers: { @json(\App\Services\LiveUpdates::REFRESH_HEADER): '1', Accept: 'text/html' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (! response.ok || response.redirected) return false; // e.g. signed out in the meantime
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = page.querySelector('main');
            const current = document.querySelector('main');
            if (! fresh || ! current || userIsBusy()) return false;

            const scrollY = window.scrollY;
            const imported = document.importNode(fresh, true);
            current.replaceWith(imported);
            runScripts(imported);
            document.title = page.title;
            window.scrollTo(0, scrollY);
            return true;
        };

        // Pages that count everything (the admin dashboard, activity) also refresh on a timer,
        // marked with <div data-refresh-every="15">, since new reviews or members don't change the version.
        let lastRefresh = Date.now();
        const refreshIsDue = () => {
            const seconds = Number(document.querySelector('[data-refresh-every]')?.dataset.refreshEvery || 0);
            return seconds > 0 && Date.now() - lastRefresh >= seconds * 1000;
        };

        const check = async () => {
            const every = document.hidden ? CHECK_EVERY_HIDDEN_MS : CHECK_EVERY_MS;
            if (checking || Date.now() - lastCheck < every - 250) return;
            checking = true;
            lastCheck = Date.now();
            try {
                const { version } = await (await fetch(live.url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' })).json();
                if ((version !== live.version || refreshIsDue()) && ! document.hidden && ! userIsBusy() && await refreshContent()) {
                    live.version = version;
                    lastRefresh = Date.now();
                }
            } catch {
                // Offline, or the server is restarting: try again on the next check.
            } finally {
                checking = false;
            }
        };
        setInterval(check, 1000); // check() spaces the requests out itself
        document.addEventListener('visibilitychange', () => { if (! document.hidden) { lastCheck = 0; check(); } });
    })();

    // ---- "Offline · 5 minutes ago" labels keep counting up without a reload ----
    (() => {
        const UNITS = [['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60], ['second', 1]];
        const timeAgo = (seconds) => {
            const [unit, size] = UNITS.find(([, size]) => seconds >= size) ?? ['second', 1];
            const count = Math.max(1, Math.floor(seconds / size));
            return `${count} ${unit}${count === 1 ? '' : 's'} ago`;
        };
        setInterval(() => document.querySelectorAll('[data-seen-at]').forEach((label) => {
            label.shownAt ??= Date.now();
            // Server-side elapsed time plus time on this page, so a wrong clock on this device doesn't matter.
            const seconds = (label.dataset.renderedAt - label.dataset.seenAt + Date.now() - label.shownAt) / 1000;
            label.textContent = timeAgo(seconds);
        }), 1000);
    })();
</script>
