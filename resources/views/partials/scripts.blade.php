<script>
    // Toasts (partials.toasts): gone after 4 seconds, or straight away when clicked
    const dismissToast = (toast) => {
        toast.classList.add('leaving');
        setTimeout(() => toast.remove(), 200);
    };
    const startToastTimer = (toast) => setTimeout(() => dismissToast(toast), 4000);
    document.querySelectorAll('#toasts .toast').forEach(startToastTimer);
    document.addEventListener('click', (e) => {
        const toast = e.target.closest('.toast');
        if (toast) dismissToast(toast);
    });

    // From Livewire components: $this->dispatch('notify', message: '...', type: 'error')
    window.addEventListener('notify', (e) => {
        const { message = '', type = 'success' } = e.detail[0] ?? e.detail;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.textContent = message;
        document.getElementById('toasts').appendChild(toast);
        startToastTimer(toast);
    });

    // Hidden admin entrance: Ctrl+Shift+L
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            window.location.href = @json(auth('admin')->check() ? route('admin.dashboard') : route('admin.login'));
        }
    });

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

    // Forms marked data-require-filled: gray, unclickable submit until every required field has a value.
    // Lists marked data-password-rules: hidden until a password is typed, then each rule turns green once met.
    const syncAuthForms = () => {
        document.querySelectorAll('form[data-require-filled]:not([data-submitted])').forEach((form) => {
            const filled = [...form.querySelectorAll('[required]')].every((input) => input.value.trim() !== '');
            form.querySelector('[type="submit"]').disabled = ! filled;
        });
        document.querySelectorAll('[data-password-rules]').forEach((list) => {
            const password = document.getElementById(list.dataset.passwordRules).value;
            list.querySelectorAll('[data-rule]').forEach((rule) => {
                rule.classList.toggle('met', new RegExp(rule.dataset.rule, 'u').test(password));
            });
            list.hidden = password === '';
        });
    };
    syncAuthForms();
    document.addEventListener('input', syncAuthForms);

    // A server error under a field is out of date once the user edits that field
    document.addEventListener('input', (e) => {
        e.target.closest('.field')?.querySelector('.field-error')?.remove();
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

        syncAuthForms();
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
</script>
