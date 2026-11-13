// Marcador de interés: ✓ / ✗ por pieza. Pulsar el botón activo quita la marca.
document.addEventListener('click', async (ev) => {
  const btn = ev.target.closest('.mark-btn');
  if (!btn) return;
  const box = btn.closest('.mark');
  const next = box.dataset.value === btn.dataset.v ? '' : btn.dataset.v;
  const body = new FormData();
  body.append('item_id', box.dataset.id);
  body.append('value', next);
  box.classList.add('busy');
  try {
    const res = await fetch('api/interest.php', { method: 'POST', body });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error);
    box.dataset.value = next;
    box.querySelectorAll('.mark-btn').forEach((b) => {
      const on = b.dataset.v === next;
      b.classList.toggle('on', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  } catch (err) {
    console.error('[observatorio] no se pudo guardar la marca', err);
    box.classList.add('error');
  } finally {
    box.classList.remove('busy');
  }
});
