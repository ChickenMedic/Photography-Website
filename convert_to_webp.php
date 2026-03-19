<?php
session_start();
require_once 'config.php';

// Secure the route
if (!isset($_SESSION['user_id'])) {
    die("<h1>Unauthorized. Please log into the Admin Dashboard first.</h1><a href='admin.php'>Login</a>");
}

$message = '';
$photosConverted = 0;
$projectsConverted = 0;

if (isset($_POST['run_conversion'])) {
    
    // 1. Process standard photos
    $stmt = $pdo->query("SELECT id, filename FROM photos");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($photos as $photo) {
        $filepath = UPLOAD_DIR . $photo['filename'];
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        // Skip files that don't exist or are already webp/gif
        if (!file_exists($filepath) || !in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
        
        $newFilename = uniqid('photo_') . '.webp';
        $newFilepath = UPLOAD_DIR . $newFilename;
        $image = null;
        
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $image = @imagecreatefromjpeg($filepath);
        } elseif ($ext === 'png') {
            $image = @imagecreatefrompng($filepath);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        }
        
        if ($image !== null) {
            if (imagewebp($image, $newFilepath, 80)) {
                // Safely update DB 
                $upStmt = $pdo->prepare("UPDATE photos SET filename = ? WHERE id = ?");
                $upStmt->execute([$newFilename, $photo['id']]);
                
                // Nuke old heavy image
                @unlink($filepath);
                $photosConverted++;
            }
            imagedestroy($image);
        }
    }
    
    // 2. Process Project Cover Images
    $projStmt = $pdo->query("SELECT id, cover_image FROM projects WHERE cover_image IS NOT NULL");
    $projects = $projStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $proj) {
        $filepath = UPLOAD_DIR . $proj['cover_image'];
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if (!file_exists($filepath) || !in_array($ext, ['jpg', 'jpeg', 'png'])) continue;
        
        $newFilename = uniqid('proj_cover_') . '.webp';
        $newFilepath = UPLOAD_DIR . $newFilename;
        $image = null;
        
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $image = @imagecreatefromjpeg($filepath);
        } elseif ($ext === 'png') {
            $image = @imagecreatefrompng($filepath);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        }
        
        if ($image !== null) {
            if (imagewebp($image, $newFilepath, 80)) {
                $upStmt = $pdo->prepare("UPDATE projects SET cover_image = ? WHERE id = ?");
                $upStmt->execute([$newFilename, $proj['id']]);
                @unlink($filepath);
                $projectsConverted++;
            }
            imagedestroy($image);
        }
    }
    
    $message = "Optimization Complete! Successfully shrunk {$photosConverted} gallery photos and {$projectsConverted} project posters into WebP format.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk WebP Optimizer</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #cbd5e1; text-align: center; padding-top: 10vh; margin: 0; }
        h1 { color: #f8fafc; margin-bottom: 5px; }
        .box { max-width: 600px; margin: 0 auto; background: #1e293b; padding: 40px; border-radius: 12px; border: 1px solid #334155; }
        .danger-text { color: #f87171; font-size: 0.95rem; margin-bottom: 30px; }
        button { background: #3b82f6; color: white; border: none; padding: 14px 28px; font-size: 1.1rem; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        button:hover { background: #2563eb; }
        .success { margin-top: 30px; padding: 20px; font-size: 1.1rem; font-weight: bold; color: #4ade80; background: rgba(34, 197, 94, 0.1); border-radius: 8px; border: 1px solid rgba(34,197,94,0.3); }
        .back-link { display: inline-block; margin-top: 30px; color: #94a3b8; text-decoration: none; }
        .back-link:hover { color: #fff; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Batch WebP Optimizer</h1>
        <p>This script will permanently convert all existing heavy `.jpg` and `.png` images sitting on your server into high-speed `.webp` files! </p>
        <p class="danger-text">⚠️ Warning: The original source images will be deleted from the server to save disk space after they are replaced with their fully compressed WebP files!</p>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="run_conversion" onclick="return confirm('Ready to permanently compress all server uploads? This might take roughly 10 seconds.');">Shrink Everything</button>
            </form>
        <?php endif; ?>
        
        <a href="admin.php" class="back-link">&larr; Return to Admin Dashboard</a>
    </div>
</body>
</html>
