<?php
/**
 * Captura de feeds RSS/Atom. Único autor de data/items/feed/AAAA-MM.json.
 *
 * Uso:
 *   CLI (cron del panel o local):  php cron/fetch_feeds.php [--limit=10]
 *   HTTP (cron externo):           POST https://.../cron/fetch_feeds.php  con cabecera  X-Cron-Token: <token>
 *
 * Guarda solo metadatos y keywords declaradas por la fuente. NUNCA el texto ni el extracto
 * (regla 3): los resúmenes y traducciones los añade la tarea local en data/enrichment/.
 */
declare(strict_types=1);

const IGNORED_KEYWORDS = ['uncategorized', 'sin categoría', 'allgemein'];
const USER_AGENT = 'observatorio-feed-reader/0.2';

$cli = PHP_SAPI === 'cli';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
    $cfgFile = __DIR__ . '/config.local.php';
    $cfg = is_file($cfgFile) ? require $cfgFile : [];
    $token = (string)($cfg['cron_token'] ?? '');
    $sent = (string)($_SERVER['HTTP_X_CRON_TOKEN'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strlen($token) < 24 || !hash_equals($token, $sent)) {
        http_response_code(404);
        exit;
    }
}

// El cron externo corta la conexión a los 30 s: la captura sigue aunque el cliente se desconecte.
ignore_user_abort(true);
set_time_limit(300);

$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false) fail('No se encuentra la carpeta data/');
$limit = 10;
foreach ($argv ?? [] as $a) if (preg_match('/^--limit=(\d+)$/', $a, $m)) $limit = (int)$m[1];

// Bloqueo para no ejecutar dos capturas a la vez.
@mkdir($dataDir . '/logs', 0775, true);
$lock = fopen($dataDir . '/logs/fetch.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) fail('Ya hay una captura en curso');

function fail(string $msg): void {
    if (PHP_SAPI !== 'cli') http_response_code(500);
    fwrite(PHP_SAPI === 'cli' ? STDERR : fopen('php://output', 'w'), "ERROR: $msg\n");
    exit(1);
}

function read_json(string $path, $default) {
    if (!is_file($path)) return $default;
    $v = json_decode((string)file_get_contents($path), true);
    return $v ?? $default;
}

function write_json(string $path, $data): void {
    @mkdir(dirname($path), 0775, true);
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    rename($tmp, $path); // reemplazo atómico
}

function http_get(string $url): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => USER_AGENT]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        if ($body === false || $code >= 400) throw new RuntimeException("HTTP $code $err");
        return (string)$body;
    }
    $ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => USER_AGENT, 'follow_location' => 1]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) throw new RuntimeException('no se pudo descargar');
    return $body;
}

function clean(string $s): string {
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function to_date(string $s): ?string {
    $t = $s !== '' ? strtotime($s) : false;
    return $t ? gmdate('Y-m-d', $t) : null;
}

/** Devuelve [['title','url','published_at','authors','keywords','language'], ...] */
function parse_feed(string $xml): array {
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if (!$doc) throw new RuntimeException('XML no válido');
    $out = [];
    $dc = 'http://purl.org/dc/elements/1.1/';
    if (isset($doc->channel)) { // RSS 2.0
        $lang = strtolower(substr((string)$doc->channel->language, 0, 2)) ?: null;
        foreach ($doc->channel->item as $it) {
            $authors = [];
            foreach ($it->children($dc)->creator as $c) if (clean((string)$c) !== '') $authors[] = clean((string)$c);
            $kw = [];
            foreach ($it->category as $c) if (clean((string)$c) !== '') $kw[] = clean((string)$c);
            $out[] = ['title' => clean((string)$it->title), 'url' => trim((string)$it->link),
                'published_at' => to_date((string)$it->pubDate), 'authors' => $authors, 'keywords' => $kw, 'language' => $lang];
        }
    } else { // Atom
        $doc->registerXPathNamespace('a', 'http://www.w3.org/2005/Atom');
        $lang = strtolower(substr((string)$doc->attributes('xml', true)->lang, 0, 2)) ?: null;
        foreach ($doc->entry as $en) {
            $url = '';
            foreach ($en->link as $l) {
                $rel = (string)$l['rel'];
                if ($rel === '' || $rel === 'alternate') { $url = (string)$l['href']; break; }
            }
            $authors = [];
            foreach ($en->author as $a) if (clean((string)$a->name) !== '') $authors[] = clean((string)$a->name);
            $kw = [];
            foreach ($en->category as $c) if ((string)$c['term'] !== '') $kw[] = (string)$c['term'];
            $out[] = ['title' => clean((string)$en->title), 'url' => trim($url),
                'published_at' => to_date((string)($en->published ?: $en->updated)), 'authors' => $authors, 'keywords' => $kw, 'language' => $lang];
        }
    }
    foreach ($out as &$e) {
        $e['keywords'] = array_values(array_filter($e['keywords'], fn($k) => !in_array(mb_strtolower($k), IGNORED_KEYWORDS, true)));
        if (preg_match('#^https?://[^/]+/([a-z]{2})/#', $e['url'], $m)) $e['language'] = $m[1]; // el idioma de la URL manda
    }
    unset($e);
    return array_values(array_filter($out, fn($e) => $e['url'] !== '' && $e['title'] !== ''));
}

// ---------- captura ----------
$sources = read_json($dataDir . '/sources.json', [])['sources'] ?? [];
$known = [];
foreach (array_merge(glob($dataDir . '/items/feed/*.json') ?: [], glob($dataDir . '/items/consulta/*.json') ?: []) as $f) {
    foreach (read_json($f, []) as $it) $known[$it['url']] = true;
}

$today = gmdate('Y-m-d');
$month = gmdate('Y-m');
$monthFile = $dataDir . "/items/feed/$month.json";
$monthItems = read_json($monthFile, []);
$prefix = "P-$month-F";
$seq = 0;
foreach ($monthItems as $it) if (str_starts_with($it['id'], $prefix)) $seq = max($seq, (int)substr($it['id'], -4));

$log = ['date' => gmdate('c'), 'new' => 0, 'feeds_ok' => 0, 'feeds_error' => []];
$lines = [];
foreach ($sources as $s) {
    foreach ($s['feeds'] ?? [] as $feed) {
        try {
            $entries = parse_feed(http_get($feed));
            $log['feeds_ok']++;
        } catch (Throwable $ex) {
            $log['feeds_error'][] = ['source' => $s['id'], 'feed' => $feed, 'error' => $ex->getMessage()];
            $lines[] = "[{$s['id']}] ERROR $feed: " . $ex->getMessage();
            continue;
        }
        usort($entries, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
        foreach (array_slice($entries, 0, $limit) as $e) {
            if (isset($known[$e['url']])) continue;
            $seq++;
            $monthItems[] = [
                'id' => $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT),
                'source_id' => $s['id'],
                'url' => $e['url'],
                'title' => $e['title'],
                'authors' => $e['authors'],
                'published_at' => $e['published_at'],
                'captured_at' => $today,
                'language' => $e['language'],
                'original_language' => $e['language'],
                'translated_from' => null,
                'source_keywords' => $e['keywords'],
                'entry_reason' => ['channel' => 'feed', 'detail' => $feed],
                'argues' => '',
                'identifiers' => ['doi' => null, 'arxiv' => null, 'openalex' => null],
                'links_to_primary' => [],
                'review' => ['reviewed_by_human' => false, 'date' => null],
                'labels' => [],
            ];
            $known[$e['url']] = true;
            $log['new']++;
            $lines[] = "[{$s['id']}] + " . end($monthItems)['id'] . ' ' . mb_substr($e['title'], 0, 90);
        }
    }
}

if ($log['new'] > 0) write_json($monthFile, $monthItems);
file_put_contents($dataDir . "/logs/fetch-$month.log", json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
flock($lock, LOCK_UN);

echo implode("\n", $lines), ($lines ? "\n" : ''), "\n{$log['new']} piezas nuevas · {$log['feeds_ok']} feeds correctos · " . count($log['feeds_error']) . " con error\n";
