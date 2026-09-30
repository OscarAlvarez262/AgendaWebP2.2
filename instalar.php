<?php

/*
 |--------------------------------------------------------------
 | instalador.php
 |
 | Asistente de puesta en marcha. Hace tres cosas:
 |   1. Muestra el estado del servidor (PHP, mysqli, mbstring,
 |      variables MARIADB_* disponibles).
 |   2. Comprueba la conexión con la base de datos.
 |   3. Crea la tabla `eventos` usando db.sql.
 |
 | También genera conexion.php con las credenciales validadas,
 | pero SOLO si ese archivo todavia no existe.
 |
 | IMPORTANTE: la base de datos NO se puede crear desde aqui.
 | DomCloud lo prohibe expresamente para PHP, para el cliente
 | mariadb y para phpMyAdmin. Hay que crearla en Setup -> Database.
 |
 | Cuando termines, borra este archivo.
 |--------------------------------------------------------------
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const RUTA_CONEXION = __DIR__ . '/conexion.php';
const RUTA_ESQUEMA = __DIR__ . '/db.sql';
const TABLA = 'eventos';

// Valores por defecto: se completan con las variables de entorno si
// existen. El nombre de usuario en DomCloud suele ser el de la cuenta.
$usuarioPre = getenv('MARIADB_USER') ?: 'realistic-service-vis';
$basePre = getenv('MARIADB_NAME') ?: $usuarioPre . '_agenda';

$usuario = $usuarioPre;
$clave = '';
$base = $basePre;

$avisos = [];
$errores = [];
$diagnostico = null;

// ---------------------------------------------------------------
// Acción: probar conexión y crear la tabla
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'instalar') {

    $usuario = trim($_POST['usuario'] ?? '');
    $clave = (string)($_POST['clave'] ?? '');
    $base = trim($_POST['base'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_-]+$/', $usuario)) {
        $errores[] = 'El usuario solo admite letras, números, guion y guion bajo.';
    }

    if (!preg_match('/^[A-Za-z0-9_-]+$/', $base)) {
        $errores[] = 'El nombre de la base solo admite letras, números, guion y guion bajo.';
    }

    if ($clave === '') {
        $errores[] = 'La contraseña está vacía.';
    }

    if (empty($errores)) {
        $mysqli = null;

        try {
            $mysqli = new mysqli(
                getenv('MARIADB_HOST') ?: 'localhost',
                $usuario,
                $clave,
                $base
            );

            $mysqli->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            $errores[] = 'Conexión rechazada: ' . $e->getMessage();
        }

        if ($mysqli !== null && empty($errores)) {
            $diagnostico = [];

            // Crear la tabla a partir de db.sql
            if (!is_readable(RUTA_ESQUEMA)) {
                $errores[] = 'No se encuentra db.sql en el servidor.';
            } else {
                $sql = file_get_contents(RUTA_ESQUEMA);

                try {
                    if ($mysqli->multi_query($sql)) {
                        do {
                            $resultado = $mysqli->store_result();

                            if ($resultado instanceof mysqli_result) {
                                $resultado->free();
                            }
                        } while ($mysqli->more_results() && $mysqli->next_result());
                    }

                    $avisos[] = 'Esquema de db.sql aplicado (CREATE TABLE IF NOT EXISTS, no borra datos).';
                } catch (mysqli_sql_exception $e) {
                    $errores[] = 'Error al aplicar db.sql: ' . $e->getMessage();
                }
            }

            // Comprobar que la tabla existe y está completa
            if (empty($errores)) {
                $nombreTabla = TABLA;

                $comprobar = $mysqli->prepare(
                    'SELECT COUNT(*) AS total FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = ?'
                );

                $comprobar->bind_param('s', $nombreTabla);
                $comprobar->execute();

                $existe = $comprobar->get_result()->fetch_assoc();

                $comprobar->close();

                if ((int) $existe['total'] === 0) {
                    $errores[] = 'La tabla ' . TABLA . ' no aparece tras aplicar db.sql.';
                } else {
                    $columnas = [];

                    foreach ($mysqli->query('SHOW COLUMNS FROM `' . TABLA . '`') as $columna) {
                        $columnas[$columna['Field']] = $columna['Type'];
                    }

                    $requeridas = ['id', 'titulo', 'fecha', 'hora', 'categoria', 'prioridad', 'descripcion', 'creado_en'];
                    $faltantes = array_diff($requeridas, array_keys($columnas));

                    if ($faltantes) {
                        $errores[] = 'Faltan columnas en ' . TABLA . ': ' . implode(', ', $faltantes) . '.';
                    } else {
                        $diagnostico['columnas'] = $columnas;

                        $indices = [];

                        foreach ($mysqli->query('SHOW INDEX FROM `' . TABLA . '`') as $indice) {
                            $indices[] = $indice['Key_name'] . ' (' . $indice['Column_name'] . ')';
                        }

                        $diagnostico['indices'] = array_values(array_unique($indices));
                        $diagnostico['filas'] = $mysqli->query('SELECT COUNT(*) AS total FROM `' . TABLA . '`')
                            ->fetch_assoc()['total'];
                        $diagnostico['base'] = $base;
                    }
                }
            }

            // Generar conexion.php si todavia no existe
            if (empty($errores) && !file_exists(RUTA_CONEXION)) {
                $contenido = plantillaConexion($usuario, $clave, $base);

                if (@file_put_contents(RUTA_CONEXION, $contenido) !== false) {
                    @chmod(RUTA_CONEXION, 0600);
                    $avisos[] = 'conexion.php creado con permisos 600.';
                } else {
                    $errores[] = 'Conexión correcta, pero no se pudo escribir conexion.php (permisos).';
                }
            } elseif (empty($errores)) {
                $avisos[] = 'conexion.php ya existía, se dejó intacto.';
            }
        }
    }
}

/*
 |--------------------------------------------------------------
 | Plantilla del archivo conexion.php generado.
 | Nowdoc para que PHP no interprete los $ del archivo destino.
 |--------------------------------------------------------------
 */
function plantillaConexion(string $usuario, string $clave, string $base): string
{
    $molde = <<<'MOLDE'
<?php

/*
 |--------------------------------------------------------------
 | Conexion generada por instalar.php. No versionar este archivo.
 |--------------------------------------------------------------
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('MARIADB_HOST') ?: 'localhost';
$usuario = getenv('MARIADB_USER') ?: __USUARIO__;
$clave = getenv('MARIADB_PASS') ?: __CLAVE__;
$base = getenv('MARIADB_NAME') ?: __BASE__;

try {
    $mysqli = new mysqli($host, $usuario, $clave, $base);

    $mysqli->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('Conexion a la BD: ' . $e->getMessage());

    die('No se pudo conectar a la base de datos.');
}

MOLDE;

    return str_replace(
        ['__USUARIO__', '__CLAVE__', '__BASE__'],
        [valorPhp($usuario), valorPhp($clave), valorPhp($base)],
        $molde
    );
}

/*
 | Escapa un valor para escribirlo como cadena de PHP.
 */
function valorPhp(string $texto): string
{
    return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $texto) . "'";
}

$extensiones = ['mysqli' => extension_loaded('mysqli'), 'mbstring' => extension_loaded('mbstring')];
$entorno = [];

foreach (['MARIADB_HOST', 'MARIADB_USER', 'MARIADB_PASS', 'MARIADB_NAME'] as $variable) {
    $valor = getenv($variable);
    $entorno[$variable] = ($valor !== false && $valor !== '');
}

$versionPhp = PHP_VERSION;
$phpAntiguo = version_compare($versionPhp, '8.0', '<');
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/estilos.css">

    <title>Instalar | AgendaWeb</title>
</head>

<body>

    <div class="tarjeta">

        <h1>Agenda<span>Web</span></h1>

        <p class="subtitulo">
            Instalador
        </p>

        <?php foreach ($errores as $mensaje): ?>
            <div class="alert alert--error" role="alert">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <?php foreach ($avisos as $mensaje): ?>
            <div class="alert alert--ok" role="alert">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>

        <!-- 1. Estado del servidor -->
        <h2>1. Estado del servidor</h2>

        <ul>
            <li>
                PHP <?= htmlspecialchars($versionPhp, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($phpAntiguo): ?>
                    (anterior a 8.0, la app sigue funcionando)
                <?php endif; ?>
            </li>
            <li>mysqli: <?= $extensiones['mysqli'] ? 'cargada' : 'NO CARGADA' ?></li>
            <li>mbstring: <?= $extensiones['mbstring'] ? 'cargada' : 'NO CARGADA' ?></li>

            <?php foreach ($entorno as $variable => $definida): ?>
                <li><?= htmlspecialchars($variable, ENT_QUOTES, 'UTF-8') ?>: <?= $definida ? 'definida' : 'no definida' ?></li>
            <?php endforeach; ?>

            <li>
                conexion.php:
                <?php if (file_exists(RUTA_CONEXION)): ?>
                    existe
                <?php else: ?>
                    no existe
                <?php endif; ?>
            </li>
        </ul>

        <!-- 2. Base de datos -->
        <h2>2. Base de datos</h2>

        <p>
            La base de datos <strong>no se puede crear desde PHP</strong>, ni con el
            cliente <code>mariadb</code>, ni desde phpMyAdmin: DomCloud lo prohíbe.
            Créala en <strong>Setup &rarr; Database</strong> y vuelve aquí.
        </p>

        <!-- 3. Formulario -->
        <h2>3. Conectar y crear la tabla</h2>

        <form method="post" action="">
            <input type="hidden" name="accion" value="instalar">

            <input
                type="text"
                name="usuario"
                placeholder="Usuario"
                value="<?= htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <input
                type="password"
                name="clave"
                placeholder="Contraseña"
                required
            >

            <input
                type="text"
                name="base"
                placeholder="Base de datos"
                value="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <div class="acciones-formulario">

                <a href="index.php" class="boton-cancelar">
                    Cancelar
                </a>

                <button type="submit">
                    Conectar y crear la tabla
                </button>

            </div>
        </form>

        <!-- 4. Resultado -->
        <?php if ($diagnostico !== null && isset($diagnostico['columnas'])): ?>

            <h2>4. Resultado</h2>

            <p>
                Conectado a <strong><?= htmlspecialchars($diagnostico['base'], ENT_QUOTES, 'UTF-8') ?></strong>.
                La tabla <strong><?= htmlspecialchars(TABLA, ENT_QUOTES, 'UTF-8') ?></strong> tiene
                <?= htmlspecialchars((string) $diagnostico['filas'], ENT_QUOTES, 'UTF-8') ?> fila(s).
            </p>

            <p>Columnas:</p>

            <ul>
                <?php foreach ($diagnostico['columnas'] as $columna => $tipo): ?>
                    <li><?= htmlspecialchars($columna, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <p>Índices:</p>

            <ul>
                <?php foreach ($diagnostico['indices'] as $indice): ?>
                    <li><?= htmlspecialchars($indice, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>

            <p>
                Ya puedes ir a <a href="index.php">index.php</a> y registrar un evento.
            </p>

        <?php endif; ?>

        <div class="alert alert--error" role="alert">
            Borra instalar.php del servidor cuando termines.
        </div>

    </div>

</body>
</html>
