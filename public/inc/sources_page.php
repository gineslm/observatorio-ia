<?php
// Página de listado de fuentes reutilizada por Fuentes y Recursos.
declare(strict_types=1);

function sources_page(string $titleKey, string $active, string $leadKey, callable $belongs): void {
    $all = array_filter(load_sources(), $belongs);
    $counts = array_count_values(array_column(load_items(), 'source_id'));

    $fq = q('q'); $fr = q('rel'); $fc = q('crit'); $fv = q('val');
    $list = array_filter($all, function ($s) use ($fq, $fr, $fc, $fv) {
        $text = $s['name'] . ' ' . ($s['type'] ?? '') . ' ' . ($s['region'] ?? '');
        foreach ($s['i18n'] ?? [] as $tx) $text .= ' ' . implode(' ', array_filter($tx));
        if (!contains($text, $fq)) return false;
        if ($fr !== '' && ($s['relevance'] ?? '') !== $fr) return false;
        if (in_array($fc, CRITERIA, true) && $fv !== '' && criterion($s, $fc)['value'] !== $fv) return false;
        return true;
    });

    layout_start(t($titleKey), $active); ?>
  <h1><?= e(t($titleKey)) ?></h1>
  <p class="lead"><?= e(t($leadKey)) ?></p>
  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($fq) ?>" placeholder="<?= e(t('search.sources')) ?>">
    <select name="rel">
      <option value=""><?= e(t('filter.rel_all')) ?></option>
      <?php foreach (['nucleo', 'adyacente'] as $k): ?>
        <option value="<?= $k ?>" <?= $fr === $k ? 'selected' : '' ?>><?= e(ucfirst(t('rel.' . $k))) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="crit">
      <option value=""><?= e(t('filter.criterion')) ?></option>
      <?php foreach (CRITERIA as $k): ?><option value="<?= $k ?>" <?= $fc === $k ? 'selected' : '' ?>><?= strtoupper($k) ?> <?= e(t('crit.' . $k)) ?></option><?php endforeach; ?>
    </select>
    <select name="val">
      <option value=""><?= e(t('filter.value')) ?></option>
      <?php foreach (['Alto', 'Medio', 'Bajo', 'NE'] as $v): ?><option value="<?= $v ?>" <?= $fv === $v ? 'selected' : '' ?>><?= e(t('score.' . $v)) ?></option><?php endforeach; ?>
    </select>
    <button><?= e(t('btn.filter')) ?></button>
  </form>
  <div class="count"><?= e(t('count.of', count($list), count($all))) ?><?= ($fq . $fr . $fc . $fv) !== '' ? ' · <a href="' . e($active) . '">' . e(t('filter.clear')) . '</a>' : '' ?></div>
  <?php if (!$list): ?>
    <div class="empty"><?= e(t('empty.sources')) ?></div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($list as $s) source_card($s, $counts[$s['id']] ?? 0); ?>
  </div>
  <?php endif;
    layout_end();
}
