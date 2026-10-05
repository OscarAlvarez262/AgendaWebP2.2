<?php

// Limpia un texto antes de mostrarlo (evita XSS)
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// '2026-10-08'  →  '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

// 'ocio'  →  'Ocio / Deporte'
function nombreCategoria(string $clave): string
{
    $nombres = [
        'trabajo'  => 'Trabajo',
        'personal' => 'Personal',
        'estudio'  => 'Estudio',
        'ocio'     => 'Ocio / Deporte',
    ];
    return $nombres[$clave] ?? $clave;
}

// Devuelve el HTML de la tarjeta de un evento
function mostrarEvento(array $ev): string
{
    $html  = '<article class="card">';
    $html .= '<div class="card__top">';
    $html .= '<span class="card__badge">' . e(nombreCategoria($ev['categoria'])) . '</span>';

    if (!empty($ev['prioridad'])) {
        $prioridad = $ev['prioridad'];
        $html .= '<span class="prioridad prioridad--' . e($prioridad) . '">'
               . e(ucfirst($prioridad)) . '</span>';
    }

    $html .= '</div>';
    $html .= '<h2 class="card__title">' . e($ev['titulo']) . '</h2>';

    $cuando = formatearFecha($ev['fecha']);
    if (!empty($ev['hora'])) {
        $cuando .= ' · ' . substr($ev['hora'], 0, 5);
    }
    $html .= '<p class="card__meta"><time datetime="' . e($ev['fecha']) . '">'
           . e($cuando) . '</time></p>';

    if (!empty($ev['descripcion'])) {
        $html .= '<p class="card__text">' . nl2br(e($ev['descripcion'])) . '</p>';
    }

    $id = (int) $ev['id'];
    $html .= '<div class="card__actions">'
           . '<a href="editar.php?id=' . $id . '" class="btn-secondary btn-sm">Editar</a>'
           . '<form method="post" action="borrar.php" class="form-inline">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
           . '</form></div>';

    return $html . '</article>';
}

require 'conexion.php';

$resultado = $mysqli->query(
    'SELECT id, titulo, fecha, hora, categoria, prioridad, descripcion
       FROM eventos
      ORDER BY fecha DESC, hora IS NULL ASC, hora DESC'
);

$eventos = $resultado->fetch_all(MYSQLI_ASSOC);

$resultado->free();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css">
    <title>AgendaWeb - Mis eventos</title>
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">
                Agenda<span>Web</span>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav__link is-active">
                    Mis eventos
                </a>
                <a href="registrar.php" class="nav__link">
                    Nuevo evento
                </a>
            </nav>
        </div>
    </header>

    <main class="contenedor">

        <?php if (($_GET['ok'] ?? '') === '1'): ?>
            <div class="alert alert--ok" role="status">
                Evento guardado correctamente.
            </div>
        <?php endif; ?>

        <?php if (($_GET['editado'] ?? '') === '1'): ?>
            <div class="alert alert--ok" role="status">
                Evento actualizado correctamente.
            </div>
        <?php endif; ?>

        <?php if (($_GET['borrado'] ?? '') === '1'): ?>
            <div class="alert alert--ok" role="status">
                Evento borrado correctamente.
            </div>
        <?php endif; ?>

        <?php if (($_GET['error'] ?? '') === '1'): ?>
            <div class="alert alert--error" role="alert">
                No se pudo completar la operación.
            </div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <p class="page__subtitle">
                    <?= count($eventos) ?> eventos registrados
                </p>
            </div>

            <a href="registrar.php" class="btn-primary">
                + Nuevo evento
            </a>
        </div>

        <?php if (empty($eventos)): ?>

            <div class="empty-state">
                <p>Aún no tienes eventos registrados.</p>
                <a href="registrar.php" class="btn-primary">
                    Registrar el primero
                </a>
            </div>

        <?php else: ?>

            <section class="card-list" aria-label="Lista de eventos">
                <?php foreach ($eventos as $ev): ?>
                    <?= mostrarEvento($ev) ?>
                <?php endforeach; ?>
            </section>

        <?php endif; ?>

    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Oscar Álvarez · 2026
        </div>
    </footer>

</body>
</html>