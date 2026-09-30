<?php

$errores = [];

$titulo = '';
$fechaHora = '';
$categoria = '';
$prioridad = '';
$descripcion = '';

const CATEGORIAS = ['trabajo', 'personal', 'estudio', 'ocio'];
const PRIORIDADES = ['baja', 'media', 'alta'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recibir datos
    $titulo = trim($_POST['titulo'] ?? '');
    $fechaHora = trim($_POST['fecha'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $prioridad = trim($_POST['prioridad'] ?? '');
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

        // getLastErrors() devuelve un array en PHP 7.x y false en 8.2+.
        // Comprobar solo warning_count mantiene la validación correcta en ambas.
        if (
            $fechaObj === false
            || (is_array($avisos) && $avisos['warning_count'] > 0)
            || $fechaObj->format('Y-m-d') !== $fecha
        ) {
            $errores['fecha'] = 'La fecha no es válida.';
        }
    }

    if (!in_array($categoria, CATEGORIAS, true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }

    if (!in_array($prioridad, PRIORIDADES, true)) {
        $errores['prioridad'] = 'Elige una prioridad válida.';
    }

    if (mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'Máximo 500 caracteres.';
    }

    // Guardar si no hay errores
    if (empty($errores)) {

        require_once 'conexion.php';

        try {
            $sql = "INSERT INTO eventos
                    (titulo, fecha, hora, categoria, prioridad, descripcion)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $mysqli->prepare($sql);

            $stmt->bind_param(
                'ssssss',
                $titulo,
                $fecha,
                $hora,
                $categoria,
                $prioridad,
                $descripcion
            );

            $stmt->execute();

            $stmt->close();
            $mysqli->close();
        } catch (mysqli_sql_exception $e) {
            error_log('AgendaWeb INSERT: ' . $e->getMessage());

            $errores['general'] = 'No se pudo guardar el evento. Intenta de nuevo.';
        }
    }

    // PRG
    if (empty($errores)) {
        header('Location: index.php?ok=1');
        exit;
    }
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

    <title>AgendaWeb</title>
</head>

<body>

    <form method="post" action="" class="tarjeta">

        <h1>Agenda<span>Web</span></h1>

        <p class="subtitulo">
            Registra un nuevo evento
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
                Agregar evento
            </button>

        </div>

    </form>

</body>
</html>
