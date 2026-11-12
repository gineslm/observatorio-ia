<?php
// Página de listado de fuentes reutilizada por Fuentes y Recursos.
declare(strict_types=1);

function sources_page(string $title, string $active, string $lead, callable $belongs): void {
    $all = array_filter(load_sources(), $belongs);
    $counts = array_count_values(array_column(load_items(), 'source_id'));

    $fq = q('q'); $fr = q('rel'); $fc = q('crit'); $fv = q('val');
    $list = array_filter($all, function ($s) use ($fq, $fr, $fc, $fv) {
        if (!contains($s['name'] . ' ' . ($s['summary'] ?? '') . ' ' . ($s['type'] ?? '') . ' ' . ($s['region'] ?? ''), $fq)) return false;
        if ($fr !== '' && ($s['relevance'] ?? '') !== $fr) return false;
        if (isset(CRITERIA[$fc]) && $fv !== '' && criterion($s, $fc)['value'] !== $fv) return false;
        return true;
    });

    layout_start($title, $active); ?>
  <h1><?= e($title) ?></h1>
  <p class="lead"><?= e($lead) ?></p>
  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($fq) ?>" placeholder="Buscar nombre, descripción, tipo, región">
    <select name="rel">
      <option value="">Relevancia: todas</option>
      <?php foreach (['nucleo' => 'Núcleo', 'adyacente' => 'Adyacente'] as $k => $v): ?>
        <option value="<?= $k ?>" <?= $fr === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="crit">
      <option value="">Criterio</option>
      <?php foreach (CRITERIA as $k => $v): ?><option value="<?= $k ?>" <?= $fc === $k ? 'selected' : '' ?>><?= strtoupper($k) ?> <?= e($v) ?></option><?php endforeach; ?>
    </select>
    <select name="val">
      <option value="">Valor</option>
      <?php foreach (['Alto', 'Medio', 'Bajo', 'NE'] as $v): ?><option <?= $fv === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
    </select>
    <button>Filtrar</button>
  </form>
  <div class="count"><?= count($list) ?> de <?= count($all) ?><?= ($fq . $fr . $fc . $fv) !== '' ? ' · <a href="' . e($active) . '">limpiar filtros</a>' : '' ?></div>
  <?php if (!$list): ?>
    <div class="empty">Nada coincide con estos filtros.</div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($list as $s) source_card($s, $counts[$s['id']] ?? 0); ?>
  </div>
  <?php endif;
    layout_end();
}
