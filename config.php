<?php
// Configuración de base de datos (ajústala según tu entorno)
// Nota: Revisa en HeidiSQL el nombre exacto de la base y el puerto.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Jere060904');
// Si tu base creada se llama distinto (por ejemplo crm_inmobiliaria), cámbiala aquí
define('DB_NAME', 'crm-inmobiliaria');
// MariaDB/MySQL por defecto: 3306. En algunos paquetes (XAMPP) podría ser 3307
define('DB_PORT', 8001);

// Opcional: forzar charset
define('DB_CHARSET', 'utf8mb4');
