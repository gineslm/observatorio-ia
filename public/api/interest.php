<?php
// POST item_id, value (1 | 0 | "") -> guarda o quita la marca de interés. Solo local y mismo origen.
declare(strict_types=1);
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/feedback.php';

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Solo POST');
if (!is_local_request()) fail(403, 'Solo disponible en local');
// Evita que otra web abierta en el navegador escriba en el servidor local.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$port = parse_url($origin, PHP_URL_PORT);
$originHost = (string)parse_url($origin, PHP_URL_HOST) . ($port ? ':' . $port : '');
if ($origin === '' || $originHost !== ($_SERVER['HTTP_HOST'] ?? '')) {
    fail(403, 'Origen no permitido');
}

$id = (string)($_POST['item_id'] ?? '');
if (!preg_match('/^P-\d{4}-\d{2}-[FC]?\d{4}$/', $id)) fail(400, 'item_id no válido');
if (!in_array($id, array_column(load_items(), 'id'), true)) fail(404, 'Pieza inexistente');

$raw = (string)($_POST['value'] ?? '');
$value = $raw === '1' ? true : ($raw === '0' ? false : null);
save_feedback($id, $value);
echo json_encode(['ok' => true, 'item_id' => $id, 'value' => $value]);
