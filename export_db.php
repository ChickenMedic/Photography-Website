<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT * FROM projects");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrationCode = "<?php\nrequire_once 'config.php';\n\ntry {\n";
    $migrationCode .= "    \$pdo->exec('TRUNCATE TABLE projects');\n\n";
    
    foreach ($projects as $p) {
        $encodedTitle = base64_encode($p['title']);
        $encodedDesc = base64_encode($p['description']);
        $encodedContent = base64_encode($p['content']);
        $encodedCover = base64_encode($p['cover_image'] ?? '');
        $encodedUrl = base64_encode($p['url']);
        $encodedSeries = base64_encode($p['series_name'] ?? '');
        $encodedCreated = base64_encode($p['created_at']);
        
        $migrationCode .= "    \$stmt = \$pdo->prepare(\"INSERT INTO projects (title, description, content, cover_image, url, series_name, created_at) VALUES (FROM_BASE64(?), FROM_BASE64(?), FROM_BASE64(?), FROM_BASE64(?), FROM_BASE64(?), FROM_BASE64(?), FROM_BASE64(?))\");\n";
        $migrationCode .= "    \$stmt->execute(['$encodedTitle', '$encodedDesc', '$encodedContent', '$encodedCover', '$encodedUrl', '$encodedSeries', '$encodedCreated']);\n\n";
    }
    
    $migrationCode .= "    echo 'Database updated successfully!';\n";
    $migrationCode .= "} catch (Exception \$e) {\n";
    $migrationCode .= "    echo 'Error: ' . \$e->getMessage();\n";
    $migrationCode .= "}\n?>";
    
    file_put_contents('db_sync.php', $migrationCode);
    echo "Migration script generated successfully at db_sync.php";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
