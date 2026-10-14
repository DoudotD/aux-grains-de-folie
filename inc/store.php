<?php
/**
 * Stockage en fichiers JSON (aucune base de données requise).
 * Chaque fichier commence par une ligne PHP qui bloque toute lecture directe
 * via le web, même si la configuration du serveur ne protège pas le dossier.
 */
declare(strict_types=1);

const STORE_HEADER = "<?php http_response_code(403); exit; ?>\n";

function store_path(string $name): string
{
    return DATA_DIR . '/' . $name . '.json.php';
}

function store_decode(string $raw): array
{
    $nl = strpos($raw, "\n");
    $json = $nl === false ? '' : substr($raw, $nl + 1);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function store_read(string $name): array
{
    $path = store_path($name);
    if (!is_file($path)) {
        return [];
    }
    $fh = @fopen($path, 'rb');
    if (!$fh) {
        return [];
    }
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return store_decode((string)$raw);
}

/**
 * Lecture-modification-écriture atomique.
 * $fn reçoit les données par référence ; sa valeur de retour est renvoyée.
 */
function store_update(string $name, callable $fn)
{
    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0750, true);
    }
    $fh = fopen(store_path($name), 'c+');
    if (!$fh) {
        throw new RuntimeException("Impossible d'écrire dans le dossier data/. Vérifiez ses droits d'accès.");
    }
    flock($fh, LOCK_EX);
    $data = store_decode((string)stream_get_contents($fh));
    $result = $fn($data);
    $json = STORE_HEADER . json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
    );
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, $json);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

/* ------------------------------ Actualités ------------------------------ */

function new_id(): string
{
    return bin2hex(random_bytes(6));
}

/** @return array<int,array> */
function posts_all(): array
{
    $items = store_read('posts')['items'] ?? [];
    return is_array($items) ? array_values($items) : [];
}

function post_find(string $id): ?array
{
    foreach (posts_all() as $p) {
        if (($p['id'] ?? '') === $id) {
            return $p;
        }
    }
    return null;
}

function post_save(array $post): void
{
    store_update('posts', function (array &$d) use ($post) {
        $items = $d['items'] ?? [];
        $found = false;
        foreach ($items as $i => $p) {
            if ($p['id'] === $post['id']) {
                $items[$i] = $post;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $items[] = $post;
        }
        $d['items'] = array_values($items);
    });
}

function post_delete(string $id): void
{
    store_update('posts', function (array &$d) use ($id) {
        $d['items'] = array_values(array_filter($d['items'] ?? [], fn($p) => $p['id'] !== $id));
    });
}

/** 'draft' | 'scheduled' | 'published' */
function post_status(array $p): string
{
    if (($p['status'] ?? 'draft') !== 'published') {
        return 'draft';
    }
    return (int)$p['published_at'] > time() ? 'scheduled' : 'published';
}

function sort_posts_desc(array $posts): array
{
    usort($posts, fn($a, $b) => [$b['published_at'], $b['id']] <=> [$a['published_at'], $a['id']]);
    return $posts;
}

/** Actualités visibles par les visiteurs, les plus récentes en premier. */
function posts_public(): array
{
    return sort_posts_desc(array_values(array_filter(posts_all(), fn($p) => post_status($p) === 'published')));
}

/* ------------------------------ Comptes ------------------------------ */

function users_all(): array
{
    $u = store_read('users')['items'] ?? [];
    return is_array($u) ? array_values($u) : [];
}

function user_by_email(string $email): ?array
{
    $email = mb_strtolower(trim($email));
    foreach (users_all() as $u) {
        if ($u['email'] === $email) {
            return $u;
        }
    }
    return null;
}

function user_by_id(string $id): ?array
{
    foreach (users_all() as $u) {
        if ($u['id'] === $id) {
            return $u;
        }
    }
    return null;
}

function user_create(string $name, string $email, string $password): array
{
    $user = [
        'id' => new_id(),
        'name' => $name,
        'email' => mb_strtolower(trim($email)),
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => time(),
    ];
    store_update('users', function (array &$d) use ($user) {
        $d['items'] = array_merge($d['items'] ?? [], [$user]);
    });
    return $user;
}

function user_update(string $id, array $fields): void
{
    store_update('users', function (array &$d) use ($id, $fields) {
        foreach ($d['items'] ?? [] as $i => $u) {
            if ($u['id'] === $id) {
                $d['items'][$i] = array_merge($u, $fields);
            }
        }
    });
}

function user_delete(string $id): void
{
    store_update('users', function (array &$d) use ($id) {
        $d['items'] = array_values(array_filter($d['items'] ?? [], fn($u) => $u['id'] !== $id));
    });
}
