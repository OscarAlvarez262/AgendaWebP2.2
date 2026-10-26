<?php
session_start();
if (isset($_SESSION['uid'])) {          // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}
require __DIR__ . '/partes/base_datos.php';

$nombre = $correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));   // Ana@x.com = ana@x.com
    $clave  = $_POST['clave']  ?? '';                          // la contraseña NO se recorta
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);   // se guarda el HASH, nunca la clave
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();

            // Si es el primer usuario, hereda los eventos de P7 (sin dueño).
            reclamarEventosHuerfanos($mysqli, (int) $mysqli->insert_id);

            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {               // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
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

    <title>AgendaWeb - Crear cuenta</title>
</head>

<body>

    <form method="post" action="" class="tarjeta">

        <h1>Agenda<span>Web</span></h1>

        <p class="subtitulo">
            Crea tu cuenta
        </p>

        <?php if (isset($errores['general'])): ?>
            <div class="alert alert--error" role="alert">
                <?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert--error" role="alert">
                Revisa los campos marcados:
                <ul>
                    <?php foreach ($errores as $mensaje): ?>
                        <li><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Nombre -->
        <input
            type="text"
            name="nombre"
            placeholder="Nombre"
            maxlength="80"
            value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
            <?= isset($errores['nombre']) ? 'class="is-error"' : '' ?>
            autocomplete="name"
            required
        >

        <?php if (isset($errores['nombre'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Correo -->
        <input
            type="email"
            name="correo"
            placeholder="Correo"
            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>"
            <?= isset($errores['correo']) ? 'class="is-error"' : '' ?>
            autocomplete="email"
            required
        >

        <?php if (isset($errores['correo'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['correo'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Contraseña: nunca se escribe de vuelta en el HTML -->
        <input
            type="password"
            name="clave"
            placeholder="Contraseña"
            minlength="8"
            <?= isset($errores['clave']) ? 'class="is-error"' : '' ?>
            autocomplete="new-password"
            required
        >

        <?php if (isset($errores['clave'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['clave'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <input
            type="password"
            name="clave2"
            placeholder="Repite la contraseña"
            minlength="8"
            <?= isset($errores['clave2']) ? 'class="is-error"' : '' ?>
            autocomplete="new-password"
            required
        >

        <?php if (isset($errores['clave2'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['clave2'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="acciones-formulario">

            <a href="login.php" class="boton-cancelar">
                Ya tengo cuenta
            </a>

            <button type="submit">
                Crear cuenta
            </button>

        </div>

    </form>

</body>
</html>
