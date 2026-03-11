<?php
declare(strict_types=1);

/**
 * Database & config utilities — PHP 8.4.
 *
 * PHP 8.4 features used:
 *  - declare(strict_types=1)
 *  - Named arguments on PDO constructor (8.0)
 *  - Union type (PDO|null) with ??= singleton pattern
 *  - array_map + closure for nanoid (first-class callables 8.1)
 *  - Env final class replacing procedural load_env()
 *  - Typed static properties
 */

final class Env
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;

        $file = dirname(__DIR__) . '/.env';
        if (!is_file($file)) return;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }

        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        self::load();
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }
}

/**
 * PDO singleton — lazy-initialised via ??= on first access.
 * Named arguments on PDO constructor (PHP 8.0+).
 */
function db(): PDO
{
    static PDO|null $pdo = null;

    return $pdo ??= new PDO(
        dsn: sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            Env::get('DB_HOST', '127.0.0.1'),
            Env::get('DB_PORT', '5432'),
            Env::get('DB_NAME', 'canvasnew'),
        ),
        username: Env::get('DB_USER', 'canvasnew'),
        password: Env::get('DB_PASS'),
        options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    );
}

/**
 * Cryptographically random nanoid-style ID.
 * Uses array_map + closure (first-class callable syntax PHP 8.1+).
 */
function nanoid(int $len = 8): string
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return implode('', array_map(
        fn(): string => $chars[random_int(0, 61)],
        range(1, $len),
    ));
}

/** Generate a tok_-prefixed edit token. */
function make_edit_token(): string
{
    return 'tok_' . bin2hex(random_bytes(20));
}

/** Convenience wrapper — kept for call-site compatibility. */
function env(string $key, string $default = ''): string
{
    return Env::get($key, $default);
}
