<?php
/**
 * Aperçu avant publication : affiche l'actualité exactement comme sur le site,
 * à partir des données du formulaire (rien n'est enregistré).
 * Chargé dans une iframe de la page d'édition.
 */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/post_input.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('actualites.php');
}
csrf_verify();

$existing = ($id = (string)($_POST['id'] ?? '')) !== '' ? post_find($id) : null;
$post = post_from_input($_POST, $existing, $user);
$post['published_at'] = $post['published_at'] ?? time();
if ($post['title'] === '') {
    $post['title'] = '(Titre à saisir)';
}
$when = $post['published_at'] > time() ? 'programmée pour le ' . fr_datetime($post['published_at']) : 'publiée immédiatement';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Aperçu – <?= e($post['title']) ?></title>
  <base target="_blank">
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="preview-page">
  <p class="preview-banner" role="status">
    <strong>Aperçu</strong> – ceci n’est pas encore en ligne. Une fois validée, cette actualité sera <?= e($when) ?>.
  </p>
  <div class="container news-layout preview-layout">
    <div class="news-main">
      <div class="posts">
        <?= render_post($post, '../') ?>
      </div>
    </div>
  </div>
</body>
</html>
