<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fuentes de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos -->
    <link rel="stylesheet" href="estilos.css">

    <title>AgendaWeb</title>
</head>

<body>

    <form method="post" action="guardar.php" class="tarjeta">

        <h1>AgendaWeb</h1>

        <p class="subtitulo">
            Registra un nuevo evento
        </p>

        <!-- Título -->
        <input 
            type="text" 
            name="titulo" 
            placeholder="Título"
            required
        >

        <!-- Fecha -->
        <input 
            type="date" 
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

                <option value="Escuela">
                    Escuela
                </option>

                <option value="Cita">
                    Cita
                </option>

                <option value="Otro">
                    Otro
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
        ></textarea>

        <!-- Botón -->
        <button type="submit">
            Agregar evento
        </button>

    </form>

</body>
</html>