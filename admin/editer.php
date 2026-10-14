<?php
/** Création / modification d'une actualité (éditeur WYSIWYG). */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require __DIR__ . '/../inc/post_input.php';
$user = require_login();

$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$existing = $id !== '' ? post_find($id) : null;
if ($id !== '' && !$existing) {
    flash('error', 'Cette actualité n’existe plus.');
    redirect('actualites.php');
}

$errors = [];
$post = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? 'draft');
    $publishing = in_array($action, ['publish', 'save'], true);
    $post = post_from_input($_POST, $existing, $user);
    $errors = post_errors($post, $publishing);

    if (!$errors) {
        $post['status'] = $publishing ? 'published' : 'draft';
        post_save($post);
        $sid = e($post['id']);
        if ($publishing) {
            $when = $post['published_at'] > time()
                ? 'L’actualité est <strong>programmée</strong> : elle apparaîtra sur le site le ' . e(fr_datetime($post['published_at'])) . '.'
                : 'L’actualité est <strong>publiée</strong> ! <a href="../actualites.php#actu-' . $sid . '" target="_blank" rel="noopener">La voir sur le site<span class="visually-hidden"> (nouvel onglet)</span></a>';
            flash('success', $when);
            redirect('actualites.php');
        }
        flash('success', 'Brouillon enregistré à ' . date('H\hi') . '. Il n’est pas visible sur le site.');
        redirect('editer.php?id=' . $post['id']);
    }
}

if (!$post) {
    $post = $existing ?? [
        'id' => '', 'title' => '', 'author' => $user['name'], 'status' => 'draft',
        'published_at' => (int)(floor(time() / 60) * 60), 'cover' => null, 'content' => '', 'ctas' => [],
        'updated_at' => 0,
    ];
}
$isNew = $existing === null;
$isPublished = $existing && ($existing['status'] ?? '') === 'published';
$dateValue = $post['published_at'] !== null ? date('Y-m-d\TH:i', (int)$post['published_at']) : (string)($_POST['published_at'] ?? '');
$ctas = array_pad($post['ctas'] ?? [], MAX_CTAS, ['label' => '', 'url' => '', 'style' => 'primary']);
$ctaVisible = count(array_filter($post['ctas'] ?? []));
$editorHtml = content_for_editor((string)$post['content'], '../');

/** Message d'erreur d'un champ + attribut aria. */
$err = fn(string $k): string => isset($errors[$k]) ? '<p class="field-error" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : '';
$invalid = fn(string $k): string => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';

admin_head($isNew ? 'Nouvelle actualité' : 'Modifier l’actualité', ['nav' => 'actualites', 'scripts' => ['editor.js'], 'body_class' => 'admin-editor']);
?>
<noscript><div class="notice notice-error">L’éditeur a besoin de JavaScript. Activez-le dans votre navigateur pour écrire une actualité.</div></noscript>

<p class="back-link"><a href="actualites.php">← Retour à la liste des actualités</a></p>
<div class="page-head">
  <h1><?= $isNew ? 'Nouvelle actualité' : 'Modifier l’actualité' ?></h1>
  <?php if (!$isNew): ?>
    <?php $st = post_status($existing); ?>
    <span class="badge badge-<?= $st ?> badge-lg"><?= ['published' => 'Publiée', 'scheduled' => 'Programmée', 'draft' => 'Brouillon'][$st] ?></span>
  <?php endif; ?>
</div>

<?php if ($errors): ?>
  <div class="notice notice-error" role="alert" id="error-summary" tabindex="-1">
    <p><strong>Quelques points à corriger avant de continuer :</strong></p>
    <ul>
      <?php foreach ($errors as $k => $msg): ?>
        <li><a href="#<?= e(in_array($k, ['editor'], true) ? 'editor' : $k) ?>"><?= e($msg) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="notice notice-info" id="restore-banner" role="status" hidden>
  <p>Nous avons retrouvé une version non enregistrée de cette actualité (modifiée le <span data-restore-date></span>).</p>
  <p class="button-row">
    <button type="button" class="btn btn-primary btn-sm" data-restore="yes">Restaurer cette version</button>
    <button type="button" class="btn btn-outline btn-sm" data-restore="no">Ignorer</button>
  </p>
</div>

<form id="post-form" method="post" action="editer.php<?= $id !== '' ? '?id=' . e($id) : '' ?>" novalidate
      data-base="../" data-upload-url="televerser.php" data-max-bytes="<?= upload_limit_bytes() ?>" data-max-label="<?= e(format_bytes(upload_limit_bytes())) ?>" data-post-id="<?= e($id) ?>" data-updated="<?= (int)($existing['updated_at'] ?? 0) ?>">
  <?= csrf_input() ?>
  <input type="hidden" name="id" value="<?= e($id) ?>">

  <!-- 1. Titre -->
  <section class="step" aria-labelledby="s1">
    <h2 id="s1"><span class="step-n" aria-hidden="true">1</span> Le titre</h2>
    <div class="field">
      <label for="title">Titre de l’actualité</label>
      <input id="title" name="title" type="text" class="input-xl" maxlength="120" value="<?= e($post['title']) ?>" placeholder="Ex. : Notre nouvelle brioche de saison"<?= $invalid('title') ?> required>
      <?= $err('title') ?>
    </div>
  </section>

  <!-- 2. Image à la une -->
  <section class="step" aria-labelledby="s2">
    <h2 id="s2"><span class="step-n" aria-hidden="true">2</span> L’image à la une <span class="optional">facultatif</span></h2>
    <p class="hint">Cette grande image s’affiche en haut de l’actualité.</p>
    <div class="cover-box" id="cover-box">
      <input type="hidden" name="cover_src" id="cover_src" value="<?= e($post['cover']['src'] ?? '') ?>">
      <div class="cover-preview" id="cover-preview"<?= empty($post['cover']['src']) ? ' hidden' : '' ?>>
        <img id="cover-img" alt="Aperçu de l’image à la une" src="<?= !empty($post['cover']['src']) ? e('../' . $post['cover']['src']) : '' ?>">
      </div>
      <div class="button-row">
        <button type="button" class="btn btn-outline" id="cover-choose"><?= empty($post['cover']['src']) ? 'Choisir une image' : 'Changer l’image' ?></button>
        <button type="button" class="btn btn-danger-outline" id="cover-remove"<?= empty($post['cover']['src']) ? ' hidden' : '' ?>>Retirer l’image</button>
        <input type="file" id="cover-file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
      </div>
      <p class="hint" id="cover-status" role="status"></p>
      <div class="field" id="cover-alt-field"<?= empty($post['cover']['src']) ? ' hidden' : '' ?>>
        <label for="cover_alt">Description de l’image</label>
        <input id="cover_alt" name="cover_alt" type="text" maxlength="250" value="<?= e($post['cover']['alt'] ?? '') ?>" placeholder="Ex. : Une brioche dorée posée sur une planche en bois"<?= $invalid('cover_alt') ?> aria-describedby="cover-alt-hint<?= isset($errors['cover_alt']) ? ' err-cover_alt' : '' ?>">
        <p class="hint" id="cover-alt-hint">Décrivez ce que l’on voit, en une phrase. C’est lu aux personnes malvoyantes.</p>
        <?= $err('cover_alt') ?>
      </div>
    </div>
  </section>

  <!-- 3. Contenu -->
  <section class="step" aria-labelledby="s3">
    <h2 id="s3"><span class="step-n" aria-hidden="true">3</span> Le texte</h2>
    <p class="hint" id="editor-hint">Écrivez comme dans un traitement de texte. Utilisez les boutons pour mettre en forme, ajouter une image ou une vidéo.</p>

    <div class="editor-box<?= isset($errors['editor']) ? ' has-error' : '' ?>">
      <div class="toolbar" role="group" aria-label="Mise en forme du texte">
        <div class="tool-group" role="group" aria-label="Annuler">
          <button type="button" class="tool" data-cmd="undo"><span aria-hidden="true">↶</span> Annuler</button>
          <button type="button" class="tool" data-cmd="redo"><span aria-hidden="true">↷</span> Rétablir</button>
        </div>
        <div class="tool-group" role="group" aria-label="Titres">
          <button type="button" class="tool" data-block="h2" aria-pressed="false">Titre</button>
          <button type="button" class="tool" data-block="h3" aria-pressed="false">Sous-titre</button>
        </div>
        <div class="tool-group" role="group" aria-label="Style du texte">
          <button type="button" class="tool tool-bold" data-cmd="bold" aria-pressed="false"><b>G</b><span class="visually-hidden">ras</span></button>
          <button type="button" class="tool tool-italic" data-cmd="italic" aria-pressed="false"><i>I</i><span class="visually-hidden">talique</span></button>
        </div>
        <div class="tool-group" role="group" aria-label="Listes et citation">
          <button type="button" class="tool" data-cmd="insertUnorderedList" aria-pressed="false"><span aria-hidden="true">•</span> Liste</button>
          <button type="button" class="tool" data-cmd="insertOrderedList" aria-pressed="false"><span aria-hidden="true">1.</span> Numérotée</button>
          <button type="button" class="tool" data-block="blockquote" aria-pressed="false"><span aria-hidden="true">“</span> Citation</button>
        </div>
        <div class="tool-group" role="group" aria-label="Insérer">
          <button type="button" class="tool" data-open="link">Lien</button>
          <button type="button" class="tool tool-accent" data-open="image">Image</button>
          <button type="button" class="tool tool-accent" data-open="video">Vidéo</button>
        </div>
      </div>

      <div id="editor" class="editor-surface prose" contenteditable="true" role="textbox" aria-multiline="true"
           aria-labelledby="s3" aria-describedby="editor-hint<?= isset($errors['editor']) ? ' err-editor' : '' ?>"
           data-placeholder="Commencez à écrire ici…" spellcheck="true" lang="fr"></div>
      <textarea id="content" name="content" hidden><?= e($editorHtml) ?></textarea>
    </div>
    <?= $err('editor') ?>
  </section>

  <!-- 4. Boutons -->
  <section class="step" aria-labelledby="s4">
    <h2 id="s4"><span class="step-n" aria-hidden="true">4</span> Les boutons <span class="optional">facultatif</span></h2>
    <p class="hint">Ajoutez jusqu’à <?= MAX_CTAS ?> boutons sous l’actualité : réserver, appeler, découvrir…</p>

    <div id="cta-list">
      <?php foreach ($ctas as $i => $c): $show = $i < $ctaVisible; ?>
        <fieldset class="cta-row" data-cta<?= $show ? '' : ' hidden' ?>>
          <legend>Bouton <?= $i + 1 ?></legend>
          <div class="cta-grid">
            <div class="field">
              <label for="cta-<?= $i ?>-label">Texte du bouton</label>
              <input id="cta-<?= $i ?>-label" name="cta[<?= $i ?>][label]" type="text" maxlength="40" value="<?= e($c['label']) ?>" placeholder="Ex. : Commander"<?= $invalid("cta-$i-label") ?>>
              <?= $err("cta-$i-label") ?>
            </div>
            <div class="field">
              <label for="cta-<?= $i ?>-url">Adresse du lien</label>
              <input id="cta-<?= $i ?>-url" name="cta[<?= $i ?>][url]" type="text" inputmode="url" autocapitalize="off" value="<?= e($c['url']) ?>" placeholder="https://… ou 02 51 00 00 00"<?= $invalid("cta-$i-url") ?>>
              <?= $err("cta-$i-url") ?>
            </div>
            <div class="field">
              <label for="cta-<?= $i ?>-style">Apparence</label>
              <select id="cta-<?= $i ?>-style" name="cta[<?= $i ?>][style]">
                <option value="primary"<?= $c['style'] === 'primary' ? ' selected' : '' ?>>Doré (mis en avant)</option>
                <option value="secondary"<?= $c['style'] === 'secondary' ? ' selected' : '' ?>>Contour (discret)</option>
              </select>
            </div>
          </div>
          <button type="button" class="btn-link" data-cta-remove>Retirer ce bouton</button>
        </fieldset>
      <?php endforeach; ?>
    </div>
    <p><button type="button" class="btn btn-outline" id="cta-add"<?= $ctaVisible >= MAX_CTAS ? ' hidden' : '' ?>><span aria-hidden="true">＋</span> Ajouter un bouton</button></p>
    <p class="hint">Pour un numéro de téléphone, tapez-le simplement (ex. 02 51 00 00 00). Pour un email, tapez l’adresse.</p>
  </section>

  <!-- 5. Publication -->
  <section class="step" aria-labelledby="s5">
    <h2 id="s5"><span class="step-n" aria-hidden="true">5</span> La publication</h2>
    <div class="cta-grid">
      <div class="field">
        <label for="author">Auteur affiché</label>
        <input id="author" name="author" type="text" maxlength="80" value="<?= e($post['author']) ?>"<?= $invalid('author') ?> required>
        <?= $err('author') ?>
      </div>
      <div class="field">
        <label for="published_at">Date de publication</label>
        <input id="published_at" name="published_at" type="datetime-local" value="<?= e($dateValue) ?>" aria-describedby="date-hint<?= isset($errors['published_at']) ? ' err-published_at' : '' ?>"<?= isset($errors['published_at']) ? ' aria-invalid="true"' : '' ?> required>
        <p class="hint" id="date-hint">Laissez la date du jour pour publier tout de suite. Choisissez une date à venir pour <strong>programmer</strong> la publication.</p>
        <?= $err('published_at') ?>
      </div>
    </div>
  </section>

  <!-- Barre d'actions -->
  <div class="action-bar" role="group" aria-label="Actions">
    <div class="action-bar-inner">
      <button type="submit" name="action" value="draft" class="btn btn-outline"><?= $isPublished ? 'Repasser en brouillon' : 'Enregistrer le brouillon' ?></button>
      <button type="submit" name="action" value="preview" class="btn btn-outline" formaction="apercu.php" formtarget="preview-frame" data-preview>Aperçu avant publication</button>
      <button type="submit" name="action" value="<?= $isPublished ? 'save' : 'publish' ?>" class="btn btn-primary btn-lg" id="publish-btn"><?= $isPublished ? 'Enregistrer les modifications' : 'Publier' ?></button>
    </div>
  </div>
</form>

<!-- Aperçu -->
<dialog id="preview-dialog" class="dialog dialog-preview" aria-labelledby="preview-title">
  <div class="dialog-bar">
    <h2 id="preview-title">Aperçu avant publication</h2>
    <div class="segmented" role="group" aria-label="Taille de l’aperçu">
      <button type="button" data-size="desktop" aria-pressed="true">Ordinateur</button>
      <button type="button" data-size="mobile" aria-pressed="false">Téléphone</button>
    </div>
    <div class="button-row">
      <button type="button" class="btn btn-outline" data-close>← Revenir à la modification</button>
      <button type="submit" form="post-form" name="action" value="<?= $isPublished ? 'save' : 'publish' ?>" class="btn btn-primary" data-publish-from-preview><?= $isPublished ? 'Enregistrer' : 'Publier' ?></button>
    </div>
  </div>
  <div class="preview-stage" id="preview-stage" data-size="desktop">
    <iframe name="preview-frame" id="preview-frame" title="Aperçu de l’actualité telle qu’elle apparaîtra sur le site"></iframe>
  </div>
</dialog>

<!-- Lien -->
<dialog id="link-dialog" class="dialog" aria-labelledby="link-title">
  <form method="dialog" class="stack" id="link-form" novalidate>
    <h2 id="link-title">Ajouter un lien</h2>
    <div class="field" id="link-text-field">
      <label for="link-text">Texte du lien</label>
      <input id="link-text" type="text" placeholder="Ex. : Voir notre carte">
    </div>
    <div class="field">
      <label for="link-url">Adresse du lien</label>
      <input id="link-url" type="text" inputmode="url" autocapitalize="off" placeholder="https://… ou 02 51 00 00 00" aria-describedby="link-err">
      <p class="field-error" id="link-err" role="alert" hidden></p>
    </div>
    <div class="button-row">
      <button type="submit" class="btn btn-primary">Valider</button>
      <button type="button" class="btn btn-outline" data-close>Annuler</button>
      <button type="button" class="btn btn-danger-outline" id="link-remove" hidden>Retirer le lien</button>
    </div>
  </form>
</dialog>

<!-- Image -->
<dialog id="image-dialog" class="dialog" aria-labelledby="image-title">
  <form method="dialog" class="stack" id="image-form" novalidate>
    <h2 id="image-title">Ajouter une image</h2>
    <div class="field" id="image-file-field">
      <label for="image-file">Choisir une photo</label>
      <input id="image-file" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
      <p class="hint">JPEG, PNG, WebP ou GIF. Les grandes photos sont réduites automatiquement avant l’envoi (limite du site : <?= e(format_bytes(upload_limit_bytes())) ?> par image).</p>
    </div>
    <p class="hint" id="image-status" role="status"></p>
    <div class="image-preview" id="image-preview" hidden><img alt="Aperçu de l’image choisie" id="image-preview-img"></div>

    <div class="field">
      <label for="image-alt">Description de l’image</label>
      <input id="image-alt" type="text" maxlength="250" placeholder="Ex. : Les pains du jour dans leur corbeille" aria-describedby="image-alt-hint image-err">
      <p class="hint" id="image-alt-hint">Décrivez ce que l’on voit, en une phrase. C’est lu aux personnes malvoyantes.</p>
      <label class="check"><input type="checkbox" id="image-decor"> L’image est purement décorative (pas de description nécessaire)</label>
    </div>

    <fieldset class="field radios">
      <legend>Placement dans le texte</legend>
      <label class="radio"><input type="radio" name="image-layout" value="center" checked> Centrée</label>
      <label class="radio"><input type="radio" name="image-layout" value="full"> Pleine largeur</label>
      <label class="radio"><input type="radio" name="image-layout" value="left"> À gauche, texte autour</label>
      <label class="radio"><input type="radio" name="image-layout" value="right"> À droite, texte autour</label>
    </fieldset>

    <div class="field">
      <label for="image-caption">Légende <span class="optional">facultatif</span></label>
      <input id="image-caption" type="text" maxlength="200">
    </div>
    <p class="field-error" id="image-err" role="alert" hidden></p>
    <div class="button-row">
      <button type="submit" class="btn btn-primary" id="image-submit">Insérer l’image</button>
      <button type="button" class="btn btn-outline" data-close>Annuler</button>
    </div>
  </form>
</dialog>

<!-- Vidéo -->
<dialog id="video-dialog" class="dialog" aria-labelledby="video-title">
  <form method="dialog" class="stack" id="video-form" novalidate>
    <h2 id="video-title">Ajouter une vidéo</h2>
    <p class="hint">Collez l’adresse d’une vidéo <strong>YouTube</strong> ou <strong>Vimeo</strong> (celle de la barre d’adresse du navigateur).</p>
    <div class="field">
      <label for="video-url">Adresse de la vidéo</label>
      <input id="video-url" type="url" inputmode="url" autocapitalize="off" placeholder="https://www.youtube.com/watch?v=…" aria-describedby="video-err">
    </div>
    <div class="field">
      <label for="video-caption">Légende ou titre de la vidéo</label>
      <input id="video-caption" type="text" maxlength="200" aria-describedby="video-caption-hint">
      <p class="hint" id="video-caption-hint">Recommandé : elle décrit la vidéo aux lecteurs d’écran.</p>
    </div>
    <p class="field-error" id="video-err" role="alert" hidden></p>
    <div class="button-row">
      <button type="submit" class="btn btn-primary">Insérer la vidéo</button>
      <button type="button" class="btn btn-outline" data-close>Annuler</button>
    </div>
  </form>
</dialog>
<?php admin_foot();
