<?php
session_start();
require_once 'config.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
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
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('photo_') . '.' . $ext;
            $destination = UPLOAD_DIR . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("INSERT INTO photos (location_id, filename) VALUES (?, ?)");
                if ($stmt->execute([$location_id, $newFilename])) {
                    echo json_encode(["status" => "success", "message" => "Photo uploaded"]);
                    exit;
                }
            }
        }
    }
    echo json_encode(["status" => "error", "message" => "Upload failed"]);
    exit;
}

$message = '';
$error = '';

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


// Handle Photo Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo'])) {
    $location_id = !empty($_POST['location_id']) ? $_POST['location_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('photo_') . '.' . $ext;
            $destination = UPLOAD_DIR . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("INSERT INTO photos (location_id, filename, title, description) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$location_id, $newFilename, $title, $description])) {
                    $message = "Photo uploaded successfully!";
                } else {
                    $error = "Database error inserting photo.";
                }
            } else {
                $error = "Error moving uploaded file.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    } else {
         $error = "Error during file upload code: " . $file['error'];
    }
}

// Handle Project Creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_project'])) {
    $title = trim($_POST['project_title']);
    $description = trim($_POST['project_description']);
    $url = trim($_POST['project_url']);
    $file = $_FILES['cover_image'];
    
    $coverFilename = null;

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $coverFilename = uniqid('proj_cover_') . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $coverFilename)) {
                $error = "Failed to upload cover image.";
                $coverFilename = null;
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO projects (title, description, cover_image, url) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $coverFilename, $url])) {
             $message = "Project created successfully!";
        } else {
             $error = "Database error creating project.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo h(SITE_NAME); ?></title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div>
                <a href="index.php" target="_blank" class="btn-link" style="margin-right: 16px;">View Site</a>
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
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="location_name">Location Name</label>
                    <input type="text" name="location_name" id="location_name" placeholder="e.g. Paris" required>
                </div>
                <button type="submit" name="create_location">Create Location</button>
            </form>
        </div>

        <div class="card">
            <h2>Upload Photos by Location</h2>
            <p style="color: #94a3b8; margin-bottom: 20px;">Drag and drop photo files directly onto a location below to upload them.</p>
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

        <div class="card">
            <h2>Upload Single Photo</h2>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
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
                    <label for="photo">Select Photo (JPG, PNG, WEBP)</label>
                    <input type="file" name="photo" id="photo" required accept="image/jpeg, image/png, image/webp, image/gif">
                </div>
                <div class="form-group">
                    <label for="title">Title (Optional)</label>
                    <input type="text" name="title" id="title" placeholder="A beautiful sunset">
                </div>
                <div class="form-group">
                    <label for="description">Description (Optional)</label>
                    <textarea name="description" id="description" placeholder="Where was this taken?"></textarea>
                </div>
                <button type="submit" name="upload_photo">Upload Photo</button>
            </form>
        </div>

        <div class="card">
            <h2>Add Project</h2>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="project_title">Project Title</label>
                    <input type="text" name="project_title" id="project_title" required>
                </div>
                <div class="form-group">
                    <label for="cover_image">Cover Image (Optional)</label>
                    <input type="file" name="cover_image" id="cover_image" accept="image/jpeg, image/png, image/webp">
                </div>
                <div class="form-group">
                    <label for="project_url">External URL (Optional)</label>
                    <input type="url" name="project_url" id="project_url" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label for="project_description">Description</label>
                    <textarea name="project_description" id="project_description"></textarea>
                </div>
                <button type="submit" name="create_project">Create Project</button>
            </form>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
