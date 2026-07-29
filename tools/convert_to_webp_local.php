<?php
// Local batch WebP converter — run via Convert-To-WebP.bat (drag & drop) or:
//   php convert_to_webp_local.php <file-or-folder> [more paths...]
// Converts jpg/jpeg/png to webp using the same settings as the website
// (2560px long edge, quality 80, EXIF rotation baked in). Originals are
// untouched; results go into a "webp" subfolder next to them.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This is a local command-line tool.');
}

const MAX_LONG_EDGE = 2560;
const WEBP_QUALITY = 80;

if (!function_exists('imagewebp')) {
    fwrite(STDERR, "ERROR: this PHP does not have GD/WebP support.\n");
    exit(1);
}
@ini_set('memory_limit', '1024M');

function bakeExifOrientation($image, $filepath) {
    if (!function_exists('exif_read_data')) return $image;
    $exif = @exif_read_data($filepath);
    $orientation = (int)($exif['Orientation'] ?? 1);
    if ($orientation <= 1 || $orientation > 8) return $image;
    if ($orientation === 2 || $orientation === 7) imageflip($image, IMG_FLIP_HORIZONTAL);
    if ($orientation === 4 || $orientation === 5) imageflip($image, IMG_FLIP_VERTICAL);
    $angle = 0;
    if ($orientation === 3) $angle = 180;
    elseif (in_array($orientation, [5, 6, 7])) $angle = -90;
    elseif ($orientation === 8) $angle = 90;
    if ($angle !== 0) {
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated !== false) { imagedestroy($image); $image = $rotated; }
    }
    return $image;
}

function convertOne($filepath, $outDir) {
    @set_time_limit(120);
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    $image = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $image = @imagecreatefromjpeg($filepath);
        if ($image) $image = bakeExifOrientation($image, $filepath);
    } elseif ($ext === 'png') {
        $image = @imagecreatefrompng($filepath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
    }
    if (!$image) return "could not open";

    $w = imagesx($image); $h = imagesy($image);
    if (max($w, $h) > MAX_LONG_EDGE) {
        $scale = MAX_LONG_EDGE / max($w, $h);
        $resized = imagescale($image, (int)round($w * $scale), (int)round($h * $scale), IMG_BICUBIC);
        if ($resized !== false) { imagedestroy($image); $image = $resized; }
    }

    if (!is_dir($outDir)) mkdir($outDir, 0777, true);
    $outFile = $outDir . DIRECTORY_SEPARATOR . pathinfo($filepath, PATHINFO_FILENAME) . '.webp';
    $ok = imagewebp($image, $outFile, WEBP_QUALITY);
    imagedestroy($image);
    if (!$ok) { @unlink($outFile); return "encode failed"; }

    return [filesize($filepath), filesize($outFile)];
}

$targets = array_slice($argv, 1);
if (empty($targets)) {
    fwrite(STDERR, "Usage: drag files or folders onto Convert-To-WebP.bat\n");
    exit(1);
}

$files = [];
foreach ($targets as $t) {
    if (is_dir($t)) {
        foreach (glob(rtrim($t, '\\/') . '/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE) as $f) $files[] = $f;
    } elseif (is_file($t)) {
        $files[] = $t;
    }
}
$files = array_unique($files);

if (empty($files)) {
    echo "No jpg/png files found.\n";
    exit(0);
}

$done = 0; $failed = 0; $bytesIn = 0; $bytesOut = 0;
foreach ($files as $i => $f) {
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
    printf("[%d/%d] %s ... ", $i + 1, count($files), basename($f));
    $result = convertOne($f, dirname($f) . DIRECTORY_SEPARATOR . 'webp');
    if (is_array($result)) {
        $done++;
        $bytesIn += $result[0];
        $bytesOut += $result[1];
        printf("%d KB -> %d KB\n", round($result[0] / 1024), round($result[1] / 1024));
    } else {
        $failed++;
        echo "FAILED ($result)\n";
    }
}

printf("\nConverted %d file(s)%s: %.1f MB -> %.1f MB. Output is in the \"webp\" folder next to your originals.\n",
    $done, $failed ? " ($failed failed)" : "", $bytesIn / 1048576, $bytesOut / 1048576);
