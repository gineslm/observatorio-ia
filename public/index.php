<?php
require __DIR__ . '/inc/app.php';
require_once __DIR__ . '/inc/feedback.php';
require __DIR__ . '/inc/grid.php';

$sources = load_sources();
$items = load_items();
$local = is_local_request();
$marks = $local ? load_feedback() : [];

// Vista del listado: tarjetas o tabla. Se recuerda en una cookie, como el idioma.
$view = q('vista');
if (in_array($view, ['tarjetas', 'tabla'], true)) {
    setcookie('vista', $view, ['expires' => time() + 31536000, 'path' => BASE_PATH, 'samesite' => 'Lax']);
} else {
    $view = ($_COOKIE['vista'] ?? '') === 'tabla' ? 'tabla' : 'tarjetas';
}

// Filtros: selección múltiple por columna, en la URL.
$filters = board_filters($sources, $marks, $local);
$sel = [];
foreach ($filters as $k => $_) $sel[$k] = qa($k);
$fq = q('q');
[$filtered, $opts] = apply_filters($items, $filters, $sel, $fq, $sources);
$cfg = grid_config();

// Campos que conserva el buscador del toolbar al enviar.
$keep = [];
foreach ($sel as $k => $vs) if ($vs) $keep[$k] = $vs;

layout_start(t('board.title'), 'index.php', ['placeholder' => t('search.items'), 'hidden' => $keep]); ?>
  <div class="list-bar">
    <div class="count"><?= e(t('count.items', count($filtered))) ?></div>
    <div class="list-tools">
      <?php if ($view === 'tabla'): ?>
      <button type="button" class="tool-btn" data-toggle="pop-cols" aria-expanded="false" aria-controls="pop-cols" title="<?= e(t('cols.btn')) ?>">
        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2.5" width="12" height="11" rx="1.5"/><path d="M6 2.5v11M10 2.5v11"/></svg><span class="lbl"><?= e(t('cols.btn')) ?></span>
      </button>
      <?php endif; ?>
      <div class="seg" role="group" aria-label="<?= e(t('view.label')) ?>">
        <?php foreach (['tarjetas' => ['view.cards', '<rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/><rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/>'],
                        'tabla' => ['view.table', '<path d="M2 3.5h12M2 8h12M2 12.5h12"/>']] as $v => [$lbl, $ico]): ?>
          <a href="<?= e(url_with(['vista' => $v])) ?>" class="seg-btn<?= $view === $v ? ' on' : '' ?>"<?= $view === $v ? ' aria-current="true"' : '' ?> title="<?= e(t($lbl)) ?>">
            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><?= $ico ?></svg><span class="lbl"><?= e(t($lbl)) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php active_filter_chips($filters, $sel, $fq); ?>

  <?php
  // Datos de cada pieza, comunes a las dos vistas.
  $rows = [];
  foreach ($filtered as $it) {
      $chips = [];
      // Primero el tipo de contenido (noticia, análisis…), después keywords e idioma.
      if (!empty($it['content_type'])) $chips[] = ['label' => t('ct.' . $it['content_type']), 'title' => t('tip.type'), 'class' => 'ct'];
      foreach (item_text($it, 'keywords') as $k) $chips[] = ['label' => $k, 'href' => url_with(['kw' => array_values(array_unique(array_merge($sel['kw'], [$k])))]), 'title' => t('tip.keyword')];
      if (!empty($it['language']) && $it['language'] !== LANG) $chips[] = ['label' => t('chip.original', strtoupper($it['language'])), 'title' => t('tip.lang') . ': ' . ucfirst(t('lang.' . $it['language']))];
      $title = item_text($it, 'title');
      $date = $it['published_at'] ?? t('no_date');
      $author = implode(', ', $it['authors'] ?? []);
      $extra = $local ? ['status' => item_status($marks[$it['id']] ?? null)] : [];
      $rows[] = $extra + [
          'id' => $it['id'],
          'title' => $title,
          'title_original' => $title !== $it['title'] ? $it['title'] : null,
          'url' => $it['url'],
          'origin' => $sources[$it['source_id']]['name'] ?? $it['source_id'],
          'date' => $date,
          'author' => $author,
          'lang' => $it['language'] ?? '',
          'meta' => [$date, $author],
          'summary' => item_text($it, 'summary'),
          'chips' => $chips,
          'fold' => true,
      ];
  }
  ?>
  <?php if ($view === 'tabla'): ?>
  <div class="table-wrap">
    <table class="items" data-kw="<?= e(implode(',', array_keys(array_filter(grid_columns(), fn($c) => !empty($c['kw']))))) ?>">
      <?php grid_head($cfg, $sel); ?>
      <?php foreach ($rows as $r) grid_row($r, $cfg); ?>
    </table>
    <?php if (!$rows): ?><div class="empty in-table"><?= e(t('empty.items')) ?></div><?php endif; ?>
  </div>
  <?php filter_popovers($filters, $sel, $opts); ?>
  <?php columns_popover($cfg); ?>
  <script src="assets/grid.js" defer></script>
  <?php elseif (!$rows): ?>
    <div class="empty"><?= e(t('empty.items')) ?></div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($rows as $r) card($r); ?>
  </div>
  <?php endif; ?>
  <?php if ($local): ?><script src="assets/interest.js" defer></script><?php endif; ?>
<?php layout_end();
