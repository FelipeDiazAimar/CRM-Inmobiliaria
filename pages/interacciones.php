<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Interacciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --accent:#22c55e; }
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#0b1220,#0f172a);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
        header{padding:24px 20px;border-bottom:1px solid #1f2937;background:#0b1220;text-align:center}
        .container{max-width:800px;margin:0 auto;padding:24px 16px;flex:1;display:flex;flex-direction:column;justify-content:center;text-align:center}
        h1{margin:0;font-size:32px;margin-bottom:8px}
        .subtitle{color:var(--muted);font-size:18px;margin-bottom:40px}
        .btn{display:inline-block;background:linear-gradient(135deg,#22c55e,#16a34a);color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;transition:background 0.2s}
        .btn:hover{background:linear-gradient(135deg,#16a34a,#15803d)}
        .sidebar{position:fixed;left:-250px;top:0;width:250px;height:100vh;background:#000000;color:#e5e7eb;padding:20px;box-sizing:border-box;border-right:1px solid #1f2937;z-index:1001;transition:left 0.3s}
        .sidebar-header h2{margin:0 0 20px;font-size:24px;color:#22c55e}
        .sidebar-nav{display:flex;flex-direction:column;gap:10px}
        .nav-link{display:flex;align-items:center;gap:10px;color:#94a3b8;text-decoration:none;padding:10px;border-radius:8px;transition:background 0.2s}
        .nav-link:hover{background:rgba(34,197,94,.1);color:#22c55e}
        .sidebar-trigger{position:fixed;left:0;top:0;width:200px;height:100vh;z-index:100vh;z-index:1000;background:rgba(0,0,0,0.1)}
        .sidebar-trigger:hover + .sidebar, .sidebar:hover{left:0}
        .main-content{padding:24px 16px;flex:1;display:flex;flex-direction:column;justify-content:center;text-align:center;max-width:100vw}
        footer{text-align:center;padding:20px;color:var(--muted);font-size:14px;margin:0 auto}
        footer{text-align:center;padding:20px;color:var(--muted);font-size:14px}
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <header>
        <h1>CRM Inmobiliaria</h1>
    </header>
    <main class="main-content">
        <h1>Sección de Interacciones</h1>
        <p class="subtitle">Esta página está en desarrollo. Aquí podrás gestionar las interacciones.</p>
        <a href="/" class="btn">Volver al Dashboard Principal</a>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
</body>
</html>