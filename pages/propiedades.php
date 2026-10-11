<?php
// AGENTE C-3 — Catálogo de propiedades (modo DEMO sin MySQL + flujo DB real intacto)
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Contrato demo (C-1 crea pages/demo_data.php; se programa contra esto de forma defensiva)
$demoFile = __DIR__ . '/demo_data.php';
if (file_exists($demoFile)) {
    require_once $demoFile;
}
$demo = function_exists('demo_activo') ? (bool) demo_activo() : false;

// Polyfill mbstring (el PHP CLI del equipo puede no traer la extensión; en web suele estar)
if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null) { preg_match_all('/./us', (string) $s, $m); return count($m[0]); }
    function mb_substr($s, $start, $len = null, $enc = null) {
        $ch = preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY);
        return implode('', array_slice($ch, (int) $start, $len === null ? null : (int) $len));
    }
    function mb_strtolower($s, $enc = null) {
        return strtr(strtolower((string) $s), ['Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ú' => 'ú', 'Ü' => 'ü', 'Ñ' => 'ñ', 'À' => 'à', 'È' => 'è', 'Ì' => 'ì', 'Ò' => 'ò', 'Ù' => 'ù']);
    }
    function mb_strtoupper($s, $enc = null) {
        return strtr(strtoupper((string) $s), ['á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ü' => 'Ü', 'ñ' => 'Ñ']);
    }
    function mb_strpos($hay, $ndl, $off = 0, $enc = null) {
        $h = preg_split('//u', (string) $hay, -1, PREG_SPLIT_NO_EMPTY);
        $n = preg_split('//u', (string) $ndl, -1, PREG_SPLIT_NO_EMPTY);
        $hn = count($h); $nn = count($n);
        if ($nn === 0) return (int) $off;
        for ($i = (int) $off; $i <= $hn - $nn; $i++) {
            $ok = true;
            for ($j = 0; $j < $nn; $j++) { if ($h[$i + $j] !== $n[$j]) { $ok = false; break; } }
            if ($ok) return $i;
        }
        return false;
    }
}

/* ================= Helpers ================= */

function h($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function fmt_usd($n) {
    return 'USD ' . number_format((float) $n, 0, ',', '.');
}

function fmt_m2($n) {
    $s = number_format((float) $n, 2, ',', '.');
    $s = preg_replace('/,00$/', '', $s);
    return $s . ' m²';
}

function tipo_canon($t) {
    $t = strtolower(trim((string) $t));
    if ($t === 'galpon') return 'galpón';
    if ($t === 'dpto' || $t === 'depto') return 'departamento';
    return $t;
}

function tipo_label($t) {
    $t = tipo_canon($t);
    $map = [
        'casa' => 'Casa', 'departamento' => 'Departamento',
        'lote' => 'Lote', 'terreno' => 'Lote',
        'oficina' => 'Oficina', 'local' => 'Local', 'galpón' => 'Galpón',
    ];
    return $map[$t] ?? ucfirst($t);
}

function tipo_kind($t) {
    // casa | depto | lote (para placeholder SVG y estilos)
    $t = strtolower(trim((string) $t));
    if (str_contains($t, 'depart') || str_contains($t, 'depto') || str_contains($t, 'dpto') || $t === 'oficina' || $t === 'local') return 'depto';
    if (str_contains($t, 'lote') || str_contains($t, 'terre') || str_contains($t, 'galp')) return 'lote';
    return 'casa';
}

function estado_canon($e) {
    $e = strtolower(trim((string) $e));
    $map = ['reservado' => 'reservada', 'vendido' => 'vendida', 'alquilado' => 'alquilada'];
    return $map[$e] ?? $e;
}

function estado_label($e) {
    $e = estado_canon($e);
    $map = ['disponible' => 'Disponible', 'reservada' => 'Reservada', 'vendida' => 'Vendida', 'alquilada' => 'Alquilada'];
    return $map[$e] ?? ucfirst($e);
}

function estado_class($e) {
    $e = estado_canon($e);
    $map = ['disponible' => 'ok', 'reservada' => 'warn', 'vendida' => 'sold', 'alquilada' => 'rent'];
    return $map[$e] ?? 'muted';
}

// Placeholder SVG inline propio por tipo (nunca imagen rota, nunca emojis)
function placeholder_svg($tipo, $titulo) {
    static $uid = 0;
    $uid++;
    $kind = tipo_kind($tipo);
    $letra = strtoupper(mb_substr(trim((string) $titulo) !== '' ? (string) $titulo : $tipo, 0, 1, 'UTF-8'));
    if ($letra === '') $letra = '·';
    $cfg = [
        'casa'  => ['c1' => '#14532d', 'c2' => '#22c55e', 'label' => 'Casa'],
        'depto' => ['c1' => '#1e3a8a', 'c2' => '#38bdf8', 'label' => 'Depto'],
        'lote'  => ['c1' => '#78350f', 'c2' => '#fbbf24', 'label' => 'Lote'],
    ][$kind];
    $icon = '';
    if ($kind === 'casa') {
        $icon = '<path d="M200 70 L320 160 L300 160 L300 260 L100 260 L100 160 L80 160 Z" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="10" stroke-linejoin="round"/><rect x="182" y="200" width="36" height="60" fill="rgba(255,255,255,.55)"/>';
    } elseif ($kind === 'depto') {
        $icon = '<rect x="140" y="70" width="120" height="190" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="10"/><g fill="rgba(255,255,255,.55)"><rect x="158" y="92" width="24" height="24"/><rect x="218" y="92" width="24" height="24"/><rect x="158" y="130" width="24" height="24"/><rect x="218" y="130" width="24" height="24"/><rect x="158" y="168" width="24" height="24"/><rect x="218" y="168" width="24" height="24"/></g><rect x="182" y="216" width="36" height="44" fill="rgba(255,255,255,.55)"/>';
    } else {
        $icon = '<path d="M60 220 L200 120 L340 220 Z" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="10" stroke-linejoin="round"/><line x1="60" y1="240" x2="340" y2="240" stroke="rgba(255,255,255,.55)" stroke-width="10" stroke-linecap="round"/><line x1="200" y1="120" x2="200" y2="80" stroke="rgba(255,255,255,.55)" stroke-width="8"/>';
    }
    return '<svg class="ph" viewBox="0 0 400 300" role="img" aria-label="Imagen no disponible (' . h($cfg['label']) . ')" preserveAspectRatio="xMidYMid slice">'
        . '<defs><linearGradient id="phg' . $uid . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $cfg['c1'] . '"/><stop offset="1" stop-color="#0f172a"/></linearGradient></defs>'
        . '<rect width="400" height="300" fill="url(#phg' . $uid . ')"/>'
        . '<circle cx="200" cy="150" r="86" fill="' . $cfg['c2'] . '" opacity="0.22"/>'
        . '<g transform="translate(0,8) scale(1)">' . $icon . '</g>'
        . '<text x="200" y="282" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="44" font-weight="700" fill="rgba(255,255,255,.9)">' . h($letra) . '</text>'
        . '</svg>';
}

// Fotos reales existentes (solo lectura): image/Inmueble/
function fotos_pool() {
    return [
        '../image/Inmueble/inmueble.jpeg',
        '../image/Inmueble/inmueble1.jpeg',
        '../image/Inmueble/inmueble2.jpeg',
        '../image/Inmueble/inmueble3.jpg',
        '../image/Inmueble/inmueble5.jpg',
        '../image/Inmueble/inmueble6.jpg',
        '../image/Inmueble/inmueble7.jpg',
        '../image/Inmueble/inmueble8.jpg',
        '../image/Inmueble/inmueble9.jpg',
    ];
}

// Galería declarada en el item demo (tolera varias formas: fotos|imagenes|galeria|foto|imagen|url)
function galeria_declarada($p) {
    $out = [];
    foreach (['fotos', 'imagenes', 'galeria', 'gallery', 'images'] as $k) {
        if (!empty($p[$k]) && is_array($p[$k])) {
            foreach ($p[$k] as $f) {
                if (is_string($f) && trim($f) !== '') $out[] = trim($f);
                elseif (is_array($f)) {
                    foreach (['src', 'url', 'foto', 'imagen', 'path'] as $kk) {
                        if (!empty($f[$kk]) && is_string($f[$kk])) { $out[] = trim($f[$kk]); break; }
                    }
                }
            }
        }
    }
    foreach (['foto', 'imagen', 'url', 'image', 'src'] as $k) {
        if (!empty($p[$k]) && is_string($p[$k])) $out[] = trim($p[$k]);
    }
    return array_values(array_unique(array_filter($out)));
}

/* ================= Normalización al modelo PROPIEDADES (.sql) =================
   PROPIEDADES(id_propiedad, id_agente, titulo, descripcion, tipo, direccion,
   ciudad, provincia, precio, superficie, ambientes, banos, dormitorios,
   antiguedad, estado) + JOIN AGENTES(nombre, apellido, email, telefono) */

function agentes_map($lista) {
    $map = [];
    if (!is_array($lista)) return $map;
    foreach ($lista as $a) {
        if (!is_array($a)) continue;
        $id = $a['id_agente'] ?? $a['id'] ?? null;
        if ($id === null) continue;
        $map[(int) $id] = [
            'nombre'   => $a['nombre'] ?? $a['name'] ?? '',
            'apellido' => $a['apellido'] ?? $a['lastname'] ?? '',
            'email'    => $a['email'] ?? $a['mail'] ?? '',
            'telefono' => $a['telefono'] ?? $a['phone'] ?? '',
        ];
    }
    return $map;
}

function prop_norm($p, $agentesMap = []) {
    if (!is_array($p)) return null;
    $id = (int) ($p['id_propiedad'] ?? $p['id'] ?? 0);
    $idAg = $p['id_agente'] ?? $p['agente_id'] ?? null;
    $idAg = ($idAg === '' || $idAg === null) ? null : (int) $idAg;
    $out = [
        'id_propiedad' => $id,
        'id_agente'    => $idAg,
        'titulo'       => $p['titulo'] ?? $p['nombre'] ?? $p['title'] ?? 'Sin título',
        'descripcion'  => $p['descripcion'] ?? $p['desc'] ?? $p['description'] ?? '',
        'tipo'         => tipo_canon($p['tipo'] ?? $p['type'] ?? 'casa'),
        'direccion'    => $p['direccion'] ?? $p['ubicacion'] ?? $p['address'] ?? '',
        'ciudad'       => $p['ciudad'] ?? $p['city'] ?? '',
        'provincia'    => $p['provincia'] ?? $p['state'] ?? '',
        'precio'       => (float) ($p['precio'] ?? $p['price'] ?? 0),
        'superficie'   => (float) ($p['superficie'] ?? $p['m2'] ?? $p['sup'] ?? $p['area'] ?? 0),
        'ambientes'    => (int) ($p['ambientes'] ?? $p['rooms'] ?? 0),
        'banos'        => (int) ($p['banos'] ?? $p['baños'] ?? $p['banios'] ?? $p['bathrooms'] ?? 0),
        'dormitorios'  => (int) ($p['dormitorios'] ?? $p['dorms'] ?? $p['habitaciones'] ?? $p['bedrooms'] ?? 0),
        'antiguedad'   => (int) ($p['antiguedad'] ?? $p['age'] ?? 0),
        'estado'       => estado_canon($p['estado'] ?? $p['status'] ?? 'disponible'),
    ];
    // Agente: datos embebidos o por mapa
    $nom = $p['agente_nombre'] ?? $p['agent_name'] ?? '';
    $ape = $p['agente_apellido'] ?? $p['agent_lastname'] ?? '';
    if ($nom === '' && !empty($p['agente']) && is_string($p['agente'])) {
        $partes = preg_split('/\s+/', trim($p['agente']), 2);
        $nom = $partes[0] ?? ''; $ape = $partes[1] ?? '';
    }
    if ($nom === '' && $idAg !== null && isset($agentesMap[$idAg])) {
        $nom = $agentesMap[$idAg]['nombre']; $ape = $agentesMap[$idAg]['apellido'];
    }
    $out['agente_nombre']   = $nom;
    $out['agente_apellido'] = $ape;
    $out['agente_email']    = $p['agente_email'] ?? (($idAg !== null && isset($agentesMap[$idAg])) ? $agentesMap[$idAg]['email'] : '');
    $out['agente_telefono'] = $p['agente_telefono'] ?? (($idAg !== null && isset($agentesMap[$idAg])) ? $agentesMap[$idAg]['telefono'] : '');
    $out['agente_full']     = trim($nom . ' ' . $ape);
    // Galería: declarada o determinística del pool por id (para que el modal tenga galería)
    $gal = galeria_declarada($p);
    if (empty($gal)) {
        $pool = fotos_pool();
        $n = count($pool);
        $base = $id > 0 ? $id : abs(crc32((string) $out['titulo']));
        $k = 1 + ($base % 3); // 1 a 3 fotos por propiedad
        for ($i = 0; $i < $k; $i++) $gal[] = $pool[($base + $i) % $n];
    }
    $out['_galeria'] = array_values($gal);
    $out['_foto'] = $gal[0] ?? null;
    return $out;
}

/* ================= Persistencia DEMO (sesión) ================= */

function demo_lista($clave) {
    if (function_exists('demo_datos')) {
        try {
            $d = demo_datos($clave);
            if (is_array($d)) return array_values($d);
        } catch (Throwable $e) { error_log('demo_datos: ' . $e->getMessage()); }
    }
    return [];
}

function demo_next_id($items) {
    $max = 0;
    foreach ($items as $p) {
        if (is_array($p)) $max = max($max, (int) ($p['id_propiedad'] ?? $p['id'] ?? 0));
    }
    return $max + 1;
}

// Crea en demo con demo_guardar() (agrega y devuelve el item con id). Fallback a sesión.
function demo_crear_item($item) {
    if (function_exists('demo_guardar')) {
        try {
            $saved = demo_guardar('propiedades', $item);
            if (is_array($saved) && isset($saved['id_propiedad'])) return $saved;
        } catch (Throwable $e) { error_log('demo_guardar: ' . $e->getMessage()); }
    }
    if (!isset($item['id_propiedad']) || (int) $item['id_propiedad'] <= 0) {
        $item['id_propiedad'] = demo_next_id(demo_lista('propiedades'));
    }
    demo_sesion_upsert($item);
    return $item;
}

// Actualiza en demo con demo_escribir() (reemplazo total). Fallback a sesión.
function demo_actualizar_item($item) {
    $id = (int) ($item['id_propiedad'] ?? 0);
    if ($id <= 0) return false;
    $todos = demo_lista('propiedades');
    $found = false;
    foreach ($todos as $i => $p) {
        if (is_array($p) && (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) === $id) { $todos[$i] = $item; $found = true; break; }
    }
    if (!$found) return false;
    if (function_exists('demo_escribir')) {
        try { demo_escribir('propiedades', array_values($todos)); return true; }
        catch (Throwable $e) { error_log('demo_escribir: ' . $e->getMessage()); }
    }
    demo_sesion_upsert($item);
    return true;
}

// Upsert directo en $_SESSION (tolera $_SESSION['demo']['propiedades'] de C-1 y otras claves)
function demo_sesion_upsert($item) {
    $id = (int) ($item['id_propiedad'] ?? 0);
    if (!isset($_SESSION['demo']) || !is_array($_SESSION['demo'])) $_SESSION['demo'] = [];
    if (!isset($_SESSION['demo']['propiedades']) || !is_array($_SESSION['demo']['propiedades'])) $_SESSION['demo']['propiedades'] = [];
    $found = false;
    foreach ($_SESSION['demo']['propiedades'] as $i => $p) {
        if (is_array($p) && (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) === $id) { $_SESSION['demo']['propiedades'][$i] = $item; $found = true; break; }
    }
    if (!$found) $_SESSION['demo']['propiedades'][] = $item;
    foreach (['demo_propiedades', 'propiedades'] as $k) {
        if (isset($_SESSION[$k]) && is_array($_SESSION[$k])) {
            $f2 = false;
            foreach ($_SESSION[$k] as $i => $p) {
                if (is_array($p) && (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) === $id) { $_SESSION[$k][$i] = $item; $f2 = true; break; }
            }
            if (!$f2) $_SESSION[$k][] = $item;
        }
    }
}

function demo_sesion_delete($id) {
    $id = (int) $id;
    if (isset($_SESSION['demo']['propiedades']) && is_array($_SESSION['demo']['propiedades'])) {
        $nuevo = [];
        foreach ($_SESSION['demo']['propiedades'] as $p) {
            if (!is_array($p) || (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) !== $id) $nuevo[] = $p;
        }
        $_SESSION['demo']['propiedades'] = array_values($nuevo);
    }
    foreach ($_SESSION as $k => $v) {
        if ($k === 'demo' || !is_array($v) || !isset($v[0]) || !is_array($v[0])) continue;
        if (!isset($v[0]['titulo']) && !isset($v[0]['title'])) continue;
        $nuevo = [];
        foreach ($v as $p) {
            if (!is_array($p) || (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) !== $id) $nuevo[] = $p;
        }
        $_SESSION[$k] = array_values($nuevo);
    }
}

function demo_eliminar_item($id) {
    $id = (int) $id;
    $todos = demo_lista('propiedades');
    $nuevo = [];
    $found = false;
    foreach ($todos as $p) {
        if (is_array($p) && (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) === $id) { $found = true; continue; }
        $nuevo[] = $p;
    }
    if (!$found) return false;
    if (function_exists('demo_escribir')) {
        try { demo_escribir('propiedades', array_values($nuevo)); }
        catch (Throwable $e) { error_log('demo_escribir: ' . $e->getMessage()); }
        foreach (demo_lista('propiedades') as $p) {
            if (is_array($p) && (int) ($p['id_propiedad'] ?? $p['id'] ?? 0) === $id) { demo_sesion_delete($id); break; }
        }
        return true;
    }
    foreach (['demo_eliminar', 'demo_borrar', 'demo_delete'] as $fn) {
        if (function_exists($fn)) {
            try { $fn('propiedades', $id); } catch (Throwable $e) {
                try { $fn($id); } catch (Throwable $e2) { error_log($fn . ': ' . $e2->getMessage()); }
            }
        }
    }
    demo_sesion_delete($id);
    return true;
}

/* ================= POST: crear / editar / eliminar ================= */

$TIPOS_OK = ['casa', 'departamento', 'lote', 'terreno', 'oficina', 'local', 'galpón', 'galpon'];
$ESTADOS_OK = ['disponible', 'reservada', 'vendida', 'alquilada'];
$errores = [];
$form_vals = [];
$flash = null;
if (!empty($_SESSION['prop_flash'])) { $flash = $_SESSION['prop_flash']; unset($_SESSION['prop_flash']); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $accion = trim((string) ($_POST['accion'] ?? ''));
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['prop_flash'] = ['tipo' => 'error', 'msg' => 'No se pudo eliminar: propiedad no válida.'];
        } else {
            $ok = false;
            if ($demo) {
                $ok = demo_eliminar_item($id);
            } else {
                try {
                    if (!extension_loaded('mysqli')) throw new RuntimeException('db');
                    if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF);
                    $c = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                    if ($c->connect_errno) throw new RuntimeException('conn');
                    if (defined('DB_CHARSET')) $c->set_charset(DB_CHARSET);
                    $st = $c->prepare('DELETE FROM PROPIEDADES WHERE id_propiedad = ?');
                    if ($st) { $st->bind_param('i', $id); $ok = (bool) $st->execute(); $st->close(); }
                    $c->close();
                } catch (Throwable $e) { error_log('propiedades eliminar: ' . $e->getMessage()); $ok = false; }
            }
            $_SESSION['prop_flash'] = $ok
                ? ['tipo' => 'ok', 'msg' => 'Propiedad eliminada correctamente.']
                : ['tipo' => 'error', 'msg' => 'No se pudo eliminar la propiedad. Intentá de nuevo.'];
        }
        header('Location: propiedades.php');
        exit;
    }

    if ($accion === 'crear' || $accion === 'actualizar') {
        $id = (int) ($_POST['id'] ?? 0);
        $form_vals = [
            'id' => $id,
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'tipo' => tipo_canon($_POST['tipo'] ?? ''),
            'precio' => trim((string) ($_POST['precio'] ?? '')),
            'superficie' => trim((string) ($_POST['superficie'] ?? '')),
            'dormitorios' => trim((string) ($_POST['dormitorios'] ?? '0')),
            'banos' => trim((string) ($_POST['banos'] ?? '0')),
            'ambientes' => trim((string) ($_POST['ambientes'] ?? '0')),
            'antiguedad' => trim((string) ($_POST['antiguedad'] ?? '0')),
            'estado' => estado_canon($_POST['estado'] ?? 'disponible'),
            'direccion' => trim((string) ($_POST['direccion'] ?? '')),
            'ciudad' => trim((string) ($_POST['ciudad'] ?? '')),
            'provincia' => trim((string) ($_POST['provincia'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'id_agente' => trim((string) ($_POST['id_agente'] ?? '')),
        ];
        // Validaciones (precio>0 y m2>0 obligatorias)
        if (mb_strlen($form_vals['titulo']) < 3) $errores[] = 'El título es obligatorio (mínimo 3 caracteres).';
        if (!in_array($form_vals['tipo'], $TIPOS_OK, true)) $errores[] = 'Elegí un tipo de propiedad válido.';
        if (!is_numeric($form_vals['precio']) || (float) $form_vals['precio'] <= 0) $errores[] = 'El precio debe ser un número mayor a 0 (USD).';
        if (!is_numeric($form_vals['superficie']) || (float) $form_vals['superficie'] <= 0) $errores[] = 'La superficie debe ser un número mayor a 0 (m²).';
        foreach (['dormitorios' => 'Dormitorios', 'banos' => 'Baños', 'ambientes' => 'Ambientes', 'antiguedad' => 'Antigüedad'] as $k => $lbl) {
            if ($form_vals[$k] !== '' && (!ctype_digit($form_vals[$k]) || (int) $form_vals[$k] < 0)) $errores[] = $lbl . ' debe ser un número entero mayor o igual a 0.';
        }
        if (!in_array($form_vals['estado'], $ESTADOS_OK, true)) $errores[] = 'Elegí un estado válido.';
        if (mb_strlen($form_vals['titulo']) > 100) $errores[] = 'El título no puede superar los 100 caracteres.';
        $idAg = ($form_vals['id_agente'] === '' || $form_vals['id_agente'] === null) ? null : (int) $form_vals['id_agente'];

        if (empty($errores)) {
            $item = [
                'id_propiedad' => $id > 0 ? $id : 0,
                'id_agente' => $idAg,
                'titulo' => mb_substr($form_vals['titulo'], 0, 100),
                'descripcion' => $form_vals['descripcion'],
                'tipo' => $form_vals['tipo'],
                'direccion' => mb_substr($form_vals['direccion'], 0, 100),
                'ciudad' => mb_substr($form_vals['ciudad'], 0, 50),
                'provincia' => mb_substr($form_vals['provincia'], 0, 50),
                'precio' => (float) $form_vals['precio'],
                'superficie' => (float) $form_vals['superficie'],
                'ambientes' => (int) $form_vals['ambientes'],
                'banos' => (int) $form_vals['banos'],
                'dormitorios' => (int) $form_vals['dormitorios'],
                'antiguedad' => (int) $form_vals['antiguedad'],
                'estado' => $form_vals['estado'],
            ];
            $ok = false;
            if ($demo) {
                if ($accion === 'actualizar' && $id > 0) {
                    $ok = demo_actualizar_item($item);
                } else {
                    $item['id_propiedad'] = 0; // demo_guardar() asigna id correlativo
                    $ok = demo_crear_item($item) !== null;
                }
            } else {
                try {
                    if (!extension_loaded('mysqli')) throw new RuntimeException('db');
                    if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF);
                    $c = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                    if ($c->connect_errno) throw new RuntimeException('conn');
                    if (defined('DB_CHARSET')) $c->set_charset(DB_CHARSET);
                    if ($accion === 'actualizar' && $id > 0) {
                        $st = $c->prepare('UPDATE PROPIEDADES SET id_agente=?, titulo=?, descripcion=?, tipo=?, direccion=?, ciudad=?, provincia=?, precio=?, superficie=?, ambientes=?, banos=?, dormitorios=?, antiguedad=?, estado=? WHERE id_propiedad=?');
                        if ($st) {
                            $st->bind_param('issssssddiiissi', $item['id_agente'], $item['titulo'], $item['descripcion'], $item['tipo'], $item['direccion'], $item['ciudad'], $item['provincia'], $item['precio'], $item['superficie'], $item['ambientes'], $item['banos'], $item['dormitorios'], $item['antiguedad'], $item['estado'], $id);
                            $ok = (bool) $st->execute(); $st->close();
                        }
                    } else {
                        $st = $c->prepare('INSERT INTO PROPIEDADES (id_agente, titulo, descripcion, tipo, direccion, ciudad, provincia, precio, superficie, ambientes, banos, dormitorios, antiguedad, estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                        if ($st) {
                            $st->bind_param('issssssddiiiss', $item['id_agente'], $item['titulo'], $item['descripcion'], $item['tipo'], $item['direccion'], $item['ciudad'], $item['provincia'], $item['precio'], $item['superficie'], $item['ambientes'], $item['banos'], $item['dormitorios'], $item['antiguedad'], $item['estado']);
                            $ok = (bool) $st->execute(); $st->close();
                        }
                    }
                    $c->close();
                } catch (Throwable $e) { error_log('propiedades guardar: ' . $e->getMessage()); $ok = false; }
            }
            if ($ok) {
                $_SESSION['prop_flash'] = ['tipo' => 'ok', 'msg' => $accion === 'actualizar' ? 'Propiedad actualizada correctamente.' : 'Propiedad creada correctamente.'];
                header('Location: propiedades.php');
                exit;
            }
            $errores[] = 'No se pudo guardar la propiedad. Revisá los datos e intentá de nuevo.';
        }
    }
}

/* ================= Carga de datos ================= */

$props_raw = [];
$agentes_raw = [];
$db_ok = !$demo;
$db_warn = false;

if ($demo) {
    $props_raw = demo_lista('propiedades');
    $agentes_raw = demo_lista('agentes');
} else {
    try {
        if (!extension_loaded('mysqli')) throw new RuntimeException('mysqli');
        if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_errno) throw new RuntimeException('conn');
        if (defined('DB_CHARSET')) $conn->set_charset(DB_CHARSET);
        $sql = "SELECT p.id_propiedad, p.id_agente, p.titulo, p.descripcion, p.tipo, p.direccion, p.ciudad, p.provincia, p.precio, p.superficie, p.ambientes, p.banos, p.dormitorios, p.antiguedad, p.estado,
                       a.nombre AS agente_nombre, a.apellido AS agente_apellido, a.email AS agente_email, a.telefono AS agente_telefono
                FROM PROPIEDADES p LEFT JOIN AGENTES a ON p.id_agente = a.id_agente
                ORDER BY p.id_propiedad DESC";
        if ($res = $conn->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                // Galería DB: primero BLOB (instalaciones con columna imagen), si no, columna url
                $gal = [];
                try {
                    $st = $conn->prepare('SELECT imagen FROM IMAGENES WHERE id_propiedad = ? ORDER BY principal DESC, id_imagen');
                    if ($st) {
                        $pid = (int) $row['id_propiedad'];
                        $st->bind_param('i', $pid);
                        $st->execute();
                        $ri = $st->get_result();
                        while ($im = $ri->fetch_assoc()) {
                            if (isset($im['imagen']) && $im['imagen'] !== null) {
                                $mime = 'image/jpeg';
                                if (function_exists('finfo_open')) {
                                    $fi = finfo_open(FILEINFO_MIME_TYPE);
                                    if ($fi) { $m = finfo_buffer($fi, $im['imagen']); if ($m) $mime = $m; finfo_close($fi); }
                                }
                                $gal[] = 'data:' . $mime . ';base64,' . base64_encode($im['imagen']);
                            }
                        }
                        $st->close();
                    }
                } catch (Throwable $e) { error_log('galeria blob: ' . $e->getMessage()); }
                if (empty($gal)) {
                    try {
                        $st2 = $conn->prepare('SELECT url FROM IMAGENES WHERE id_propiedad = ? ORDER BY principal DESC, id_imagen');
                        if ($st2) {
                            $pid = (int) $row['id_propiedad'];
                            $st2->bind_param('i', $pid);
                            $st2->execute();
                            $ru = $st2->get_result();
                            while ($u = $ru->fetch_assoc()) {
                                if (!empty($u['url'])) {
                                    $url = trim((string) $u['url']);
                                    if (str_starts_with($url, '/image/')) $url = '..' . $url;
                                    $gal[] = $url;
                                }
                            }
                            $st2->close();
                        }
                    } catch (Throwable $e) { error_log('galeria url: ' . $e->getMessage()); }
                }
                if (!empty($gal)) $row['fotos'] = $gal;
                $props_raw[] = $row;
            }
            $res->free();
        }
        try {
            if ($res2 = $conn->query('SELECT id_agente, nombre, apellido, email, telefono FROM AGENTES ORDER BY nombre, apellido')) {
                while ($a = $res2->fetch_assoc()) $agentes_raw[] = $a;
                $res2->free();
            }
        } catch (Throwable $e) { error_log('agentes: ' . $e->getMessage()); }
        $conn->close();
    } catch (Throwable $e) {
        error_log('propiedades db: ' . $e->getMessage());
        $db_ok = false; $db_warn = true; $props_raw = []; $agentes_raw = [];
    }
}

$agentesMap = agentes_map($agentes_raw);
$props = [];
foreach ($props_raw as $p) {
    $n = prop_norm($p, $agentesMap);
    if ($n) $props[] = $n;
}

/* ================= Filtros + orden (GET) ================= */

$f_q = trim((string) ($_GET['q'] ?? ''));
$f_tipo = tipo_canon($_GET['tipo'] ?? '');
$f_estado = estado_canon($_GET['estado'] ?? '');
$f_min = trim((string) ($_GET['pmin'] ?? ''));
$f_max = trim((string) ($_GET['pmax'] ?? ''));
$f_orden = trim((string) ($_GET['orden'] ?? 'recientes'));
$hay_filtros = ($f_q !== '' || $f_tipo !== '' || $f_estado !== '' || $f_min !== '' || $f_max !== '');

// Facetas para los selects
$tipos_facet = [];
$estados_facet = [];
foreach ($props as $p) {
    $tipos_facet[tipo_canon($p['tipo'])] = tipo_label($p['tipo']);
    $estados_facet[estado_canon($p['estado'])] = estado_label($p['estado']);
}
ksort($tipos_facet); ksort($estados_facet);

$filtradas = array_filter($props, function ($p) use ($f_q, $f_tipo, $f_estado, $f_min, $f_max) {
    if ($f_tipo !== '' && tipo_canon($p['tipo']) !== $f_tipo) return false;
    if ($f_estado !== '' && estado_canon($p['estado']) !== $f_estado) return false;
    if ($f_min !== '' && is_numeric($f_min) && (float) $p['precio'] < (float) $f_min) return false;
    if ($f_max !== '' && is_numeric($f_max) && (float) $p['precio'] > (float) $f_max) return false;
    if ($f_q !== '') {
        $bol = mb_strtolower($p['titulo'] . ' ' . $p['direccion'] . ' ' . $p['ciudad'] . ' ' . $p['provincia'] . ' ' . $p['descripcion'] . ' ' . $p['tipo'], 'UTF-8');
        foreach (preg_split('/\s+/', mb_strtolower($f_q, 'UTF-8')) as $tok) {
            if ($tok !== '' && mb_strpos($bol, $tok) === false) return false;
        }
    }
    return true;
});
$filtradas = array_values($filtradas);

switch ($f_orden) {
    case 'precio_asc':  usort($filtradas, fn($a, $b) => $a['precio'] <=> $b['precio']); break;
    case 'precio_desc': usort($filtradas, fn($a, $b) => $b['precio'] <=> $a['precio']); break;
    case 'm2_desc':     usort($filtradas, fn($a, $b) => $b['superficie'] <=> $a['superficie']); break;
    case 'dorms_desc':  usort($filtradas, fn($a, $b) => $b['dormitorios'] <=> $a['dormitorios']); break;
    default:            usort($filtradas, fn($a, $b) => $b['id_propiedad'] <=> $a['id_propiedad']);
}

// Prefill de edición (?editar=id)
$editando = null;
if (isset($_GET['editar'])) {
    $eid = (int) $_GET['editar'];
    foreach ($props as $p) {
        if ((int) $p['id_propiedad'] === $eid) { $editando = $p; break; }
    }
}
if (!empty($form_vals)) {
    // Si falló la validación, el form muestra lo enviado
    $editando = [
        'id_propiedad' => (int) ($form_vals['id'] ?? 0),
        'titulo' => $form_vals['titulo'], 'tipo' => $form_vals['tipo'],
        'precio' => $form_vals['precio'], 'superficie' => $form_vals['superficie'],
        'dormitorios' => $form_vals['dormitorios'], 'banos' => $form_vals['banos'],
        'ambientes' => $form_vals['ambientes'], 'antiguedad' => $form_vals['antiguedad'],
        'estado' => $form_vals['estado'], 'direccion' => $form_vals['direccion'],
        'ciudad' => $form_vals['ciudad'], 'provincia' => $form_vals['provincia'],
        'descripcion' => $form_vals['descripcion'], 'id_agente' => $form_vals['id_agente'] === '' ? null : (int) $form_vals['id_agente'],
    ];
}
$es_edicion = ($editando !== null && (int) ($editando['id_propiedad'] ?? 0) > 0 && empty($form_vals)) || (!empty($form_vals) && (int) ($form_vals['id'] ?? 0) > 0);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Propiedades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilos/propiedades.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <header>
        <h1>CRM Inmobiliaria - Propiedades</h1>
    </header>
    <main class="main-content">
        <div class="container">
            <div class="page-head">
                <div>
                    <h1>Catálogo de Propiedades</h1>
                    <p class="subtitle">Fotos, precio en USD, superficie, dormitorios y agente asignado.</p>
                </div>
                <div class="head-badges">
                    <?php if ($demo): ?><span class="demo-badge" title="Sin MySQL: los cambios se guardan en la sesión local">Modo demo</span><?php endif; ?>
                    <a class="btn" href="#panel-form">+ Nueva propiedad</a>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="flash <?= $flash['tipo'] === 'ok' ? 'flash-ok' : 'flash-error' ?>" role="status"><?= h($flash['msg']) ?></div>
            <?php endif; ?>
            <?php if (!empty($errores)): ?>
                <div class="flash flash-error" role="alert">
                    <strong>No se pudo guardar.</strong>
                    <ul><?php foreach ($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            <?php if ($db_warn): ?>
                <div class="flash flash-error" role="alert">No se pudieron cargar las propiedades. Intentá de nuevo en unos minutos.</div>
            <?php elseif ($demo && empty($props) && !$hay_filtros): ?>
                <div class="flash flash-info" role="status">Modo demo sin datos: falta <code>pages/demo_data.php</code> (lo provee C-1) o la sesión está vacía.</div>
            <?php endif; ?>

            <details class="filtros" id="filtrosPanel" open>
                <summary><span class="filtros-title">Filtros y orden</span><span class="filtros-count"><?= count($filtradas) ?> de <?= count($props) ?></span></summary>
                <form method="get" action="propiedades.php" class="filtros-grid">
                    <label class="field">Búsqueda
                        <input type="search" name="q" value="<?= h($f_q) ?>" placeholder="Título, dirección, ciudad…" autocomplete="off">
                    </label>
                    <label class="field">Tipo
                        <select name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_facet as $val => $lbl): ?>
                                <option value="<?= h($val) ?>" <?= $f_tipo === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Estado
                        <select name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados_facet as $val => $lbl): ?>
                                <option value="<?= h($val) ?>" <?= $f_estado === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Precio mín (USD)
                        <input type="number" name="pmin" min="0" step="1000" value="<?= h($f_min) ?>" placeholder="0">
                    </label>
                    <label class="field">Precio máx (USD)
                        <input type="number" name="pmax" min="0" step="1000" value="<?= h($f_max) ?>" placeholder="Sin tope">
                    </label>
                    <label class="field">Orden
                        <select name="orden">
                            <option value="recientes" <?= $f_orden === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                            <option value="precio_asc" <?= $f_orden === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                            <option value="precio_desc" <?= $f_orden === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                            <option value="m2_desc" <?= $f_orden === 'm2_desc' ? 'selected' : '' ?>>Mayor superficie</option>
                            <option value="dorms_desc" <?= $f_orden === 'dorms_desc' ? 'selected' : '' ?>>Más dormitorios</option>
                        </select>
                    </label>
                    <div class="filtros-actions">
                        <button type="submit" class="btn">Aplicar</button>
                        <?php if ($hay_filtros || $f_orden !== 'recientes'): ?><a class="btn-ghost" href="propiedades.php">Limpiar</a><?php endif; ?>
                    </div>
                </form>
            </details>

            <?php if (empty($filtradas)): ?>
                <div class="empty" role="status">
                    <?= placeholder_svg('', 'Búsqueda') ?>
                    <h2><?= $hay_filtros ? 'Sin resultados con esos filtros' : 'Todavía no hay propiedades' ?></h2>
                    <p><?= $hay_filtros ? 'Probá ampliar el rango de precios o limpiar la búsqueda.' : 'Creá la primera propiedad con el botón “Nueva propiedad”.' ?></p>
                    <?php if ($hay_filtros): ?><a class="btn" href="propiedades.php">Ver todas</a>
                    <?php else: ?><a class="btn" href="#panel-form">+ Nueva propiedad</a><?php endif; ?>
                </div>
            <?php else: ?>
            <div class="grid">
                <?php foreach ($filtradas as $prop):
                    $pid = (int) $prop['id_propiedad'];
                    $gal = $prop['_galeria'];
                    $foto = $prop['_foto'];
                    $loc = trim(tipo_label($prop['tipo']) . ' · ' . trim($prop['ciudad'] . ', ' . $prop['provincia'], ', '), ' ·');
                ?>
                <article class="card" aria-labelledby="t-<?= $pid ?>">
                    <div class="card-media">
                        <?= placeholder_svg($prop['tipo'], $prop['titulo']) ?>
                        <?php if ($foto): ?><img src="<?= h($foto) ?>" alt="Foto de <?= h($prop['titulo']) ?>" loading="lazy" onerror="this.remove()"><?php endif; ?>
                        <span class="badge badge-<?= estado_class($prop['estado']) ?>"><?= h(estado_label($prop['estado'])) ?></span>
                        <span class="chip-tipo"><?= h(tipo_label($prop['tipo'])) ?></span>
                    </div>
                    <div class="card-body">
                        <h2 id="t-<?= $pid ?>"><?= h($prop['titulo']) ?></h2>
                        <p class="location"><?= h($loc !== '' ? $loc : tipo_label($prop['tipo'])) ?></p>
                        <p class="price"><?= fmt_usd($prop['precio']) ?></p>
                        <ul class="specs" aria-label="Características">
                            <li><strong><?= h(fmt_m2($prop['superficie'])) ?></strong><span>superficie</span></li>
                            <li><strong><?= (int) $prop['dormitorios'] ?> dorm.</strong><span>dormitorios</span></li>
                            <li><strong><?= (int) $prop['banos'] ?> baños</strong><span>baños</span></li>
                        </ul>
                        <p class="agente">Agente: <?= $prop['agente_full'] !== '' ? h($prop['agente_full']) : '<span class="muted">Sin asignar</span>' ?></p>
                        <div class="card-actions">
                            <button type="button" class="open-modal" data-target="modal-<?= $pid ?>" data-prop="<?= $pid ?>">Ver detalles</button>
                            <a class="btn-ghost btn-small" href="propiedades.php?editar=<?= $pid ?>#panel-form">Editar</a>
                            <form method="post" action="propiedades.php" class="inline-form" onsubmit="return confirm('¿Eliminar «<?= h(str_replace("'", "\\'", $prop['titulo'])) ?>»? Esta acción no se puede deshacer.');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $pid ?>">
                                <button type="submit" class="btn-danger btn-small">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </article>

                <div id="modal-<?= $pid ?>" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="m-t-<?= $pid ?>">
                    <div class="modal-content">
                        <button type="button" class="modal-close" data-target="modal-<?= $pid ?>" aria-label="Cerrar ventana">×</button>
                        <h2 id="m-t-<?= $pid ?>"><?= h($prop['titulo']) ?></h2>
                        <p class="modal-sub"><em><?= h($loc) ?></em> <span class="badge badge-<?= estado_class($prop['estado']) ?>"><?= h(estado_label($prop['estado'])) ?></span></p>
                        <div class="modal-body">
                            <div class="image-gallery modal-gallery" data-prop="<?= $pid ?>" data-images="<?= h(json_encode(array_values($gal), JSON_UNESCAPED_SLASHES)) ?>">
                                <div class="gal-main card-media">
                                    <?= placeholder_svg($prop['tipo'], $prop['titulo']) ?>
                                    <?php if ($foto): ?><img id="img-<?= $pid ?>" src="<?= h($foto) ?>" alt="Foto de <?= h($prop['titulo']) ?>" onerror="this.remove()"><?php endif; ?>
                                </div>
                                <?php if (count($gal) > 1): ?>
                                    <button type="button" class="prev" data-gprev="<?= $pid ?>" aria-label="Foto anterior">‹</button>
                                    <button type="button" class="next" data-gnext="<?= $pid ?>" aria-label="Foto siguiente">›</button>
                                    <div class="thumbs" role="list">
                                        <?php foreach ($gal as $i => $src): ?>
                                            <button type="button" role="listitem" class="thumb<?= $i === 0 ? ' active' : '' ?>" data-goto="<?= $pid ?>:<?= $i ?>" aria-label="Ver foto <?= $i + 1 ?>">
                                                <img src="<?= h($src) ?>" alt="" loading="lazy" onerror="this.closest('.thumb').style.display='none'">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-section">
                                <h3>Detalles</h3>
                                <ul class="kv">
                                    <li><strong>Precio:</strong> <?= fmt_usd($prop['precio']) ?></li>
                                    <li><strong>Superficie:</strong> <?= h(fmt_m2($prop['superficie'])) ?></li>
                                    <li><strong>Ambientes:</strong> <?= (int) $prop['ambientes'] ?></li>
                                    <li><strong>Baños:</strong> <?= (int) $prop['banos'] ?></li>
                                    <li><strong>Dormitorios:</strong> <?= (int) $prop['dormitorios'] ?></li>
                                    <li><strong>Antigüedad:</strong> <?= (int) $prop['antiguedad'] ?> años</li>
                                    <li><strong>Estado:</strong> <?= h(estado_label($prop['estado'])) ?></li>
                                    <?php if (trim($prop['direccion']) !== ''): ?><li><strong>Dirección:</strong> <?= h($prop['direccion']) ?></li><?php endif; ?>
                                </ul>
                                <?php if (trim($prop['descripcion']) !== ''): ?><p><?= nl2br(h($prop['descripcion'])) ?></p><?php endif; ?>
                            </div>
                            <div class="modal-section">
                                <h3>Agente asignado</h3>
                                <?php if ($prop['agente_full'] !== ''): ?>
                                    <p><strong><?= h($prop['agente_full']) ?></strong></p>
                                    <?php if ($prop['agente_email'] !== ''): ?><p>Correo: <?= h($prop['agente_email']) ?></p><?php endif; ?>
                                    <?php if ($prop['agente_telefono'] !== ''): ?><p>Teléfono: <?= h($prop['agente_telefono']) ?></p><?php endif; ?>
                                <?php else: ?><p class="muted">Sin agente asignado.</p><?php endif; ?>
                            </div>
                            <div class="modal-section modal-crud">
                                <a class="btn-ghost btn-small" href="propiedades.php?editar=<?= $pid ?>#panel-form">Editar esta propiedad</a>
                                <form method="post" action="propiedades.php" class="inline-form" onsubmit="return confirm('¿Eliminar «<?= h(str_replace("'", "\\'", $prop['titulo'])) ?>»? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $pid ?>">
                                    <button type="submit" class="btn-danger btn-small">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <section class="panel-form" id="panel-form" aria-labelledby="form-title">
                <h2 id="form-title"><?= $es_edicion ? 'Editar propiedad #' . (int) ($editando['id_propiedad'] ?? $form_vals['id'] ?? 0) : 'Nueva propiedad' ?></h2>
                <p class="muted"><?= $demo ? 'Modo demo: se guarda en tu sesión local.' : 'Se guarda en la base de datos.' ?> Precio y superficie deben ser mayores a 0.</p>
                <form method="post" action="propiedades.php#panel-form" novalidate>
                    <input type="hidden" name="accion" value="<?= $es_edicion ? 'actualizar' : 'crear' ?>">
                    <?php if ($es_edicion): ?><input type="hidden" name="id" value="<?= (int) ($editando['id_propiedad'] ?? $form_vals['id'] ?? 0) ?>"><?php endif; ?>
                    <div class="form-grid">
                        <label class="field span2">Título *
                            <input type="text" name="titulo" required maxlength="100" value="<?= h($editando['titulo'] ?? '') ?>" placeholder="Ej: Casa con pileta en Rosario">
                        </label>
                        <label class="field">Tipo *
                            <select name="tipo" required>
                                <?php foreach (['casa' => 'Casa', 'departamento' => 'Departamento', 'lote' => 'Lote', 'terreno' => 'Terreno', 'oficina' => 'Oficina', 'local' => 'Local', 'galpón' => 'Galpón'] as $v => $l): ?>
                                    <option value="<?= h($v) ?>" <?= (tipo_canon($editando['tipo'] ?? '') === $v || ($v === 'galpón' && tipo_canon($editando['tipo'] ?? '') === 'galpon')) ? 'selected' : '' ?>><?= h($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">Estado *
                            <select name="estado" required>
                                <?php foreach (['disponible' => 'Disponible', 'reservada' => 'Reservada', 'vendida' => 'Vendida', 'alquilada' => 'Alquilada'] as $v => $l): ?>
                                    <option value="<?= h($v) ?>" <?= (estado_canon($editando['estado'] ?? 'disponible') === $v) ? 'selected' : '' ?>><?= h($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">Precio USD *
                            <input type="number" name="precio" required min="1" step="0.01" value="<?= h($editando['precio'] ?? '') ?>" placeholder="250000">
                        </label>
                        <label class="field">Superficie m² *
                            <input type="number" name="superficie" required min="1" step="0.01" value="<?= h($editando['superficie'] ?? '') ?>" placeholder="150">
                        </label>
                        <label class="field">Dormitorios
                            <input type="number" name="dormitorios" min="0" step="1" value="<?= h($editando['dormitorios'] ?? '0') ?>">
                        </label>
                        <label class="field">Baños
                            <input type="number" name="banos" min="0" step="1" value="<?= h($editando['banos'] ?? '0') ?>">
                        </label>
                        <label class="field">Ambientes
                            <input type="number" name="ambientes" min="0" step="1" value="<?= h($editando['ambientes'] ?? '0') ?>">
                        </label>
                        <label class="field">Antigüedad (años)
                            <input type="number" name="antiguedad" min="0" step="1" value="<?= h($editando['antiguedad'] ?? '0') ?>">
                        </label>
                        <label class="field">Dirección
                            <input type="text" name="direccion" maxlength="100" value="<?= h($editando['direccion'] ?? '') ?>" placeholder="Calle Luna 45">
                        </label>
                        <label class="field">Ciudad
                            <input type="text" name="ciudad" maxlength="50" value="<?= h($editando['ciudad'] ?? '') ?>" placeholder="Rosario">
                        </label>
                        <label class="field">Provincia
                            <input type="text" name="provincia" maxlength="50" value="<?= h($editando['provincia'] ?? '') ?>" placeholder="Santa Fe">
                        </label>
                        <label class="field">Agente
                            <select name="id_agente">
                                <option value="">Sin asignar</option>
                                <?php foreach ($agentesMap as $aid => $a): ?>
                                    <option value="<?= (int) $aid ?>" <?= ((int) ($editando['id_agente'] ?? -1) === (int) $aid) ? 'selected' : '' ?>><?= h(trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field span2">Descripción
                            <textarea name="descripcion" rows="3" placeholder="Jardín, pileta, orientación…"><?= h($editando['descripcion'] ?? '') ?></textarea>
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn"><?= $es_edicion ? 'Guardar cambios' : 'Crear propiedad' ?></button>
                        <?php if ($es_edicion): ?><a class="btn-ghost" href="propiedades.php#panel-form">Cancelar edición</a><?php endif; ?>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
    </footer>
    <script>
        var imageIndexes = {};
        function galleryImages(propId) {
            var g = document.querySelector('.image-gallery[data-prop="' + propId + '"]');
            if (!g) return [];
            try { return JSON.parse(g.getAttribute('data-images') || '[]'); } catch (e) { return []; }
        }
        function paintGallery(propId) {
            var imgs = galleryImages(propId);
            if (!imgs.length) return;
            var i = (typeof imageIndexes[propId] === 'undefined') ? 0 : imageIndexes[propId];
            i = (i + imgs.length) % imgs.length;
            imageIndexes[propId] = i;
            var modal = document.getElementById('modal-' + propId);
            var main = modal ? modal.querySelector('.gal-main') : null;
            if (main) {
                var old = main.querySelector('img');
                if (old) old.remove();
                var ni = document.createElement('img');
                ni.src = imgs[i];
                ni.alt = 'Foto ' + (i + 1) + ' de la propiedad';
                ni.onerror = function () { this.remove(); };
                main.appendChild(ni);
            }
            if (modal) {
                modal.querySelectorAll('.thumb').forEach(function (t, k) {
                    t.classList.toggle('active', k === i);
                });
            }
        }
        function changeImage(propId, direction) {
            var imgs = galleryImages(propId);
            if (!imgs.length) return;
            if (typeof imageIndexes[propId] === 'undefined') imageIndexes[propId] = 0;
            imageIndexes[propId] = (imageIndexes[propId] + direction + imgs.length) % imgs.length;
            paintGallery(propId);
        }
        function openModal(modalId, propId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            imageIndexes[propId] = 0;
            paintGallery(propId);
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }
        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active')) document.body.classList.remove('modal-open');
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Filtros colapsables en móvil, abiertos en escritorio
            var f = document.getElementById('filtrosPanel');
            if (f && window.matchMedia('(max-width: 719px)').matches && !window.location.search) f.removeAttribute('open');
            document.querySelectorAll('.open-modal').forEach(function (b) {
                b.addEventListener('click', function () { openModal(this.dataset.target, this.dataset.prop); });
            });
            document.querySelectorAll('.modal-close').forEach(function (b) {
                b.addEventListener('click', function () { closeModal(this.dataset.target); });
            });
            document.querySelectorAll('.modal-overlay').forEach(function (ov) {
                ov.addEventListener('click', function (e) { if (e.target === ov) closeModal(ov.id); });
            });
            document.querySelectorAll('[data-gprev]').forEach(function (b) {
                b.addEventListener('click', function () { changeImage(b.getAttribute('data-gprev'), -1); });
            });
            document.querySelectorAll('[data-gnext]').forEach(function (b) {
                b.addEventListener('click', function () { changeImage(b.getAttribute('data-gnext'), 1); });
            });
            document.querySelectorAll('[data-goto]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var parts = b.getAttribute('data-goto').split(':');
                    imageIndexes[parts[0]] = parseInt(parts[1], 10) || 0;
                    paintGallery(parts[0]);
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    var m = document.querySelector('.modal-overlay.active');
                    if (m) closeModal(m.id);
                }
            });
        });
    </script>
</body>
</html>
