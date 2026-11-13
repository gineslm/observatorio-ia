<?php
// Marcas humanas de interés por pieza (data/feedback/interest.json).
// Solo se pueden escribir desde la máquina local: es la herramienta operativa, no la web pública.
declare(strict_types=1);

const FEEDBACK_FILE = DATA_DIR . '/feedback/interest.json';

function is_local_request(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($ip, ['127.0.0.1', '::1'], true);
}

/** Mapa item_id => ['interesting' => bool, 'date' => 'AAAA-MM-DD']. */
function load_feedback(): array {
    $arr = json_decode((string)@file_get_contents(FEEDBACK_FILE), true);
    $map = [];
    foreach (is_array($arr) ? $arr : [] as $r) $map[$r['item_id']] = $r;
    return $map;
}

/** $value: true, false o null (quitar marca). */
function save_feedback(string $itemId, ?bool $value): void {
    $dir = dirname(FEEDBACK_FILE);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $fh = fopen(FEEDBACK_FILE, 'c+');
    if (!$fh || !flock($fh, LOCK_EX)) throw new RuntimeException('No se pudo bloquear el fichero de marcas');
    $arr = json_decode(stream_get_contents($fh) ?: '[]', true) ?: [];
    $arr = array_values(array_filter($arr, fn($r) => $r['item_id'] !== $itemId));
    if ($value !== null) $arr[] = ['item_id' => $itemId, 'interesting' => $value, 'date' => date('Y-m-d')];
    usort($arr, fn($a, $b) => strcmp($a['item_id'], $b['item_id']));
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    flock($fh, LOCK_UN);
    fclose($fh);
}
