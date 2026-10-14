<?php
/**
 * Jeu de données de DÉMONSTRATION (ne pas déployer en production).
 * Usage : GDF_DATA_DIR=/chemin/vers/data-demo php tools/seed-demo.php [nombre]
 * Génère des actualités fictives réparties sur plusieurs mois pour tester
 * la pagination et les archives. Ne touche pas aux comptes.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Ce script se lance en ligne de commande.');
}
require __DIR__ . '/../inc/bootstrap.php';

$n = max(1, (int)($argv[1] ?? 25));
$bodies = [
    '<p>Ceci est une <strong>actualité de démonstration</strong>. Elle sert à tester la mise en page, la pagination et les archives du site.</p><h2>Un titre dans le texte</h2><p>Du texte en <em>italique</em>, une <a href="https://www.exemple.fr">liste de liens</a> et une liste :</p><ul><li>Premier point</li><li>Deuxième point</li></ul>',
    '<p>Un second exemple, plus court. Les contenus les plus récents apparaissent toujours <strong>en haut</strong> de la page.</p><blockquote><p>Une citation mise en valeur.</p></blockquote>',
];
$posts = [];
$now = time();
for ($i = 0; $i < $n; $i++) {
    $ts = $now - (int)($i * 11.5 * 86400) - 3600 * ($i % 7);
    $posts[] = [
        'id' => new_id(),
        'title' => '[Démo] Actualité n° ' . ($n - $i),
        'author' => $i % 2 ? 'Tiffany Dudit' : 'Yoann Couton',
        'status' => 'published',
        'published_at' => $ts,
        'cover' => null,
        'content' => sanitize_content($bodies[$i % 2]),
        'ctas' => $i % 3 === 0 ? [['label' => 'Nous appeler', 'url' => 'tel:0251000000', 'style' => 'primary'], ['label' => 'Voir les horaires', 'url' => 'index.php#infos', 'style' => 'secondary']] : [],
        'created_at' => $ts,
        'updated_at' => $ts,
        'updated_by' => 'démo',
    ];
}
store_update('posts', function (array &$d) use ($posts) {
    $d['items'] = $posts;
});
echo "$n actualités de démonstration créées dans " . DATA_DIR . "\n";
