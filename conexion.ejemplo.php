<?php

/*
 |--------------------------------------------------------------
 | Plantilla de conexión. Esta versión NO contiene contraseñas.
 |
 | En producción (DomCloud) el servidor debería inyectar las
 | variables MARIADB_HOST, MARIADB_USER, MARIADB_PASS y
 | MARIADB_NAME. Si no las inyecta, hay que rellenar los valores
 | fijos de abajo (el archivo instalar.php los escribe por ti).
 |
 | El deploy script genera conexion.php a partir de este archivo:
 |     cp conexion.ejemplo.php conexion.php
 |
 | Para desarrollo local, copia este archivo como conexion.php y
 | rellena los valores fijos.
 |--------------------------------------------------------------
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('MARIADB_HOST') ?: 'localhost';
$usuario = getenv('MARIADB_USER') ?: '';
$clave = getenv('MARIADB_PASS') ?: '';
$base = getenv('MARIADB_NAME') ?: '';

// Sin fallbacks tipo 'root' / '' / 'agenda': DomCloud nunca usa root
// con contraseña vacía, así que un valor por defecto equivocado solo
// produciría un error de conexión confuso.
$faltan = [];

if ($usuario === '') {
    $faltan[] = 'MARIADB_USER';
}

if ($clave === '') {
    $faltan[] = 'MARIADB_PASS';
}

if ($base === '') {
    $faltan[] = 'MARIADB_NAME';
}

if (!empty($faltan)) {
    die(
        'conexion.php incompleto: faltan ' . implode(', ', $faltan) . '. '
        . 'Definelas en el servidor o rellena los valores fijos de conexion.php.'
    );
}

try {
    $mysqli = new mysqli($host, $usuario, $clave, $base);

    $mysqli->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('Conexion a la BD: ' . $e->getMessage());

    die('No se pudo conectar a la base de datos.');
}
