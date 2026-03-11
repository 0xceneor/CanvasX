<?php
/**
 * Development router — php -S localhost:8080 router.php
 * Handles all URL routing without Nginx.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim($uri, '/');

// Block data, config, ws internals, .env
if (preg_match('#^/(data|\.env|config/|ws/|db/)#', $uri)) {
    http_response_code(403); echo 'Forbidden'; exit;
}

// Serve real static files from public/
$public_file = __DIR__ . '/public' . $uri;
if (is_file($public_file) && !str_ends_with($public_file, '.php')) {
    return false;
}

// /c/<id>
if (preg_match('#^/c/([a-zA-Z0-9]+)/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/public/canvas.php'; exit;
}

// /generate
if (in_array($uri, ['/generate', '/generate/'])) {
    require __DIR__ . '/public/generate.php'; exit;
}

// /docs
if (in_array($uri, ['/docs', '/docs/'])) {
    require __DIR__ . '/public/docs.php'; exit;
}

// /og.php
if ($uri === '/og.php') {
    require __DIR__ . '/public/og.php'; exit;
}

// /api/*
if (preg_match('#^/api/([a-z\-]+\.php)$#', $uri, $m)) {
    $f = __DIR__ . '/public/api/' . $m[1];
    if (is_file($f)) { require $f; exit; }
}

// /pipeline/*
if (preg_match('#^/pipeline/([a-z\-]+\.php)$#', $uri, $m)) {
    $f = __DIR__ . '/pipeline/' . $m[1];
    if (is_file($f)) { require $f; exit; }
}

// /assets/*
if (preg_match('#^/assets/(.+)$#', $uri, $m)) {
    $f = __DIR__ . '/public/assets/' . $m[1];
    if (is_file($f)) {
        $mime = match(pathinfo($f, PATHINFO_EXTENSION)) {
            'js'  => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        readfile($f); exit;
    }
}

// /components/*
if (preg_match('#^/components/(.+\.js)$#', $uri, $m)) {
    $f = __DIR__ . '/public/components/' . $m[1];
    if (is_file($f)) {
        header('Content-Type: application/javascript');
        readfile($f); exit;
    }
}

// Root → generator
if (in_array($uri, ['/', ''])) {
    require __DIR__ . '/public/index.php'; exit;
}

// Fallback: try public/<uri>.php
$php_file = __DIR__ . '/public' . $uri . '.php';
if (is_file($php_file)) { require $php_file; exit; }

http_response_code(404);
echo '404 — ' . htmlspecialchars($uri);
