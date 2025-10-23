<?php
require_once __DIR__ . '/config.php';

// Conectar a la base de datos
$db_ok = true;
$warn = [];
$conn = null;
if ($db_ok) {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_errno) {
        $db_ok = false;
        $warn[] = 'Error de conexión (' . $conn->connect_errno . '): ' . $conn->connect_error;
    } else {
        if (defined('DB_CHARSET')) {
            $conn->set_charset(DB_CHARSET);
        }
    }
}

// Datos del Kanban
$kanban_data = [];
$counts = ['nuevo' => 0, 'seguimiento' => 0, 'negociacion' => 0, 'cerrado' => 0];

// Datos para filtros
$agentes = [];
$medios = [];
$prioridades = ['alta', 'media', 'baja'];
$clientes = [];
$propiedades = [];

if ($db_ok && $conn) {
    // Obtener agentes
    if ($result = $conn->query("SELECT id_agente, nombre, apellido FROM AGENTES ORDER BY nombre")) {
        while ($row = $result->fetch_assoc()) {
            $agentes[] = $row;
        }
        $result->free();
    }

    // Obtener clientes
    if ($result = $conn->query("SELECT id_cliente, nombre, apellido FROM CLIENTES ORDER BY nombre")) {
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        $result->free();
    }

    // Obtener propiedades
    if ($result = $conn->query("SELECT id_propiedad, titulo FROM PROPIEDADES ORDER BY titulo")) {
        while ($row = $result->fetch_assoc()) {
            $propiedades[] = $row;
        }
        $result->free();
    }

    // Obtener medios únicos
    if ($result = $conn->query("SELECT DISTINCT medio FROM INTERACCIONES WHERE medio IS NOT NULL ORDER BY medio")) {
        while ($row = $result->fetch_assoc()) {
            $medios[] = $row['medio'];
        }
        $result->free();
    }
}

// Manejar creación de nueva interacción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'] ?? '';
    $id_cliente = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
    $id_propiedad = !empty($_POST['id_propiedad']) ? (int)$_POST['id_propiedad'] : null;
    $id_agente = !empty($_POST['id_agente']) ? (int)$_POST['id_agente'] : null;
    $medio = $_POST['medio'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $estado_kanban = $_POST['estado_kanban'] ?? 'nuevo';
    $proxima_accion = $_POST['proxima_accion'] ?? '';
    $fecha_proxima_accion = $_POST['fecha_proxima_accion'] ?? null;
    $fecha_interaccion = date('Y-m-d H:i:s');

    // Calcular posición
    $posicion = 0;
    $stmt_pos = $conn->prepare("SELECT MAX(posicion) as max_pos FROM INTERACCIONES WHERE estado_kanban = ?");
    $stmt_pos->bind_param("s", $estado_kanban);
    $stmt_pos->execute();
    $result_pos = $stmt_pos->get_result();
    if ($row = $result_pos->fetch_assoc()) {
        $posicion = ($row['max_pos'] ?? 0) + 1;
    }
    $stmt_pos->close();

    // Insertar
    $stmt = $conn->prepare("INSERT INTO INTERACCIONES (titulo, descripcion, fecha_interaccion, id_cliente, id_propiedad, id_agente, medio, prioridad, estado_kanban, posicion, proxima_accion, fecha_proxima_accion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiisssisss", $titulo, $descripcion, $fecha_interaccion, $id_cliente, $id_propiedad, $id_agente, $medio, $prioridad, $estado_kanban, $posicion, $proxima_accion, $fecha_proxima_accion);
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $warn[] = 'Error al crear interacción: ' . $stmt->error;
    }
    $stmt->close();
}

if ($db_ok && $conn) {
    // Consulta para obtener interacciones con joins para nombres
    $sql = "SELECT
    interaccion.*,
    cliente.nombre AS cliente_nombre,
    cliente.apellido AS cliente_apellido,
    propiedad.titulo AS propiedad_titulo,
    (SELECT imagen_perfil.url
     FROM IMAGENES_PERFILES AS imagen_perfil
     WHERE (imagen_perfil.id_cliente = interaccion.id_cliente
            OR imagen_perfil.id_agente = interaccion.id_agente)
       AND imagen_perfil.principal = 1
     LIMIT 1) AS imagen_perfil_url,
    imagen_propiedad.imagen AS imagen_propiedad_blob
FROM INTERACCIONES AS interaccion
LEFT JOIN CLIENTES AS cliente ON interaccion.id_cliente = cliente.id_cliente
LEFT JOIN PROPIEDADES AS propiedad ON interaccion.id_propiedad = propiedad.id_propiedad
LEFT JOIN IMAGENES AS imagen_propiedad ON propiedad.id_propiedad = imagen_propiedad.id_propiedad AND imagen_propiedad.principal = 1
ORDER BY interaccion.estado_kanban, interaccion.posicion, interaccion.fecha_interaccion DESC";
    if ($result = $conn->query($sql)) {
        // Obtener lista de imágenes locales para fallback
        $image_dir = __DIR__ . '/../image/Inmueble';
        $image_files = glob($image_dir . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
        while ($row = $result->fetch_assoc()) {
            $estado = $row['estado_kanban'] ?: 'nuevo';
            if (!isset($kanban_data[$estado])) {
                $kanban_data[$estado] = [];
            }
            // Generar imagen aleatoria local como blob si no hay blob en DB
            $imagen_propiedad_blob = $row['imagen_propiedad_blob'];
            $mime = '';
            if (!$imagen_propiedad_blob && !empty($image_files)) {
                $random_image = $image_files[array_rand($image_files)];
                $image_data = file_get_contents($random_image);
                $imagen_propiedad_blob = base64_encode($image_data);
                $mime = getMimeType($random_image);
            }
            $row['imagen_propiedad_blob'] = $imagen_propiedad_blob;
            $row['mime'] = $mime;
            $kanban_data[$estado][] = $row;
            $counts[$estado]++;
        }
        $result->free();
    }

    $conn->close();
}

// Función auxiliar para obtener MIME type desde extensión
function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];
    return $mimes[$ext] ?? 'application/octet-stream';
}

// Función auxiliar para formatear fecha
function formatDate($date)
{
    if (!$date) return 'Sin fecha';
    $d = new DateTime($date);
    return $d->format('d/m');
}
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
                <h1>Tablero de Interacciones</h1>
                <p class="subtitle">Organiza cada contacto según su estado y coordina las próximas acciones del equipo.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary">Exportar resumen</button>
                <button type="button" class="btn btn-primary">+ Nueva interacción</button>
            </div>
        </header>
        <main class="main-content">
            <?php if (!$db_ok): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                    <h3 style="color: #ef4444; margin: 0 0 8px;">Error de conexión a la base de datos</h3>
                    <ul style="color: var(--text); margin: 0; padding-left: 20px;">
                        <?php foreach ($warn as $w): ?>
                            <li><?php echo htmlspecialchars($w); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <section class="kanban-toolbar" aria-label="Filtros del tablero">
                <div class="filters">
                    <label class="field">
                        <span>Agente</span>
                        <select name="filter-agent" id="filter-agent">
                            <option value="all">Todos</option>
                            <?php foreach ($agentes as $agente): ?>
                                <option value="<?php echo $agente['id_agente'] ?? ''; ?>"><?php echo htmlspecialchars(($agente['nombre'] ?? '') . ' ' . ($agente['apellido'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Canal</span>
                        <select name="filter-channel" id="filter-channel">
                            <option value="all">Todos</option>
                            <?php foreach ($medios as $medio): ?>
                                <option value="<?php echo strtolower($medio); ?>"><?php echo htmlspecialchars($medio); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Prioridad</span>
                        <select name="filter-priority" id="filter-priority">
                            <option value="all">Todas</option>
                            <?php foreach ($prioridades as $prioridad): ?>
                                <option value="<?php echo $prioridad; ?>"><?php echo ucfirst($prioridad); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="toolbar-actions">
                    <button type="button" class="btn btn-ghost">Ver historial</button>
                    <button type="button" class="btn btn-ghost">Plantillas</button>
                </div>
            </section>

            <section class="sql-query-preview" aria-label="Consulta SQL utilizada">
                <h3 class="sql-query-preview__title">Consulta SQL</h3>
                <pre class="sql-query-preview__code"><code><?php echo htmlspecialchars($sql); ?></code></pre>
            </section>

            <section class="kanban-board" aria-label="Tablero Kanban de interacciones">
                <?php
                $column_config = [
                    'nuevo' => [
                        'title' => 'Nuevo contacto',
                        'description' => 'Leads recién registrados y pendientes de primer contacto.',
                        'data_status' => 'nuevo-contacto'
                    ],
                    'seguimiento' => [
                        'title' => 'Seguimiento',
                        'description' => 'Contactos que requieren acciones programadas.',
                        'data_status' => 'seguimiento'
                    ],
                    'negociacion' => [
                        'title' => 'Negociación',
                        'description' => 'Casos con propuestas económicas y contraofertas.',
                        'data_status' => 'negociacion'
                    ],
                    'cerrado' => [
                        'title' => 'Cerrado',
                        'description' => 'Interacciones finalizadas y registradas en el CRM.',
                        'data_status' => 'cerrado'
                    ]
                ];

                foreach ($column_config as $estado => $config) {
                    echo '<article class="kanban-column" data-status="' . $config['data_status'] . '">';
                    echo '<header class="column-header">';
                    echo '<div>';
                    echo '<h2>' . $config['title'] . '</h2>';
                    echo '<p class="column-description">' . $config['description'] . '</p>';
                    echo '</div>';
                    echo '<span class="badge">' . ($counts[$estado] ?? 0) . '</span>';
                    echo '</header>';
                    echo '<div class="kanban-list">';

                    if (isset($kanban_data[$estado])) {
                        foreach ($kanban_data[$estado] as $interaccion) {
                            $cliente = trim(($interaccion['cliente_nombre'] ?? '') . ' ' . ($interaccion['cliente_apellido'] ?? ''));
                            $prioridad_class = ($interaccion['prioridad'] ?? 'media') == 'alta' ? 'high' : (($interaccion['prioridad'] ?? 'media') == 'media' ? 'medium' : 'low');
                            $prioridad_text = ucfirst($interaccion['prioridad'] ?? 'media');

                            echo '<article class="kanban-card" data-id="' . ($interaccion['id_interaccion'] ?? 0) . '" data-titulo="' . htmlspecialchars($interaccion['titulo'] ?? '') . '" data-descripcion="' . htmlspecialchars($interaccion['descripcion'] ?? '') . '" data-cliente="' . htmlspecialchars($cliente) . '" data-medio="' . htmlspecialchars($interaccion['medio'] ?? '') . '" data-prioridad="' . $prioridad_text . '" data-proxima="' . htmlspecialchars($interaccion['proxima_accion'] ?? '') . '" data-fecha="' . formatDate($interaccion['fecha_proxima_accion']) . '" data-imagen-perfil="' . htmlspecialchars($interaccion['imagen_perfil_url'] ?? '') . '" data-imagen-propiedad-blob="' . htmlspecialchars($interaccion['imagen_propiedad_blob'] ?? '') . '" data-mime="' . htmlspecialchars($interaccion['mime']) . '" data-id-cliente="' . ($interaccion['id_cliente'] ?? '') . '" data-id-propiedad="' . ($interaccion['id_propiedad'] ?? '') . '" data-id-agente="' . ($interaccion['id_agente'] ?? '') . '">';

                            if ($interaccion['imagen_perfil_url'] ?? '') {
                                echo '<img class="card-avatar" src="' . htmlspecialchars($interaccion['imagen_perfil_url']) . '" alt="Imagen de perfil">';
                            } else {
                                echo '<div class="card-avatar">👤</div>';
                            }
                            echo '<div class="card-content">';
                            echo '<h3>' . htmlspecialchars($interaccion['titulo'] ?? '') . '</h3>';
                            echo '<p>' . htmlspecialchars($cliente) . '</p>';
                            echo '</div>';
                            echo '</article>';
                        }
                    }

                    echo '</div>';
                    echo '</article>';
                }
                ?>
            </section>
        </main>
        <footer>
            <p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
        </footer>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <div class="modal-property-image" id="modal-property-image"></div>
            <div class="modal-body">
                <div class="modal-avatar">👤</div>
                <div class="modal-info">
                    <h2 id="modal-titulo"></h2>
                    <small id="modal-sql-titulo"></small>
                    <p id="modal-cliente"></p>
                    <small id="modal-sql-cliente"></small>
                    <div class="modal-details">
                        <p><strong>Descripción:</strong> <span id="modal-descripcion"></span></p>
                        <small id="modal-sql-descripcion"></small>
                        <p><strong>Canal:</strong> <span id="modal-medio"></span></p>
                        <small id="modal-sql-medio"></small>
                        <p><strong>Prioridad:</strong> <span id="modal-prioridad"></span></p>
                        <small id="modal-sql-prioridad"></small>
                        <p><strong>Próxima acción:</strong> <span id="modal-proxima"></span></p>
                        <small id="modal-sql-proxima"></small>
                        <p><strong>Fecha próxima:</strong> <span id="modal-fecha"></span></p>
                        <small id="modal-sql-fecha"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Interaction Modal -->
    <div id="new-interaction-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Nueva Interacción</h2>
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <form method="post" action="" style="flex: 1;">
                    <label for="titulo">Título:</label>
                    <input type="text" id="titulo" name="titulo" required>
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion"></textarea>
                    <label for="id_cliente">Cliente:</label>
                    <select id="id_cliente" name="id_cliente">
                        <option value="">Seleccionar cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id_cliente'] ?? ''; ?>"><?php echo htmlspecialchars(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="id_propiedad">Propiedad:</label>
                    <select id="id_propiedad" name="id_propiedad">
                        <option value="">Seleccionar propiedad</option>
                        <?php foreach ($propiedades as $propiedad): ?>
                            <option value="<?php echo $propiedad['id_propiedad'] ?? ''; ?>"><?php echo htmlspecialchars($propiedad['titulo'] ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="id_agente">Agente:</label>
                    <select id="id_agente" name="id_agente">
                        <option value="">Seleccionar agente</option>
                        <?php foreach ($agentes as $agente): ?>
                            <option value="<?php echo $agente['id_agente'] ?? ''; ?>"><?php echo htmlspecialchars(($agente['nombre'] ?? '') . ' ' . ($agente['apellido'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="medio">Medio:</label>
                    <input type="text" id="medio" name="medio">
                    <label for="prioridad">Prioridad:</label>
                    <select id="prioridad" name="prioridad">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                    </select>
                    <label for="estado_kanban">Estado:</label>
                    <select id="estado_kanban" name="estado_kanban">
                        <option value="nuevo">Nuevo</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="negociacion">Negociación</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                    <label for="proxima_accion">Próxima Acción:</label>
                    <input type="text" id="proxima_accion" name="proxima_accion">
                    <label for="fecha_proxima_accion">Fecha Próxima Acción:</label>
                    <input type="date" id="fecha_proxima_accion" name="fecha_proxima_accion">
                    <button type="submit" class="btn btn-primary">Crear Interacción</button>
                </form>
                <div id="sql-preview" style="flex: 1; margin-top:20px; font-family:'Fira Code', Consolas, 'Courier New', monospace; font-size:14px; color:var(--accent-soft); white-space:pre-wrap;"></div>
            </div>
        </div>
    </div>
    <script>
        const modal = document.getElementById('modal');
        const modalClose = document.querySelector('.modal-close');

        // Ensure modal is hidden on load
        modal.style.display = 'none';

        // Modal
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('click', () => {
                const imagenPerfil = card.dataset.imagenPerfil;
                const imagenPropiedadBlob = card.dataset.imagenPropiedadBlob;
                const modalAvatar = document.querySelector('.modal-avatar');
                const modalPropertyImage = document.getElementById('modal-property-image');
                if (imagenPropiedadBlob) {
                    modalPropertyImage.innerHTML = '<img src="data:' + card.dataset.mime + ';base64,' + imagenPropiedadBlob + '" alt="Imagen de propiedad">';
                } else {
                    const randomHouse = 'https://via.placeholder.com/500x200/cccccc/000000?text=Casa+' + Math.floor(Math.random() * 10 + 1);
                    modalPropertyImage.innerHTML = '<img src="' + randomHouse + '" alt="Imagen de propiedad">';
                }
                if (imagenPerfil) {
                    modalAvatar.innerHTML = '<img src="' + imagenPerfil + '" alt="Imagen de perfil">';
                } else {
                    modalAvatar.innerHTML = '👤';
                }
                document.getElementById('modal-titulo').textContent = card.dataset.titulo;
                document.getElementById('modal-cliente').textContent = card.dataset.cliente;
                document.getElementById('modal-descripcion').textContent = card.dataset.descripcion;
                document.getElementById('modal-medio').textContent = card.dataset.medio;
                document.getElementById('modal-prioridad').textContent = card.dataset.prioridad;
                document.getElementById('modal-proxima').textContent = card.dataset.proxima;
                document.getElementById('modal-fecha').textContent = card.dataset.fecha;
                document.getElementById('modal-sql-titulo').textContent = 'SQL: SELECT titulo FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                document.getElementById('modal-sql-cliente').textContent = 'SQL: SELECT nombre, apellido FROM CLIENTES WHERE id_cliente = ' + card.dataset.idCliente;
                document.getElementById('modal-sql-descripcion').textContent = 'SQL: SELECT descripcion FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                document.getElementById('modal-sql-medio').textContent = 'SQL: SELECT medio FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                document.getElementById('modal-sql-prioridad').textContent = 'SQL: SELECT prioridad FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                document.getElementById('modal-sql-proxima').textContent = 'SQL: SELECT proxima_accion FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                document.getElementById('modal-sql-fecha').textContent = 'SQL: SELECT fecha_proxima_accion FROM INTERACCIONES WHERE id_interaccion = ' + card.dataset.id;
                modal.style.display = 'block';
            });
        });

        modalClose.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Toggle column descriptions
        document.querySelectorAll('.column-header h2').forEach(h2 => {
            h2.addEventListener('click', () => {
                const desc = h2.nextElementSibling;
                if (desc.style.display === 'none' || desc.style.display === '') {
                    desc.style.display = 'block';
                } else {
                    desc.style.display = 'none';
                }
            });
        });

        // New Interaction Modal
        const newModal = document.getElementById('new-interaction-modal');
        const newModalClose = newModal.querySelector('.modal-close');
        const newBtn = document.querySelector('.header-actions .btn-primary');
        const sqlPreview = document.getElementById('sql-preview');
        const form = document.querySelector('#new-interaction-modal form');
        newModal.style.display = 'none';
        newBtn.addEventListener('click', () => {
            newModal.style.display = 'block';
            updateSQL();
        });
        newModalClose.addEventListener('click', () => {
            newModal.style.display = 'none';
        });
        window.addEventListener('click', (e) => {
            if (e.target === newModal) {
                newModal.style.display = 'none';
            }
        });

        function updateSQL() {
            const titulo = form.titulo.value ? `'${form.titulo.value.replace(/'/g, "''")}'` : 'NULL';
            const descripcion = form.descripcion.value ? `'${form.descripcion.value.replace(/'/g, "''")}'` : 'NULL';
            const id_cliente = form.id_cliente.value || 'NULL';
            const id_propiedad = form.id_propiedad.value || 'NULL';
            const id_agente = form.id_agente.value || 'NULL';
            const medio = form.medio.value ? `'${form.medio.value.replace(/'/g, "''")}'` : 'NULL';
            const prioridad = `'${form.prioridad.value}'`;
            const estado_kanban = `'${form.estado_kanban.value}'`;
            const proxima_accion = form.proxima_accion.value ? `'${form.proxima_accion.value.replace(/'/g, "''")}'` : 'NULL';
            const fecha_proxima_accion = form.fecha_proxima_accion.value ? `'${form.fecha_proxima_accion.value}'` : 'NULL';
            const fecha_interaccion = 'NOW()';
            const posicion = 'AUTO';
            const sql = `INSERT INTO INTERACCIONES
    (titulo, descripcion, fecha_interaccion, id_cliente, id_propiedad, id_agente, medio, prioridad, estado_kanban, posicion, proxima_accion, fecha_proxima_accion)
VALUES
    (${titulo}, ${descripcion}, ${fecha_interaccion}, ${id_cliente}, ${id_propiedad}, ${id_agente}, ${medio}, ${prioridad}, ${estado_kanban}, ${posicion}, ${proxima_accion}, ${fecha_proxima_accion})`;
            sqlPreview.textContent = sql;
        }

        form.addEventListener('input', updateSQL);
        form.addEventListener('change', updateSQL);
    </script>
</body>

</html>