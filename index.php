<?php
require_once __DIR__ . '/includes/config.php';

// ============================================================
// Contact Form Processing (with detailed mail error logging)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    header('Content-Type: application/json');

    $server_domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $from_email = 'noreply@' . $server_domain;
    $to = 'kennethvillanueva194@gmail.com';

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if (empty($name)) $errors[] = 'name';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
    if (empty($message)) $errors[] = 'message';
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields: ' . implode(', ', $errors)]);
        exit;
    }

    $email_clean = str_replace(["\r", "\n", "\r\n"], '', $email);
    $subject_clean = str_replace(["\r", "\n", "\r\n"], '', $subject);

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

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: SC Hotel <{$from_email}>\r\n";
    $headers .= "Reply-To: {$email_clean}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $additional_params = "-f" . $from_email;

    $mail_sent = false;
    $error_message = '';
    try {
        @ini_set('sendmail_from', $from_email);
        $mail_sent = mail($to, $email_subject, $html_message, $headers, $additional_params);
        if (!$mail_sent) {
            $error = error_get_last();
            $error_message = $error ? $error['message'] : 'Unknown mail error.';
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $mail_sent = false;
    }

    if ($mail_sent) {
        echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully.']);
    } else {
        error_log("SC Hotel Contact: mail() failed. Error: " . $error_message);
        echo json_encode([
            'success' => false,
            'message' => 'Mail send failed: ' . $error_message
        ]);
    }
    exit;
}
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SC Hotel - Luxury & Comfort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========== LUXURY DESIGN SYSTEM ========== */
        :root {
            --primary: #28a745;
            --primary-dark: #1e7e34;
            --accent: #f39c12;
            --bg-light: #f8f9fa;
            --gold: #c9a227;
            --gold-light: #e6c85a;
            --charcoal: #1a1a1a;
            --charcoal-soft: #2d2d2d;
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-body);
            overflow-x: hidden;
            background: var(--cream);
            color: var(--charcoal);
            font-weight: 400;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes contactPopIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes contactShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-6px); }
            80% { transform: translateX(6px); }
        }
        @keyframes contactFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUpChat {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
        @keyframes subtlePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(40,167,69,0.3); }
            50% { box-shadow: 0 0 0 12px rgba(40,167,69,0); }
        }

        /* ========== SCROLL REVEAL ========== */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-left {
            opacity: 0;
            transform: translateX(-60px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .reveal-left.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(60px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        .reveal-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* ========== TYPOGRAPHY ========== */
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-display);
            font-weight: 600;
            color: var(--charcoal);
            letter-spacing: 0.02em;
        }

        .section-label {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
            display: block;
        }

        /* ========== HERO CAROUSEL ========== */
        .hero-carousel {
            height: 100vh;
            overflow: hidden;
            position: relative;
        }
        .hero-carousel .carousel-item {
            height: 100vh;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .hero-carousel .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(10,10,10,0.65) 0%, rgba(10,10,10,0.25) 50%, rgba(10,10,10,0.5) 100%);
            z-index: 1;
        }
        .hero-carousel .carousel-caption {
            bottom: 50%;
            transform: translateY(50%);
            z-index: 2;
            text-align: left;
            left: 8%;
            right: auto;
            max-width: 680px;
        }
        .hero-carousel .carousel-caption .hero-label {
            font-family: var(--font-body);
            font-size: 0.8rem;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            color: var(--gold-light);
            margin-bottom: 20px;
            display: inline-block;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.3s forwards;
        }
        .hero-carousel h1 {
            font-size: 5rem;
            font-weight: 400;
            color: #ffffff;
            line-height: 1.1;
            text-shadow: 2px 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.5s forwards;
        }
        .hero-carousel h1 em {
            font-style: italic;
            color: var(--gold-light);
            font-weight: 300;
        }
        .hero-carousel p {
            font-family: var(--font-body);
            font-size: 1.15rem;
            font-weight: 300;
            color: rgba(255,255,255,0.85);
            letter-spacing: 0.05em;
            margin-bottom: 35px;
            max-width: 480px;
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.7s forwards;
        }
        .hero-carousel .btn-hero {
            padding: 16px 42px;
            border-radius: 0;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.6);
            color: #fff;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            opacity: 0;
            animation: fadeInUp 0.8s ease 0.9s forwards;
        }
        .hero-carousel .btn-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gold);
            transition: var(--transition);
            z-index: -1;
        }
        .hero-carousel .btn-hero:hover {
            border-color: var(--gold);
            color: #fff;
        }
        .hero-carousel .btn-hero:hover::before {
            left: 0;
        }
        .hero-carousel .btn-hero i {
            margin-left: 10px;
            transition: transform 0.3s ease;
        }
        .hero-carousel .btn-hero:hover i {
            transform: translateX(5px);
        }
        .hero-carousel .carousel-indicators {
            bottom: 40px;
            justify-content: flex-start;
            left: 8%;
            margin-left: 0;
        }
        .hero-carousel .carousel-indicators button {
            width: 40px;
            height: 2px;
            border-radius: 0;
            background: rgba(255,255,255,0.4);
            border: none;
            margin-right: 12px;
            transition: var(--transition);
        }
        .hero-carousel .carousel-indicators button.active {
            background: var(--gold);
            width: 60px;
        }
        .hero-carousel .carousel-control-prev,
        .hero-carousel .carousel-control-next {
            width: 60px;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        .hero-carousel .carousel-control-prev:hover,
        .hero-carousel .carousel-control-next:hover {
            opacity: 1;
        }
        .hero-scroll-hint {
            position: absolute;
            bottom: 40px;
            right: 8%;
            z-index: 3;
            color: rgba(255,255,255,0.6);
            font-family: var(--font-body);
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            writing-mode: vertical-rl;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: float 3s ease-in-out infinite;
        }
        .hero-scroll-hint::after {
            content: '';
            width: 1px;
            height: 60px;
            background: linear-gradient(to bottom, rgba(255,255,255,0.6), transparent);
        }

        /* ========== SECTION TITLES ========== */
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        .section-title h2 {
            font-size: 3rem;
            font-weight: 400;
            color: var(--charcoal);
            position: relative;
            display: inline-block;
            padding-bottom: 20px;
            margin-bottom: 15px;
        }
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--gold);
        }
        .section-title p {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 560px;
            margin: 0 auto;
            font-weight: 300;
        }

        /* ========== ROOM CARDS ========== */
        .room-card {
            border: none;
            border-radius: 0;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            height: 100%;
            background: #fff;
            position: relative;
        }
        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        .room-card .room-img-wrap {
            overflow: hidden;
            position: relative;
            height: 280px;
        }
        .room-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .room-card:hover img {
            transform: scale(1.08);
        }
        .room-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--gold);
            color: #fff;
            padding: 6px 16px;
            font-family: var(--font-body);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            z-index: 2;
        }
        .room-card .card-body {
            padding: 28px 24px;
            background: #fff;
        }
        .room-card .card-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--charcoal);
        }
        .room-card .card-text {
            font-family: var(--font-body);
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 18px;
        }
        .room-card .price {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 18px;
        }
        .room-card .price span {
            font-family: var(--font-body);
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
        .btn-luxury {
            display: inline-block;
            padding: 12px 28px;
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--charcoal);
            background: transparent;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
        }
        .btn-luxury:hover {
            background: var(--charcoal);
            color: #fff;
            border-color: var(--charcoal);
        }
        .btn-luxury-gold {
            display: inline-block;
            padding: 16px 40px;
            font-family: var(--font-body);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #fff;
            background: var(--gold);
            border: 1px solid var(--gold);
            border-radius: 0;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
        }
        .btn-luxury-gold:hover {
            background: transparent;
            color: var(--gold);
            border-color: var(--gold);
        }
        .btn-luxury-outline {
            display: inline-block;
            padding: 14px 36px;
            font-family: var(--font-body);
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--charcoal);
            background: transparent;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
        }
        .btn-luxury-outline:hover {
            background: var(--charcoal);
            color: #fff;
        }

        /* ========== ABOUT SECTION ========== */
        .about-section {
            background: var(--cream);
            position: relative;
        }
        .about-img-wrap {
            position: relative;
            overflow: hidden;
        }
        .about-img-wrap img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            transition: transform 1s ease;
        }
        .about-img-wrap:hover img {
            transform: scale(1.03);
        }
        .about-img-wrap::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: -15px;
            width: 120px;
            height: 120px;
            border: 2px solid var(--gold);
            z-index: -1;
        }
        .about-content h3 {
            font-family: var(--font-display);
            font-size: 2.4rem;
            font-weight: 400;
            margin-bottom: 24px;
            line-height: 1.3;
        }
        .about-content h3 em {
            font-style: italic;
            color: var(--gold);
        }
        .about-content p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 18px;
            font-weight: 300;
        }
        .about-list {
            list-style: none;
            padding: 0;
            margin-top: 24px;
        }
        .about-list li {
            padding: 10px 0;
            font-size: 0.9rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }
        .about-list li:last-child {
            border-bottom: none;
        }
        .about-list li i {
            color: var(--gold);
            font-size: 0.9rem;
        }

        /* ========== SERVICES SECTION ========== */
        .service-item {
            padding: 40px 24px;
            text-align: center;
            transition: var(--transition);
            background: transparent;
            border: 1px solid transparent;
        }
        .service-item:hover {
            background: #fff;
            box-shadow: var(--shadow-md);
            border-color: rgba(201,162,39,0.15);
        }
        .service-icon {
            width: 72px;
            height: 72px;
            background: transparent;
            border: 1.5px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--gold);
            font-size: 1.5rem;
            transition: var(--transition);
        }
        .service-item:hover .service-icon {
            background: var(--gold);
            color: #fff;
            transform: translateY(-4px);
        }
        .service-item h5 {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .service-item p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 300;
            line-height: 1.6;
        }

        /* ========== TESTIMONIALS ========== */
        .testimonial-card {
            background: #fff;
            padding: 36px 30px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            position: relative;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .testimonial-card::before {
            content: '\201C';
            font-family: var(--font-display);
            font-size: 5rem;
            color: var(--gold);
            opacity: 0.15;
            position: absolute;
            top: 10px;
            right: 20px;
            line-height: 1;
        }
        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
        }
        .testimonial-card h6 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }
        .testimonial-card small {
            color: var(--text-muted);
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .testimonial-card p {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.7;
            font-weight: 300;
            font-style: italic;
            margin-bottom: 16px;
        }
        .stars {
            color: var(--gold);
            font-size: 0.8rem;
            letter-spacing: 2px;
        }

        /* ========== BLOG ========== */
        .blog-card {
            border: none;
            border-radius: 0;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            height: 100%;
            background: #fff;
        }
        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .blog-card .blog-img-wrap {
            overflow: hidden;
            height: 240px;
        }
        .blog-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .blog-card:hover img {
            transform: scale(1.06);
        }
        .blog-card .card-body {
            padding: 24px;
        }
        .blog-card .blog-date {
            font-family: var(--font-body);
            font-size: 0.7rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 10px;
            display: block;
        }
        .blog-card h5 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .blog-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 300;
            line-height: 1.6;
            margin-bottom: 14px;
        }
        .blog-card .read-more {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--charcoal);
            text-decoration: none;
            border-bottom: 1px solid var(--charcoal);
            padding-bottom: 2px;
            transition: var(--transition);
        }
        .blog-card .read-more:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

        /* ========== CONTACT ========== */
        .contact-section {
            background: var(--cream-dark);
        }
        .contact-form {
            background: #fff;
            padding: 48px 40px;
            box-shadow: var(--shadow-sm);
            height: 100%;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .contact-form h4 {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 28px;
            position: relative;
            padding-bottom: 16px;
        }
        .contact-form h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--gold);
        }
        .contact-form .form-control {
            border-radius: 0;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: var(--transition);
            background: var(--cream);
            color: var(--charcoal);
        }
        .contact-form .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201,162,39,0.15);
            background: #fff;
        }
        .contact-form .form-control::placeholder {
            color: #aaa;
            font-weight: 300;
        }
        .btn-send {
            background: var(--charcoal);
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 16px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #fff;
            transition: var(--transition);
        }
        .btn-send:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
        }
        .contact-info-item {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            align-items: flex-start;
        }
        .contact-info-item i {
            color: var(--gold);
            font-size: 1.1rem;
            margin-top: 4px;
        }
        .contact-info-item h6 {
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 4px;
            color: var(--charcoal);
        }
        .contact-info-item p {
            font-size: 0.9rem;
            color: var(--text-muted);
            font-weight: 300;
            margin: 0;
        }
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,0.1);
            color: var(--charcoal);
            margin-right: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
            text-decoration: none;
        }
        .social-links a:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #fff;
        }

        /* ========== MODAL (Z-INDEX FIX) ========== */
        
        /* 1. Force the modal to sit ABOVE the fixed navbar (navbar z-index is 9999) */
        .modal {
            z-index: 99999 !important; 
        }
        /* 2. Force the backdrop to sit ABOVE the navbar but BELOW the modal */
        .modal-backdrop {
            z-index: 99990 !important; 
        }
        
        .modal-dialog {
            margin-top: 80px; /* Push modal down so it doesn't overlap the navbar */
        }
        @media (max-width: 576px) {
            .modal-dialog {
                margin-top: 70px;
            }
        }

        .modal-content {
            border-radius: 0;
            border: none;
            box-shadow: var(--shadow-lg);
        }
        .modal-header {
            background: var(--charcoal);
            color: #fff;
            border-radius: 0;
            border-bottom: none;
            padding: 20px 28px;
        }
        .modal-header .modal-title {
            font-family: var(--font-display);
            font-size: 1.3rem;
            font-weight: 600;
        }
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.9;
            padding: 0.75rem;
            margin: -0.75rem -0.75rem -0.75rem auto;
            width: 40px;
            height: 40px;
            background-size: 1rem;
            z-index: 100000; /* Ensure it sits on top */
        }
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }
        .modal-body {
            padding: 28px;
        }
        .modal-body img {
            border-radius: 0;
            width: 100%;
            margin-bottom: 0;
            height: 100%;
            object-fit: cover;
        }
        .modal-body h4 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
        }
        .modal-body .form-control {
            border-radius: 0;
            border: 1px solid #ddd;
            padding: 10px 14px;
            font-size: 0.85rem;
        }
        .modal-body .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201,162,39,0.15);
        }
        .modal-body .btn-primary {
            background: var(--charcoal);
            border: none;
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
        }
        .modal-body .btn-primary:hover {
            background: var(--gold);
        }
        .modal-body .btn-book {
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
        }
        .modal-body .btn-book:hover {
            opacity: 0.9;
        }

        /* ========== CONTACT SUBMISSION MODAL ========== */
        .contact-status-icon {
            font-size: 4.5rem;
            display: block;
            margin: 0 auto 20px;
            animation: contactFadeIn 0.5s ease;
        }
        .contact-status-icon.spinner {
            color: var(--gold);
            animation: spin 1s linear infinite;
        }
        .contact-status-icon.success {
            color: var(--primary);
            animation: contactPopIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .contact-status-icon.error {
            color: #dc3545;
            animation: contactShake 0.5s ease;
        }
        .contact-status-title {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 10px;
        }
        .contact-status-message {
            color: var(--text-muted);
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 300;
        }
        #contactSubmissionModal .modal-content {
            padding: 10px;
        }
        #contactSubmissionModal .btn-primary {
            background: var(--charcoal);
            border: none;
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
        }
        #contactSubmissionModal .btn-primary:hover {
            background: var(--gold);
        }
        #contactSubmissionModal .btn-outline-danger {
            border-radius: 0;
            font-family: var(--font-body);
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 12px;
        }

        /* ========== CHAT WIDGET ========== */
        .chat-widget-fixed {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            font-family: var(--font-body);
        }
        .chat-toggle-btn {
            background: var(--charcoal);
            color: #fff;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            animation: subtlePulse 3s infinite;
        }
        .chat-toggle-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
            background: var(--gold);
        }
        .chat-window {
            width: 380px;
            height: 580px;
            background: #fff;
            border-radius: 0;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: fixed;
            bottom: 94px;
            right: 0;
            z-index: 9998;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .chat-header {
            background: var(--charcoal);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-header-info i {
            font-size: 1.5rem;
            color: var(--gold);
        }
        .chat-header-info h4 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        .chat-header-info span {
            font-size: 0.65rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            opacity: 0.7;
            color: var(--gold-light);
        }
        .chat-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .chat-close-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: var(--cream);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 2px;
        }
        .bot-message, .user-message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeInUpChat 0.3s ease;
        }
        .user-message {
            flex-direction: row-reverse;
        }
        .bot-avatar, .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        .bot-avatar {
            background: var(--charcoal);
            color: var(--gold);
        }
        .user-avatar {
            background: var(--gold);
            color: #fff;
        }
        .message-bubble {
            max-width: 72%;
            padding: 12px 16px;
            border-radius: 0;
            font-size: 0.85rem;
            line-height: 1.5;
            word-wrap: break-word;
            font-weight: 300;
        }
        .bot-bubble {
            background: #fff;
            color: var(--charcoal);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 2px solid var(--gold);
        }
        .user-bubble {
            background: var(--charcoal);
            color: #fff;
        }
        .chat-input-area {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            background: var(--cream);
        }
        .suggestion-chip {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0;
            padding: 6px 12px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: var(--transition);
            color: var(--charcoal);
            font-weight: 400;
            letter-spacing: 0.03em;
        }
        .suggestion-chip:hover {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
        }
        .input-wrapper {
            display: flex;
            padding: 12px 16px;
            gap: 10px;
            align-items: center;
        }
        #chat-input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0;
            outline: none;
            font-size: 0.85rem;
            transition: var(--transition);
            font-family: var(--font-body);
            background: var(--cream);
        }
        #chat-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201,162,39,0.1);
            background: #fff;
        }
        #send-btn {
            background: var(--charcoal);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }
        #send-btn:hover {
            background: var(--gold);
            transform: scale(1.05);
        }
        .chat-footer {
            background: var(--cream);
            text-align: center;
            padding: 8px;
            font-size: 0.65rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.06);
            letter-spacing: 0.05em;
        }
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            padding: 12px 18px;
            border-radius: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 2px solid var(--gold);
        }
        .typing-indicator span {
            width: 7px;
            height: 7px;
            background: var(--gold);
            border-radius: 50%;
            display: inline-block;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0s; }

        /* ========== FOOTER SPACING ========== */
        .footer-spacer {
            height: 0;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 991px) {
            .hero-carousel h1 { font-size: 3.5rem; }
            .hero-carousel .carousel-caption { left: 5%; max-width: 500px; }
            .section-title h2 { font-size: 2.4rem; }
            .about-img-wrap img { height: 400px; }
            .about-img-wrap::after { display: none; }
            .contact-form { padding: 36px 28px; }
        }
        @media (max-width: 768px) {
            .hero-carousel { height: 100vh; }
            .hero-carousel .carousel-item { height: 100vh; }
            .hero-carousel h1 { font-size: 2.6rem; }
            .hero-carousel p { font-size: 1rem; }
            .hero-carousel .carousel-caption { left: 6%; max-width: 90%; }
            .hero-carousel .carousel-indicators { left: 6%; }
            .hero-scroll-hint { display: none; }
            .section-title h2 { font-size: 2rem; }
            .section-title p { font-size: 0.9rem; }
            .about-content h3 { font-size: 1.9rem; }
            .about-img-wrap img { height: 300px; }
            .contact-form { padding: 28px 20px; }
            .chat-window {
                width: calc(100vw - 32px);
                right: 16px;
                bottom: 84px;
                height: 540px;
            }
            .hero-carousel .carousel-control-prev,
            .hero-carousel .carousel-control-next { display: none; }
            .testimonial-card { padding: 28px 22px; }
            .blog-card .blog-img-wrap { height: 200px; }
        }
        @media (max-width: 576px) {
            .hero-carousel h1 { font-size: 2rem; }
            .hero-carousel .btn-hero { padding: 12px 28px; font-size: 0.75rem; }
            .section-title h2 { font-size: 1.7rem; }
            .room-card .room-img-wrap { height: 220px; }
            .service-item { padding: 28px 16px; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Carousel -->
<div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active" style="background-image: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&dpr=2');">
            <div class="carousel-caption">
                <span class="hero-label">Welcome to SC Hotel</span>
                <h1>Luxury &<br><em>Comfort</em> Redefined</h1>
                <p>Experience the art of fine hospitality in the heart of the city.</p>
                <a href="booking.php" class="btn-hero">Book Now <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('https://images.pexels.com/photos/189296/pexels-photo-189296.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&dpr=2');">
            <div class="carousel-caption">
                <span class="hero-label">Exquisite Suites</span>
                <h1>Experience<br><em>Elegance</em> & Serenity</h1>
                <p>Exquisite suites with breathtaking views and timeless design.</p>
                <a href="booking.php" class="btn-hero">Explore Rooms <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&dpr=2');">
            <div class="carousel-caption">
                <span class="hero-label">Unmatched Hospitality</span>
                <h1>Where Every<br><em>Moment</em> Matters</h1>
                <p>24/7 concierge and world-class amenities at your fingertips.</p>
                <a href="booking.php" class="btn-hero">Reserve Now <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
    <div class="hero-scroll-hint">Scroll</div>
</div>

<!-- Rooms Section -->
<div class="container my-5 py-5">
    <div class="section-title reveal">
        <span class="section-label">Accommodation</span>
        <h2>Our Luxury Rooms</h2>
        <p>Discover a sanctuary of elegance and comfort, thoughtfully designed for the discerning traveler.</p>
    </div>
    <div class="row g-4">
        <?php
        $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id LIMIT 3");
        $featured_rooms = $stmt->fetchAll();
        foreach ($featured_rooms as $index => $room):
        ?>
        <div class="col-md-6 col-lg-4 reveal" style="transition-delay: <?= $index * 0.15 ?>s;">
            <div class="card room-card h-100 position-relative">
                <div class="room-img-wrap">
                    <div class="room-badge"><?= htmlspecialchars($room['type']) ?></div>
                    <img src="assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($room['type']) ?>" onerror="this.src='assets/uploads/rooms/default_room.jpg'">
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= htmlspecialchars($room['type']) ?> Room</h5>
                    <p class="card-text flex-grow-1"><?= htmlspecialchars(substr($room['description'], 0, 100)) ?>...</p>
                    <p class="price">₱<?= number_format($room['price_per_night'], 2) ?> <span>/ night</span></p>
                    <button class="btn-luxury w-100 view-details" 
                            data-id="<?= $room['id'] ?>"
                            data-type="<?= htmlspecialchars($room['type']) ?>"
                            data-number="<?= htmlspecialchars($room['room_number']) ?>"
                            data-price="<?= $room['price_per_night'] ?>"
                            data-capacity="<?= $room['capacity'] ?>"
                            data-desc="<?= htmlspecialchars($room['description']) ?>"
                            data-img="assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>">
                        View Details
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-5 reveal">
        <a href="booking.php" class="btn-luxury-gold">Show All Rooms <i class="fas fa-arrow-right ms-2"></i></a>
    </div>
</div>

<!-- About Section -->
<div class="about-section py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-md-6 reveal-left">
                <div class="about-img-wrap">
                    <img src="https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&dpr=2" alt="Hotel lobby">
                </div>
            </div>
            <div class="col-md-6 reveal-right">
                <div class="about-content">
                    <span class="section-label">About Us</span>
                    <h3>Experience Luxury <em>Redefined</em></h3>
                    <p>SC Hotel offers a perfect blend of modern luxury and traditional hospitality. Located in the city center, we provide easy access to business districts, shopping, and entertainment.</p>
                    <p>Our 100+ elegantly designed rooms, world-class amenities, and dedicated staff ensure an unforgettable stay. Whether for business or leisure, we cater to your every need.</p>
                    <ul class="about-list">
                        <li><i class="fas fa-check"></i> 24/7 Concierge Service</li>
                        <li><i class="fas fa-check"></i> Free High-Speed Wi-Fi</li>
                        <li><i class="fas fa-check"></i> Rooftop Infinity Pool</li>
                        <li><i class="fas fa-check"></i> Fine Dining Restaurant</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<div class="container py-5 my-4">
    <div class="section-title reveal">
        <span class="section-label">What We Offer</span>
        <h2>Our Premium Services</h2>
        <p>Indulge in our curated collection of amenities designed to elevate your stay.</p>
    </div>
    <div class="row g-4">
        <div class="col-6 col-md-3 reveal" style="transition-delay: 0s;">
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-utensils"></i></div>
                <h5>Fine Dining</h5>
                <p>Exquisite local & international cuisine prepared by master chefs.</p>
            </div>
        </div>
        <div class="col-6 col-md-3 reveal" style="transition-delay: 0.1s;">
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-dumbbell"></i></div>
                <h5>Fitness Center</h5>
                <p>State-of-the-art gym with personal trainers available.</p>
            </div>
        </div>
        <div class="col-6 col-md-3 reveal" style="transition-delay: 0.2s;">
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-swimmer"></i></div>
                <h5>Swimming Pool</h5>
                <p>Heated indoor pool and relaxing jacuzzi.</p>
            </div>
        </div>
        <div class="col-6 col-md-3 reveal" style="transition-delay: 0.3s;">
            <div class="service-item">
                <div class="service-icon"><i class="fas fa-car"></i></div>
                <h5>Airport Transfer</h5>
                <p>Luxury shuttle service to/from the airport.</p>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="py-5" style="background: var(--cream-dark);">
    <div class="container py-4">
        <div class="section-title reveal">
            <span class="section-label">Testimonials</span>
            <h2>What Our Guests Say</h2>
            <p>Stories from those who have experienced the SC Hotel difference.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 reveal" style="transition-delay: 0s;">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" class="testimonial-avatar me-3">
                        <div>
                            <h6 class="mb-0">Sarah Johnson</h6>
                            <small>Business Traveler</small>
                        </div>
                    </div>
                    <p>"Absolutely amazing stay! The staff went above and beyond. The room was spotless and the view breathtaking. Will definitely return."</p>
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                </div>
            </div>
            <div class="col-md-4 reveal" style="transition-delay: 0.1s;">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" class="testimonial-avatar me-3">
                        <div>
                            <h6 class="mb-0">Michael Chen</h6>
                            <small>Family Vacation</small>
                        </div>
                    </div>
                    <p>"Perfect family getaway. The pool was a hit with the kids, and the breakfast buffet was delicious. Highly recommend!"</p>
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                </div>
            </div>
            <div class="col-md-4 reveal" style="transition-delay: 0.2s;">
                <div class="testimonial-card">
                    <div class="d-flex align-items-center mb-3">
                        <img src="https://randomuser.me/api/portraits/women/45.jpg" class="testimonial-avatar me-3">
                        <div>
                            <h6 class="mb-0">Emma Williams</h6>
                            <small>Honeymoon</small>
                        </div>
                    </div>
                    <p>"Magical experience! The suite was luxurious, and the staff surprised us with a complimentary upgrade. Truly 5-star service."</p>
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Blog Section -->
<div class="container py-5 my-4">
    <div class="section-title reveal">
        <span class="section-label">Journal</span>
        <h2>Latest from Our Blog</h2>
        <p>Insights, stories, and inspiration from the world of SC Hotel.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4 reveal" style="transition-delay: 0s;">
            <div class="card blog-card">
                <div class="blog-img-wrap">
                    <img src="https://images.pexels.com/photos/261102/pexels-photo-261102.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&dpr=2" class="card-img-top" alt="Travel tips">
                </div>
                <div class="card-body">
                    <span class="blog-date">March 15, 2025</span>
                    <h5>Top 10 Places to Visit in the City</h5>
                    <p>Discover hidden gems and must-see attractions near SC Hotel...</p>
                    <a href="#" class="read-more">Read More <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal" style="transition-delay: 0.1s;">
            <div class="card blog-card">
                <div class="blog-img-wrap">
                    <img src="https://images.pexels.com/photos/67468/pexels-photo-67468.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&dpr=2" class="card-img-top" alt="Dining">
                </div>
                <div class="card-body">
                    <span class="blog-date">March 10, 2025</span>
                    <h5>Exclusive Chef's Table Experience</h5>
                    <p>Join our culinary journey with seasonal tasting menus...</p>
                    <a href="#" class="read-more">Read More <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 reveal" style="transition-delay: 0.2s;">
            <div class="card blog-card">
                <div class="blog-img-wrap">
                    <img src="https://images.pexels.com/photos/2253821/pexels-photo-2253821.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&dpr=2" class="card-img-top" alt="Wellness">
                </div>
                <div class="card-body">
                    <span class="blog-date">March 5, 2025</span>
                    <h5>Wellness Retreat at SC Hotel</h5>
                    <p>Rejuvenate your mind and body with our spa packages...</p>
                    <a href="#" class="read-more">Read More <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Section -->
<div class="contact-section py-5">
    <div class="container py-4">
        <div class="section-title reveal">
            <span class="section-label">Contact</span>
            <h2>Get in Touch</h2>
            <p>We would be delighted to assist you with any inquiries or reservations.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 reveal-left">
                <div class="contact-form">
                    <h4>Send us a message</h4>
                    <form id="contactForm" novalidate enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="subject" class="form-control" placeholder="Subject">
                        </div>
                        <div class="mb-3">
                            <textarea name="message" rows="4" class="form-control" placeholder="Your Message" required></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                        </div>
                        <button type="submit" class="btn-send w-100" id="contactSubmitBtn">
                            Send Message <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-md-6 reveal-right">
                <div class="contact-form">
                    <h4>Contact Information</h4>
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h6>Address</h6>
                            <p>123 Luxury Avenue, Downtown City, 10001</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h6>Phone</h6>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h6>Email</h6>
                            <p>info@schotel.com</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h6 style="font-family: var(--font-body); font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 12px;">Follow Us</h6>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Room Details Modal -->
<div class="modal fade" id="roomDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-0">
                    <div class="col-md-6">
                        <img id="detailsImg" src="" class="img-fluid" style="width:100%; height:100%; min-height:300px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 p-4">
                        <h4 id="detailsType" class="mb-3"></h4>
                        <p class="mb-2"><strong style="font-weight:600;">Room Number:</strong> <span id="detailsNumber"></span></p>
                        <p class="mb-2"><strong style="font-weight:600;">Price:</strong> ₱<span id="detailsPrice"></span> / night</p>
                        <p class="mb-2"><strong style="font-weight:600;">Capacity:</strong> <span id="detailsCapacity"></span> persons</p>
                        <p class="mb-3"><strong style="font-weight:600;">Description:</strong> <span id="detailsDesc"></span></p>
                        <hr>
                        <h6 style="font-family: var(--font-display); font-weight:600; font-size:1.1rem;">Check Availability</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.75rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--text-muted);">Check-in</label>
                                <input type="date" id="availCheckIn" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:0.75rem; letter-spacing:0.05em; text-transform:uppercase; color:var(--text-muted);">Check-out</label>
                                <input type="date" id="availCheckOut" class="form-control">
                            </div>
                        </div>
                        <button id="checkAvailabilityBtn" class="btn btn-primary mt-3 w-100">Check Availability</button>
                        <div id="availabilityResult" class="mt-3 text-center"></div>
                        <a id="bookFromModalBtn" href="#" class="btn btn-book text-white mt-3 w-100" style="background: var(--gold); display: none;">Book Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Submission Modal -->
<div class="modal fade" id="contactSubmissionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4">
            <div class="modal-body">
                <div id="contactStatusIcon" class="contact-status-icon">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <h5 id="contactStatusTitle" class="contact-status-title">Sending your message...</h5>
                <p id="contactStatusMessage" class="contact-status-message">Please wait a moment.</p>
                <button id="contactStatusActionBtn" class="btn btn-primary w-100" style="display: none;">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Chat Widget -->
<div class="chat-widget-fixed">
    <div id="chat-toggle" class="chat-toggle-btn"><i class="fas fa-comment-dots"></i></div>
    <div id="chat-window" class="chat-window" style="display: none;">
        <div class="chat-header">
            <div class="chat-header-info"><i class="fas fa-concierge-bell"></i><div><h4>SC Concierge</h4><span>Online</span></div></div>
            <button id="chat-close" class="chat-close-btn"><i class="fas fa-times"></i></button>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="bot-message"><div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="message-bubble bot-bubble">Hello! I'm your SC Concierge. Ask me about room prices, availability, check-in/out times, or booking help.</div></div>
        </div>
        <div class="chat-input-area">
            <div class="suggestions" id="suggestions"></div>
            <div class="input-wrapper">
                <input type="text" id="chat-input" placeholder="Type your question..." autocomplete="off">
                <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <div class="chat-footer"><small>Powered by SC Hotel Concierge</small></div>
    </div>
</div>
<iframe id="chatIframe" style="display: none;"></iframe>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ============================================================
    // SCROLL REVEAL ANIMATIONS
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealElements.forEach(el => revealObserver.observe(el));
    });

    // ============================================================
    // ROOM DETAILS MODAL
    // ============================================================
    let currentRoomForBooking = null;

    document.querySelectorAll('.view-details').forEach(btn => {
        btn.addEventListener('click', function() {
            currentRoomForBooking = {
                id: this.dataset.id,
                type: this.dataset.type,
                number: this.dataset.number,
                price: this.dataset.price,
                capacity: this.dataset.capacity,
                desc: this.dataset.desc,
                img: this.dataset.img
            };
            document.getElementById('detailsType').innerText = currentRoomForBooking.type + ' Room';
            document.getElementById('detailsNumber').innerText = currentRoomForBooking.number;
            document.getElementById('detailsPrice').innerText = parseFloat(currentRoomForBooking.price).toFixed(2);
            document.getElementById('detailsCapacity').innerText = currentRoomForBooking.capacity;
            document.getElementById('detailsDesc').innerText = currentRoomForBooking.desc;
            document.getElementById('detailsImg').src = currentRoomForBooking.img;
            document.getElementById('availCheckIn').value = '';
            document.getElementById('availCheckOut').value = '';
            document.getElementById('availabilityResult').innerHTML = '';
            document.getElementById('bookFromModalBtn').style.display = 'none';
            new bootstrap.Modal(document.getElementById('roomDetailsModal')).show();
        });
    });

    document.getElementById('checkAvailabilityBtn').addEventListener('click', function() {
        let checkIn = document.getElementById('availCheckIn').value;
        let checkOut = document.getElementById('availCheckOut').value;
        if (!checkIn || !checkOut) {
            document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-warning" style="border-radius:0; font-size:0.85rem;">Please select both dates.</div>';
            return;
        }
        if (checkIn >= checkOut) {
            document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-warning" style="border-radius:0; font-size:0.85rem;">Check-out must be after check-in.</div>';
            return;
        }
        fetch('check_availability.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'room_id=' + currentRoomForBooking.id + '&check_in=' + checkIn + '&check_out=' + checkOut
        })
        .then(res => res.json())
        .then(data => {
            if (data.available) {
                document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-success" style="border-radius:0; font-size:0.85rem;">Room is available for selected dates!</div>';
                let bookLink = document.getElementById('bookFromModalBtn');
                bookLink.href = 'booking.php?room_id=' + currentRoomForBooking.id + '&check_in=' + checkIn + '&check_out=' + checkOut;
                bookLink.style.display = 'block';
            } else {
                document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">Room is already booked for these dates.</div>';
                document.getElementById('bookFromModalBtn').style.display = 'none';
            }
        });
    });

    // ============================================================
    // CONTACT FORM AJAX SUBMISSION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        const submitBtn = document.getElementById('contactSubmitBtn');
        const modal = new bootstrap.Modal(document.getElementById('contactSubmissionModal'), {
            backdrop: 'static',
            keyboard: false
        });
        const statusIcon = document.getElementById('contactStatusIcon');
        const statusTitle = document.getElementById('contactStatusTitle');
        const statusMessage = document.getElementById('contactStatusMessage');
        const actionBtn = document.getElementById('contactStatusActionBtn');

        function setLoadingState() {
            statusIcon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            statusIcon.className = 'contact-status-icon spinner';
            statusTitle.textContent = 'Sending your message...';
            statusMessage.textContent = 'Please wait a moment.';
            actionBtn.style.display = 'none';
            actionBtn.className = 'btn btn-primary w-100';
            actionBtn.textContent = 'Done';
            const newBtn = actionBtn.cloneNode(true);
            actionBtn.parentNode.replaceChild(newBtn, actionBtn);
            document.getElementById('contactStatusActionBtn');
        }

        function setSuccessState() {
            statusIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            statusIcon.className = 'contact-status-icon success';
            statusTitle.textContent = 'Message Sent Successfully!';
            statusMessage.textContent = 'Thank you for contacting SC Hotel. We\u2019ll get back to you as soon as possible.';
            actionBtn.style.display = 'block';
            actionBtn.className = 'btn btn-primary w-100';
            actionBtn.textContent = 'Done';
            actionBtn.onclick = function() {
                modal.hide();
                contactForm.reset();
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Message <i class="fas fa-paper-plane ms-2"></i>';
                contactForm.classList.remove('was-validated');
                contactForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            };
        }

        function setErrorState(errorMsg) {
            statusIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
            statusIcon.className = 'contact-status-icon error';
            statusTitle.textContent = 'Failed to Send Message';
            statusMessage.textContent = errorMsg || 'Unable to send your message. Please try again.';
            actionBtn.style.display = 'block';
            actionBtn.className = 'btn btn-outline-danger w-100';
            actionBtn.textContent = 'Try Again';
            actionBtn.onclick = function() {
                modal.hide();
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send Message <i class="fas fa-paper-plane ms-2"></i>';
                contactForm.classList.remove('was-validated');
                contactForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            };
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            contactForm.classList.remove('was-validated');
            contactForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            const name = this.querySelector('input[name="name"]');
            const email = this.querySelector('input[name="email"]');
            const message = this.querySelector('textarea[name="message"]');
            let valid = true;

            if (!name.value.trim()) {
                name.classList.add('is-invalid');
                valid = false;
            }
            if (!email.value.trim()) {
                email.classList.add('is-invalid');
                valid = false;
            } else if (!isValidEmail(email.value.trim())) {
                email.classList.add('is-invalid');
                valid = false;
            }
            if (!message.value.trim()) {
                message.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) {
                contactForm.classList.add('was-validated');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending... <i class="fas fa-spinner fa-spin ms-2"></i>';

            setLoadingState();
            modal.show();

            const formData = new FormData(contactForm);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    setSuccessState();
                } else {
                    setErrorState(data.message || 'Unable to send your message.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                setErrorState('Server error. Please check your connection and try again.');
            });
        });
    });

    // ============================================================
    // CHAT WIDGET
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        fetch('<?= SITE_URL ?>assistant.php?reset=1', { method: 'GET', cache: 'no-store' });

        var chatToggle = document.getElementById('chat-toggle');
        var chatWindow = document.getElementById('chat-window');
        var chatClose = document.getElementById('chat-close');
        var chatInput = document.getElementById('chat-input');
        var sendBtn = document.getElementById('send-btn');
        var chatMessages = document.getElementById('chat-messages');
        var chatIframe = document.getElementById('chatIframe');
        var suggestionsDiv = document.getElementById('suggestions');

        var typingIndicator = null;
        var pendingTimeout = null;

        function showTypingIndicator() {
            removeTypingIndicator();
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'bot-message';
            typingIndicator.innerHTML = '<div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="typing-indicator"><span></span><span></span><span></span></div>';
            chatMessages.appendChild(typingIndicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            if (typingIndicator) {
                typingIndicator.remove();
                typingIndicator = null;
            }
            if (pendingTimeout) {
                clearTimeout(pendingTimeout);
                pendingTimeout = null;
            }
        }

        function escapeHtml(str) {
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function appendMessage(text, isUser) {
            var div = document.createElement('div');
            div.className = isUser ? 'user-message' : 'bot-message';

            if (isUser) {
                div.innerHTML = '<div class="message-bubble user-bubble">' + escapeHtml(text) + '</div><div class="user-avatar"><i class="fas fa-user"></i></div>';
            } else {
                var escaped = escapeHtml(text);
                var urlRegex = /(https?:\/\/[^\s]+)/g;
                var linkedText = escaped.replace(urlRegex, function(url) {
                    return '<a href="' + url + '" target="_blank" style="color: var(--gold); text-decoration: underline;">' + url + '</a>';
                });
                div.innerHTML = '<div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="message-bubble bot-bubble">' + linkedText + '</div>';
            }
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function updateSuggestions(botMessage) {
            suggestionsDiv.innerHTML = '';
            var suggestions = [];
            var lowerMsg = botMessage.toLowerCase();
            
            if (lowerMsg.includes('which room type') || lowerMsg.includes('standard twin') || lowerMsg.includes('deluxe')) {
                suggestions = ['Standard Twin', 'Standard Double', 'Deluxe', 'Superior'];
            } 
            else if (lowerMsg.includes('check\u2011in date')) {
                suggestions = ['Tomorrow', 'Next Monday', 'August 1, 2026'];
            }
            else if (lowerMsg.includes('check\u2011out date')) {
                suggestions = ['Next day', 'August 2, 2026', 'August 5, 2026'];
            }
            else if (lowerMsg.includes('available') && (lowerMsg.includes('yes') || lowerMsg.includes('not'))) {
                suggestions = ['Book this room', 'Check another date', 'Show other rooms'];
            }
            else {
                suggestions = ['Show room prices', 'Book a room', 'Check availability', 'Hotel amenities', 'Contact info'];
            }
            
            suggestions = [...new Set(suggestions)].slice(0, 5);
            suggestions.forEach(sug => {
                var chip = document.createElement('div');
                chip.className = 'suggestion-chip';
                chip.textContent = sug;
                chip.addEventListener('click', function() {
                    chatInput.value = sug;
                    sendBtn.click();
                });
                suggestionsDiv.appendChild(chip);
            });
        }

        if (chatToggle) {
            chatToggle.addEventListener('click', function() {
                chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
                if (chatWindow.style.display === 'flex') chatInput.focus();
            });
        }
        if (chatClose) {
            chatClose.addEventListener('click', function() { chatWindow.style.display = 'none'; });
        }

        if (chatIframe) {
            chatIframe.addEventListener('load', function() {
                setTimeout(function() {
                    removeTypingIndicator();
                    try {
                        var iframeDoc = chatIframe.contentDocument || chatIframe.contentWindow.document;
                        var responseText = iframeDoc.body.innerText;
                        if (responseText && responseText.trim()) {
                            var data = JSON.parse(responseText);
                            if (data.reply) {
                                appendMessage(data.reply, false);
                                updateSuggestions(data.reply);
                            } else {
                                appendMessage('Sorry, I did not understand.', false);
                            }
                        } else {
                            appendMessage('No response from server.', false);
                        }
                    } catch(e) {
                        console.error("Iframe error:", e);
                        appendMessage('Sorry, I\'m having trouble. Please try again.', false);
                    }
                    if (chatIframe.contentDocument) chatIframe.contentDocument.body.innerHTML = '';
                }, 400);
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                var msg = chatInput.value.trim();
                if (msg === "") return;
                appendMessage(msg, true);
                chatInput.value = '';
                showTypingIndicator();
                suggestionsDiv.innerHTML = '';
                var url = '<?= SITE_URL ?>assistant.php?msg=' + encodeURIComponent(msg);
                chatIframe.src = url;
            });
        }
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') sendBtn.click();
            });
        }
        
        updateSuggestions('Hello');
    });
</script>
</body>
</html>