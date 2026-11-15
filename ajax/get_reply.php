<?php
// Allow cross-origin requests from your own domain (optional but safe)
header('Access-Control-Allow-Origin: https://schotel.kesug.com');
header('Content-Type: application/json');

$message = isset($_GET['msg']) ? strtolower(trim($_GET['msg'])) : '';

if (empty($message)) {
    echo json_encode(['reply' => 'Please type a message.']);
    exit;
}

// Simple rule-based responses (same logic as before)
function getBotReply($msg) {
    if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false) {
        return "Hello! Welcome to SC Hotel. How can I help you today?";
    }
    if (strpos($msg, 'check-in') !== false || strpos($msg, 'check in') !== false) {
        return "Check-in time is 2:00 PM. Check-out is 11:00 AM. Early check-in subject to availability.";
    }
    if (strpos($msg, 'cancellation') !== false || strpos($msg, 'cancel') !== false) {
        return "Free cancellation up to 24 hours before check-in. Cancellations after that may incur a one-night charge.";
    }
    if (strpos($msg, 'price') !== false || strpos($msg, 'cost') !== false || strpos($msg, 'rate') !== false) {
        return "Our room prices per night: Standard: ₱2,500, Deluxe: ₱4,000, Suite: ₱6,000, Superior: ₱3,500.";
    }
    if (strpos($msg, 'available') !== false || strpos($msg, 'free room') !== false) {
        return "To see real-time availability, please visit the Booking page and select your dates. I can help with general info!";
    }
    if (strpos($msg, 'recommend') !== false || strpos($msg, 'suggest') !== false) {
        return "I recommend our Deluxe room – great value with excellent amenities!";
    }
    if (strpos($msg, 'book') !== false || strpos($msg, 'reserve') !== false) {
        return "To make a booking, please log in to your account and go to the Booking page. I can help you find available rooms!";
    }
    if (strpos($msg, 'thank') !== false) {
        return "You're welcome! Let me know if you need anything else.";
    }
    return "Thank you for your message. You can ask me about check‑in/out times, room prices, cancellation policy, or booking help.";
}

$reply = getBotReply($message);
echo json_encode(['reply' => $reply]);
?>