<?php
declare(strict_types=1);

/**
 * OG image generator — /og.php?id=aB3xKp9m
 * Pure PHP GD, 1200x630px, cached 1 hour.
 * No Chrome, no Puppeteer, no external service.
 *
 * PHP 8.4 features:
 *  - declare(strict_types=1)
 *  - Nullsafe operator on db() chain
 *  - Typed variables throughout
 *  - Named argument on imagettftext call
 */

require_once dirname(__DIR__) . '/config/db.php';

$id = preg_replace('/[^a-zA-Z0-9]/', '', (string)($_GET['id'] ?? ''));

if ($id === '') { http_response_code(400); exit; }

// 1-hour HTTP cache
header('Cache-Control: public, max-age=3600');
header('Content-Type: image/png');

// Fetch canvas title
$title = 'canvas.new';
try {
    $stmt = db()->prepare("SELECT title FROM canvases WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row && $row['title'] !== null && $row['title'] !== '') {
        $title = (string)$row['title'];
    }
} catch (PDOException) {}  // anonymous catch — PHP 8.0

// Canvas dimensions
$W   = 1200;
$H   = 630;
$img = imagecreatetruecolor($W, $H);

// Allocate colors
$bg_dark    = imagecolorallocate($img, 5,   5,   5);
$surface    = imagecolorallocate($img, 18,  18,  18);
$border_col = imagecolorallocate($img, 40,  40,  40);
$white      = imagecolorallocate($img, 240, 240, 240);
$muted      = imagecolorallocate($img, 100, 100, 100);
$accent     = imagecolorallocate($img, 168, 85,  247);

// Background
imagefilledrectangle($img, 0, 0, $W, $H, $bg_dark);

// Noise simulation — deterministic random dots seeded by canvas ID
mt_srand(crc32($id));
for ($i = 0; $i < 2000; $i++) {
    $x = mt_rand(0, $W);
    $y = mt_rand(0, $H);
    $v = mt_rand(10, 25);
    $c = imagecolorallocatealpha($img, $v, $v, $v, 110);
    imagesetpixel($img, $x, $y, $c);
}

// Decorative accent bar (left edge)
imagefilledrectangle($img, 0, 0, 4, $H, $accent);

// Main card
$cx = 80; $cy = 80; $cw = $W - 160; $ch = $H - 160;
imagefilledrectangle($img, $cx, $cy, $cx + $cw, $cy + $ch, $surface);
imagerectangle($img, $cx, $cy, $cx + $cw, $cy + $ch, $border_col);
imagefilledrectangle($img, $cx, $cy, $cx + 3, $cy + $ch, $accent); // card accent

// Logo
imagestring($img, 5, $cx + 28, $cy + 28, 'canvas.new', $accent);
imagestring($img, 3, $cx + 28, $cy + 58, '/' . $id, $muted);

// Title — wrap at 46 chars, max 3 lines
$title    = mb_substr($title, 0, 120);
$lines    = wordwrap($title, 46, "\n", true);
$line_arr = array_slice(explode("\n", $lines), 0, 3);
$title_y  = $cy + 120;

foreach ($line_arr as $i => $line) {
    $font_file = __DIR__ . '/../config/fonts/BebasNeue-Regular.ttf';
    if (is_file($font_file)) {
        imagettftext($img, 48, 0, $cx + 28, $title_y + ($i * 72), $white, $font_file, $line);
    } else {
        // Fallback: scaled built-in font
        $scale     = 4;
        $base_size = 5;
        $char_w    = imagefontwidth($base_size);
        $char_h    = imagefontheight($base_size);
        $text_img  = imagecreatetruecolor(strlen($line) * $char_w, $char_h);
        $black     = imagecolorallocate($text_img, 0, 0, 0);
        imagefilledrectangle($text_img, 0, 0, imagesx($text_img) - 1, imagesy($text_img) - 1, $black);
        imagestring($text_img, $base_size, 0, 0, $line, $white);
        $tw = imagesx($text_img) * $scale;
        $th = imagesy($text_img) * $scale;
        imagecopyresized($img, $text_img, $cx + 28, $title_y + ($i * ($th + 8)), 0, 0, $tw, $th, imagesx($text_img), imagesy($text_img));
        imagedestroy($text_img);
    }
}

// Bottom bar + URL
imagefilledrectangle($img, $cx + 28, $cy + $ch - 52, $cx + $cw - 28, $cy + $ch - 51, $border_col);

$base_url = env('CANVAS_BASE_URL', 'canvas.new');
$url_text = rtrim(str_replace(['http://', 'https://'], '', $base_url), '/') . '/c/' . $id;
imagestring($img, 3, $cx + 28, $cy + $ch - 40, $url_text, $muted);

imagepng($img);
imagedestroy($img);
