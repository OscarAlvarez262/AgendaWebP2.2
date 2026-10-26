<?php
session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
require __DIR__ . '/partes/base_datos.php';

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    // 1 · Buscar al usuario por su correo
    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();   // null si no existe
    $stmt->close();

    // 2 · ¿Existe y la contraseña coincide con su hash?
    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true);                  // id de sesión nuevo al entrar
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];

        // Si es el primer usuario, hereda los eventos de P7 (sin dueño).
        reclamarEventosHuerfanos($mysqli, (int) $usuario['id']);

        header('Location: index.php');
        exit;
    }

    // 3 · Mismo mensaje en los dos casos: no revelamos si el correo existe
    $error = 'Correo o contraseña incorrectos.';
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos -->
    <link rel="stylesheet" href="css/estilos.css">

    <title>AgendaWeb - Iniciar sesión</title>
</head>

<body>

    <form method="post" action="" class="tarjeta">

        <h1>Agenda<span>Web</span></h1>

        <p class="subtitulo">
            Inicia sesión
        </p>

        <?php if (isset($_GET['registrado'])): ?>
            <div class="alert alert--ok" role="status">
                Cuenta creada. Ya puedes iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['salio'])): ?>
            <div class="alert alert--ok" role="status">
                Cerraste sesión. ¡Hasta pronto!
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert--error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Correo -->
        <input
            type="email"
            name="correo"
            placeholder="Correo"
            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email"
            required
        >

        <!-- Contraseña: nunca se escribe de vuelta en el HTML -->
        <input
            type="password"
            name="clave"
            placeholder="Contraseña"
            autocomplete="current-password"
            required
        >

        <!-- Acciones -->
        <div class="acciones-formulario">

            <a href="registro.php" class="boton-cancelar">
                Crear cuenta
            </a>

            <button type="submit">
                Entrar
            </button>

        </div>

    </form>

</body>
</html>
