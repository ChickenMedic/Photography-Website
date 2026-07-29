<?php
// config.php
// Environment Detection (ignore any :port suffix, e.g. localhost:8080)
$hostname = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]);
$isLocalhost = in_array($hostname, ['localhost', '127.0.0.1']);

if ($isLocalhost) {
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_USER', 'SDawson-admin');
    define('DB_PASS', 'LeicaWreckingBall2026!@#');
}
define('DB_HOST', 'localhost');
define('DB_NAME', 'personal_portfolio');

// Establish Database Connection using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Site Configurations
define('SITE_NAME', 'Sam Dawson | Photography');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// Helper function to sanitize output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ---- Image processing helpers (GD) ----

// Raise memory_limit if it is below the target; never lowers an already higher limit.
function ensureMemoryLimitMb($targetMb) {
    $cur = trim(ini_get('memory_limit'));
    if ($cur === '-1' || $cur === '') return;
    $unit = strtoupper(substr($cur, -1));
    $val = (float)$cur;
    if ($unit === 'G') $val *= 1024;
    elseif ($unit === 'K') $val /= 1024;
    elseif ($unit !== 'M') $val /= 1048576; // plain number = bytes
    if ($val < $targetMb) @ini_set('memory_limit', $targetMb . 'M');
}

// Bake the EXIF camera orientation into the pixels. GD ignores the EXIF tag and
// strips it on save, so without this a phone photo flips sideways after any edit.
function applyExifOrientation($image, $filepath) {
    if (!function_exists('exif_read_data')) return $image;
    $exif = @exif_read_data($filepath);
    $orientation = (int)($exif['Orientation'] ?? 1);
    if ($orientation <= 1 || $orientation > 8) return $image;

    if ($orientation === 2 || $orientation === 7) imageflip($image, IMG_FLIP_HORIZONTAL);
    if ($orientation === 4 || $orientation === 5) imageflip($image, IMG_FLIP_VERTICAL);

    $angle = 0;
    if ($orientation === 3) $angle = 180;
    elseif (in_array($orientation, [5, 6, 7])) $angle = -90; // 90 CW
    elseif ($orientation === 8) $angle = 90;                 // 90 CCW

    if ($angle !== 0) {
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated !== false) {
            imagedestroy($image);
            $image = $rotated;
        }
    }
    return $image;
}

// Load a jpg/png/webp file into GD, normalizing PNG alpha and EXIF rotation.
function loadImageForEditing($filepath) {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $image = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $image = @imagecreatefromjpeg($filepath);
        if ($image) $image = applyExifOrientation($image, $filepath);
    } elseif ($ext === 'png') {
        $image = @imagecreatefrompng($filepath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
    } elseif ($ext === 'webp') {
        $image = @imagecreatefromwebp($filepath);
    }
    return $image ?: null;
}

// Downscale so the long edge is at most $maxLongEdge px (full res is only needed for print).
function resizeToMaxEdge($image, $maxLongEdge = 2560) {
    $width = imagesx($image);
    $height = imagesy($image);
    if (max($width, $height) <= $maxLongEdge) return $image;
    $scale = $maxLongEdge / max($width, $height);
    $resized = imagescale($image, (int)round($width * $scale), (int)round($height * $scale), IMG_BICUBIC);
    if ($resized !== false) {
        imagedestroy($image);
        $image = $resized;
    }
    return $image;
}

// Re-encode an existing upload as a resized WebP file in UPLOAD_DIR.
// Returns the new filename, or null on failure. Does not touch the DB or the original file.
function reencodeAsWebp($filepath, $prefix) {
    if (!function_exists('imagewebp')) return null;
    ensureMemoryLimitMb(512);
    $image = loadImageForEditing($filepath);
    if (!$image) return null;
    $image = resizeToMaxEdge($image);
    $newFilename = uniqid($prefix) . '.webp';
    $ok = imagewebp($image, UPLOAD_DIR . $newFilename, 80);
    imagedestroy($image);
    if (!$ok) {
        @unlink(UPLOAD_DIR . $newFilename);
        return null;
    }
    return $newFilename;
}
?>
