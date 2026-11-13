// Tabla del tablero: mostrar/ocultar columnas (orden fijo). Se guarda en la cookie «cols»,
// que el servidor lee para pintar la tabla ya configurada (sin parpadeo al recargar).
(() => {
  const list = document.querySelector('.col-list');
  const table = document.querySelector('table.items');
  if (!list || !table) return;

  const read = () => [...list.children].map((li) => ({
    key: li.dataset.col,
    on: li.querySelector('input').checked,
  }));

  const save = (cfg) => {
    const val = cfg.map((c) => (c.on ? '' : '-') + c.key).join(',');
    document.cookie = `cols=${val}; path=${list.dataset.base}; max-age=31536000; samesite=lax`;
  };

  const apply = (cfg) => {
    cfg.forEach(({ key, on }) => {
      table.querySelectorAll(`[data-col="${key}"]`).forEach((cell) => cell.classList.toggle('col-off', !on));
    });
    // Fila desplegable (misma regla que detail_spans en grid.php): hueco antes del título, resumen del título
    // hasta la primera columna «kw» visible, keywords desde ahí hasta el final (incluida la de acciones).
    const kwCols = (table.dataset.kw || '').split(',');
    const vis = cfg.filter((c) => c.on).map((c) => c.key);
    const i = vis.indexOf('title');
    let j = vis.findIndex((k, n) => n > i && kwCols.includes(k));
    if (j < 0) j = vis.length;
    table.querySelectorAll('tr.detail').forEach((tr) => {
      const pre = tr.querySelector('.d-pre');
      pre.hidden = i === 0;
      pre.colSpan = Math.max(1, i);
      tr.querySelector('.d-sum').colSpan = j - i;
      tr.querySelector('.d-kw').colSpan = vis.length - j + 1;
    });
  };

  list.addEventListener('change', () => { const cfg = read(); apply(cfg); save(cfg); });
})();
