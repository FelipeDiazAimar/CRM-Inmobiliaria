<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Dashboard Principal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilos/index.css">
</head>
<body>
    <video class="background-video" autoplay muted loop playsinline>
        <source src="../public/background.mp4" type="video/mp4">
    </video>
    <?php include('sidebar.php'); ?>
    <main class="main-content">
        <div class="container">
            <h1>Bienvenido al Dashboard Principal</h1>
            <p class="subtitle">Selecciona una sección para visualizar las analíticas de tu CRM inmobiliario</p>
            <div class="grid">
                <div class="card">
                    <h2>Inicio</h2>
                    <p>Visualiza métricas generales, KPIs y estadísticas del sistema.</p>
                    <a href="/pages/inicio.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg> Ir a Inicio</a>
                </div>
                <div class="card">
                    <h2>Propiedades</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las propiedades y los clientes.</p>
                    <a href="/pages/propiedades.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><rect x="9" y="6" width="6" height="4"/><path d="M9 18h6"/><path d="M9 14h6"/></svg> Ir a Propiedades</a>
                </div>
                <div class="card">
                    <h2>Interacciones</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las interacciones de agentes y clientes.</p>
                    <a href="/pages/interacciones.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Ir a Interacciones</a>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
