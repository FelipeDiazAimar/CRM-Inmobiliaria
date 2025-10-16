<?php
header('Content-Type: text/plain; charset=utf-8');

// Parámetros por GET (con valores por defecto)
$host = $_GET['host'] ?? '127.0.0.1';
$user = $_GET['user'] ?? 'root';
$pass = $_GET['pass'] ?? '';
$db   = $_GET['db']   ?? null; // si es null, primero probamos sin DB
$port = intval($_GET['port'] ?? 8001); // XAMPP a veces usa 3307

echo "host=$host port=$port user=$user db=".($db ?? '(none)')."\n";

// 1) Probar conexión al servidor sin seleccionar DB
try {
    $mysqli = @new mysqli($host, $user, $pass, '', $port);
    if ($mysqli->connect_errno) {
        throw new Exception('mysqli connect error: ' . $mysqli->connect_error);
    }
    echo "server OK (mysqli)\n";
    // listar algunas bases (si el usuario tiene permiso)
    if ($result = $mysqli->query('SHOW DATABASES')) {
        $count = 0;
        echo "databases: ";
        while ($row = $result->fetch_row()) {
            echo $row[0] . ' ';
            if (++$count >= 10) break; // limitar salida
        }
        echo "\n";
    }
    $mysqli->close();
} catch (Throwable $e) {
    echo "server FAIL (mysqli): ".$e->getMessage()."\n";
}

// 2) Si se proporcionó db, probar con esa base
if ($db !== null && $db !== '') {
    try {
        $mysqli = @new mysqli($host, $user, $pass, $db, $port);
        if ($mysqli->connect_errno) {
            throw new Exception('mysqli connect error: ' . $mysqli->connect_error);
        }
        $res = $mysqli->query('SELECT 1 as ok');
        $row = $res ? $res->fetch_assoc() : null;
        echo "mysqli DB OK: ".json_encode($row)."\n";
        $mysqli->close();
    } catch (Throwable $e) {
        echo "mysqli DB FAIL: ".$e->getMessage()."\n";
    }

    // Opción B: PDO MySQL
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $val = $pdo->query('SELECT 1 as ok')->fetch();
        echo "pdo_mysql DB OK: ".json_encode($val)."\n";
    } catch (Throwable $e) {
        echo "pdo_mysql DB FAIL: ".$e->getMessage()."\n";
    }
}
