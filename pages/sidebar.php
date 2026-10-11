<?php
// AGENTE C-6 — Sidebar premium. No emite <html>/<head>: es un fragmento para include() desde /pages/*.
// Activo por basename + drawer mobile + badge demo discreto contra demo_activo() exacta (contrato C-1).
$__crm_current = strtolower((string)(basename((string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: (string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')))));
if ($__crm_current === '' || $__crm_current === 'pages') {
    $__crm_current = 'index.php';
}
$__crm_active = static function (string $file) use ($__crm_current): string {
    return $__crm_current === strtolower($file) ? ' active' : '';
};
$__crm_aria = static function (string $file) use ($__crm_current): string {
    return $__crm_current === strtolower($file) ? ' aria-current="page"' : '';
};
// Contrato C-1: demo_activo() vive en config.php (y opcionalmente demo_data.php). Carga perezosa solo si falta.
if (!function_exists('demo_activo') && is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('demo_activo') && is_file(__DIR__ . '/demo_data.php')) {
    require_once __DIR__ . '/demo_data.php';
}
$__crm_demo = false;
if (function_exists('demo_activo')) {
    try {
        $__crm_demo = (bool) demo_activo();
    } catch (Throwable $__e) {
        $__crm_demo = false;
    }
}
?>
<link rel="stylesheet" href="../estilos/sidebar.css">
<div class="crm-topbar">
    <button type="button" id="sidebar-toggle" class="sidebar-hamburger" aria-label="Abrir menú" aria-expanded="false" aria-controls="crm-sidebar">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="20" y2="17" />
        </svg>
    </button>
    <a class="crm-topbar__brand" href="/pages/index.php" aria-label="Ir al Dashboard">
        <img src="../public/logocrm.png" alt="CRM Inmobiliaria" onerror="this.onerror=null;this.src='../public/logo-crm.svg'">
        <span>CRM Inmobiliaria</span>
    </a>
    <?php if ($__crm_demo): ?><span class="demo-badge" title="Estás viendo datos de demostración">Demo</span><?php endif; ?>
</div>
<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
<aside class="sidebar" id="crm-sidebar" aria-label="Navegación principal">
    <div class="sidebar-header">
        <a class="sidebar-brand" href="/pages/index.php" aria-label="CRM Inmobiliaria - Dashboard">
            <img src="../public/logocrm.png" alt="CRM Logo" onerror="this.onerror=null;this.src='../public/logo-crm.svg'">
            <span class="sidebar-brand__text">CRM Inmobiliaria<small>Panel de gestión</small></span>
        </a>
        <div class="sidebar-header__row">
            <?php if ($__crm_demo): ?><span class="demo-badge" title="Estás viendo datos de demostración">Demo</span><?php endif; ?>
            <button type="button" id="sidebar-close" class="sidebar-close" aria-label="Cerrar menú" aria-controls="crm-sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="6" y1="6" x2="18" y2="18" />
                    <line x1="18" y1="6" x2="6" y2="18" />
                </svg>
            </button>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="/pages/index.php" class="nav-link<?= $__crm_active('index.php') ?>"<?= $__crm_aria('index.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <line x1="9" y1="9" x2="15" y2="9" />
                <line x1="9" y1="15" x2="15" y2="15" />
            </svg>
            Dashboard
        </a>
        <a href="/pages/inicio.php" class="nav-link<?= $__crm_active('inicio.php') ?>"<?= $__crm_aria('inicio.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9,22 9,12 15,12 15,22" />
            </svg>
            Inicio
        </a>
        <a href="/pages/propiedades.php" class="nav-link<?= $__crm_active('propiedades.php') ?>"<?= $__crm_aria('propiedades.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                <rect x="9" y="6" width="6" height="4" />
                <path d="M9 18h6" />
                <path d="M9 14h6" />
            </svg>
            Propiedades
        </a>
        <a href="/pages/interacciones.php" class="nav-link<?= $__crm_active('interacciones.php') ?>"<?= $__crm_aria('interacciones.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
            Interacciones
        </a>
        <a href="/pages/clientes.php" class="nav-link<?= $__crm_active('clientes.php') ?>"<?= $__crm_aria('clientes.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            Clientes
        </a>
        <a href="/pages/agentes.php" class="nav-link<?= $__crm_active('agentes.php') ?>"<?= $__crm_aria('agentes.php') ?>>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
            </svg>
            Agentes
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
</aside>
<script>
    (function() {
        var sidebar = document.getElementById('crm-sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var toggle = document.getElementById('sidebar-toggle');
        var closeBtn = document.getElementById('sidebar-close');
        if (sidebar) {
            var openDrawer = function() {
                sidebar.classList.add('open');
                if (overlay) overlay.classList.add('show');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.setAttribute('aria-label', 'Cerrar menú');
                }
                document.body.classList.add('sidebar-open');
            };
            var closeDrawer = function() {
                sidebar.classList.remove('open');
                if (overlay) overlay.classList.remove('show');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.setAttribute('aria-label', 'Abrir menú');
                }
                document.body.classList.remove('sidebar-open');
            };
            var isOpen = function() { return sidebar.classList.contains('open'); };
            if (toggle) toggle.addEventListener('click', function() { isOpen() ? closeDrawer() : openDrawer(); });
            if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
            if (overlay) overlay.addEventListener('click', closeDrawer);
            sidebar.querySelectorAll('.nav-link').forEach(function(link) {
                link.addEventListener('click', function() { if (window.innerWidth <= 900) closeDrawer(); });
            });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDrawer(); });
        }
        var storageKey = 'crm-dashboard-theme';
        var body = document.body;
        var themeToggle = document.getElementById('theme-toggle');
        if (themeToggle && body) {
            var label = themeToggle.querySelector('.theme-toggle__label');
            var setTheme = function(mode) {
                if (mode === 'light') {
                    body.classList.add('theme-light');
                    themeToggle.dataset.theme = 'light';
                    if (label) label.textContent = 'Modo oscuro';
                } else {
                    body.classList.remove('theme-light');
                    themeToggle.dataset.theme = 'dark';
                    if (label) label.textContent = 'Modo claro';
                }
                try { localStorage.setItem(storageKey, mode); } catch (err) {}
            };
            var stored = null;
            try { stored = localStorage.getItem(storageKey); } catch (err) {}
            var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            setTheme(stored === 'light' || (!stored && prefersLight) ? 'light' : 'dark');
            themeToggle.addEventListener('click', function() {
                setTheme(body.classList.contains('theme-light') ? 'dark' : 'light');
            });
        }
    })();
</script>
