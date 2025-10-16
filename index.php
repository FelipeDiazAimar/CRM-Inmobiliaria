<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Inicio index.php\n";

// Validar que la extensión mysqli esté cargada
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    die("Error: la extensión mysqli no está habilitada en PHP\n");
}

// Evitar que mysqli lance excepciones no capturadas
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Conectar a la base de datos con manejo de errores claro
$conexion = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conexion->connect_errno) {
    http_response_code(500);
    die(
        "Error de conexión (" . $conexion->connect_errno . "): " . $conexion->connect_error . "\n" .
        "Host: " . DB_HOST . ":" . DB_PORT . "\n" .
        "Base: " . DB_NAME . "\n"
    );
}

// Ajustar charset si está definido
if (defined('DB_CHARSET')) {
    $conexion->set_charset(DB_CHARSET);
}

echo "✅ Conexión exitosa con MariaDB\n";

// Pequeña consulta de prueba
$res = $conexion->query('SELECT 1 AS ok');
if ($res) {
    $row = $res->fetch_assoc();
    echo "SELECT prueba: " . json_encode($row) . "\n";
}

$conexion->close();
?>
