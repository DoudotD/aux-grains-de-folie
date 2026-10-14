<?php
/**
 * Nettoyage du HTML produit par l'éditeur (liste blanche stricte).
 * Tout ce qui n'est pas explicitement autorisé est supprimé ou « déballé ».
 *
 * Balises conservées : p, h2, h3, strong, em, a, ul, ol, li, blockquote, br,
 * figure (image avec légende / vidéo), figcaption, img.
 */
declare(strict_types=1);

const SAN_DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea',
    'select', 'svg', 'math', 'noscript', 'template', 'head', 'title', 'meta', 'link', 'video', 'audio'];
const SAN_BLOCKS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'blockquote', 'figure', 'img', 'hr'];
const SAN_CONTAINERS = ['div', 'section', 'article', 'main', 'body', 'header', 'footer', 'aside', 'li',
    'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'center', 'dl', 'dt', 'dd', 'figcaption'];
const SAN_FIG_LAYOUTS = ['fig-left', 'fig-right', 'fig-center', 'fig-full'];

/** Point d'entrée : renvoie du HTML sûr (ou une chaîne vide). */
function sanitize_content(string $html): string
{
    $html = trim($html);
    if ($html === '' || strlen($html) > 400000) {
        return '';
    }
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }
    $out = san_blocks($body);
    $out = preg_replace('#<p>(?:\s|&nbsp;|\x{00A0})*</p>#u', '', $out) ?? $out;
    return trim($out);
}

/**
 * Rattrape les saisies de novices : « www.exemple.fr » → https://…,
 * « 02 51 00 00 00 » → tel:…, « nom@exemple.fr » → mailto:…
 */
function normalize_link(string $u): string
{
    $u = trim($u);
    if ($u === '' || preg_match('#^[a-z][a-z0-9+.\-]*:#i', $u) || preg_match('#^[/\#?]#', $u)) {
        return $u;
    }
    if (preg_match('/^[^\s@\/]+@[^\s@\/]+\.[a-z]{2,}$/i', $u)) {
        return 'mailto:' . $u;
    }
    if (preg_match('/^\+?[\d\s.\-()]{8,}$/', $u)) {
        return 'tel:' . preg_replace('/[\s.\-()]/', '', $u);
    }
    if (!preg_match('/\.(html?|php)([#?\/].*)?$/i', $u) && preg_match('#^(?:www\.)?[a-z0-9\-]+(?:\.[a-z0-9\-]+)*\.[a-z]{2,}(?:[/?\#].*)?$#i', $u)) {
        return 'https://' . $u;
    }
    return $u;
}

/** Vérifie/normalise une URL (liens et boutons). Renvoie null si refusée. */
function san_url(string $url): ?string
{
    $url = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $url) ?? '');
    if ($url === '' || strlen($url) > 2000) {
        return null;
    }
    if (preg_match('#^([a-z][a-z0-9+.\-]*):#i', $url, $m)) {
        return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $url : null;
    }
    if (str_starts_with($url, '//')) {
        return null;
    }
    return $url; // lien relatif ou ancre
}

/** @return string[] classes d'un élément */
function san_classes(DOMElement $el): array
{
    return preg_split('/\s+/', trim($el->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

function san_text(string $s): string
{
    return htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Contenu en ligne : texte, strong, em, a, br. */
function san_inline(DOMNode $parent): string
{
    $out = '';
    foreach ($parent->childNodes as $n) {
        if ($n->nodeType === XML_TEXT_NODE) {
            $out .= san_text((string)$n->nodeValue);
            continue;
        }
        if (!$n instanceof DOMElement) {
            continue;
        }
        $tag = strtolower($n->nodeName);
        if (in_array($tag, SAN_DROP, true)) {
            continue;
        }
        switch ($tag) {
            case 'br':
                $out .= '<br>';
                break;
            case 'strong':
            case 'b':
                $in = san_inline($n);
                $out .= trim(strip_tags($in)) === '' ? $in : '<strong>' . $in . '</strong>';
                break;
            case 'em':
            case 'i':
                $in = san_inline($n);
                $out .= trim(strip_tags($in)) === '' ? $in : '<em>' . $in . '</em>';
                break;
            case 'a':
                $href = san_url(normalize_link($n->getAttribute('href')));
                $in = san_inline($n);
                if ($href === null || trim(strip_tags($in)) === '') {
                    $out .= $in;
                } else {
                    $rel = preg_match('#^https?://#i', $href) ? ' rel="noopener noreferrer"' : '';
                    $out .= '<a href="' . e($href) . '"' . $rel . '>' . $in . '</a>';
                }
                break;
            default:
                $out .= san_inline($n); // balise inconnue : on garde seulement son contenu
        }
    }
    return $out;
}

/** Contenu de niveau bloc : regroupe le texte « libre » en paragraphes. */
function san_blocks(DOMNode $root): string
{
    $out = '';
    $run = '';
    $flush = function () use (&$out, &$run) {
        $plain = preg_replace('/[\s\x{00A0}]+/u', '', strip_tags(str_replace('<br>', '', $run))) ?? '';
        if ($plain !== '') {
            $out .= '<p>' . trim($run) . '</p>';
        }
        $run = '';
    };

    foreach ($root->childNodes as $n) {
        if ($n->nodeType === XML_TEXT_NODE) {
            $run .= san_text((string)$n->nodeValue);
            continue;
        }
        if (!$n instanceof DOMElement) {
            continue;
        }
        $tag = strtolower($n->nodeName);
        if (in_array($tag, SAN_DROP, true)) {
            continue;
        }
        if (in_array($tag, SAN_BLOCKS, true)) {
            $flush();
            $out .= san_block($n, $tag);
        } elseif (in_array($tag, SAN_CONTAINERS, true)) {
            $flush();
            $out .= san_blocks($n);
        } else {
            // élément en ligne (strong, em, a, span…) : on le traite comme du texte
            $wrap = new DOMDocument();
            $holder = $wrap->createElement('x');
            $holder->appendChild($wrap->importNode($n, true));
            $run .= san_inline($holder);
        }
    }
    $flush();
    return $out;
}

function san_block(DOMElement $n, string $tag): string
{
    switch ($tag) {
        case 'p':
            return '<p>' . trim(san_inline($n)) . '</p>';
        case 'h1':
        case 'h2':
            $in = trim(san_inline($n));
            return trim(strip_tags($in)) === '' ? '' : '<h2>' . $in . '</h2>';
        case 'h3':
        case 'h4':
        case 'h5':
        case 'h6':
            $in = trim(san_inline($n));
            return trim(strip_tags($in)) === '' ? '' : '<h3>' . $in . '</h3>';
        case 'ul':
        case 'ol':
            $items = '';
            foreach ($n->childNodes as $li) {
                if ($li instanceof DOMElement && strtolower($li->nodeName) === 'li') {
                    $items .= san_list_item($li);
                }
            }
            return $items === '' ? '' : "<$tag>$items</$tag>";
        case 'blockquote':
            $in = san_blocks($n);
            return $in === '' ? '' : '<blockquote>' . $in . '</blockquote>';
        case 'figure':
            return san_figure($n);
        case 'img':
            return san_image_figure($n, 'fig-center', '');
        default:
            return '';
    }
}

function san_list_item(DOMElement $li): string
{
    $inline = '';
    $nested = '';
    foreach ($li->childNodes as $c) {
        if ($c instanceof DOMElement && in_array(strtolower($c->nodeName), ['ul', 'ol'], true)) {
            $nested .= san_block($c, strtolower($c->nodeName));
        } elseif ($c instanceof DOMElement && in_array(strtolower($c->nodeName), ['p', 'div'], true)) {
            $inline .= san_inline($c) . ' ';
        } else {
            $holder = new DOMDocument();
            $x = $holder->createElement('x');
            $x->appendChild($holder->importNode($c, true));
            $inline .= san_inline($x);
        }
    }
    $inline = trim($inline);
    if (trim(strip_tags($inline)) === '' && $nested === '') {
        return '';
    }
    return '<li>' . $inline . $nested . '</li>';
}

/** URL d'image autorisée : uniquement les fichiers envoyés via l'espace de gestion. */
function san_image_src(string $src): ?string
{
    if (preg_match('#^(?:\.\./|/)?(uploads/[A-Za-z0-9/_\-.]+\.(?:jpe?g|png|webp|gif))$#', trim($src), $m)
        && !str_contains($m[1], '..')) {
        return $m[1];
    }
    return null;
}

function san_caption(DOMElement $fig): string
{
    foreach ($fig->getElementsByTagName('figcaption') as $cap) {
        $c = trim(san_inline($cap));
        return trim(strip_tags($c)) === '' ? '' : $c;
    }
    return '';
}

function san_image_figure(DOMElement $img, string $layout, string $caption): string
{
    $src = san_image_src($img->getAttribute('src'));
    if ($src === null) {
        return '';
    }
    $alt = mb_substr(trim($img->getAttribute('alt')), 0, 250);
    return '<figure class="' . $layout . '"><img src="' . e($src) . '" alt="' . e($alt) . '">'
        . ($caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : '') . '</figure>';
}

/** Identifiant de vidéo valide ? */
function video_id_ok(string $provider, string $id): bool
{
    return ($provider === 'youtube' && preg_match('/^[A-Za-z0-9_\-]{11}$/', $id) === 1)
        || ($provider === 'vimeo' && preg_match('/^\d{5,12}$/', $id) === 1);
}

function san_figure(DOMElement $fig): string
{
    $caption = san_caption($fig);
    $provider = strtolower($fig->getAttribute('data-provider'));
    $id = $fig->getAttribute('data-id');
    if ($provider !== '' && video_id_ok($provider, $id)) {
        return '<figure class="fig-video" data-provider="' . $provider . '" data-id="' . e($id) . '">'
            . ($caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : '') . '</figure>';
    }
    $layout = 'fig-center';
    foreach (san_classes($fig) as $c) {
        if (in_array($c, SAN_FIG_LAYOUTS, true)) {
            $layout = $c;
            break;
        }
    }
    foreach ($fig->getElementsByTagName('img') as $img) {
        return san_image_figure($img, $layout, $caption);
    }
    return '';
}
