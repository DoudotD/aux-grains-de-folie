<?php
/** Lecture et validation du formulaire d'une actualité. */
declare(strict_types=1);

/**
 * Construit une actualité à partir des données envoyées par l'éditeur.
 * Tout est nettoyé ici (HTML, URL, images) : on ne fait jamais confiance au navigateur.
 */
function post_from_input(array $in, ?array $existing, array $user): array
{
    $tz = new DateTimeZone('Europe/Paris');
    $ts = null;
    $rawDate = trim((string)($in['published_at'] ?? ''));
    if ($rawDate !== '') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $rawDate, $tz);
        $ts = $dt ? $dt->getTimestamp() : null;
    } else {
        $ts = $existing['published_at'] ?? (int)(floor(time() / 60) * 60);
    }

    $coverSrc = san_image_src((string)($in['cover_src'] ?? ''));
    return [
        'id' => $existing['id'] ?? new_id(),
        'title' => trim(mb_substr((string)($in['title'] ?? ''), 0, 120)),
        'author' => trim(mb_substr((string)($in['author'] ?? ($existing['author'] ?? $user['name'])), 0, 80)),
        'status' => $existing['status'] ?? 'draft',
        'published_at' => $ts, // null si la date saisie est invalide
        'cover' => $coverSrc ? ['src' => $coverSrc, 'alt' => trim(mb_substr((string)($in['cover_alt'] ?? ''), 0, 250))] : null,
        'content' => sanitize_content((string)($in['content'] ?? '')),
        'ctas' => clean_ctas($in['cta'] ?? []),
        'created_at' => $existing['created_at'] ?? time(),
        'updated_at' => time(),
        'updated_by' => $user['name'],
    ];
}

/**
 * @return array<string,string> erreurs : identifiant du champ → message
 */
function post_errors(array $p, bool $publishing): array
{
    $err = [];
    if ($p['title'] === '') {
        $err['title'] = 'Donnez un titre à votre actualité.';
    }
    if ($p['author'] === '') {
        $err['author'] = 'Indiquez le nom de l’auteur.';
    }
    if ($p['published_at'] === null) {
        $err['published_at'] = 'La date de publication n’est pas valide.';
    }
    if ($p['cover'] && $p['cover']['alt'] === '') {
        $err['cover_alt'] = 'Décrivez l’image à la une en quelques mots (pour les personnes qui ne la voient pas).';
    }
    if ($publishing && $p['content'] === '') {
        $err['editor'] = 'Écrivez un peu de texte (ou ajoutez une image) avant de publier.';
    }
    foreach ($p['ctas'] as $i => $c) {
        $n = $i + 1;
        if ($c['label'] === '') {
            $err["cta-$i-label"] = "Bouton $n : indiquez le texte du bouton.";
        }
        if ($c['url'] === '') {
            $err["cta-$i-url"] = "Bouton $n : indiquez l’adresse du lien.";
        } elseif (san_url($c['url']) === null) {
            $err["cta-$i-url"] = "Bouton $n : cette adresse n’est pas valide (exemples : https://…, tel:0251000000, mailto:nom@exemple.fr).";
        }
    }
    return $err;
}
