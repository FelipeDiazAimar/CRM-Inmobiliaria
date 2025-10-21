<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Dashboard Principal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --accent:#22c55e; }
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:#000000;color:var(--text);min-height:100vh;display:flex;flex-direction:column}
        header{padding:24px 20px;border-bottom:1px solid #1f2937;background:#000000;text-align:center}
        .container{max-width:800px;margin:0 auto;padding:24px 16px;flex:1;display:flex;flex-direction:column;justify-content:center}
        h1{margin:0;font-size:32px;margin-bottom:8px;text-align:center}
        .subtitle{color:var(--muted);font-size:18px;margin-bottom:40px;text-align:center}
        .grid{display:flex;flex-direction:column;gap:20px;max-width:600px;margin:0 auto}
        .card{background:rgba(17,24,39,.7);backdrop-filter:blur(8px);border:1px solid #1f2937;border-radius:16px;padding:32px;text-align:center;transition:transform 0.2s, box-shadow 0.2s}
        .card:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.3)}
        .card h2{margin:0 0 16px;font-size:24px}
        .card p{margin:0 0 24px;color:var(--muted)}
        .btn{display:inline-block;background:linear-gradient(135deg,#22c55e,#16a34a);color:white;padding:12px 40px;border-radius:8px;text-decoration:none;font-weight:600;transition:background 0.2s;backdrop-filter:blur(10px);display:flex;align-items:center;gap:8px;justify-content:center}
        .btn:hover{background:linear-gradient(135deg,#16a34a,#15803d)}
        .sidebar{position:fixed;left:-250px;top:0;width:250px;height:100vh;background:#000000;color:#e5e7eb;padding:20px;box-sizing:border-box;border-right:1px solid #1f2937;z-index:1001;transition:left 0.3s}
        .sidebar-header h2{margin:0 0 20px;font-size:24px;color:#22c55e}
        .sidebar-nav{display:flex;flex-direction:column;gap:10px}
        .nav-link{display:flex;align-items:center;gap:10px;color:#94a3b8;text-decoration:none;padding:10px;border-radius:8px;transition:background 0.2s}
        .nav-link:hover{background:rgba(34,197,94,.1);color:#22c55e}
        .sidebar-trigger{position:fixed;left:0;top:0;width:200px;height:100vh;z-index:1000;background:rgba(0,0,0,0.1)}
        .sidebar-trigger:hover + .sidebar, .sidebar:hover{left:0}
        .main-content{padding:24px 16px;flex:1;display:flex;flex-direction:column;justify-content:center;max-width:100vw}
        footer{text-align:center;padding:20px;color:var(--muted);font-size:14px;margin:0 auto}
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <main class="main-content">
        <div class="container">
            <h1>Bienvenido al Dashboard Principal</h1>
            <p class="subtitle">Selecciona una sección para visualizar las analíticas de tu CRM inmobiliario</p>
            <div class="grid">
                <div class="card">
                    <h2>Inicio</h2>
                    <p>Visualiza métricas generales, KPIs y estadísticas del sistema.</p>
                    <a href="/inicio.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg> Ir a Inicio</a>
                </div>
                <div class="card">
                    <h2>Propiedades</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las propiedades y los clientes.</p>
                    <a href="/propiedades.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><rect x="9" y="6" width="6" height="4"/><path d="M9 18h6"/><path d="M9 14h6"/></svg> Ir a Propiedades</a>
                </div>
                <div class="card">
                    <h2>Interacciones</h2>
                    <p>Visualiza métricas, KPIs y estadísticas relacionadas con las interacciones de agentes y clientes.</p>
                    <a href="/interacciones.php" class="btn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Ir a Interacciones</a>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
