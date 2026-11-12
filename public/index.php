<?php
// Visor de solo lectura: los JSON de data/ son la fuente de verdad.
declare(strict_types=1);

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$dataDir = dirname(__DIR__) . '/data';
$raw = @file_get_contents($dataDir . '/sources.json');
$db = $raw ? json_decode($raw, true) : null;
if (!is_array($db) || !isset($db['sources'])) {
    http_response_code(500);
    exit('No se pudo leer data/sources.json');
}
$sources = $db['sources'];

// Piezas: todos los data/items/*.json (vacío de momento).
$items = [];
foreach (glob($dataDir . '/items/*.json') ?: [] as $f) {
    $arr = json_decode((string)file_get_contents($f), true);
    if (is_array($arr)) $items = array_merge($items, $arr);
}

// Filtros por GET.
$q   = trim((string)($_GET['q'] ?? ''));
$rel = (string)($_GET['rel'] ?? '');
$crit = (string)($_GET['crit'] ?? '');
$val = (string)($_GET['val'] ?? '');
$crits = ['c1' => 'Autoría', 'c2' => 'Verificabilidad', 'c3' => 'Financiación', 'c4' => 'Incertidumbre', 'c5' => 'Hecho/opinión'];

function crit(array $s, string $c): array {
    $group = in_array($c, ['c1', 'c3'], true) ? 'institutional_evaluation' : 'sampling_evaluation';
    return $s[$group][$c] ?? ['value' => 'NE', 'evidence_level' => null];
}

$filtered = array_filter($sources, function ($s) use ($q, $rel, $crit, $val, $crits) {
    if ($q !== '' && stripos($s['name'] . ' ' . ($s['type'] ?? '') . ' ' . ($s['region'] ?? ''), $q) === false) return false;
    if ($rel !== '' && ($s['relevance'] ?? '') !== $rel) return false;
    if ($crit !== '' && isset($crits[$crit]) && $val !== '' && crit($s, $crit)['value'] !== $val) return false;
    return true;
});
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Observatorio IA</title>
<style>
  :root { --bg:#fafaf8; --fg:#1d1d1b; --muted:#6b6b66; --line:#e3e2dc; --card:#fff;
          --alto:#2f7d4f; --medio:#b07d12; --bajo:#b33a3a; --ne:#8a8a85; --warn:#fff4d6; }
  @media (prefers-color-scheme: dark) { :root { --bg:#171716; --fg:#ecebe6; --muted:#9d9c96; --line:#33322f; --card:#1f1f1d; --warn:#3a3218; } }
  * { box-sizing: border-box; }
  body { margin:0; font:15px/1.5 system-ui, sans-serif; background:var(--bg); color:var(--fg); }
  main { max-width:1200px; margin:0 auto; padding:24px 16px 64px; }
  h1 { margin:0 0 4px; font-size:1.6rem; }
  .muted { color:var(--muted); }
  .warn { background:var(--warn); border:1px solid var(--line); padding:10px 14px; border-radius:8px; margin:16px 0; }
  form { display:flex; flex-wrap:wrap; gap:8px; margin:16px 0; }
  input, select, button { font:inherit; padding:6px 10px; border:1px solid var(--line); border-radius:6px; background:var(--card); color:var(--fg); }
  .wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; background:var(--card); }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
  th { font-size:.8rem; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); white-space:nowrap; }
  .b { display:inline-block; min-width:52px; text-align:center; padding:1px 6px; border-radius:4px; color:#fff; font-size:.8rem; }
  .Alto { background:var(--alto); } .Medio { background:var(--medio); } .Bajo { background:var(--bajo); } .NE { background:var(--ne); }
  .ev { font-size:.7rem; color:var(--muted); margin-left:2px; }
  details summary { cursor:pointer; color:var(--muted); font-size:.85rem; }
  details p { max-width:60ch; font-size:.85rem; }
  a { color:inherit; }
</style>
</head>
<body>
<main>
  <h1>Observatorio IA</h1>
  <div class="muted">Registro de fuentes · esquema <?= e($db['schema_version']) ?> · rúbrica <?= e($db['rubric_version']) ?> · <?= count($sources) ?> fuentes · <?= count($items) ?> piezas</div>

  <div class="warn">Evaluaciones <strong>propuestas por un asistente de IA sin revisión humana</strong>. No son juicios definitivos. NE = no evaluable (no equivale a Bajo). El superíndice indica el nivel de evidencia (P/S/M/-); «?» = pendiente.</div>

  <form method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar nombre, tipo, región">
    <select name="rel">
      <option value="">Relevancia: todas</option>
      <?php foreach (['nucleo' => 'Núcleo', 'adyacente' => 'Adyacente'] as $k => $v): ?>
        <option value="<?= $k ?>" <?= $rel === $k ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <select name="crit">
      <option value="">Criterio</option>
      <?php foreach ($crits as $k => $v): ?>
        <option value="<?= $k ?>" <?= $crit === $k ? 'selected' : '' ?>><?= strtoupper($k) ?> <?= e($v) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="val">
      <option value="">Valor</option>
      <?php foreach (['Alto', 'Medio', 'Bajo', 'NE'] as $v): ?>
        <option <?= $val === $v ? 'selected' : '' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button>Filtrar</button>
    <a href="?" class="muted" style="align-self:center">Limpiar</a>
  </form>

  <div class="muted" style="margin-bottom:8px"><?= count($filtered) ?> resultados</div>
  <div class="wrap">
  <table>
    <thead><tr>
      <th>ID</th><th>Fuente</th><th>Tipo / región</th><th>Relev.</th>
      <?php foreach ($crits as $k => $v): ?><th title="<?= e($v) ?>"><?= strtoupper($k) ?></th><?php endforeach; ?>
      <th>n</th><th>Notas</th>
    </tr></thead>
    <tbody>
    <?php foreach ($filtered as $s): ?>
      <tr>
        <td class="muted"><?= e($s['id']) ?></td>
        <td><a href="<?= e($s['url']) ?>" rel="noopener" target="_blank"><?= e($s['name']) ?></a><?= ($s['kind'] ?? null) === 'infrastructure' ? ' <span class="muted">(infraestructura)</span>' : '' ?></td>
        <td><?= e($s['type'] ?? '') ?><br><span class="muted"><?= e($s['region'] ?? '') ?> · <?= e(implode(', ', $s['languages'] ?? [])) ?></span></td>
        <td><?= e($s['relevance'] ?? '') ?></td>
        <?php foreach (array_keys($crits) as $c): $x = crit($s, $c); ?>
          <td><span class="b <?= e($x['value']) ?>"><?= e($x['value']) ?></span><sup class="ev"><?= e($x['evidence_level'] ?? '?') ?></sup></td>
        <?php endforeach; ?>
        <td><?= e((string)($s['sampling_evaluation']['n_items'] ?? '–')) ?></td>
        <td><details><summary>ver</summary>
          <p><strong>Estado:</strong> <?= e($s['legacy_review_status'] ?? $s['status']) ?></p>
          <p><?= e($s['legacy_evidence_notes'] ?? '') ?></p>
        </details></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>
</body>
</html>
