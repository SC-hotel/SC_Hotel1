<?php
session_start();
define('DB_HOST', 'sql309.infinityfree.com');
define('DB_NAME', 'if0_41992765_sc');
define('DB_USER', 'if0_41992765');
define('DB_PASS', 'NwlX8lkU2vK');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}





// Include helper functions
require_once __DIR__ . '/functions.php';

define('SITE_URL', 'https://schotel.kesug.com/');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

// ========== SMTP Configuration (SendGrid) ==========
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');
define('SMTP_PASS', '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');
define('SMTP_FROM_EMAIL', 'kennethbaronda45@gmail.com');
define('SMTP_FROM_NAME', 'schotel');


// ========== Face++ API Configuration ==========
define('FACE_API_KEY', 'U2USZve6N87Wif5bYN8ivKFZjLusq-bJ');
define('FACE_API_SECRET', 'yJ-znXPvXR7EkwkzVIZYU8LelONPm0id');


// OpenAI API Configuration
define('OPENAI_API_KEY', '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');
define('OPENAI_MODEL', 'gpt-4o-mini');  // or 'gpt-4o-mini'
?>