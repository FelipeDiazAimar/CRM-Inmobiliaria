﻿<!doctype html>
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
        body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#0b1220,#0f172a);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
        header{padding:24px 20px;border-bottom:1px solid #1f2937;background:#0b1220;text-align:center}
        .container{max-width:800px;margin:0 auto;padding:24px 16px;flex:1;display:flex;flex-direction:column;justify-content:center}
        h1{margin:0;font-size:32px;margin-bottom:8px}
        .subtitle{color:var(--muted);font-size:18px;margin-bottom:40px}
        .grid{display:grid;gap:20px;grid-template-columns:repeat(auto-fit,minmax(250px,1fr))}
        .card{background:rgba(17,24,39,.7);backdrop-filter:blur(8px);border:1px solid #1f2937;border-radius:16px;padding:32px;text-align:center;transition:transform 0.2s, box-shadow 0.2s}
        .card:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.3)}
        .card h2{margin:0 0 16px;font-size:24px}
        .card p{margin:0 0 24px;color:var(--muted)}
        .btn{display:inline-block;background:linear-gradient(135deg,#22c55e,#16a34a);color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;transition:background 0.2s}
        .btn:hover{background:linear-gradient(135deg,#16a34a,#15803d)}
        footer{text-align:center;padding:20px;color:var(--muted);font-size:14px}
    </style>
</head>
<body>
    <header>
        <h1>CRM Inmobiliaria</h1>
    </header>
    <main class="container">
        <h1>Bienvenido al Dashboard Principal</h1>
        <p class="subtitle">Selecciona una sección para gestionar tu CRM inmobiliario</p>
        <div class="grid">
            <div class="card">
                <h2>Inicio</h2>
                <p>Visualiza métricas generales, KPIs y estadísticas del sistema.</p>
                <a href="/inicio.php" class="btn">Ir a Inicio</a>
            </div>
            <div class="card">
                <h2>Propiedades</h2>
                <p>Gestiona las propiedades disponibles, agrega nuevas o edita existentes.</p>
                <a href="/propiedades.php" class="btn">Ir a Propiedades</a>
            </div>
            <div class="card">
                <h2>Interacciones</h2>
                <p>Revisa y administra las interacciones con clientes y agentes.</p>
                <a href="/interacciones.php" class="btn">Ir a Interacciones</a>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
