<?php

// Borrar un evento. Solo por POST: nunca por GET, para que
// un enlace suelto en el navegador no borre nada por accidente.
require __DIR__ . '/partes/sesion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// El id SIEMPRE como número: "3 OR 1=1" se vuelve 3
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?error=1');
    exit;
}

require_once __DIR__ . '/partes/base_datos.php';

try {
    // El segundo ? es la autorización: solo borra si el evento es tuyo.
    $stmt = $mysqli->prepare(
        'DELETE FROM eventos WHERE id = ? AND usuario_id = ?'
    );
    $stmt->bind_param('ii', $id, $usuarioId);
    $stmt->execute();

    $borrados = $stmt->affected_rows;   // cuántas filas borró: 1 o 0

    $stmt->close();
    $mysqli->close();

    // PRG: solo decimos "borrado" si de verdad borró una fila
    if ($borrados === 1) {
        header('Location: index.php?borrado=1');
    } else {
        header('Location: index.php?error=1');
    }
    exit;

} catch (mysqli_sql_exception $e) {
    error_log('AgendaWeb DELETE: ' . $e->getMessage());

    $mysqli->close();

    header('Location: index.php?error=1');
    exit;
}
