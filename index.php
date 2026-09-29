
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="estilos.css">
    <title>Mis eventos | AgendaWeb</title>
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
                    + Nuevo evento
                </a>
            </nav>
        </div>
    </header>

    <main class="contenedor dashboard">

        <section class="bienvenida">
            <div class="bienvenida__contenido">
                <span class="etiqueta">TU ESPACIO PERSONAL</span>

                <h1>Organiza tu día.</h1>

                <p>
                    Ten tus pendientes y actividades en un solo lugar.
                    Planea mejor, cumple tus metas y aprovecha tu tiempo.
                </p>

                <a href="registrar.php" class="boton-principal">
                    + Crear nuevo evento
                </a>
            </div>

            <div class="bienvenida__decoracion" aria-hidden="true">
                <span>✦</span>
                <div class="decoracion-circulo">A</div>
                <span>✓</span>
            </div>
        </section>

        <section class="resumen">
            <article class="resumen__tarjeta">
                <span class="resumen__icono">▤</span>
                <div>
                    <p>Total de eventos</p>
                    <h2>03</h2>
                </div>
            </article>

            <article class="resumen__tarjeta">
                <span class="resumen__icono">◷</span>
                <div>
                    <p>Pendientes</p>
                    <h2>02</h2>
                </div>
            </article>

            <article class="resumen__tarjeta">
                <span class="resumen__icono">✓</span>
                <div>
                    <p>Completados</p>
                    <h2>01</h2>
                </div>
            </article>
        </section>

        <section class="eventos">
            <div class="eventos__encabezado">
                <div>
                    <span class="etiqueta">MANTÉN TODO BAJO CONTROL</span>
                    <h2>Tus próximos eventos</h2>
                    <p>Consulta tus actividades y organiza tus pendientes.</p>
                </div>

                <a href="registrar.php" class="enlace-eventos">
                    + Agregar evento
                </a>
            </div>

            <!-- EJEMPLOS VISUALES:
                 Sustituye estas tarjetas por el ciclo PHP
                 que mostrará los eventos guardados en SQL. -->

            <div class="lista-eventos">

                <article class="evento">
                    <div class="evento__fecha">
                        <span>30</span>
                        <small>SEP</small>
                    </div>

                    <div class="evento__info">
                        <span class="evento__categoria">Escuela</span>
                        <h3>Entregar proyecto</h3>
                        <p>10:00 h · Entrega de actividad escolar</p>
                    </div>

                    <span class="prioridad prioridad--alta">
                        Alta
                    </span>
                </article>

                <article class="evento">
                    <div class="evento__fecha">
                        <span>02</span>
                        <small>OCT</small>
                    </div>

                    <div class="evento__info">
                        <span class="evento__categoria">Personal</span>
                        <h3>Estudiar para el examen</h3>
                        <p>16:00 h · Repasar los temas pendientes</p>
                    </div>

                    <span class="prioridad prioridad--media">
                        Media
                    </span>
                </article>

                <article class="evento">
                    <div class="evento__fecha">
                        <span>05</span>
                        <small>OCT</small>
                    </div>

                    <div class="evento__info">
                        <span class="evento__categoria">Trabajo</span>
                        <h3>Revisar actividades</h3>
                        <p>12:00 h · Organizar tareas de la semana</p>
                    </div>

                    <span class="prioridad prioridad--baja">
                        Baja
                    </span>
                </article>

            </div>
        </section>

    </main>

    <footer class="site-footer">
        <div class="contenedor">
            AgendaWeb · Tu nombre · 2026
        </div>
    </footer>

</body>
</html>