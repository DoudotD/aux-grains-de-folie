<?php
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';
send_security_headers(false, 'https://images.unsplash.com');
$latest = array_slice(posts_public(), 0, 2); // les 2 actualités les plus récentes
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aux Grains de Folie – Boulangerie pâtisserie à Coëx, Vendée</title>
  <meta name="description" content="Aux Grains de Folie, boulangerie-pâtisserie à Coëx en Vendée, tenue par Yoann Couton et Tiffany Dudit. Plus de 10 médailles en concours de boulangerie et de pâtisserie. 8 place Georges Clemenceau.">
  <meta name="theme-color" content="#2a1a10">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232a1a10'/%3E%3Cpath d='M32 52V14' stroke='%23d9a441' stroke-width='3' stroke-linecap='round'/%3E%3Cpath d='M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z' fill='%23d9a441'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="style.css">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Bakery",
    "name": "Aux Grains de Folie",
    "description": "Boulangerie-pâtisserie artisanale à Coëx, en Vendée.",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "8 place Georges Clemenceau",
      "postalCode": "85220",
      "addressLocality": "Coëx",
      "addressRegion": "Vendée",
      "addressCountry": "FR"
    },
    "founder": [
      { "@type": "Person", "name": "Yoann Couton" },
      { "@type": "Person", "name": "Tiffany Dudit" }
    ]
  }
  </script>
</head>

<body>
  <a class="skip-link" href="#contenu">Aller au contenu principal</a>

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="#accueil" aria-label="Aux Grains de Folie – retour à l’accueil">
        <svg class="brand-mark" viewBox="0 0 64 64" width="32" height="32" aria-hidden="true" focusable="false">
          <path d="M32 58V12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <path fill="currentColor" d="M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z"/>
        </svg>
        <span class="brand-text">Aux Grains <span class="brand-de">de</span> Folie</span>
      </a>

      <nav aria-label="Navigation principale">
        <ul class="nav-list">
          <li><a href="#savoir-faire">Savoir-faire</a></li>
          <li><a href="#creations">Créations</a></li>
          <li><a href="#medailles">Médailles</a></li>
          <li><a class="nav-cta" href="actualites.php">Actualités</a></li>
          <li><a href="#infos">Horaires &amp; accès</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="contenu">

    <!-- ===== ACCUEIL ===== -->
    <section id="accueil" class="hero" aria-labelledby="titre-accueil">
      <img class="hero-img" alt=""
           src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=75&w=1200"
           srcset="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=70&w=700 700w,
                   https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=75&w=1200 1200w,
                   https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=75&w=1800 1800w,
                   https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=75&w=2400 2400w"
           sizes="100vw" width="2400" height="1600" fetchpriority="high">
      <div class="container hero-content">
        <p class="eyebrow">Boulangerie · Pâtisserie · Coëx, Vendée</p>
        <h1 id="titre-accueil">Aux Grains <span class="h1-de">de</span> Folie</h1>
        <p class="hero-lead">Le pain, les viennoiseries et les gourmandises d’une maison qui met un grain de folie dans chaque fournée.</p>
        <p class="hero-actions">
          <a class="btn btn-primary" href="#infos">Horaires &amp; accès</a>
          <a class="btn btn-ghost" href="#medailles">Nos médailles</a>
        </p>
      </div>
    </section>

    <!-- ===== SAVOIR-FAIRE ===== -->
    <section id="savoir-faire" class="section" aria-labelledby="titre-savoir-faire">
      <div class="container split">
        <div class="split-text">
          <p class="eyebrow eyebrow-dark">Notre maison</p>
          <h2 id="titre-savoir-faire">Le goût du travail bien fait</h2>
          <p>Au cœur de Coëx, <strong>Aux Grains de Folie</strong> est tenue par <strong>Yoann Couton</strong> et <strong>Tiffany Dudit</strong>. Ici, le pain se façonne à la main, les viennoiseries se dorent à point et les pâtisseries se composent avec exigence.</p>
          <p>Du premier pétrissage avant l’aube jusqu’à la dernière baguette de l’après-midi, une seule idée guide l’équipe : vous offrir le meilleur de la boulangerie et de la pâtisserie, tous les jours.</p>
          <p class="signature"><span class="visually-hidden">Signé : </span>Yoann Couton &amp; Tiffany Dudit</p>
        </div>

        <figure class="split-media">
          <img alt="Mains d’un boulanger pétrissant une pâte sur un plan de travail fariné, un rouleau en bois en arrière-plan"
               src="https://images.unsplash.com/photo-1517686469429-8bdb88b9f907?auto=format&fit=crop&q=75&w=900"
               srcset="https://images.unsplash.com/photo-1517686469429-8bdb88b9f907?auto=format&fit=crop&q=70&w=500 500w,
                       https://images.unsplash.com/photo-1517686469429-8bdb88b9f907?auto=format&fit=crop&q=75&w=900 900w,
                       https://images.unsplash.com/photo-1517686469429-8bdb88b9f907?auto=format&fit=crop&q=75&w=1300 1300w"
               sizes="(min-width: 60em) 40vw, 100vw" width="1300" height="975" loading="lazy">
        </figure>
      </div>
    </section>

    <?php if ($latest): ?>
    <!-- ===== ACTUALITÉS (aperçu) ===== -->
    <section id="actualites" class="section section-light" aria-labelledby="titre-actualites">
      <div class="container">
        <header class="section-head">
          <p class="eyebrow eyebrow-dark">Du nouveau à la boulangerie</p>
          <h2 id="titre-actualites">Dernières actualités</h2>
        </header>

        <ul class="cards cards-news">
          <?php foreach ($latest as $post):
            $ts = (int)$post['published_at'];
            $url = 'actualites.php#actu-' . preg_replace('/[^a-z0-9]/i', '', (string)$post['id']);
            $cover = !empty($post['cover']['src']) ? san_image_src((string)$post['cover']['src']) : null;
          ?>
          <li class="card card-news">
            <?php if ($cover): ?>
            <figure>
              <img src="<?= e($cover) ?>" alt="<?= e((string)($post['cover']['alt'] ?? '')) ?>" width="1100" height="825" loading="lazy" decoding="async">
            </figure>
            <?php else: ?>
            <figure class="card-ph" aria-hidden="true">
              <svg viewBox="0 0 64 64" width="72" height="72" focusable="false"><path d="M32 58V12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><path fill="currentColor" d="M32 24c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Zm0 12c-8 0-11-5-11-10 8 0 11 4 11 10Zm0 0c8 0 11-5 11-10-8 0-11 4-11 10Z"/></svg>
            </figure>
            <?php endif; ?>
            <div class="card-body">
              <p class="card-meta"><span class="visually-hidden">Publié le </span><time datetime="<?= e(date('c', $ts)) ?>"><?= e(fr_date($ts)) ?></time> <span aria-hidden="true">·</span> <span class="visually-hidden">par </span><?= e((string)$post['author']) ?></p>
              <h3><a class="card-link" href="<?= e($url) ?>"><?= e((string)$post['title']) ?></a></h3>
              <p><?= e(post_excerpt($post)) ?></p>
              <p class="card-more" aria-hidden="true">Lire la suite <span>→</span></p>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>

        <p class="section-cta"><a class="btn btn-primary btn-lg" href="actualites.php">Consulter toutes les actualités</a></p>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===== CRÉATIONS ===== -->
    <section id="creations" class="section section-paper" aria-labelledby="titre-creations">
      <div class="container">
        <header class="section-head">
          <p class="eyebrow eyebrow-dark">À la carte du jour</p>
          <h2 id="titre-creations">Nos créations</h2>
          <p class="section-intro">Trois univers, une même passion pour les belles matières et les gestes justes.</p>
        </header>

        <ul class="cards">
          <li class="card">
            <figure>
              <img alt="Pains de campagne rustiques empilés et tenus à la main, avec un épi de blé"
                   src="https://images.unsplash.com/photo-1567042661848-7161ce446f85?auto=format&fit=crop&q=75&w=800"
                   srcset="https://images.unsplash.com/photo-1567042661848-7161ce446f85?auto=format&fit=crop&q=70&w=480 480w,
                           https://images.unsplash.com/photo-1567042661848-7161ce446f85?auto=format&fit=crop&q=75&w=800 800w,
                           https://images.unsplash.com/photo-1567042661848-7161ce446f85?auto=format&fit=crop&q=75&w=1100 1100w"
                   sizes="(min-width: 60em) 30vw, (min-width: 40em) 45vw, 100vw" width="1100" height="825" loading="lazy">
            </figure>
            <div class="card-body">
              <h3>Les pains</h3>
              <p>Baguettes, pains de campagne et miches croustillantes : une croûte dorée, une mie savoureuse, cuits chaque jour.</p>
            </div>
          </li>

          <li class="card">
            <figure>
              <img alt="Croissants dorés et feuilletés tout juste sortis du four, sur du papier de cuisson"
                   src="https://images.unsplash.com/photo-1623334044303-241021148842?auto=format&fit=crop&q=75&w=800"
                   srcset="https://images.unsplash.com/photo-1623334044303-241021148842?auto=format&fit=crop&q=70&w=480 480w,
                           https://images.unsplash.com/photo-1623334044303-241021148842?auto=format&fit=crop&q=75&w=800 800w,
                           https://images.unsplash.com/photo-1623334044303-241021148842?auto=format&fit=crop&q=75&w=1100 1100w"
                   sizes="(min-width: 60em) 30vw, (min-width: 40em) 45vw, 100vw" width="1100" height="825" loading="lazy">
            </figure>
            <div class="card-body">
              <h3>Les viennoiseries</h3>
              <p>Croissants, pains au chocolat et feuilletés : le beurre, la légèreté et le croustillant d’un petit-déjeuner réussi.</p>
            </div>
          </li>

          <li class="card">
            <figure>
              <img alt="Vitrine de pâtisseries : entremets, tartes aux fruits rouges et créations au chocolat"
                   src="https://images.unsplash.com/photo-1583338917451-face2751d8d5?auto=format&fit=crop&q=75&w=800"
                   srcset="https://images.unsplash.com/photo-1583338917451-face2751d8d5?auto=format&fit=crop&q=70&w=480 480w,
                           https://images.unsplash.com/photo-1583338917451-face2751d8d5?auto=format&fit=crop&q=75&w=800 800w,
                           https://images.unsplash.com/photo-1583338917451-face2751d8d5?auto=format&fit=crop&q=75&w=1100 1100w"
                   sizes="(min-width: 60em) 30vw, (min-width: 40em) 45vw, 100vw" width="1100" height="825" loading="lazy">
            </figure>
            <div class="card-body">
              <h3>Les pâtisseries</h3>
              <p>Tartes, entremets et gourmandises, dressés avec soin pour les petits plaisirs comme pour les grandes tablées.</p>
            </div>
          </li>
        </ul>
      </div>
    </section>

    <!-- ===== GALERIE / AMBIANCE ===== -->
    <section class="section gallery-section" aria-labelledby="titre-vitrine">
      <div class="container">
        <header class="section-head">
          <p class="eyebrow eyebrow-dark">Ambiance</p>
          <h2 id="titre-vitrine">Dans la vitrine</h2>
        </header>

        <ul class="gallery">
          <li>
            <figure>
              <img alt="Étagères d’une boulangerie garnies de viennoiseries et de pains dorés derrière une vitrine"
                   src="https://images.unsplash.com/photo-1568254183919-78a4f43a2877?auto=format&fit=crop&q=75&w=900"
                   srcset="https://images.unsplash.com/photo-1568254183919-78a4f43a2877?auto=format&fit=crop&q=70&w=500 500w,
                           https://images.unsplash.com/photo-1568254183919-78a4f43a2877?auto=format&fit=crop&q=75&w=900 900w,
                           https://images.unsplash.com/photo-1568254183919-78a4f43a2877?auto=format&fit=crop&q=75&w=1300 1300w"
                   sizes="(min-width: 60em) 33vw, (min-width: 40em) 50vw, 100vw" width="1300" height="975" loading="lazy">
            </figure>
          </li>
          <li>
            <figure>
              <img alt="Gros pain de campagne coupé en deux, laissant voir sa mie alvéolée, sur un papier de boulanger"
                   src="https://images.unsplash.com/photo-1590301157172-7ba48dd1c2b2?auto=format&fit=crop&q=75&w=900"
                   srcset="https://images.unsplash.com/photo-1590301157172-7ba48dd1c2b2?auto=format&fit=crop&q=70&w=500 500w,
                           https://images.unsplash.com/photo-1590301157172-7ba48dd1c2b2?auto=format&fit=crop&q=75&w=900 900w,
                           https://images.unsplash.com/photo-1590301157172-7ba48dd1c2b2?auto=format&fit=crop&q=75&w=1300 1300w"
                   sizes="(min-width: 60em) 33vw, (min-width: 40em) 50vw, 100vw" width="1300" height="975" loading="lazy">
            </figure>
          </li>
          <li>
            <figure>
              <img alt="Croissants fraîchement cuits saupoudrés de farine, sur fond sombre"
                   src="https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=75&w=900"
                   srcset="https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=70&w=500 500w,
                           https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=75&w=900 900w,
                           https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=75&w=1300 1300w"
                   sizes="(min-width: 60em) 33vw, (min-width: 40em) 50vw, 100vw" width="1300" height="975" loading="lazy">
            </figure>
          </li>
          <li>
            <figure>
              <img alt="Petites pâtisseries et brioches présentées dans des corbeilles"
                   src="https://images.unsplash.com/photo-1534432182912-63863115e106?auto=format&fit=crop&q=75&w=900"
                   srcset="https://images.unsplash.com/photo-1534432182912-63863115e106?auto=format&fit=crop&q=70&w=500 500w,
                           https://images.unsplash.com/photo-1534432182912-63863115e106?auto=format&fit=crop&q=75&w=900 900w,
                           https://images.unsplash.com/photo-1534432182912-63863115e106?auto=format&fit=crop&q=75&w=1300 1300w"
                   sizes="(min-width: 60em) 33vw, (min-width: 40em) 50vw, 100vw" width="1300" height="975" loading="lazy">
            </figure>
          </li>
        </ul>
      </div>
    </section>

    <!-- ===== MÉDAILLES ===== -->
    <section id="medailles" class="section section-dark medals" aria-labelledby="titre-medailles">
      <div class="container medals-inner">
        <svg class="medal-icon" viewBox="0 0 96 120" width="96" height="120" aria-hidden="true" focusable="false">
          <path d="M26 4h16l10 30H36L26 4Zm44 0H54L44 34h16L70 4Z" fill="currentColor" opacity=".55"/>
          <circle cx="48" cy="76" r="34" fill="none" stroke="currentColor" stroke-width="4"/>
          <circle cx="48" cy="76" r="25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2 4"/>
          <path d="M48 96V60" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          <path fill="currentColor" d="M48 70c-6 0-8-4-8-8 6 0 8 3 8 8Zm0 0c6 0 8-4 8-8-6 0-8 3-8 8Zm0 9c-6 0-8-4-8-8 6 0 8 3 8 8Zm0 0c6 0 8-4 8-8-6 0-8 3-8 8Z"/>
        </svg>

        <div>
          <p class="eyebrow">Reconnaissance du métier</p>
          <h2 id="titre-medailles">Plus de <span class="big-number">10&nbsp;médailles</span></h2>
          <p class="medals-text">Plus de dix médailles nous ont été décernées lors de concours de boulangerie et de pâtisserie. Chacune récompense un savoir-faire, mais surtout un engagement : celui de vous proposer, jour après jour, des produits à la hauteur de votre confiance.</p>
        </div>
      </div>
    </section>

    <!-- ===== INFOS PRATIQUES ===== -->
    <section id="infos" class="section" aria-labelledby="titre-infos">
      <div class="container">
        <header class="section-head">
          <p class="eyebrow eyebrow-dark">Venez nous voir</p>
          <h2 id="titre-infos">Horaires &amp; accès</h2>
        </header>

        <div class="info-grid">
          <div class="info-card">
            <h3>Horaires d’ouverture</h3>
            <h4>Du Mardi au Samedi</h4>
            <table class="hours">
              <caption class="visually-hidden">Horaires d’ouverture de la boulangerie du mardi au samedi</caption>
              <thead>
                <tr><th scope="col">Créneau</th><th scope="col">Horaires</th></tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row">Matin</th>
                  <td><time datetime="06:30">6h30</time> – <time datetime="13:30">13h30</time></td>
                </tr>
                <tr>
                  <th scope="row">Après-midi</th>
                  <td><time datetime="15:30">15h30</time> – <time datetime="19:30">19h30</time></td>
                </tr>
              </tbody>
            </table>
            <h4>Le Dimanche</h4>
            <table class="hours">
              <caption class="visually-hidden">Horaires d’ouverture de la boulangerie le dimanche</caption>
              <thead>
                <tr><th scope="col">Créneau</th><th scope="col">Horaires</th></tr>
              </thead>
              <tbody>
                <tr>
                  <th scope="row">Matin</th>
                  <td><time datetime="06:30">6h30</time> – <time datetime="13:30">13h30</time></td>
                </tr>
                <tr>
                  <th scope="row">Après-midi</th>
                  <td>Fermé</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="info-card">
            <h3>Adresse</h3>
            <address>
              <strong>Aux Grains de Folie</strong><br>
              8 place Georges Clemenceau<br>
              85220 Coëx<br>
              Vendée
            </address>
            <p>
              <a class="btn btn-primary" href="https://www.google.com/maps/search/?api=1&amp;query=8+place+Georges+Clemenceau+85220+Co%C3%ABx" target="_blank" rel="noopener noreferrer">Itinéraire <span class="visually-hidden">vers la boulangerie (s’ouvre dans un nouvel onglet)</span><span aria-hidden="true"> ↗</span></a>
            </p>
          </div>
        </div>
      </div>
    </section>

  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <p class="footer-brand">Aux Grains de Folie</p>
      <p>Boulangerie-pâtisserie · 8 place Georges Clemenceau, 85220 Coëx</p>
      <p class="footer-small">Yoann Couton &amp; Tiffany Dudit · Photographies : <a href="https://unsplash.com/fr" target="_blank" rel="noopener noreferrer">Unsplash<span class="visually-hidden"> (s’ouvre dans un nouvel onglet)</span></a> · <a href="admin/">Espace de gestion</a></p>
      <p><a class="to-top" href="#accueil">Retour en haut de page</a></p>
    </div>
  </footer>
</body>
</html>
