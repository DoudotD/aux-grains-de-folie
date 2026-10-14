<?php
/**
 * Authentification (email + mot de passe), sessions, protection CSRF,
 * limitation des tentatives de connexion.
 */
declare(strict_types=1);

const SESSION_IDLE_SECONDS = 4 * 3600;   // déconnexion après 4 h d'inactivité
const LOGIN_MAX_FAILS = 5;               // tentatives avant blocage
const LOGIN_WINDOW = 900;                // fenêtre / durée de blocage : 15 min
const PASSWORD_MIN = 10;

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/';
    session_name('gdf_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
    if (isset($_SESSION['last']) && time() - (int)$_SESSION['last'] > SESSION_IDLE_SECONDS) {
        $_SESSION = [];
    }
    $_SESSION['last'] = time();
}

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    session_boot();
    $cache = isset($_SESSION['uid']) ? user_by_id((string)$_SESSION['uid']) : null;
    return $cache;
}

/** Redirige vers la page de connexion si personne n'est connecté. */
function require_login(): array
{
    send_security_headers(true);
    $u = current_user();
    if (!$u) {
        redirect('index.php');
    }
    return $u;
}

function auth_login(array $user): void
{
    session_boot();
    session_regenerate_id(true);
    $_SESSION['uid'] = $user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function auth_logout(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

/* ------------------------------ CSRF ------------------------------ */

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['csrf'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** À appeler en début de traitement de tout formulaire POST. */
function csrf_verify(): void
{
    $sent = (string)($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Session expirée ou requête invalide. Retournez en arrière, rechargez la page et recommencez.');
    }
}

/* ------------------------ Limitation des tentatives ------------------------ */

function throttle_keys(string $email = ''): array
{
    $keys = ['ip:' . ($_SERVER['REMOTE_ADDR'] ?? '?')];
    if ($email !== '') {
        $keys[] = 'mail:' . mb_strtolower($email);
    }
    return array_map(fn($k) => hash('sha256', $k), $keys);
}

/** Secondes d'attente restantes (0 = autorisé). */
function throttle_wait(string $email = ''): int
{
    $d = store_read('throttle');
    $wait = 0;
    foreach (throttle_keys($email) as $k) {
        $recent = array_filter($d[$k] ?? [], fn($t) => $t > time() - LOGIN_WINDOW);
        if (count($recent) >= LOGIN_MAX_FAILS) {
            $wait = max($wait, min($recent) + LOGIN_WINDOW - time());
        }
    }
    return $wait;
}

function throttle_fail(string $email = ''): void
{
    $keys = throttle_keys($email);
    store_update('throttle', function (array &$d) use ($keys) {
        foreach ($d as $k => $times) {
            $d[$k] = array_values(array_filter($times, fn($t) => $t > time() - LOGIN_WINDOW));
            if (!$d[$k]) {
                unset($d[$k]);
            }
        }
        foreach ($keys as $k) {
            $d[$k][] = time();
        }
    });
}

function throttle_clear(string $email = ''): void
{
    $keys = throttle_keys($email);
    store_update('throttle', function (array &$d) use ($keys) {
        foreach ($keys as $k) {
            unset($d[$k]);
        }
    });
}

/* ------------------------------ Installation ------------------------------ */

/**
 * Clé d'installation : générée une fois, écrite dans data/setup.json.php.
 * Elle empêche un inconnu de créer le premier compte avant vous.
 */
function setup_key(): string
{
    $key = store_read('setup')['key'] ?? '';
    if ($key === '') {
        $key = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        store_update('setup', function (array &$d) use ($key) {
            $d['key'] = $key;
        });
    }
    return $key;
}

/** Valide un mot de passe. @return string|null message d'erreur */
function password_problem(string $pw): ?string
{
    if (mb_strlen($pw) < PASSWORD_MIN) {
        return 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN . ' caractères.';
    }
    return null;
}

/* ------------------------------ Messages flash ------------------------------ */

function flash(string $type, string $message): void
{
    session_boot();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    session_boot();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
