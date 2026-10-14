<?php
/** Liste des actualités (tableau de bord). */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
$user = require_login();

$filter = (string)($_GET['statut'] ?? '');
$all = sort_posts_desc(posts_all());

$counts = ['all' => count($all), 'published' => 0, 'scheduled' => 0, 'draft' => 0];
foreach ($all as $p) {
    $counts[post_status($p)]++;
}
$posts = $filter && isset($counts[$filter]) && $filter !== 'all'
    ? array_values(array_filter($all, fn($p) => post_status($p) === $filter))
    : $all;
if (!isset($counts[$filter])) {
    $filter = 'all';
}

$labels = ['published' => 'Publiée', 'scheduled' => 'Programmée', 'draft' => 'Brouillon'];
$tabs = ['all' => 'Toutes', 'published' => 'Publiées', 'scheduled' => 'Programmées', 'draft' => 'Brouillons'];

admin_head('Actualités', ['nav' => 'actualites']);
?>
<div class="page-head">
  <h1>Vos actualités</h1>
  <a class="btn btn-primary btn-lg" href="editer.php"><span aria-hidden="true">＋</span> Nouvelle actualité</a>
</div>

<?php if (!$all): ?>
  <div class="empty-state">
    <h2>Rien d’écrit pour l’instant</h2>
    <p>Une nouveauté, un événement, un nouveau pain ? Écrivez votre première actualité : elle apparaîtra en haut de la page « Actualités » du site.</p>
    <p><a class="btn btn-primary btn-lg" href="editer.php">Écrire ma première actualité</a></p>
  </div>
<?php else: ?>
  <nav aria-label="Filtrer les actualités" class="tabs">
    <ul>
      <?php foreach ($tabs as $key => $label): if ($key !== 'all' && $counts[$key] === 0) { continue; } ?>
        <li><a href="actualites.php<?= $key === 'all' ? '' : '?statut=' . $key ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= $label ?> <span class="count"><?= $counts[$key] ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <ul class="post-list">
    <?php foreach ($posts as $p): $st = post_status($p); ?>
      <li class="post-row">
        <div class="post-row-main">
          <h2 class="post-row-title"><a href="editer.php?id=<?= e($p['id']) ?>"><?= e($p['title'] !== '' ? $p['title'] : '(Sans titre)') ?></a></h2>
          <p class="post-row-meta">
            <span class="badge badge-<?= $st ?>"><?= $labels[$st] ?></span>
            <span><?= $st === 'draft' ? 'Dernière modification le ' . e(fr_datetime((int)($p['updated_at'] ?? $p['published_at']))) : e(fr_datetime((int)$p['published_at'])) ?></span>
            <span aria-hidden="true">·</span>
            <span>par <?= e($p['author']) ?></span>
          </p>
        </div>
        <div class="post-row-actions">
          <a class="btn btn-outline btn-sm" href="editer.php?id=<?= e($p['id']) ?>">Modifier<span class="visually-hidden"> « <?= e($p['title']) ?> »</span></a>
          <?php if ($st === 'published'): ?>
            <a class="btn btn-outline btn-sm" href="../actualites.php#actu-<?= e($p['id']) ?>" target="_blank" rel="noopener">Voir<span class="visually-hidden"> « <?= e($p['title']) ?> » sur le site (nouvel onglet)</span></a>
          <?php endif; ?>
          <a class="btn btn-danger-outline btn-sm" href="supprimer.php?id=<?= e($p['id']) ?>">Supprimer<span class="visually-hidden"> « <?= e($p['title']) ?> »</span></a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php admin_foot();
