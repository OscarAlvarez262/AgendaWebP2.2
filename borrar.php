<?php

// Borrar un evento. Solo por POST: nunca por GET, para que
// un enlace suelto en el navegador no borre nada por accidente.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=1');
    exit;
}

require_once 'conexion.php';

try {
    $stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $stmt->close();
    $mysqli->close();

    // PRG
    header('Location: index.php?borrado=1');
    exit;

} catch (mysqli_sql_exception $e) {
    error_log('AgendaWeb DELETE: ' . $e->getMessage());

    $mysqli->close();

    header('Location: index.php?error=1');
    exit;
}