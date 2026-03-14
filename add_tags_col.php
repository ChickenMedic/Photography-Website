<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM photos LIKE 'tags'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE photos ADD COLUMN tags VARCHAR(255) DEFAULT '' AFTER description");
        echo "Successfully added 'tags' column to photos table.\n";
    } else {
        echo "Column 'tags' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
