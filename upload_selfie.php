<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['selfie'])) {
    echo json_encode(['success' => false, 'error' => 'No selfie uploaded']);
    exit;
}

$uploadDir = 'assets/uploads/selfies/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$filename = time() . '_' . $_SESSION['user_id'] . '.jpg';
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($_FILES['selfie']['tmp_name'], $targetPath)) {
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save selfie']);
}
?>