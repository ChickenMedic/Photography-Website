<?php
require_once __DIR__ . '/config.php';

try {
    // Create locations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS locations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Locations table created.\n";

    // Try to add location_id column to photos table
    try {
        $pdo->exec("ALTER TABLE photos ADD COLUMN location_id INT NULL");
        $pdo->exec("ALTER TABLE photos ADD FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL");
        echo "Added location_id column to photos.\n";
    } catch (PDOException $e) {
        // If it fails, maybe column already exists or similar error; ignore
        echo "Column location_id might already exist or error: " . $e->getMessage() . "\n";
    }

    echo "Migration completed.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
