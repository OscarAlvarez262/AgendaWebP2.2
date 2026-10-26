<?php

// Guardia de las páginas privadas: una sola copia protege a todas.
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// Quién está usando la app
$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';
