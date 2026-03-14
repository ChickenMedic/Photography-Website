<?php
require_once 'config.php';
$stmt = $pdo->query("SELECT id, title, url, LENGTH(content) as content_len FROM projects");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Title: {$row['title']}, URL: {$row['url']}, Content Length: {$row['content_len']}<br>";
}
?>
