<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if (isset($_GET['room_id'])) {
    $stmt = $pdo->prepare("SELECT id, type, room_number, price_per_night FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['room_id']]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($room);
} else {
    echo json_encode(null);
}
?>