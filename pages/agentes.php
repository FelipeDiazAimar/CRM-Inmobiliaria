<?php
// pages/agentes.php — Módulo AGENTES (AGENTE C-8).
// Un solo HTML; bifurcación interna demo/real en la capa de datos.
// DEMO (sin MySQL): CRUD en $_SESSION. REAL: misma capa contra MySQL (prepared statements).
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: text/html; charset=utf-8');

define('C8_COMISION_TASA', 0.03); // 3% del monto de cada transacción de sus propiedades.

$demoFile = __DIR__ . '/demo_data.php';
if (file_exists($demoFile)) {
    require_once $demoFile; // Contrato C-1: demo_activo(), demo_datos(), demo_guardar()/demo_escribir()
}

// ---------- Conexión (sin errores SQL visibles, como propiedades.php) ----------
$db_ok = extension_loaded('mysqli');
$conn = null;
if ($db_ok && function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}
if ($db_ok) {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_errno) {
        $db_ok = false;
        $conn = null;
    } elseif (defined('DB_CHARSET')) {
        @$conn->set_charset(DB_CHARSET);
    }
}
$demo = !$db_ok; // Sin MySQL: MODO DEMO.
if (function_exists('demo_activo')) {
    try {
        $demo = ((bool)demo_activo()) || !$db_ok;
    } catch (Throwable $e) {
        $demo = !$db_ok;
    }
}

// ---------- Seed demo (columnas de la tabla AGENTES del .sql) ----------
function c8_seed_demo()
{
    if (!empty($_SESSION['c8_demo_agentes'])) {
        return;
    }
    $_SESSION['c8_demo_agentes'] = [
        ['id_agente' => 1, 'nombre' => 'Carlos', 'apellido' => 'Ramírez', 'email' => 'carlosr@mail.com', 'telefono' => '3411234563', 'matricula' => 'AG001', 'fecha_ingreso' => '2018-03-15'],
        ['id_agente' => 2, 'nombre' => 'Laura', 'apellido' => 'Fernández', 'email' => 'lauraf@mail.com', 'telefono' => '3411234564', 'matricula' => 'AG002', 'fecha_ingreso' => '2020-06-01'],
        ['id_agente' => 3, 'nombre' => 'Lucía', 'apellido' => 'Torres', 'email' => 'luciatorres@mail.com', 'telefono' => '3411234565', 'matricula' => 'AG003', 'fecha_ingreso' => '2015-09-20'],
        ['id_agente' => 4, 'nombre' => 'Martín', 'apellido' => 'Rojas', 'email' => 'martinr@mail.com', 'telefono' => '3411234566', 'matricula' => 'AG004', 'fecha_ingreso' => '2022-01-10'],
        ['id_agente' => 5, 'nombre' => 'Carlos', 'apellido' => 'Ramírez', 'email' => 'carlosr2@mail.com', 'telefono' => '3411234567', 'matricula' => 'AG005', 'fecha_ingreso' => '2017-11-05'],
        ['id_agente' => 6, 'nombre' => 'Laura', 'apellido' => 'Fernández', 'email' => 'lauraf2@mail.com', 'telefono' => '3411234568', 'matricula' => 'AG006', 'fecha_ingreso' => '2019-04-18'],
        ['id_agente' => 7, 'nombre' => 'Lucía', 'apellido' => 'Torres', 'email' => 'luciatorres2@mail.com', 'telefono' => '3411234569', 'matricula' => 'AG007', 'fecha_ingreso' => '2013-07-30'],
        ['id_agente' => 8, 'nombre' => 'Martín', 'apellido' => 'Rojas', 'email' => 'martinr2@mail.com', 'telefono' => '3411234570', 'matricula' => 'AG008', 'fecha_ingreso' => '2024-02-12'],
        ['id_agente' => 9, 'nombre' => 'Carlos', 'apellido' => 'Ramírez', 'email' => 'carlosr3@mail.com', 'telefono' => '3411234571', 'matricula' => 'AG009', 'fecha_ingreso' => '2016-08-25'],
        ['id_agente' => 10, 'nombre' => 'Laura', 'apellido' => 'Fernández', 'email' => 'lauraf3@mail.com', 'telefono' => '3411234572', 'matricula' => 'AG010', 'fecha_ingreso' => '2021-12-05'],
    ];
    // Mapa agente->propiedades y montos de TRANSACCIONES del .sql (solo lo necesario para stats).
    $_SESSION['c8_demo_propiedades'] = [
        ['id_propiedad' => 1, 'id_agente' => 1, 'titulo' => 'Casa con pileta'],
        ['id_propiedad' => 2, 'id_agente' => 2, 'titulo' => 'Departamento céntrico'],
        ['id_propiedad' => 3, 'id_agente' => 3, 'titulo' => 'Terreno zona sur'],
        ['id_propiedad' => 4, 'id_agente' => 4, 'titulo' => 'Oficina moderna'],
        ['id_propiedad' => 5, 'id_agente' => 1, 'titulo' => 'Casa de lujo'],
        ['id_propiedad' => 6, 'id_agente' => 2, 'titulo' => 'Departamento económico'],
        ['id_propiedad' => 7, 'id_agente' => 3, 'titulo' => 'Galpón industrial'],
        ['id_propiedad' => 8, 'id_agente' => 4, 'titulo' => 'Local comercial'],
        ['id_propiedad' => 9, 'id_agente' => 1, 'titulo' => 'Casa antigua reciclada'],
        ['id_propiedad' => 10, 'id_agente' => 2, 'titulo' => 'Departamento con balcón'],
    ];
    $_SESSION['c8_demo_transacciones'] = [
        ['id_transaccion' => 1, 'id_propiedad' => 1, 'monto' => 250000],
        ['id_transaccion' => 2, 'id_propiedad' => 2, 'monto' => 120000],
        ['id_transaccion' => 3, 'id_propiedad' => 3, 'monto' => 60000],
        ['id_transaccion' => 4, 'id_propiedad' => 4, 'monto' => 95000],
        ['id_transaccion' => 5, 'id_propiedad' => 5, 'monto' => 500000],
        ['id_transaccion' => 6, 'id_propiedad' => 6, 'monto' => 80000],
        ['id_transaccion' => 7, 'id_propiedad' => 7, 'monto' => 150000],
        ['id_transaccion' => 8, 'id_propiedad' => 8, 'monto' => 90000],
        ['id_transaccion' => 9, 'id_propiedad' => 9, 'monto' => 180000],
        ['id_transaccion' => 10, 'id_propiedad' => 10, 'monto' => 140000],
    ];
}

// Lee un conjunto demo: prefiere el contrato C-1 si está disponible, si no la sesión propia.
function c8_demo_leer($tipo)
{
    if (function_exists('demo_datos')) {
        try {
            $d = demo_datos($tipo);
            if (is_array($d) && count($d) > 0) {
                return array_values($d);
            }
        } catch (Throwable $e) {
        }
    }
    return isset($_SESSION['c8_demo_' . $tipo]) ? array_values($_SESSION['c8_demo_' . $tipo]) : [];
}

// Persiste un conjunto demo: siempre en sesión propia + best-effort al contrato C-1.
function c8_demo_persistir($tipo, $todos)
{
    $_SESSION['c8_demo_' . $tipo] = array_values($todos);
    if (function_exists('demo_escribir')) {
        try {
            demo_escribir($tipo, array_values($todos));
        } catch (Throwable $e) {
        }
    }
}

// Best-effort de compatibilidad con el contrato C-1 (firma demo_guardar('agentes', $item)).
// Nota: demo_guardar() solo agrega; para crear/actualizar/eliminar usamos demo_escribir()
// (reemplazo de colección), así que esta vía no se usa en el flujo normal.
function c8_demo_upsert_contrato($item)
{
    return $item;
}

function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helpers multibyte con fallback (este PHP puede no tener mbstring).
function c8_len($s)
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($s, 'UTF-8');
    }
    if (function_exists('preg_match_all') && @preg_match_all('/./us', $s, $m)) {
        return count($m[0]);
    }
    return strlen($s);
}
function c8_initial($s)
{
    $ch = '?';
    if (function_exists('mb_substr')) {
        $ch = mb_substr($s, 0, 1, 'UTF-8');
        return function_exists('mb_strtoupper') ? mb_strtoupper($ch, 'UTF-8') : strtoupper($ch);
    }
    if (@preg_match('/^./us', $s, $m)) {
        $ch = $m[0];
    } else {
        $ch = substr($s, 0, 1);
    }
    return strtoupper($ch);
}

// ---------- CAPA DE DATOS (bifurcación interna demo/real) ----------
function agentes_listar()
{
    global $conn, $demo;
    if ($demo) {
        c8_seed_demo();
        $a = c8_demo_leer('agentes');
        return $a;
    }
    $out = [];
    $res = @$conn->query('SELECT id_agente, nombre, apellido, email, telefono, matricula, fecha_ingreso FROM AGENTES ORDER BY apellido, nombre');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[] = $row;
        }
        $res->free();
    }
    return $out;
}

function agentes_obtener($id)
{
    foreach (agentes_listar() as $a) {
        if ((int)$a['id_agente'] === (int)$id) {
            return $a;
        }
    }
    return null;
}

// Stats: propiedades asignadas + comisiones (3% del monto de transacciones de sus propiedades).
function agentes_stats()
{
    global $conn, $demo;
    $conteo = [];
    $volumen = [];
    if ($demo) {
        c8_seed_demo();
        $props = c8_demo_leer('propiedades');
        $trans = c8_demo_leer('transacciones');
        $propAgente = [];
        foreach ($props as $p) {
            $aid = (int)($p['id_agente'] ?? 0);
            $conteo[$aid] = ($conteo[$aid] ?? 0) + 1;
            $propAgente[(int)$p['id_propiedad']] = $aid;
        }
        foreach ($trans as $t) {
            $aid = $propAgente[(int)($t['id_propiedad'] ?? 0)] ?? 0;
            if ($aid) {
                $volumen[$aid] = ($volumen[$aid] ?? 0) + (float)($t['monto'] ?? 0);
            }
        }
    } else {
        $r1 = @$conn->query('SELECT id_agente, COUNT(*) n FROM PROPIEDADES GROUP BY id_agente');
        if ($r1) {
            while ($r = $r1->fetch_assoc()) {
                $conteo[(int)$r['id_agente']] = (int)$r['n'];
            }
            $r1->free();
        }
        $r2 = @$conn->query('SELECT P.id_agente aid, SUM(T.monto) vol FROM TRANSACCIONES T JOIN PROPIEDADES P ON P.id_propiedad = T.id_propiedad GROUP BY P.id_agente');
        if ($r2) {
            while ($r = $r2->fetch_assoc()) {
                $volumen[(int)$r['aid']] = (float)$r['vol'];
            }
            $r2->free();
        }
    }
    $stats = [];
    foreach (array_unique(array_merge(array_keys($conteo), array_keys($volumen))) as $aid) {
        $vol = $volumen[$aid] ?? 0;
        $stats[$aid] = [
            'props' => $conteo[$aid] ?? 0,
            'volumen' => $vol,
            'comision' => $vol * C8_COMISION_TASA,
        ];
    }
    return $stats;
}

function agentes_validar($d, $id_actual = 0)
{
    $err = [];
    $nombre = trim($d['nombre'] ?? '');
    $apellido = trim($d['apellido'] ?? '');
    $email = trim($d['email'] ?? '');
    $telefono = trim($d['telefono'] ?? '');
    $matricula = trim($d['matricula'] ?? '');
    $fecha = trim($d['fecha_ingreso'] ?? '');
    if ($nombre === '' || c8_len($nombre) > 50) {
        $err[] = 'Nombre requerido (máx. 50 caracteres).';
    }
    if ($apellido === '' || c8_len($apellido) > 50) {
        $err[] = 'Apellido requerido (máx. 50 caracteres).';
    }
    if ($email === '' || c8_len($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err[] = 'Email válido requerido (máx. 100 caracteres).';
    }
    if (c8_len($telefono) > 20) {
        $err[] = 'Teléfono: máx. 20 caracteres.';
    }
    if ($matricula === '' || c8_len($matricula) > 20) {
        $err[] = 'Matrícula requerida (máx. 20 caracteres).';
    }
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) === false || $fecha > date('Y-m-d')) {
        $err[] = 'Fecha de ingreso válida requerida (no futura, formato AAAA-MM-DD).';
    }
    // Unicidad de email y matrícula.
    foreach (agentes_listar() as $a) {
        if ((int)$a['id_agente'] === (int)$id_actual) {
            continue;
        }
        if (strcasecmp((string)$a['email'], $email) === 0) {
            $err[] = 'Ese email ya está asignado a otro agente.';
        }
        if (strcasecmp((string)$a['matricula'], $matricula) === 0) {
            $err[] = 'Esa matrícula ya está asignada a otro agente.';
        }
    }
    return $err;
}

function agentes_crear($d)
{
    global $conn, $demo;
    $item = [
        'nombre' => trim($d['nombre'] ?? ''),
        'apellido' => trim($d['apellido'] ?? ''),
        'email' => trim($d['email'] ?? ''),
        'telefono' => trim($d['telefono'] ?? ''),
        'matricula' => trim($d['matricula'] ?? ''),
        'fecha_ingreso' => trim($d['fecha_ingreso'] ?? ''),
    ];
    if ($demo) {
        $todos = c8_demo_leer('agentes');
        $max = 0;
        foreach ($todos as $a) {
            $max = max($max, (int)$a['id_agente']);
        }
        $item['id_agente'] = $max + 1;
        $todos[] = $item;
        c8_demo_persistir('agentes', $todos); // demo_escribir() cubre crear/actualizar/eliminar
        return true;
    }
    $stmt = @$conn->prepare('INSERT INTO AGENTES (nombre, apellido, email, telefono, matricula, fecha_ingreso) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssssss', $item['nombre'], $item['apellido'], $item['email'], $item['telefono'], $item['matricula'], $item['fecha_ingreso']);
    $ok = @$stmt->execute();
    $stmt->close();
    return $ok;
}

function agentes_actualizar($id, $d)
{
    global $conn, $demo;
    $item = [
        'nombre' => trim($d['nombre'] ?? ''),
        'apellido' => trim($d['apellido'] ?? ''),
        'email' => trim($d['email'] ?? ''),
        'telefono' => trim($d['telefono'] ?? ''),
        'matricula' => trim($d['matricula'] ?? ''),
        'fecha_ingreso' => trim($d['fecha_ingreso'] ?? ''),
    ];
    if ($demo) {
        $todos = c8_demo_leer('agentes');
        $ok = false;
        foreach ($todos as &$a) {
            if ((int)$a['id_agente'] === (int)$id) {
                $a = array_merge($a, $item);
                $ok = true;
                break;
            }
        }
        unset($a);
        if ($ok) {
            c8_demo_persistir('agentes', $todos);
        }
        return $ok;
    }
    $stmt = @$conn->prepare('UPDATE AGENTES SET nombre=?, apellido=?, email=?, telefono=?, matricula=?, fecha_ingreso=? WHERE id_agente=?');
    if (!$stmt) {
        return false;
    }
    $id = (int)$id;
    $stmt->bind_param('ssssssi', $item['nombre'], $item['apellido'], $item['email'], $item['telefono'], $item['matricula'], $item['fecha_ingreso'], $id);
    $ok = @$stmt->execute();
    $stmt->close();
    return $ok;
}

// Cantidad de propiedades asignadas (para decidir bloqueo/reassign al eliminar).
function agentes_props_count($id)
{
    global $conn, $demo;
    if ($demo) {
        $n = 0;
        foreach (c8_demo_leer('propiedades') as $p) {
            if ((int)($p['id_agente'] ?? 0) === (int)$id) {
                $n++;
            }
        }
        return $n;
    }
    $stmt = @$conn->prepare('SELECT COUNT(*) n FROM PROPIEDADES WHERE id_agente=?');
    if (!$stmt) {
        return 0;
    }
    $id = (int)$id;
    $stmt->bind_param('i', $id);
    $n = 0;
    if (@$stmt->execute()) {
        $r = $stmt->get_result();
        if ($r && ($row = $r->fetch_assoc())) {
            $n = (int)$row['n'];
        }
    }
    $stmt->close();
    return $n;
}

// Elimina. Si tiene propiedades exige $reasignar_a (id de otro agente); si no, devuelve 'bloqueado'.
function agentes_eliminar($id, $reasignar_a = 0)
{
    global $conn, $demo;
    $id = (int)$id;
    $nprops = agentes_props_count($id);
    if ($nprops > 0 && !(int)$reasignar_a) {
        return 'bloqueado';
    }
    if ($demo) {
        if ($nprops > 0) {
            // Validar destino: debe existir y ser distinto del eliminado (evita huérfanos).
            $dest_ok = false;
            foreach (c8_demo_leer('agentes') as $cand) {
                if ((int)$cand['id_agente'] === (int)$reasignar_a && (int)$reasignar_a !== $id) {
                    $dest_ok = true;
                    break;
                }
            }
            if (!$dest_ok) {
                return false;
            }
            $props = c8_demo_leer('propiedades');
            foreach ($props as &$p) {
                if ((int)($p['id_agente'] ?? 0) === $id) {
                    $p['id_agente'] = (int)$reasignar_a;
                }
            }
            unset($p);
            c8_demo_persistir('propiedades', $props);
        }
        $todos = array_values(array_filter(c8_demo_leer('agentes'), function ($a) use ($id) {
            return (int)$a['id_agente'] !== $id;
        }));
        c8_demo_persistir('agentes', $todos);
        return true;
    }
    @$conn->begin_transaction();
    if ($nprops > 0) {
        $rea = (int)$reasignar_a;
        if ($rea === $id || !agentes_obtener($rea)) {
            @$conn->rollback();
            return false;
        }
        $up = @$conn->prepare('UPDATE PROPIEDADES SET id_agente=? WHERE id_agente=?');
        if (!$up) {
            @$conn->rollback();
            return false;
        }
        $up->bind_param('ii', $rea, $id);
        if (!@$up->execute()) {
            $up->close();
            @$conn->rollback();
            return false;
        }
        $up->close();
    }
    $del = @$conn->prepare('DELETE FROM AGENTES WHERE id_agente=?');
    if (!$del) {
        @$conn->rollback();
        return false;
    }
    $del->bind_param('i', $id);
    $ok = @$del->execute();
    $del->close();
    if ($ok) {
        @$conn->commit();
    } else {
        @$conn->rollback();
    }
    return $ok;
}

// ---------- POST ----------
$msg = trim($_GET['msg'] ?? '');
$errores = [];
$form = [];
$modal_abrir = ''; // id del modal a reabrir si hay error de validación
$confirm_delete = 0; // id de agente pendiente de confirmación con reasignación

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear' || $accion === 'actualizar') {
        $id = (int)($_POST['id_agente'] ?? 0);
        $form = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'matricula' => $_POST['matricula'] ?? '',
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?? '',
        ];
        $errores = agentes_validar($form, $accion === 'actualizar' ? $id : 0);
        if (empty($errores)) {
            $ok = $accion === 'crear' ? agentes_crear($form) : agentes_actualizar($id, $form);
            if ($ok) {
                header('Location: agentes.php?msg=' . urlencode($accion === 'crear' ? 'Agente creado correctamente.' : 'Agente actualizado correctamente.'));
                exit;
            }
            $errores[] = 'No se pudo guardar el agente. Intentá nuevamente.';
            $modal_abrir = $accion === 'crear' ? 'modal-nuevo' : 'modal-editar-' . $id;
        } else {
            $modal_abrir = $accion === 'crear' ? 'modal-nuevo' : 'modal-editar-' . $id;
        }
    } elseif ($accion === 'eliminar') {
        $id = (int)($_POST['id_agente'] ?? 0);
        $rea = (int)($_POST['reasignar_a'] ?? 0);
        $res = agentes_eliminar($id, $rea);
        if ($res === true) {
            header('Location: agentes.php?msg=' . urlencode($rea ? 'Agente eliminado. Propiedades reasignadas.' : 'Agente eliminado correctamente.'));
            exit;
        } elseif ($res === 'bloqueado') {
            $confirm_delete = $id; // se reabre el modal con aviso elegante + select de reasignación
        } else {
            $errores[] = 'No se pudo eliminar el agente. Intentá nuevamente.';
        }
    }
}

$agentes = agentes_listar();
$stats = agentes_stats();
foreach ($agentes as &$a) {
    $s = $stats[(int)$a['id_agente']] ?? ['props' => 0, 'volumen' => 0, 'comision' => 0];
    $a['_props'] = $s['props'];
    $a['_volumen'] = $s['volumen'];
    $a['_comision'] = $s['comision'];
}
unset($a);
// Ranking por comisiones (desc).
$ranking = $agentes;
usort($ranking, function ($x, $y) {
    return $y['_comision'] <=> $x['_comision'];
});
$ranking = array_slice($ranking, 0, 5);

function c8_iniciales($a)
{
    $n = trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''));
    $parts = preg_split('/\s+/', $n);
    $ini = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $ini .= c8_initial($p);
        }
        if (c8_len($ini) >= 2) {
            break;
        }
    }
    return $ini !== '' ? $ini : '?';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Agentes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilos/agentes.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <header>
        <h1>CRM Inmobiliaria - Agentes</h1>
    </header>
    <main class="main-content">
        <div class="container">
            <h1>Equipo de ventas</h1>
            <p class="subtitle">Vendedores, propiedades asignadas y comisiones generadas.</p>

            <?php if ($demo): ?>
                <div class="demo-badge">Modo demo: sin conexión MySQL. Los cambios se guardan en la sesión.</div>
            <?php endif; ?>
            <?php if ($msg !== ''): ?>
                <div class="ok"><?php echo e($msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($errores)): ?>
                <div class="error">No se pudo completar la operación:<br><?php echo implode('<br>', array_map('e', $errores)); ?></div>
            <?php endif; ?>

            <div class="toolbar">
                <button class="open-modal btn" data-target="modal-nuevo">+ Nuevo agente</button>
            </div>

            <section class="ranking">
                <h2>Ranking por comisiones <span class="ranking-hint">(3% del monto operado)</span></h2>
                <?php if (empty($ranking)): ?>
                    <p class="empty">Todavía no hay agentes para rankear. Creá el primero con “+ Nuevo agente”.</p>
                <?php else: ?>
                    <ol class="ranking-list">
                        <?php $pos = 1; foreach ($ranking as $r): ?>
                            <li class="ranking-item pos-<?php echo $pos; ?>">
                                <span class="ranking-pos"><?php echo $pos; ?>º</span>
                                <span class="ranking-name"><?php echo e(trim($r['nombre'] . ' ' . $r['apellido'])); ?></span>
                                <span class="ranking-com">$<?php echo number_format($r['_comision'], 0, ',', '.'); ?></span>
                            </li>
                        <?php $pos++; endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>

            <?php if (empty($agentes)): ?>
                <div class="empty-block">
                    <p class="empty-title">Aún no hay agentes cargados</p>
                    <p class="empty">Sumá a tu primer vendedor para empezar a asignar propiedades y medir comisiones.</p>
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($agentes as $a):
                        $aid = (int)$a['id_agente'];
                        $nom_completo = trim($a['nombre'] . ' ' . $a['apellido']);
                    ?>
                        <div class="card">
                            <div class="ag-head">
                                <div class="ag-avatar" aria-hidden="true"><?php echo e(c8_iniciales($a)); ?></div>
                                <div>
                                    <h2><?php echo e($nom_completo); ?></h2>
                                    <p class="ag-mat">Matrícula <?php echo e($a['matricula']); ?></p>
                                </div>
                            </div>
                            <p class="ag-contact"><?php echo e($a['email']); ?><br><?php echo e($a['telefono']); ?></p>
                            <p class="ag-ingreso">En el equipo desde <?php echo e($a['fecha_ingreso']); ?></p>
                            <div class="ag-stats">
                                <div class="ag-stat"><span class="ag-stat-n"><?php echo (int)$a['_props']; ?></span><span class="ag-stat-l">propiedades</span></div>
                                <div class="ag-stat"><span class="ag-stat-n">$<?php echo number_format($a['_volumen'], 0, ',', '.'); ?></span><span class="ag-stat-l">volumen</span></div>
                                <div class="ag-stat"><span class="ag-stat-n">$<?php echo number_format($a['_comision'], 0, ',', '.'); ?></span><span class="ag-stat-l">comisión</span></div>
                            </div>
                            <div class="card-actions">
                                <button class="open-modal btn-small" data-target="modal-editar-<?php echo $aid; ?>">Editar</button>
                                <button class="open-modal btn-small btn-danger" data-target="modal-del-<?php echo $aid; ?>">Eliminar</button>
                            </div>
                        </div>

                        <div id="modal-editar-<?php echo $aid; ?>" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
                            <div class="modal-content">
                                <button class="modal-close" data-target="modal-editar-<?php echo $aid; ?>" aria-label="Cerrar ventana">×</button>
                                <h2>Editar agente</h2>
                                <form method="post" class="ag-form">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="id_agente" value="<?php echo $aid; ?>">
                                    <label>Nombre<input name="nombre" maxlength="50" required value="<?php echo e($a['nombre']); ?>"></label>
                                    <label>Apellido<input name="apellido" maxlength="50" required value="<?php echo e($a['apellido']); ?>"></label>
                                    <label>Email<input name="email" type="email" maxlength="100" required value="<?php echo e($a['email']); ?>"></label>
                                    <label>Teléfono<input name="telefono" maxlength="20" value="<?php echo e($a['telefono']); ?>"></label>
                                    <label>Matrícula<input name="matricula" maxlength="20" required value="<?php echo e($a['matricula']); ?>"></label>
                                    <label>Fecha de ingreso<input name="fecha_ingreso" type="date" max="<?php echo e(date('Y-m-d')); ?>" required value="<?php echo e($a['fecha_ingreso']); ?>"></label>
                                    <div class="form-actions">
                                        <button type="submit" class="btn">Guardar cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="modal-del-<?php echo $aid; ?>" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
                            <div class="modal-content">
                                <button class="modal-close" data-target="modal-del-<?php echo $aid; ?>" aria-label="Cerrar ventana">×</button>
                                <h2>Eliminar agente</h2>
                                <?php if ((int)$a['_props'] > 0): ?>
                                    <div class="warn-box">
                                        <p><strong><?php echo e($nom_completo); ?></strong> tiene <strong><?php echo (int)$a['_props']; ?> propiedad(es)</strong> asignada(s).</p>
                                        <p>Para eliminarlo, reasigná esas propiedades a otro vendedor del equipo:</p>
                                    </div>
                                    <form method="post" class="ag-form">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_agente" value="<?php echo $aid; ?>">
                                        <label>Reasignar propiedades a
                                            <select name="reasignar_a" required>
                                                <option value="">— Elegí un agente —</option>
                                                <?php foreach ($agentes as $cand):
                                                    if ((int)$cand['id_agente'] === $aid) continue; ?>
                                                    <option value="<?php echo (int)$cand['id_agente']; ?>"><?php echo e(trim($cand['nombre'] . ' ' . $cand['apellido'])); ?> (<?php echo e($cand['matricula']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <?php if (count($agentes) < 2): ?>
                                            <div class="warn-box"><p>No hay otro agente disponible: no se puede eliminar sin dejar propiedades huérfanas.</p></div>
                                        <?php else: ?>
                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-danger">Reasignar y eliminar</button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <p>¿Eliminar a <strong><?php echo e($nom_completo); ?></strong>? No tiene propiedades asignadas. Esta acción no se puede deshacer.</p>
                                    <form method="post" class="ag-form">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_agente" value="<?php echo $aid; ?>">
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="modal-nuevo" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="modal-content">
                    <button class="modal-close" data-target="modal-nuevo" aria-label="Cerrar ventana">×</button>
                    <h2>Nuevo agente</h2>
                    <form method="post" class="ag-form">
                        <input type="hidden" name="accion" value="crear">
                        <label>Nombre<input name="nombre" maxlength="50" required value="<?php echo e($form['nombre'] ?? ''); ?>"></label>
                        <label>Apellido<input name="apellido" maxlength="50" required value="<?php echo e($form['apellido'] ?? ''); ?>"></label>
                        <label>Email<input name="email" type="email" maxlength="100" required value="<?php echo e($form['email'] ?? ''); ?>"></label>
                        <label>Teléfono<input name="telefono" maxlength="20" value="<?php echo e($form['telefono'] ?? ''); ?>"></label>
                        <label>Matrícula<input name="matricula" maxlength="20" required value="<?php echo e($form['matricula'] ?? ''); ?>"></label>
                        <label>Fecha de ingreso<input name="fecha_ingreso" type="date" max="<?php echo e(date('Y-m-d')); ?>" required value="<?php echo e($form['fecha_ingreso'] ?? ''); ?>"></label>
                        <div class="form-actions">
                            <button type="submit" class="btn">Crear agente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
    <script>
        function openModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active')) {
                document.body.classList.remove('modal-open');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.open-modal').forEach(function(button) {
                button.addEventListener('click', function() {
                    openModal(this.dataset.target);
                });
            });
            document.querySelectorAll('.modal-close').forEach(function(button) {
                button.addEventListener('click', function() {
                    closeModal(this.dataset.target);
                });
            });
            document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
                overlay.addEventListener('click', function(event) {
                    if (event.target === overlay) {
                        closeModal(overlay.id);
                    }
                });
            });
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    var activeModal = document.querySelector('.modal-overlay.active');
                    if (activeModal) {
                        closeModal(activeModal.id);
                    }
                }
            });
            <?php if ($modal_abrir !== ''): ?>
                openModal(<?php echo json_encode($modal_abrir); ?>);
            <?php endif; ?>
            <?php if ($confirm_delete > 0): ?>
                openModal('modal-del-<?php echo (int)$confirm_delete; ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
