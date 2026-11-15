<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// If already logged in as admin, go to dashboard
if (isAdmin()) {
    header('Location: index.php');
    exit;
}
// If logged in as a regular user, redirect to homepage
if (isLoggedIn() && !isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    // Only allow users with role = 'admin'
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_role'] = $admin['role'];
        $loginSuccess = true; // Show loading screen, then redirect via JS
    } else {
        $error = 'Invalid admin credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | SC Hotel</title>
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

        /* ========== LOGIN CARD ========== */
        .login-card {
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .login-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        /* Gold accent line at the top of the card */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gold);
        }

        .login-header {
            padding: 40px 35px 20px;
            text-align: center;
        }

        .login-header h3 {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .login-header p {
            font-family: var(--font-body);
            font-size: 0.85rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .login-body {
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
        .btn-login {
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

        .btn-login:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.25);
        }

        .user-link {
            color: var(--gold);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: var(--transition);
            border-bottom: 1px solid transparent;
        }

        .user-link:hover {
            border-bottom-color: var(--gold);
            color: var(--gold);
        }

        .back-home {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            transition: var(--transition);
        }

        .back-home:hover {
            color: var(--charcoal);
        }

        /* ========== ALERTS ========== */
        .alert-danger {
            background-color: #fdf3f3;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 0;
            color: #721c24;
            font-size: 0.85rem;
        }

        /* ========== LOADING SCREEN ========== */
        #loading-screen {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: var(--charcoal);
            color: #fff;
            overflow: hidden;
        }
        #loading-screen.active { display: flex; }

        /* Animated gold glow blobs */
        #loading-screen::before,
        #loading-screen::after {
            content: "";
            position: absolute;
            width: 45vmax;
            height: 45vmax;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201, 162, 39, 0.15) 0%, rgba(201, 162, 39, 0) 70%);
            animation: floatBlob 6s ease-in-out infinite alternate;
        }
        #loading-screen::before { top: -15vmax; left: -15vmax; }
        #loading-screen::after { bottom: -15vmax; right: -15vmax; animation-delay: -3s; }
        @keyframes floatBlob {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(4vmax, 4vmax) scale(1.15); }
        }

        .loader-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeInUp 0.6s ease both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Spinner ring + shield icon */
        .loader-ring {
            width: 110px;
            height: 110px;
            margin: 0 auto 24px;
            position: relative;
        }
        .loader-ring::before,
        .loader-ring::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--gold);
            border-right-color: rgba(201, 162, 39, 0.3);
            animation: spin 1s linear infinite;
        }
        .loader-ring::after {
            inset: 12px;
            border-top-color: #ffffff;
            border-right-color: rgba(255,255,255,0.2);
            animation-direction: reverse;
            animation-duration: 1.4s;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loader-ring .loader-icon {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--gold);
            animation: pulseIcon 1.5s ease-in-out infinite;
        }
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%      { transform: scale(1.12); opacity: 0.7; }
        }

        .loader-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 400;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }
        .loader-title em {
            font-style: italic;
            color: var(--gold);
        }
        .loader-subtitle {
            font-family: var(--font-body);
            font-size: 0.85rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 26px;
        }

        /* progress bar */
        .loader-progress {
            width: 240px;
            height: 2px;
            margin: 0 auto;
            background: rgba(255,255,255,0.15);
            border-radius: 50px;
            overflow: hidden;
        }
        .loader-progress span {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: 50px;
            background: var(--gold);
            animation: fillBar 1s ease forwards;
        }
        @keyframes fillBar {
            from { width: 0%; }
            to   { width: 100%; }
        }

        /* checkmark pop */
        .loader-check {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.6rem;
            color: var(--gold);
            opacity: 0;
            transform: scale(0.4);
            animation: checkPop 0.4s ease 0.85s forwards;
        }
        @keyframes checkPop {
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 768px) {
            .login-body { padding: 10px 25px 30px; }
            .login-header { padding: 30px 25px 15px; }
            .login-header h3 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h3>Admin Login</h3>
                        <p>Access the management panel</p>
                    </div>
                    <div class="login-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem; padding-top: 1.1rem;"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Admin Email</label>
                                <input type="email" name="email" class="form-control" placeholder="admin@schotel.com" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                Sign In as Admin
                            </button>
                        </form>
                        
                        <hr class="my-4" style="opacity: 0.1;">
                        
                        <div class="text-center">
                            <a href="../login.php" class="user-link">
                                <i class="fas fa-arrow-left me-1" style="font-size: 0.7rem;"></i> Back to User Login
                            </a>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="../index.php" class="back-home">
                                <i class="fas fa-home me-1" style="font-size: 0.75rem;"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Animated Loading Screen ===== -->
    <div id="loading-screen">
        <div class="loader-content">
            <div class="loader-ring">
                <div class="loader-icon"><i class="fas fa-user-shield"></i></div>
                <div class="loader-check"><i class="fas fa-check"></i></div>
            </div>
            <div class="loader-title">Welcome, <em>Admin</em></div>
            <div class="loader-subtitle">Loading your dashboard</div>
            <div class="loader-progress"><span></span></div>
        </div>
    </div>

    <?php if ($loginSuccess): ?>
    <script>
        // Show the animated loading screen, then redirect
        document.addEventListener('DOMContentLoaded', function () {
            var loader = document.getElementById('loading-screen');
            loader.classList.add('active');

            // prevent scroll while loading
            document.body.style.overflow = 'hidden';

            setTimeout(function () {
                window.location.href = 'index.php';
            }, 1600);
        });
    </script>
    <?php endif; ?>
</body>
</html>