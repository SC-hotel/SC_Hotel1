<?php
// contact_process.php
header('Content-Type: application/json');

// --- Configuration ---
// Use a valid "From" email that matches your server's domain.
// If your site is example.com, use something like info@example.com
// We'll dynamically use the current domain if possible.
$server_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$from_email = 'noreply@' . $server_domain; // e.g., noreply@yoursite.com

$to = 'kennethvillanueva194@gmail.com';

// --- Collect and sanitize ---
$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// --- Validation ---
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Prevent header injection
$email_clean = str_replace(["\r", "\n", "\r\n"], '', $email);
$subject_clean = str_replace(["\r", "\n", "\r\n"], '', $subject);

// --- Build HTML email template ---
$email_subject = !empty($subject_clean) ? "SC Hotel Contact: {$subject_clean}" : "New Contact Form Message - SC Hotel";

$safe_name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_subject = htmlspecialchars($subject_clean, ENT_QUOTES, 'UTF-8');
$safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
$safe_message_nl = nl2br($safe_message);

$html_message = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>New Contact Message</title>
</head>
<body style="margin:0; padding:0; background:#f4f7fa; font-family:'Segoe UI', Arial, sans-serif;">
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background:#ffffff; margin:30px auto; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
    <tr>
        <td style="background:linear-gradient(135deg, #1e7e34, #28a745); padding:30px 20px; text-align:center; border-radius:12px 12px 0 0;">
            <h1 style="color:#ffffff; margin:0; font-size:28px; font-weight:700;">SC HOTEL</h1>
            <p style="color:#ffffff; margin:5px 0 0; font-size:16px; opacity:0.9;">New Contact Form Message</p>
        </td>
    </tr>
    <tr>
        <td style="padding:30px 25px;">
            <h2 style="color:#1e2a3a; font-size:20px; font-weight:600; border-bottom:2px solid #28a745; padding-bottom:10px;">Guest Information</h2>
            <table width="100%" style="border-collapse:collapse; margin:15px 0;">
                <tr><td style="padding:8px 0; font-weight:600; width:100px;">Name</td><td style="padding:8px 0;">{$safe_name}</td></tr>
                <tr><td style="padding:8px 0; font-weight:600;">Email</td><td style="padding:8px 0;">{$safe_email}</td></tr>
                <tr><td style="padding:8px 0; font-weight:600;">Subject</td><td style="padding:8px 0;">{$safe_subject}</td></tr>
            </table>
            <h3 style="color:#1e2a3a; font-size:18px; font-weight:600; margin:20px 0 10px;">Message</h3>
            <div style="background:#f8f9fa; padding:15px; border-radius:8px; border-left:4px solid #28a745; color:#2c3e50; line-height:1.6;">
                {$safe_message_nl}
            </div>
        </td>
    </tr>
    <tr>
        <td style="background:#f8f9fa; padding:20px; text-align:center; border-radius:0 0 12px 12px; font-size:13px; color:#6c757d;">
            <p style="margin:0;">&copy; SC Hotel Management System</p>
            <p style="margin:5px 0 0;">Thank you for using the SC Hotel Contact Form.</p>
        </td>
    </tr>
</table>
</body>
</html>
HTML;

// --- Email headers ---
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: SC Hotel <{$from_email}>\r\n";
$headers .= "Reply-To: {$email_clean}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Additional parameter to set envelope sender (helps with SPF/DKIM)
$additional_params = "-f" . $from_email;

// --- Send email ---
$mail_sent = mail($to, $email_subject, $html_message, $headers, $additional_params);

if ($mail_sent) {
    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);
} else {
    // Log the error for debugging (check your server's error log)
    error_log("SC Hotel Contact Form: mail() failed to send to $to");
    echo json_encode(['success' => false, 'message' => 'Unable to send your message. Please try again later.']);
}
?>