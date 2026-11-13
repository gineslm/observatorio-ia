// Estado de valoración por pieza (solo local). Cada clic avanza: pendiente -> ok -> destacado -> pendiente.
const NEXT = { sin: 'ok', ok: 'estrella', estrella: 'sin' };

const paint = (btn, status) => {
  const L = JSON.parse(btn.dataset.labels);
  btn.dataset.status = status;
  btn.title = `${L[status]} · ${L.next.replace('%s', L[NEXT[status]])}`;
  btn.setAttribute('aria-label', `${L.col}: ${L[status]}`);
};

document.addEventListener('click', async (ev) => {
  const btn = ev.target.closest('.status-btn');
  if (!btn || btn.classList.contains('busy')) return;
  const next = NEXT[btn.dataset.status] ?? 'ok';
  const body = new FormData();
  body.append('item_id', btn.dataset.id);
  body.append('status', next);
  btn.classList.add('busy');
  btn.classList.remove('error');
  try {
    const res = await fetch('api/interest.php', { method: 'POST', body });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error);
    paint(btn, next);
  } catch (err) {
    console.error('[observatorio] no se pudo guardar el estado', err);
    btn.classList.add('error');
  } finally {
    btn.classList.remove('busy');
  }
});
