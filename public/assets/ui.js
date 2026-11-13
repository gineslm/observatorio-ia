// Toolbar, desplegables y popovers. Menús (.dd) y popovers (.pop) se cierran con clic fuera, Esc o scroll.
const togglers = () => document.querySelectorAll('[data-toggle]');
const targetOf = (btn) => document.getElementById(btn.dataset.toggle);
const isFloating = (btn) => !!btn.closest('.dd') || !!targetOf(btn)?.classList.contains('pop');

// Los popovers van con position: fixed para que no los recorte el scroll de la tabla.
const place = (btn, pop) => {
  const r = btn.getBoundingClientRect();
  const w = pop.offsetWidth;
  const left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
  pop.style.left = `${left}px`;
  pop.style.top = `${r.bottom + 6}px`;
  pop.style.maxHeight = `${Math.max(160, window.innerHeight - r.bottom - 24)}px`;
};

const setOpen = (btn, open) => {
  const el = targetOf(btn);
  if (!el) return;
  el.hidden = !open;
  btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  btn.classList.toggle('active', open);
  if (open && el.classList.contains('pop')) {
    place(btn, el);
    el.querySelector('.pop-find, input[type=checkbox]:not([disabled])')?.focus({ preventScroll: true });
  }
};
const closeMenus = (except) => togglers().forEach((b) => {
  if (b !== except && isFloating(b) && b.getAttribute('aria-expanded') === 'true') setOpen(b, false);
});

document.addEventListener('click', (ev) => {
  const btn = ev.target.closest('[data-toggle]');
  if (btn) {
    closeMenus(btn);
    setOpen(btn, btn.getAttribute('aria-expanded') !== 'true');
    return;
  }
  if (!ev.target.closest('.dd-menu, .pop')) closeMenus();
});

document.addEventListener('keydown', (ev) => {
  if (ev.key === 'Escape') closeMenus();
});
// Al hacer scroll fuera del popover, se cierra (su posición dejaría de coincidir con el botón).
document.addEventListener('scroll', (ev) => {
  if (!(ev.target instanceof Element && ev.target.closest('.pop'))) closeMenus();
}, true);
window.addEventListener('resize', () => closeMenus());

// Búsqueda dentro de un popover de filtro.
document.addEventListener('input', (ev) => {
  if (!ev.target.classList.contains('pop-find')) return;
  const term = ev.target.value.trim().toLowerCase();
  ev.target.closest('.pop').querySelectorAll('.pop-list label').forEach((l) => {
    l.hidden = term !== '' && !l.textContent.toLowerCase().includes(term);
  });
});
