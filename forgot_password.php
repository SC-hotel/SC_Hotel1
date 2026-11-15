<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Auto-detect PHPMailer path
$phpmailer_paths = [
    __DIR__ . '/includes/phpmailer/src/PHPMailer.php',
    __DIR__ . '/includes/PHPMailer/src/PHPMailer.php',
    __DIR__ . '/phpmailer/src/PHPMailer.php',
];

$mailer_loaded = false;
foreach ($phpmailer_paths as $path) {
    if (file_exists($path)) {
        require_once __DIR__ . '/includes/phpmailer/src/Exception.php';
        require_once __DIR__ . '/includes/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/includes/phpmailer/src/SMTP.php';
        $mailer_loaded = true;
        break;
    }
}

if (!$mailer_loaded) {
    die('PHPMailer not found. Please check the path.');
}

// === SMTP CONFIGURATION (SendGrid) ===
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_USER', 'apikey');                           // ✅ Correct: 'apikey' as username
define('SMTP_PASS', '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');            // <-- Replace with your actual API key
define('SMTP_PORT', 587);
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
define('MAIL_FROM', 'kennethbaronda45@gmail.com');   // Verified sender
define('MAIL_FROM_NAME', 'schotel');

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$step = 'email';
$error = '';
$success = '';
$email = '';

// Step 1: Send verification code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_code'])) {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            $error = 'No account found with this email address.';
        } else {
            // Generate 6-digit code
            $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Delete old codes
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $code, $expires_at]);
            
            // Send email using PHPMailer
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($email);
                $mail->Subject = "Password Reset Code - SC Hotel";
                
                // REDESIGNED HTML Email Template (Luxury Style)
                $htmlBody = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Password Reset Code</title>
                    <style>
                        body { font-family: \'Jost\', Arial, sans-serif; background-color: #faf8f5; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 0; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); border: 1px solid rgba(201,162,39,0.15); }
                        .header { background: #1a1a1a; padding: 40px 20px; text-align: center; color: white; border-bottom: 3px solid #c9a227; }
                        .header h1 { font-family: \'Cormorant Garamond\', Georgia, serif; margin: 0; font-size: 32px; font-weight: 600; letter-spacing: 1px; }
                        .header p { margin: 8px 0 0; opacity: 0.8; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; }
                        .content { padding: 40px 30px; }
                        .content p { color: #555; line-height: 1.7; font-size: 15px; font-weight: 300; }
                        .code-box { background: #faf8f5; padding: 25px; text-align: center; margin: 30px 0; border: 1px solid rgba(201,162,39,0.3); }
                        .code { font-size: 42px; font-weight: 600; color: #c9a227; letter-spacing: 8px; font-family: monospace; }
                        .expiry { font-size: 12px; color: #8a8a8a; margin-top: 15px; text-transform: uppercase; letter-spacing: 1px; }
                        .footer { background: #f0ece4; padding: 20px; text-align: center; font-size: 12px; color: #8a8a8a; border-top: 1px solid rgba(0,0,0,0.05); }
                        .footer a { color: #1a1a1a; text-decoration: none; border-bottom: 1px solid #c9a227; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>SC HOTEL</h1>
                            <p>Password Reset Request</p>
                        </div>
                        <div class="content">
                            <p style="margin-top: 0;">Hello,</p>
                            <p>We received a request to reset your password for your SC Hotel account. Use the verification code below to proceed.</p>
                            <div class="code-box">
                                <div class="code">' . $code . '</div>
                                <div class="expiry">This code will expire in 15 minutes</div>
                            </div>
                            <p style="text-align: center; font-size: 13px;">If you did not request this, please ignore this email.</p>
                        </div>
                        <div class="footer">
                            &copy; ' . date('Y') . ' SC Hotel. All rights reserved.<br>
                            <a href="' . SITE_URL . '">' . SITE_URL . '</a>
                        </div>
                    </div>
                </body>
                </html>
                ';
                
                $mail->isHTML(true);
                $mail->Body = $htmlBody;
                $mail->AltBody = "Dear User,\n\nYou requested to reset your password for your SC Hotel account.\n\nYour verification code is: $code\n\nThis code will expire in 15 minutes.\n\nIf you did not request this, please ignore this email.\n\nRegards,\nSC Hotel Team";
                
                $mail->send();
                $success = 'A verification code has been sent to your email address.';
                $step = 'verify';
                $_SESSION['reset_email'] = $email;
            } catch (Exception $e) {
                $error = 'Failed to send email. Mailer Error: ' . $mail->ErrorInfo;
            }
        }
    }
}

// Step 2: Verify code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $email = $_SESSION['reset_email'] ?? '';
    $code = sanitizeInput($_POST['code']);
    
    if (empty($code)) {
        $error = 'Please enter the verification code.';
    } else {
        $stmt = $pdo->prepare("SELECT id, expires_at, used FROM password_resets WHERE email = ? AND code = ?");
        $stmt->execute([$email, $code]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            $error = 'Invalid verification code.';
        } elseif ($reset['used']) {
            $error = 'This code has already been used. Please request a new one.';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $error = 'The verification code has expired. Please request a new one.';
        } else {
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['id']]);
            $success = 'Code verified! Please set your new password.';
            $step = 'reset';
            $_SESSION['reset_verified'] = true;
        }
    }
}

// Step 3: Reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        $error = 'Please verify your code first.';
    } else {
        $email = $_SESSION['reset_email'] ?? '';
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);
            
            $success = 'Password updated successfully! You can now login.';
            header("refresh:3;url=login.php");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | SC Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========== LUXURY DESIGN SYSTEM ========== */
        :root {
            --gold: #c9a227;
            --gold-light: #e6c85a;
            --charcoal: #1a1a1a;
            --cream: #faf8f5;
            --cream-dark: #f0ece4;
            --text-muted: #8a8a8a;
            --font-display: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'Jost', 'Segoe UI', sans-serif;
            --shadow-sm: 0 2px 12px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 3rem 0;
            color: var(--charcoal);
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(201, 162, 39, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(201, 162, 39, 0.04) 0%, transparent 50%);
        }

        /* ========== FORGOT PASSWORD CARD ========== */
        .forgot-card {
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .forgot-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .forgot-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gold);
        }

        .forgot-header {
            padding: 40px 35px 20px;
            text-align: center;
        }

        .forgot-header h3 {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .forgot-header p {
            font-family: var(--font-body);
            font-size: 0.85rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .forgot-body {
            padding: 10px 40px 40px;
        }

        /* ========== FORM ELEMENTS ========== */
        .form-label {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--charcoal);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border-radius: 0;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: var(--transition);
            background: var(--cream);
            color: var(--charcoal);
        }

        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.15);
            background: #fff;
        }

        .form-control::placeholder {
            color: #aaa;
            font-weight: 300;
        }

        /* ========== BUTTONS & LINKS ========== */
        .btn-primary-custom {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 16px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transition: var(--transition);
            width: 100%;
        }

        .btn-primary-custom:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.25);
        }

        .link-gold {
            color: var(--gold);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: var(--transition);
        }

        .link-gold:hover {
            border-bottom-color: var(--gold);
        }

        /* ========== ALERTS ========== */
        .alert-danger, .alert-success {
            border-radius: 0;
            font-size: 0.85rem;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background-color: #fdf3f3;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert-success {
            background-color: #f0f9f4;
            border-color: var(--gold);
            color: #155724;
        }

        /* ========== CUSTOM STEP INDICATOR ========== */
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        
        .step-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 0.85rem;
            transition: var(--transition);
            border: 1px solid #e0e0e0;
            background: var(--cream);
            color: var(--text-muted);
        }
        
        .step-dot.active {
            background: var(--charcoal);
            border-color: var(--charcoal);
            color: var(--gold);
        }
        
        .step-dot.inactive {
            background: var(--cream);
            border-color: #e0e0e0;
            color: #aaa;
        }
        
        .step-line {
            flex: 1;
            height: 1px;
            max-width: 50px;
            background: #e0e0e0;
            transition: var(--transition);
        }
        
        .step-line.active {
            background: var(--gold);
        }
        
        .step-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 8px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .forgot-body { padding: 10px 25px 30px; }
            .forgot-header { padding: 30px 25px 15px; }
            .forgot-header h3 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="forgot-card">
                    <div class="forgot-header">
                        <h3>Reset Password</h3>
                        <p>Recover your SC Hotel account</p>
                    </div>
                    <div class="forgot-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="text-center">
                                <div class="step-dot <?= $step == 'email' ? 'active' : ($step == 'verify' ? 'active' : 'inactive') ?>">1</div>
                                <div class="step-label">Email</div>
                            </div>
                            <div class="step-line <?= $step == 'verify' || $step == 'reset' ? 'active' : '' ?>"></div>
                            <div class="text-center">
                                <div class="step-dot <?= $step == 'verify' ? 'active' : ($step == 'reset' ? 'active' : 'inactive') ?>">2</div>
                                <div class="step-label">Verify</div>
                            </div>
                            <div class="step-line <?= $step == 'reset' ? 'active' : '' ?>"></div>
                            <div class="text-center">
                                <div class="step-dot <?= $step == 'reset' ? 'active' : 'inactive' ?>">3</div>
                                <div class="step-label">Reset</div>
                            </div>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($step == 'email'): ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="your@email.com" required>
                                </div>
                                <button type="submit" name="send_code" class="btn-primary-custom">
                                    Send Verification Code
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($step == 'verify'): ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" name="code" placeholder="Enter 6-digit code" maxlength="6" required style="letter-spacing: 0.5em; font-size: 1.2rem; text-align: center;">
                                    <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">Enter the code sent to your email</small>
                                </div>
                                <button type="submit" name="verify_code" class="btn-primary-custom">
                                    Verify Code
                                </button>
                            </form>
                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="link-gold" style="font-size: 0.85rem;">Request a new code</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($step == 'reset'): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" placeholder="••••••••" required>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Minimum 6 characters</small>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" placeholder="••••••••" required>
                                </div>
                                <button type="submit" name="reset_password" class="btn-primary-custom">
                                    Reset Password
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                            <p class="mb-0" style="font-size: 0.85rem; color: var(--text-muted);">
                                Remember your password? 
                                <a href="login.php" class="link-gold ms-1">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>