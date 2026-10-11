<?php
// AGENTE C-4 — Timeline de interacciones (pages/interacciones.php)
// MODO DEMO sin MySQL vía contrato C-1 (pages/demo_data.php). En real, DB intacta.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// 1) Contrato demo C-1 si existe. Si aún no existe (C-1 en paralelo), fallback local compatible.
$demo_vendor = __DIR__ . '/demo_data.php';
$usa_vendor = file_exists($demo_vendor);
if ($usa_vendor) {
    require_once $demo_vendor;
}

if (!function_exists('demo_activo')) {
    function demo_activo(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        if (!extension_loaded('mysqli')) {
            return $cache = true;
        }
        try {
            $c = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
            if ($c->connect_errno) {
                return $cache = true;
            }
            $c->close();
            return $cache = false;
        } catch (Throwable $e) {
            return $cache = false === false ? true : true;
        }
    }
}

if (!function_exists('demo_semilla_c4')) {
    function demo_semilla_c4(string $tabla): array
    {
        if ($tabla === 'clientes') {
            $n = [
                ['Juan', 'Pérez', 'juanp@mail.com'], ['María', 'Gómez', 'mariag@mail.com'],
                ['Pedro', 'Santos', 'pedros@mail.com'], ['Ana', 'López', 'anal@mail.com'],
                ['Sofía', 'Vega', 'sofiav@mail.com'], ['Diego', 'Molina', 'diegom@mail.com'],
                ['Lucía', 'Torres', 'luciatorres@mail.com'], ['Martín', 'Rojas', 'martinr@mail.com'],
                ['Laura', 'Fernández', 'lauraf@mail.com'], ['Carlos', 'Ramírez', 'carlosr@mail.com'],
            ];
            $out = [];
            foreach ($n as $i => $c) {
                $out[] = ['id_cliente' => $i + 1, 'nombre' => $c[0], 'apellido' => $c[1], 'email' => $c[2]];
            }
            return $out;
        }
        if ($tabla === 'propiedades') {
            $t = ['Casa con pileta', 'Departamento céntrico', 'Terreno zona sur', 'Oficina moderna', 'Casa de lujo', 'Departamento económico', 'Galpón industrial', 'Local comercial', 'Casa antigua reciclada', 'Departamento con balcón'];
            $out = [];
            foreach ($t as $i => $titulo) {
                $out[] = ['id_propiedad' => $i + 1, 'titulo' => $titulo];
            }
            return $out;
        }
        if ($tabla === 'agentes') {
            $n = [['Carlos', 'Ramírez'], ['Laura', 'Fernández'], ['Lucía', 'Torres'], ['Martín', 'Rojas']];
            $out = [];
            foreach ($n as $i => $a) {
                $out[] = ['id_agente' => $i + 1, 'nombre' => $a[0], 'apellido' => $a[1]];
            }
            return $out;
        }
        if ($tabla === 'interacciones') {
            // Columnas exactas de la tabla INTERACCIONES del .sql
            $rows = [
                [1, 1, 1, 1, 'Consulta por Loft Centro', 'Cliente solicita información del loft ubicado en el centro', 'nuevo', 'alta', 'WhatsApp', 'Enviar dossier comercial', null, '2025-10-01 10:00:00', 1],
                [2, 2, 2, 2, 'Tour virtual solicitado', 'El cliente pidió tour virtual del departamento céntrico', 'nuevo', 'media', 'Email', 'Confirmar disponibilidad con propietario', '2025-10-23', '2025-10-01 11:30:00', 2],
                [3, 3, 3, 3, 'Contacto feria inmobiliaria', 'Lead generado en evento presencial, pendiente de primera llamada', 'nuevo', 'media', 'Teléfono', 'Agendar llamada de presentación', '2025-10-22', '2025-10-02 08:45:00', 3],
                [4, 4, 4, 4, 'Visita a Casa Río', 'Seguimiento posterior a la visita presencial realizada por el cliente', 'seguimiento', 'media', 'Visita', 'Enviar condiciones de reserva', '2025-10-22', '2025-10-02 14:00:00', 1],
                [5, 5, 1, 5, 'Seguimiento financiación', 'El cliente necesita simulación bancaria para compra de casa de lujo', 'seguimiento', 'alta', 'Teléfono', 'Compartir simulación hipotecaria', '2025-10-24', '2025-10-03 11:00:00', 2],
                [6, 6, 2, 6, 'Contrato en revisión', 'Se envió borrador de contrato de alquiler para su revisión', 'seguimiento', 'baja', 'Email', 'Recibir feedback del cliente', '2025-10-24', '2025-10-03 12:00:00', 3],
                [7, 7, 3, 7, 'Oferta por galpón industrial', 'Negociación activa con propuesta inicial del interesado', 'negociacion', 'media', 'WhatsApp', 'Enviar contraoferta ajustada', '2025-10-25', '2025-10-04 09:30:00', 1],
                [8, 8, 4, 8, 'Confirma visita Local Comercio', 'Cliente prepara propuesta para alquiler del local comercial', 'negociacion', 'alta', 'Teléfono', 'Preparar propuesta final', '2025-10-21', '2025-10-04 10:30:00', 2],
                [9, 9, 1, 9, 'Propuesta casa reciclada', 'Comprador analiza condiciones de escrituración', 'negociacion', 'media', 'Visita', 'Coordinar escribanía', '2025-10-26', '2025-10-05 11:00:00', 3],
                [10, 10, 2, 10, 'Alquiler departamento balcón', 'Operación cerrada y contrato firmado, seguimiento postventa', 'cerrado', 'baja', 'WhatsApp', 'Solicitar documentación final', '2025-10-20', '2025-10-05 12:00:00', 1],
                [11, 1, 1, 1, 'Listado actualizado loft', 'Se envió listado actualizado al lead original', 'cerrado', 'media', 'Email', 'Realizar encuesta de satisfacción', '2025-10-27', '2025-10-06 10:00:00', 2],
                [12, 2, 2, 2, 'Seguimiento post visita centro', 'Cliente evaluando alternativas tras visita', 'cerrado', 'baja', 'Teléfono', 'Archivar interacción en CRM', '2025-10-19', '2025-10-06 11:00:00', 3],
            ];
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id_interaccion' => $r[0], 'id_cliente' => $r[1], 'id_agente' => $r[2],
                    'id_propiedad' => $r[3], 'titulo' => $r[4], 'descripcion' => $r[5],
                    'estado_kanban' => $r[6], 'prioridad' => $r[7], 'medio' => $r[8],
                    'proxima_accion' => $r[9], 'fecha_proxima_accion' => $r[10],
                    'fecha_interaccion' => $r[11], 'posicion' => $r[12],
                    'created_at' => $r[11], 'updated_at' => $r[11],
                ];
            }
            return $out;
        }
        return [];
    }
}

if (!function_exists('demo_datos')) {
    function demo_datos(string $tabla): array
    {
        if (!isset($_SESSION['c4_demo']) || !is_array($_SESSION['c4_demo'])) {
            $_SESSION['c4_demo'] = [];
        }
        if (!isset($_SESSION['c4_demo'][$tabla])) {
            $_SESSION['c4_demo'][$tabla] = demo_semilla_c4($tabla);
        }
        return $_SESSION['c4_demo'][$tabla];
    }
}

if (!function_exists('demo_guardar')) {
    function demo_guardar(string $tabla, array $item): void
    {
        if (!isset($_SESSION['c4_demo']) || !is_array($_SESSION['c4_demo'])) {
            $_SESSION['c4_demo'] = [];
        }
        if (!isset($_SESSION['c4_demo'][$tabla])) {
            $_SESSION['c4_demo'][$tabla] = demo_semilla_c4($tabla);
        }
        $_SESSION['c4_demo'][$tabla][] = $item;
    }
}

// Helpers C-4 (prefijo para no chocar con otros agentes; sin mbstring para PHP mínimo)
if (!function_exists('c4_normalizar_tipo')) {
function c4_normalizar_tipo(?string $medio): string
{
    $m = strtolower(trim((string)$medio));
    // Normalizar acentos sin mbstring
    $m = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $m);
    if (in_array($m, ['telefono', 'llamada', 'llamadas', 'call', 'llamar'], true)) {
        return 'llamada';
    }
    if (in_array($m, ['email', 'e-mail', 'mail', 'correo', 'correo electronico'], true)) {
        return 'email';
    }
    if (in_array($m, ['visita', 'visitas', 'presencial', 'casa', 'tour'], true)) {
        return 'visita';
    }
    if (in_array($m, ['whatsapp', 'wpp', 'chat', 'mensaje', 'mensajes'], true)) {
        return 'whatsapp';
    }
    return 'otro';
}
}

if (!function_exists('c4_etiqueta_tipo')) {
function c4_etiqueta_tipo(string $canon): string
{
    return match ($canon) {
        'llamada' => 'Llamada',
        'email' => 'Email',
        'visita' => 'Visita',
        'whatsapp' => 'WhatsApp',
        default => 'Otro',
    };
}
}

if (!function_exists('c4_medio_canonico')) {
function c4_medio_canonico(string $canon): string
{
    // Valor a persistir en la columna `medio` (compatible con seed: Teléfono/Email/Visita/WhatsApp)
    return match ($canon) {
        'llamada' => 'Teléfono',
        'email' => 'Email',
        'visita' => 'Visita',
        'whatsapp' => 'WhatsApp',
        default => 'Teléfono',
    };
}
}

if (!function_exists('c4_svg_icono')) {
function c4_svg_icono(string $canon): string
{
    // Estilo coherente con sidebar: 20x20, viewBox 24, fill none, stroke currentColor, width 2
    $base = 'width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
    return match ($canon) {
        'llamada' => '<svg ' . $base . '><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'email' => '<svg ' . $base . '><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>',
        'visita' => '<svg ' . $base . '><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>',
        'whatsapp' => '<svg ' . $base . '><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        default => '<svg ' . $base . '><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };
}
}

if (!function_exists('c4_esc')) {
function c4_esc($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
}

if (!function_exists('c4_fecha_corta')) {
function c4_fecha_corta(?string $dt): string
{
    if (!$dt) {
        return 'Sin fecha';
    }
    try {
        $d = new DateTime((string)$dt);
        return $d->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return 'Sin fecha';
    }
}
}

$es_demo = function_exists('demo_activo') ? (bool)demo_activo() : true;

$clientes = [];
$propiedades = [];
$agentes = [];
$interacciones_all = [];
$aviso_demo = '';
$aviso_error = '';
$aviso_ok = '';

// ---------------- Carga base ----------------
if ($es_demo) {
    try {
        $clientes = demo_datos('clientes');
        $propiedades = demo_datos('propiedades');
        $agentes = demo_datos('agentes');
        $interacciones_all = demo_datos('interacciones');
    } catch (Throwable $e) {
        $clientes = $clientes ?: [];
        $propiedades = $propiedades ?: [];
        $agentes = $agentes ?: [];
        $interacciones_all = [];
        $aviso_error = 'No se pudieron cargar los datos de demostración. Intentalo de nuevo.';
    }
    // Fusión defensiva: creados en esta sesión cuando el vendor C-1 sí persiste aparte.
    if (!empty($_SESSION['c4_interacciones_nuevas']) && is_array($_SESSION['c4_interacciones_nuevas'])) {
        $ids = array_column($interacciones_all, 'id_interaccion');
        foreach ($_SESSION['c4_interacciones_nuevas'] as $nueva) {
            if (!in_array($nueva['id_interaccion'] ?? null, $ids, false)) {
                $interacciones_all[] = $nueva;
            }
        }
    }
    // Ocultar eliminados en demo (el contrato solo expone demo_guardar, no borrado).
    $eliminadas = $_SESSION['c4_interacciones_eliminadas'] ?? [];
    if (!empty($eliminadas)) {
        $interacciones_all = array_values(array_filter(
            $interacciones_all,
            fn($r) => !in_array($r['id_interaccion'] ?? null, $eliminadas, false)
        ));
    }
    $aviso_demo = 'Modo demo: sin conexión a MySQL. Los cambios se guardan solo en tu sesión.';
} else {
    try {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        if ($conn->connect_errno) {
            throw new RuntimeException('db');
        }
        if (defined('DB_CHARSET')) {
            @$conn->set_charset(DB_CHARSET);
        }
        $q = @$conn->query("SELECT id_cliente, nombre, apellido FROM CLIENTES ORDER BY nombre");
        if ($q) {
            while ($row = $q->fetch_assoc()) {
                $clientes[] = $row;
            }
            $q->free();
        }
        $q = @$conn->query("SELECT id_propiedad, titulo FROM PROPIEDADES ORDER BY titulo");
        if ($q) {
            while ($row = $q->fetch_assoc()) {
                $propiedades[] = $row;
            }
            $q->free();
        }
        $q = @$conn->query("SELECT id_agente, nombre, apellido FROM AGENTES ORDER BY nombre");
        if ($q) {
            while ($row = $q->fetch_assoc()) {
                $agentes[] = $row;
            }
            $q->free();
        }
        $conn->close();
    } catch (Throwable $e) {
        // Sin errores SQL visibles: degradar a demo en memoria solo para lectura base.
        $es_demo = true;
        $aviso_demo = 'Modo demo: sin conexión a MySQL. Los cambios se guardan solo en tu sesión.';
        try {
            $clientes = demo_datos('clientes');
            $propiedades = demo_datos('propiedades');
            $agentes = demo_datos('agentes');
            $interacciones_all = demo_datos('interacciones');
        } catch (Throwable $e2) {
            $aviso_error = 'No se pudieron cargar los datos. Intentalo de nuevo.';
        }
    }
}

// Mapas id => nombre para timeline
$map_clientes = [];
foreach ($clientes as $c) {
    $id = $c['id_cliente'] ?? null;
    if ($id !== null) {
        $map_clientes[$id] = trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''));
    }
}
$map_propiedades = [];
foreach ($propiedades as $p) {
    $id = $p['id_propiedad'] ?? null;
    if ($id !== null) {
        $map_propiedades[$id] = $p['titulo'] ?? ('Propiedad #' . $id);
    }
}
$map_agentes = [];
foreach ($agentes as $a) {
    $id = $a['id_agente'] ?? null;
    if ($id !== null) {
        $map_agentes[$id] = trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''));
    }
}

// ---------------- Acciones POST (crear / eliminar) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id_del = (int)($_POST['id_interaccion'] ?? 0);
        if ($id_del <= 0) {
            $aviso_error = 'No se pudo eliminar: interacción inválida.';
        } elseif ($es_demo) {
            // Demo: CRUD en $_SESSION. Borrado vía demo_escribir (C-1) si existe;
            // además se registra el id para que el borrado sobreviva a cualquier store.
            if (!isset($_SESSION['c4_interacciones_eliminadas'])) {
                $_SESSION['c4_interacciones_eliminadas'] = [];
            }
            if (!in_array($id_del, $_SESSION['c4_interacciones_eliminadas'], false)) {
                $_SESSION['c4_interacciones_eliminadas'][] = $id_del;
            }
            try {
                $todos = demo_datos('interacciones');
                $restantes = array_values(array_filter(
                    $todos,
                    fn($r) => (int)($r['id_interaccion'] ?? 0) !== $id_del
                ));
                if (function_exists('demo_escribir')) {
                    demo_escribir('interacciones', $restantes);
                } else {
                    $_SESSION['c4_demo']['interacciones'] = $restantes;
                    $_SESSION['demo']['interacciones'] = $restantes;
                }
            } catch (Throwable $e) {
                // El filtro por eliminadas ya oculta el registro en esta sesión.
            }
            if (!empty($_SESSION['c4_interacciones_nuevas'])) {
                $_SESSION['c4_interacciones_nuevas'] = array_values(array_filter(
                    $_SESSION['c4_interacciones_nuevas'],
                    fn($r) => (int)($r['id_interaccion'] ?? 0) !== $id_del
                ));
            }
            $interacciones_all = array_values(array_filter(
                $interacciones_all,
                fn($r) => (int)($r['id_interaccion'] ?? 0) !== $id_del
            ));
            $aviso_ok = 'Interacción eliminada (demo).';
        } else {
            // Real: DB intacta, DELETE preparado, sin exponer SQL.
            try {
                $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
                if ($conn->connect_errno) {
                    throw new RuntimeException('db');
                }
                $stmt = $conn->prepare("DELETE FROM INTERACCIONES WHERE id_interaccion = ?");
                if (!$stmt) {
                    throw new RuntimeException('db');
                }
                $stmt->bind_param("i", $id_del);
                if ($stmt->execute()) {
                    $aviso_ok = 'Interacción eliminada.';
                    $interacciones_all = array_values(array_filter(
                        $interacciones_all,
                        fn($r) => (int)($r['id_interaccion'] ?? 0) !== $id_del
                    ));
                } else {
                    $aviso_error = 'No se pudo eliminar la interacción. Intentalo de nuevo.';
                }
                $stmt->close();
                $conn->close();
            } catch (Throwable $e) {
                $aviso_error = 'No se pudo eliminar la interacción. Intentalo de nuevo.';
            }
        }
    } else {
        // Crear: validada (cliente + tipo + fecha requeridos)
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $tipo_raw = trim((string)($_POST['tipo'] ?? ($_POST['medio'] ?? '')));
        $fecha_raw = trim((string)($_POST['fecha_interaccion'] ?? ''));
        $id_propiedad = $_POST['id_propiedad'] !== '' && isset($_POST['id_propiedad']) ? (int)$_POST['id_propiedad'] : null;
        if (isset($_POST['id_propiedad']) && (string)$_POST['id_propiedad'] === '') {
            $id_propiedad = null;
        }
        $id_agente = isset($_POST['id_agente']) && (string)$_POST['id_agente'] !== '' ? (int)$_POST['id_agente'] : null;
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $errores = [];

        if ($id_cliente <= 0) {
            $errores[] = 'Elegí un cliente.';
        } elseif (!isset($map_clientes[$id_cliente])) {
            $errores[] = 'El cliente elegido no existe.';
        }
        $canon = c4_normalizar_tipo($tipo_raw);
        if ($tipo_raw === '' || !in_array($canon, ['llamada', 'email', 'visita', 'whatsapp'], true)) {
            $errores[] = 'Elegí un tipo válido: llamada, email, visita o WhatsApp.';
        }
        $fecha_dt = null;
        if ($fecha_raw === '') {
            $errores[] = 'Indicá la fecha de la interacción.';
        } else {
            try {
                $fecha_dt = new DateTime(str_replace('T', ' ', $fecha_raw));
                $fecha_raw = $fecha_dt->format('Y-m-d H:i:s');
            } catch (Throwable $e) {
                $errores[] = 'La fecha indicada no es válida.';
            }
        }
        if ($id_propiedad !== null && $id_propiedad <= 0) {
            $id_propiedad = null;
        }
        if ($id_propiedad !== null && !isset($map_propiedades[$id_propiedad])) {
            $errores[] = 'La propiedad elegida no existe.';
        }

        if (!empty($errores)) {
            $aviso_error = implode(' ', $errores);
        } else {
            $medio_db = c4_medio_canonico($canon);
            if ($titulo === '') {
                $titulo = c4_etiqueta_tipo($canon) . ' con ' . ($map_clientes[$id_cliente] ?? ('Cliente #' . $id_cliente));
            }
            if (strlen($titulo) > 120) {
                $titulo = substr($titulo, 0, 120);
            }

            if ($es_demo) {
                $max = 0;
                foreach ($interacciones_all as $r) {
                    $max = max($max, (int)($r['id_interaccion'] ?? 0));
                }
                $ahora = date('Y-m-d H:i:s');
                $item = [
                    'id_interaccion' => $max + 1,
                    'id_cliente' => $id_cliente,
                    'id_agente' => $id_agente,
                    'id_propiedad' => $id_propiedad,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'estado_kanban' => 'nuevo',
                    'prioridad' => 'media',
                    'medio' => $medio_db,
                    'proxima_accion' => null,
                    'fecha_proxima_accion' => null,
                    'fecha_interaccion' => $fecha_raw,
                    'posicion' => 0,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
                try {
                    demo_guardar('interacciones', $item); // Contrato C-1
                } catch (Throwable $e) {
                    $aviso_error = 'No se pudo guardar la interacción demo. Intentalo de nuevo.';
                    $item = null;
                }
                if (($item ?? null) !== null) {
                    // Respaldo local para que el alta sobreviva aunque el vendor use otro store.
                    if (!isset($_SESSION['c4_interacciones_nuevas'])) {
                        $_SESSION['c4_interacciones_nuevas'] = [];
                    }
                    // Evitar duplicado si demo_datos ya lo refleja en la próxima carga.
                    $_SESSION['c4_interacciones_nuevas'][] = $item;
                    $interacciones_all[] = $item;
                    $aviso_ok = 'Interacción creada (demo).';
                }
            } else {
                try {
                    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
                    if ($conn->connect_errno) {
                        throw new RuntimeException('db');
                    }
                    if (defined('DB_CHARSET')) {
                        @$conn->set_charset(DB_CHARSET);
                    }
                    $estado = 'nuevo';
                    $prio = 'media';
                    $pos = 0;
                    $desc = $descripcion !== '' ? $descripcion : null;
                    $stmt = $conn->prepare("INSERT INTO INTERACCIONES (id_cliente, id_agente, id_propiedad, titulo, descripcion, estado_kanban, prioridad, medio, fecha_interaccion, posicion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new RuntimeException('db');
                    }
                    $stmt->bind_param("iiissssssi", $id_cliente, $id_agente, $id_propiedad, $titulo, $desc, $estado, $prio, $medio_db, $fecha_raw, $pos);
                    if ($stmt->execute()) {
                        $aviso_ok = 'Interacción creada.';
                    } else {
                        $aviso_error = 'No se pudo crear la interacción. Revisá los datos e intentalo de nuevo.';
                    }
                    $stmt->close();
                    $conn->close();
                } catch (Throwable $e) {
                    $aviso_error = 'No se pudo crear la interacción. Revisá los datos e intentalo de nuevo.';
                }
            }
        }
    }
}

// ---------------- Carga de interacciones en modo real (tras POST) ----------------
if (!$es_demo) {
    try {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        if ($conn->connect_errno) {
            throw new RuntimeException('db');
        }
        if (defined('DB_CHARSET')) {
            @$conn->set_charset(DB_CHARSET);
        }
        $sql = "SELECT i.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                       p.titulo AS propiedad_titulo, a.nombre AS agente_nombre, a.apellido AS agente_apellido
                FROM INTERACCIONES i
                LEFT JOIN CLIENTES c ON i.id_cliente = c.id_cliente
                LEFT JOIN PROPIEDADES p ON i.id_propiedad = p.id_propiedad
                LEFT JOIN AGENTES a ON i.id_agente = a.id_agente
                ORDER BY i.fecha_interaccion DESC";
        $res = @$conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $interacciones_all[] = $row;
            }
            $res->free();
        }
        $conn->close();
    } catch (Throwable $e) {
        if ($aviso_error === '') {
            $aviso_error = 'No se pudieron cargar las interacciones. Intentalo de nuevo.';
        }
    }
}

// Enriquecer filas con nombres (demo y real)
foreach ($interacciones_all as &$r) {
    if (empty($r['cliente_nombre'])) {
        $r['cliente_nombre'] = $map_clientes[$r['id_cliente'] ?? 0] ?? ('Cliente #' . ($r['id_cliente'] ?? '?'));
    } else {
        $r['cliente_nombre'] = trim(($r['cliente_nombre'] ?? '') . ' ' . ($r['cliente_apellido'] ?? ''));
    }
    if (empty($r['propiedad_titulo']) && isset($r['id_propiedad']) && $r['id_propiedad'] !== null) {
        $r['propiedad_titulo'] = $map_propiedades[$r['id_propiedad']] ?? ('Propiedad #' . $r['id_propiedad']);
    }
    if (!empty($r['agente_nombre']) && !str_contains((string)$r['agente_nombre'], ' ')) {
        $r['agente_nombre'] = trim(($r['agente_nombre'] ?? '') . ' ' . ($r['agente_apellido'] ?? ''));
    } elseif (empty($r['agente_nombre']) && isset($r['id_agente']) && $r['id_agente'] !== null) {
        $r['agente_nombre'] = $map_agentes[$r['id_agente']] ?? ('Agente #' . $r['id_agente']);
    }
    $r['_canon'] = c4_normalizar_tipo($r['medio'] ?? '');
}
unset($r);

// Contadores por tipo (sobre el total, sin filtros)
$contadores = ['llamada' => 0, 'email' => 0, 'visita' => 0, 'whatsapp' => 0, 'otro' => 0];
foreach ($interacciones_all as $r) {
    $k = $r['_canon'] ?? 'otro';
    if (!isset($contadores[$k])) {
        $contadores[$k] = 0;
    }
    $contadores[$k]++;
}

// ---------------- Filtros GET: cliente, propiedad, tipo, fecha ----------------
$f_cliente = $_GET['f_cliente'] ?? 'all';
$f_propiedad = $_GET['f_propiedad'] ?? 'all';
$f_tipo = $_GET['f_tipo'] ?? 'all';
$f_fecha = trim((string)($_GET['f_fecha'] ?? ''));

$filtradas = array_filter($interacciones_all, function ($r) use ($f_cliente, $f_propiedad, $f_tipo, $f_fecha) {
    if ($f_cliente !== 'all' && (string)($r['id_cliente'] ?? '') !== (string)$f_cliente) {
        return false;
    }
    if ($f_propiedad !== 'all') {
        if ($f_propiedad === 'none' && !empty($r['id_propiedad'])) {
            return false;
        }
        if ($f_propiedad !== 'none' && (string)($r['id_propiedad'] ?? '') !== (string)$f_propiedad) {
            return false;
        }
    }
    if ($f_tipo !== 'all' && ($r['_canon'] ?? '') !== $f_tipo) {
        return false;
    }
    if ($f_fecha !== '') {
        try {
            $d = (new DateTime((string)($r['fecha_interaccion'] ?? '')))->format('Y-m-d');
            if ($d !== $f_fecha) {
                return false;
            }
        } catch (Throwable $e) {
            return false;
        }
    }
    return true;
});

// Orden cronológico descendente
usort($filtradas, fn($a, $b) => strcmp((string)($b['fecha_interaccion'] ?? ''), (string)($a['fecha_interaccion'] ?? '')));

$hay_filtros = ($f_cliente !== 'all' || $f_propiedad !== 'all' || $f_tipo !== 'all' || $f_fecha !== '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CRM Inmobiliaria - Interacciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/estilos/interacciones.css">
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="page-wrapper">
        <header class="page-header">
            <div class="page-title">
                <h1>Interacciones</h1>
                <p class="subtitle">Timeline cronológico de llamadas, emails, visitas y WhatsApp con cada cliente.</p>
            </div>
            <div class="header-actions">
                <a class="btn btn-secondary" href="?">Limpiar filtros</a>
                <a class="btn btn-primary" href="#nueva">+ Nueva interacción</a>
            </div>
        </header>

        <main class="main-content">
            <?php if ($aviso_demo !== ''): ?>
                <div class="demo-banner" role="status"><?php echo c4_esc($aviso_demo); ?></div>
            <?php endif; ?>
            <?php if ($aviso_ok !== ''): ?>
                <div class="alert-ok" role="status"><?php echo c4_esc($aviso_ok); ?></div>
            <?php endif; ?>
            <?php if ($aviso_error !== ''): ?>
                <div class="alert-error" role="alert"><?php echo c4_esc($aviso_error); ?></div>
            <?php endif; ?>

            <section class="counters" aria-label="Contadores por tipo">
                <?php foreach (['llamada', 'email', 'visita', 'whatsapp'] as $t): ?>
                    <a class="counter counter--<?php echo $t; ?><?php echo $f_tipo === $t ? ' is-active' : ''; ?>"
                       href="?f_cliente=<?php echo urlencode((string)$f_cliente); ?>&amp;f_propiedad=<?php echo urlencode((string)$f_propiedad); ?>&amp;f_tipo=<?php echo $t; ?>&amp;f_fecha=<?php echo urlencode($f_fecha); ?>">
                        <span class="counter__icon"><?php echo c4_svg_icono($t); ?></span>
                        <span class="counter__body">
                            <span class="counter__num"><?php echo (int)($contadores[$t] ?? 0); ?></span>
                            <span class="counter__label"><?php echo c4_etiqueta_tipo($t); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <div class="counter counter--total" aria-label="Total">
                    <span class="counter__body">
                        <span class="counter__num"><?php echo count($interacciones_all); ?></span>
                        <span class="counter__label">Total</span>
                    </span>
                </div>
            </section>

            <section class="filters-card" aria-label="Filtros del timeline">
                <form method="get" action="" class="filters-grid">
                    <label class="field">
                        <span>Cliente</span>
                        <select name="f_cliente">
                            <option value="all">Todos</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?php echo c4_esc($c['id_cliente'] ?? ''); ?>" <?php echo (string)$f_cliente === (string)($c['id_cliente'] ?? '') ? 'selected' : ''; ?>>
                                    <?php echo c4_esc(trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Propiedad</span>
                        <select name="f_propiedad">
                            <option value="all">Todas</option>
                            <option value="none" <?php echo $f_propiedad === 'none' ? 'selected' : ''; ?>>Sin propiedad</option>
                            <?php foreach ($propiedades as $p): ?>
                                <option value="<?php echo c4_esc($p['id_propiedad'] ?? ''); ?>" <?php echo (string)$f_propiedad === (string)($p['id_propiedad'] ?? '') ? 'selected' : ''; ?>>
                                    <?php echo c4_esc($p['titulo'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Tipo</span>
                        <select name="f_tipo">
                            <option value="all">Todos</option>
                            <?php foreach (['llamada' => 'Llamada', 'email' => 'Email', 'visita' => 'Visita', 'whatsapp' => 'WhatsApp'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $f_tipo === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                            <?php if (!empty($contadores['otro'])): ?>
                                <option value="otro" <?php echo $f_tipo === 'otro' ? 'selected' : ''; ?>>Otro</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Fecha</span>
                        <input type="date" name="f_fecha" value="<?php echo c4_esc($f_fecha); ?>">
                    </label>
                    <div class="filters-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <?php if ($hay_filtros): ?>
                            <a class="btn btn-ghost" href="?">Quitar filtros</a>
                        <?php endif; ?>
                    </div>
                </form>
                <p class="filters-result" role="status">
                    <?php echo count($filtradas); ?> de <?php echo count($interacciones_all); ?> interacciones
                    <?php if ($hay_filtros): ?> (con filtros aplicados)<?php endif; ?>
                </p>
            </section>

            <div class="timeline-layout">
                <section class="timeline" aria-label="Timeline de interacciones">
                    <?php if (empty($filtradas)): ?>
                        <div class="empty-state">
                            <div class="empty-state__icon" aria-hidden="true">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <?php if ($hay_filtros): ?>
                                <h2>Sin resultados con esos filtros</h2>
                                <p>Probá quitar un filtro o elegir otra fecha para ver el timeline completo.</p>
                                <a class="btn btn-secondary" href="?">Ver todo el timeline</a>
                            <?php else: ?>
                                <h2>Todavía no hay interacciones</h2>
                                <p>Registrá la primera llamada, email, visita o WhatsApp con el formulario de abajo.</p>
                                <a class="btn btn-primary" href="#nueva">Crear la primera</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filtradas as $it):
                            $canon = $it['_canon'] ?? 'otro';
                            $badge = $canon === 'otro' && !empty($it['medio']) ? $it['medio'] : c4_etiqueta_tipo($canon);
                            $fecha = c4_fecha_corta($it['fecha_interaccion'] ?? null);
                        ?>
                            <article class="timeline-item tipo-<?php echo c4_esc($canon); ?>">
                                <span class="timeline-dot" title="<?php echo c4_esc($badge); ?>"><?php echo c4_svg_icono($canon); ?></span>
                                <div class="timeline-card">
                                    <header class="timeline-card__head">
                                        <span class="tipo-badge"><?php echo c4_esc($badge); ?></span>
                                        <time datetime="<?php echo c4_esc($it['fecha_interaccion'] ?? ''); ?>"><?php echo c4_esc($fecha); ?></time>
                                    </header>
                                    <h3><?php echo c4_esc($it['titulo'] ?? '(sin título)'); ?></h3>
                                    <p class="timeline-meta">
                                        <strong><?php echo c4_esc($it['cliente_nombre'] ?? ''); ?></strong>
                                        <?php if (!empty($it['propiedad_titulo'])): ?>
                                            <span> · <?php echo c4_esc($it['propiedad_titulo']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($it['agente_nombre'])): ?>
                                            <span> · <?php echo c4_esc($it['agente_nombre']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($it['descripcion'])): ?>
                                        <p class="timeline-desc"><?php echo c4_esc($it['descripcion']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($it['proxima_accion'])): ?>
                                        <p class="timeline-next">Próxima: <?php echo c4_esc($it['proxima_accion']); ?></p>
                                    <?php endif; ?>
                                    <form method="post" action="" class="timeline-delete"
                                          onsubmit="return confirm('¿Eliminar esta interacción? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_interaccion" value="<?php echo (int)($it['id_interaccion'] ?? 0); ?>">
                                        <button type="submit" class="btn btn-danger-ghost">Eliminar</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <aside class="timeline-form" id="nueva" aria-label="Crear interacción">
                    <h2>Nueva interacción</h2>
                    <p class="form-hint">Cliente, tipo y fecha son obligatorios.</p>
                    <form method="post" action="#nueva">
                        <input type="hidden" name="accion" value="crear">
                        <label class="field" for="f-id-cliente">
                            <span>Cliente *</span>
                            <select id="f-id-cliente" name="id_cliente" required>
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?php echo c4_esc($c['id_cliente'] ?? ''); ?>">
                                        <?php echo c4_esc(trim(($c['nombre'] ?? '') . ' ' . ($c['apellido'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field" for="f-tipo">
                            <span>Tipo *</span>
                            <select id="f-tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="llamada">Llamada</option>
                                <option value="email">Email</option>
                                <option value="visita">Visita</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                        </label>
                        <label class="field" for="f-fecha-int">
                            <span>Fecha *</span>
                            <input id="f-fecha-int" type="datetime-local" name="fecha_interaccion" required>
                        </label>
                        <label class="field" for="f-id-prop">
                            <span>Propiedad (opcional)</span>
                            <select id="f-id-prop" name="id_propiedad">
                                <option value="">Sin propiedad</option>
                                <?php foreach ($propiedades as $p): ?>
                                    <option value="<?php echo c4_esc($p['id_propiedad'] ?? ''); ?>"><?php echo c4_esc($p['titulo'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field" for="f-id-ag">
                            <span>Agente (opcional)</span>
                            <select id="f-id-ag" name="id_agente">
                                <option value="">Sin agente</option>
                                <?php foreach ($agentes as $a): ?>
                                    <option value="<?php echo c4_esc($a['id_agente'] ?? ''); ?>">
                                        <?php echo c4_esc(trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field" for="f-titulo">
                            <span>Título (opcional)</span>
                            <input id="f-titulo" type="text" name="titulo" maxlength="120" placeholder="Ej: Llamada con Juan Pérez">
                        </label>
                        <label class="field" for="f-desc">
                            <span>Descripción (opcional)</span>
                            <textarea id="f-desc" name="descripcion" rows="3" placeholder="Resumen del contacto..."></textarea>
                        </label>
                        <button type="submit" class="btn btn-primary">Guardar interacción</button>
                    </form>
                </aside>
            </div>
        </main>
        <footer>
            <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
