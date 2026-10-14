<?php
/** Gabarit de l'espace de gestion. */
declare(strict_types=1);

/**
 * @param array{bare?:bool,nav?:string,scripts?:string[],body_class?:string} $o
 */
function admin_head(string $title, array $o = []): void
{
    $user = empty($o['bare']) ? current_user() : null;
    $nav = $o['nav'] ?? '';
    $flash = flash_take();
    ?>
<!doctype html>
<html lang="fr" class="no-js">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#2a1a10">
  <title><?= e($title) ?> – Espace de gestion – Aux Grains de Folie</title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232a1a10'/%3E%3Cpath d='M32 52V14' stroke='%23d9a441' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="../assets/admin.css">
  <script src="../assets/admin.js" defer></script>
  <?php foreach ($o['scripts'] ?? [] as $s): ?>
  <script src="../assets/<?= e($s) ?>" defer></script>
  <?php endforeach; ?>
</head>
<body class="admin <?= e($o['body_class'] ?? '') ?>">
  <a class="skip-link" href="#contenu">Aller au contenu principal</a>
    <?php if ($user): ?>
  <header class="admin-bar">
    <div class="admin-bar-inner">
      <a class="admin-brand" href="actualites.php">
        <svg viewBox="0 0 64 64" width="26" height="26" aria-hidden="true" focusable="false"><path d="M32 58V12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path fill="currentColor" d="M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z"/></svg>
        <span>Espace de gestion</span>
      </a>
      <nav aria-label="Navigation de l’espace de gestion">
        <ul class="admin-nav">
          <li><a href="actualites.php"<?= $nav === 'actualites' ? ' aria-current="page"' : '' ?>>Actualités</a></li>
          <li><a href="compte.php"<?= $nav === 'compte' ? ' aria-current="page"' : '' ?>>Mon compte</a></li>
          <li><a href="../actualites.php" target="_blank" rel="noopener">Voir le site<span class="visually-hidden"> (s’ouvre dans un nouvel onglet)</span> <span aria-hidden="true">↗</span></a></li>
        </ul>
      </nav>
      <form class="admin-logout" method="post" action="deconnexion.php">
        <?= csrf_input() ?>
        <span class="admin-user"><?= e($user['name']) ?></span>
        <button class="btn-link" type="submit">Se déconnecter</button>
      </form>
    </div>
  </header>
    <?php endif; ?>

  <main id="contenu" class="admin-main" tabindex="-1">
    <?php if ($flash): ?>
    <div class="notice notice-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
      <?= $flash['message'] /* HTML déjà échappé par l'appelant */ ?>
    </div>
    <?php endif; ?>
    <?php
}

function admin_foot(): void
{
    ?>
  </main>
</body>
</html>
    <?php
}
