(() => {
    'use strict';

    const body = document.body;
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    const closeSidebar = () => body.classList.remove('sidebar-open');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-open');
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('.app-sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    // Konfirmasi untuk tindakan yang mengubah data.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Tampilkan/sembunyikan kata sandi.
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!target) {
                return;
            }

            const show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');

            const icon = button.querySelector('i');
            if (icon) {
                icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    });

    // Pilih semua hak akses dalam satu grup.
    document.querySelectorAll('[data-check-group]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const group = toggle.getAttribute('data-check-group');
            document.querySelectorAll(`input[type="checkbox"][data-group="${group}"]`).forEach((checkbox) => {
                checkbox.checked = toggle.checked;
            });
        });
    });

    document.querySelectorAll('[data-check-group]').forEach((toggle) => {
        const group = toggle.getAttribute('data-check-group');
        const checkboxes = Array.from(document.querySelectorAll(`input[type="checkbox"][data-group="${group}"]`));
        const sync = () => {
            toggle.checked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
        };

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', sync));
        sync();
    });

    // Cegah pengiriman ganda pada form.
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            const submit = form.querySelector('button[type="submit"]');
            if (submit && !submit.disabled) {
                submit.disabled = true;
                submit.classList.add('disabled');
            }
        });
    });

    // Alert hilang otomatis setelah beberapa detik.
    window.setTimeout(() => {
        document.querySelectorAll('.app-alert').forEach((alert) => {
            if (window.bootstrap) {
                const instance = window.bootstrap.Alert.getOrCreateInstance(alert);
                instance.close();
            }
        });
    }, 8000);

    // Generate slug dari nama role.
    const roleName = document.getElementById('name');
    const roleSlug = document.getElementById('slug');

    if (roleName && roleSlug && roleSlug.value === '') {
        roleName.addEventListener('input', () => {
            if (roleSlug.dataset.edited === 'true') {
                return;
            }

            roleSlug.value = roleName.value
                .toLowerCase()
                .normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 50);
        });

        roleSlug.addEventListener('input', () => {
            roleSlug.dataset.edited = 'true';
        });
    }
})();
