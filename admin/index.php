<?php
/** Connexion (email + mot de passe) et création du premier compte. */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
send_security_headers(true);

if (current_user()) {
    redirect('actualites.php');
}

$setupMode = count(users_all()) === 0;
if ($setupMode) {
    setup_key(); // crée data/setup.json.php dès l'affichage du formulaire
}
$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $old['email'] = $email;

    if ($setupMode) {
        /* ----- Création du premier compte ----- */
        $name = trim((string)($_POST['name'] ?? ''));
        $old['name'] = $name;
        $key = strtoupper(trim((string)($_POST['setup_key'] ?? '')));
        $wait = throttle_wait();
        if ($wait > 0) {
            $errors[] = 'Trop de tentatives. Réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
        } elseif (!hash_equals(setup_key(), $key)) {
            throttle_fail();
            $errors[] = 'La clé d’installation est incorrecte.';
        }
        if ($name === '') {
            $errors[] = 'Indiquez votre prénom et votre nom.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Indiquez une adresse email valide.';
        }
        if ($pwErr = password_problem($password)) {
            $errors[] = $pwErr;
        }
        if ($password !== (string)($_POST['password2'] ?? '')) {
            $errors[] = 'Les deux mots de passe ne sont pas identiques.';
        }
        if (!$errors) {
            $user = user_create($name, $email, $password);
            throttle_clear();
            auth_login($user);
            flash('success', 'Bienvenue ' . e($name) . ' ! Votre compte est créé. Vous pouvez écrire votre première actualité.');
            redirect('actualites.php');
        }
    } else {
        /* ----- Connexion ----- */
        $wait = throttle_wait($email);
        if ($wait > 0) {
            $errors[] = 'Trop de tentatives échouées. Pour votre sécurité, réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
        } else {
            $user = user_by_email($email);
            // Vérification effectuée même si l'email est inconnu (durée constante)
            $hash = $user['hash'] ?? '$2y$12$7wM071J13W8nxyBkUBvRIeRuPyuhFCzOSaxIBOMqJcr2dYNmOLFoa';
            if (password_verify($password, $hash) && $user) {
                throttle_clear($email);
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    user_update($user['id'], ['hash' => password_hash($password, PASSWORD_DEFAULT)]);
                }
                auth_login($user);
                redirect('actualites.php');
            }
            throttle_fail($email);
            $errors[] = 'Email ou mot de passe incorrect.';
        }
    }
}

admin_head($setupMode ? 'Première connexion' : 'Connexion', ['bare' => true, 'body_class' => 'admin-login']);
?>
<div class="login-card">
  <p class="login-brand">
    <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true" focusable="false"><path d="M32 58V12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path fill="currentColor" d="M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z"/></svg>
    <span>Aux Grains <em>de</em> Folie</span>
  </p>

  <?php if ($setupMode): ?>
    <h1>Bienvenue !</h1>
    <p class="lead">Créons le premier compte pour publier vos actualités.</p>
  <?php else: ?>
    <h1>Espace de gestion</h1>
    <p class="lead">Connectez-vous pour écrire et publier vos actualités.</p>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="notice notice-error" role="alert">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" action="index.php" class="stack" novalidate>
    <?= csrf_input() ?>

    <?php if ($setupMode): ?>
      <p class="hint hint-box">Pour sécuriser l’installation, saisissez la <strong>clé d’installation</strong> : elle est écrite dans le fichier
        <code>data/setup.json.php</code> de votre hébergement.</p>
      <div class="field">
        <label for="setup_key">Clé d’installation</label>
        <input id="setup_key" name="setup_key" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
      </div>
      <div class="field">
        <label for="name">Prénom et nom</label>
        <input id="name" name="name" type="text" autocomplete="name" value="<?= e($old['name']) ?>" required>
      </div>
    <?php endif; ?>

    <div class="field">
      <label for="email">Adresse email</label>
      <input id="email" name="email" type="email" autocomplete="username" inputmode="email" value="<?= e($old['email']) ?>" required autofocus>
    </div>

    <div class="field">
      <label for="password"><?= $setupMode ? 'Choisissez un mot de passe' : 'Mot de passe' ?></label>
      <div class="password-wrap">
        <input id="password" name="password" type="password" autocomplete="<?= $setupMode ? 'new-password' : 'current-password' ?>" required<?= $setupMode ? ' aria-describedby="pw-hint"' : '' ?>>
        <button type="button" class="btn-link toggle-password" data-toggle-password="password" aria-pressed="false">Afficher</button>
      </div>
      <?php if ($setupMode): ?>
        <p class="hint" id="pw-hint">Au moins <?= PASSWORD_MIN ?> caractères. Une petite phrase est plus sûre et plus facile à retenir.</p>
      <?php endif; ?>
    </div>

    <?php if ($setupMode): ?>
      <div class="field">
        <label for="password2">Confirmez le mot de passe</label>
        <input id="password2" name="password2" type="password" autocomplete="new-password" required>
      </div>
    <?php endif; ?>

    <button class="btn btn-primary btn-block" type="submit"><?= $setupMode ? 'Créer mon compte' : 'Me connecter' ?></button>
  </form>

  <p class="login-back"><a href="../index.php">← Retour au site</a></p>
</div>
<?php admin_foot();
