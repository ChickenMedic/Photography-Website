<?php
require_once 'config.php';

try {
    // Because the AWS database grids push "newest" to the top left, 
    // we need Part 1 to have the highest/newest time to display first.
    
    // Using LIKE '%Part 1%' as a fallback just in case the URL was typed differently on the live server.
    $pdo->exec("UPDATE projects SET created_at = '2026-03-14 12:00:00' WHERE url IN ('XamppSetup', 'AmazonLinuxSetup') OR title LIKE '%Part 1%'");
    $pdo->exec("UPDATE projects SET created_at = '2026-03-14 11:00:00' WHERE url = 'GitSetup' OR title LIKE '%Part 2%'");
    $pdo->exec("UPDATE projects SET created_at = '2026-03-14 10:00:00' WHERE url = 'AWSGuideStep1' OR title LIKE '%Part 3%'");
    $pdo->exec("UPDATE projects SET created_at = '2026-03-14 09:00:00' WHERE url = 'BuilderGuide' OR title LIKE '%Part 4%'");

    echo "<h1>Timeline Sequence Patched</h1>";
    echo "Successfully updated live timestamps! Your projects should now perfectly display 1, 2, 3, 4.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
