<?php
require_once 'config.php';
$stmt = $pdo->query("DESCRIBE photos");
while ($row = $stmt->fetch()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>
