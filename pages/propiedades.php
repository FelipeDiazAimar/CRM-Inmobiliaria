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

// Query propiedades disponibles
$props = [];
if ($db_ok) {
	$sql_props = "SELECT p.id_propiedad, p.titulo, p.tipo, p.precio, p.direccion AS ubicacion, p.ciudad, p.provincia, p.estado, p.descripcion, p.superficie, p.ambientes, p.banos, p.dormitorios, p.antiguedad,
						 a.nombre AS agente_nombre, a.apellido AS agente_apellido, a.email AS agente_email, a.telefono AS agente_telefono
				  FROM PROPIEDADES p
				  LEFT JOIN AGENTES a ON p.id_agente = a.id_agente
				  WHERE p.estado = 'disponible'
				  ORDER BY p.id_propiedad DESC";
	if ($res = $conn->query($sql_props)) {
		while ($row = $res->fetch_assoc()) {
			$props[] = $row;
		}
		$res->free();
	}
}
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
			<h1>Catálogo de Propiedades Disponibles</h1>
			<p class="subtitle">Explora las propiedades disponibles, sus características, agentes asignados y clientes interesados.</p>
			<?php
			if (!$db_ok) {
				echo '<div class="error">Error de base de datos: ' . implode('<br>', $warn) . '</div>';
			} elseif (empty($props)) {
				echo '<p>No hay propiedades disponibles en este momento.</p>';
			} else {
				echo '<div class="grid">';
				foreach ($props as $prop) {
					$propId = (int)$prop['id_propiedad'];

					// Imágenes asociadas
					$images = [];
					$stmt_img = $conn->prepare("SELECT url FROM IMAGENES WHERE id_propiedad = ? ORDER BY principal DESC, id_imagen");
					if ($stmt_img) {
						$stmt_img->bind_param('i', $propId);
						$stmt_img->execute();
						$res_img = $stmt_img->get_result();
						while ($img = $res_img->fetch_assoc()) {
							$images[] = $img['url'];
						}
						$stmt_img->close();
					}

					// Interacciones con clientes
					$interacciones = [];
					$sql_interes = "SELECT c.nombre, c.apellido, c.email, c.telefono, i.fecha_interaccion AS fecha, i.medio, i.descripcion AS notas
									FROM INTERACCIONES i
									JOIN CLIENTES c ON i.id_cliente = c.id_cliente
									WHERE i.id_propiedad = ?
									ORDER BY i.fecha_interaccion DESC";
					$stmt_interes = $conn->prepare($sql_interes);
					if ($stmt_interes) {
						$stmt_interes->bind_param('i', $propId);
						$stmt_interes->execute();
						$res_interes = $stmt_interes->get_result();
						while ($cliente = $res_interes->fetch_assoc()) {
							$interacciones[] = $cliente;
						}
						$stmt_interes->close();
					}

					// Transacciones registradas
					$transacciones = [];
					$sql_trans = "SELECT tipo AS tipo_transaccion, monto AS precio_final, fecha_inicio AS fecha
								  FROM TRANSACCIONES
								  WHERE id_propiedad = ?";
					$stmt_trans = $conn->prepare($sql_trans);
					if ($stmt_trans) {
						$stmt_trans->bind_param('i', $propId);
						$stmt_trans->execute();
						$res_trans = $stmt_trans->get_result();
						while ($trans = $res_trans->fetch_assoc()) {
							$transacciones[] = $trans;
						}
						$stmt_trans->close();
					}

					// Citas agendadas
					$citas = [];
					$sql_citas = "SELECT c.nombre, c.apellido, citas.fecha, citas.estado, citas.notas
								  FROM CITAS citas
								  JOIN CLIENTES c ON citas.id_cliente = c.id_cliente
								  WHERE citas.id_propiedad = ?
								  ORDER BY citas.fecha";
					$stmt_citas = $conn->prepare($sql_citas);
					if ($stmt_citas) {
						$stmt_citas->bind_param('i', $propId);
						$stmt_citas->execute();
						$res_citas = $stmt_citas->get_result();
						while ($cita = $res_citas->fetch_assoc()) {
							$citas[] = $cita;
						}
						$stmt_citas->close();
					}

					echo '<div class="card">';
					echo '<h2>' . htmlspecialchars($prop['titulo']) . '</h2>';
					if (!empty($images)) {
						echo '<div class="card-image">';
						echo '<img src="../' . htmlspecialchars($images[0]) . '" alt="Imagen de propiedad">';
						echo '</div>';
					} else {
						echo '<div class="card-image">';
						echo '<img src="https://via.placeholder.com/400x300/1f2937/94a3b8?text=Sin+Imagen" alt="Imagen genérica">';
						echo '</div>';
					}
					echo '<div class="card-summary">';
					echo '<p class="location">' . htmlspecialchars($prop['tipo']) . ' · ' . htmlspecialchars($prop['ciudad']) . ', ' . htmlspecialchars($prop['provincia']) . '</p>';
					echo '<p class="price">$' . number_format($prop['precio'], 0, ',', '.') . '</p>';
					echo '<p>Superficie: ' . htmlspecialchars($prop['superficie']) . ' m² · Ambientes: ' . htmlspecialchars($prop['ambientes']) . '</p>';
					echo '<p>Estado: ' . htmlspecialchars($prop['estado']) . '</p>';
					echo '</div>';
					echo '<div class="card-actions">';
					echo '<button class="open-modal" data-target="modal-' . $propId . '" data-prop="' . $propId . '">Ver detalles</button>';
					echo '</div>';
					echo '</div>';

					echo '<div id="modal-' . $propId . '" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true">';
					echo '<div class="modal-content">';
					echo '<button class="modal-close" data-target="modal-' . $propId . '" aria-label="Cerrar ventana">×</button>';
					echo '<h2>' . htmlspecialchars($prop['titulo']) . '</h2>';
					echo '<p><em>' . htmlspecialchars($prop['tipo']) . ' en ' . htmlspecialchars($prop['ubicacion']) . ', ' . htmlspecialchars($prop['ciudad']) . ', ' . htmlspecialchars($prop['provincia']) . '</em></p>';
					echo '<div class="modal-body">';
					if (!empty($images)) {
						echo '<div class="image-gallery modal-gallery" data-prop="' . $propId . '" data-images="' . htmlspecialchars(json_encode($images), ENT_QUOTES) . '">';
						echo '<img id="img-' . $propId . '" src="../' . htmlspecialchars($images[0]) . '" alt="Imagen de propiedad">';
						if (count($images) > 1) {
							echo '<button class="prev" onclick="changeImage(' . $propId . ', -1)">‹</button>';
							echo '<button class="next" onclick="changeImage(' . $propId . ', 1)">›</button>';
						}
						echo '</div>';
					} else {
						echo '<img src="https://via.placeholder.com/800x600/111827/94a3b8?text=Sin+Imagen" alt="Imagen genérica" class="modal-placeholder">';
					}

					echo '<div class="modal-section">';
					echo '<h3>Detalles de la Propiedad</h3>';
					echo '<ul>';
					echo '<li><strong>Precio:</strong> $' . number_format($prop['precio'], 0, ',', '.') . '</li>';
					echo '<li><strong>Superficie:</strong> ' . htmlspecialchars($prop['superficie']) . ' m²</li>';
					echo '<li><strong>Ambientes:</strong> ' . htmlspecialchars($prop['ambientes']) . '</li>';
					echo '<li><strong>Baños:</strong> ' . htmlspecialchars($prop['banos']) . '</li>';
					echo '<li><strong>Dormitorios:</strong> ' . htmlspecialchars($prop['dormitorios']) . '</li>';
					echo '<li><strong>Antigüedad:</strong> ' . htmlspecialchars($prop['antiguedad']) . ' años</li>';
					echo '<li><strong>Estado:</strong> ' . htmlspecialchars($prop['estado']) . '</li>';
					echo '</ul>';
					if (!empty($prop['descripcion'])) {
						echo '<p>' . nl2br(htmlspecialchars($prop['descripcion'])) . '</p>';
					}
					echo '</div>';

					echo '<div class="modal-section">';
					echo '<h3>Agente Asignado</h3>';
					if (!empty($prop['agente_nombre'])) {
						echo '<p>' . htmlspecialchars($prop['agente_nombre'] . ' ' . $prop['agente_apellido']) . '</p>';
						echo '<p>Email: ' . htmlspecialchars($prop['agente_email']) . '</p>';
						echo '<p>Teléfono: ' . htmlspecialchars($prop['agente_telefono']) . '</p>';
					} else {
						echo '<p>No asignado</p>';
					}
					echo '</div>';

					echo '<div class="modal-section">';
					echo '<h3>Interacciones con Clientes</h3>';
					if (!empty($interacciones)) {
						echo '<ul>';
						foreach ($interacciones as $cliente) {
							echo '<li>';
							echo '<strong>' . htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) . '</strong>';
							echo '<br>Medio: ' . htmlspecialchars($cliente['medio']);
							echo '<br>Email: ' . htmlspecialchars($cliente['email']) . ' | Tel: ' . htmlspecialchars($cliente['telefono']);
							echo '<br>Fecha: ' . htmlspecialchars($cliente['fecha']);
							if (!empty($cliente['notas'])) {
								echo '<br>Notas: ' . htmlspecialchars($cliente['notas']);
							}
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>No hay interacciones registradas para esta propiedad.</p>';
					}
					echo '</div>';

					echo '<div class="modal-section">';
					echo '<h3>Transacciones</h3>';
					if (!empty($transacciones)) {
						echo '<ul>';
						foreach ($transacciones as $trans) {
							echo '<li>';
							echo '<strong>' . htmlspecialchars($trans['tipo_transaccion']) . '</strong> - Monto: $' . number_format($trans['precio_final'], 0, ',', '.');
							echo ' - Fecha: ' . htmlspecialchars($trans['fecha']);
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>No hay transacciones registradas.</p>';
					}
					echo '</div>';

					echo '<div class="modal-section">';
					echo '<h3>Citas Programadas</h3>';
					if (!empty($citas)) {
						echo '<ul>';
						foreach ($citas as $cita) {
							echo '<li>';
							echo '<strong>' . htmlspecialchars($cita['nombre'] . ' ' . $cita['apellido']) . '</strong>';
							echo ' - Fecha: ' . htmlspecialchars($cita['fecha']) . ' - Estado: ' . htmlspecialchars($cita['estado']);
							if (!empty($cita['notas'])) {
								echo ' - Notas: ' . htmlspecialchars($cita['notas']);
							}
							echo '</li>';
						}
						echo '</ul>';
					} else {
						echo '<p>No hay citas programadas.</p>';
					}
					echo '</div>';

					echo '</div>'; // modal-body
					echo '</div>'; // modal-content
					echo '</div>'; // modal-overlay
				}
				echo '</div>'; // grid
			}

			if ($conn instanceof mysqli) {
				$conn->close();
			}
			?>
		</div>
	</main>
	<footer>
		<p>&copy; 2025 CRM Inmobiliaria. Todos los derechos reservados.</p>
	</footer>
	<script>
		var imageIndexes = {};

		function changeImage(propId, direction) {
			var gallery = document.querySelector('.image-gallery[data-prop="' + propId + '"]');
			if (!gallery) return;
			var imagesAttr = gallery.getAttribute('data-images');
			if (!imagesAttr) return;
			var images = JSON.parse(imagesAttr);
			if (!images.length) return;

			if (typeof imageIndexes[propId] === 'undefined') {
				imageIndexes[propId] = 0;
			}

			imageIndexes[propId] = (imageIndexes[propId] + direction + images.length) % images.length;

			var imgEl = document.getElementById('img-' + propId);
			if (imgEl) {
				imgEl.src = '../' + images[imageIndexes[propId]];
			}
		}

		function openModal(modalId, propId) {
			var modal = document.getElementById(modalId);
			if (!modal) return;
			modal.classList.add('active');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('modal-open');

			var gallery = modal.querySelector('.image-gallery[data-prop="' + propId + '"]');
			if (gallery) {
				var imagesAttr = gallery.getAttribute('data-images');
				if (imagesAttr) {
					var images = JSON.parse(imagesAttr);
					if (images.length) {
						imageIndexes[propId] = 0;
						var imgEl = document.getElementById('img-' + propId);
						if (imgEl) {
							imgEl.src = '../' + images[0];
						}
					}
				}
			}
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
					openModal(this.dataset.target, this.dataset.prop);
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
		});
	</script>
</body>

</html>