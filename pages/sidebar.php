<link rel="stylesheet" href="../estilos/sidebar.css">
<link rel="stylesheet" href="../estilos/sidebar.css">
<div class="sidebar-trigger"></div>
<div class="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <img src="../public/logocrm.png" alt="CRM Logo" style="width: 50px; height: auto;">
            <h2 style="margin: 0; font-size: 15px;">CRM INMOBILIARIA</h2>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="/pages/index.php" class="nav-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <line x1="9" y1="9" x2="15" y2="9" />
                <line x1="9" y1="15" x2="15" y2="15" />
            </svg>
            Dashboard
        </a>
        <a href="/pages/inicio.php" class="nav-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9,22 9,12 15,12 15,22" />
            </svg>
            Inicio
        </a>
        <a href="/pages/propiedades.php" class="nav-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                <rect x="9" y="6" width="6" height="4" />
                <path d="M9 18h6" />
                <path d="M9 14h6" />
            </svg>
            Propiedades
        </a>
        <a href="/pages/interacciones.php" class="nav-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            Interacciones
        </a>
    </nav>
    <div class="sidebar-footer">
        <button type="button" id="theme-toggle" class="theme-toggle" data-theme="dark">
            <span class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4" />
                    <line x1="12" y1="2" x2="12" y2="4" />
                    <line x1="12" y1="20" x2="12" y2="22" />
                    <line x1="4.93" y1="4.93" x2="6.34" y2="6.34" />
                    <line x1="17.66" y1="17.66" x2="19.07" y2="19.07" />
                    <line x1="2" y1="12" x2="4" y2="12" />
                    <line x1="20" y1="12" x2="22" y2="12" />
                    <line x1="4.93" y1="19.07" x2="6.34" y2="17.66" />
                    <line x1="17.66" y1="6.34" x2="19.07" y2="4.93" />
                </svg>
            </span>
            <span class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 0 1 12.21 3 7 7 0 1 0 21 12.79z" />
                </svg>
            </span>
            <span class="theme-toggle__label">Modo claro</span>
        </button>
    </div>
</div>
<script>
    (function() {
        const storageKey = 'crm-dashboard-theme';
        const body = document.body;
        const toggle = document.getElementById('theme-toggle');
        if (!toggle || !body) return;
        const label = toggle.querySelector('.theme-toggle__label');

        const setTheme = (mode) => {
            if (mode === 'light') {
                body.classList.add('theme-light');
                toggle.dataset.theme = 'light';
                if (label) label.textContent = 'Modo oscuro';
            } else {
                body.classList.remove('theme-light');
                toggle.dataset.theme = 'dark';
                if (label) label.textContent = 'Modo claro';
            }
            try {
                localStorage.setItem(storageKey, mode);
            } catch (err) {}
        };

        const stored = (() => {
            try {
                return localStorage.getItem(storageKey);
            } catch (err) {
                return null;
            }
        })();
        const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        const initial = stored === 'light' || (!stored && prefersLight) ? 'light' : 'dark';
        setTheme(initial);

        toggle.addEventListener('click', () => {
            const next = body.classList.contains('theme-light') ? 'dark' : 'light';
            setTheme(next);
        });
    })();
</script>