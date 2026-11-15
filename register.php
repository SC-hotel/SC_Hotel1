<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please refresh the page.';
    } else {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']) ? true : false;
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!$terms) {
            $error = 'You must agree to the Terms and Conditions to register.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered. Please login.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $default_pic = 'default.png';
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, profile_pic, role) VALUES (?, ?, ?, ?, 'user')");
                if ($stmt->execute([$name, $email, $hashed_password, $default_pic])) {
                    $success = 'Registration successful! You can now login.';
                    $name = $email = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SC Hotel</title>
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

        /* ========== REGISTER CARD ========== */
        .register-card {
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .register-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gold);
        }

        .register-header {
            padding: 40px 35px 20px;
            text-align: center;
        }

        .register-header h3 {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .register-header p {
            font-family: var(--font-body);
            font-size: 0.85rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .register-body {
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
        .btn-register {
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

        .btn-register:hover:not(:disabled) {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.25);
        }

        .btn-register:disabled {
            background: #e0e0e0;
            border-color: #e0e0e0;
            color: #999;
            cursor: not-allowed;
        }

        .login-link {
            color: var(--gold);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: var(--transition);
        }

        .login-link:hover {
            border-bottom-color: var(--gold);
        }

        /* ========== TERMS CHECKBOX ========== */
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-bottom: 1.5rem;
        }

        .terms-check input[type="checkbox"] {
            margin-top: 0.2rem;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: var(--gold); /* Modern browsers */
            border-radius: 0;
        }

        .terms-check label {
            font-size: 0.85rem;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1.5;
        }

        .terms-check a {
            color: var(--charcoal);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid var(--gold);
            cursor: pointer;
            transition: var(--transition);
        }

        .terms-check a:hover {
            color: var(--gold);
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

        /* ========== MODAL - PREMIUM TERMS ========== */
        .modal-content {
            border-radius: 0;
            border: none;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--charcoal);
            padding: 1.5rem 2rem;
            border-bottom: none;
        }
        
        .modal-header .modal-title {
            font-family: var(--font-display);
            font-weight: 600;
            color: #ffffff;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .modal-header .modal-title i {
            font-size: 1.4rem;
            color: var(--gold);
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
            transition: var(--transition);
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 2.5rem;
            max-height: 65vh;
            overflow-y: auto;
            background: var(--cream);
            font-size: 0.9rem;
            line-height: 1.8;
            color: var(--charcoal);
            font-family: var(--font-body);
        }

        .modal-body h5 {
            font-family: var(--font-display);
            color: var(--charcoal);
            font-weight: 600;
            margin-top: 1.8rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 0.5rem;
        }

        .modal-body h5 i {
            color: var(--gold);
            font-size: 1.1rem;
        }

        .modal-body h5:first-of-type {
            margin-top: 0;
        }

        .modal-body p {
            color: #555;
            margin-bottom: 1rem;
            font-weight: 300;
            text-align: justify;
        }

        .modal-body .last-updated {
            text-align: right;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding-top: 1rem;
        }

        .modal-footer {
            background: #ffffff;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1.2rem 2rem;
            justify-content: center;
        }

        .btn-agree {
            background: var(--gold);
            color: #ffffff;
            border: 1px solid var(--gold);
            border-radius: 0;
            padding: 0.8rem 2.5rem;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-agree:hover {
            background: var(--charcoal);
            border-color: var(--charcoal);
            transform: translateY(-2px);
            color: #ffffff;
        }

        /* Scrollbar styling */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }
        .modal-body::-webkit-scrollbar-track {
            background: var(--cream-dark);
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: var(--gold);
        }

        @media (max-width: 768px) {
            .register-body { padding: 10px 25px 30px; }
            .register-header { padding: 30px 25px 15px; }
            .register-header h3 { font-size: 1.8rem; }
            .modal-body { padding: 1.5rem; }
            .modal-body p { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="register-card">
                    <div class="register-header">
                        <h3>Create Account</h3>
                        <p>Join SC Hotel today</p>
                    </div>
                    <div class="register-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem; padding-top: 1.1rem;"></button>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?> <a href="login.php" style="color: var(--gold); font-weight: 500; text-decoration: none; border-bottom: 1px solid var(--gold);">Login now</a>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem; padding-top: 1.1rem;"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name ?? '') ?>" placeholder="John Doe" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="your@email.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                                <small class="text-muted" style="font-size: 0.7rem; letter-spacing: 0.05em;">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" placeholder="••••••••" required>
                            </div>

                            <!-- Terms and Conditions Checkbox -->
                            <div class="terms-check">
                                <input type="checkbox" name="terms" id="termsCheckbox" required>
                                <label for="termsCheckbox">
                                    I have read and agree to the 
                                    <a id="termsLink">Terms and Conditions</a>.
                                </label>
                            </div>

                            <button type="submit" class="btn-register" id="registerBtn" disabled>
                                Register Account
                            </button>
                        </form>
                        
                        <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                            <p class="mb-0" style="font-size: 0.85rem; color: var(--text-muted);">
                                Already have an account? 
                                <a href="login.php" class="login-link ms-1">Login here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== PREMIUM TERMS MODAL ========== -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-contract"></i> Terms & Conditions
                    </h5>
                    <button type="button" class="btn-close" id="modalCloseBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5><i class="fas fa-check-circle"></i> 1. Acceptance of Terms</h5>
                    <p>By registering for an account on SC Hotel, you agree to comply with and be bound by these Terms and Conditions. If you do not agree, please do not use our services.</p>

                    <h5><i class="fas fa-user-shield"></i> 2. User Accounts</h5>
                    <p>You are responsible for maintaining the confidentiality of your account credentials. You agree to notify us immediately of any unauthorized use of your account.</p>

                    <h5><i class="fas fa-calendar-check"></i> 3. Booking Policies</h5>
                    <p>All room bookings are subject to availability. SC Hotel reserves the right to cancel or modify bookings in case of unforeseen circumstances.</p>

                    <h5><i class="fas fa-credit-card"></i> 4. Payment and Cancellation</h5>
                    <p>Payments are processed securely. Cancellation policies vary by room type and will be displayed at the time of booking.</p>

                    <h5><i class="fas fa-lock"></i> 5. Privacy Policy</h5>
                    <p>Your personal data is collected and processed in accordance with our Privacy Policy. We do not share your data with third parties without your consent.</p>

                    <h5><i class="fas fa-ban"></i> 6. Prohibited Activities</h5>
                    <p>You agree not to use our platform for any unlawful purpose, including but not limited to fraudulent bookings, spamming, or distributing malicious content.</p>

                    <h5><i class="fas fa-scale-balanced"></i> 7. Limitation of Liability</h5>
                    <p>SC Hotel is not liable for any indirect, incidental, or consequential damages arising from your use of our services.</p>

                    <h5><i class="fas fa-pen"></i> 8. Changes to Terms</h5>
                    <p>We reserve the right to update these Terms at any time. Continued use of our services constitutes acceptance of the updated Terms.</p>

                    <div class="last-updated">
                        <i class="far fa-calendar-alt me-1"></i> Last updated: <?= date('F d, Y') ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-agree" id="agreeBtn">
                        I Agree
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckbox = document.getElementById('termsCheckbox');
            const registerBtn = document.getElementById('registerBtn');
            const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
            const agreeBtn = document.getElementById('agreeBtn');
            const termsLink = document.getElementById('termsLink');
            const closeBtn = document.getElementById('modalCloseBtn');

            // Open modal when checkbox is clicked (prevent toggling)
            termsCheckbox.addEventListener('click', function(e) {
                e.preventDefault();
                termsModal.show();
            });

            // Open modal when link is clicked
            termsLink.addEventListener('click', function(e) {
                e.preventDefault();
                termsModal.show();
            });

            // When "I Agree" is clicked, check the checkbox, enable register button, close modal
            agreeBtn.addEventListener('click', function() {
                termsCheckbox.checked = true;
                registerBtn.disabled = false;
                termsModal.hide();
            });

            // Close button
            closeBtn.addEventListener('click', function() {
                termsModal.hide();
            });

            // Safety check if user somehow unchecks
            termsCheckbox.addEventListener('change', function() {
                registerBtn.disabled = !this.checked;
            });
        });
    </script>
</body>
</html>