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

if ($db_ok && $conn) {
    // Obtener agentes
    if ($result = $conn->query("SELECT id_agente, nombre, apellido FROM AGENTES ORDER BY nombre")) {
        while ($row = $result->fetch_assoc()) {
            $agentes[] = $row;
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

if ($db_ok && $conn) {
    // Consulta para obtener interacciones con joins para nombres
    $sql = "SELECT i.*, 
                   c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                   p.titulo AS propiedad_titulo,
                   (SELECT ip.url FROM IMAGENES_PERFILES ip WHERE (ip.id_cliente = i.id_cliente OR ip.id_agente = i.id_agente) AND ip.principal = 1 LIMIT 1) AS imagen_perfil_url,
                   iprop.url AS imagen_propiedad_url
            FROM INTERACCIONES i
            LEFT JOIN CLIENTES c ON i.id_cliente = c.id_cliente
            LEFT JOIN PROPIEDADES p ON i.id_propiedad = p.id_propiedad
            LEFT JOIN IMAGENES iprop ON p.id_propiedad = iprop.id_propiedad AND iprop.principal = 1
            ORDER BY i.estado_kanban, i.posicion, i.fecha_interaccion DESC";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $estado = $row['estado_kanban'];
            if (!isset($kanban_data[$estado])) {
                $kanban_data[$estado] = [];
            }
            $kanban_data[$estado][] = $row;
            $counts[$estado]++;
        }
        $result->free();
    }

    $conn->close();
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
                                <option value="<?php echo $agente['id_agente']; ?>"><?php echo htmlspecialchars($agente['nombre'] . ' ' . $agente['apellido']); ?></option>
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
                            $cliente = trim($interaccion['cliente_nombre'] . ' ' . $interaccion['cliente_apellido']);
                            $prioridad_class = $interaccion['prioridad'] == 'alta' ? 'high' : ($interaccion['prioridad'] == 'media' ? 'medium' : 'low');
                            $prioridad_text = ucfirst($interaccion['prioridad']);

                            echo '<article class="kanban-card" data-id="' . $interaccion['id_interaccion'] . '" data-titulo="' . htmlspecialchars($interaccion['titulo']) . '" data-descripcion="' . htmlspecialchars($interaccion['descripcion']) . '" data-cliente="' . htmlspecialchars($cliente) . '" data-medio="' . htmlspecialchars($interaccion['medio']) . '" data-prioridad="' . $prioridad_text . '" data-proxima="' . htmlspecialchars($interaccion['proxima_accion'] ?? '') . '" data-fecha="' . formatDate($interaccion['fecha_proxima_accion']) . '" data-imagen-perfil="' . htmlspecialchars($interaccion['imagen_perfil_url'] ?? '') . '" data-imagen-propiedad="' . htmlspecialchars($interaccion['imagen_propiedad_url'] ?? '') . '">';
                            if ($interaccion['imagen_perfil_url']) {
                                echo '<img class="card-avatar" src="' . htmlspecialchars($interaccion['imagen_perfil_url']) . '" alt="Imagen de perfil">';
                            } else {
                                echo '<div class="card-avatar">👤</div>';
                            }
                            echo '<div class="card-content">';
                            echo '<h3>' . htmlspecialchars($interaccion['titulo']) . '</h3>';
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
                    <p id="modal-cliente"></p>
                    <div class="modal-details">
                        <p><strong>Descripción:</strong> <span id="modal-descripcion"></span></p>
                        <p><strong>Canal:</strong> <span id="modal-medio"></span></p>
                        <p><strong>Prioridad:</strong> <span id="modal-prioridad"></span></p>
                        <p><strong>Próxima acción:</strong> <span id="modal-proxima"></span></p>
                        <p><strong>Fecha próxima:</strong> <span id="modal-fecha"></span></p>
                    </div>
                </div>
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
                const imagenPropiedad = card.dataset.imagenPropiedad;
                const modalAvatar = document.querySelector('.modal-avatar');
                const modalPropertyImage = document.getElementById('modal-property-image');
                modalPropertyImage.innerHTML = imagenPropiedad ? '<img src="' + imagenPropiedad + '" alt="Imagen de propiedad">' : '<img src="https://via.placeholder.com/500x200/cccccc/000000?text=Imagen+de+Propiedad" alt="Imagen de propiedad">';
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
    </script>
</body>

</html>