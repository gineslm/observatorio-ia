<?php
// Utilidades comunes del visor. Solo lectura: los JSON de data/ son la fuente de verdad.
declare(strict_types=1);

const DATA_DIR = __DIR__ . '/../../data';
const CRITERIA = ['c1' => 'Autoría', 'c2' => 'Verificabilidad', 'c3' => 'Financiación', 'c4' => 'Incertidumbre', 'c5' => 'Hecho/opinión'];

const LANGS = ['es' => 'castellano', 'en' => 'inglés', 'de' => 'alemán', 'fr' => 'francés', 'it' => 'italiano', 'zh' => 'chino', 'ca' => 'catalán', 'pt' => 'portugués'];

function lang_name(?string $code): string { return LANGS[$code ?? ''] ?? (string)$code; }

/** Keywords de una pieza en castellano si existen, si no las originales. */
function item_keywords(array $it): array {
    $orig = $it['source_keywords'] ?? [];
    $es = $it['source_keywords_es'] ?? [];
    return count($es) === count($orig) && $es ? $es : $orig;
}

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function q(string $k): string { return trim((string)($_GET[$k] ?? '')); }

function url_with(array $params): string {
    $p = array_filter(array_merge($_GET, $params), fn($v) => $v !== null && $v !== '');
    return '?' . http_build_query($p);
}

function load_sources(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $db = json_decode((string)@file_get_contents(DATA_DIR . '/sources.json'), true);
    $cache = [];
    foreach ($db['sources'] ?? [] as $s) $cache[$s['id']] = $s;
    return $cache;
}

function load_items(): array {
    $items = [];
    foreach (glob(DATA_DIR . '/items/*.json') ?: [] as $f) {
        $arr = json_decode((string)file_get_contents($f), true);
        if (is_array($arr)) $items = array_merge($items, $arr);
    }
    usort($items, fn($a, $b) => strcmp($b['published_at'] ?? '', $a['published_at'] ?? ''));
    return $items;
}

function criterion(array $s, string $c): array {
    $group = in_array($c, ['c1', 'c3'], true) ? 'institutional_evaluation' : 'sampling_evaluation';
    return $s[$group][$c] ?? ['value' => 'NE', 'evidence_level' => null];
}

function contains(string $haystack, string $needle): bool {
    return $needle === '' || mb_stripos($haystack, $needle) !== false;
}

/**
 * Card común para piezas, fuentes y recursos.
 * $c: title, url, origin, meta[], chips[] (label, href?, title?), summary, scores (fuente|null), notes, footer
 */
function card(array $c): void { ?>
  <article class="card">
    <div class="origin"><?= e($c['origin'] ?? '') ?></div>
    <h3><a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"<?= !empty($c['title_original']) ? ' title="Título original: ' . e($c['title_original']) . '"' : '' ?>><?= e($c['title']) ?></a></h3>
    <?php if (!empty($c['meta'])): ?><div class="meta"><?= e(implode(' · ', array_filter($c['meta']))) ?></div><?php endif; ?>
    <?php if (!empty($c['summary'])): ?>
      <p class="summary"><?= e($c['summary']) ?></p>
    <?php else: ?>
      <p class="summary pending">Resumen pendiente.</p>
    <?php endif; ?>
    <?php if (!empty($c['scores'])): ?>
      <div class="scores">
        <?php foreach (CRITERIA as $k => $label): $x = criterion($c['scores'], $k); ?>
          <span class="score <?= e($x['value']) ?>" title="<?= e(strtoupper($k) . ' ' . $label . ' · evidencia: ' . ($x['evidence_level'] ?? 'pendiente')) ?>"><?= strtoupper($k) ?> <?= e($x['value']) ?></span>
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
      <details><summary>Notas de evaluación</summary><p><?= e($c['notes']) ?></p></details>
    <?php endif; ?>
    <?php if (!empty($c['footer'])): ?><div class="card-foot"><?= $c['footer'] /* HTML ya escapado por quien llama */ ?></div><?php endif; ?>
  </article>
<?php }

function source_card(array $s, int $nItems = 0): void {
    card([
        'title' => $s['name'],
        'url' => $s['url'],
        'origin' => ($s['type'] ?? '') . ' · ' . ($s['region'] ?? ''),
        'meta' => [implode(', ', $s['languages'] ?? []), $s['relevance'] ?? '', 'n=' . ($s['sampling_evaluation']['n_items'] ?? '–')],
        'summary' => $s['summary'] ?? null,
        'scores' => $s,
        'notes' => $s['legacy_evidence_notes'] ?? null,
        'footer' => $nItems > 0 ? '<a href="index.php?fuente=' . e($s['id']) . '">' . $nItems . ' piezas en el tablero →</a>' : '',
    ]);
}

function layout_start(string $title, string $active): void {
    $nav = ['index.php' => 'Tablero', 'fuentes.php' => 'Fuentes', 'recursos.php' => 'Recursos']; ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Observatorio IA</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div class="wrap-h">
    <a class="brand" href="index.php">Observatorio IA</a>
    <nav><?php foreach ($nav as $href => $label): ?><a href="<?= $href ?>" class="<?= $active === $href ? 'on' : '' ?>"><?= $label ?></a><?php endforeach; ?></nav>
  </div>
</header>
<main>
<?php }

function layout_end(): void { ?>
  <footer class="foot">Evaluaciones y resúmenes propuestos por un asistente de IA, sin revisión humana. NE = no evaluable (no equivale a Bajo). Las keywords son las que declara cada fuente, no una clasificación propia.</footer>
</main>
</body>
</html>
<?php }
