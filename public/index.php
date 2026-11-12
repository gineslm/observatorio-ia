<?php
require __DIR__ . '/inc/app.php';

$sources = load_sources();
$items = load_items();

$fq = q('q'); $ff = q('fuente'); $fl = q('idioma'); $fk = q('kw');

$filtered = array_filter($items, function ($it) use ($sources, $fq, $ff, $fl, $fk) {
    $src = $sources[$it['source_id']]['name'] ?? '';
    $kws = $it['source_keywords'] ?? [];
    if (!contains($it['title'] . ' ' . ($it['summary'] ?? '') . ' ' . $src . ' ' . implode(' ', $kws), $fq)) return false;
    if ($ff !== '' && $it['source_id'] !== $ff) return false;
    if ($fl !== '' && ($it['language'] ?? '') !== $fl) return false;
    if ($fk !== '' && !in_array($fk, $kws, true)) return false;
    return true;
});

$activeSources = array_unique(array_column($items, 'source_id'));
$langs = array_unique(array_filter(array_column($items, 'language')));
sort($langs);
$pending = count(array_filter($items, fn($it) => empty($it['summary'])));

layout_start('Tablero', 'index.php'); ?>
  <h1>Tablero</h1>
  <p class="lead">Últimas piezas capturadas de las fuentes del registro.</p>
  <div class="stats">
    <div class="stat"><b><?= count($items) ?></b><span>piezas</span></div>
    <div class="stat"><b><?= count($activeSources) ?></b><span>fuentes con piezas</span></div>
    <div class="stat"><b><?= count($sources) ?></b><span>fuentes en el registro</span></div>
    <div class="stat"><b><?= $pending ?></b><span>resúmenes pendientes</span></div>
  </div>

  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($fq) ?>" placeholder="Buscar en títulos, resúmenes, fuentes y keywords">
    <select name="fuente">
      <option value="">Todas las fuentes</option>
      <?php foreach ($activeSources as $id): ?>
        <option value="<?= e($id) ?>" <?= $ff === $id ? 'selected' : '' ?>><?= e($sources[$id]['name'] ?? $id) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="idioma">
      <option value="">Todos los idiomas</option>
      <?php foreach ($langs as $l): ?><option <?= $fl === $l ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
    <?php if ($fk !== ''): ?><input type="hidden" name="kw" value="<?= e($fk) ?>"><?php endif; ?>
    <button>Buscar</button>
  </form>
  <?php if ($fk !== ''): ?>
    <div class="active-filters">Keyword: <span class="chip"><?= e($fk) ?></span> <a href="<?= e(url_with(['kw' => null])) ?>">quitar</a></div>
  <?php endif; ?>
  <div class="count"><?= count($filtered) ?> piezas<?= ($fq . $ff . $fl . $fk) !== '' ? ' · <a href="index.php">limpiar filtros</a>' : '' ?></div>

  <?php if (!$filtered): ?>
    <div class="empty">No hay piezas con estos filtros.</div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($filtered as $it):
        $chips = [];
        foreach ($it['source_keywords'] ?? [] as $k) $chips[] = ['label' => $k, 'href' => url_with(['kw' => $k]), 'title' => 'Keyword declarada por la fuente'];
        if (!empty($it['language'])) $chips[] = ['label' => strtoupper($it['language']), 'title' => 'Idioma'];
        if (!empty($it['content_type'])) $chips[] = ['label' => $it['content_type'], 'title' => 'Tipo de contenido'];
        card([
            'title' => $it['title'],
            'url' => $it['url'],
            'origin' => $sources[$it['source_id']]['name'] ?? $it['source_id'],
            'meta' => [$it['published_at'] ?? 'sin fecha', implode(', ', $it['authors'] ?? [])],
            'summary' => $it['summary'] ?? null,
            'chips' => $chips,
        ]);
    endforeach; ?>
  </div>
  <?php endif; ?>
<?php layout_end();
