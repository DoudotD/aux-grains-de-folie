<?php
/**
 * Rendu HTML d'une actualité. Utilisé à l'identique par la page publique
 * et par l'aperçu de l'espace de gestion.
 */
declare(strict_types=1);

/** Analyse une URL YouTube / Vimeo. @return array{provider:string,id:string}|null */
function parse_video_url(string $url): ?array
{
    $url = trim($url);
    $patterns = [
        ['youtube', '#^(?:https?://)?(?:www\.|m\.)?youtu\.be/([A-Za-z0-9_\-]{11})#i'],
        ['youtube', '~^(?:https?://)?(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:watch\?(?:[^\s#]*&)?v=|embed/|shorts/|live/)([A-Za-z0-9_\-]{11})~i'],
        ['vimeo', '#^(?:https?://)?(?:www\.)?vimeo\.com/(?:[^\s/]+/)*?(\d{5,12})(?:$|[/?\#])#i'],
        ['vimeo', '#^(?:https?://)?player\.vimeo\.com/video/(\d{5,12})#i'],
    ];
    foreach ($patterns as [$provider, $re]) {
        if (preg_match($re, $url, $m)) {
            return ['provider' => $provider, 'id' => $m[1]];
        }
    }
    return null;
}

/**
 * Transforme le HTML stocké en HTML d'affichage.
 * - $base : préfixe des chemins (« » sur le site, « ../ » dans l'espace de gestion)
 */
function content_html(string $stored, string $base, string $title): string
{
    $html = $stored;
    // Hiérarchie de titres : la page a un h1, l'actualité un h2 → le contenu démarre à h3
    $html = str_replace(['<h3>', '</h3>'], ['<h4>', '</h4>'], $html);
    $html = str_replace(['<h2>', '</h2>'], ['<h3>', '</h3>'], $html);
    // Images : chemin + chargement différé
    $html = preg_replace('#<img src="(uploads/[^"]+)"#', '<img loading="lazy" decoding="async" src="' . $base . '$1"', $html) ?? $html;
    // Vidéos : intégration respectueuse de la vie privée
    $html = preg_replace_callback(
        '#<figure class="fig-video" data-provider="(youtube|vimeo)" data-id="([^"]+)">(.*?)</figure>#s',
        function ($m) use ($title) {
            [, $provider, $id, $inner] = $m;
            $label = trim(strip_tags($inner)) !== '' ? trim(strip_tags($inner)) : $title;
            $src = $provider === 'youtube'
                ? 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id)
                : 'https://player.vimeo.com/video/' . rawurlencode($id) . '?dnt=1';
            return '<figure class="fig-video"><div class="video-frame"><iframe src="' . e($src)
                . '" title="Vidéo : ' . e($label) . '" loading="lazy" allowfullscreen '
                . 'allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" '
                . 'referrerpolicy="strict-origin-when-cross-origin"></iframe></div>' . $inner . '</figure>';
        },
        $html
    ) ?? $html;
    return $html;
}

/** Version « éditeur » : même HTML mais sans transformation des titres ni des vidéos. */
function content_for_editor(string $stored, string $base): string
{
    return preg_replace('#<img src="(uploads/[^"]+)"#', '<img src="' . $base . '$1"', $stored) ?? $stored;
}

/** Valide et nettoie la liste des boutons d'action. */
function clean_ctas($raw): array
{
    $out = [];
    if (!is_array($raw)) {
        return $out;
    }
    foreach ($raw as $c) {
        if (!is_array($c)) {
            continue;
        }
        $label = trim(mb_substr((string)($c['label'] ?? ''), 0, 40));
        $url = normalize_link((string)($c['url'] ?? ''));
        if ($label === '' && $url === '') {
            continue;
        }
        $out[] = [
            'label' => $label,
            'url' => $url,
            'style' => (($c['style'] ?? '') === 'secondary') ? 'secondary' : 'primary',
        ];
        if (count($out) >= MAX_CTAS) {
            break;
        }
    }
    return $out;
}

function render_ctas(array $ctas): string
{
    $items = '';
    foreach ($ctas as $c) {
        $url = san_url((string)$c['url']);
        if ($url === null || trim((string)$c['label']) === '') {
            continue;
        }
        $external = (bool)preg_match('#^https?://#i', $url);
        $class = $c['style'] === 'secondary' ? 'btn btn-outline' : 'btn btn-primary';
        $items .= '<li><a class="' . $class . '" href="' . e($url) . '"'
            . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>'
            . e((string)$c['label'])
            . ($external ? '<span class="visually-hidden"> (s’ouvre dans un nouvel onglet)</span>' : '')
            . '</a></li>';
    }
    return $items === '' ? '' : '<ul class="post-ctas" aria-label="Liens utiles">' . $items . '</ul>';
}

/** HTML d'une actualité (balise <article>). */
function render_post(array $p, string $base = ''): string
{
    $ts = (int)$p['published_at'];
    $id = 'actu-' . preg_replace('/[^a-z0-9]/i', '', (string)$p['id']);
    $title = (string)$p['title'];
    $h = '<article class="post" id="' . e($id) . '" aria-labelledby="' . e($id) . '-titre">';

    if (!empty($p['cover']['src']) && ($src = san_image_src((string)$p['cover']['src'])) !== null) {
        $h .= '<figure class="post-cover"><img loading="lazy" decoding="async" src="' . e($base . $src)
            . '" alt="' . e((string)($p['cover']['alt'] ?? '')) . '"></figure>';
    }
    $h .= '<div class="post-body">';
    $h .= '<h2 class="post-title" id="' . e($id) . '-titre">' . e($title) . '</h2>';
    $h .= '<p class="post-meta"><span class="visually-hidden">Publié le </span>'
        . '<time datetime="' . e(date('c', $ts)) . '">' . e(fr_date($ts)) . '</time>'
        . ' <span aria-hidden="true">·</span> <span class="post-author"><span class="visually-hidden">par </span>'
        . e((string)$p['author']) . '</span></p>';
    $h .= '<div class="prose">' . content_html((string)$p['content'], $base, $title) . '</div>';
    $h .= render_ctas($p['ctas'] ?? []);
    $h .= '</div></article>';
    return $h;
}

/** Extrait texte (sans balises) d'une actualité, coupé proprement à un mot. */
function post_excerpt(array $p, int $max = 170): string
{
    $html = preg_replace('#</(p|h2|h3|li|blockquote|figcaption)>#', '$0 ', (string)$p['content']) ?? '';
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false && $space > $max * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,;:.–-") . '…';
}
