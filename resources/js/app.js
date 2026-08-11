import 'bootstrap-icons/font/bootstrap-icons.css';

// ---------------------------------------------------------------------------
// ADMS — application shell behaviour (sidebar, confirmations, table filters)
// ---------------------------------------------------------------------------

(function () {
    'use strict';

    var body = document.body;
    var sidebar = document.getElementById('appSidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarOverlay = document.getElementById('sidebarOverlay');

    var STORAGE_KEY = 'adms.sidebar.collapsed';
    var isCollapsed = localStorage.getItem(STORAGE_KEY) === '1';

    function isMobile() {
        return window.innerWidth < 992;
    }

    function syncTooltips() {
        if (!window.bootstrap || !window.bootstrap.Tooltip) return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            var tip = window.bootstrap.Tooltip.getInstance(el);
            if (isCollapsed && !isMobile()) {
                if (!tip) new window.bootstrap.Tooltip(el);
            } else if (tip) {
                tip.dispose();
            }
        });
    }

    function refresh() {
        if (isMobile()) {
            body.classList.remove('sidebar-collapsed');
        } else {
            body.classList.toggle('sidebar-collapsed', isCollapsed);
        }
        body.classList.toggle('sidebar-mobile-open', false);
        syncTooltips();
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (isMobile()) {
                body.classList.toggle('sidebar-mobile-open');
            } else {
                isCollapsed = !isCollapsed;
                localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
                refresh();
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            body.classList.remove('sidebar-mobile-open');
        });
    }

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) {
                    body.classList.remove('sidebar-mobile-open');
                }
            });
        });
    }

    window.addEventListener('resize', refresh);
    refresh();

    // -----------------------------------------------------------------------
    // Client-side table filter (input[data-filter-table])
    // -----------------------------------------------------------------------
    document.querySelectorAll('input[data-filter-table]').forEach(function (input) {
        input.addEventListener('input', function () {
            var table = document.querySelector(this.getAttribute('data-filter-table'));
            if (!table) return;
            var term = this.value.trim().toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            var visible = 0;

            rows.forEach(function (row) {
                if (row.getAttribute('data-empty-row') !== null) return;
                var match = row.textContent.toLowerCase().indexOf(term) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            var empty = table.querySelector('tbody tr[data-empty-row]');
            if (empty) {
                empty.style.display = visible === 0 ? '' : 'none';
            }
        });
    });

    // -----------------------------------------------------------------------
    // Global confirmation dialog (form[data-confirm="message"])
    // -----------------------------------------------------------------------
    var confirmForm = null;
    var confirmModalEl = document.getElementById('globalConfirmModal');
    var confirmBtn = document.getElementById('globalConfirmBtn');

    function showConfirm(message, form) {
        if (confirmModalEl && window.bootstrap) {
            var msg = document.getElementById('globalConfirmMessage');
            if (msg) msg.textContent = message;
            confirmForm = form;
            window.bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
            return true;
        }
        return false;
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (this.dataset.confirmed === '1') return;
            e.preventDefault();
            e.stopPropagation();
            var message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!showConfirm(message, this)) {
                // Native fallback when the shared modal is unavailable
                if (window.confirm(message)) {
                    this.dataset.confirmed = '1';
                    this.requestSubmit();
                }
            }
        });
    });

    if (confirmBtn && confirmModalEl) {
        confirmBtn.addEventListener('click', function () {
            if (confirmForm) {
                confirmForm.dataset.confirmed = '1';
                confirmForm.requestSubmit();
                confirmForm = null;
            }
            window.bootstrap.Modal.getInstance(confirmModalEl).hide();
        });
    }

    // -----------------------------------------------------------------------
    // Auto-dismiss flash alerts
    // -----------------------------------------------------------------------
    document.querySelectorAll('.alert-autodismiss').forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && window.bootstrap.Alert) {
                window.bootstrap.Alert.getOrCreateInstance(alert).close();
            } else {
                alert.classList.add('fade');
                alert.style.display = 'none';
            }
        }, 5000);
    });
})();
