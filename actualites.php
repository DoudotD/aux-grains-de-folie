<?php
/**
 * Page publique « Actualités ».
 * Paramètres : ?annee=2026&mois=10&page=2   |   ?tout=1 (tout sur une page)
 */
declare(strict_types=1);

require __DIR__ . '/inc/bootstrap.php';
send_security_headers();

$all = posts_public(); // déjà triées : les plus récentes en premier

/* --- Archives : années → mois --------------------------------------- */
$archive = [];
foreach ($all as $p) {
    $y = (int)date('Y', (int)$p['published_at']);
    $m = (int)date('n', (int)$p['published_at']);
    $archive[$y]['count'] = ($archive[$y]['count'] ?? 0) + 1;
    $archive[$y]['months'][$m] = ($archive[$y]['months'][$m] ?? 0) + 1;
}
krsort($archive);
foreach ($archive as &$a) {
    krsort($a['months']);
}
unset($a);

/* --- Filtres --------------------------------------------------------- */
$year = isset($_GET['annee']) ? (int)$_GET['annee'] : 0;
$year = isset($archive[$year]) ? $year : 0;
$month = isset($_GET['mois']) ? (int)$_GET['mois'] : 0;
$month = ($year && isset($archive[$year]['months'][$month])) ? $month : 0;

$filtered = array_values(array_filter($all, function ($p) use ($year, $month) {
    $ts = (int)$p['published_at'];
    return (!$year || (int)date('Y', $ts) === $year) && (!$month || (int)date('n', $ts) === $month);
}));

/* --- Pagination ------------------------------------------------------ */
$total = count($filtered);
$showAll = ($_GET['tout'] ?? '') === '1';
$pages = max(1, (int)ceil($total / PER_PAGE));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$items = $showAll ? $filtered : array_slice($filtered, ($page - 1) * PER_PAGE, PER_PAGE);

/** Construit une URL de la page en conservant (ou non) les filtres. */
function news_url(array $params = [], string $anchor = '#liste'): string
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== 0 && $v !== '' && $v !== false);
    return 'actualites.php' . ($params ? '?' . http_build_query($params) : '') . $anchor;
}

$periodLabel = $month ? FR_MONTHS[$month] . ' ' . $year : ($year ? (string)$year : '');
$title = 'Actualités' . ($periodLabel ? ' – ' . $periodLabel : '') . ' – Aux Grains de Folie, boulangerie à Coëx';
site_head($title, 'Les actualités de la boulangerie-pâtisserie Aux Grains de Folie à Coëx : nouveautés, événements et vie de la maison.');
site_header('actualites', '', '#liste');
?>

  <main id="contenu">
    <section class="page-hero" aria-labelledby="haut">
      <div class="container">
        <p class="eyebrow">Aux Grains de Folie</p>
        <h1 id="haut" tabindex="-1">Actualités</h1>
        <p class="page-hero-lead">Nouveautés, événements, saisons et coups de cœur : suivez la vie de la boulangerie.</p>
      </div>
    </section>

    <div class="container news-layout">

      <?php if ($archive): ?>
      <nav class="archive" aria-labelledby="archive-titre">
        <h2 id="archive-titre" class="archive-title">Archives</h2>
        <ul class="archive-list">
          <li>
            <a href="<?= e(news_url()) ?>"<?= !$year ? ' aria-current="page"' : '' ?>>Toutes les actualités <span class="count"><?= count($all) ?></span></a>
          </li>
          <?php foreach ($archive as $y => $info): ?>
          <li>
            <details<?= $year === $y ? ' open' : '' ?>>
              <summary><?= $y ?> <span class="count"><?= $info['count'] ?></span></summary>
              <ul>
                <li><a href="<?= e(news_url(['annee' => $y])) ?>"<?= ($year === $y && !$month) ? ' aria-current="page"' : '' ?>>Toute l’année <?= $y ?></a></li>
                <?php foreach ($info['months'] as $m => $n): ?>
                <li><a href="<?= e(news_url(['annee' => $y, 'mois' => $m])) ?>"<?= ($year === $y && $month === $m) ? ' aria-current="page"' : '' ?>><?= e(ucfirst(FR_MONTHS[$m])) ?> <span class="count"><?= $n ?></span></a></li>
                <?php endforeach; ?>
              </ul>
            </details>
          </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <?php endif; ?>

      <div class="news-main" id="liste" tabindex="-1">
        <?php if ($periodLabel): ?>
          <p class="news-filter">Actualités de <strong><?= e($periodLabel) ?></strong>
            · <a href="<?= e(news_url()) ?>">Voir toutes les actualités</a></p>
        <?php endif; ?>

        <?php if (!$items): ?>
          <p class="news-empty">Aucune actualité à afficher pour le moment. Revenez bientôt !</p>
        <?php else: ?>
          <p class="news-count" role="status">
            <?php if ($showAll): ?>
              <?= $total ?> actualité<?= $total > 1 ? 's' : '' ?> affichée<?= $total > 1 ? 's' : '' ?> sur une seule page
            <?php else: ?>
              <?= $total ?> actualité<?= $total > 1 ? 's' : '' ?><?= $pages > 1 ? ' · page ' . $page . ' sur ' . $pages : '' ?>
            <?php endif; ?>
          </p>

          <div class="posts">
            <?php foreach ($items as $p) { echo render_post($p), "\n"; } ?>
          </div>

          <?php if ($pages > 1 || $showAll): ?>
          <div class="pager-zone">
            <?php if (!$showAll && $pages > 1): ?>
            <nav class="pager" aria-label="Pagination des actualités">
              <ul>
                <?php if ($page > 1): ?>
                  <li><a class="pager-link" rel="prev" href="<?= e(news_url(['annee' => $year, 'mois' => $month, 'page' => $page - 1])) ?>"><span aria-hidden="true">←</span> Plus récentes</a></li>
                <?php endif; ?>
                <?php
                $shown = [];
                for ($i = 1; $i <= $pages; $i++) {
                    if ($i === 1 || $i === $pages || abs($i - $page) <= 1) {
                        $shown[] = $i;
                    }
                }
                $prevI = 0;
                foreach ($shown as $i):
                    if ($i - $prevI > 1) { echo '<li aria-hidden="true" class="pager-gap">…</li>'; }
                    $prevI = $i;
                    if ($i === $page): ?>
                      <li><a class="pager-link is-current" href="<?= e(news_url(['annee' => $year, 'mois' => $month, 'page' => $i])) ?>" aria-current="page"><span class="visually-hidden">Page </span><?= $i ?></a></li>
                    <?php else: ?>
                      <li><a class="pager-link" href="<?= e(news_url(['annee' => $year, 'mois' => $month, 'page' => $i])) ?>"><span class="visually-hidden">Page </span><?= $i ?></a></li>
                    <?php endif;
                endforeach; ?>
                <?php if ($page < $pages): ?>
                  <li><a class="pager-link" rel="next" href="<?= e(news_url(['annee' => $year, 'mois' => $month, 'page' => $page + 1])) ?>">Plus anciennes <span aria-hidden="true">→</span></a></li>
                <?php endif; ?>
              </ul>
            </nav>
            <p class="pager-all"><a href="<?= e(news_url(['annee' => $year, 'mois' => $month, 'tout' => 1])) ?>">Tout afficher sur une seule page</a></p>
            <?php elseif ($showAll && $total > PER_PAGE): ?>
            <p class="pager-all"><a href="<?= e(news_url(['annee' => $year, 'mois' => $month])) ?>">Revenir à l’affichage par <?= PER_PAGE ?> actualités</a></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <a class="to-top-btn" href="#haut" data-to-top>
    <span aria-hidden="true">↑</span> Haut<span class="visually-hidden"> : remonter en haut des actualités</span>
  </a>

<?php site_footer(); ?>
  <script src="assets/news.js" defer></script>
</body>
</html>
