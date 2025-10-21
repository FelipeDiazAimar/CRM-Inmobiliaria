<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Validar extensión mysqli
$db_ok = true;
$warn  = [];
if (!extension_loaded('mysqli')) {
        $db_ok = false;
        $warn[] = 'La extensión mysqli no está habilitada en PHP';
}

// Evitar que mysqli lance excepciones no capturadas
if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = null;
if ($db_ok) {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_errno) {
                $db_ok = false;
                $warn[] = 'Error de conexión (' . $conn->connect_errno . '): ' . $conn->connect_error . ' | ' . DB_HOST . ':' . DB_PORT . ' DB=' . DB_NAME;
        } else {
                if (defined('DB_CHARSET')) {
                        $conn->set_charset(DB_CHARSET);
                }
        }
}

// Funciones auxiliares
function fetch_key_value($conn, $sql, $key, $val) {
        $out = [];
        if (!$conn) return $out;
        if ($res = $conn->query($sql)) {
                while ($row = $res->fetch_assoc()) {
                        $out[$row[$key]] = (int)$row[$val];
                }
                $res->free();
        }
        return $out;
}

function fetch_series($conn, $sql, $x, $y) {
        $labels = [];
        $data   = [];
        if ($conn && ($res = $conn->query($sql))) {
                while ($row = $res->fetch_assoc()) {
                        $labels[] = $row[$x];
                        $data[]   = (int)$row[$y];
                }
                $res->free();
        }
        return [$labels, $data];
}

// Métricas por defecto (demo)
$usingSample = false;
$kpis = [
        'clientes' => 10,
        'agentes' => 10,
        'propiedades' => 10,
        'transacciones' => 10,
        'monto_total_trans' => 1880000,
        'interacciones' => 15,
];
// Datos demo adaptados al nuevo esquema
$propPorTipo = ['casa'=>3,'departamento'=>3,'terreno'=>1,'oficina'=>1,'galpón'=>1,'local'=>1];
$propPorEstado = ['disponible'=>10];
$transPorTipo = ['venta'=>5,'alquiler'=>5];
$transMontoPorTipo = ['venta'=>1080000,'alquiler'=>800000];
$interPorMedio = ['Teléfono'=>6,'Email'=>4,'WhatsApp'=>5,'Visita'=>2];
$interPorDia_labels = [];
$interPorDia_data = [];
$transPorDia_labels = [];
$transPorDia_data = [];
$citasPorDia_labels = [];
$citasPorDia_data = [];
$clientesPorCiudad = ['Rosario'=>10];
$caractPopulares = ['Pileta'=>2,'Cochera'=>2,'Jardín'=>2,'Balcón'=>2,'Aire acondicionado'=>1,'Amoblada'=>1,'Seguridad 24hs'=>1,'Ascensor'=>1];
$etiquetasPopulares = ['Lujo'=>3,'Céntrica'=>3,'Económica'=>1,'Reciclada'=>1,'Industrial'=>1,'Exclusiva'=>2,'Moderna'=>1,'Con vista'=>1,'Frente al río'=>1,'Oportunidad'=>1];
// Series demo de los últimos 7 días
for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime('today'))->modify("-{$i} days")->format('Y-m-d');
        $interPorDia_labels[] = $d; $interPorDia_data[] = rand(0, 4);
        $transPorDia_labels[] = $d; $transPorDia_data[] = rand(0, 4);
        $citasPorDia_labels[] = $d; $citasPorDia_data[] = rand(0, 4);
}

if ($db_ok) {
                // KPIs reales del nuevo esquema (tablas en MAYÚSCULAS)
                $q1 = $conn->query('SELECT COUNT(*) AS c FROM CLIENTES');
                $q2 = $conn->query('SELECT COUNT(*) AS c FROM AGENTES');
                $q3 = $conn->query('SELECT COUNT(*) AS c FROM PROPIEDADES');
                $q4 = $conn->query('SELECT COUNT(*) AS c, COALESCE(SUM(monto),0) s FROM TRANSACCIONES');
                $q5 = $conn->query('SELECT COUNT(*) AS c FROM INTERACCIONES');
                if ($q1 && $q2 && $q3 && $q4 && $q5) {
                        $kpis['clientes'] = (int)$q1->fetch_assoc()['c'];
                        $kpis['agentes'] = (int)$q2->fetch_assoc()['c'];
                        $kpis['propiedades'] = (int)$q3->fetch_assoc()['c'];
                        $r4 = $q4->fetch_assoc();
                        $kpis['transacciones'] = (int)$r4['c'];
                        $kpis['monto_total_trans'] = (float)$r4['s'];
                        $kpis['interacciones'] = (int)$q5->fetch_assoc()['c'];
                }
                if ($q1) $q1->free(); if ($q2) $q2->free(); if ($q3) $q3->free(); if ($q4) $q4->free(); if ($q5) $q5->free();

                // Distribuciones
                $propPorTipo = fetch_key_value($conn, "SELECT tipo, COUNT(*) cnt FROM PROPIEDADES GROUP BY tipo ORDER BY cnt DESC", 'tipo','cnt');
                $propPorEstado = fetch_key_value($conn, "SELECT estado, COUNT(*) cnt FROM PROPIEDADES GROUP BY estado ORDER BY cnt DESC", 'estado','cnt');
                $transPorTipo = fetch_key_value($conn, "SELECT tipo, COUNT(*) cnt FROM TRANSACCIONES GROUP BY tipo", 'tipo','cnt');
                $transMontoPorTipo = fetch_key_value($conn, "SELECT tipo, SUM(monto) cnt FROM TRANSACCIONES GROUP BY tipo", 'tipo','cnt');
                $interPorMedio = fetch_key_value($conn, "SELECT medio, COUNT(*) cnt FROM INTERACCIONES GROUP BY medio", 'medio','cnt');

                // Series temporales
                list($interPorDia_labels, $interPorDia_data) = fetch_series($conn, "SELECT DATE(fecha_interaccion) d, COUNT(*) cnt FROM INTERACCIONES GROUP BY DATE(fecha_interaccion) ORDER BY d", 'd','cnt');
                list($transPorDia_labels, $transPorDia_data) = fetch_series($conn, "SELECT DATE(fecha_inicio) d, COUNT(*) cnt FROM TRANSACCIONES GROUP BY DATE(fecha_inicio) ORDER BY d", 'd','cnt');
                list($citasPorDia_labels, $citasPorDia_data) = fetch_series($conn, "SELECT DATE(fecha) d, COUNT(*) cnt FROM CITAS GROUP BY DATE(fecha) ORDER BY d", 'd','cnt');

                // Top ciudad de clientes
                $clientesPorCiudad = fetch_key_value($conn, "SELECT ciudad, COUNT(*) cnt FROM CLIENTES GROUP BY ciudad ORDER BY cnt DESC LIMIT 10", 'ciudad','cnt');

                // Características y etiquetas populares
                $caractPopulares = fetch_key_value(
                        $conn,
                        "SELECT c.nombre, COUNT(*) cnt
                         FROM PROPIEDAD_CARACTERISTICA pc
                         JOIN CARACTERISTICAS c ON c.id_caracteristica=pc.id_caracteristica
                         WHERE pc.valor IN ('Sí','Si','YES','Yes','1','true')
                         GROUP BY c.nombre ORDER BY cnt DESC LIMIT 10",
                        'nombre','cnt'
                );
                $etiquetasPopulares = fetch_key_value(
                        $conn,
                        "SELECT e.nombre, COUNT(*) cnt
                         FROM PROPIEDAD_ETIQUETA pe
                         JOIN ETIQUETAS e ON e.id_etiqueta=pe.id_etiqueta
                         GROUP BY e.nombre ORDER BY cnt DESC LIMIT 10",
                        'nombre','cnt'
                );

                // Si no hay datos, usar demo para no dejar vacío el dashboard
                if (
                        $kpis['clientes'] === 0 &&
                        $kpis['propiedades'] === 0 &&
                        $kpis['transacciones'] === 0 &&
                        $kpis['interacciones'] === 0
                ) {
                        $usingSample = true;
                }
}

if (!$db_ok) {
        $usingSample = true;
}

if ($conn) { @$conn->close(); }

// Preparar datos para JS
$js = [
        'kpis' => $kpis,
        'propPorTipo' => $propPorTipo,
        'propPorEstado' => $propPorEstado,
        'transPorTipo' => $transPorTipo,
        'transMontoPorTipo' => $transMontoPorTipo,
        'interPorMedio' => $interPorMedio,
        'interPorDia' => [
                'labels' => $interPorDia_labels,
                'data' => $interPorDia_data,
        ],
        'transPorDia' => [
            'labels' => $transPorDia_labels,
            'data' => $transPorDia_data,
        ],
        'citasPorDia' => [
            'labels' => $citasPorDia_labels,
            'data' => $citasPorDia_data,
        ],
        'clientesPorCiudad' => $clientesPorCiudad,
        'caractPopulares' => $caractPopulares,
        'etiquetasPopulares' => $etiquetasPopulares,
        'usingSample' => $usingSample,
        'warnings' => $warn,
        'db' => [ 'host' => DB_HOST, 'port' => DB_PORT, 'name' => DB_NAME ]
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --accent:#22c55e; }
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;background:linear-gradient(180deg,#0b1220,#0f172a);color:var(--text)}
        header{padding:24px 20px;border-bottom:1px solid #1f2937;background:#0b1220}
        .container{max-width:1100px;margin:0 auto;padding:24px 16px}
        h1{margin:0;font-size:22px}
        .grid{display:grid;gap:16px}
        .kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
        .card{background:rgba(17,24,39,.7);backdrop-filter:blur(8px);border:1px solid #1f2937;border-radius:12px;padding:16px}
        .kpi .label{color:var(--muted);font-size:12px}
        .kpi .value{font-size:28px;font-weight:700;margin-top:6px}
        .foot{color:var(--muted);font-size:12px;margin-top:24px}
        @media (max-width: 800px){ .kpis{grid-template-columns:1fr} }
        canvas{width:100%!important;max-height:320px}
        .warning{background:#7c2d12;color:#fde68a;border:1px solid #b45309;padding:10px;border-radius:10px;margin-bottom:16px}
        .badge{display:inline-block;border:1px solid #374151;background:#111827;padding:2px 8px;border-radius:999px;color:#9ca3af;font-size:12px}
        .sidebar{position:fixed;left:-250px;top:0;width:250px;height:100vh;background:#000000;color:#e5e7eb;padding:20px;box-sizing:border-box;border-right:1px solid #1f2937;z-index:1001;transition:left 0.3s}
        .sidebar-header h2{margin:0 0 20px;font-size:24px;color:#22c55e}
        .sidebar-nav{display:flex;flex-direction:column;gap:10px}
        .nav-link{display:flex;align-items:center;gap:10px;color:#94a3b8;text-decoration:none;padding:10px;border-radius:8px;transition:background 0.2s}
        .nav-link:hover{background:rgba(34,197,94,.1);color:#22c55e}
        .sidebar-trigger{position:fixed;left:0;top:0;width:200px;height:100vh;z-index:1000;background:rgba(0,0,0,0.1)}
        .sidebar-trigger:hover + .sidebar, .sidebar:hover{left:0}
        .main-content{padding:24px 16px;max-width:100vw}
        footer{text-align:center;padding:20px;color:var(--muted);font-size:14px;margin:0 auto}
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <header>
        <div class="container">
            <h1>CRM Inmobiliaria • Dashboard</h1>
        </div>
    </header>
    <main class="main-content">
        <div id="warnings"></div>
            <div class="grid kpis">
                <div class="card kpi"><div class="label">Clientes</div><div id="kpi-clientes" class="value">-</div></div>
                <div class="card kpi"><div class="label">Agentes</div><div id="kpi-agentes" class="value">-</div></div>
                <div class="card kpi"><div class="label">Propiedades</div><div id="kpi-propiedades" class="value">-</div></div>
            </div>
            <div class="grid kpis" style="margin-top:16px">
                <div class="card kpi"><div class="label">Transacciones</div><div id="kpi-transacciones" class="value">-</div></div>
                <div class="card kpi"><div class="label">Monto total</div><div id="kpi-monto" class="value">-</div></div>
                <div class="card kpi"><div class="label">Interacciones</div><div id="kpi-interacciones" class="value">-</div></div>
            </div>

        <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr)); margin-top:16px">
                            <div class="card">
                                <div class="label">Clientes por ciudad</div>
                <canvas id="chartClientes"></canvas>
            </div>
            <div class="card">
                        <div class="label">Propiedades por tipo</div>
                <canvas id="chartProps"></canvas>
            </div>
            <div class="card" style="grid-column:1/-1">
                <div class="label">Interacciones por día</div>
                <canvas id="chartInterDia"></canvas>
            </div>
            <div class="card" style="grid-column:1/-1">
                        <div class="label">Interacciones por medio</div>
                <canvas id="chartInterTipo"></canvas>
            </div>
                    <div class="card">
                        <div class="label">Propiedades por estado</div>
                        <canvas id="chartPropEstado"></canvas>
                    </div>
                    <div class="card">
                        <div class="label">Transacciones por tipo</div>
                        <canvas id="chartTransTipo"></canvas>
                    </div>
                    <div class="card" style="grid-column:1/-1">
                        <div class="label">Transacciones por día</div>
                        <canvas id="chartTransDia"></canvas>
                    </div>
                    <div class="card">
                        <div class="label">Citas por día</div>
                        <canvas id="chartCitasDia"></canvas>
                    </div>
                    <div class="card">
                        <div class="label">Top ciudades (clientes)</div>
                        <canvas id="chartClientesCiudad"></canvas>
                    </div>
                    <div class="card">
                        <div class="label">Características populares</div>
                        <canvas id="chartCaract"></canvas>
                    </div>
                    <div class="card">
                        <div class="label">Etiquetas populares</div>
                        <canvas id="chartEtiq"></canvas>
                    </div>
        </div>

        <div class="foot">
            <span class="badge">DB: <?=htmlspecialchars($js['db']['name'])?> @ <?=htmlspecialchars($js['db']['host'])?>:<?=htmlspecialchars((string)$js['db']['port'])?></span>
            <?php if ($js['usingSample']): ?>
                <span class="badge">Datos de ejemplo</span>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>

    <script>
        const DATA = <?php echo json_encode($js, JSON_UNESCAPED_UNICODE); ?>;

        const warnEl = document.getElementById('warnings');
        if (DATA.warnings && DATA.warnings.length) {
            DATA.warnings.forEach(w => {
                const d = document.createElement('div');
                d.className = 'warning'; d.textContent = w; warnEl.appendChild(d);
            });
        }

        // KPIs
        const num = n => new Intl.NumberFormat('es-AR').format(n);
        const money = n => new Intl.NumberFormat('es-AR', { style:'currency', currency:'ARS', maximumFractionDigits:0 }).format(n);
        document.getElementById('kpi-clientes').textContent = num(DATA.kpis.clientes);
        document.getElementById('kpi-agentes').textContent = num(DATA.kpis.agentes ?? 0);
        document.getElementById('kpi-propiedades').textContent = num(DATA.kpis.propiedades);
        document.getElementById('kpi-transacciones').textContent = num(DATA.kpis.transacciones ?? 0);
        document.getElementById('kpi-monto').textContent = money(DATA.kpis.monto_total_trans ?? 0);
        document.getElementById('kpi-interacciones').textContent = num(DATA.kpis.interacciones);

        // Utilidades para colores
        const palette = ['#60a5fa','#34d399','#fbbf24','#f472b6','#a78bfa','#ef4444','#22d3ee','#84cc16'];
        const ctx = id => document.getElementById(id).getContext('2d');

            // Clientes (placeholder): ciudades
            const cciLabels = Object.keys(DATA.clientesPorCiudad);
            const cciData   = Object.values(DATA.clientesPorCiudad);
            new Chart(ctx('chartClientes'), {
                type:'bar',
                data:{ labels:cciLabels, datasets:[{ label:'Clientes', data:cciData, backgroundColor:'#84cc16' }]},
                options:{ scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

        // Propiedades por tipo (bar)
        const pptLabels = Object.keys(DATA.propPorTipo);
        const pptData   = Object.values(DATA.propPorTipo);
        new Chart(ctx('chartProps'), {
            type:'bar',
            data:{ labels:pptLabels, datasets:[{ label:'Cantidad', data:pptData, backgroundColor:'#34d399' }]},
            options:{
                scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } },
                plugins:{ legend:{ labels:{ color:'#e5e7eb' } } }
            }
        });

        // Interacciones por día (line)
        new Chart(ctx('chartInterDia'), {
            type:'line',
            data:{ labels:DATA.interPorDia.labels, datasets:[{ label:'Interacciones', data:DATA.interPorDia.data, borderColor:'#60a5fa', backgroundColor:'rgba(96,165,250,.2)', tension:.2, fill:true }]},
            options:{
                scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } },
                plugins:{ legend:{ labels:{ color:'#e5e7eb' } } }
            }
        });

            // Interacciones por medio (pie)
            const iptLabels = Object.keys(DATA.interPorMedio);
            const iptData   = Object.values(DATA.interPorMedio);
        new Chart(ctx('chartInterTipo'), {
            type:'pie',
            data:{ labels:iptLabels, datasets:[{ data:iptData, backgroundColor: palette.slice(0,iptLabels.length) }]},
            options:{ plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
        });

            // Propiedades por estado
            const pesLabels = Object.keys(DATA.propPorEstado);
            const pesData   = Object.values(DATA.propPorEstado);
            new Chart(ctx('chartPropEstado'), {
                type:'doughnut',
                data:{ labels:pesLabels, datasets:[{ data:pesData, backgroundColor: palette.slice(0,pesLabels.length) }]},
                options:{ plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

            // Transacciones por tipo
            const tptLabels = Object.keys(DATA.transPorTipo);
            const tptData   = Object.values(DATA.transPorTipo);
            new Chart(ctx('chartTransTipo'), {
                type:'bar',
                data:{ labels:tptLabels, datasets:[{ label:'Cantidad', data:tptData, backgroundColor:'#22d3ee' }]},
                options:{ scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

            // Transacciones por día
            new Chart(ctx('chartTransDia'), {
                type:'line',
                data:{ labels:DATA.transPorDia.labels, datasets:[{ label:'Transacciones', data:DATA.transPorDia.data, borderColor:'#a78bfa', backgroundColor:'rgba(167,139,250,.2)', tension:.2, fill:true }]},
                options:{ scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

            // Citas por día
            new Chart(ctx('chartCitasDia'), {
                type:'line',
                data:{ labels:DATA.citasPorDia.labels, datasets:[{ label:'Citas', data:DATA.citasPorDia.data, borderColor:'#fbbf24', backgroundColor:'rgba(251,191,36,.2)', tension:.2, fill:true }]},
                options:{ scales:{ x:{ ticks:{ color:'#e5e7eb' } }, y:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

            // Características populares
            const carLabels = Object.keys(DATA.caractPopulares);
            const carData   = Object.values(DATA.caractPopulares);
            new Chart(ctx('chartCaract'), {
                type:'bar',
                data:{ labels:carLabels, datasets:[{ label:'Cantidad', data:carData, backgroundColor:'#34d399' }]},
                options:{ indexAxis:'y', scales:{ x:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true }, y:{ ticks:{ color:'#e5e7eb' } } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });

            // Etiquetas populares
            const etiLabels = Object.keys(DATA.etiquetasPopulares);
            const etiData   = Object.values(DATA.etiquetasPopulares);
            new Chart(ctx('chartEtiq'), {
                type:'bar',
                data:{ labels:etiLabels, datasets:[{ label:'Cantidad', data:etiData, backgroundColor:'#60a5fa' }]},
                options:{ indexAxis:'y', scales:{ x:{ ticks:{ color:'#e5e7eb' }, beginAtZero:true }, y:{ ticks:{ color:'#e5e7eb' } } }, plugins:{ legend:{ labels:{ color:'#e5e7eb' } } } }
            });
    </script>
</body>
</html>
