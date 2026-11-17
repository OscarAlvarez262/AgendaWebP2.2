<?php
/* api.php · P10 · mis eventos en JSON */

session_start();

// 1 · Lo PRIMERO que sale: la etiqueta "esto es JSON"
header('Content-Type: application/json; charset=utf-8');

// Una sola salida para todas las respuestas: código + JSON + fin
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2 · Sin sesión no hay datos: 401 = "no sé quién eres"
if (!isset($_SESSION['uid'])) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}
$usuarioId = (int) $_SESSION['uid'];

// Esta API solo CONSULTA: cualquier otro método se rechaza
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

// Punto único de conexión del proyecto (incluye conexion.php y aplica la migración P8 si hace falta)
require __DIR__ . '/partes/base_datos.php';

// 5 · Filtros opcionales: lo mismo que el buscador de P9
$q = mb_substr(trim($_GET['q'] ?? ''), 0, 60);
$categoriaId = (int) ($_GET['categoria'] ?? 0);

$condiciones = ['e.usuario_id = ?'];          // SIEMPRE: solo mis eventos
$tipos       = 'i';
$valores     = [$usuarioId];

if ($q !== '') {
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(e.titulo LIKE ? OR e.descripcion LIKE ?)';
    $tipos      .= 'ss';
    $valores[]   = $patron;
    $valores[]   = $patron;
}
if ($categoriaId > 0) {
    $condiciones[] = 'e.categoria_id = ?';
    $tipos      .= 'i';
    $valores[]   = $categoriaId;
}

// 3 · Nunca SELECT *: elige las columnas que entregas
$sql = "SELECT e.id, e.titulo, e.fecha,
               TIME_FORMAT(e.hora, '%H:%i') AS hora,
               c.nombre AS categoria, e.descripcion
          FROM eventos e
          JOIN categorias c ON c.id = e.categoria_id
         WHERE " . implode(' AND ', $condiciones) . "
         ORDER BY e.fecha, e.hora";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$eventos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$mysqli->close();

// 4 · Respuesta: datos de contexto + el arreglo de eventos
responder(200, [
    'usuario' => $_SESSION['nombre'] ?? '',
    'total'   => count($eventos),
    'filtros' => ['q' => $q, 'categoria' => $categoriaId],
    'eventos' => $eventos,
]);
