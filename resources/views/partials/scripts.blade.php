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
</script>
