<?php
// Utilidades comunes del visor. Solo lectura: los JSON de data/ son la fuente de verdad.
declare(strict_types=1);

// En local, data/ es hermana de public/; en el servidor se despliega junto a index.php.
define('DATA_DIR', is_dir(__DIR__ . '/../data') ? __DIR__ . '/../data' : __DIR__ . '/../../data');
// Ruta base del portal (p. ej. /portal/ia/) para que la cookie no afecte a otras webs del dominio.
define('BASE_PATH', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/');
const LANGS = ['es' => 'Castellano', 'en' => 'English'];
const CRITERIA = ['c1', 'c2', 'c3', 'c4', 'c5'];

// ---------- idioma ----------
$requested = $_GET['lang'] ?? null;
if (is_string($requested) && isset(LANGS[$requested])) {
    setcookie('lang', $requested, ['expires' => time() + 31536000, 'path' => BASE_PATH, 'samesite' => 'Lax']);
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
    // Piezas en bruto de ambos autores + enriquecimiento (textos y tipo) unido por id.
    $items = [];
    foreach (array_merge(glob(DATA_DIR . '/items/feed/*.json') ?: [], glob(DATA_DIR . '/items/consulta/*.json') ?: []) as $f) {
        $arr = json_decode((string)file_get_contents($f), true);
        if (is_array($arr)) $items = array_merge($items, $arr);
    }
    $enrich = [];
    foreach (glob(DATA_DIR . '/enrichment/*.json') ?: [] as $f) {
        $map = json_decode((string)file_get_contents($f), true);
        if (is_array($map)) $enrich += $map;
    }
    foreach ($items as &$it) {
        $x = $enrich[$it['id']] ?? [];
        $it['i18n'] = $x['i18n'] ?? [];
        $it['content_type'] = $x['content_type'] ?? null;
    }
    unset($it);
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
function card(array $c): void {
    $fold = !empty($c['fold']); ?>
  <article class="card<?= $fold ? ' card-fold' : '' ?>">
    <?php if ($fold): /* origen y meta en una sola línea */ ?>
      <div class="byline"><span class="src"><?= e($c['origin'] ?? '') ?></span><?php foreach (array_filter($c['meta'] ?? []) as $m): ?><span class="sep">·</span><span><?= e($m) ?></span><?php endforeach; ?></div>
    <?php else: ?>
      <div class="origin"><?= e($c['origin'] ?? '') ?></div>
    <?php endif; ?>
    <?php if (!empty($c['title_extra'])): ?><div class="title-row"><?php endif; ?>
    <h3><a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"<?= !empty($c['title_original']) ? ' title="' . e(t('tip.original_title', $c['title_original'])) . '"' : '' ?>><?= e($c['title']) ?></a></h3>
    <?php if (!empty($c['title_extra'])): ?><?= $c['title_extra'] /* HTML ya escapado por quien llama */ ?></div><?php endif; ?>
    <?php if ($fold):
        // Fila visible: chips principales (tipo de contenido) + icono. El resto, desplegable.
        $lead = array_values(array_filter($c['chips'] ?? [], fn($ch) => ($ch['class'] ?? '') === 'ct'));
        $rest = array_values(array_filter($c['chips'] ?? [], fn($ch) => ($ch['class'] ?? '') !== 'ct'));
        $sid = 'sum-' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($c['id'] ?? md5($c['url']))); ?>
      <div class="fold-row">
        <?php chips($lead); ?>
        <?php if (array_key_exists('status', $c)) status_button($c['id'], $c['status']); ?>
        <button type="button" class="round-btn sum-toggle" data-toggle="<?= e($sid) ?>" aria-expanded="false" aria-controls="<?= e($sid) ?>" title="<?= e(t('summary.show')) ?>" aria-label="<?= e(t('summary.show')) ?>">
          <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 6l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
      <div id="<?= e($sid) ?>" class="fold-body" hidden>
        <?php if (!empty($c['summary'])): ?>
          <p class="summary"><?= e($c['summary']) ?></p>
        <?php else: ?>
          <p class="sum-pending"><?= e(t('summary.pending')) ?></p>
        <?php endif; ?>
        <?php chips($rest); ?>
      </div>
    <?php else: ?>
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
    <?php chips($c['chips'] ?? []); ?>
    <?php endif; /* fold */ ?>
    <?php if (!empty($c['notes'])): ?>
      <details><summary><?= e(t('notes')) ?> <?= e(t('notes.only_es')) ?></summary><p lang="es"><?= e($c['notes']) ?></p></details>
    <?php endif; ?>
    <?php if (!empty($c['footer'])): ?><div class="card-foot"><?= $c['footer'] /* HTML ya escapado por quien llama */ ?></div><?php endif; ?>
  </article>
<?php }

/**
 * Botón de estado de valoración (solo local). Cada clic avanza: pendiente -> ok -> destacado -> pendiente.
 * Pinta los tres iconos; el CSS muestra el del estado actual (data-status). Lo gestiona assets/interest.js.
 */
function status_button(string $id, string $status): void {
    $next = ['sin' => 'ok', 'ok' => 'estrella', 'estrella' => 'sin'][$status]; ?>
      <button type="button" class="round-btn status-btn" data-id="<?= e($id) ?>" data-status="<?= e($status) ?>"
              title="<?= e(t('status.' . $status) . ' · ' . t('status.next', t('status.' . $next))) ?>" aria-label="<?= e(t('col.status') . ': ' . t('status.' . $status)) ?>"
              data-labels="<?= e(json_encode(['sin' => t('status.sin'), 'ok' => t('status.ok'), 'estrella' => t('status.estrella'), 'next' => t('status.next'), 'col' => t('col.status')], JSON_UNESCAPED_UNICODE)) ?>">
        <?php foreach (['sin', 'ok', 'estrella'] as $s) echo status_icon($s); ?>
      </button>
<?php }

/** Icono SVG de un estado (sin, ok, estrella). El color lo pone el CSS (.st-*). */
function status_icon(string $status, int $size = 18): string {
    $paths = [
        'sin' => '<circle cx="9" cy="9" r="6.2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2.6 2.4"/>',
        'ok' => '<circle cx="9" cy="9" r="7.5" fill="currentColor"/><path d="M5.6 9.2l2.3 2.3 4.5-4.9" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'estrella' => '<path d="M9 1.8l2.15 4.5 4.9.6-3.6 3.4.95 4.9L9 12.8l-4.4 2.4.95-4.9L2 6.9l4.9-.6z" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linejoin="round"/>',
    ];
    return '<svg class="st-icon i-' . $status . ' st-' . $status . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 18 18" aria-hidden="true">' . ($paths[$status] ?? '') . '</svg>';
}

/** Chips: label, href?, title?, class? (clase extra, p. ej. 'ct' para el tipo de contenido). */
function chips(array $list): void {
    if (!$list) return; ?>
      <div class="chips">
        <?php foreach ($list as $ch): $cls = 'chip' . (!empty($ch['class']) ? ' ' . $ch['class'] : ''); ?>
          <?php if (!empty($ch['href'])): ?>
            <a class="<?= e($cls) ?>" href="<?= e($ch['href']) ?>" title="<?= e($ch['title'] ?? '') ?>"><?= e($ch['label']) ?></a>
          <?php else: ?>
            <span class="<?= e($cls) ?> muted-chip" title="<?= e($ch['title'] ?? '') ?>"><?= e($ch['label']) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
<?php }

/**
 * $byStatus: piezas de la fuente por estado de valoración (solo local) o null.
 * Se muestran como chips con el número en la línea del título: destacado (amarillo), interesante (verde), pendiente (contorno).
 */
function source_card(array $s, int $nItems = 0, ?array $byStatus = null): void {
    $origin = source_text($s, 'type') . ' · ' . source_text($s, 'region');
    if (($s['kind'] ?? null) === 'infrastructure') $origin .= ' · ' . t('infrastructure');
    $foot = $nItems > 0 ? '<a href="index.php?fuente[]=' . e(urlencode($s['id'])) . '">' . e(t('items_in_board', $nItems)) . '</a>' : '';
    $stChips = '';
    if ($nItems > 0 && $byStatus !== null) {
        foreach (['estrella', 'ok', 'sin'] as $st) {
            $n = $byStatus[$st] ?? 0;
            $href = 'index.php?' . http_build_query(['fuente' => [$s['id']], 'estado' => [$st]]);
            $stChips .= '<a class="st-chip st-chip-' . $st . ($n ? '' : ' zero') . '" href="' . e($href) . '" title="' . e(t('status.' . $st) . ': ' . $n) . '">' . $n . '</a>';
        }
        $stChips = '<span class="st-chips">' . $stChips . '</span>';
    }
    card([
        'title_extra' => $stChips,
        'title' => $s['name'],
        'url' => $s['url'],
        'origin' => $origin,
        'meta' => [implode(', ', $s['languages'] ?? []), t('rel.' . ($s['relevance'] ?? '')), 'n=' . ($s['sampling_evaluation']['n_items'] ?? '–')],
        'summary' => source_text($s, 'summary'),
        'scores' => $s,
        'notes' => $s['legacy_evidence_notes'] ?? null,
        'footer' => $foot,
    ]);
}

/**
 * $tb: placeholder (buscador), filters (callable que pinta los controles del panel),
 * hidden (campos ocultos que conserva el formulario).
 * Los controles van con form="tb" para compartir formulario con el buscador.
 */
function layout_start(string $title, string $active, array $tb = []): void {
    $nav = ['index.php' => t('nav.board'), 'fuentes.php' => t('nav.sources'), 'recursos.php' => t('nav.resources')];
    $filters = $tb['filters'] ?? null;
    // Panel abierto si hay algún filtro aplicado (aparte del texto buscado).
    $open = $filters && array_filter(array_diff_key($_GET, ['q' => 1, 'lang' => 1, 'kw' => 1, 'vista' => 1]), fn($v) => $v !== ''); ?>
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

    <div class="dd">
      <button type="button" class="icon-btn" data-toggle="menu-nav" aria-expanded="false" aria-controls="menu-nav" title="<?= e(t('menu')) ?>">
        <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <span class="sr"><?= e(t('menu')) ?></span>
      </button>
      <nav id="menu-nav" class="dd-menu" hidden>
        <?php foreach ($nav as $href => $label): ?><a href="<?= $href ?>"<?= $active === $href ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
      </nav>
    </div>

    <?php if (isset($tb['placeholder'])): ?>
    <form id="tb" class="tb-search" method="get" action="<?= e($active) ?>" role="search">
      <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="4.8" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10.6 10.6 14 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      <input type="search" name="q" value="<?= e(q('q')) ?>" placeholder="<?= e($tb['placeholder']) ?>" aria-label="<?= e($tb['placeholder']) ?>">
      <?php foreach ($tb['hidden'] ?? [] as $k => $v): foreach ((array)$v as $x): if ($x !== ''): ?><input type="hidden" name="<?= e(is_array($v) ? $k . '[]' : $k) ?>" value="<?= e($x) ?>"><?php endif; endforeach; endforeach; ?>
    </form>
    <?php else: ?><div class="tb-spacer"></div><?php endif; ?>

    <?php if ($filters): ?>
    <button type="button" class="icon-btn<?= $open ? ' active' : '' ?>" data-toggle="filter-panel" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="filter-panel" title="<?= e(t('btn.filters')) ?>">
      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M2 4h14M4.5 9h9M7 14h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      <span class="lbl"><?= e(t('btn.filters')) ?></span>
    </button>
    <?php endif; ?>

    <div class="dd">
      <button type="button" class="icon-btn lang-btn" data-toggle="menu-lang" aria-expanded="false" aria-controls="menu-lang" title="<?= e(t('lang.label')) ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6.2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M1.8 8h12.4M8 1.8c-2.2 2.4-2.2 10 0 12.4M8 1.8c2.2 2.4 2.2 10 0 12.4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
        <span><?= strtoupper(LANG) ?></span>
      </button>
      <div id="menu-lang" class="dd-menu dd-right" hidden>
        <?php foreach (LANGS as $code => $name): ?><a href="<?= e(url_with(['lang' => $code])) ?>" hreflang="<?= $code ?>" lang="<?= $code ?>" title="<?= e($name) ?>"<?= LANG === $code ? ' class="on" aria-current="true"' : '' ?>><?= strtoupper($code) ?></a><?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if ($filters): ?>
  <div id="filter-panel" class="filter-panel"<?= $open ? '' : ' hidden' ?>>
    <div class="wrap-p">
      <?php $filters(); ?>
      <button type="submit" form="tb"><?= e(t('btn.apply')) ?></button>
    </div>
  </div>
  <?php endif; ?>
</header>
<main>
<?php }

function layout_end(): void { ?>
  <footer class="foot"><?= e(t('footer')) ?></footer>
</main>
<script src="assets/ui.js" defer></script>
</body>
</html>
<?php }
