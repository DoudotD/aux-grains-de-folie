<?php
/** Confirmation puis suppression d'une actualité. */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$post = post_find($id);
if (!$post) {
    flash('error', 'Cette actualité n’existe plus.');
    redirect('actualites.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    post_delete($id);
    flash('success', 'L’actualité « ' . e($post['title']) . ' » a été supprimée.');
    redirect('actualites.php');
}

admin_head('Supprimer une actualité', ['nav' => 'actualites']);
?>
<div class="narrow">
  <h1>Supprimer cette actualité ?</h1>
  <p class="lead">Vous êtes sur le point de supprimer définitivement :</p>
  <p class="confirm-title">« <?= e($post['title']) ?> »</p>
  <?php if (post_status($post) === 'published'): ?>
    <p class="hint-box">Cette actualité est actuellement visible sur le site : elle en disparaîtra immédiatement.</p>
  <?php endif; ?>
  <p>Cette action est <strong>irréversible</strong>.</p>
  <form method="post" action="supprimer.php" class="button-row">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= e($id) ?>">
    <button class="btn btn-danger" type="submit">Oui, supprimer définitivement</button>
    <a class="btn btn-outline" href="actualites.php">Non, la conserver</a>
  </form>
</div>
<?php admin_foot();
