<?php
// Utilidades comunes del visor. Solo lectura: los JSON de data/ son la fuente de verdad.
declare(strict_types=1);

const DATA_DIR = __DIR__ . '/../../data';
const LANGS = ['es' => 'Castellano', 'en' => 'English'];
const CRITERIA = ['c1', 'c2', 'c3', 'c4', 'c5'];

// ---------- idioma ----------
$requested = $_GET['lang'] ?? null;
if (is_string($requested) && isset(LANGS[$requested])) {
    setcookie('lang', $requested, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
    define('LANG', $requested);
} else {
    $c = $_COOKIE['lang'] ?? 'es';
    define('LANG', isset(LANGS[$c]) ? $c : 'es');
}
$GLOBALS['STRINGS'] = require __DIR__ . '/../lang/' . LANG . '.php';

/** Texto de interfaz traducido; admite argumentos sprintf. */
function t(string $key, ...$args): string {
    $s = $GLOBALS['STRINGS'][$key] ?? $key;
    return $args ? sprintf($s, ...$args) : $s;
}

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function q(string $k): string { return trim((string)($_GET[$k] ?? '')); }

function url_with(array $params): string {
    $p = array_filter(array_merge($_GET, $params), fn($v) => $v !== null && $v !== '');
    return '?' . http_build_query($p);
}

// ---------- datos ----------
function load_sources(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $db = json_decode((string)@file_get_contents(DATA_DIR . '/sources.json'), true);
    $cache = [];
    foreach ($db['sources'] ?? [] as $s) $cache[$s['id']] = $s;
    return $cache;
}

function load_items(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $items = [];
    foreach (glob(DATA_DIR . '/items/*.json') ?: [] as $f) {
        $arr = json_decode((string)file_get_contents($f), true);
        if (is_array($arr)) $items = array_merge($items, $arr);
    }
    usort($items, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
    return $cache = $items;
}

/** Texto de una pieza en el idioma actual. Título: cae al original; resumen: null si falta. */
function item_text(array $it, string $field) {
    $v = $it['i18n'][LANG][$field] ?? null;
    if ($field === 'title') return $v ?: $it['title'];
    if ($field === 'keywords') {
        $orig = $it['source_keywords'] ?? [];
        return (is_array($v) && count($v) === count($orig) && $v) ? $v : $orig;
    }
    return $v;
}

/** Texto de una fuente en el idioma actual; los campos base están en castellano. */
function source_text(array $s, string $field): ?string {
    return $s['i18n'][LANG][$field] ?? $s['i18n']['es'][$field] ?? $s[$field] ?? null;
}

function criterion(array $s, string $c): array {
    $group = in_array($c, ['c1', 'c3'], true) ? 'institutional_evaluation' : 'sampling_evaluation';
    return $s[$group][$c] ?? ['value' => 'NE', 'evidence_level' => null];
}

function contains(string $haystack, string $needle): bool {
    return $needle === '' || mb_stripos($haystack, $needle) !== false;
}

// ---------- componentes ----------
/**
 * Card común para piezas, fuentes y recursos.
 * $c: title, title_original?, url, origin, meta[], chips[] (label, href?, title?), summary, scores (fuente|null), notes, footer
 */
function card(array $c): void { ?>
  <article class="card">
    <div class="origin"><?= e($c['origin'] ?? '') ?></div>
    <h3><a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"<?= !empty($c['title_original']) ? ' title="' . e(t('tip.original_title', $c['title_original'])) . '"' : '' ?>><?= e($c['title']) ?></a></h3>
    <?php if (!empty($c['meta'])): ?><div class="meta"><?= e(implode(' · ', array_filter($c['meta']))) ?></div><?php endif; ?>
    <?php if (!empty($c['summary'])): ?>
      <p class="summary"><?= e($c['summary']) ?></p>
    <?php else: ?>
      <p class="summary pending"><?= e(t('summary.pending')) ?></p>
    <?php endif; ?>
    <?php if (!empty($c['scores'])): ?>
      <div class="scores">
        <?php foreach (CRITERIA as $k): $x = criterion($c['scores'], $k); ?>
          <span class="score <?= e($x['value']) ?>" title="<?= e(strtoupper($k) . ' ' . t('crit.' . $k) . ' · ' . t('evidence') . ': ' . ($x['evidence_level'] ?? t('pending'))) ?>"><?= strtoupper($k) ?> <?= e(t('score.' . $x['value'])) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($c['chips'])): ?>
      <div class="chips">
        <?php foreach ($c['chips'] as $ch): ?>
          <?php if (!empty($ch['href'])): ?>
            <a class="chip" href="<?= e($ch['href']) ?>" title="<?= e($ch['title'] ?? '') ?>"><?= e($ch['label']) ?></a>
          <?php else: ?>
            <span class="chip muted-chip" title="<?= e($ch['title'] ?? '') ?>"><?= e($ch['label']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($c['notes'])): ?>
      <details><summary><?= e(t('notes')) ?> <?= e(t('notes.only_es')) ?></summary><p lang="es"><?= e($c['notes']) ?></p></details>
    <?php endif; ?>
    <?php if (!empty($c['footer'])): ?><div class="card-foot"><?= $c['footer'] /* HTML ya escapado por quien llama */ ?></div><?php endif; ?>
  </article>
<?php }

function source_card(array $s, int $nItems = 0): void {
    $origin = source_text($s, 'type') . ' · ' . source_text($s, 'region');
    if (($s['kind'] ?? null) === 'infrastructure') $origin .= ' · ' . t('infrastructure');
    card([
        'title' => $s['name'],
        'url' => $s['url'],
        'origin' => $origin,
        'meta' => [implode(', ', $s['languages'] ?? []), t('rel.' . ($s['relevance'] ?? '')), 'n=' . ($s['sampling_evaluation']['n_items'] ?? '–')],
        'summary' => source_text($s, 'summary'),
        'scores' => $s,
        'notes' => $s['legacy_evidence_notes'] ?? null,
        'footer' => $nItems > 0 ? '<a href="index.php?fuente=' . e($s['id']) . '">' . e(t('items_in_board', $nItems)) . '</a>' : '',
    ]);
}

function layout_start(string $title, string $active): void {
    $nav = ['index.php' => t('nav.board'), 'fuentes.php' => t('nav.sources'), 'recursos.php' => t('nav.resources')]; ?>
<!doctype html>
<html lang="<?= LANG ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e(t('brand')) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div class="wrap-h">
    <a class="brand" href="index.php"><?= e(t('brand')) ?></a>
    <nav>
      <?php foreach ($nav as $href => $label): ?><a href="<?= $href ?>" class="<?= $active === $href ? 'on' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
      <span class="lang-switch"><?php foreach (LANGS as $code => $name): ?><a href="<?= e(url_with(['lang' => $code])) ?>" class="<?= LANG === $code ? 'on' : '' ?>" title="<?= e($name) ?>" hreflang="<?= $code ?>"><?= strtoupper($code) ?></a><?php endforeach; ?></span>
    </nav>
  </div>
</header>
<main>
<?php }

function layout_end(): void { ?>
  <footer class="foot"><?= e(t('footer')) ?></footer>
</main>
</body>
</html>
<?php }
