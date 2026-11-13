<?php
// Diagnóstico TEMPORAL del hosting. Solo lectura salvo una carpeta de prueba que se borra con &limpiar=1.
// Se sube con `python scripts/ftps.py diag-subir` (que sustituye el token) y se borra con `diag-borrar`.
declare(strict_types=1);

const TOKEN = '__TOKEN__';
// El marcador se compara troceado para que la sustitución del token no lo altere.
if (TOKEN === '__TO' . 'KEN__' || strlen(TOKEN) < 16 || !hash_equals(TOKEN, (string)($_GET['t'] ?? ''))) {
    http_response_code(404);
    exit;
}
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

$testDir = __DIR__ . '/diag_test';
if (isset($_GET['limpiar'])) {
    @unlink($testDir . '/.htaccess');
    @unlink($testDir . '/secreto.txt');
    @rmdir($testDir);
    exit("Carpeta de prueba eliminada: " . (is_dir($testDir) ? 'NO' : 'sí') . "\n");
}

$out = [];
$ok = fn($b) => $b ? 'sí' : 'NO';
$disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
$can = fn(string $f) => function_exists($f) && !in_array($f, $disabled, true);

$out[] = '== PHP y servidor';
$out[] = 'PHP: ' . PHP_VERSION . ' (' . PHP_SAPI . ')';
$out[] = 'Servidor: ' . ($_SERVER['SERVER_SOFTWARE'] ?? '?');
$out[] = 'SO: ' . PHP_OS . ' ' . php_uname('r');
$out[] = 'Document root: ' . ($_SERVER['DOCUMENT_ROOT'] ?? '?');
$out[] = 'Carpeta del script: ' . __DIR__;
$out[] = 'open_basedir: ' . (ini_get('open_basedir') ?: '(sin límite)');
$out[] = 'memory_limit: ' . ini_get('memory_limit') . ' · max_execution_time: ' . ini_get('max_execution_time') . ' s';
$free = @disk_free_space(__DIR__);
$out[] = 'Espacio libre: ' . ($free ? round($free / 1048576) . ' MB' : '?');

$out[] = '';
$out[] = '== Extensiones';
foreach (['curl', 'openssl', 'mbstring', 'json', 'simplexml', 'dom', 'libxml', 'pdo_mysql', 'pdo_sqlite', 'zip'] as $ext) {
    $out[] = str_pad($ext, 12) . $ok(extension_loaded($ext));
}

$out[] = '';
$out[] = '== Ejecución de comandos (git, cron por línea de comandos)';
foreach (['exec', 'shell_exec', 'proc_open', 'system'] as $f) $out[] = str_pad($f, 12) . $ok($can($f));
if ($can('shell_exec')) {
    $out[] = 'which php: ' . trim((string)@shell_exec('command -v php 2>/dev/null'));
    $out[] = 'PHP_BINARY: ' . PHP_BINARY;
    $out[] = 'git: ' . (trim((string)@shell_exec('git --version 2>/dev/null')) ?: 'no disponible');
}

$out[] = '';
$out[] = '== Salida HTTP (lectura de RSS)';
$out[] = 'allow_url_fopen: ' . $ok((bool)ini_get('allow_url_fopen'));
$feed = 'https://ainowinstitute.org/feed';
if (extension_loaded('curl')) {
    $ch = curl_init($feed);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'observatorio-diagnostico/0.1']);
    $body = curl_exec($ch);
    $out[] = 'cURL ' . $feed . ': HTTP ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ', ' . strlen((string)$body) . ' bytes, '
        . round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2) . ' s' . (curl_error($ch) ? ' · error: ' . curl_error($ch) : '');
    if ($body && extension_loaded('simplexml')) {
        $xml = @simplexml_load_string((string)$body);
        $out[] = 'Parseo RSS: ' . ($xml ? count($xml->channel->item ?? []) . ' items' : 'falló');
    }
}

$out[] = '';
$out[] = '== Escritura en disco y protección .htaccess';
$w = @is_dir($testDir) || @mkdir($testDir, 0755);
$w = $w && @file_put_contents($testDir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n") !== false;
$w = $w && @file_put_contents($testDir . '/secreto.txt', 'si ves esto, .htaccess NO protege') !== false;
$out[] = 'PHP puede escribir en su carpeta: ' . $ok($w);
if ($w && extension_loaded('curl')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $ch = curl_init($base . '/diag_test/secreto.txt');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_NOBODY => true]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $out[] = 'Acceso web a carpeta protegida: HTTP ' . $code . ($code === 403 ? ' → .htaccess protege (bien)' : ' → REVISAR: debería ser 403');
}
$out[] = '(Al terminar, abre esta misma URL añadiendo &limpiar=1 o ejecuta diag-borrar.)';

echo implode("\n", $out), "\n";
