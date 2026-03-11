<?php
/**
 * PDO connection singleton.
 * Usage: $pdo = db();
 */

function load_env(): void {
    static $loaded = false;
    if ($loaded) return;
    $env_file = dirname(__DIR__) . '/.env';
    if (!file_exists($env_file)) return;
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
        $_ENV[trim($k)] = trim($v);
    }
    $loaded = true;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    load_env();

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '5432';
    $name = getenv('DB_NAME') ?: 'canvasnew';
    $user = getenv('DB_USER') ?: 'canvasnew';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

/**
 * Generate a cryptographically random nanoid-style ID.
 */
function nanoid(int $len = 8): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $id = '';
    for ($i = 0; $i < $len; $i++) {
        $id .= $chars[random_int(0, 61)];
    }
    return $id;
}

/**
 * Generate an edit token.
 */
function make_edit_token(): string {
    return 'tok_' . bin2hex(random_bytes(20));
}

/**
 * Load .env and return a value.
 */
function env(string $key, string $default = ''): string {
    load_env();
    $v = getenv($key);
    return $v !== false ? $v : $default;
}
