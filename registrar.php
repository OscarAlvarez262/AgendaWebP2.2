<?php

$errores = [];

$titulo = '';
$fechaHora = '';
$categoria = '';
$descripcion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recibir datos
    $titulo = trim($_POST['titulo'] ?? '');
    $fechaHora = trim($_POST['fecha'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
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
    $categoriasOK = [
        'trabajo',
        'personal',
        'estudio',
        'ocio'
    ];

    if ($titulo === '') {
    $errores['titulo'] = 'El título es obligatorio.';
    } elseif (strlen($titulo) > 120) {
    $errores['titulo'] = 'Máximo 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } elseif (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        $errores['fecha'] = 'La fecha no es válida.';
    }

    if (!in_array($categoria, $categoriasOK, true)) {
        $errores['categoria'] = 'Elige una categoría válida.';
    }

    // Guardar si no hay errores
    if (empty($errores)) {

        require 'conexion.php';

        $sql = "INSERT INTO eventos
                (titulo, fecha, hora, categoria, descripcion)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $mysqli->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $titulo,
            $fecha,
            $hora,
            $categoria,
            $descripcion
        );

        $stmt->execute();

        $stmt->close();
        $mysqli->close();

        // PRG
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

        <!-- Título -->
        <input 
            type="text" 
            name="titulo"
            placeholder="Título"
            value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <!-- Fecha y hora -->
        <input 
            type="datetime-local" 
            name="fecha"
            required
        >

        <!-- Categoría y prioridad -->
        <div class="fila-opciones">

            <select name="categoria" required>
                <option value="" disabled selected>
                    Categoría
                </option>

                <option value="Personal">
                    Personal
                </option>

                <option value="Trabajo">
                    Trabajo
                </option>

                <option value="Estudio">
                    Estudio
                </option>

                <option value="Ocio">
                    Ocio
                </option>
            </select>

            <select name="prioridad" required>
                <option value="" disabled selected>
                    Prioridad
                </option>

                <option value="Baja">
                    Baja
                </option>

                <option value="Media">
                    Media
                </option>

                <option value="Alta">
                    Alta
                </option>
            </select>

        </div>

        <!-- Descripción -->
        <textarea 
            name="descripcion"
            placeholder="Descripción del evento"
        ><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>

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
