<?php

require __DIR__ . '/partes/sesion.php';   // página: sin sesión → login

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// Las categorías del filtro: son pocas y no cambian, las pone PHP
require __DIR__ . '/conexion.php';
$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre')
                     ->fetch_all(MYSQLI_ASSOC);
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilos.css">
    <title>AgendaWeb - Explorar</title>
</head>
<body class="layout">

    <header class="site-header">
        <div class="contenedor site-header__inner">
            <a href="index.php" class="logo">
                Agenda<span>Web</span>
            </a>

            <nav class="nav">
                <a href="index.php" class="nav__link">
                    Mis eventos
                </a>
                <a href="explorar.php" class="nav__link is-active" aria-current="page">
                    Explorar
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
        <div class="page__header">
            <div>
                <h1 class="page__title">Explorar</h1>
                <p class="page__subtitle" id="estado" role="status">Cargando eventos…</p>
            </div>
        </div>

        <form class="buscador" id="filtros" role="search">
            <input class="form-input" type="search" id="q" maxlength="60"
                   placeholder="Escribe para buscar…" aria-label="Buscar eventos">
            <select class="form-select" id="categoria" aria-label="Filtrar por categoría">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <section class="card-list" id="lista" aria-label="Eventos" aria-live="polite"></section>
    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Oscar Álvarez · 2026
        </div>
    </footer>

    <script src="js/explorar.js"></script>
</body>
</html>
