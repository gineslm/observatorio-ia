<?php
// Tablero: filtros por columna (selección múltiple, en la URL) y tabla con columnas configurables (cookie).
declare(strict_types=1);

/** Valores de un parámetro múltiple (?p[]=a&p[]=b). Acepta también ?p=a de enlaces antiguos. */
function qa(string $k): array {
    $v = $_GET[$k] ?? [];
    $v = is_array($v) ? $v : [$v];
    return array_values(array_unique(array_filter($v, fn($x) => is_string($x) && trim($x) !== '')));
}

/** Campos ocultos con los parámetros actuales, salvo $except. */
function hidden_inputs(array $except = []): void {
    foreach ($_GET as $k => $v) {
        if (in_array($k, $except, true)) continue;
        foreach ((array)$v as $x) {
            if (!is_string($x) || $x === '') continue; ?>
      <input type="hidden" name="<?= e(is_array($v) ? $k . '[]' : $k) ?>" value="<?= e($x) ?>">
<?php   }
    }
}

const NONE = '_';  // valor para «sin dato» (sin tipo, sin autor…)

/**
 * Filtros del tablero. values: valores de una pieza; label: texto de un valor; order: orden de las opciones (opcional).
 * Dentro de un filtro, los valores se combinan con O; entre filtros, con Y.
 */
function board_filters(array $sources, array $marks, bool $local): array {
    $none = fn($arr) => $arr ?: [NONE];
    $f = [
        'fuente' => ['col' => 'col.source', 'values' => fn($it) => [$it['source_id']],
                     'label' => fn($v) => $sources[$v]['name'] ?? $v],
        'tipo'   => ['col' => 'col.type', 'values' => fn($it) => [$it['content_type'] ?: NONE],
                     'label' => fn($v) => $v === NONE ? t('filter.none') : t('ct.' . $v)],
        'autor'  => ['col' => 'col.author', 'values' => fn($it) => $none($it['authors'] ?? []),
                     'label' => fn($v) => $v === NONE ? t('filter.none') : $v],
        'idioma' => ['col' => 'col.lang', 'values' => fn($it) => [$it['language'] ?? NONE],
                     'label' => fn($v) => $v === NONE ? t('filter.none') : strtoupper($v)],
        // Keywords: en cualquier idioma (original, es, en). Se añaden desde los chips, no tienen columna.
        'kw'     => ['col' => 'filter.keyword', 'values' => function ($it) {
                         $k = $it['source_keywords'] ?? [];
                         foreach ($it['i18n'] ?? [] as $tx) $k = array_merge($k, $tx['keywords'] ?? []);
                         return $k;
                     }, 'label' => fn($v) => $v],
    ];
    if ($local) {
        $f['estado'] = ['col' => 'col.status', 'values' => fn($it) => [item_status($marks[$it['id']] ?? null)],
                        'label' => fn($v) => t('status.' . $v), 'order' => STATUSES,
                        'icon' => fn($v) => status_icon($v, 16)];
    }
    return $f;
}

/** Texto en el que busca el buscador del toolbar (cualquier idioma). */
function item_haystack(array $it, array $sources): string {
    $s = $it['title'] . ' ' . ($sources[$it['source_id']]['name'] ?? '') . ' ' . implode(' ', $it['source_keywords'] ?? []);
    foreach ($it['i18n'] ?? [] as $tx) $s .= ' ' . ($tx['title'] ?? '') . ' ' . ($tx['summary'] ?? '') . ' ' . implode(' ', $tx['keywords'] ?? []);
    return $s;
}

/**
 * Aplica búsqueda y filtros. Devuelve [piezas filtradas, opciones por filtro].
 * Las opciones de cada filtro se cuentan sobre las piezas que pasan los demás filtros (facetas).
 */
function apply_filters(array $items, array $filters, array $sel, string $q, array $sources): array {
    $vals = [];  // valores precalculados por pieza y filtro
    $items = array_values(array_filter($items, fn($it) => contains(item_haystack($it, $sources), $q)));
    foreach ($items as $i => $it) foreach ($filters as $k => $f) $vals[$i][$k] = $f['values']($it);

    $pass = function (int $i, ?string $except) use ($filters, $sel, &$vals): bool {
        foreach ($filters as $k => $_) {
            if ($k === $except || !$sel[$k]) continue;
            if (!array_intersect($sel[$k], $vals[$i][$k])) return false;
        }
        return true;
    };

    $out = []; $opts = array_fill_keys(array_keys($filters), []);
    foreach ($items as $i => $it) {
        if ($pass($i, null)) $out[] = $it;
        foreach ($filters as $k => $_) {
            if (!$pass($i, $k)) continue;
            foreach (array_unique($vals[$i][$k]) as $v) $opts[$k][$v] = ($opts[$k][$v] ?? 0) + 1;
        }
    }
    // Los valores seleccionados se muestran siempre, aunque ahora no tengan piezas.
    foreach ($sel as $k => $vs) foreach ($vs as $v) $opts[$k][$v] = $opts[$k][$v] ?? 0;
    foreach ($opts as $k => &$o) {
        $lbl = $filters[$k]['label'];
        $ord = $filters[$k]['order'] ?? null;  // orden propio (p. ej. estados); si no, alfabético con «sin dato» al final
        if ($ord) $o += array_fill_keys($ord, 0);  // con orden propio se muestran siempre todos los valores
        uksort($o, $ord
            ? fn($a, $b) => array_search((string)$a, $ord, true) <=> array_search((string)$b, $ord, true)
            : fn($a, $b) => ($a === NONE) <=> ($b === NONE) ?: strnatcasecmp((string)$lbl((string)$a), (string)$lbl((string)$b)));
    }
    unset($o);
    return [$out, $opts];
}

/** Chips de filtros activos, cada uno con su × para quitarlo. */
function active_filter_chips(array $filters, array $sel, string $q): void {
    $chips = [];
    if ($q !== '') $chips[] = [t('filter.q'), '«' . $q . '»', url_with(['q' => null]), ''];
    foreach ($filters as $k => $f) foreach ($sel[$k] as $v) {
        $chips[] = [t($f['col']), $f['label']($v), url_with([$k => array_values(array_diff($sel[$k], [$v]))]), isset($f['icon']) ? $f['icon']($v) : ''];
    }
    if (!$chips) return; ?>
  <div class="fchips">
    <?php foreach ($chips as [$name, $val, $href, $icon]): ?>
      <a class="fchip" href="<?= e($href) ?>" title="<?= e(t('filter.remove')) ?>"><span class="k"><?= e($name) ?>:</span> <?= $icon /* SVG propio */ ?><?= e($val) ?><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 3l6 6M9 3l-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span class="sr"><?= e(t('filter.remove')) ?></span></a>
    <?php endforeach; ?>
    <?php if (count($chips) > 1): ?><a class="fchips-clear" href="index.php"><?= e(t('filter.clear_all')) ?></a><?php endif; ?>
  </div>
<?php }

// ---------- columnas de la tabla ----------
/**
 * Columnas de la tabla, en orden fijo. filter: parámetro del filtro. fixed: no se puede ocultar.
 * kw: en la fila desplegable, la columna queda en la zona de keywords (el resto, en la del resumen).
 */
function grid_columns(): array {
    return [
        'title'  => ['label' => 'col.title', 'fixed' => true],
        'source' => ['label' => 'col.source', 'filter' => 'fuente'],
        'author' => ['label' => 'col.author', 'filter' => 'autor', 'kw' => true],
        'date'   => ['label' => 'col.date', 'kw' => true],
        'type'   => ['label' => 'col.type', 'filter' => 'tipo', 'kw' => true],
        'lang'   => ['label' => 'col.lang', 'filter' => 'idioma', 'off' => true, 'kw' => true],
    ] + (function_exists('is_local_request') && is_local_request()
        ? ['status' => ['label' => 'col.status', 'filter' => 'estado', 'kw' => true]] : []);
}

/**
 * Visibilidad desde la cookie «cols» ("source,title,-lang": con «-» = oculta). El orden es fijo (grid_columns).
 * Columnas que la cookie no menciona: visibilidad por defecto. Devuelve [clave => visible].
 */
function grid_config(): array {
    $tokens = explode(',', (string)($_COOKIE['cols'] ?? ''));
    $cfg = [];
    foreach (grid_columns() as $key => $c) {
        $visible = in_array($key, $tokens, true) ? true : (in_array('-' . $key, $tokens, true) ? false : empty($c['off']));
        $cfg[$key] = $visible || !empty($c['fixed']);
    }
    return $cfg;
}

function filter_button(string $param, bool $active, string $label): void { ?>
  <button type="button" class="fbtn<?= $active ? ' on' : '' ?>" data-toggle="pop-<?= e($param) ?>" aria-expanded="false" aria-controls="pop-<?= e($param) ?>" title="<?= e(t('filter.by', $label)) ?>" aria-label="<?= e(t('filter.by', $label)) ?>">
    <svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M1.5 2h9L7 6.5V10L5 9V6.5z" fill="<?= $active ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>
  </button>
<?php }

/** Cabecera de la tabla según la configuración. */
function grid_head(array $cfg, array $sel): void {
    $cols = grid_columns(); ?>
      <thead><tr>
        <?php foreach ($cfg as $key => $on): $c = $cols[$key]; ?>
          <th class="c-<?= $key ?><?= $on ? '' : ' col-off' ?>" data-col="<?= $key ?>"><span class="th"><?= e(t($c['label'])) ?><?php if (!empty($c['filter'])) filter_button($c['filter'], (bool)$sel[$c['filter']], t($c['label'])); ?></span></th>
        <?php endforeach; ?>
        <th class="c-act"><span class="sr"><?= e(t('summary.show')) ?></span></th>
      </tr></thead>
<?php }

/** Celda de una columna para una fila. */
function grid_cell(string $key, array $r, bool $on): void {
    $cls = 'c-' . $key . ($on ? '' : ' col-off'); ?>
          <td class="<?= $cls ?>" data-col="<?= $key ?>"<?= $key === 'author' ? ' title="' . e($r['author']) . '"' : '' ?>><?php
    switch ($key) {
        case 'date': echo e($r['date']); break;
        case 'source': echo e($r['origin']); break;
        case 'title': ?><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"<?= !empty($r['title_original']) ? ' title="' . e(t('tip.original_title', $r['title_original'])) . '"' : '' ?>><?= e($r['title']) ?></a><?php break;
        case 'type': chips(array_values(array_filter($r['chips'], fn($ch) => ($ch['class'] ?? '') === 'ct'))); break;
        case 'author': echo e($r['author']); break;
        case 'lang': if ($r['lang']) { ?><abbr class="lang-code" title="<?= e(ucfirst(t('lang.' . $r['lang']))) ?>"><?= e(strtoupper($r['lang'])) ?></abbr><?php } break;
        case 'status': if (isset($r['status'])) status_button($r['id'], $r['status']); break;
    } ?></td>
<?php }

/**
 * Reparto de la fila desplegable: [columnas antes del título, columnas del resumen, columnas de keywords].
 * El resumen va del título hasta la primera columna «kw» visible; las keywords, desde ahí hasta el final
 * (incluida la columna de acciones). Misma regla en assets/grid.js.
 */
function detail_spans(array $cfg): array {
    $cols = grid_columns();
    $vis = array_keys(array_filter($cfg));
    $i = (int)array_search('title', $vis, true);
    $j = count($vis);
    foreach ($vis as $n => $key) if ($n > $i && !empty($cols[$key]['kw'])) { $j = $n; break; }
    return [$i, $j - $i, count($vis) - $j + 1];
}

/** Fila de la tabla + fila desplegable con resumen y keywords. */
function grid_row(array $r, array $cfg): void {
    $rest = array_values(array_filter($r['chips'], fn($ch) => ($ch['class'] ?? '') !== 'ct'));
    $sid = 'row-' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$r['id']); ?>
      <tbody class="it">
        <tr>
          <?php foreach ($cfg as $key => $on) grid_cell($key, $r, $on); ?>
          <td class="c-act"><div class="act">
            <button type="button" class="round-btn sum-toggle" data-toggle="<?= e($sid) ?>" aria-expanded="false" aria-controls="<?= e($sid) ?>" title="<?= e(t('summary.show')) ?>" aria-label="<?= e(t('summary.show')) ?>">
              <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 6l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div></td>
        </tr>
        <?php [$pre, $mid, $post] = detail_spans($cfg); ?>
        <tr id="<?= e($sid) ?>" class="detail" hidden>
          <td class="d-pre" colspan="<?= max(1, $pre) ?>"<?= $pre ? '' : ' hidden' ?>></td>
          <td class="d-sum" colspan="<?= $mid ?>">
            <?php if (!empty($r['summary'])): ?><p class="summary"><?= e($r['summary']) ?></p>
            <?php else: ?><p class="sum-pending"><?= e(t('summary.pending')) ?></p><?php endif; ?>
          </td>
          <td class="d-kw" colspan="<?= $post ?>"><?php chips($rest); ?></td>
        </tr>
      </tbody>
<?php }

/** Desplegables de filtro (uno por filtro con columna). Se envían por GET conservando el resto de parámetros. */
function filter_popovers(array $filters, array $sel, array $opts): void {
    foreach ($filters as $k => $f):
        if ($k === 'kw') continue;
        $many = count($opts[$k]) > 8; ?>
  <div id="pop-<?= e($k) ?>" class="pop" hidden>
    <form method="get" action="index.php">
      <?php hidden_inputs([$k]); ?>
      <div class="pop-title"><?= e(t('filter.by', t($f['col']))) ?></div>
      <?php if ($many): ?><input type="search" class="pop-find" placeholder="<?= e(t('filter.search')) ?>" aria-label="<?= e(t('filter.search')) ?>"><?php endif; ?>
      <div class="pop-list">
        <?php foreach ($opts[$k] as $v => $n): $v = (string)$v; ?>
          <label class="<?= $n ? '' : 'zero' ?>"><input type="checkbox" name="<?= e($k) ?>[]" value="<?= e($v) ?>"<?= in_array($v, $sel[$k], true) ? ' checked' : '' ?>><?= isset($f['icon']) ? $f['icon']($v) : '' ?><span class="l"><?= e($f['label']($v)) ?></span><span class="n"><?= $n ?></span></label>
        <?php endforeach; ?>
      </div>
      <div class="pop-foot">
        <a href="<?= e(url_with([$k => null])) ?>"><?= e(t('btn.clear')) ?></a>
        <button type="submit"><?= e(t('btn.apply')) ?></button>
      </div>
    </form>
  </div>
<?php endforeach;
}

/** Panel de columnas: mostrar/ocultar (orden fijo). Lo gestiona assets/grid.js. */
function columns_popover(array $cfg): void {
    $cols = grid_columns(); ?>
  <div id="pop-cols" class="pop pop-cols" hidden>
    <div class="pop-title"><?= e(t('cols.btn')) ?></div>
    <ul class="col-list" data-base="<?= e(BASE_PATH) ?>">
      <?php foreach ($cfg as $key => $on): $fixed = !empty($cols[$key]['fixed']); ?>
        <li data-col="<?= $key ?>">
          <label><input type="checkbox"<?= $on ? ' checked' : '' ?><?= $fixed ? ' disabled' : '' ?>> <?= e(t($cols[$key]['label'])) ?></label>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php }
