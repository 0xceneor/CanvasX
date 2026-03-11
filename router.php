<?php
declare(strict_types=1);

/**
 * Development router — php -S localhost:8080 router.php
 * Handles URL routing without Nginx for local dev.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - match expression (already present, expanded)
 *  - str_ends_with / str_starts_with
 *  - Typed variables
 */

$uri = '/' . ltrim((string)parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Block internal directories
if (preg_match('#^/(data|\.env|config/|ws/|db/)#', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Serve real static files from public/ (no PHP)
$public_file = __DIR__ . '/public' . $uri;
if (is_file($public_file) && !str_ends_with($public_file, '.php')) {
    return false;
}

// /c/<id>
if (preg_match('#^/c/([a-zA-Z0-9]+)/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/public/canvas.php';
    exit;
}

// Named page routes
$page = match (rtrim($uri, '/')) {
    '/generate' => __DIR__ . '/public/generate.php',
    '/docs'     => __DIR__ . '/public/docs.php',
    '/og.php'   => __DIR__ . '/public/og.php',
    '/', ''     => __DIR__ . '/public/index.php',
    default     => null,
};

if ($page !== null) {
    require $page;
    exit;
}

// /api/*.php
if (preg_match('#^/api/([a-z\-]+\.php)$#', $uri, $m)) {
    $f = __DIR__ . '/public/api/' . $m[1];
    if (is_file($f)) { require $f; exit; }
}

// /pipeline/*.php
if (preg_match('#^/pipeline/([a-z\-]+\.php)$#', $uri, $m)) {
    $f = __DIR__ . '/pipeline/' . $m[1];
    if (is_file($f)) { require $f; exit; }
}

// /assets/*  — serve with correct MIME via match
if (preg_match('#^/assets/(.+)$#', $uri, $m)) {
    $f = __DIR__ . '/public/assets/' . $m[1];
    if (is_file($f)) {
        $mime = match (pathinfo($f, PATHINFO_EXTENSION)) {
            'js'  => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        readfile($f);
        exit;
    }
}

// /components/*.js
if (preg_match('#^/components/(.+\.js)$#', $uri, $m)) {
    $f = __DIR__ . '/public/components/' . $m[1];
    if (is_file($f)) {
        header('Content-Type: application/javascript');
        readfile($f);
        exit;
    }
}

// Fallback: try public/<uri>.php
$php_fallback = __DIR__ . '/public' . $uri . '.php';
if (is_file($php_fallback)) {
    require $php_fallback;
    exit;
}

http_response_code(404);
echo '404 — ' . htmlspecialchars($uri);
