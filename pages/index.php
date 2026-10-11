<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Dashboard Principal</title>
    <link rel="icon" type="image/svg+xml" href="../public/favicon.svg">
    <link rel="icon" type="image/png" href="../public/logocrm.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilos/index.css">
    <style>
        /* AGENTE C-6: hub responsive sin tocar estilos/index.css */
        .hub-grid { width: 100%; }
        @media (min-width: 860px) {
            .hub-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                max-width: 920px;
            }
            .hub-grid .card:last-child:nth-child(odd) {
                grid-column: 1 / -1;
                max-width: 600px;
                margin: 0 auto;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <video class="background-video" autoplay muted loop playsinline>
        <source src="../public/background.mp4" type="video/mp4">
    </video>
    <?php include('sidebar.php'); ?>
    <main class="main-content">
        <div class="container">
            <img src="../public/logocrm.png" alt="CRM Logo" class="logo" onerror="this.onerror=null;this.src='../public/logo-crm.svg'">
            <h1>Bienvenido al Dashboard Principal</h1>
            <p class="subtitle">Selecciona una sección para visualizar las analíticas de tu CRM inmobiliario</p>
            <div class="grid hub-grid">
                <div class="card">
                    <h2>Inicio</h2>
                    <p>Visualiza métricas generales, KPIs y estadísticas del sistema.</p>
                    <a href="/pages/inicio.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9,22 9,12 15,12 15,22" />
                        </svg> Ir a Inicio</a>
                </div>
                <div class="card">
                    <h2>Propiedades</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las propiedades y los clientes.</p>
                    <a href="/pages/propiedades.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                            <rect x="9" y="6" width="6" height="4" />
                            <path d="M9 18h6" />
                            <path d="M9 14h6" />
                        </svg> Ir a Propiedades</a>
                </div>
                <div class="card">
                    <h2>Interacciones</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las interacciones de agentes y clientes.</p>
                    <a href="/pages/interacciones.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg> Ir a Interacciones</a>
                </div>
                <div class="card">
                    <h2>Clientes</h2>
                    <p>Gestiona la cartera de clientes, búsquedas y fichas de contacto.</p>
                    <a href="/pages/clientes.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg> Ir a Clientes</a>
                </div>
                <div class="card">
                    <h2>Agentes</h2>
                    <p>Consulta el equipo comercial, asignaciones y rendimiento por agente.</p>
                    <a href="/pages/agentes.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2" />
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                        </svg> Ir a Agentes</a>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
</body>

</html>
