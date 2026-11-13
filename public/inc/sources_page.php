<?php
// Página de listado de fuentes reutilizada por Fuentes y Recursos.
declare(strict_types=1);
require_once __DIR__ . '/feedback.php';

function sources_page(string $titleKey, string $active, string $leadKey, callable $belongs): void {
    $all = array_filter(load_sources(), $belongs);
    $counts = array_count_values(array_column(load_items(), 'source_id'));
    // Piezas por fuente y estado de valoración (en local y, en solo lectura, online).
    $byStatus = null;
    if (status_visible()) {
        $marks = load_feedback();
        $byStatus = [];
        foreach (load_items() as $it) {
            $st = item_status($marks[$it['id']] ?? null);
            $byStatus[$it['source_id']][$st] = ($byStatus[$it['source_id']][$st] ?? 0) + 1;
        }
    }

    $fq = q('q'); $fr = q('rel'); $fc = q('crit'); $fv = q('val');
    $list = array_filter($all, function ($s) use ($fq, $fr, $fc, $fv) {
        $text = $s['name'] . ' ' . ($s['type'] ?? '') . ' ' . ($s['region'] ?? '');
        foreach ($s['i18n'] ?? [] as $tx) $text .= ' ' . implode(' ', array_filter($tx));
        if (!contains($text, $fq)) return false;
        if ($fr !== '' && ($s['relevance'] ?? '') !== $fr) return false;
        if (in_array($fc, CRITERIA, true) && $fv !== '' && criterion($s, $fc)['value'] !== $fv) return false;
        return true;
    });

    layout_start(t($titleKey), $active, [
        'placeholder' => t('search.sources'),
        'filters' => function () use ($fr, $fc, $fv) { ?>
    <select name="rel" form="tb" aria-label="<?= e(t('filter.rel_all')) ?>">
      <option value=""><?= e(t('filter.rel_all')) ?></option>
      <?php foreach (['nucleo', 'adyacente'] as $k): ?>
        <option value="<?= $k ?>" <?= $fr === $k ? 'selected' : '' ?>><?= e(ucfirst(t('rel.' . $k))) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="crit" form="tb" aria-label="<?= e(t('filter.criterion')) ?>">
      <option value=""><?= e(t('filter.criterion')) ?></option>
      <?php foreach (CRITERIA as $k): ?><option value="<?= $k ?>" <?= $fc === $k ? 'selected' : '' ?>><?= strtoupper($k) ?> <?= e(t('crit.' . $k)) ?></option><?php endforeach; ?>
    </select>
    <select name="val" form="tb" aria-label="<?= e(t('filter.value')) ?>">
      <option value=""><?= e(t('filter.value')) ?></option>
      <?php foreach (['Alto', 'Medio', 'Bajo', 'NE'] as $v): ?><option value="<?= $v ?>" <?= $fv === $v ? 'selected' : '' ?>><?= e(t('score.' . $v)) ?></option><?php endforeach; ?>
    </select>
<?php }]); ?>
  <h1><?= e(t($titleKey)) ?></h1>
  <p class="lead"><?= e(t($leadKey)) ?></p>
  <div class="count"><?= e(t('count.of', count($list), count($all))) ?><?= ($fq . $fr . $fc . $fv) !== '' ? ' · <a href="' . e($active) . '">' . e(t('filter.clear')) . '</a>' : '' ?></div>
  <?php if (!$list): ?>
    <div class="empty"><?= e(t('empty.sources')) ?></div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($list as $s) source_card($s, $counts[$s['id']] ?? 0, $byStatus === null ? null : ($byStatus[$s['id']] ?? [])); ?>
  </div>
  <?php endif;
    layout_end();
}
