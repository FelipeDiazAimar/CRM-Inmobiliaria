<?php
// pages/clientes.php — Módulo Clientes (AGENTE C-5)
// Demo (sin MySQL, contrato C-1 pages/demo_data.php) o DB real con la misma vista.
// Capa: clientes_listar / clientes_obtener / clientes_guardar / clientes_borrar
//       + clientes_interacciones / clientes_prop_interes (ficha).
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Contrato demo de C-1 (opcional: puede no existir todavía).
$__demoFile = __DIR__ . '/demo_data.php';
if (file_exists($__demoFile)) {
    require_once $__demoFile;
}

header('Content-Type: text/html; charset=utf-8');

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// ---------- Conexión (igual patrón que inicio/propiedades) ----------
$db_ok = true;
$conn = null;
if (!extension_loaded('mysqli')) {
    $db_ok = false;
} else {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if (!$conn || $conn->connect_errno) {
        $db_ok = false;
        $conn = null;
    } elseif (defined('DB_CHARSET')) {
        @$conn->set_charset(DB_CHARSET);
    }
}

// ---------- Helpers ----------
function e($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/** ¿Estamos en MODO DEMO? Contrato C-1: demo_activo(): bool */
function clientes_es_demo($db_ok) {
    if (function_exists('demo_activo')) {
        try {
            return (bool)demo_activo();
        } catch (Throwable $t) {
            return !$db_ok;
        }
    }
    return !$db_ok;
}

/** Semilla demo (10 filas del .sql) para cuando demo_data.php aún no existe. */
function clientes_seed() {
    return [
        ['id_cliente' => 1, 'nombre' => 'Juan', 'apellido' => 'Pérez', 'email' => 'juanp@mail.com', 'telefono' => '3411234561', 'direccion' => 'Calle Falsa 123', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '12345678'],
        ['id_cliente' => 2, 'nombre' => 'María', 'apellido' => 'Gómez', 'email' => 'mariag@mail.com', 'telefono' => '3411234562', 'direccion' => 'Av. Siempre Viva 742', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '87654321'],
        ['id_cliente' => 3, 'nombre' => 'Pedro', 'apellido' => 'Santos', 'email' => 'pedros@mail.com', 'telefono' => '3411234563', 'direccion' => 'Calle Sol 45', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '23456789'],
        ['id_cliente' => 4, 'nombre' => 'Ana', 'apellido' => 'López', 'email' => 'anal@mail.com', 'telefono' => '3411234564', 'direccion' => 'Av. Luna 12', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '34567890'],
        ['id_cliente' => 5, 'nombre' => 'Sofía', 'apellido' => 'Vega', 'email' => 'sofiav@mail.com', 'telefono' => '3411234565', 'direccion' => 'Calle Estrella 78', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '45678901'],
        ['id_cliente' => 6, 'nombre' => 'Diego', 'apellido' => 'Molina', 'email' => 'diegom@mail.com', 'telefono' => '3411234566', 'direccion' => 'Av. Libertad 22', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '56789012'],
        ['id_cliente' => 7, 'nombre' => 'Lucía', 'apellido' => 'Torres', 'email' => 'luciatorres@mail.com', 'telefono' => '3411234567', 'direccion' => 'Calle Río 56', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '67890123'],
        ['id_cliente' => 8, 'nombre' => 'Martín', 'apellido' => 'Rojas', 'email' => 'martinr@mail.com', 'telefono' => '3411234568', 'direccion' => 'Calle Mar 89', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '78901234'],
        ['id_cliente' => 9, 'nombre' => 'Laura', 'apellido' => 'Fernández', 'email' => 'lauraf@mail.com', 'telefono' => '3411234569', 'direccion' => 'Av. Tierra 5', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '89012345'],
        ['id_cliente' => 10, 'nombre' => 'Carlos', 'apellido' => 'Ramírez', 'email' => 'carlosr@mail.com', 'telefono' => '3411234570', 'direccion' => 'Calle Cielo 100', 'ciudad' => 'Rosario', 'provincia' => 'Santa Fe', 'dni' => '90123456'],
    ];
}

function clientes_demo_todos() {
    if (function_exists('demo_datos')) {
        try {
            $d = demo_datos('clientes');
            if (is_array($d)) return array_values($d);
        } catch (Throwable $t) {}
    }
    if (!isset($_SESSION['clientes_demo']) || !is_array($_SESSION['clientes_demo'])) {
        $_SESSION['clientes_demo'] = clientes_seed();
    }
    return array_values($_SESSION['clientes_demo']);
}

function clientes_demo_persistir_todos($todos) {
    $todos = array_values($todos);
    if (function_exists('demo_escribir')) {
        try { demo_escribir('clientes', $todos); } catch (Throwable $t) {}
    }
    $_SESSION['clientes_demo'] = $todos;
}

function clientes_demo_next_id($todos) {
    $max = 0;
    foreach ($todos as $c) { $max = max($max, (int)($c['id_cliente'] ?? 0)); }
    return $max + 1;
}

/** Validaciones compartidas demo/DB. */
function clientes_validar($d) {
    $err = [];
    if (trim($d['nombre'] ?? '') === '') $err['nombre'] = 'El nombre es obligatorio.';
    if (trim($d['apellido'] ?? '') === '') $err['apellido'] = 'El apellido es obligatorio.';
    $email = trim($d['email'] ?? '');
    if ($email === '') $err['email'] = 'El email es obligatorio.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = 'Email inválido.';
    $tel = trim($d['telefono'] ?? '');
    if ($tel === '') {
        $err['telefono'] = 'El teléfono es obligatorio.';
    } else {
        $soloDig = preg_replace('/\D/', '', $tel);
        if (strlen($soloDig) < 6) $err['telefono'] = 'Teléfono inválido (mínimo 6 dígitos).';
        elseif (!preg_match('/^[0-9+\-\s().]+$/', $tel)) $err['telefono'] = 'Teléfono con caracteres inválidos.';
    }
    return $err;
}

// ---------- CAPA clientes_* (bifurca demo/DB, una sola vista) ----------
function clientes_listar($conn, $db_ok, $es_demo, $q = '') {
    $q = trim($q);
    if ($es_demo) {
        $todos = clientes_demo_todos();
        if ($q !== '') {
            $todos = array_values(array_filter($todos, function ($c) use ($q) {
                $nom = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
                return stripos($nom, $q) !== false || stripos((string)($c['email'] ?? ''), $q) !== false;
            }));
        }
        usort($todos, function ($a, $b) {
            return strcmp(($a['apellido'] ?? '') . ($a['nombre'] ?? ''), ($b['apellido'] ?? '') . ($b['nombre'] ?? ''));
        });
        return $todos;
    }
    $out = [];
    if (!$db_ok || !$conn) return $out;
    try {
        if ($q !== '') {
            $like = '%' . $q . '%';
            $st = @$conn->prepare('SELECT id_cliente,nombre,apellido,email,telefono,direccion,ciudad,provincia,dni FROM CLIENTES WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ? ORDER BY apellido,nombre');
            if (!$st) return $out;
            $st->bind_param('sss', $like, $like, $like);
            if (!$st->execute()) { $st->close(); return $out; }
            $res = $st->get_result();
            if ($res) { while ($r = $res->fetch_assoc()) $out[] = $r; $res->free(); }
            $st->close();
        } else {
            $res = @$conn->query('SELECT id_cliente,nombre,apellido,email,telefono,direccion,ciudad,provincia,dni FROM CLIENTES ORDER BY apellido,nombre');
            if ($res) { while ($r = $res->fetch_assoc()) $out[] = $r; $res->free(); }
        }
    } catch (Throwable $t) {}
    return $out;
}

function clientes_obtener($conn, $db_ok, $es_demo, $id) {
    $id = (int)$id;
    if ($id <= 0) return null;
    if ($es_demo) {
        foreach (clientes_demo_todos() as $c) {
            if ((int)($c['id_cliente'] ?? 0) === $id) return $c;
        }
        return null;
    }
    if (!$db_ok || !$conn) return null;
    try {
        $st = @$conn->prepare('SELECT id_cliente,nombre,apellido,email,telefono,direccion,ciudad,provincia,dni FROM CLIENTES WHERE id_cliente=? LIMIT 1');
        if (!$st) return null;
        $st->bind_param('i', $id);
        if (!$st->execute()) { $st->close(); return null; }
        $res = $st->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($res) $res->free();
        $st->close();
        return $row ?: null;
    } catch (Throwable $t) { return null; }
}

function clientes_guardar($conn, $db_ok, $es_demo, $data, $id = null) {
    $errores = clientes_validar($data);
    if ($errores) return ['ok' => false, 'errores' => $errores, 'id' => null];
    $f = [
        'nombre' => trim($data['nombre']), 'apellido' => trim($data['apellido']),
        'email' => trim($data['email']), 'telefono' => trim($data['telefono']),
        'direccion' => trim($data['direccion'] ?? ''), 'ciudad' => trim($data['ciudad'] ?? ''),
        'provincia' => trim($data['provincia'] ?? ''), 'dni' => trim($data['dni'] ?? ''),
    ];
    if ($es_demo) {
        $todos = clientes_demo_todos();
        if ($id === null) {
            $nuevo = array_merge(['id_cliente' => clientes_demo_next_id($todos)], $f);
            if (function_exists('demo_guardar')) {
                // Contrato C-1: demo_guardar('clientes', $item): array (devuelve item con id asignado).
                try { $saved = demo_guardar('clientes', $nuevo); } catch (Throwable $t) { $saved = null; }
                $nid = (int)(is_array($saved) && isset($saved['id_cliente']) ? $saved['id_cliente'] : $nuevo['id_cliente']);
                // Espejo local (solo se usa si demo_datos no existe en futuras lecturas).
                $todos[] = array_merge($nuevo, ['id_cliente' => $nid]);
                $_SESSION['clientes_demo'] = array_values($todos);
                return ['ok' => true, 'errores' => [], 'id' => $nid];
            }
            $todos[] = $nuevo;
            clientes_demo_persistir_todos($todos);
            return ['ok' => true, 'errores' => [], 'id' => (int)$nuevo['id_cliente']];
        }
        $found = false;
        foreach ($todos as &$c) {
            if ((int)($c['id_cliente'] ?? 0) === (int)$id) { $c = array_merge(['id_cliente' => (int)$id], $f); $found = true; break; }
        }
        unset($c);
        if (!$found) return ['ok' => false, 'errores' => ['general' => 'Cliente no encontrado.'], 'id' => null];
        clientes_demo_persistir_todos($todos);
        return ['ok' => true, 'errores' => [], 'id' => (int)$id];
    }
    // DB real (prepared, sin exponer SQL).
    if (!$db_ok || !$conn) return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
    try {
        if ($id === null) {
            $st = @$conn->prepare('INSERT INTO CLIENTES (nombre,apellido,email,telefono,direccion,ciudad,provincia,dni) VALUES (?,?,?,?,?,?,?,?)');
            if (!$st) return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
            $st->bind_param('ssssssss', $f['nombre'], $f['apellido'], $f['email'], $f['telefono'], $f['direccion'], $f['ciudad'], $f['provincia'], $f['dni']);
            $ok = @$st->execute();
            $nid = $ok ? (int)$conn->insert_id : null;
            $st->close();
            if (!$ok) return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
            return ['ok' => true, 'errores' => [], 'id' => $nid];
        }
        $iid = (int)$id;
        $st = @$conn->prepare('UPDATE CLIENTES SET nombre=?,apellido=?,email=?,telefono=?,direccion=?,ciudad=?,provincia=?,dni=? WHERE id_cliente=?');
        if (!$st) return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
        $st->bind_param('ssssssssi', $f['nombre'], $f['apellido'], $f['email'], $f['telefono'], $f['direccion'], $f['ciudad'], $f['provincia'], $f['dni'], $iid);
        $ok = @$st->execute();
        $st->close();
        if (!$ok) return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
        return ['ok' => true, 'errores' => [], 'id' => $iid];
    } catch (Throwable $t) {
        return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar. Intentá nuevamente.'], 'id' => null];
    }
}

function clientes_borrar($conn, $db_ok, $es_demo, $id) {
    $id = (int)$id;
    if ($id <= 0) return false;
    if ($es_demo) {
        $todos = clientes_demo_todos();
        $nuevos = array_values(array_filter($todos, function ($c) use ($id) {
            return (int)($c['id_cliente'] ?? 0) !== $id;
        }));
        if (count($nuevos) === count($todos)) return false;
        clientes_demo_persistir_todos($nuevos);
        return true;
    }
    if (!$db_ok || !$conn) return false;
    try {
        $st = @$conn->prepare('DELETE FROM CLIENTES WHERE id_cliente=?');
        if (!$st) return false;
        $st->bind_param('i', $id);
        $ok = (bool)@$st->execute();
        $aff = $st->affected_rows;
        $st->close();
        return $ok && $aff > 0;
    } catch (Throwable $t) { return false; }
}

function clientes_interacciones($conn, $db_ok, $es_demo, $id_cliente) {
    $id_cliente = (int)$id_cliente;
    $out = [];
    if ($es_demo) {
        $fuente = [];
        if (function_exists('demo_datos')) {
            try { $fuente = demo_datos('interacciones'); } catch (Throwable $t) { $fuente = []; }
        }
        if (!is_array($fuente)) $fuente = [];
        foreach ($fuente as $i) {
            if ((int)($i['id_cliente'] ?? 0) === $id_cliente) $out[] = $i;
        }
        usort($out, function ($a, $b) {
            return strcmp((string)($b['fecha_interaccion'] ?? ''), (string)($a['fecha_interaccion'] ?? ''));
        });
        return array_slice($out, 0, 50);
    }
    if (!$db_ok || !$conn) return $out;
    try {
        $st = @$conn->prepare('SELECT id_interaccion,titulo,descripcion,medio,estado_kanban,prioridad,fecha_interaccion,id_propiedad FROM INTERACCIONES WHERE id_cliente=? ORDER BY fecha_interaccion DESC LIMIT 50');
        if (!$st) return $out;
        $st->bind_param('i', $id_cliente);
        if ($st->execute()) {
            $res = $st->get_result();
            if ($res) { while ($r = $res->fetch_assoc()) $out[] = $r; $res->free(); }
        }
        $st->close();
    } catch (Throwable $t) {}
    return $out;
}

function clientes_prop_interes($conn, $db_ok, $es_demo, $id_cliente) {
    $id_cliente = (int)$id_cliente;
    $out = [];
    $idx = [];
    $agregar = function ($p) use (&$out, &$idx) {
        $pid = (int)($p['id_propiedad'] ?? 0);
        if ($pid <= 0 || isset($idx[$pid])) return;
        $idx[$pid] = true;
        $out[] = $p;
    };
    if ($es_demo) {
        $props = [];
        if (function_exists('demo_datos')) {
            try { $props = demo_datos('propiedades'); } catch (Throwable $t) { $props = []; }
        }
        if (!is_array($props) || !$props) return [];
        $map = [];
        foreach ($props as $p) $map[(int)($p['id_propiedad'] ?? 0)] = $p;
        foreach (clientes_interacciones($conn, $db_ok, true, $id_cliente) as $i) {
            $pid = (int)($i['id_propiedad'] ?? 0);
            if ($pid && isset($map[$pid])) $agregar($map[$pid]);
        }
        return $out;
    }
    if (!$db_ok || !$conn) return $out;
    try {
        // Vía INTERACCIONES.
        $st = @$conn->prepare('SELECT DISTINCT p.id_propiedad,p.titulo,p.tipo,p.precio,p.ciudad,p.provincia FROM PROPIEDADES p INNER JOIN INTERACCIONES i ON i.id_propiedad=p.id_propiedad WHERE i.id_cliente=? ORDER BY p.titulo');
        if ($st) {
            $st->bind_param('i', $id_cliente);
            if ($st->execute()) {
                $res = $st->get_result();
                if ($res) { while ($r = $res->fetch_assoc()) $agregar($r); $res->free(); }
            }
            $st->close();
        }
        // Vía TRANSACCIONES (interés formalizado).
        $st2 = @$conn->prepare('SELECT DISTINCT p.id_propiedad,p.titulo,p.tipo,p.precio,p.ciudad,p.provincia FROM PROPIEDADES p INNER JOIN TRANSACCIONES t ON t.id_propiedad=p.id_propiedad WHERE t.id_cliente=? ORDER BY p.titulo');
        if ($st2) {
            $st2->bind_param('i', $id_cliente);
            if ($st2->execute()) {
                $res = $st2->get_result();
                if ($res) { while ($r = $res->fetch_assoc()) $agregar($r); $res->free(); }
            }
            $st2->close();
        }
    } catch (Throwable $t) {}
    return $out;
}

// ---------- Estado / POST (PRG) ----------
$es_demo = clientes_es_demo($db_ok);
$q = trim($_GET['q'] ?? '');
$ver_id = isset($_GET['ver']) ? (int)$_GET['ver'] : 0;
$editar_id = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$flash_ok = trim($_GET['ok'] ?? '');
$flash_err = trim($_GET['error'] ?? '');
$errores = [];
$form = ['nombre' => '', 'apellido' => '', 'email' => '', 'telefono' => '', 'direccion' => '', 'ciudad' => '', 'provincia' => '', 'dni' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'eliminar') {
        $del = (int)($_POST['id_cliente'] ?? 0);
        $ok = clientes_borrar($conn, $db_ok, $es_demo, $del);
        $dest = 'clientes.php?q=' . urlencode($q) . ($ok ? '&ok=' . urlencode('Cliente eliminado.') : '&error=' . urlencode('No se pudo eliminar.'));
        if ($ver_id === $del && $ok) $dest = 'clientes.php?ok=' . urlencode('Cliente eliminado.');
        header('Location: ' . $dest);
        exit;
    }
    if ($accion === 'guardar') {
        $gid = ($_POST['id_cliente'] ?? '') !== '' ? (int)$_POST['id_cliente'] : null;
        foreach (array_keys($form) as $k) $form[$k] = trim($_POST[$k] ?? '');
        $r = clientes_guardar($conn, $db_ok, $es_demo, $form, $gid);
        if ($r['ok']) {
            header('Location: clientes.php?ver=' . (int)$r['id'] . '&ok=' . urlencode($gid === null ? 'Cliente creado.' : 'Cliente actualizado.'));
            exit;
        }
        $errores = $r['errores'];
        $editar_id = $gid ?? 0;
    }
} elseif ($editar_id > 0) {
    $ex = clientes_obtener($conn, $db_ok, $es_demo, $editar_id);
    if ($ex) {
        foreach (array_keys($form) as $k) $form[$k] = (string)($ex[$k] ?? '');
    } else {
        $flash_err = 'Cliente no encontrado.';
        $editar_id = 0;
    }
}

$clientes = clientes_listar($conn, $db_ok, $es_demo, $q);
$ficha = $ver_id > 0 ? clientes_obtener($conn, $db_ok, $es_demo, $ver_id) : null;
$ficha_inter = $ficha ? clientes_interacciones($conn, $db_ok, $es_demo, $ver_id) : [];
$ficha_props = $ficha ? clientes_prop_interes($conn, $db_ok, $es_demo, $ver_id) : [];
if ($ver_id > 0 && !$ficha && $flash_err === '') $flash_err = 'Cliente no encontrado.';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Clientes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilos/clientes.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <header class="page-header">
        <div>
            <h1>Clientes</h1>
            <p class="subtitle">Buscá, creá y gestioná clientes. Ficha con interacciones y propiedades de interés.</p>
        </div>
        <div>
            <?php if ($es_demo): ?>
                <span class="badge badge-demo">MODO DEMO (sin MySQL)</span>
            <?php else: ?>
                <span class="badge badge-db">Conectado a MySQL</span>
            <?php endif; ?>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
            <?php if ($flash_ok !== ''): ?>
                <div class="alert alert-ok" role="status"><?php echo e($flash_ok); ?></div>
            <?php endif; ?>
            <?php if ($flash_err !== '' || isset($errores['general'])): ?>
                <div class="alert alert-err" role="alert"><?php echo e($flash_err !== '' ? $flash_err : $errores['general']); ?></div>
            <?php endif; ?>

            <div class="toolbar">
                <form class="search-form" method="get" action="clientes.php" role="search">
                    <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por nombre o email…" aria-label="Buscar por nombre o email" />
                    <button class="btn btn-secondary" type="submit">Buscar</button>
                </form>
                <div>
                    <span class="count"><?php echo count($clientes); ?> resultado(s)</span>
                    <a class="btn btn-primary" href="clientes.php?q=<?php echo urlencode($q); ?>&editar=0#form-cliente">+ Nuevo cliente</a>
                </div>
            </div>

            <?php if ($ficha): ?>
            <section class="card ficha" aria-label="Ficha del cliente">
                <h2><?php echo e(trim($ficha['nombre'] . ' ' . $ficha['apellido'])); ?></h2>
                <p class="ficha-mail"><?php echo e($ficha['email']); ?> · <?php echo e($ficha['telefono']); ?></p>
                <dl>
                    <div><dt>Dirección</dt><dd><?php echo e($ficha['direccion'] !== '' ? $ficha['direccion'] : '—'); ?></dd></div>
                    <div><dt>Ciudad / Provincia</dt><dd><?php echo e(trim(($ficha['ciudad'] !== '' ? $ficha['ciudad'] : '—') . ' / ' . ($ficha['provincia'] !== '' ? $ficha['provincia'] : '—'))); ?></dd></div>
                    <div><dt>DNI</dt><dd><?php echo e($ficha['dni'] !== '' ? $ficha['dni'] : '—'); ?></dd></div>
                    <div><dt>ID</dt><dd>#<?php echo (int)$ficha['id_cliente']; ?></dd></div>
                </dl>
                <div class="form-actions">
                    <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>&editar=<?php echo (int)$ficha['id_cliente']; ?>#form-cliente">Editar</a>
                    <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>">Volver al listado</a>
                </div>
                <div class="ficha-cols">
                    <div class="ficha-box">
                        <h3>Interacciones (<?php echo count($ficha_inter); ?>)</h3>
                        <?php if (!$ficha_inter): ?>
                            <p class="empty">Sin interacciones registradas.</p>
                        <?php else: ?>
                        <ul>
                            <?php foreach ($ficha_inter as $it): ?>
                            <li>
                                <strong><?php echo e($it['titulo'] ?? '(sin título)'); ?></strong><br>
                                <span class="muted"><?php echo e($it['medio'] ?? '—'); ?> · <?php echo e($it['estado_kanban'] ?? '—'); ?> · <?php echo e($it['fecha_interaccion'] ?? ''); ?></span>
                                <?php if (!empty($it['descripcion'])): ?><br><?php echo e($it['descripcion']); ?><?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <div class="ficha-box">
                        <h3>Propiedades de interés (<?php echo count($ficha_props); ?>)</h3>
                        <?php if (!$ficha_props): ?>
                            <p class="empty">Sin propiedades vinculadas.</p>
                        <?php else: ?>
                        <ul>
                            <?php foreach ($ficha_props as $p): ?>
                            <li>
                                <strong><?php echo e($p['titulo'] ?? ('Propiedad #' . (int)($p['id_propiedad'] ?? 0))); ?></strong><br>
                                <span class="muted"><?php echo e($p['tipo'] ?? '—'); ?> · <?php echo e($p['ciudad'] ?? ''); ?><?php echo isset($p['precio']) ? ' · $' . number_format((float)$p['precio'], 0, ',', '.') : ''; ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!$clientes): ?>
                <div class="card"><p class="count">Sin resultados<?php echo $q !== '' ? ' para "' . e($q) . '"' : ''; ?>.</p></div>
            <?php else: ?>
            <div class="tabla-wrap" role="region" aria-label="Tabla de clientes" tabindex="0">
                <table>
                    <thead>
                        <tr><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Ciudad</th><th>DNI</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><strong><?php echo e(trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''))); ?></strong></td>
                            <td><?php echo e($c['email'] ?? ''); ?></td>
                            <td><?php echo e($c['telefono'] ?? ''); ?></td>
                            <td class="muted"><?php echo e($c['ciudad'] ?? ''); ?></td>
                            <td class="muted"><?php echo e($c['dni'] ?? ''); ?></td>
                            <td>
                                <span class="row-actions">
                                    <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>&ver=<?php echo (int)$c['id_cliente']; ?>">Ver ficha</a>
                                    <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>&editar=<?php echo (int)$c['id_cliente']; ?>#form-cliente">Editar</a>
                                    <form class="inline-form" method="post" action="clientes.php?q=<?php echo urlencode($q); ?>" onsubmit="return confirm('¿Eliminar a <?php echo e(trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''))); ?>? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="accion" value="eliminar" />
                                        <input type="hidden" name="id_cliente" value="<?php echo (int)$c['id_cliente']; ?>" />
                                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="clientes-cards">
                <?php foreach ($clientes as $c): ?>
                <article class="cliente-card">
                    <h3><?php echo e(trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''))); ?></h3>
                    <p><?php echo e($c['email'] ?? ''); ?></p>
                    <p><?php echo e($c['telefono'] ?? ''); ?> · <?php echo e($c['ciudad'] ?? ''); ?></p>
                    <div class="row-actions">
                        <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>&ver=<?php echo (int)$c['id_cliente']; ?>">Ver ficha</a>
                        <a class="btn btn-secondary btn-sm" href="clientes.php?q=<?php echo urlencode($q); ?>&editar=<?php echo (int)$c['id_cliente']; ?>#form-cliente">Editar</a>
                        <form class="inline-form" method="post" action="clientes.php?q=<?php echo urlencode($q); ?>" onsubmit="return confirm('¿Eliminar a este cliente?');">
                            <input type="hidden" name="accion" value="eliminar" />
                            <input type="hidden" name="id_cliente" value="<?php echo (int)$c['id_cliente']; ?>" />
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <section class="card form-card" id="form-cliente" aria-label="Crear o editar cliente">
                <h2><?php echo $editar_id > 0 ? 'Editar cliente #' . (int)$editar_id : 'Nuevo cliente'; ?></h2>
                <p class="hint">Validaciones: nombre + apellido obligatorios, email válido, teléfono con mínimo 6 dígitos.</p>
                <?php if ($errores): ?>
                    <div class="alert alert-err"><strong>Revisá el formulario:</strong>
                        <ul><?php foreach ($errores as $msg): ?><li><?php echo e($msg); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="clientes.php?q=<?php echo urlencode($q); ?>#form-cliente">
                    <input type="hidden" name="accion" value="guardar" />
                    <?php if ($editar_id > 0): ?><input type="hidden" name="id_cliente" value="<?php echo (int)$editar_id; ?>" /><?php endif; ?>
                    <div class="form-grid">
                        <label class="field">Nombre *
                            <input name="nombre" value="<?php echo e($form['nombre']); ?>" maxlength="50" required />
                            <?php if (isset($errores['nombre'])): ?><span class="err"><?php echo e($errores['nombre']); ?></span><?php endif; ?>
                        </label>
                        <label class="field">Apellido *
                            <input name="apellido" value="<?php echo e($form['apellido']); ?>" maxlength="50" required />
                            <?php if (isset($errores['apellido'])): ?><span class="err"><?php echo e($errores['apellido']); ?></span><?php endif; ?>
                        </label>
                        <label class="field">Email *
                            <input type="email" name="email" value="<?php echo e($form['email']); ?>" maxlength="100" required />
                            <?php if (isset($errores['email'])): ?><span class="err"><?php echo e($errores['email']); ?></span><?php endif; ?>
                        </label>
                        <label class="field">Teléfono *
                            <input name="telefono" value="<?php echo e($form['telefono']); ?>" maxlength="20" required />
                            <?php if (isset($errores['telefono'])): ?><span class="err"><?php echo e($errores['telefono']); ?></span><?php endif; ?>
                        </label>
                        <label class="field field-full">Dirección
                            <input name="direccion" value="<?php echo e($form['direccion']); ?>" maxlength="100" />
                        </label>
                        <label class="field">Ciudad
                            <input name="ciudad" value="<?php echo e($form['ciudad']); ?>" maxlength="50" />
                        </label>
                        <label class="field">Provincia
                            <input name="provincia" value="<?php echo e($form['provincia']); ?>" maxlength="50" />
                        </label>
                        <label class="field">DNI
                            <input name="dni" value="<?php echo e($form['dni']); ?>" maxlength="20" />
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit"><?php echo $editar_id > 0 ? 'Guardar cambios' : 'Crear cliente'; ?></button>
                        <?php if ($editar_id > 0): ?><a class="btn btn-secondary" href="clientes.php?q=<?php echo urlencode($q); ?>#form-cliente">Cancelar edición</a><?php endif; ?>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <footer><p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p></footer>
</body>
</html>
<?php if ($conn instanceof mysqli) { @$conn->close(); } ?>
