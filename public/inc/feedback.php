<?php
// Marcas humanas de interés por pieza (data/feedback/interest.json).
// Solo se pueden escribir desde la máquina local: es la herramienta operativa, no la web pública.
declare(strict_types=1);

const FEEDBACK_FILE = DATA_DIR . '/feedback/interest.json';

/**
 * Solo la herramienta local: servidor de desarrollo de PHP (php -S) y petición desde la propia máquina.
 * En el hosting (Apache/FPM) siempre es false, aunque un proxy haga llegar las visitas como 127.0.0.1.
 */
function is_local_request(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return PHP_SAPI === 'cli-server' && in_array($ip, ['127.0.0.1', '::1'], true);
}

/** El estado se muestra en local y, en solo lectura, online si se ha subido el fichero de marcas. */
function status_visible(): bool {
    return is_local_request() || is_file(FEEDBACK_FILE);
}

/** Cambiar el estado: solo en local. */
function status_editable(): bool {
    return is_local_request();
}

// Estados de valoración, en orden de avance: pendiente (sin registro) -> ok (interesante) -> destacado.
const STATUSES = ['sin', 'ok', 'estrella'];

/** Mapa item_id => ['interesting' => bool, 'featured' => bool?, 'date' => 'AAAA-MM-DD']. */
function load_feedback(): array {
    $arr = json_decode((string)@file_get_contents(FEEDBACK_FILE), true);
    $map = [];
    foreach (is_array($arr) ? $arr : [] as $r) $map[$r['item_id']] = $r;
    return $map;
}

/** Estado de una pieza a partir de su registro. Las marcas antiguas «no interesa» (interesting=false) cuentan como pendientes. */
function item_status(?array $r): string {
    if (!$r || empty($r['interesting'])) return 'sin';
    return !empty($r['featured']) ? 'estrella' : 'ok';
}

/** $status: 'sin' (quita el registro), 'ok' o 'estrella'. */
function save_feedback(string $itemId, string $status): void {
    $dir = dirname(FEEDBACK_FILE);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $fh = fopen(FEEDBACK_FILE, 'c+');
    if (!$fh || !flock($fh, LOCK_EX)) throw new RuntimeException('No se pudo bloquear el fichero de marcas');
    $arr = json_decode(stream_get_contents($fh) ?: '[]', true) ?: [];
    $arr = array_values(array_filter($arr, fn($r) => $r['item_id'] !== $itemId));
    if ($status !== 'sin') {
        $rec = ['item_id' => $itemId, 'interesting' => true];
        if ($status === 'estrella') $rec['featured'] = true;
        $arr[] = $rec + ['date' => date('Y-m-d')];
    }
    usort($arr, fn($a, $b) => strcmp($a['item_id'], $b['item_id']));
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    flock($fh, LOCK_UN);
    fclose($fh);
}
