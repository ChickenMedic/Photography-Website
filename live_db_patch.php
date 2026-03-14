<?php
// live_db_patch.php
// WARNING: NEVER RUN THIS ON LOCALHOST AGAIN. ONLY RUN THIS ON THE LIVE AWS SERVER.
require_once 'config.php';

try {
    // 1. Add 'series_name' to projects if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'series_name'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE projects ADD COLUMN series_name VARCHAR(100) NULL AFTER url;");
        echo "Successfully added 'series_name' column to the 'projects' table.<br>";
    } else {
        echo "'series_name' column already exists.<br>";
    }

    // 2. Add 'tags' to photos if it doesn't exist (added locally earlier today)
    $stmt2 = $pdo->query("SHOW COLUMNS FROM photos LIKE 'tags'");
    if ($stmt2->rowCount() == 0) {
        $pdo->exec("ALTER TABLE photos ADD COLUMN tags VARCHAR(255) NULL AFTER description;");
        echo "Successfully added 'tags' column to the 'photos' table.<br>";
    } else {
        echo "'tags' column already exists.<br>";
    }

    echo "<br><b>Database structural patch complete!</b> You can now use the admin panel to create projects.";

} catch (PDOException $e) {
    echo "<b>Database Patch Failed:</b> " . $e->getMessage();
}
?>
