<?php
/** Mon compte : mot de passe, comptes autorisés. */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
$user = require_login();

$errors = ['pw' => [], 'add' => []];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $do = (string)($_POST['do'] ?? '');

    if ($do === 'password') {
        $current = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify($current, $user['hash'])) {
            $errors['pw'][] = 'Votre mot de passe actuel est incorrect.';
        }
        if ($pwErr = password_problem($new)) {
            $errors['pw'][] = $pwErr;
        }
        if ($new !== (string)($_POST['new2'] ?? '')) {
            $errors['pw'][] = 'Les deux nouveaux mots de passe ne sont pas identiques.';
        }
        if (!$errors['pw']) {
            user_update($user['id'], ['hash' => password_hash($new, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            flash('success', 'Votre mot de passe a bien été modifié.');
            redirect('compte.php');
        }
    } elseif ($do === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        $old = ['name' => $name, 'email' => $email];
        if ($name === '') {
            $errors['add'][] = 'Indiquez le prénom et le nom.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['add'][] = 'Indiquez une adresse email valide.';
        } elseif (user_by_email($email)) {
            $errors['add'][] = 'Un compte existe déjà avec cette adresse email.';
        }
        if ($pwErr = password_problem($pw)) {
            $errors['add'][] = $pwErr;
        }
        if (!$errors['add']) {
            user_create($name, $email, $pw);
            flash('success', 'Le compte de ' . e($name) . ' a été créé. Communiquez-lui son mot de passe de façon sécurisée.');
            redirect('compte.php');
        }
    } elseif ($do === 'delete') {
        $target = user_by_id((string)($_POST['id'] ?? ''));
        if ($target && $target['id'] !== $user['id']) {
            user_delete($target['id']);
            flash('success', 'Le compte de ' . e($target['name']) . ' a été supprimé.');
        }
        redirect('compte.php');
    }
}

$users = users_all();
admin_head('Mon compte', ['nav' => 'compte']);
?>
<h1>Mon compte</h1>
<p class="lead">Connecté en tant que <strong><?= e($user['name']) ?></strong> (<?= e($user['email']) ?>).</p>

<div class="cards-2">
  <section class="card-box" aria-labelledby="t-pw">
    <h2 id="t-pw">Changer mon mot de passe</h2>
    <?php if ($errors['pw']): ?>
      <div class="notice notice-error" role="alert"><ul><?php foreach ($errors['pw'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="stack" novalidate>
      <?= csrf_input() ?>
      <input type="hidden" name="do" value="password">
      <input type="text" name="username" value="<?= e($user['email']) ?>" autocomplete="username" hidden>
      <div class="field">
        <label for="current">Mot de passe actuel</label>
        <input id="current" name="current" type="password" autocomplete="current-password" required>
      </div>
      <div class="field">
        <label for="new">Nouveau mot de passe</label>
        <input id="new" name="new" type="password" autocomplete="new-password" aria-describedby="new-hint" required>
        <p class="hint" id="new-hint">Au moins <?= PASSWORD_MIN ?> caractères.</p>
      </div>
      <div class="field">
        <label for="new2">Confirmez le nouveau mot de passe</label>
        <input id="new2" name="new2" type="password" autocomplete="new-password" required>
      </div>
      <button class="btn btn-primary" type="submit">Enregistrer le mot de passe</button>
    </form>
  </section>

  <section class="card-box" aria-labelledby="t-users">
    <h2 id="t-users">Personnes autorisées</h2>
    <p class="hint">Ces personnes peuvent écrire et publier des actualités.</p>
    <ul class="user-list">
      <?php foreach ($users as $u): ?>
        <li>
          <span><strong><?= e($u['name']) ?></strong><br><span class="hint"><?= e($u['email']) ?></span></span>
          <?php if ($u['id'] === $user['id']): ?>
            <span class="badge badge-published">Vous</span>
          <?php else: ?>
            <form method="post">
              <?= csrf_input() ?>
              <input type="hidden" name="do" value="delete">
              <input type="hidden" name="id" value="<?= e($u['id']) ?>">
              <button class="btn btn-danger-outline btn-sm" type="submit" data-confirm="Retirer l’accès de <?= e($u['name']) ?> ?">Retirer<span class="visually-hidden"> l’accès de <?= e($u['name']) ?></span></button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <details class="add-user" <?= $errors['add'] ? 'open' : '' ?>>
      <summary class="btn btn-outline btn-sm">Ajouter une personne</summary>
      <?php if ($errors['add']): ?>
        <div class="notice notice-error" role="alert"><ul><?php foreach ($errors['add'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <form method="post" class="stack" novalidate>
        <?= csrf_input() ?>
        <input type="hidden" name="do" value="add">
        <div class="field">
          <label for="u-name">Prénom et nom</label>
          <input id="u-name" name="name" type="text" value="<?= e($old['name']) ?>" required>
        </div>
        <div class="field">
          <label for="u-email">Adresse email</label>
          <input id="u-email" name="email" type="email" value="<?= e($old['email']) ?>" required>
        </div>
        <div class="field">
          <label for="u-pw">Mot de passe provisoire</label>
          <input id="u-pw" name="password" type="text" autocomplete="off" aria-describedby="u-pw-hint" required>
          <p class="hint" id="u-pw-hint">Au moins <?= PASSWORD_MIN ?> caractères. La personne pourra ensuite le changer.</p>
        </div>
        <button class="btn btn-primary" type="submit">Créer le compte</button>
      </form>
    </details>
  </section>
</div>
<?php admin_foot();
