<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Step 1: Loading config.php...<br>";
require_once 'includes/config.php';
echo "Step 2: Config loaded. Testing PDO...<br>";
$stmt = $pdo->query("SELECT 1");
echo "Step 3: Database works.<br>";
echo "Step 4: Checking functions.php...<br>";
if (function_exists('generateCSRFToken')) {
    echo "CSRF function exists.<br>";
} else {
    echo "CSRF function missing!<br>";
}
echo "All good. If you see this, your core files are fine.<br>";
?>