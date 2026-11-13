<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/feedback.php';

$sources = load_sources();
$items = load_items();
$local = is_local_request();
$marks = $local ? load_feedback() : [];

$fq = q('q'); $ff = q('fuente'); $fl = q('idioma'); $fk = q('kw'); $fm = $local ? q('marca') : '';

$filtered = array_filter($items, function ($it) use ($sources, $fq, $ff, $fl, $fk, $fm, $marks) {
    $mk = isset($marks[$it['id']]) ? ($marks[$it['id']]['interesting'] ? 'si' : 'no') : 'sin';
    if ($fm !== '' && $fm !== $mk) return false;
    // Búsqueda y keyword valen en cualquier idioma (es, en u original).
    $kws = $it['source_keywords'] ?? [];
    $all = $it['title'] . ' ' . $sources[$it['source_id']]['name'];
    foreach ($it['i18n'] ?? [] as $tx) {
        $all .= ' ' . ($tx['title'] ?? '') . ' ' . ($tx['summary'] ?? '');
        $kws = array_merge($kws, $tx['keywords'] ?? []);
    }
    if (!contains($all . ' ' . implode(' ', $kws), $fq)) return false;
    if ($ff !== '' && $it['source_id'] !== $ff) return false;
    if ($fl !== '' && ($it['language'] ?? '') !== $fl) return false;
    if ($fk !== '' && !in_array($fk, $kws, true)) return false;
    return true;
});

$activeSources = array_values(array_unique(array_column($items, 'source_id')));
$langs = array_values(array_unique(array_filter(array_column($items, 'language'))));
sort($langs);

layout_start(t('board.title'), 'index.php'); ?>
  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($fq) ?>" placeholder="<?= e(t('search.items')) ?>">
    <select name="fuente">
      <option value=""><?= e(t('filter.all_sources')) ?></option>
      <?php foreach ($activeSources as $id): ?>
        <option value="<?= e($id) ?>" <?= $ff === $id ? 'selected' : '' ?>><?= e($sources[$id]['name'] ?? $id) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="idioma">
      <option value=""><?= e(t('filter.all_langs')) ?></option>
      <?php foreach ($langs as $l): ?><option value="<?= e($l) ?>" <?= $fl === $l ? 'selected' : '' ?>><?= e(ucfirst(t('lang.' . $l))) ?></option><?php endforeach; ?>
    </select>
    <?php if ($local): ?>
    <select name="marca">
      <option value=""><?= e(t('mark.all')) ?></option>
      <?php foreach (['si' => 'mark.f_yes', 'no' => 'mark.f_no', 'sin' => 'mark.f_none'] as $k => $lbl): ?>
        <option value="<?= $k ?>" <?= $fm === $k ? 'selected' : '' ?>><?= e(t($lbl)) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <?php if ($fk !== ''): ?><input type="hidden" name="kw" value="<?= e($fk) ?>"><?php endif; ?>
    <button><?= e(t('btn.search')) ?></button>
  </form>
  <?php if ($fk !== ''): ?>
    <div class="active-filters"><?= e(t('filter.keyword')) ?>: <span class="chip"><?= e($fk) ?></span> <a href="<?= e(url_with(['kw' => null])) ?>"><?= e(t('filter.remove')) ?></a></div>
  <?php endif; ?>
  <?php if (($fq . $ff . $fl . $fk . $fm) !== ''): ?>
    <div class="count"><?= e(t('count.items', count($filtered))) ?> · <a href="index.php"><?= e(t('filter.clear')) ?></a></div>
  <?php endif; ?>

  <?php if (!$filtered): ?>
    <div class="empty"><?= e(t('empty.items')) ?></div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($filtered as $it):
        $chips = [];
        foreach (item_text($it, 'keywords') as $k) $chips[] = ['label' => $k, 'href' => url_with(['kw' => $k]), 'title' => t('tip.keyword')];
        if (!empty($it['language']) && $it['language'] !== LANG) $chips[] = ['label' => t('chip.original', t('lang.' . $it['language'])), 'title' => t('tip.lang')];
        if (!empty($it['content_type'])) $chips[] = ['label' => t('ct.' . $it['content_type']), 'title' => t('tip.type')];
        $title = item_text($it, 'title');
        $extra = $local ? ['id' => $it['id'], 'mark' => isset($marks[$it['id']]) ? (bool)$marks[$it['id']]['interesting'] : null] : [];
        card($extra + [
            'title' => $title,
            'title_original' => $title !== $it['title'] ? $it['title'] : null,
            'url' => $it['url'],
            'origin' => $sources[$it['source_id']]['name'] ?? $it['source_id'],
            'meta' => [$it['published_at'] ?? t('no_date'), implode(', ', $it['authors'] ?? [])],
            'summary' => item_text($it, 'summary'),
            'chips' => $chips,
        ]);
    endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($local): ?><script src="assets/interest.js" defer></script><?php endif; ?>
<?php layout_end();
