<?php
session_start();
require_once 'config.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$message = '';
$error = '';

// Check if a massive bulk upload exceeded PHP's maximum POST limits, causing a silent failure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $error = "Upload Failed: You attached too much data at once for the server to process. Please try uploading fewer or smaller photos per batch.";
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}

// If not logged in, show login form
if (!isset($_SESSION['user_id'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #0f172a; color: #fff; font-family: 'Inter', sans-serif;}
        .login-container { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 400px;}
        .login-container h1 { margin-bottom: 24px; font-size: 24px; text-align: center;}
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; margin-bottom: 16px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; box-sizing: border-box;}
        button { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.2s;}
        button:hover { background: #2563eb; }
        .error { color: #ef4444; margin-bottom: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
        <form method="POST" action="admin.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// Ensure uploads directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

/**
 * Automatically converts JPG/PNG uploads to WebP for massive file size reduction.
 * Falls back to standard upload if the format is unsupported or GD is missing.
 */
function processImageUpload($tmpPath, $originalExt, $destinationFolder, $prefix, $convertToWebp = true) {
    $ext = strtolower($originalExt);
    if (!$convertToWebp || !function_exists('imagewebp') || $ext === 'webp' || $ext === 'gif') {
        $filename = uniqid($prefix) . '.' . $ext;
        return move_uploaded_file($tmpPath, $destinationFolder . $filename) ? $filename : false;
    }

    $filename = uniqid($prefix) . '.webp';
    $destination = $destinationFolder . $filename;
    $info = @getimagesize($tmpPath);
    if (!$info) return false;

    $image = null;
    if ($info['mime'] == 'image/jpeg') {
        $image = @imagecreatefromjpeg($tmpPath);
    } elseif ($info['mime'] == 'image/png') {
        $image = @imagecreatefrompng($tmpPath);
        if ($image) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
    }

    if ($image !== null) {
        $success = imagewebp($image, $destination, 80); // 80% is the optimal WebP quality
        imagedestroy($image);
        return $success ? $filename : false;
    }

    // Ultimate fallback
    $filename = uniqid($prefix) . '.' . $ext;
    return move_uploaded_file($tmpPath, $destinationFolder . $filename) ? $filename : false;
}

// Auto-run DB migrations for locations
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->query("SHOW COLUMNS FROM photos LIKE 'location_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE photos ADD COLUMN location_id INT NULL");
        $pdo->exec("ALTER TABLE photos ADD FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL");
    }

    $stmtActive = $pdo->query("SHOW COLUMNS FROM locations LIKE 'is_active'");
    if ($stmtActive->rowCount() == 0) {
        $pdo->exec("ALTER TABLE locations ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }
} catch (PDOException $e) { /* Ignore migration errors */ }

// Handle AJAX Desktop Photo Upload to Location
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_upload_location'])) {
    header('Content-Type: application/json');
    $location_id = $_POST['location_id'];
    $convertToWebp = isset($_POST['convert_webp']) && $_POST['convert_webp'] === '1';
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $finalFilename = processImageUpload($file['tmp_name'], $ext, UPLOAD_DIR, 'photo_', $convertToWebp);

            if ($finalFilename) {
                $stmt = $pdo->prepare("INSERT INTO photos (location_id, filename) VALUES (?, ?)");
                if ($stmt->execute([$location_id, $finalFilename])) {
                    echo json_encode(["status" => "success", "message" => "Photo uploaded"]);
                    exit;
                }
            }
        }
    }
    echo json_encode(["status" => "error", "message" => "Upload failed"]);
    exit;
}

// Handle Gallery Toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_location'])) {
    $loc_id = $_POST['location_id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE locations SET is_active = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $loc_id])) {
        $message = "Location visibility updated.";
    } else {
        $error = "Failed to update location visibility.";
    }
}

// Handle Location Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_location'])) {
    $name = trim($_POST['location_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO locations (name) VALUES (?)");
        if ($stmt->execute([$name])) {
            $message = "Location created!";
        } else {
            $error = "Failed to create location.";
        }
    }
}


// Handle Photo Upload (Bulk Supported)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo'])) {
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $tags = trim($_POST['tags']);
    $convertToWebp = isset($_POST['convert_webp']) ? true : false;
    
    // Check if files were actually uploaded
    if (!empty($_FILES['photo']['name'][0])) {
        $files = $_FILES['photo'];
        $uploadCount = 0;
        $errorCount = 0;
        $fileErrors = [];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        // Loop through each submitted file in the array
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $finalFilename = processImageUpload($files['tmp_name'][$i], $ext, UPLOAD_DIR, 'photo_', $convertToWebp);

                    if ($finalFilename) {
                        $stmt = $pdo->prepare("INSERT INTO photos (location_id, filename, title, description, tags) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$location_id, $finalFilename, $title, $description, $tags])) {
                            $uploadCount++;
                        } else {
                            $errorCount++;
                            $fileErrors[] = $files['name'][$i] . " (Database error)";
                        }
                    } else {
                        $errorCount++;
                        $fileErrors[] = $files['name'][$i] . " (Upload/Conversion failed)";
                    }
                } else {
                    $errorCount++;
                    $fileErrors[] = $files['name'][$i] . " (Invalid file format: $ext)";
                }
            } else {
                $errorCount++;
                if ($files['error'][$i] === UPLOAD_ERR_INI_SIZE || $files['error'][$i] === UPLOAD_ERR_FORM_SIZE) {
                    $fileErrors[] = $files['name'][$i] . " (File too large for server)";
                } else {
                    $fileErrors[] = $files['name'][$i] . " (Error Code: " . $files['error'][$i] . ")";
                }
            }
        }

        if ($uploadCount > 0) {
            $message = "Successfully uploaded $uploadCount photo(s)!";
            if ($errorCount > 0) {
                $message .= " Failed to upload: " . implode(', ', $fileErrors);
            }
        } else {
            $error = "Failed to upload photos: " . implode(', ', $fileErrors);
            if (empty($errorCount)) $error = "No valid files selected.";
        }
    } else {
        $error = "Please select at least one photo to upload.";
    }
}
// Handle Bulk Photo Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulk_delete_photos']) && !empty($_POST['bulk_delete_ids'])) {
    $ids = $_POST['bulk_delete_ids'];
    $ids = array_filter($ids, 'is_numeric');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $photosToDelete = $stmt->fetchAll();
        
        foreach ($photosToDelete as $ptd) {
            @unlink(UPLOAD_DIR . $ptd['filename']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id IN ($placeholders)");
        if ($stmt->execute($ids)) {
            $message = "Successfully deleted " . count($ids) . " selected photo(s).";
        } else {
            $error = "Failed to bulk delete photos from database.";
        }
    }
}

// Handle Photo Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_photo'])) {
    $id = $_POST['delete_photo_id'];
    
    // First, find the file name so we can delete the physical image off the server
    $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
    $stmt->execute([$id]);
    $photoToDelete = $stmt->fetch();
    
    if ($photoToDelete && $photoToDelete['filename']) {
        @unlink(UPLOAD_DIR . $photoToDelete['filename']);
    }
    
    // Then, delete the database record
    $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Photo permanently deleted.";
    } else {
        $error = "Failed to delete photo from database.";
    }
}

// Handle Individual Photo WebP Conversion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['convert_photo_webp'])) {
    $id = $_POST['convert_photo_id'];
    
    $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
    $stmt->execute([$id]);
    $photoToConvert = $stmt->fetch();
    
    if ($photoToConvert && $photoToConvert['filename']) {
        $filepath = UPLOAD_DIR . $photoToConvert['filename'];
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if (file_exists($filepath) && in_array($ext, ['jpg', 'jpeg', 'png'])) {
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
                    $upStmt = $pdo->prepare("UPDATE photos SET filename = ? WHERE id = ?");
                    if ($upStmt->execute([$newFilename, $id])) {
                        @unlink($filepath);
                        $message = "Photo perfectly compressed to WebP format!";
                    } else {
                        $error = "Failed to update database with WebP photo.";
                        @unlink($newFilepath); // Clean up if DB fails
                    }
                } else {
                    $error = "Failed to encode WebP image.";
                }
                imagedestroy($image);
            } else {
                $error = "Failed to open original image for conversion.";
            }
        } else {
            $error = "File doesn't exist or is already optimized.";
        }
    } else {
        $error = "Failed to find photo to convert.";
    }
}

// Handle Individual Photo Rotation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rotate_photo'])) {
    $id = $_POST['rotate_photo_id'];
    
    $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id = ?");
    $stmt->execute([$id]);
    $photoToRotate = $stmt->fetch();
    
    if ($photoToRotate && $photoToRotate['filename']) {
        $filepath = UPLOAD_DIR . $photoToRotate['filename'];
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if (file_exists($filepath) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $image = null;
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $image = @imagecreatefromjpeg($filepath);
            } elseif ($ext === 'png') {
                $image = @imagecreatefrompng($filepath);
            } elseif ($ext === 'webp') {
                $image = @imagecreatefromwebp($filepath);
            }
            
            if ($image !== null) {
                if ($ext === 'png') {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                
                // Rotate 90 degrees clockwise (-90 in GD)
                $rotated = imagerotate($image, -90, 0);
                
                if ($rotated !== false) {
                    $success = false;
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $success = imagejpeg($rotated, $filepath, 100);
                    } elseif ($ext === 'png') {
                        imagesavealpha($rotated, true);
                        $success = imagepng($rotated, $filepath);
                    } elseif ($ext === 'webp') {
                        $success = imagewebp($rotated, $filepath, 80);
                    }
                    
                    if ($success) {
                        $message = "Photo rotated successfully!";
                    } else {
                        $error = "Failed to save rotated image.";
                    }
                    imagedestroy($rotated);
                } else {
                    $error = "Failed to rotate image memory.";
                }
                imagedestroy($image);
            } else {
                $error = "Failed to open original image for rotation.";
            }
        } else {
            $error = "File doesn't exist or is unsupported for rotation.";
        }
    } else {
        $error = "Failed to find photo to rotate.";
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => empty($error) ? 'success' : 'error', 'message' => empty($error) ? ($message ?? '') : $error]);
        exit;
    }
}

// Handle Project Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_project'])) {
    $title = trim($_POST['project_title']);
    $description = trim($_POST['project_description']);
    $url = trim($_POST['project_url']);
    $series_name = trim($_POST['series_name']);
    $content = isset($_POST['project_content']) ? trim($_POST['project_content']) : '';
    $file = $_FILES['cover_image'];
    
    $coverFilename = null;

    $convertToWebp = isset($_POST['convert_webp_proj']) ? true : false;

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $coverFilename = processImageUpload($file['tmp_name'], $ext, UPLOAD_DIR, 'proj_cover_', $convertToWebp);
            if (!$coverFilename) {
                $error = "Failed to upload or convert cover image.";
            }
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, cover_image, url, series_name, content) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $coverFilename, $url, $series_name, $content])) {
                 $message = "Project created successfully!";
            } else {
                 $error = "Database error creating project.";
            }
        } catch (PDOException $e) {
            $error = "Fatal SQL Error: " . $e->getMessage();
        }
    }
}

// Handle Project Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_project'])) {
    $id = $_POST['delete_project_id'];
    $stmt = $pdo->prepare("SELECT cover_image FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $proj = $stmt->fetch();
    if ($proj && $proj['cover_image']) {
        @unlink(UPLOAD_DIR . $proj['cover_image']);
    }
    $pdo->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
    $message = "Project deleted successfully.";
}

// Handle Project Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_project'])) {
    $id = $_POST['update_project_id'];
    $title = trim($_POST['project_title']);
    $description = trim($_POST['project_description']);
    $url = trim($_POST['project_url']);
    $series_name = trim($_POST['series_name']);
    $content = isset($_POST['project_content']) ? trim($_POST['project_content']) : '';

    $file = $_FILES['cover_image'];
    
    $stmt = $pdo->prepare("SELECT cover_image FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $oldProj = $stmt->fetch();
    $coverFilename = $oldProj ? $oldProj['cover_image'] : null;

    $convertToWebp = isset($_POST['convert_webp_proj']) ? true : false;
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $newFilename = processImageUpload($file['tmp_name'], $ext, UPLOAD_DIR, 'proj_cover_', $convertToWebp);
            if ($newFilename) {
                if ($coverFilename) @unlink(UPLOAD_DIR . $coverFilename);
                $coverFilename = $newFilename;
            } else {
                $error = "Failed to upload or convert cover image.";
            }
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET title=?, description=?, cover_image=?, url=?, series_name=?, content=? WHERE id=?");
            if ($stmt->execute([$title, $description, $coverFilename, $url, $series_name, $content, $id])) {
                 $message = "Project updated successfully!";
                 header("Location: admin.php");
                 exit;
            } else {
                 $error = "Database error updating project.";
            }
        } catch (PDOException $e) {
            $error = "Fatal SQL Error: " . $e->getMessage();
        }
    }
}

$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll();
$all_photos = $pdo->query("SELECT * FROM photos ORDER BY id DESC")->fetchAll();

$edit_project_data = null;
if (isset($_GET['edit_project'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$_GET['edit_project']]);
    $edit_project_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo h(SITE_NAME); ?></title>
    <!-- Summernote WYSIWYG Editor (Free, No API Key, Drag & Drop Images) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
      $(document).ready(function() {
          var ServerPhotoButton = function(context) {
              var ui = $.summernote.ui;
              var button = ui.button({
                  contents: '<i class="note-icon-picture"/> Server Photo',
                  tooltip: 'Insert Existing Photo from Website',
                  click: function() {
                      document.getElementById('photoModal').style.display = 'flex';
                  }
              });
              return button.render();
          }

          $('#project_content').summernote({
              height: 400,
              placeholder: 'Write your project guide here. You can drag and drop images directly into this box!',
              buttons: {
                  serverPhoto: ServerPhotoButton
              },
              toolbar: [
                  ['style', ['style']],
                  ['font', ['bold', 'italic', 'underline', 'clear']],
                  ['para', ['ul', 'ol', 'paragraph']],
                  ['custom', ['serverPhoto']],
                  ['insert', ['link', 'picture', 'video']],
                  ['view', ['codeview', 'help']]
              ]
          });
      });

      function insertPhoto(url) {
          $('#project_content').summernote('insertImage', url);
          document.getElementById('photoModal').style.display = 'none';
      }
    </script>
    <!-- We will use a basic inline style for admin to keep dependencies low, but could link external -->
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #cbd5e1; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 20px;}
        h1, h2 { color: #f8fafc; margin-top: 0;}
        .card { background: #1e293b; padding: 24px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #334155;}
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #94a3b8;}
        input[type="text"], input[type="url"], textarea, input[type="file"] { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 100px; }
        button { padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; }
        button:hover { background: #2563eb; }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .alert-success { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .alert-error { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-link { color: #3b82f6; text-decoration: none; }
        .btn-link:hover { text-decoration: underline; }
        .locations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .location-dropzone { border: 2px dashed #475569; border-radius: 8px; padding: 30px 20px; text-align: center; background: #0f172a; transition: all 0.3s; cursor: pointer; position: relative; }
        .location-dropzone.dragover { border-color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
        .location-dropzone h3 { margin-bottom: 10px; color: #f8fafc; font-size: 1.2rem; }
        .location-dropzone p { margin: 0; color: #94a3b8; font-size: 0.9rem; pointer-events: none; }
        .upload-progress { position: absolute; bottom: 0; left: 0; height: 4px; background: #3b82f6; width: 0%; transition: width 0.3s; border-radius: 0 0 8px 8px;}
        select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #475569; background: #0f172a; color: white; margin-bottom: 16px; }
        
        /* Photo Picker Modal */
        .photo-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center; }
        .photo-modal-content { background: #1e293b; padding: 24px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto; border: 1px solid #334155; box-shadow: 0 10px 25px rgba(0,0,0,0.5);}
        .photo-modal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; margin-top: 20px; }
        .photo-modal-grid img { width: 100%; height: 150px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: border-color 0.2s; }
        .photo-modal-grid img:hover { border-color: #3b82f6; }
        .close-modal { float: right; cursor: pointer; color: #94a3b8; font-size: 28px; line-height: 1; font-weight: bold; }
        .close-modal:hover { color: #f8fafc; }
        ul, li { list-style: none; padding: 0; margin: 0; }

        /* Collapsible Card Enhancements */
        .card h2 { cursor: pointer; user-select: none; display: flex; justify-content: space-between; align-items: center; transition: color 0.3s; margin-bottom: 0; }
        .card h2:hover { color: #60a5fa; }
        .card h2::after { content: '＋'; font-size: 1.2rem; color: #64748b; transition: transform 0.3s; }
        .card h2.active::after { content: '−'; transform: rotate(180deg); }
        .card-content { margin-top: 20px; transition: all 0.3s ease; }
    </style>
</head>
<body>
    <!-- Photo Picker Modal -->
    <div id="photoModal" class="photo-modal">
        <div class="photo-modal-content">
            <span class="close-modal" onclick="document.getElementById('photoModal').style.display='none'">&times;</span>
            <h2 style="margin-bottom: 5px;">Select a Photo</h2>
            <p style="color: #94a3b8; margin-top: 0; margin-bottom: 20px;">Click any photo previously uploaded to the gallery to insert it into your project.</p>
            
            <input type="text" id="photoSearch" placeholder="Search by tags (e.g. guide)" style="margin-bottom: 20px;" onkeyup="filterPhotos()">

            <div class="photo-modal-grid" id="photoGrid">
                <?php foreach ($all_photos as $img): ?>
                    <img src="uploads/<?php echo h($img['filename']); ?>" alt="<?php echo h($img['title']); ?>" data-tags="<?php echo isset($img['tags']) ? h(strtolower($img['tags'])) : ''; ?>" onclick="insertPhoto('uploads/<?php echo h($img['filename']); ?>')">
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function filterPhotos() {
            var input = document.getElementById("photoSearch").value.toLowerCase();
            var grid = document.getElementById("photoGrid");
            var images = grid.getElementsByTagName("img");
            
            for (var i = 0; i < images.length; i++) {
                var tags = images[i].getAttribute("data-tags");
                if (tags && tags.indexOf(input) > -1) {
                    images[i].style.display = "";
                } else if (!input) {
                    images[i].style.display = "";
                } else {
                    images[i].style.display = "none";
                }
            }
        }
    </script>

    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div>
                <a href="/" target="_blank" class="btn-link" style="margin-right: 16px;">View Site</a>
                <a href="admin.php?action=logout" class="btn-link">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Add Location (Album)</h2>
            <div class="card-content" style="display: none;">
                <form method="POST" action="admin.php">
                    <div class="form-group">
                        <label for="location_name">Location Name</label>
                        <input type="text" name="location_name" id="location_name" placeholder="e.g. Paris" required>
                    </div>
                    <button type="submit" name="create_location">Create Location</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>Upload Photos by Location</h2>
            <div class="card-content" style="display: none;">
                <p style="color: #94a3b8; margin-bottom: 20px;">Drag and drop photo files directly onto a location below to upload them.</p>
                <div class="form-group" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer; margin: 0; color: #f8fafc;">
                        <input type="checkbox" id="global_convert_webp" value="1" style="width: auto; margin-right: 15px; transform: scale(1.2);">
                        Compress dropped photos to WebP format (Recommended)
                    </label>
                </div>
                <div class="locations-grid">
                    <?php
                    if (!isset($pdo)) { require_once 'config.php'; }
                    $stmtLocs = $pdo->query("SELECT * FROM locations ORDER BY name ASC");
                    $locations = $stmtLocs->fetchAll();
                    if (empty($locations)) {
                        echo "<p style='color: #94a3b8;'>No locations found. Create one above.</p>";
                    } else {
                        foreach ($locations as $loc) {
                            $isActive = isset($loc['is_active']) ? $loc['is_active'] : 1;
                            $opacity = $isActive ? '1' : '0.5';
                            echo "<div class='location-dropzone' data-id='" . $loc['id'] . "' style='opacity: {$opacity};'>";
                            echo "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;'>";
                            echo "<h3 style='margin:0; text-align:left;'>" . h($loc['name']) . ($isActive ? "" : " <span style='font-size:0.8rem; color:#ef4444;'>(Hidden)</span>") . "</h3>";
                            echo "<form method='POST' action='admin.php' style='margin:0;'>";
                            echo "<input type='hidden' name='location_id' value='{$loc['id']}'>";
                            echo "<input type='hidden' name='current_status' value='{$isActive}'>";
                            echo "<button type='submit' name='toggle_location' style='padding: 4px 8px; font-size: 11px; background: ".($isActive ? '#ef4444' : '#22c55e').";'>" . ($isActive ? 'Hide' : 'Show') . "</button>";
                            echo "</form>";
                            echo "</div>";
                            echo "<p>Drop photos here</p>";
                            echo "<div class='upload-progress'></div>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Upload Photos</h2>
            <div class="card-content" style="display: none;">
                <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 20px;">
                    <strong>Server Limits:</strong> Max Single Photo: <?php echo ini_get('upload_max_filesize'); ?> | Max Total Batch: <?php echo ini_get('post_max_size'); ?> | Max Photos per Batch: <?php echo ini_get('max_file_uploads'); ?>
                </p>
                <form method="POST" action="admin.php" enctype="multipart/form-data">
                    <div class="form-group" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; cursor: pointer; margin: 0; color: #f8fafc;">
                            <input type="checkbox" name="convert_webp" value="1" style="width: auto; margin-right: 15px; transform: scale(1.2);">
                            Compress these photos to WebP format (Recommended)
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="location_id">Select Location (Optional)</label>
                        <select name="location_id" id="location_id">
                            <option value="">-- None --</option>
                            <?php foreach($locations as $loc): ?>
                                <option value="<?php echo $loc['id']; ?>"><?php echo h($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="photo">Select Photos (Select multiple from phone camera roll)</label>
                        <input type="file" name="photo[]" id="photo" required multiple accept="image/jpeg, image/png, image/webp, image/gif">
                    </div>
                    <div class="form-group">
                        <label for="title">Title (Optional)</label>
                        <input type="text" name="title" id="title" placeholder="A beautiful sunset">
                    </div>
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea name="description" id="description" placeholder="Where was this taken?"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="tags">Tags (comma-separated, optional)</label>
                        <input type="text" name="tags" id="tags" placeholder="e.g. guide, hero, portrait">
                    </div>
                    <button type="submit" name="upload_photo">Upload Photo</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2><?php echo $edit_project_data ? 'Edit Project' : 'Add Project'; ?></h2>
            <div class="card-content" <?php echo $edit_project_data ? 'style="display: block;"' : 'style="display: none;"'; ?>>
                <form method="POST" action="admin.php" enctype="multipart/form-data">
                    <?php if ($edit_project_data): ?>
                        <input type="hidden" name="update_project_id" value="<?php echo $edit_project_data['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group" style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 6px; border: 1px solid #334155; margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; cursor: pointer; margin: 0; color: #f8fafc;">
                            <input type="checkbox" name="convert_webp_proj" value="1" style="width: auto; margin-right: 15px; transform: scale(1.2);">
                            Compress cover image to WebP format (Recommended)
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="project_title">Project Title</label>
                        <input type="text" name="project_title" id="project_title" required value="<?php echo $edit_project_data ? h($edit_project_data['title']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="cover_image">Cover Image (Optional) <?php if ($edit_project_data && $edit_project_data['cover_image']) echo ' - Leave blank to keep current'; ?></label>
                        <input type="file" name="cover_image" id="cover_image" accept="image/jpeg, image/png, image/webp">
                    </div>
                    <div class="form-group">
                        <label for="project_url">Clean URL or External Link (Optional)</label>
                        <input type="text" name="project_url" id="project_url" placeholder="e.g. AWSGuideStep1 or https://..." value="<?php echo $edit_project_data ? h($edit_project_data['url']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="series_name">Series Name (Optional grouping for Homepage)</label>
                        <input type="text" name="series_name" id="series_name" placeholder="e.g. Website Builder" value="<?php echo $edit_project_data ? h($edit_project_data['series_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="project_description">Short Description (for the Project Card)</label>
                        <textarea name="project_description" id="project_description" placeholder="A brief summary for the homepage"><?php echo $edit_project_data ? h($edit_project_data['description']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="project_content">Full Page Content (WYSIWYG Editor)</label>
                        <textarea name="project_content" id="project_content"><?php echo $edit_project_data ? h($edit_project_data['content']) : ''; ?></textarea>
                    </div>
                    <?php if ($edit_project_data): ?>
                        <button type="submit" name="update_project">Update Project</button>
                        <a href="admin.php" style="margin-left: 10px; color: #cbd5e1; text-decoration: none;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="create_project">Create Project</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>Manage Photos</h2>
            <div class="card-content" style="display: none;">
                <p style="color: #94a3b8; margin-bottom: 20px;">Review and delete uploaded photos. Deleting a photo will permanently remove it from the server and the galleries.</p>
                <div style="overflow-x:auto;">
                    <form method="POST" action="admin.php" id="bulkDeleteForm" onsubmit="return confirm('Are you completely sure you want to permanently delete all selected photos? This cannot be undone.');">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid #475569;">
                                <th style="padding: 10px; width: 40px;"><input type="checkbox" id="selectAllPhotos" onclick="toggleAllCheckboxes(this)"></th>
                                <th style="padding: 10px;">Preview</th>
                                <th style="padding: 10px;">ID</th>
                                <th style="padding: 10px;">Filename</th>
                                <th style="padding: 10px;">Title</th>
                                <th style="padding: 10px;">Size</th>
                                <th style="padding: 10px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_photos as $photoItem): ?>
                            <?php 
                                $filepath = UPLOAD_DIR . $photoItem['filename'];
                                $sizeStr = 'N/A';
                                if (file_exists($filepath)) {
                                    $bytes = filesize($filepath);
                                    if ($bytes >= 1048576) {
                                        $sizeStr = "<span style='color: " . ($bytes > 3145728 ? '#ef4444' : '#f59e0b') . "'>" . number_format($bytes / 1048576, 2) . " MB</span>";
                                    } elseif ($bytes >= 1024) {
                                        $sizeStr = "<span style='color: #22c55e'>" . number_format($bytes / 1024, 0) . " KB</span>";
                                    } else {
                                        $sizeStr = $bytes . ' B';
                                    }
                                }
                            ?>
                            <tr style="border-bottom: 1px solid #334155;">
                                <td style="padding: 10px;"><input type="checkbox" name="bulk_delete_ids[]" value="<?php echo $photoItem['id']; ?>" class="photo-checkbox"></td>
                                <td style="padding: 10px;">
                                    <img src="uploads/<?php echo h($photoItem['filename']); ?>" alt="preview" style="width: 100px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #475569;">
                                </td>
                                <td style="padding: 10px; color: #94a3b8;"><?php echo $photoItem['id']; ?></td>
                                <td style="padding: 10px; font-family: monospace; color: #cbd5e1;"><?php echo h(substr($photoItem['filename'], 0, 15)) . '...'; ?></td>
                                <td style="padding: 10px;"><?php echo h($photoItem['title'] ?: 'Untitled'); ?></td>
                                <td style="padding: 10px; font-family: monospace; font-size: 0.9rem;"><?php echo $sizeStr; ?></td>
                                <td style="padding: 10px;">
                                    <?php 
                                        $ext = strtolower(pathinfo($photoItem['filename'], PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                                    ?>
                                    <form method="POST" action="admin.php" style="display:inline; margin-right: 15px;">
                                        <input type="hidden" name="convert_photo_id" value="<?php echo $photoItem['id']; ?>">
                                        <button type="submit" name="convert_photo_webp" style="background: none; border: none; color: #3b82f6; cursor: pointer; padding: 0; font-weight: normal; font-size: 1rem; text-decoration: underline;">Convert to WebP</button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array(strtolower(pathinfo($photoItem['filename'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])): ?>
                                    <form method="POST" action="admin.php" style="display:inline; margin-right: 15px;" class="ajax-rotate-form">
                                        <input type="hidden" name="rotate_photo_id" value="<?php echo $photoItem['id']; ?>">
                                        <input type="hidden" name="rotate_photo" value="1">
                                        <button type="button" onclick="handleAjaxRotate(this)" style="background: none; border: none; color: #f59e0b; cursor: pointer; padding: 0; font-weight: normal; font-size: 1rem; text-decoration: underline;">Rotate 90&deg;</button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you completely sure you want to permanently delete this photo? This cannot be undone.');">
                                        <input type="hidden" name="delete_photo_id" value="<?php echo $photoItem['id']; ?>">
                                        <button type="submit" name="delete_photo" style="background: none; border: none; color: #f87171; cursor: pointer; padding: 0; font-weight: normal; font-size: 1rem; text-decoration: underline;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 20px;">
                        <button type="submit" name="bulk_delete_photos" style="background: #ef4444;">Delete Selected Photos</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Manage Projects</h2>
            <div class="card-content" style="display: none;">
                <div style="overflow-x:auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid #475569;">
                                <th style="padding: 10px;">ID</th>
                                <th style="padding: 10px;">Title</th>
                                <th style="padding: 10px;">URL/Slug</th>
                                <th style="padding: 10px;">Series</th>
                                <th style="padding: 10px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($projects as $p): ?>
                            <tr style="border-bottom: 1px solid #334155;">
                                <td style="padding: 10px;"><?php echo $p['id']; ?></td>
                                <td style="padding: 10px;"><?php echo h($p['title']); ?></td>
                                <td style="padding: 10px;"><?php echo h($p['url']); ?></td>
                                <td style="padding: 10px;"><?php echo h($p['series_name']); ?></td>
                                <td style="padding: 10px;">
                                    <a href="admin.php?edit_project=<?php echo $p['id']; ?>" style="color: #4ade80; text-decoration: none; margin-right: 10px;">Edit</a>
                                    <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                        <input type="hidden" name="delete_project_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="delete_project" style="background: none; border: none; color: #f87171; cursor: pointer; padding: 0; font-weight: normal; font-size: 1rem; text-decoration: underline;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAllCheckboxes(source) {
            const checkboxes = document.querySelectorAll('.photo-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function handleAjaxRotate(btn) {
            const form = btn.closest('form');
            const formData = new FormData(form);
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Rotating...';
            btn.disabled = true;
            
            fetch('admin.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => {
                const tr = form.closest('tr');
                const img = tr.querySelector('img');
                if (img) {
                    const url = new URL(img.src, window.location.origin);
                    url.searchParams.set('t', new Date().getTime());
                    img.src = url.toString();
                }
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(() => {
                btn.innerHTML = 'Error';
            });
        }

        // Drag and drop logic for Locations
        const dropzones = document.querySelectorAll('.location-dropzone');
        
        dropzones.forEach(zone => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                zone.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                zone.addEventListener(eventName, () => zone.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                zone.addEventListener(eventName, () => zone.classList.remove('dragover'), false);
            });

            zone.addEventListener('drop', handleDrop, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function handleDrop(e) {
            let dt = e.dataTransfer;
            let files = dt.files;
            let locationId = e.target.closest('.location-dropzone').dataset.id;
            let progress = e.target.closest('.location-dropzone').querySelector('.upload-progress');

            ([...files]).forEach(file => uploadFile(file, locationId, progress));
        }

        function uploadFile(file, locationId, progressBar) {
            let url = 'admin.php';
            let formData = new FormData();
            formData.append('photo', file);
            formData.append('location_id', locationId);
            formData.append('ajax_upload_location', '1');
            const convertWebp = document.getElementById('global_convert_webp') && document.getElementById('global_convert_webp').checked ? '1' : '0';
            formData.append('convert_webp', convertWebp);

            let xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            
            xhr.upload.addEventListener('progress', (e) => {
                let percent = (e.loaded / e.total) * 100;
                progressBar.style.width = percent + '%';
            });

            xhr.addEventListener('readystatechange', () => {
                if (xhr.readyState == 4) {
                    setTimeout(() => { progressBar.style.width = '0%'; }, 500); // reset
                    if (xhr.status == 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status !== 'success') {
                                alert("Failed to upload " + file.name + ": " + response.message);
                            }
                        } catch(e) { console.error(e); }
                    } else {
                        alert("Error uploading " + file.name);
                    }
                }
            });

            xhr.send(formData);
        }
        // Collapsible Card Logic
        const cardHeaders = document.querySelectorAll('.card h2');
        cardHeaders.forEach(header => {
            header.addEventListener('click', () => {
                header.classList.toggle('active');
                const content = header.nextElementSibling;
                if (content.style.display === "none") {
                    content.style.display = "block";
                } else {
                    content.style.display = "none";
                }
            });
            
            // If the header has the active class (from PHP Edit rendering), make sure it shows
            if (header.classList.contains('active')) {
                const content = header.nextElementSibling;
                content.style.display = "block";
            }
        });
    </script>
</body>
</html>
