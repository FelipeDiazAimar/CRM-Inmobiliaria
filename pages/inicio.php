<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// ============================================================
// Contrato DEMO (lo provee pages/demo_data.php, agente C-1).
// Interfaz exacta: demo_activo(): bool y
// demo_datos('clientes'|'agentes'|'propiedades'|'interacciones'|'citas'|'transacciones'): array
// con las columnas del .sql. Si el archivo aún no existe, este
// dashboard sigue funcionando (valores por defecto, sin errores).
// ============================================================
$__demoFile = __DIR__ . '/demo_data.php';
if (file_exists($__demoFile)) {
    require_once $__demoFile;
}

/** Lee una tabla demo tolerando que falte la función o vengan datos raros. */
function c2_demo_tabla(string $nombre): array
{
    if (!function_exists('demo_datos')) {
        return [];
    }
    try {
        $d = demo_datos($nombre);
    } catch (Throwable $e) {
        return [];
    }
    return is_array($d) ? array_values($d) : [];
}

/** Timestamp tolerante para fechas 'Y-m-d' o 'Y-m-d H:i:s'. */
function c2_ts($fecha): int
{
    if (!is_string($fecha) || $fecha === '') {
        return 0;
    }
    $t = strtotime($fecha);
    return $t === false ? 0 : (int)$t;
}

function c2_fmt_num($n): string
{
    return number_format((float)$n, 0, ',', '.');
}

function c2_fmt_monto($n): string
{
    return '$ ' . number_format((float)$n, 0, ',', '.');
}

/** Corte de texto seguro con/sin extensión mbstring. */
function c2_cortar(string $s, int $n): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $n);
    }
    if (preg_match('/^.{0,' . $n . '}/us', $s, $m)) {
        return $m[0];
    }
    return substr($s, 0, $n);
}

/** Inicial mayúscula segura con/sin extensión mbstring. */
function c2_inicial(string $w): string
{
    $ch = c2_cortar($w, 1);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($ch);
    }
    return strtoupper($ch);
}

/** '2025-10' -> 'Oct 2025' (etiqueta amable para el KPI de comisiones). */
function c2_mes_corto(string $ym): string
{
    $meses = [
        '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
        '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
    ];
    $p = explode('-', $ym);
    if (count($p) !== 2 || !isset($meses[$p[1]])) {
        return 'el mes';
    }
    return $meses[$p[1]] . ' ' . $p[0];
}

$esDemo = function_exists('demo_activo') && demo_activo();

// Paginación (ambos modos)
$page1 = $_GET['page1'] ?? 1;
$page2 = $_GET['page2'] ?? 1;
$page3 = $_GET['page3'] ?? 1;
$limit = 10;
$offset1 = ($page1 - 1) * $limit;
$offset2 = ($page2 - 1) * $limit;
$offset3 = ($page3 - 1) * $limit;

// Funciones auxiliares (modo real)
function fetch_key_value($conn, $sql, $key, $val)
{
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

function fetch_series($conn, $sql, $x, $y)
{
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

if ($esDemo) {
    // ============================================================
    // MODO DEMO: todo sale de demo_datos(). Sin MySQL, sin mysqli,
    // sin consultas crudas visibles. Flujo real intacto más abajo.
    // ============================================================
    $conn = null;
    $db_ok = false;
    $usingSample = true;
    $warnings = [];
    $warn = [];
    $js = [];
    $js['db'] = ['name' => 'demo', 'host' => 'local', 'port' => '-'];

    $demoClientes = c2_demo_tabla('clientes');
    $demoAgentes  = c2_demo_tabla('agentes');
    $demoProps    = c2_demo_tabla('propiedades');
    $demoInter    = c2_demo_tabla('interacciones');
    $demoCitas    = c2_demo_tabla('citas');
    $demoTrans    = c2_demo_tabla('transacciones');

    // Mapas id -> nombre legible (con valores por defecto)
    $mapClientes = [];
    foreach ($demoClientes as $c) {
        if (!is_array($c)) continue;
        $id = $c['id_cliente'] ?? null;
        if ($id === null) continue;
        $mapClientes[$id] = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? '')) ?: 'Cliente ' . $id;
    }
    $mapAgentes = [];
    foreach ($demoAgentes as $a) {
        if (!is_array($a)) continue;
        $id = $a['id_agente'] ?? null;
        if ($id === null) continue;
        $mapAgentes[$id] = trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? '')) ?: 'Agente ' . $id;
    }
    $mapProps = [];
    $propAgente = [];
    foreach ($demoProps as $p) {
        if (!is_array($p)) continue;
        $id = $p['id_propiedad'] ?? null;
        if ($id === null) continue;
        $mapProps[$id] = ($p['titulo'] ?? '') !== '' ? $p['titulo'] : 'Propiedad ' . $id;
        if (isset($p['id_agente'])) {
            $propAgente[$id] = $p['id_agente'];
        }
    }

    // KPI 1: propiedades activas (estado disponible/activa)
    $estadosActivos = ['disponible', 'disponibles', 'activa', 'activo', 'libre'];
    $propActivas = 0;
    foreach ($demoProps as $p) {
        if (!is_array($p)) continue;
        if (in_array(strtolower(trim((string)($p['estado'] ?? ''))), $estadosActivos, true)) {
            $propActivas++;
        }
    }

    // KPI 2: clientes
    $totalClientes = count($demoClientes);

    // KPI 3: citas próximas (abiertas: pendiente/confirmada; lista ordenada por fecha)
    $estadosCitaAbierta = ['pendiente', 'confirmada', 'programada', 'agendada'];
    $citasPend = [];
    foreach ($demoCitas as $c) {
        if (!is_array($c)) continue;
        if (in_array(strtolower(trim((string)($c['estado'] ?? ''))), $estadosCitaAbierta, true)) {
            $citasPend[] = $c;
        }
    }
    $hoy = strtotime('today');
    $citasProxLista = array_values(array_filter($citasPend, function ($c) use ($hoy) {
        return c2_ts($c['fecha'] ?? '') >= $hoy;
    }));
    $citasNota = 'Agendadas a partir de hoy';
    if (count($citasProxLista) === 0) {
        // Sin futuras: mostrar las pendientes más cercanas registradas.
        $citasProxLista = $citasPend;
        $citasNota = 'Mostrando las más cercanas registradas';
    }
    usort($citasProxLista, function ($a, $b) {
        return c2_ts($a['fecha'] ?? '') <=> c2_ts($b['fecha'] ?? '');
    });
    $citasProxLista = array_slice($citasProxLista, 0, 5);
    $citasProximas = count($citasPend);

    // KPI 4: comisiones del mes calculadas de transacciones.
    // Supuesto documentado: comisión del 3% sobre el monto operado.
    // "El mes" = el mes más reciente presente en los datos (así el
    // modo demo con fechas fijas siempre muestra un valor útil).
    define('C2_COMISION', 0.03);
    $mesRef = '';
    foreach ($demoTrans as $t) {
        if (!is_array($t)) continue;
        $f = substr((string)($t['fecha_inicio'] ?? ''), 0, 7);
        if (preg_match('/^\d{4}-\d{2}$/', $f) && $f > $mesRef) {
            $mesRef = $f;
        }
    }
    if ($mesRef === '') {
        $mesRef = date('Y-m');
    }
    $montoMes = 0.0;
    foreach ($demoTrans as $t) {
        if (!is_array($t)) continue;
        if (substr((string)($t['fecha_inicio'] ?? ''), 0, 7) === $mesRef) {
            $montoMes += (float)($t['monto'] ?? 0);
        }
    }
    $comisionMes = round($montoMes * C2_COMISION, 2);
    $mesCorto = c2_mes_corto($mesRef);

    // KPIs de compatibilidad (mismo HTML/JS que el modo real)
    $montoTotal = 0.0;
    foreach ($demoTrans as $t) {
        if (is_array($t)) $montoTotal += (float)($t['monto'] ?? 0);
    }
    $kpis = [
        'clientes' => $totalClientes,
        'agentes' => count($demoAgentes),
        'propiedades' => count($demoProps),
        'prop_activas' => $propActivas,
        'transacciones' => count($demoTrans),
        'monto_total_trans' => $montoTotal,
        'interacciones' => count($demoInter),
        'citas_proximas' => $citasProximas,
        'comision_mes' => $comisionMes,
        'monto_mes' => $montoMes,
        'mes_ref' => $mesRef,
    ];

    // Actividad reciente: últimas 5 interacciones
    $actividad = $demoInter;
    usort($actividad, function ($a, $b) {
        $ta = is_array($a) ? c2_ts($a['fecha_interaccion'] ?? '') : 0;
        $tb = is_array($b) ? c2_ts($b['fecha_interaccion'] ?? '') : 0;
        return $tb <=> $ta;
    });
    $actividad = array_slice(array_values(array_filter($actividad, 'is_array')), 0, 5);

    // Top agentes: operaciones (vía propiedad), volumen y propiedades a cargo
    $statOps = [];
    $statMonto = [];
    foreach ($demoTrans as $t) {
        if (!is_array($t)) continue;
        $idAg = $propAgente[$t['id_propiedad'] ?? ''] ?? null;
        if ($idAg === null) continue;
        $statOps[$idAg] = ($statOps[$idAg] ?? 0) + 1;
        $statMonto[$idAg] = ($statMonto[$idAg] ?? 0.0) + (float)($t['monto'] ?? 0);
    }
    $statProps = [];
    foreach ($demoProps as $p) {
        if (!is_array($p) || !isset($p['id_agente'])) continue;
        $statProps[$p['id_agente']] = ($statProps[$p['id_agente']] ?? 0) + 1;
    }
    $topAgentes = [];
    foreach ($demoAgentes as $a) {
        if (!is_array($a)) continue;
        $id = $a['id_agente'] ?? null;
        if ($id === null) continue;
        $topAgentes[] = [
            'id' => $id,
            'nombre' => $mapAgentes[$id] ?? ('Agente ' . $id),
            'ops' => $statOps[$id] ?? 0,
            'monto' => $statMonto[$id] ?? 0.0,
            'props' => $statProps[$id] ?? 0,
        ];
    }
    usort($topAgentes, function ($a, $b) {
        if ($b['ops'] !== $a['ops']) return $b['ops'] <=> $a['ops'];
        return $b['monto'] <=> $a['monto'];
    });
    $topAgentes = array_slice($topAgentes, 0, 5);
    $maxOps = 0;
    foreach ($topAgentes as $ta) {
        $maxOps = max($maxOps, (int)$ta['ops']);
    }

    // Datos para JS (compatibilidad con el script original)
    $clientesData = $demoClientes;
    $propiedadesData = $demoProps;
    $interaccionesData = $demoInter;
    $agentesData = $demoAgentes;
    $citasData = $demoCitas;
    $transData = $demoTrans;
    $totalPropiedades = count($demoProps);
    $totalInteracciones = count($demoInter);

    $extraTables = [];
    $extraTablesData = [];
    $extraTablesTotals = [];
    $allowedTables = ['CLIENTES', 'AGENTES', 'PROPIEDADES', 'INTERACCIONES', 'CITAS', 'TRANSACCIONES'];
} else {
    // ============================================================
    // MODO REAL: flujo original intacto (MySQL, mismo HTML).
    // ============================================================
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
    $kpis = [
        'clientes' => 10,
        'agentes' => 10,
        'propiedades' => 10,
        'transacciones' => 10,
        'monto_total_trans' => 1880000,
        'interacciones' => 15,
    ];
    // Datos demo adaptados al nuevo esquema
    $propPorTipo = ['casa' => 3, 'departamento' => 3, 'terreno' => 1, 'oficina' => 1, 'galpón' => 1, 'local' => 1];
    $propPorEstado = ['disponible' => 10];
    $transPorTipo = ['venta' => 5, 'alquiler' => 5];
    $transMontoPorTipo = ['venta' => 1080000, 'alquiler' => 800000];
    $interPorMedio = ['Teléfono' => 6, 'Email' => 4, 'WhatsApp' => 5, 'Visita' => 2];
    $interPorDia_labels = [];
    $interPorDia_data = [];
    $transPorDia_labels = [];
    $transPorDia_data = [];
    $citasPorDia_labels = [];
    $citasPorDia_data = [];
    $clientesPorCiudad = ['Rosario' => 10];
    $caractPopulares = ['Pileta' => 2, 'Cochera' => 2, 'Jardín' => 2, 'Balcón' => 2, 'Aire acondicionado' => 1, 'Amoblada' => 1, 'Seguridad 24hs' => 1, 'Ascensor' => 1];
    $etiquetasPopulares = ['Lujo' => 3, 'Céntrica' => 3, 'Económica' => 1, 'Reciclada' => 1, 'Industrial' => 1, 'Exclusiva' => 2, 'Moderna' => 1, 'Con vista' => 1, 'Frente al río' => 1, 'Oportunidad' => 1];
    // Series demo de los últimos 7 días
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime('today'))->modify("-{$i} days")->format('Y-m-d');
        $interPorDia_labels[] = $d;
        $interPorDia_data[] = rand(0, 4);
        $transPorDia_labels[] = $d;
        $transPorDia_data[] = rand(0, 4);
        $citasPorDia_labels[] = $d;
        $citasPorDia_data[] = rand(0, 4);
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
        if ($q1) $q1->free();
        if ($q2) $q2->free();
        if ($q3) $q3->free();
        if ($q4) $q4->free();
        if ($q5) $q5->free();

        // Distribuciones
        $propPorTipo = fetch_key_value($conn, "SELECT tipo, COUNT(*) cnt FROM PROPIEDADES GROUP BY tipo ORDER BY cnt DESC", 'tipo', 'cnt');
        $propPorEstado = fetch_key_value($conn, "SELECT estado, COUNT(*) cnt FROM PROPIEDADES GROUP BY estado ORDER BY cnt DESC", 'estado', 'cnt');
        $transPorTipo = fetch_key_value($conn, "SELECT tipo, COUNT(*) cnt FROM TRANSACCIONES GROUP BY tipo", 'tipo', 'cnt');
        $transMontoPorTipo = fetch_key_value($conn, "SELECT tipo, SUM(monto) cnt FROM TRANSACCIONES GROUP BY tipo", 'tipo', 'cnt');
        $interPorMedio = fetch_key_value($conn, "SELECT medio, COUNT(*) cnt FROM INTERACCIONES GROUP BY medio", 'medio', 'cnt');

        // Series temporales
        list($interPorDia_labels, $interPorDia_data) = fetch_series($conn, "SELECT DATE(fecha_interaccion) d, COUNT(*) cnt FROM INTERACCIONES GROUP BY DATE(fecha_interaccion) ORDER BY d", 'd', 'cnt');
        list($transPorDia_labels, $transPorDia_data) = fetch_series($conn, "SELECT DATE(fecha_inicio) d, COUNT(*) cnt FROM TRANSACCIONES GROUP BY DATE(fecha_inicio) ORDER BY d", 'd', 'cnt');
        list($citasPorDia_labels, $citasPorDia_data) = fetch_series($conn, "SELECT DATE(fecha) d, COUNT(*) cnt FROM CITAS GROUP BY DATE(fecha) ORDER BY d", 'd', 'cnt');

        // Top ciudad de clientes
        $clientesPorCiudad = fetch_key_value($conn, "SELECT ciudad, COUNT(*) cnt FROM CLIENTES GROUP BY ciudad ORDER BY cnt DESC LIMIT 10", 'ciudad', 'cnt');

        // Características y etiquetas populares
        $caractPopulares = fetch_key_value(
            $conn,
            "SELECT c.nombre, COUNT(*) cnt
                             FROM PROPIEDAD_CARACTERISTICA pc
                             JOIN CARACTERISTICAS c ON c.id_caracteristica=pc.id_caracteristica
                             WHERE pc.valor IN ('Sí','Si','YES','Yes','1','true')
                             GROUP BY c.nombre ORDER BY cnt DESC LIMIT 10",
            'nombre',
            'cnt'
        );
        $etiquetasPopulares = fetch_key_value(
            $conn,
            "SELECT e.nombre, COUNT(*) cnt
                             FROM PROPIEDAD_ETIQUETA pe
                             JOIN ETIQUETAS e ON e.id_etiqueta=pe.id_etiqueta
                             GROUP BY e.nombre ORDER BY cnt DESC LIMIT 10",
            'nombre',
            'cnt'
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

    $extraTables = [];
    $extraTablesData = [];
    $extraTablesTotals = [];
    $allowedTables = ['CLIENTES', 'PROPIEDADES', 'INTERACCIONES'];

    if ($conn) {
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_row()) {
                $tableName = $row[0];
                if (!in_array(strtoupper($tableName), ['CLIENTES', 'PROPIEDADES', 'INTERACCIONES'])) {
                    $extraTables[] = $tableName;
                }
            }
            $result->free();
        }

        $extraTables = array_slice($extraTables, 0, 9);

        foreach ($extraTables as $tableName) {
            $data = [];
            if ($res = $conn->query("SELECT * FROM `$tableName` LIMIT 10")) {
                while ($row = $res->fetch_assoc()) {
                    $data[] = $row;
                }
                $res->free();
            }

            $extraTablesData[$tableName] = $data;

            $total = 0;
            if ($res = $conn->query("SELECT COUNT(*) as c FROM `$tableName`")) {
                $total = (int)$res->fetch_assoc()['c'];
                $res->free();
            }
            $extraTablesTotals[$tableName] = $total;
        }

        $allowedTables = array_merge($allowedTables, $extraTables);
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
$js['extraTables'] = $extraTables;
$js['extraTablesData'] = $extraTablesData;
$js['extraTablesTotals'] = $extraTablesTotals;

$jsonData = json_encode($js, JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    $jsonData = '{}';
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $tableParam = $_GET['table'] ?? '';
    $tableName = '';
    foreach ($allowedTables as $allowed) {
        if (strcasecmp($allowed, $tableParam) === 0) {
            $tableName = $allowed;
            break;
        }
    }

    if ($tableName === '') {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Tabla no permitida']);
        exit;
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    $offset = ($page - 1) * 10;

    $data = [];
    $total = 0;
    $tableKey = strtoupper($tableName);

    if ($conn) {
        if ($res = $conn->query("SELECT * FROM `$tableName` LIMIT 10 OFFSET $offset")) {
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
            $res->free();
        }
        if ($res = $conn->query("SELECT COUNT(*) as c FROM `$tableName`")) {
            $total = (int)$res->fetch_assoc()['c'];
            $res->free();
        }
    } else {
        switch ($tableKey) {
            case 'CLIENTES':
                $total = $totalClientes;
                $data = array_slice($clientesData, $offset, 10);
                break;
            case 'AGENTES':
                $fuente = $agentesData ?? [];
                $total = count($fuente);
                $data = array_slice($fuente, $offset, 10);
                break;
            case 'PROPIEDADES':
                $total = $totalPropiedades;
                $data = array_slice($propiedadesData, $offset, 10);
                break;
            case 'INTERACCIONES':
                $total = $totalInteracciones;
                $data = array_slice($interaccionesData, $offset, 10);
                break;
            case 'CITAS':
                $fuente = $citasData ?? [];
                $total = count($fuente);
                $data = array_slice($fuente, $offset, 10);
                break;
            case 'TRANSACCIONES':
                $fuente = $transData ?? [];
                $total = count($fuente);
                $data = array_slice($fuente, $offset, 10);
                break;
            default:
                $baseData = $extraTablesData[$tableName] ?? [];
                $total = count($baseData);
                $data = array_slice($baseData, $offset, 10);
                break;
        }
    }

    $response = ['data' => $data, 'total' => $total, 'page' => $page];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($conn) {
    @$conn->close();
}

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
        <?php if ($esDemo): ?>
        <div class="demo-banner" role="status">
            <span class="demo-dot" aria-hidden="true"></span>
            <p><strong>Modo demostración.</strong> Estás viendo datos de ejemplo: no hace falta tener MySQL instalado.</p>
        </div>

        <div class="grid kpis-premium">
            <div class="card kpi kpi-gold">
                <div class="kpi-top">
                    <div class="label">Propiedades activas</div>
                    <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9,22 9,12 15,12 15,22" /></svg>
                </div>
                <div id="kpi-prop-activas" class="value"><?= c2_fmt_num($kpis['prop_activas'] ?? 0) ?></div>
                <div class="sub"><?= c2_fmt_num($kpis['propiedades'] ?? 0) ?> propiedades en total</div>
            </div>
            <div class="card kpi">
                <div class="kpi-top">
                    <div class="label">Clientes</div>
                    <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                </div>
                <div id="kpi-clientes" class="value"><?= c2_fmt_num($kpis['clientes'] ?? 0) ?></div>
                <div class="sub">Cartera total de clientes</div>
            </div>
            <div class="card kpi">
                <div class="kpi-top">
                    <div class="label">Citas próximas</div>
                    <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
                </div>
                <div id="kpi-citas" class="value"><?= c2_fmt_num($kpis['citas_proximas'] ?? 0) ?></div>
                <div class="sub"><?= htmlspecialchars($citasNota) ?></div>
            </div>
            <div class="card kpi kpi-gold">
                <div class="kpi-top">
                    <div class="label">Comisiones · <?= htmlspecialchars($mesCorto) ?></div>
                    <svg class="kpi-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                </div>
                <div id="kpi-comisiones" class="value"><?= c2_fmt_monto($kpis['comision_mes'] ?? 0) ?></div>
                <div class="sub">3% de <?= c2_fmt_monto($kpis['monto_mes'] ?? 0) ?> operados</div>
            </div>
        </div>

        <div class="grid kpis kpis-compat">
            <div class="card kpi">
                <div class="label">Agentes</div>
                <div id="kpi-agentes" class="value"><?= c2_fmt_num($kpis['agentes'] ?? 0) ?></div>
                <div class="sub">Equipo comercial activo</div>
            </div>
            <div class="card kpi">
                <div class="label">Transacciones</div>
                <div id="kpi-transacciones" class="value"><?= c2_fmt_num($kpis['transacciones'] ?? 0) ?></div>
                <div class="sub">Ventas y alquileres</div>
            </div>
            <div class="card kpi">
                <div class="label">Monto total</div>
                <div id="kpi-monto" class="value"><?= c2_fmt_monto($kpis['monto_total_trans'] ?? 0) ?></div>
                <div class="sub">Volumen acumulado</div>
            </div>
            <div class="card kpi">
                <div class="label">Interacciones</div>
                <div id="kpi-interacciones" class="value"><?= c2_fmt_num($kpis['interacciones'] ?? 0) ?></div>
                <div class="sub">Contactos registrados</div>
            </div>
            <div class="card kpi" hidden>
                <div class="label">Propiedades</div>
                <div id="kpi-propiedades" class="value"><?= c2_fmt_num($kpis['propiedades'] ?? 0) ?></div>
            </div>
        </div>

        <div class="dash-grid">
            <section class="panel" aria-label="Actividad reciente">
                <h3>Actividad reciente</h3>
                <p class="hint">Últimas interacciones con tus clientes.</p>
                <?php if (count($actividad) === 0): ?>
                <p class="empty">Todavía no hay actividad registrada. Cuando hables con un cliente, aparecerá aquí.</p>
                <?php else: ?>
                <ul class="act-list">
                    <?php foreach ($actividad as $it): ?>
                    <?php
                        $titulo = trim((string)($it['titulo'] ?? ''));
                        if ($titulo === '') $titulo = 'Interacción sin título';
                        $cliId = $it['id_cliente'] ?? null;
                        $propId = $it['id_propiedad'] ?? null;
                        $cliNom = ($cliId !== null && isset($mapClientes[$cliId])) ? $mapClientes[$cliId] : '—';
                        $propTit = ($propId !== null && isset($mapProps[$propId])) ? $mapProps[$propId] : '—';
                        $medio = trim((string)($it['medio'] ?? ''));
                        if ($medio === '') $medio = 'Sin medio';
                        $kanban = strtolower(trim((string)($it['estado_kanban'] ?? 'nuevo')));
                        $fts = c2_ts($it['fecha_interaccion'] ?? '');
                        $fechaTxt = $fts > 0 ? date('d/m/Y', $fts) : '—';
                    ?>
                    <li class="act-item">
                        <div class="act-main">
                            <strong><?= htmlspecialchars($titulo) ?></strong>
                            <span class="mini"><?= htmlspecialchars($cliNom) ?> · <?= htmlspecialchars($propTit) ?></span>
                        </div>
                        <div class="act-meta">
                            <span class="pill kanban-<?= htmlspecialchars($kanban) ?>"><?= htmlspecialchars($kanban) ?></span>
                            <span class="mini"><?= htmlspecialchars($medio) ?> · <?= htmlspecialchars($fechaTxt) ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>

            <section class="panel" aria-label="Próximas citas">
                <h3>Próximas citas</h3>
                <p class="hint">Visitas y reuniones agendadas con clientes.</p>
                <?php if (count($citasProxLista) === 0): ?>
                <p class="empty">No hay citas próximas por ahora. Agendá una visita y la verás en este panel.</p>
                <?php else: ?>
                <ul class="cita-list">
                    <?php foreach ($citasProxLista as $ct): ?>
                    <?php
                        $fts = c2_ts($ct['fecha'] ?? '');
                        $dia = $fts > 0 ? date('d', $fts) : '—';
                        $mesCita = $fts > 0 ? date('M', $fts) : '';
                        $horaTxt = $fts > 0 ? date('d/m/Y H:i', $fts) : 'Fecha a convenir';
                        $cliId = $ct['id_cliente'] ?? null;
                        $propId = $ct['id_propiedad'] ?? null;
                        $agId = $ct['id_agente'] ?? null;
                        $cliNom = ($cliId !== null && isset($mapClientes[$cliId])) ? $mapClientes[$cliId] : 'Cliente a confirmar';
                        $propTit = ($propId !== null && isset($mapProps[$propId])) ? $mapProps[$propId] : 'Propiedad a confirmar';
                        $agNom = ($agId !== null && isset($mapAgentes[$agId])) ? $mapAgentes[$agId] : 'Agente a asignar';
                        $estado = strtolower(trim((string)($ct['estado'] ?? 'pendiente')));
                        $notas = trim((string)($ct['notas'] ?? ''));
                    ?>
                    <li class="cita-item">
                        <div class="cita-fecha" aria-hidden="true"><span class="d"><?= htmlspecialchars($dia) ?></span><span class="m"><?= htmlspecialchars($mesCita) ?></span></div>
                        <div class="cita-main">
                            <strong><?= htmlspecialchars($propTit) ?></strong>
                            <span class="mini"><?= htmlspecialchars($cliNom) ?> · <?= htmlspecialchars($agNom) ?></span>
                            <span class="mini"><?= htmlspecialchars($horaTxt) ?> · <?= htmlspecialchars($estado) ?></span>
                            <?php if ($notas !== ''): ?>
                            <span class="nota"><?= htmlspecialchars(c2_cortar($notas, 120)) ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>

            <section class="panel panel-full" aria-label="Top agentes">
                <h3>Top agentes</h3>
                <p class="hint">Quiénes están cerrando más operaciones.</p>
                <?php if (count($topAgentes) === 0): ?>
                <p class="empty">Aún no hay agentes con operaciones. Cuando se registre una venta o alquiler, aparecerán aquí.</p>
                <?php else: ?>
                <ol class="agent-list">
                    <?php $pos = 1; foreach ($topAgentes as $ta): ?>
                    <?php
                        $ini = '';
                        $kIni = 0;
                        foreach (preg_split('/\s+/', trim($ta['nombre'])) as $w) {
                            if ($w !== '' && $kIni < 2) { $ini .= c2_inicial($w); $kIni++; }
                        }
                        if ($ini === '') $ini = '—';
                        $pct = $maxOps > 0 ? round(((int)$ta['ops'] / $maxOps) * 100) : 0;
                        $opsTxt = ((int)$ta['ops'] === 1) ? '1 operación' : c2_fmt_num($ta['ops']) . ' operaciones';
                    ?>
                    <li class="agent-row">
                        <span class="rank"><?= $pos ?></span>
                        <span class="avatar" aria-hidden="true"><?= htmlspecialchars($ini) ?></span>
                        <div class="agent-main">
                            <strong><?= htmlspecialchars($ta['nombre']) ?></strong>
                            <span class="mini"><?= htmlspecialchars($opsTxt) ?> · <?= c2_fmt_monto($ta['monto']) ?> · <?= c2_fmt_num($ta['props']) ?> propiedades a cargo</span>
                            <span class="bar" aria-hidden="true"><span style="width:<?= (int)$pct ?>%"></span></span>
                        </div>
                    </li>
                    <?php $pos++; endforeach; ?>
                </ol>
                <?php endif; ?>
            </section>
        </div>

        <div class="panel demo-note">
            <h3>Estás en modo demostración</h3>
            <p class="hint">Aquí ves un resumen ejecutivo con datos de ejemplo. Cuando conectes tu base de datos, vas a poder explorar todas las tablas completas desde este mismo panel.</p>
        </div>
        <?php else: ?>
        <div class="grid kpis">
            <div class="card kpi">
                <div class="label">Clientes</div>
                <div id="kpi-clientes" class="value">-</div>
                <div class="tooltip">SQL: SELECT COUNT(*) AS c FROM CLIENTES</div>
            </div>
            <div class="card kpi">
                <div class="label">Agentes</div>
                <div id="kpi-agentes" class="value">-</div>
                <div class="tooltip">SQL: SELECT COUNT(*) AS c FROM AGENTES</div>
            </div>
            <div class="card kpi">
                <div class="label">Propiedades</div>
                <div id="kpi-propiedades" class="value">-</div>
                <div class="tooltip">SQL: SELECT COUNT(*) AS c FROM PROPIEDADES</div>
            </div>
        </div>
        <div class="grid kpis" style="margin-top:16px">
            <div class="card kpi">
                <div class="label">Transacciones</div>
                <div id="kpi-transacciones" class="value">-</div>
                <div class="tooltip">SQL: SELECT COUNT(*) AS c FROM TRANSACCIONES</div>
            </div>
            <div class="card kpi">
                <div class="label">Monto total</div>
                <div id="kpi-monto" class="value">-</div>
                <div class="tooltip">SQL: SELECT COALESCE(SUM(monto),0) s FROM TRANSACCIONES</div>
            </div>
            <div class="card kpi">
                <div class="label">Interacciones</div>
                <div id="kpi-interacciones" class="value">-</div>
                <div class="tooltip">SQL: SELECT COUNT(*) AS c FROM INTERACCIONES</div>
            </div>
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
                    <div class="table-scroll" style="margin-top:8px;">
                        <table style="width:100%; border-collapse:collapse; color:var(--text);">
                            <thead id="theadClientes" style="background:var(--thead-bg);"></thead>
                            <tbody id="tbodyClientes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginationClientes" class="pagination"></div>

            <div class="card sql-card sql-accent-1" style="width:100%">
                <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#06b6d4" stroke-width="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="3" y1="15" x2="21" y2="15" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="9" y1="3" x2="9" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="15" y1="3" x2="15" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                </svg>
                <div class="sql-content">
                    <h3 style="color:var(--text);margin:0">Consulta 2: Tabla propiedades</h3>
                    <pre class="sql-sqltext">SELECT * FROM propiedades LIMIT 10</pre>
                    <div class="table-scroll" style="margin-top:8px;">
                        <table style="width:100%; border-collapse:collapse; color:var(--text);">
                            <thead id="theadPropiedades" style="background:var(--thead-bg);"></thead>
                            <tbody id="tbodyPropiedades"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginationPropiedades" class="pagination"></div>

            <div class="card sql-card sql-accent-1" style="width:100%">
                <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#06b6d4" stroke-width="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="3" y1="15" x2="21" y2="15" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="9" y1="3" x2="9" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="15" y1="3" x2="15" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                </svg>
                <div class="sql-content">
                    <h3 style="color:var(--text);margin:0">Consulta 3: Tabla interacciones</h3>
                    <pre class="sql-sqltext">SELECT * FROM interacciones LIMIT 10</pre>
                    <div class="table-scroll" style="margin-top:8px;">
                        <table style="width:100%; border-collapse:collapse; color:var(--text);">
                            <thead id="theadInteracciones" style="background:var(--thead-bg);"></thead>
                            <tbody id="tbodyInteracciones"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="paginationInteracciones" class="pagination"></div>

        <?php
        $consultaNum = 4;
        if (!empty($extraTables)) {
            foreach ($extraTables as $index => $tableName) {
                if ($consultaNum > 12) {
                    break;
                }
                $suffix = 'Extra' . ($index + 1);
        ?>
            <div class="card sql-card sql-accent-1" style="width:100%">
                <svg class="sql-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="#06b6d4" stroke-width="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="3" y1="15" x2="21" y2="15" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="9" y1="3" x2="9" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                    <line x1="15" y1="3" x2="15" y2="21" stroke="#06b6d4" stroke-width="2"></line>
                </svg>
                <div class="sql-content">
                    <h3 style="color:var(--text);margin:0">Consulta <?= $consultaNum; ?>: Tabla <?= htmlspecialchars($tableName); ?></h3>
                    <pre class="sql-sqltext">SELECT * FROM <?= htmlspecialchars($tableName); ?> LIMIT 10</pre>
                    <div class="table-scroll" style="margin-top:8px;">
                        <table style="width:100%; border-collapse:collapse; color:var(--text);">
                            <thead id="thead<?= $suffix; ?>" style="background:var(--thead-bg);"></thead>
                            <tbody id="tbody<?= $suffix; ?>"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="pagination<?= $suffix; ?>" class="pagination"></div>
        <?php
                $consultaNum++;
            }
        }
        ?>

        </div>
        <?php endif; ?>

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

        if (!DATA.kpis) DATA.kpis = {
            clientes: 0,
            agentes: 0,
            propiedades: 0,
            prop_activas: 0,
            transacciones: 0,
            monto_total_trans: 0,
            interacciones: 0,
            citas_proximas: 0,
            comision_mes: 0
        };
        if (!DATA.clientesPorCiudad) DATA.clientesPorCiudad = {};
        if (!DATA.propPorTipo) DATA.propPorTipo = {};
        if (!DATA.interPorDia) DATA.interPorDia = {
            labels: [],
            data: []
        };
        if (!DATA.interPorMedio) DATA.interPorMedio = {};
        if (!DATA.propPorEstado) DATA.propPorEstado = {};
        if (!DATA.transPorTipo) DATA.transPorTipo = {};
        if (!DATA.transPorDia) DATA.transPorDia = {
            labels: [],
            data: []
        };
        if (!DATA.citasPorDia) DATA.citasPorDia = {
            labels: [],
            data: []
        };
        if (!DATA.caractPopulares) DATA.caractPopulares = {};
        if (!DATA.etiquetasPopulares) DATA.etiquetasPopulares = {};
        if (!DATA.warnings) DATA.warnings = [];

        const warnEl = document.getElementById('warnings');
        if (DATA.warnings && DATA.warnings.length) {
            DATA.warnings.forEach(w => {
                const d = document.createElement('div');
                d.className = 'warning';
                d.textContent = w;
                warnEl.appendChild(d);
            });
        }

        // KPIs (con guarda: en modo demo algunos bloques se renderizan en servidor)
        const num = n => new Intl.NumberFormat('es-AR').format(n);
        const money = n => new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            maximumFractionDigits: 0
        }).format(n);
        const setText = (id, txt) => {
            const el = document.getElementById(id);
            if (el) el.textContent = txt;
        };
        setText('kpi-clientes', num(DATA.kpis.clientes ?? 0));
        setText('kpi-agentes', num(DATA.kpis.agentes ?? 0));
        setText('kpi-propiedades', num(DATA.kpis.propiedades ?? 0));
        setText('kpi-prop-activas', num(DATA.kpis.prop_activas ?? DATA.kpis.propiedades ?? 0));
        setText('kpi-transacciones', num(DATA.kpis.transacciones ?? 0));
        setText('kpi-monto', money(DATA.kpis.monto_total_trans ?? 0));
        setText('kpi-interacciones', num(DATA.kpis.interacciones ?? 0));
        setText('kpi-citas', num(DATA.kpis.citas_proximas ?? 0));
        setText('kpi-comisiones', money(DATA.kpis.comision_mes ?? 0));

        // Configuración de tablas
        if (!DATA.clientesData) DATA.clientesData = [];
        if (!DATA.propiedadesData) DATA.propiedadesData = [];
        if (!DATA.interaccionesData) DATA.interaccionesData = [];
        if (!Array.isArray(DATA.extraTables)) DATA.extraTables = [];
        if (!DATA.extraTablesData) DATA.extraTablesData = {};
        if (!DATA.extraTablesTotals) DATA.extraTablesTotals = {};
        if (!DATA.totalClientes) DATA.totalClientes = DATA.clientesData.length;
        if (!DATA.totalPropiedades) DATA.totalPropiedades = DATA.propiedadesData.length;
        if (!DATA.totalInteracciones) DATA.totalInteracciones = DATA.interaccionesData.length;
        if (!DATA.page1) DATA.page1 = 1;
        if (!DATA.page2) DATA.page2 = 1;
        if (!DATA.page3) DATA.page3 = 1;

        console.log('DATA:', DATA);

        const rowsPerPage = 10;
        const tableConfigs = [
            {
                key: 'clientes',
                tableName: 'CLIENTES',
                headId: 'theadClientes',
                bodyId: 'tbodyClientes',
                paginationId: 'paginationClientes',
                data: DATA.clientesData,
                total: DATA.totalClientes,
                initialPage: DATA.page1
            },
            {
                key: 'propiedades',
                tableName: 'PROPIEDADES',
                headId: 'theadPropiedades',
                bodyId: 'tbodyPropiedades',
                paginationId: 'paginationPropiedades',
                data: DATA.propiedadesData,
                total: DATA.totalPropiedades,
                initialPage: DATA.page2
            },
            {
                key: 'interacciones',
                tableName: 'INTERACCIONES',
                headId: 'theadInteracciones',
                bodyId: 'tbodyInteracciones',
                paginationId: 'paginationInteracciones',
                data: DATA.interaccionesData,
                total: DATA.totalInteracciones,
                initialPage: DATA.page3
            }
        ];

        DATA.extraTables.forEach((tableName, idx) => {
            const suffix = `Extra${idx + 1}`;
            tableConfigs.push({
                key: `extra${idx + 1}`,
                tableName,
                headId: `thead${suffix}`,
                bodyId: `tbody${suffix}`,
                paginationId: `pagination${suffix}`,
                data: (DATA.extraTablesData[tableName] || []),
                total: (DATA.extraTablesTotals[tableName] ?? (DATA.extraTablesData[tableName] ? DATA.extraTablesData[tableName].length : 0)),
                initialPage: 1
            });
        });

        const configMap = {};

        const escHtml = value => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        const formatCell = value => {
            if (value === null || value === undefined) return '';
            if (typeof value === 'object') {
                try {
                    return escHtml(JSON.stringify(value));
                } catch (err) {
                    return '';
                }
            }
            return escHtml(value);
        };

        const renderTable = (config, data, total, page) => {
            const thead = document.getElementById(config.headId);
            const tbody = document.getElementById(config.bodyId);
            const paginationEl = document.getElementById(config.paginationId);
            if (!thead || !tbody || !paginationEl) return;

            if (!data || data.length === 0) {
                thead.innerHTML = '';
                tbody.innerHTML = '<tr><td colspan="1" style="text-align:center; color:#94a3b8;">Sin datos</td></tr>';
                paginationEl.innerHTML = '';
                config.currentPage = page;
                config.total = total;
                return;
            }

            const headers = Object.keys(data[0]);
            thead.innerHTML = '<tr>' + headers.map(h => '<th style="padding:8px; border:1px solid #374151;">' + escHtml(h) + '</th>').join('') + '</tr>';
            tbody.innerHTML = data.map(row => '<tr>' + headers.map(h => '<td style="padding:8px; border:1px solid #374151;">' + formatCell(row[h]) + '</td>').join('') + '</tr>').join('');

            const totalForCalc = total && total > 0 ? total : data.length;
            const totalPages = Math.max(1, Math.ceil(totalForCalc / rowsPerPage));
            let html = '';
            for (let p = 1; p <= totalPages; p++) {
                const activeClass = p === page ? ' active' : '';
                html += '<button class="pagination-btn' + activeClass + '" onclick="changePage(\'' + config.key + '\',' + p + ')">' + p + '</button>';
            }
            paginationEl.innerHTML = totalPages > 0 ? html : '';

            config.currentPage = page;
            config.total = totalForCalc;
        };

        tableConfigs.forEach(config => {
            configMap[config.key] = config;
            renderTable(config, config.data, config.total, config.initialPage || 1);
        });

        function changePage(key, newPage) {
            const config = configMap[key];
            if (!config) return;
            fetch('?ajax=1&table=' + encodeURIComponent(config.tableName) + '&page=' + newPage)
                .then(response => response.json())
                .then(response => {
                    if (!response || !Array.isArray(response.data)) return;
                    const total = typeof response.total === 'number' ? response.total : response.data.length;
                    const page = typeof response.page === 'number' ? response.page : newPage;
                    renderTable(config, response.data, total, page);
                })
                .catch(err => console.error('Error fetching data for', config.tableName, err));
        }

        window.changePage = changePage;
    </script>
</body>

</html>
