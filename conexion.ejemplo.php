<?php

/*
 |--------------------------------------------------------------
 | Plantilla de conexión. Esta versión NO contiene contraseñas.
 | En producción (DomCloud) el servidor inyecta las variables
 | MARIADB_HOST, MARIADB_USER, MARIADB_PASS y MARIADB_NAME.
 |
 | El deploy script genera conexion.php a partir de este archivo:
 |     cp conexion.ejemplo.php conexion.php
 |
 | Para desarrollo local, copia este archivo como conexion.php y
 | rellena el fallback de 'pass'.
 |--------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$credenciales = [
    'host' => getenv('MARIADB_HOST') ?: 'localhost',
    'user' => getenv('MARIADB_USER') ?: 'root',
    'pass' => getenv('MARIADB_PASS') ?: '',
    'db'   => getenv('MARIADB_NAME') ?: 'agenda',
];

try {
    $mysqli = new mysqli(
        $credenciales['host'],
        $credenciales['user'],
        $credenciales['pass'],
        $credenciales['db']
    );

    $mysqli->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('Conexion a la BD: ' . $e->getMessage());

    die('No se pudo conectar a la base de datos.');
}
