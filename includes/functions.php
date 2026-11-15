<?php
require_once __DIR__ . '/config.php';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function uploadFile($file, $targetDir, $allowedTypes = ['jpg','jpeg','png','gif']) {
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    if (in_array($fileType, $allowedTypes) && move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return false;
}

function getAvailableRooms($check_in, $check_out, $type = '', $max_price = 0) {
    global $pdo;
    $sql = "SELECT r.* FROM rooms r WHERE r.status = 'available' AND r.id NOT IN (
                SELECT b.room_id FROM bookings b 
                WHERE b.status != 'cancelled' 
                AND ((b.check_in <= ? AND b.check_out >= ?) OR (b.check_in <= ? AND b.check_out >= ?))
            )";
    $params = [$check_out, $check_in, $check_in, $check_out];
    
    if (!empty($type)) {
        $sql .= " AND r.type = ?";
        $params[] = $type;
    }
    if ($max_price > 0) {
        $sql .= " AND r.price_per_night <= ?";
        $params[] = $max_price;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function calculateTotalPrice($room_id, $check_in, $check_out) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
    $days = (strtotime($check_out) - strtotime($check_in)) / (60*60*24);
    return $room['price_per_night'] * $days;
}

function isRoomAvailable($room_id, $check_in, $check_out) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE room_id = ? 
        AND status != 'cancelled'
        AND ((check_in <= ? AND check_out >= ?) 
        OR (check_in <= ? AND check_out >= ?)
        OR (check_in >= ? AND check_out <= ?))
    ");
    $stmt->execute([$room_id, $check_out, $check_in, $check_in, $check_in, $check_in, $check_out]);
    return $stmt->fetchColumn() == 0;
}

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========== EMAIL NOTIFICATION (FIXED VERSION) ==========
function sendBookingStatusEmail($user_email, $user_name, $booking_details, $status, $room_info) {
    // Log that the function was called
    error_log("sendBookingStatusEmail called for {$user_email}, status: {$status}");

    // Validate required fields
    if (empty($user_email) || empty($user_name)) {
        error_log("Email not sent: missing recipient email or name");
        return false;
    }

    // Prepare dates safely (fallback if missing)
    $check_in_display = isset($booking_details['check_in']) ? date('F d, Y', strtotime($booking_details['check_in'])) : 'Not specified';
    $check_out_display = isset($booking_details['check_out']) ? date('F d, Y', strtotime($booking_details['check_out'])) : 'Not specified';
    $check_in_time_display = isset($booking_details['check_in_time']) && !empty($booking_details['check_in_time']) 
                            ? date('h:i A', strtotime($booking_details['check_in_time'])) 
                            : '2:00 PM (default)';
    $total_price_display = isset($booking_details['total_price']) ? number_format($booking_details['total_price'], 2) : '0.00';
    $room_type = isset($room_info['type']) ? $room_info['type'] : 'Unknown';
    $room_number = isset($room_info['room_number']) ? $room_info['room_number'] : 'N/A';

    $subject = ($status == 'approved') ? "Booking Approved - SC Hotel" : "Booking Cancelled - SC Hotel";
    
    // HTML email template
    $html_content = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .button { display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .details { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header"><h2>SC Hotel</h2></div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($user_name) . ',</p>
                <p>Your booking has been <strong>' . strtoupper($status) . '</strong>.</p>
                <div class="details">
                    <h4>Booking Details:</h4>
                    <p><strong>Room:</strong> ' . htmlspecialchars($room_type) . ' (#' . htmlspecialchars($room_number) . ')</p>
                    <p><strong>Check-in Date:</strong> ' . $check_in_display . '</p>
                    <p><strong>Check-out Date:</strong> ' . $check_out_display . '</p>
                    <p><strong>Check-in Time:</strong> ' . $check_in_time_display . '</p>
                    <p><strong>Total Amount:</strong> ₱' . $total_price_display . '</p>
                </div>
                ' . ($status == 'approved' ? '<p>We look forward to welcoming you. If you have any questions, please contact us.</p>' : '<p>We regret any inconvenience. If you believe this was a mistake, please contact us.</p>') . '
                <a href="' . SITE_URL . 'mybookings.php" class="button">View My Bookings</a>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' SC Hotel. All rights reserved.<br>
                123 Luxury Avenue, Downtown City | +1 (555) 123-4567 | info@schotel.com
            </div>
        </div>
    </body>
    </html>';
    
    $plain_text = "Dear $user_name,\n\nYour booking has been " . strtoupper($status) . ".\n\n";
    $plain_text .= "Booking Details:\nRoom: $room_type (#$room_number)\n";
    $plain_text .= "Check-in: $check_in_display\nCheck-out: $check_out_display\n";
    $plain_text .= "Check-in Time: $check_in_time_display\n";
    $plain_text .= "Total: ₱$total_price_display\n\n";
    $plain_text .= "View your bookings: " . SITE_URL . "mybookings.php\n";

    // ------------------------------------------------------------
    // Method 1: Try PHPMailer with SendGrid
    // ------------------------------------------------------------
    $phpmailer_path = __DIR__ . '/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailer_path)) {
        require_once __DIR__ . '/phpmailer/src/Exception.php';
        require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/src/SMTP.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user_email, $user_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_content;
            $mail->AltBody = $plain_text;
            $mail->send();
            error_log("Email sent successfully via PHPMailer/SendGrid to {$user_email}");
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer failed: " . $mail->ErrorInfo);
            // Fall through to mail() fallback
        }
    } else {
        error_log("PHPMailer not found at $phpmailer_path");
    }

    // ------------------------------------------------------------
    // Method 2: Fallback to PHP mail() function
    // ------------------------------------------------------------
    $headers = "From: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    if (mail($user_email, $subject, $html_content, $headers)) {
        error_log("Email sent successfully via fallback mail() to {$user_email}");
        return true;
    } else {
        error_log("Both PHPMailer and mail() failed to send email to {$user_email}");
        return false;
    }
}
?>