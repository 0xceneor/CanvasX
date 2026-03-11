<?php
/**
 * OG image generator — /og.php?id=aB3xKp9m
 * Pure PHP GD, 1200x630px, cached 1 hour.
 * No Chrome, no Puppeteer, no external service.
 */
require_once dirname(__DIR__) . '/config/db.php';

$id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');

if (!$id) { http_response_code(400); exit; }

// 1-hour HTTP cache
header('Cache-Control: public, max-age=3600');
header('Content-Type: image/png');

// Fetch canvas title
$title = 'canvas.new';
try {
    $stmt = db()->prepare("SELECT title FROM canvases WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row && $row['title']) $title = $row['title'];
} catch (PDOException $_) {}

// Canvas dimensions
$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);

// Colors
$bg_dark    = imagecolorallocate($img, 5,   5,   5);
$surface    = imagecolorallocate($img, 18,  18,  18);
$border_col = imagecolorallocate($img, 40,  40,  40);
$white      = imagecolorallocate($img, 240, 240, 240);
$muted      = imagecolorallocate($img, 100, 100, 100);
$accent     = imagecolorallocate($img, 168, 85,  247);
$accent_dim = imagecolorallocate($img, 30,  15,  45);

// Background
imagefilledrectangle($img, 0, 0, $W, $H, $bg_dark);

// Noise simulation — sparse random dots
mt_srand(crc32($id));
for ($i = 0; $i < 2000; $i++) {
    $x = mt_rand(0, $W); $y = mt_rand(0, $H);
    $v = mt_rand(10, 25);
    $c = imagecolorallocatealpha($img, $v, $v, $v, 110);
    imagesetpixel($img, $x, $y, $c);
}

// Decorative accent rectangle (top-left corner)
imagefilledrectangle($img, 0, 0, 4, $H, $accent);

// Main card
$cx = 80; $cy = 80; $cw = $W - 160; $ch = $H - 160;
imagefilledrectangle($img, $cx, $cy, $cx + $cw, $cy + $ch, $surface);
imagerectangle($img, $cx, $cy, $cx + $cw, $cy + $ch, $border_col);

// Accent line on card left
imagefilledrectangle($img, $cx, $cy, $cx + 3, $cy + $ch, $accent);

// Logo — "canvas.new" branding top-left of card
$logo_text = 'canvas.new';
$logo_size = 5; // GD built-in font size (1-5)
imagestring($img, $logo_size, $cx + 28, $cy + 28, $logo_text, $accent);

// Canvas ID badge
$id_text = '/' . $id;
imagestring($img, 3, $cx + 28, $cy + 58, $id_text, $muted);

// Title — wrap at ~50 chars per line, max 2 lines
$title = mb_substr($title, 0, 120);
$lines = wordwrap($title, 46, "\n", true);
$line_arr = array_slice(explode("\n", $lines), 0, 3);

$title_y = $cy + 120;
foreach ($line_arr as $i => $line) {
    // Simulate large text with imagettftext if font available, else fall back to built-in
    $font_file = __DIR__ . '/../config/fonts/BebasNeue-Regular.ttf';
    if (file_exists($font_file)) {
        $bbox = imagettfbbox(48, 0, $font_file, $line);
        imagettftext($img, 48, 0, $cx + 28, $title_y + ($i * 72), $white, $font_file, $line);
    } else {
        // GD built-in: scale up with imagestring (max font size 5 ≈ 9px)
        // Use a bigger font by drawing scaled-up characters
        $scale = 4;
        $base_size = 5;
        $char_w = imagefontwidth($base_size);
        $char_h = imagefontheight($base_size);
        $text_img = imagecreatetruecolor(strlen($line) * $char_w, $char_h);
        $black = imagecolorallocate($text_img, 0, 0, 0);
        imagefilledrectangle($text_img, 0, 0, imagesx($text_img) - 1, imagesy($text_img) - 1, $black);
        imagestring($text_img, $base_size, 0, 0, $line, $white);
        $tw = imagesx($text_img) * $scale;
        $th = imagesy($text_img) * $scale;
        imagecopyresized($img, $text_img, $cx + 28, $title_y + ($i * ($th + 8)), 0, 0, $tw, $th, imagesx($text_img), imagesy($text_img));
        imagedestroy($text_img);
    }
}

// Bottom bar
imagefilledrectangle($img, $cx + 28, $cy + $ch - 52, $cx + $cw - 28, $cy + $ch - 51, $border_col);

$base_url = env('CANVAS_BASE_URL', 'canvas.new');
$url_text = rtrim(str_replace(['http://','https://'], '', $base_url), '/') . '/c/' . $id;
imagestring($img, 3, $cx + 28, $cy + $ch - 40, $url_text, $muted);

// Output
imagepng($img);
imagedestroy($img);
