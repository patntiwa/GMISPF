'use strict';
const menu = document.querySelector('[data-menu]');
const sidebar = document.querySelector('#sidebar');
const mobile = window.matchMedia('(max-width: 740px)');
function setMenu(open) {
  const expanded = open && mobile.matches;
  menu?.setAttribute('aria-expanded', String(expanded));
  menu?.setAttribute('aria-label', expanded ? 'Fermer le menu' : 'Ouvrir le menu');
  document.body.classList.toggle('menu-open', expanded);
  if (sidebar) sidebar.inert = mobile.matches && !expanded;
}
setMenu(false);
mobile.addEventListener('change', () => setMenu(false));
menu?.addEventListener('click', () => setMenu(menu.getAttribute('aria-expanded') !== 'true'));
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menu?.getAttribute('aria-expanded') === 'true') {
    setMenu(false);
    menu.focus();
  }
  if (event.key === '/' && !event.ctrlKey && !event.metaKey && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
    const search = document.querySelector('#global-search');
    if (search && search.getBoundingClientRect().width > 0) { event.preventDefault(); search.focus(); }
  }
});
document.addEventListener('click', (event) => {
  if (mobile.matches && !sidebar?.contains(event.target) && !menu?.contains(event.target)) setMenu(false);
});
document.querySelector('[data-password]')?.addEventListener('click', (event) => {
  const input = document.querySelector('#password');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  event.currentTarget.textContent = show ? 'Masquer' : 'Afficher';
  event.currentTarget.setAttribute('aria-pressed', String(show));
});
document.querySelectorAll('[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});
