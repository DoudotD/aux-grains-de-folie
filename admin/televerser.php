<?php
/**
 * Envoi d'une image depuis l'éditeur. Répond en JSON.
 * L'image est vérifiée, orientée, redimensionnée et ré-encodée (GD) :
 * le fichier d'origine n'est jamais conservé tel quel.
 */
declare(strict_types=1);

require __DIR__ . '/../inc/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Méthode non autorisée.', 405);
}
csrf_verify();

$file = $_FILES['image'] ?? null;
if (!$file || !is_array($file)) {
    fail('Aucune image reçue. Elle dépasse peut-être la taille acceptée par ce site (' . format_bytes(upload_limit_bytes()) . ' maximum).');
}
switch ($file['error']) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        fail('Cette image est trop volumineuse : ce site accepte au maximum ' . format_bytes(upload_limit_bytes()) . ' par image.');
    default:
        fail('L’envoi de l’image a échoué. Réessayez.');
}
if ($file['size'] > upload_limit_bytes()) {
    fail('Cette image est trop volumineuse : ce site accepte au maximum ' . format_bytes(upload_limit_bytes()) . ' par image.');
}

$info = @getimagesize($file['tmp_name']);
$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
if (!$info || !isset($types[$info[2]])) {
    fail('Format non reconnu. Utilisez une image JPEG, PNG, WebP ou GIF.');
}
if ($info[0] * $info[1] > 50_000_000) {
    fail('Les dimensions de cette image sont trop grandes.');
}

$dir = UPLOAD_DIR . '/' . date('Y');
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    fail('Impossible d’enregistrer l’image : le dossier « uploads » n’est pas accessible en écriture.', 500);
}
$name = bin2hex(random_bytes(8));

if (function_exists('imagecreatefromstring')) {
    $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
    if (!$src) {
        fail('Cette image est illisible ou abîmée.');
    }
    // Orientation EXIF (photos de téléphone)
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
        if ($rot && ($rotated = imagerotate($src, $rot, 0))) {
            $src = $rotated;
        }
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $max = 1800;
    if (max($w, $h) > $max) {
        $ratio = $max / max($w, $h);
        $nw = max(1, (int)round($w * $ratio));
        $nh = max(1, (int)round($h * $ratio));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $src = $dst;
    }
    // Les images avec transparence restent en PNG, le reste en JPEG léger.
    $hasAlpha = in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true) && image_has_alpha($src);
    if ($hasAlpha) {
        $ext = 'png';
        $ok = imagepng($src, "$dir/$name.png", 6);
    } else {
        $ext = 'jpg';
        $flat = imagecreatetruecolor(imagesx($src), imagesy($src));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));
        $ok = imagejpeg($flat, "$dir/$name.jpg", 82);
    }
    if (!$ok) {
        fail('Impossible d’enregistrer l’image.', 500);
    }
} else {
    // GD indisponible : on conserve le fichier, déjà validé par getimagesize().
    $ext = $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name.$ext")) {
        fail('Impossible d’enregistrer l’image.', 500);
    }
}

echo json_encode(['ok' => true, 'src' => 'uploads/' . date('Y') . "/$name.$ext"], JSON_UNESCAPED_SLASHES);

function image_has_alpha($img): bool
{
    $w = imagesx($img);
    $h = imagesy($img);
    $stepX = max(1, intdiv($w, 60));
    $stepY = max(1, intdiv($h, 60));
    for ($x = 0; $x < $w; $x += $stepX) {
        for ($y = 0; $y < $h; $y += $stepY) {
            if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) {
                return true;
            }
        }
    }
    return false;
}
