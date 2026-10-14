/* Espace de gestion : petits comportements communs. */
(() => {
  'use strict';
  document.documentElement.classList.replace('no-js', 'js');

  // Afficher / masquer le mot de passe
  document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    const input = document.getElementById(btn.dataset.togglePassword);
    if (!input) return;
    btn.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? 'Masquer' : 'Afficher';
      btn.setAttribute('aria-pressed', String(show));
    });
  });

  // Confirmation avant une action sensible
  document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-confirm]');
    if (el && !window.confirm(el.dataset.confirm)) event.preventDefault();
  });

  // Après une erreur de formulaire, place le focus sur le résumé des erreurs
  const summary = document.getElementById('error-summary');
  if (summary) summary.focus();
})();
