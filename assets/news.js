/* Page Actualités : bouton « Haut » (amélioration progressive). */
(() => {
  'use strict';
  const btn = document.querySelector('[data-to-top]');
  const top = document.getElementById('haut');
  if (!btn || !top) return;

  document.documentElement.classList.add('js');

  const update = () => btn.classList.toggle('is-visible', window.scrollY > 500);
  window.addEventListener('scroll', update, { passive: true });
  update();

  btn.addEventListener('click', (event) => {
    event.preventDefault();
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    // Replace le focus clavier / lecteur d'écran en haut de la page
    top.focus({ preventScroll: true });
  });
})();
