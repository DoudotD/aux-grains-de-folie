<?php
/**
 * Socle commun : constantes, helpers, en-têtes de sécurité.
 * Compatible PHP 8.0+.
 */
declare(strict_types=1);

if (defined('GDF')) {
    return;
}
define('GDF', true);

define('ROOT', dirname(__DIR__));
// Les chemins peuvent être surchargés (tests) par variables d'environnement.
define('DATA_DIR', rtrim((string)(getenv('GDF_DATA_DIR') ?: ROOT . '/data'), '/'));
define('UPLOAD_DIR', rtrim((string)(getenv('GDF_UPLOAD_DIR') ?: ROOT . '/uploads'), '/'));

const PER_PAGE = 10;               // publications par page sur la page Actualités
const MAX_UPLOAD_BYTES = 10485760; // plafond voulu par l'application (10 Mo)
const MAX_CTAS = 3;

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

const FR_MONTHS = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

/** Convertit « 8M », « 512K »… (valeurs de php.ini) en octets. */
function ini_bytes(string $v): int
{
    $v = trim($v);
    $n = (int)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024; // no break
        case 'm': $n *= 1024; // no break
        case 'k': $n *= 1024;
    }
    return $n;
}

/** Taille maximale réellement acceptée par le serveur pour une image envoyée. */
function upload_limit_bytes(): int
{
    $limits = array_filter([
        MAX_UPLOAD_BYTES,
        ini_bytes((string)ini_get('upload_max_filesize')),
        ini_bytes((string)ini_get('post_max_size')) - 65536, // marge pour le reste du formulaire
    ], fn($n) => $n > 0);
    return max(1, min($limits));
}

/** « 2 Mo », « 512 Ko »… */
function format_bytes(int $b): string
{
    return $b >= 1048576
        ? rtrim(rtrim(number_format($b / 1048576, 1, ',', ''), '0'), ',') . ' Mo'
        : max(1, (int)round($b / 1024)) . ' Ko';
}

/** Échappement HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** « 13 octobre 2026 » */
function fr_date(int $ts): string
{
    return (int)date('j', $ts) . ((int)date('j', $ts) === 1 ? 'er' : '') . ' '
        . FR_MONTHS[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** « 13 octobre 2026 à 08h30 » */
function fr_datetime(int $ts): string
{
    return fr_date($ts) . ' à ' . date('H\hi', $ts);
}

function send_security_headers(bool $noStore = false, string $extraImgSrc = ''): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:" . ($extraImgSrc !== '' ? ' ' . $extraImgSrc : '') . "; style-src 'self'; script-src 'self'; "
        . "frame-src 'self' https://www.youtube-nocookie.com https://player.vimeo.com; "
        . "frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    if ($noStore) {
        header('Cache-Control: no-store');
    }
}

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/auth.php';
