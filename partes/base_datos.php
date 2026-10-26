<?php

/*
 |--------------------------------------------------------------
 | Punto único de conexión + migración automática.
 |
 | Las páginas incluyen este archivo en lugar de conexion.php.
 | Así P8 se aplica sola en el primer request del deploy, aunque
 | conexion.php venga generado por DomCloud.
 |--------------------------------------------------------------
 */

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/esquema.php';

asegurarEsquemaP8($mysqli);
