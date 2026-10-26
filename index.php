<?php

// Guardia: sin sesión no se entra (y de aquí salen $usuarioId y $usuarioNombre)
require __DIR__ . '/partes/sesion.php';

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

// Devuelve el HTML de la tarjeta de un evento
function mostrarEvento(array $ev): string
{
    $html  = '<article class="card">';
    $html .= '<div class="card__top">';
    $html .= '<span class="card__badge">' . e($ev['categoria']) . '</span>';

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
           . '<form method="post" action="borrar.php" class="form-inline"'
           . ' onsubmit="return confirm(\'¿Borrar este evento? No se puede deshacer.\')">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="btn-danger btn-sm">Borrar</button>'
           . '</form></div>';

    return $html . '</article>';
}

require __DIR__ . '/partes/base_datos.php';

// e y c son apodos (alias); ON dice cómo se relacionan las dos tablas.
// AS categoria deja la clave igual que siempre usó mostrarEvento().
// El WHERE limita la lista a los eventos del usuario con sesión.
$stmt = $mysqli->prepare(
    'SELECT e.id, e.titulo, e.fecha, e.hora, e.prioridad, e.descripcion,
            c.nombre AS categoria
       FROM eventos e
       JOIN categorias c ON c.id = e.categoria_id
      WHERE e.usuario_id = ?
      ORDER BY e.fecha DESC, e.hora IS NULL ASC, e.hora DESC'
);

$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Resumen por categoría: LEFT JOIN trae también las que no tienen
// eventos, y en ese caso COUNT(e.id) sale con 0.
// Ojo: el filtro del usuario va en el ON, no en un WHERE; si fuera
// WHERE, las categorías sin eventos tuyos desaparecerían del resumen.
$stmt = $mysqli->prepare(
    'SELECT c.nombre, COUNT(e.id) AS total
       FROM categorias c
       LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
      GROUP BY c.id, c.nombre
      ORDER BY c.nombre'
);

$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
                <span class="nav__link">
                    Hola, <?= e($usuarioNombre) ?>
                </span>
                <a href="logout.php" class="nav__link">
                    Salir
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
                Ese evento no existe (quizá ya fue borrado).
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

        <p class="resumen">
            <?php foreach ($resumen as $r): ?>
                <span class="card__badge">
                    <?= e($r['nombre']) ?> · <?= (int) $r['total'] ?>
                </span>
            <?php endforeach; ?>
        </p>

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