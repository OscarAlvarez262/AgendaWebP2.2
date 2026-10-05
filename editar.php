<?php

require_once 'conexion.php';

$errores = [];

$id = 0;
$titulo = '';
$fechaHora = '';
$categoria = '';
$prioridad = '';
$descripcion = '';

const CATEGORIAS_EDITAR = ['trabajo', 'personal', 'estudio', 'ocio'];
const PRIORIDADES_EDITAR = ['baja', 'media', 'alta'];

// ------------------------------------------------------------------
// GET: cargar el evento en el formulario
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        header('Location: index.php?error=1');
        exit;
    }

    try {
        $stmt = $mysqli->prepare(
            'SELECT titulo, fecha, hora, categoria, prioridad, descripcion
               FROM eventos
              WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $evento = $stmt->get_result()->fetch_assoc();

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('AgendaWeb SELECT editar: ' . $e->getMessage());

        header('Location: index.php?error=1');
        exit;
    }

    if (!$evento) {
        $mysqli->close();

        header('Location: index.php?error=1');
        exit;
    }

    $titulo      = $evento['titulo'];
    $categoria   = $evento['categoria'];
    $prioridad   = $evento['prioridad'];
    $descripcion = $evento['descripcion'] ?? '';

    $fechaHora = $evento['fecha'];
    if (!empty($evento['hora'])) {
        $fechaHora .= 'T' . substr($evento['hora'], 0, 5);
    }

    $mysqli->close();
}

// ------------------------------------------------------------------
// POST: validar y actualizar
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recibir datos
    $id          = (int) ($_POST['id'] ?? 0);
    $titulo      = trim($_POST['titulo'] ?? '');
    $fechaHora   = trim($_POST['fecha'] ?? '');
    $categoria   = trim($_POST['categoria'] ?? '');
    $prioridad   = trim($_POST['prioridad'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Separar fecha y hora
    $fecha = '';
    $hora = '';

    if ($fechaHora !== '') {
        $partes = explode('T', $fechaHora);

        $fecha = $partes[0] ?? '';
        $hora = $partes[1] ?? '';
    }

    // Validación
    if ($id <= 0) {
        $errores['general'] = 'El evento a editar no es válido.';
    }

    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'Máximo 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
        $avisos = DateTime::getLastErrors();

        if (
            $fechaObj === false
            || (is_array($avisos) && $avisos['warning_count'] > 0)
            || $fechaObj->format('Y-m-d') !== $fecha
        ) {
            $errores['fecha'] = 'La fecha no es válida.';
        }
    }

    if (!in_array($categoria, CATEGORIAS_EDITAR, true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }

    if (!in_array($prioridad, PRIORIDADES_EDITAR, true)) {
        $errores['prioridad'] = 'Elige una prioridad válida.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'Máximo 500 caracteres.';
    }

    // Actualizar si no hay errores
    if (empty($errores)) {

        try {
            $stmt = $mysqli->prepare(
                'UPDATE eventos
                    SET titulo = ?,
                        fecha = ?,
                        hora = ?,
                        categoria = ?,
                        prioridad = ?,
                        descripcion = ?
                  WHERE id = ?'
            );

            $stmt->bind_param(
                'ssssssi',
                $titulo,
                $fecha,
                $hora,
                $categoria,
                $prioridad,
                $descripcion,
                $id
            );

            $stmt->execute();

            $stmt->close();
            $mysqli->close();

            // PRG
            header('Location: index.php?editado=1');
            exit;

        } catch (mysqli_sql_exception $e) {
            error_log('AgendaWeb UPDATE: ' . $e->getMessage());

            $errores['general'] = 'No se pudo actualizar el evento. Intenta de nuevo.';
        }
    }

    $mysqli->close();
}

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

    <title>AgendaWeb - Editar evento</title>
</head>

<body>

    <form method="post" action="" class="tarjeta">

        <h1>Agenda<span>Web</span></h1>

        <p class="subtitulo">
            Editar evento
        </p>

        <input
            type="hidden"
            name="id"
            value="<?= $id ?>"
        >

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

        <!-- Título -->
        <input
            type="text"
            name="titulo"
            placeholder="Título"
            value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
            <?= isset($errores['titulo']) ? 'class="is-error"' : '' ?>
            required
        >

        <?php if (isset($errores['titulo'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['titulo'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Fecha y hora -->
        <input
            type="datetime-local"
            name="fecha"
            value="<?= htmlspecialchars($fechaHora, ENT_QUOTES, 'UTF-8') ?>"
            <?= isset($errores['fecha']) ? 'class="is-error"' : '' ?>
            required
        >

        <?php if (isset($errores['fecha'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['fecha'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Categoría y prioridad -->
        <div class="fila-opciones">

            <select name="categoria" <?= isset($errores['categoria']) ? 'class="is-error" required' : 'required' ?>>
                <option value="" disabled <?= $categoria === '' ? 'selected' : '' ?>>
                    Categoría
                </option>

                <?php foreach (['personal' => 'Personal', 'trabajo' => 'Trabajo', 'estudio' => 'Estudio', 'ocio' => 'Ocio'] as $valor => $texto): ?>
                    <option
                        value="<?= $valor ?>"
                        <?= $categoria === $valor ? 'selected' : '' ?>
                    >
                        <?= $texto ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="prioridad" <?= isset($errores['prioridad']) ? 'class="is-error" required' : 'required' ?>>
                <option value="" disabled <?= $prioridad === '' ? 'selected' : '' ?>>
                    Prioridad
                </option>

                <?php foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $valor => $texto): ?>
                    <option
                        value="<?= $valor ?>"
                        <?= $prioridad === $valor ? 'selected' : '' ?>
                    >
                        <?= $texto ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>

        <?php if (isset($errores['categoria'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['categoria'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <?php if (isset($errores['prioridad'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['prioridad'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Descripción -->
        <textarea
            name="descripcion"
            placeholder="Descripción del evento"
            <?= isset($errores['descripcion']) ? 'class="is-error"' : '' ?>
        ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>

        <?php if (isset($errores['descripcion'])): ?>
            <span class="campo-error">
                <?= htmlspecialchars($errores['descripcion'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="acciones-formulario">

            <a href="index.php" class="boton-cancelar">
                Cancelar
            </a>

            <button type="submit">
                Guardar cambios
            </button>

        </div>

    </form>

</body>
</html>