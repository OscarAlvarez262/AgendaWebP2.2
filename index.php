<?php
require 'conexion.php';

$sql = "SELECT id, titulo, fecha, hora, categoria, prioridad, descripcion
        FROM eventos
        ORDER BY fecha DESC, hora IS NULL ASC, hora DESC";

$resultado = $mysqli->query($sql);
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

        <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
            <div class="alert alert--ok" role="status">
                ✅ Evento guardado correctamente.
            </div>
        <?php endif; ?>

        <div class="page__header">
            <div>
                <h1 class="page__title">Mis eventos</h1>
                <p class="page__subtitle">
                    <?= $resultado->num_rows ?>
                    eventos registrados
                </p>
            </div>

            <a href="registrar.php" class="btn-primary">
                + Nuevo evento
            </a>
        </div>

        <?php if ($resultado->num_rows > 0): ?>

            <section class="card-list">

                <?php while ($evento = $resultado->fetch_assoc()): ?>

                    <article class="card">

                        <div class="card__top">

                            <span class="card__badge">
                                <?= htmlspecialchars(
                                    ucfirst($evento['categoria']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <span class="prioridad prioridad--<?= htmlspecialchars(
                                $evento['prioridad'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                                <?= htmlspecialchars(
                                    ucfirst($evento['prioridad']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </div>

                        <h2 class="card__title">
                            <?= htmlspecialchars(
                                $evento['titulo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <p class="card__meta">
                            <time>
                                <?= htmlspecialchars($evento['fecha']) ?>

                                <?php if (!empty($evento['hora'])): ?>
                                    · <?= htmlspecialchars(substr($evento['hora'], 0, 5)) ?>
                                <?php endif; ?>
                            </time>
                        </p>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <p class="card__text">
                                <?= nl2br(htmlspecialchars(
                                    $evento['descripcion'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )) ?>
                            </p>
                        <?php endif; ?>

                    </article>

                <?php endwhile; ?>

            </section>

        <?php else: ?>

            <div class="empty-state">
                <p>Aún no tienes eventos registrados.</p>
                <a href="registrar.php" class="btn-primary">
                    Registrar el primero
                </a>
            </div>

        <?php endif; ?>

    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Oscar Álvarez · 2026
        </div>
    </footer>

</body>
</html>

<?php
$resultado->free();
$mysqli->close();
?>