<?php
/**
 * Gabarit public (en-tête, pied de page) pour les pages PHP du site.
 * Reprend à l'identique le balisage de index.php.
 */
declare(strict_types=1);

function site_head(string $title, string $description, string $base = ''): void
{
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <meta name="theme-color" content="#2a1a10">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232a1a10'/%3E%3Cpath d='M32 52V14' stroke='%23d9a441' stroke-width='3' stroke-linecap='round'/%3E%3Cpath d='M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z' fill='%23d9a441'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?= e($base) ?>style.css">
</head>
<body>
    <?php
}

function site_header(string $current = '', string $base = '', string $skipTarget = '#contenu'): void
{
    $link = function (string $href, string $label, string $key, string $class = '') use ($current, $base): string {
        $aria = $current === $key ? ' aria-current="page"' : '';
        $cls = $class !== '' ? ' class="' . $class . '"' : '';
        return '<li><a' . $cls . ' href="' . e($base . $href) . '"' . $aria . '>' . $label . '</a></li>';
    };
    ?>
  <a class="skip-link" href="<?= e($skipTarget) ?>">Aller au contenu principal</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?= e($base) ?>index.php" aria-label="Aux Grains de Folie – retour à l’accueil">
        <svg class="brand-mark" viewBox="0 0 64 64" width="32" height="32" aria-hidden="true" focusable="false">
          <path d="M32 58V12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <path fill="currentColor" d="M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z"/>
        </svg>
        <span class="brand-text">Aux Grains <span class="brand-de">de</span> Folie</span>
      </a>

      <nav aria-label="Navigation principale">
        <ul class="nav-list">
          <?= $link('index.php#savoir-faire', 'Savoir-faire', 'savoir-faire') ?>
          <?= $link('index.php#creations', 'Créations', 'creations') ?>
          <?= $link('index.php#medailles', 'Médailles', 'medailles') ?>
          <?= $link('actualites.php', 'Actualités', 'actualites', 'nav-cta') ?>
          <?= $link('index.php#infos', 'Horaires &amp; accès', 'infos') ?>
        </ul>
      </nav>
    </div>
  </header>
    <?php
}

function site_footer(string $base = ''): void
{
    ?>
  <footer class="site-footer">
    <div class="container footer-inner">
      <p class="footer-brand">Aux Grains de Folie</p>
      <p>Boulangerie-pâtisserie · 8 place Georges Clemenceau, 85220 Coëx</p>
      <p class="footer-small">Yoann Couton &amp; Tiffany Dudit · <a href="<?= e($base) ?>admin/">Espace de gestion</a></p>
      <p><a class="to-top" href="#haut">Retour en haut de page</a></p>
    </div>
  </footer>
    <?php
}
