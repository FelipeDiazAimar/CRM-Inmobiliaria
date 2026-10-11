<?php
// Cargar variables de entorno desde .env
function loadEnv($path)
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Buscar el primer .env disponible subiendo niveles para soportar ejecuciones desde /pages
$candidatePaths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
    dirname(__DIR__, 2) . '/.env',
];
foreach ($candidatePaths as $envPath) {
    if (file_exists($envPath)) {
        loadEnv($envPath);
        break;
    }
}

// Configuración de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'crm-inmobiliaria');
define('DB_PORT', getenv('DB_PORT') ?: 8001);
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ============================================================
// MODO DEMO (AGENTE C-1) — funciona sin MySQL, no toca modo real
// Contrato: demo_activo(), db_conectar() lazy nullable, db_o_demo()
// ============================================================

if (!function_exists('demo_activo')) {
    /**
     * true por defecto; solo false si DEMO_MODE=false (insensible a mayúsculas).
     * También acepta 0/no/off como desactivadores por robustez.
     */
    function demo_activo(): bool
    {
        $v = getenv('DEMO_MODE');
        if ($v === false) {
            return true; // sin .env ni env: demo activo (no hay MySQL local)
        }
        $v = strtolower(trim((string)$v));
        if (in_array($v, ['false', '0', 'no', 'off'], true)) {
            return false;
        }
        return true;
    }
}

if (!function_exists('db_conectar')) {
    /**
     * Conexión lazy mysqli. Devuelve null (NO lanza) si MySQL no responde,
     * si falta la extensión mysqli o si hay cualquier error.
     * Uso: $db = db_conectar(); if ($db === null) { // usar demo_datos() }
     */
    function db_conectar(): ?mysqli
    {
        static $conn = null;
        static $intentado = false;
        if ($intentado) {
            return $conn;
        }
        $intentado = true;
        try {
            if (!extension_loaded('mysqli')) {
                return null;
            }
            if (function_exists('mysqli_report')) {
                mysqli_report(MYSQLI_REPORT_OFF);
            }
            $host = defined('DB_HOST') ? (string)DB_HOST : (string)(getenv('DB_HOST') ?: 'localhost');
            $user = defined('DB_USER') ? (string)DB_USER : (string)(getenv('DB_USER') ?: 'root');
            $pass = defined('DB_PASS') ? (string)DB_PASS : (string)(getenv('DB_PASS') ?: '');
            $name = defined('DB_NAME') ? (string)DB_NAME : (string)(getenv('DB_NAME') ?: 'crm-inmobiliaria');
            $port = defined('DB_PORT') ? (int)DB_PORT : (int)(getenv('DB_PORT') ?: 3306);
            if ($port <= 0) {
                $port = 3306;
            }
            $c = @new mysqli($host, $user, $pass, $name, $port);
            if ($c instanceof mysqli && $c->connect_errno) {
                return null;
            }
            if ($c instanceof mysqli && defined('DB_CHARSET') && DB_CHARSET !== '') {
                @$c->set_charset((string)DB_CHARSET);
            }
            $conn = $c instanceof mysqli ? $c : null;
            return $conn;
        } catch (Throwable $e) {
            $conn = null;
            return null;
        }
    }
}

if (!function_exists('db_pdo')) {
    /**
     * Conexión lazy PDO (opcional). Devuelve null (NO lanza) si MySQL no responde.
     */
    function db_pdo(): ?PDO
    {
        static $pdo = null;
        static $intentado = false;
        if ($intentado) {
            return $pdo;
        }
        $intentado = true;
        try {
            if (!extension_loaded('pdo_mysql')) {
                return null;
            }
            $host = defined('DB_HOST') ? (string)DB_HOST : (string)(getenv('DB_HOST') ?: 'localhost');
            $name = defined('DB_NAME') ? (string)DB_NAME : (string)(getenv('DB_NAME') ?: 'crm-inmobiliaria');
            $port = defined('DB_PORT') ? (int)DB_PORT : (int)(getenv('DB_PORT') ?: 3306);
            $user = defined('DB_USER') ? (string)DB_USER : (string)(getenv('DB_USER') ?: 'root');
            $pass = defined('DB_PASS') ? (string)DB_PASS : (string)(getenv('DB_PASS') ?: '');
            $cs = defined('DB_CHARSET') ? (string)DB_CHARSET : 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$cs}";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2,
            ]);
            return $pdo;
        } catch (Throwable $e) {
            $pdo = null;
            return null;
        }
    }
}

if (!function_exists('db_o_demo')) {
    /**
     * true si hay que usar datos demo: demo_activo() o MySQL inalcanzable.
     * En modo real (DEMO_MODE=false + MySQL OK) devuelve false.
     */
    function db_o_demo(): bool
    {
        if (demo_activo()) {
            return true;
        }
        return db_conectar() === null;
    }
}

// Hace disponibles demo_datos()/demo_guardar()/demo_escribir() en todo require de config.
if (file_exists(__DIR__ . '/demo_data.php')) {
    require_once __DIR__ . '/demo_data.php';
}
