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
$warnings = [];
$js = [];
$js['db'] = ['name' => 'unknown', 'host' => 'unknown', 'port' => 'unknown'];
$clientesData = [];
$propiedadesData = [];
$interaccionesData = [];
$totalClientes = 0;
$totalPropiedades = 0;
$totalInteracciones = 0;
$page1 = $_GET['page1'] ?? 1;
$page2 = $_GET['page2'] ?? 1;
$page3 = $_GET['page3'] ?? 1;
$limit = 10;
$offset1 = ($page1 - 1) * $limit;
$offset2 = ($page2 - 1) * $limit;
$offset3 = ($page3 - 1) * $limit;
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
                $js['db'] = [
                    'name' => defined('DB_NAME') ? DB_NAME : 'unknown',
                    'host' => defined('DB_HOST') ? DB_HOST : 'unknown',
                    'port' => defined('DB_PORT') ? DB_PORT : 'unknown',
                ];
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
                         FROM propiedad_caracteristica pc
                         JOIN caracteristicas c ON c.id_caracteristica=pc.id_caracteristica
                         WHERE pc.valor IN ('Sí','Si','YES','Yes','1','true')
                         GROUP BY c.nombre ORDER BY cnt DESC LIMIT 10",
                        'nombre','cnt'
                );
                $etiquetasPopulares = fetch_key_value(
                        $conn,
                        "SELECT e.nombre, COUNT(*) cnt
                         FROM propiedad_etiqueta pe
                         JOIN etiquetas e ON e.id_etiqueta=pe.id_etiqueta
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

                // Datos para tablas de consultas
                $clientesData = [];
                $propiedadesData = [];
                $interaccionesData = [];

                if ($conn) {
                    $res1 = $conn->query("SELECT * FROM CLIENTES LIMIT $limit OFFSET $offset1");
                    if ($res1) {
                        while ($row = $res1->fetch_assoc()) {
                            $clientesData[] = $row;
                        }
                        $res1->free();
                    }
                    $totalClientes = 0;
                    if ($res = $conn->query("SELECT COUNT(*) as c FROM CLIENTES")) {
                        $totalClientes = $res->fetch_assoc()['c'];
                        $res->free();
                    }

                    $res2 = $conn->query("SELECT * FROM PROPIEDADES LIMIT $limit OFFSET $offset2");
                    if ($res2) {
                        while ($row = $res2->fetch_assoc()) {
                            $propiedadesData[] = $row;
                        }
                        $res2->free();
                    }
                    $totalPropiedades = 0;
                    if ($res = $conn->query("SELECT COUNT(*) as c FROM PROPIEDADES")) {
                        $totalPropiedades = $res->fetch_assoc()['c'];
                        $res->free();
                    }

                    $res3 = $conn->query("SELECT * FROM INTERACCIONES LIMIT $limit OFFSET $offset3");
                    if ($res3) {
                        while ($row = $res3->fetch_assoc()) {
                            $interaccionesData[] = $row;
                        }
                        $res3->free();
                    }
                    $totalInteracciones = 0;
                    if ($res = $conn->query("SELECT COUNT(*) as c FROM INTERACCIONES")) {
                        $totalInteracciones = $res->fetch_assoc()['c'];
                        $res->free();
                    }
                }
}

if ($db_ok && count($clientesData) == 0) {
    $usingSample = true;
    $clientesData = [
        ['id_cliente' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juan@example.com', 'telefono' => '123456789', 'ciudad' => 'Rosario'],
        ['id_cliente' => 2, 'nombre' => 'María', 'apellido' => 'Gómez', 'email' => 'maria@example.com', 'telefono' => '987654321', 'ciudad' => 'Santa Fe'],
    ];
    $totalClientes = count($clientesData);
    $propiedadesData = [
        ['id_propiedad' => 1, 'titulo' => 'Casa con pileta', 'tipo' => 'casa', 'precio' => 250000, 'estado' => 'disponible', 'id_agente' => 1],
        ['id_propiedad' => 2, 'titulo' => 'Departamento céntrico', 'tipo' => 'departamento', 'precio' => 150000, 'estado' => 'disponible', 'id_agente' => 2],
    ];
    $totalPropiedades = count($propiedadesData);
    $interaccionesData = [
        ['id_interaccion' => 1, 'medio' => 'Teléfono', 'fecha_interaccion' => '2023-01-01', 'id_propiedad' => 1, 'id_cliente' => 1],
        ['id_interaccion' => 2, 'medio' => 'Email', 'fecha_interaccion' => '2023-01-02', 'id_propiedad' => 2, 'id_cliente' => 2],
    ];
    $totalInteracciones = count($interaccionesData);
}

if (!$db_ok) {
        $usingSample = true;
        // Datos demo para tablas
        $clientesData = [
            ['id_cliente' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juan@example.com', 'telefono' => '123456789', 'ciudad' => 'Rosario'],
            ['id_cliente' => 2, 'nombre' => 'María', 'apellido' => 'Gómez', 'email' => 'maria@example.com', 'telefono' => '987654321', 'ciudad' => 'Santa Fe'],
        ];
        $propiedadesData = [
            ['id_propiedad' => 1, 'titulo' => 'Casa con pileta', 'tipo' => 'casa', 'precio' => 250000, 'estado' => 'disponible', 'id_agente' => 1],
            ['id_propiedad' => 2, 'titulo' => 'Departamento céntrico', 'tipo' => 'departamento', 'precio' => 150000, 'estado' => 'disponible', 'id_agente' => 2],
        ];
        $interaccionesData = [
            ['id_interaccion' => 1, 'medio' => 'Teléfono', 'fecha_interaccion' => '2023-01-01', 'id_propiedad' => 1, 'id_cliente' => 1],
            ['id_interaccion' => 2, 'medio' => 'Email', 'fecha_interaccion' => '2023-01-02', 'id_propiedad' => 2, 'id_cliente' => 2],
        ];
        $totalClientes = count($clientesData);
        $totalPropiedades = count($propiedadesData);
        $totalInteracciones = count($interaccionesData);
}

$js['clientesData'] = $clientesData;
$js['propiedadesData'] = $propiedadesData;
$js['interaccionesData'] = $interaccionesData;
$js['kpis'] = $kpis;
$js['totalClientes'] = $totalClientes;
$js['totalPropiedades'] = $totalPropiedades;
$js['totalInteracciones'] = $totalInteracciones;
$js['page1'] = 1;
$js['page2'] = 1;
$js['page3'] = 1;
$js['usingSample'] = $usingSample;
$js['warnings'] = $warnings;

$jsonData = json_encode($js, JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    $jsonData = '{}';
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $table = $_GET['table'] ?? '';
    $response = [];
    if ($table == 'clientes') {
        $page = $_GET['page1'] ?? 1;
        $offset = ($page - 1) * 10;
        $data = [];
        if ($conn) {
            $res = $conn->query("SELECT * FROM CLIENTES LIMIT 10 OFFSET $offset");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
                $res->free();
            }
        } else {
            $data = array_slice($clientesData, $offset, 10);
        }
        $response = ['data' => $data, 'total' => $totalClientes, 'page' => $page];
    } elseif ($table == 'propiedades') {
        $page = $_GET['page2'] ?? 1;
        $offset = ($page - 1) * 10;
        $data = [];
        if ($conn) {
            $res = $conn->query("SELECT * FROM PROPIEDADES LIMIT 10 OFFSET $offset");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
                $res->free();
            }
        } else {
            $data = array_slice($propiedadesData, $offset, 10);
        }
        $response = ['data' => $data, 'total' => $totalPropiedades, 'page' => $page];
    } elseif ($table == 'interacciones') {
        $page = $_GET['page3'] ?? 1;
        $offset = ($page - 1) * 10;
        $data = [];
        if ($conn) {
            $res = $conn->query("SELECT * FROM INTERACCIONES LIMIT 10 OFFSET $offset");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
                $res->free();
            }
        } else {
            $data = array_slice($interaccionesData, $offset, 10);
        }
        $response = ['data' => $data, 'total' => $totalInteracciones, 'page' => $page];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($conn) { @$conn->close(); }

// Preparar datos para JS
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

    <link rel="stylesheet" href="../estilos/inicio.css">
</head>
<body>
    <?php include('sidebar.php'); ?>
    <header>
        <div class="container">
            <h1>Dashboard Analítico - Consultas SQL y Métricas del CRM Inmobiliario</h1>
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

        <h2 style="color:var(--text); margin-top:40px;">Consultas SQL y Datos de Tablas</h2>
        <p style="color:var(--muted);">A continuación se muestran las tablas crudas de la base de datos, con límite 10 y paginación si es necesario.</p>

    <div class="sql-section" style="display:flex;flex-direction:column;gap:20px;margin-top:20px;">
        <div class="card sql-card sql-accent-1" style="width:100%">
            <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#06b6d4" stroke-width="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9" stroke="#06b6d4" stroke-width="2"></line>
                <line x1="3" y1="15" x2="21" y2="15" stroke="#06b6d4" stroke-width="2"></line>
                <line x1="9" y1="3" x2="9" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                <line x1="15" y1="3" x2="15" y2="21" stroke="#06b6d4" stroke-width="2"></line>
            </svg>
            <div class="sql-content">
          <h3 style="color:var(--text);margin:0">Consulta 1: Tabla clientes</h3>
          <pre class="sql-sqltext">SELECT * FROM clientes LIMIT 10</pre>
                <div style="overflow-x:auto; margin-top:8px;">
                    <table style="width:100%; border-collapse:collapse; color:var(--text);">
                        <thead id="theadClientes" style="background:#1f2937;"></thead>
                        <tbody id="tbodyClientes"></tbody>
                    </table>
                </div>
                <div id="paginationClientes" class="pagination"></div>
            </div>
        </div>

        <div class="card sql-card sql-accent-2" style="width:100%">
            <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#f59e0b" stroke-width="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9" stroke="#f59e0b" stroke-width="2"></line>
                <line x1="3" y1="15" x2="21" y2="15" stroke="#f59e0b" stroke-width="2"></line>
                <line x1="9" y1="3" x2="9" y2="21" stroke="#f59e0b" stroke-width="2"></line>
                <line x1="15" y1="3" x2="15" y2="21" stroke="#f59e0b" stroke-width="2"></line>
            </svg>
            <div class="sql-content">
          <h3 style="color:var(--text);margin:0">Consulta 2: Tabla propiedades</h3>
          <pre class="sql-sqltext">SELECT * FROM propiedades LIMIT 10</pre>
                <div style="overflow-x:auto; margin-top:8px;">
                    <table style="width:100%; border-collapse:collapse; color:var(--text);">
                        <thead id="theadPropiedades" style="background:#1f2937;"></thead>
                        <tbody id="tbodyPropiedades"></tbody>
                    </table>
                </div>
                <div id="paginationPropiedades" class="pagination"></div>
            </div>
        </div>

        <div class="card sql-card sql-accent-3" style="width:100%">
            <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#ef4444" stroke-width="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9" stroke="#ef4444" stroke-width="2"></line>
                <line x1="3" y1="15" x2="21" y2="15" stroke="#ef4444" stroke-width="2"></line>
                <line x1="9" y1="3" x2="9" y2="21" stroke="#ef4444" stroke-width="2"></line>
                <line x1="15" y1="3" x2="15" y2="21" stroke="#ef4444" stroke-width="2"></line>
            </svg>
            <div class="sql-content">
          <h3 style="color:var(--text);margin:0">Consulta 3: Tabla interacciones</h3>
          <pre class="sql-sqltext">SELECT * FROM interacciones LIMIT 10</pre>
                <div style="overflow-x:auto; margin-top:8px;">
                    <table style="width:100%; border-collapse:collapse; color:var(--text);">
                        <thead id="theadInteracciones" style="background:#1f2937;"></thead>
                        <tbody id="tbodyInteracciones"></tbody>
                    </table>
                </div>
                <div id="paginationInteracciones" class="pagination"></div>
            </div>
        </div>
    </div>

        <div class="foot">
            <span class="badge">DB: <?= isset($js['db']['name']) ? htmlspecialchars($js['db']['name']) : 'unknown' ?> @ <?= isset($js['db']['host']) ? htmlspecialchars($js['db']['host']) : 'unknown' ?>:<?= isset($js['db']['port']) ? htmlspecialchars((string)$js['db']['port']) : 'unknown' ?></span>
            <?php if (isset($js['usingSample']) && $js['usingSample']): ?>
                <span class="badge">Datos de ejemplo</span>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>

    <script>
        const DATA = <?php echo $jsonData; ?>;

        if (!DATA.kpis) DATA.kpis = {clientes: 0, agentes: 0, propiedades: 0, transacciones: 0, monto_total_trans: 0, interacciones: 0};
        if (!DATA.clientesPorCiudad) DATA.clientesPorCiudad = {};
        if (!DATA.propPorTipo) DATA.propPorTipo = {};
        if (!DATA.interPorDia) DATA.interPorDia = {labels: [], data: []};
        if (!DATA.interPorMedio) DATA.interPorMedio = {};
        if (!DATA.propPorEstado) DATA.propPorEstado = {};
        if (!DATA.transPorTipo) DATA.transPorTipo = {};
        if (!DATA.transPorDia) DATA.transPorDia = {labels: [], data: []};
        if (!DATA.citasPorDia) DATA.citasPorDia = {labels: [], data: []};
        if (!DATA.caractPopulares) DATA.caractPopulares = {};
        if (!DATA.etiquetasPopulares) DATA.etiquetasPopulares = {};
        if (!DATA.warnings) DATA.warnings = [];

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

        // Llenar tablas
        const tableMap = {'page1':'clientes', 'page2':'propiedades', 'page3':'interacciones'};
        const fillTable = (theadId, tbodyId, data, total, page, param) => {
            console.log('Filling table:', theadId, tbodyId, 'data length:', data ? data.length : 'null', 'total:', total, 'page:', page);
            const thead = document.getElementById(theadId);
            const tbody = document.getElementById(tbodyId);
            if (!data || data.length === 0) {
                thead.innerHTML = '';
                tbody.innerHTML = '<tr><td colspan="1" style="text-align:center; color:#94a3b8;">Sin datos</td></tr>';
                return;
            }
            const headers = Object.keys(data[0]);
            thead.innerHTML = '<tr>' + headers.map(h => '<th style="padding:8px; border:1px solid #374151;">' + h + '</th>').join('') + '</tr>';
            tbody.innerHTML = data.map(row => '<tr>' + headers.map(h => '<td style="padding:8px; border:1px solid #374151;">' + (row[h] || '') + '</td>').join('') + '</tr>').join('');
            // pagination
            const paginationId = 'pagination' + theadId.replace('thead', '');
            const paginationEl = document.getElementById(paginationId);
            const table = tableMap[param];
            let html = '';
            const totalPages = Math.ceil(total / 10);
            for (let p = 1; p <= totalPages; p++) {
                const activeClass = p === page ? ' active' : '';
                html += '<button class="pagination-btn' + activeClass + '" onclick="changePage(\'' + table + '\', ' + p + ')">' + p + '</button>';
            }
            paginationEl.innerHTML = html;
        };

        function changePage(table, newPage) {
            console.log('Changing page:', table, newPage);
            const param = table === 'clientes' ? 'page1' : table === 'propiedades' ? 'page2' : 'page3';
            fetch('?ajax=1&table=' + table + '&' + param + '=' + newPage)
            .then(response => response.json())
            .then(data => {
                console.log('Received data for', table, ':', data);
                const theadId = 'thead' + table.charAt(0).toUpperCase() + table.slice(1);
                const tbodyId = 'tbody' + table.charAt(0).toUpperCase() + table.slice(1);
                fillTable(theadId, tbodyId, data.data, data.total, data.page, param);
            });
        }

        // Ensure data exists
        if (!DATA.clientesData) DATA.clientesData = [];
        if (!DATA.propiedadesData) DATA.propiedadesData = [];
        if (!DATA.interaccionesData) DATA.interaccionesData = [];
        if (!DATA.totalClientes) DATA.totalClientes = 0;
        if (!DATA.totalPropiedades) DATA.totalPropiedades = 0;
        if (!DATA.totalInteracciones) DATA.totalInteracciones = 0;
        if (!DATA.page1) DATA.page1 = 1;
        if (!DATA.page2) DATA.page2 = 1;
        if (!DATA.page3) DATA.page3 = 1;

        console.log('DATA:', DATA);

        fillTable('theadClientes', 'tbodyClientes', DATA.clientesData, DATA.totalClientes, DATA.page1, 'page1');
        fillTable('theadPropiedades', 'tbodyPropiedades', DATA.propiedadesData, DATA.totalPropiedades, DATA.page2, 'page2');
        fillTable('theadInteracciones', 'tbodyInteracciones', DATA.interaccionesData, DATA.totalInteracciones, DATA.page3, 'page3');
    </script>
</body>
</html>
