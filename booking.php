<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

if (isBanned()) {
    session_destroy();
    header('Location: login.php?error=banned');
    exit;
}

$available_rooms = [];
$all_rooms = [];
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$room_type = $_GET['type'] ?? '';
$price_max = $_GET['price_max'] ?? 0;
$error = '';

// ========== BOOKING SUBMISSION ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please refresh the page and try again.";
    } else {
        if (isBanned()) {
            $error = "Your account has been banned. You cannot make new bookings.";
        } else {
            $room_id = $_POST['room_id'];
            $check_in = $_POST['check_in'];
            $check_out = $_POST['check_out'];
            $check_in_time = $_POST['check_in_time'] ?? '14:00:00';
            $selfie_image = $_POST['selfie_image'] ?? '';
            
            $today = date('Y-m-d');
            if ($check_in < $today) {
                $error = "Check-in date cannot be in the past.";
            } elseif ($check_in >= $check_out) {
                $error = "Check-out must be after check-in.";
            } elseif (!function_exists('isRoomAvailable') || !isRoomAvailable($room_id, $check_in, $check_out)) {
                $error = "Room is not available for selected dates.";
            } else {
                $id_document = '';
                if (isset($_FILES['gov_id']) && $_FILES['gov_id']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                    $ext = strtolower(pathinfo($_FILES['gov_id']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $uploadDir = UPLOAD_DIR . 'id_docs/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $fileName = time() . '_' . $_SESSION['user_id'] . '.' . $ext;
                        if (move_uploaded_file($_FILES['gov_id']['tmp_name'], $uploadDir . $fileName)) {
                            $id_document = $fileName;
                        } else {
                            $error = "Failed to upload ID document.";
                        }
                    } else {
                        $error = "Invalid file type. Allowed: JPG, PNG, PDF.";
                    }
                } else {
                    $error = "Please upload a government-issued ID.";
                }
                
                if (empty($error)) {
                    $total_price = calculateTotalPrice($room_id, $check_in, $check_out);
                    $sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, check_in_time, total_price, id_document, selfie_image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $check_in_time, $total_price, $id_document, $selfie_image])) {
                        $_SESSION['success'] = "Booking request sent! Awaiting admin approval.";
                        header('Location: mybookings.php');
                        exit;
                    } else {
                        $error = "Booking failed. Please try again.";
                        error_log("Booking insert error: " . print_r($stmt->errorInfo(), true));
                    }
                }
            }
        }
    }
}

// ========== ROOM SEARCH ==========
if ($check_in && $check_out) {
    $available_rooms = getAvailableRooms($check_in, $check_out, $room_type, $price_max);
} else {
    $sql = "SELECT * FROM rooms WHERE 1=1";
    $params = [];
    if (!empty($room_type)) {
        $sql .= " AND type = ?";
        $params[] = $room_type;
    }
    if ($price_max > 0) {
        $sql .= " AND price_per_night <= ?";
        $params[] = $price_max;
    }
    $sql .= " ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_rooms = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room | SC Hotel</title>
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
            color: var(--charcoal);
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== HERO SECTION ========== */
        .booking-hero {
            position: relative;
            background: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&dpr=2') center/cover no-repeat;
            padding: 140px 0 100px;
            text-align: center;
            color: white;
            margin-bottom: 0;
        }
        .booking-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10,10,10,0.7), rgba(10,10,10,0.4));
        }
        .booking-hero .container {
            position: relative;
            z-index: 2;
        }
        .booking-hero h1 {
            font-family: var(--font-display);
            font-size: 3.8rem;
            font-weight: 400;
            text-shadow: 2px 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 15px;
        }
        .booking-hero h1 em {
            font-style: italic;
            color: var(--gold-light);
        }
        .booking-hero p {
            font-size: 1rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        /* ========== SEARCH CARD ========== */
        .search-card {
            background: #ffffff;
            padding: 35px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            box-shadow: var(--shadow-lg);
            border-top: 3px solid var(--gold);
        }
        .search-card .form-label {
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--charcoal);
            margin-bottom: 8px;
        }
        .search-card .form-control,
        .search-card .form-select {
            border-radius: 0;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: var(--transition);
            background: var(--cream);
        }
        .search-card .form-control:focus,
        .search-card .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.15);
            background: #fff;
        }
        .btn-search {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 12px 25px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            width: 100%;
            transition: var(--transition);
            height: 100%;
        }
        .btn-search:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.25);
        }

        /* ========== SECTION TITLES ========== */
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
        .section-title h2, .section-title h3 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            font-weight: 400;
            color: var(--charcoal);
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }
        .section-title h2::after, .section-title h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--gold);
        }

        /* ========== ROOM CARDS ========== */
        .room-card {
            border: none;
            border-radius: 0;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            background: #fff;
            height: 100%;
            position: relative;
            border: 1px solid rgba(0,0,0,0.03);
        }
        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        .room-img {
            height: 260px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .room-card:hover .room-img {
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
        }
        .room-card h5.card-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .room-card .card-text {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.6;
            font-weight: 300;
            margin-bottom: 18px;
        }
        .room-price {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--gold);
        }
        .room-price small {
            font-family: var(--font-body);
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 400;
            letter-spacing: 0.05em;
        }
        .availability-badge {
            display: inline-block;
            padding: 6px 14px;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border: 1px solid;
        }
        .availability-available {
            background: transparent;
            border-color: var(--gold);
            color: var(--gold);
        }
        .availability-not {
            background: transparent;
            border-color: #dc3545;
            color: #dc3545;
        }

        /* ========== BUTTONS ========== */
        .btn-book {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 14px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            width: 100%;
            transition: var(--transition);
        }
        .btn-book:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            border: 1px solid var(--charcoal);
            color: var(--charcoal);
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
        }
        .btn-outline-primary:hover {
            background: var(--charcoal);
            color: #ffffff;
        }

        /* ========== ALERTS & BANNERS ========== */
        .info-banner {
            background: #ffffff;
            border-left: 3px solid var(--gold);
            padding: 20px 25px;
            margin-bottom: 40px;
            font-size: 0.9rem;
            color: var(--charcoal);
            box-shadow: var(--shadow-sm);
        }
        .alert-danger {
            background-color: #fdf3f3;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 0;
            color: #721c24;
            font-size: 0.85rem;
        }
        .no-rooms {
            text-align: center;
            padding: 60px 30px;
            background: #fff;
            box-shadow: var(--shadow-sm);
        }
        .no-rooms i {
            color: var(--gold);
            opacity: 0.4;
            margin-bottom: 20px;
        }

        /* ========== MODALS (Z-INDEX & RESPONSIVE FIX) ========== */
        
        /* 1. Force the modal to sit ABOVE the fixed navbar (navbar z-index is 9999) */
        .modal {
            z-index: 10050 !important; 
        }
        /* 2. Force the backdrop to sit ABOVE the navbar but BELOW the modal */
        .modal-backdrop {
            z-index: 10040 !important; 
        }

        .modal-dialog {
            margin: 0.5rem auto;
            max-width: 95%;
        }
        @media (min-width: 576px) {
            .modal-dialog {
                margin: 1.75rem auto;
                max-width: 500px;
            }
        }
        @media (min-width: 992px) {
            .modal-lg {
                max-width: 800px;
            }
        }

        .modal-content {
            border-radius: 0;
            border: none;
            box-shadow: var(--shadow-lg);
            max-height: calc(100vh - 1rem);
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 576px) {
            .modal-content {
                max-height: calc(100vh - 3.5rem);
            }
        }

        .modal-header {
            background: var(--charcoal);
            color: #ffffff;
            padding: 15px 20px;
            border-bottom: none;
            flex-shrink: 0;
        }
        .modal-header .modal-title {
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* 3. Fix the X button size and make it easily clickable */
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
            padding: 0.75rem;
            margin: -0.75rem -0.75rem -0.75rem auto;
            width: 40px;
            height: 40px;
            background-size: 1rem;
            transition: var(--transition);
            z-index: 10051; /* Ensure it sits on top */
            cursor: pointer;
        }
        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 20px;
            background: var(--cream);
            overflow-y: auto;
            flex: 1 1 auto;
        }
        @media (min-width: 768px) {
            .modal-body {
                padding: 30px;
            }
        }

        .modal-body .form-control {
            border-radius: 0;
            border: 1px solid #ddd;
            padding: 12px 16px;
            background: #fff;
            font-size: 0.9rem;
        }
        .modal-body .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.15);
        }
        .modal-body .form-label {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .modal-footer {
            background: #fff;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
            flex-shrink: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }
        @media (max-width: 575px) {
            .modal-footer {
                flex-direction: column-reverse;
            }
            .modal-footer > * {
                width: 100%;
                margin: 0 !important;
            }
        }
        .modal-footer .btn-primary {
            background: var(--charcoal);
            border: 1px solid var(--charcoal);
            border-radius: 0;
            font-family: var(--font-body);
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 10px 24px;
            flex: 1;
        }
        .modal-footer .btn-primary:hover {
            background: var(--gold);
            border-color: var(--gold);
        }
        .modal-footer .btn-secondary {
            background: transparent;
            border: 1px solid #ccc;
            color: #666;
            border-radius: 0;
            font-family: var(--font-body);
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 10px 24px;
            flex: 1;
        }
        .modal-footer .btn-secondary:hover {
            background: #f0f0f0;
        }

        /* ========== MODAL ACTION BUTTONS (FIXED) ========== */
        /* A smaller, properly sized button for use inside modals */
        .btn-modal-action {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 12px 24px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            width: 100%;
            max-width: 280px; /* Prevents it from being huge */
            margin: 15px auto 0; /* Centers it with spacing */
            display: block;
            transition: var(--transition);
        }
        .btn-modal-action:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ========== STEP INDICATOR ========== */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #fff;
            padding: 12px 15px;
            border: 1px solid rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 10px;
        }
        @media (max-width: 575px) {
            .step-indicator {
                flex-direction: column;
            }
        }
        .step {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ddd;
            flex-shrink: 0;
        }
        .step.active {
            color: var(--charcoal);
            font-weight: 600;
        }
        .step.active::before {
            background: var(--gold);
        }

        /* Room Details Modal Specific Fixes */
        #roomDetailsModal .modal-body {
            padding: 0;
        }
        #roomDetailsModal .row {
            margin: 0;
        }
        #roomDetailsModal .col-md-6:first-child img {
            height: 250px !important;
            width: 100%;
            object-fit: cover;
        }
        @media (min-width: 768px) {
            #roomDetailsModal .col-md-6:first-child img {
                height: 100% !important;
                min-height: 400px;
            }
        }
        #roomDetailsModal .col-md-6:last-child {
            padding: 20px !important;
        }
        @media (min-width: 768px) {
            #roomDetailsModal .col-md-6:last-child {
                padding: 30px !important;
            }
        }

        /* Webcam styling */
        .webcam-container video {
            width: 100%;
            max-width: 320px;
            border: 2px solid var(--gold);
            background: #000;
            border-radius: 4px;
        }

        /* ========== CHAT WIDGET ========== */
        .chat-widget-fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: var(--font-body);
        }
        .chat-toggle-btn {
            background: var(--charcoal);
            color: #fff;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .chat-toggle-btn:hover {
            transform: scale(1.08);
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
            bottom: 90px;
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
        @keyframes fadeInUpChat {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.1);
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
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        @media (max-width: 768px) {
            .booking-hero h1 { font-size: 2.4rem; }
            .search-card { margin-top: -30px; padding: 25px; }
            .section-title h2, .section-title h3 { font-size: 2rem; }
            .chat-window { width: calc(100vw - 32px); right: 16px; bottom: 80px; height: 540px; }
            .chat-toggle-btn { width: 50px; height: 50px; font-size: 1.2rem; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="booking-hero">
    <div class="container">
        <h1>Find Your <em>Perfect</em> Stay</h1>
        <p>Luxury, comfort, and unforgettable experiences await</p>
    </div>
</div>

<div class="container mb-5">
    <!-- Search Card -->
    <div class="search-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Check-in</label>
                <input type="date" name="check_in" class="form-control" value="<?= htmlspecialchars($check_in) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Check-out</label>
                <input type="date" name="check_out" class="form-control" value="<?= htmlspecialchars($check_out) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Room Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Standard Twin" <?= $room_type == 'Standard Twin' ? 'selected' : '' ?>>Standard Twin</option>
                    <option value="Standard Double" <?= $room_type == 'Standard Double' ? 'selected' : '' ?>>Standard Double</option>
                    <option value="Deluxe" <?= $room_type == 'Deluxe' ? 'selected' : '' ?>>Deluxe</option>
                    <option value="Superior" <?= $room_type == 'Superior' ? 'selected' : '' ?>>Superior</option>
                    <option value="Suite" <?= $room_type == 'Suite' ? 'selected' : '' ?>>Suite</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">Max Price</label>
                <input type="number" name="price_max" class="form-control" placeholder="Any" value="<?= htmlspecialchars($price_max) ?>">
            </div>
            <div class="col-lg-2 col-12">
                <button type="submit" class="btn-search">Search</button>
            </div>
        </form>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem; padding-top: 1.1rem;"></button>
        </div>
    <?php endif; ?>

    <div class="mt-5">
        <?php if ($check_in && $check_out): ?>
            <div class="section-title mb-5">
                <span class="section-label">Search Results</span>
                <h3>Available Rooms</h3>
                <p class="text-muted mt-2" style="font-weight: 300;">
                    Showing availability for <strong><?= date('M d, Y', strtotime($check_in)) ?></strong> to <strong><?= date('M d, Y', strtotime($check_out)) ?></strong>
                </p>
            </div>
            <div class="row g-4">
                <?php if (count($available_rooms) > 0): ?>
                    <?php foreach ($available_rooms as $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="room-card position-relative">
                            <div class="room-badge"><?= htmlspecialchars($room['type']) ?></div>
                            <img src="assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>" class="room-img" alt="<?= $room['type'] ?>" onerror="this.src='assets/uploads/rooms/default_room.jpg'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0">Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                    <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user-friends me-1"></i> <?= $room['capacity'] ?></div>
                                </div>
                                <p class="card-text"><?= htmlspecialchars(substr($room['description'], 0, 100)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="room-price">₱<?= number_format($room['price_per_night'], 2) ?><small> / night</small></div>
                                    <div class="availability-badge availability-available"><i class="fas fa-check-circle me-1"></i> Available</div>
                                </div>
                                <button class="btn-book book-now-btn" 
                                        data-id="<?= $room['id'] ?>"
                                        data-type="<?= htmlspecialchars($room['type']) ?>"
                                        data-number="<?= htmlspecialchars($room['room_number']) ?>"
                                        data-price="<?= $room['price_per_night'] ?>"
                                        data-checkin="<?= htmlspecialchars($check_in) ?>"
                                        data-checkout="<?= htmlspecialchars($check_out) ?>">
                                    Book This Room
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="no-rooms">
                            <i class="fas fa-bed fa-4x"></i>
                            <h4 class="mb-3" style="font-family: var(--font-display);">No rooms available</h4>
                            <p class="text-muted">We couldn't find any available rooms for your selected dates. Please try different dates or room types.</p>
                            <a href="booking.php" class="btn-search mt-3 d-inline-block" style="width: auto; padding: 12px 30px;">Reset Search</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="info-banner">
                <i class="fas fa-info-circle me-2" style="color: var(--gold);"></i> 
                Showing all rooms. Use the search form above to check availability for specific dates.
            </div>
            
            <div class="section-title mb-5">
                <span class="section-label">Our Collection</span>
                <h3>All Rooms</h3>
                <p class="text-muted mt-2" style="font-weight: 300;"><?= count($all_rooms) ?> elegantly designed rooms</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($all_rooms as $room): 
                    $default_check_in = date('Y-m-d');
                    $default_check_out = date('Y-m-d', strtotime('+1 day'));
                    $is_available_now = function_exists('isRoomAvailable') && isRoomAvailable($room['id'], $default_check_in, $default_check_out);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="room-card position-relative">
                        <div class="room-badge"><?= htmlspecialchars($room['type']) ?></div>
                        <img src="assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>" class="room-img" alt="<?= $room['type'] ?>" onerror="this.src='assets/uploads/rooms/default_room.jpg'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                <div class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-user-friends me-1"></i> <?= $room['capacity'] ?></div>
                            </div>
                            <p class="card-text"><?= htmlspecialchars(substr($room['description'], 0, 100)) ?>...</p>
                            <div class="mb-4">
                                <div class="room-price">₱<?= number_format($room['price_per_night'], 2) ?><small> / night</small></div>
                                <div class="mt-2">
                                    <?php if ($is_available_now): ?>
                                        <span class="availability-badge availability-available"><i class="fas fa-check-circle me-1"></i> Available now</span>
                                    <?php else: ?>
                                        <span class="availability-badge availability-not"><i class="fas fa-times-circle me-1"></i> Not available today</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn-outline-primary w-100 mb-2 view-details-btn" 
                                    data-id="<?= $room['id'] ?>"
                                    data-type="<?= htmlspecialchars($room['type']) ?>"
                                    data-number="<?= htmlspecialchars($room['room_number']) ?>"
                                    data-price="<?= $room['price_per_night'] ?>"
                                    data-capacity="<?= $room['capacity'] ?>"
                                    data-desc="<?= htmlspecialchars($room['description']) ?>"
                                    data-img="assets/uploads/rooms/<?= htmlspecialchars($room['image']) ?>">
                                View Details
                            </button>
                            <button class="btn-book book-now-btn" 
                                    data-id="<?= $room['id'] ?>"
                                    data-type="<?= htmlspecialchars($room['type']) ?>"
                                    data-number="<?= htmlspecialchars($room['room_number']) ?>"
                                    data-price="<?= $room['price_per_night'] ?>">
                                Book Now
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
                        <img id="detailsImg" src="" class="img-fluid" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="col-md-6 p-4" style="background:#fff;">
                        <h4 id="detailsType" style="font-family: var(--font-display); font-size: 2rem; margin-bottom: 20px;"></h4>
                        <p class="mb-2"><strong style="font-weight:500; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em;">Room:</strong> <span id="detailsNumber"></span></p>
                        <p class="mb-2"><strong style="font-weight:500; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em;">Price:</strong> <span class="room-price" style="font-size:1.2rem;">₱<span id="detailsPrice"></span></span> / night</p>
                        <p class="mb-2"><strong style="font-weight:500; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em;">Capacity:</strong> <span id="detailsCapacity"></span> persons</p>
                        <p class="mb-4"><strong style="font-weight:500; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em;">Description:</strong> <span id="detailsDesc" style="font-weight:300; color:#666;"></span></p>
                        <hr style="opacity:0.1;">
                        <h6 style="font-family: var(--font-display); font-size: 1.2rem; margin-bottom: 15px;">Check Availability</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Check-in</label>
                                <input type="date" id="availCheckIn" class="form-control" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Check-out</label>
                                <input type="date" id="availCheckOut" class="form-control" min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <!-- FIXED: Changed from btn-search to btn-modal-action -->
                        <button id="checkAvailabilityBtn" class="btn-modal-action">Check Availability</button>
                        
                        <div id="availabilityResult" class="mt-3 text-center"></div>
                        <!-- FIXED: Changed from btn-book to btn-modal-action -->
                        <button id="bookFromModalBtn" class="btn-modal-action" style="display: none; background: var(--gold); border-color: var(--gold);">Book Now</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Your Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="step-indicator">
                    <div class="step active" data-step="1">Step 1: Booking Details</div>
                    <div class="step" data-step="2">Step 2: Face Verification</div>
                </div>

                <!-- Step 1 -->
                <div id="step1">
                    <form id="bookingForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="room_id" id="bookRoomId">
                        
                        <div class="mb-3">
                            <label class="form-label">Selected Room</label>
                            <input type="text" id="bookRoomInfo" class="form-control" readonly style="background:#f9f9f9;">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-in Date</label>
                                <input type="date" name="check_in" id="bookCheckIn" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-out Date</label>
                                <input type="date" name="check_out" id="bookCheckOut" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Check-in Time</label>
                            <input type="time" name="check_in_time" id="bookCheckInTime" class="form-control" value="14:00" required>
                            <small class="text-muted" style="font-size:0.7rem;">Standard check-in is 2:00 PM. You may request a different time.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Government-Issued ID (JPG, PNG, PDF)</label>
                            <input type="file" name="gov_id" id="gov_id" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small class="text-muted" style="font-size:0.7rem;">Required for verification.</small>
                        </div>
                        
                        <div id="bookingError" class="alert alert-danger d-none"></div>
                    </form>
                </div>

                <!-- Step 2 -->
                <div id="step2" style="display: none;">
                    <div class="text-center mb-3">
                        <p class="mb-4" style="font-weight:300; color:#555;">Please take a clear selfie to match with your uploaded ID.</p>
                        <div class="webcam-container d-flex flex-column align-items-center">
                            <video id="webcam" autoplay playsinline width="320" height="240" style="background:#000; object-fit: cover;"></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                            <div class="mt-3">
                                <button type="button" id="startCameraBtn" class="btn-outline-primary" style="padding: 8px 20px;">Start Camera</button>
                                <button type="button" id="captureBtn" class="btn-search" style="width:auto; padding: 8px 20px;" disabled>Take Selfie & Verify</button>
                            </div>
                        </div>
                        <div id="verificationResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="prevBtn" class="btn btn-secondary" style="display: none;">Previous</button>
                <button type="button" id="nextBtn" class="btn btn-primary">Next Step</button>
                <button type="submit" name="book_room" id="submitBtn" class="btn btn-primary" style="display: none; background: var(--gold); border-color: var(--gold);" form="bookingForm">Confirm Booking</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
// ========== ROOM DETAILS & BOOKING BUTTONS ==========
let currentRoomDetails = null;

document.querySelectorAll('.view-details-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentRoomDetails = {
            id: this.dataset.id, type: this.dataset.type, number: this.dataset.number,
            price: this.dataset.price, capacity: this.dataset.capacity,
            desc: this.dataset.desc, img: this.dataset.img
        };
        document.getElementById('detailsType').innerText = currentRoomDetails.type + ' Room';
        document.getElementById('detailsNumber').innerText = currentRoomDetails.number;
        document.getElementById('detailsPrice').innerText = parseFloat(currentRoomDetails.price).toFixed(2);
        document.getElementById('detailsCapacity').innerText = currentRoomDetails.capacity;
        document.getElementById('detailsDesc').innerText = currentRoomDetails.desc;
        document.getElementById('detailsImg').src = currentRoomDetails.img;
        document.getElementById('availCheckIn').value = '';
        document.getElementById('availCheckOut').value = '';
        document.getElementById('availabilityResult').innerHTML = '';
        document.getElementById('bookFromModalBtn').style.display = 'none';
        new bootstrap.Modal(document.getElementById('roomDetailsModal')).show();
    });
});

document.getElementById('checkAvailabilityBtn')?.addEventListener('click', function() {
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
    if (checkIn < '<?= date('Y-m-d') ?>') {
        document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-warning" style="border-radius:0; font-size:0.85rem;">Check-in date cannot be in the past.</div>';
        return;
    }
    fetch('check_availability.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'room_id=' + currentRoomDetails.id + '&check_in=' + checkIn + '&check_out=' + checkOut
    })
    .then(res => res.json())
    .then(data => {
        if (data.available) {
            document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-success" style="border-radius:0; font-size:0.85rem;">Room is available!</div>';
            let bookBtn = document.getElementById('bookFromModalBtn');
            bookBtn.style.display = 'block';
            bookBtn.replaceWith(bookBtn.cloneNode(true));
            let newBtn = document.getElementById('bookFromModalBtn');
            newBtn.addEventListener('click', function() {
                document.getElementById('bookRoomId').value = currentRoomDetails.id;
                document.getElementById('bookRoomInfo').value = currentRoomDetails.type + ' Room (#' + currentRoomDetails.number + ') - ₱' + parseFloat(currentRoomDetails.price).toFixed(2) + '/night';
                document.getElementById('bookCheckIn').value = checkIn;
                document.getElementById('bookCheckOut').value = checkOut;
                bootstrap.Modal.getInstance(document.getElementById('roomDetailsModal')).hide();
                // Reset steps
                document.getElementById('step1').style.display = 'block';
                document.getElementById('step2').style.display = 'none';
                document.getElementById('prevBtn').style.display = 'none';
                document.getElementById('nextBtn').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
                document.querySelector('.step[data-step="1"]').classList.add('active');
                document.querySelector('.step[data-step="2"]').classList.remove('active');
                new bootstrap.Modal(document.getElementById('bookingModal')).show();
            });
        } else {
            document.getElementById('availabilityResult').innerHTML = '<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">Room is already booked for these dates.</div>';
            document.getElementById('bookFromModalBtn').style.display = 'none';
        }
    });
});

document.querySelectorAll('.book-now-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('bookRoomId').value = this.dataset.id;
        document.getElementById('bookRoomInfo').value = this.dataset.type + ' Room (#' + this.dataset.number + ') - ₱' + parseFloat(this.dataset.price).toFixed(2) + '/night';
        if (this.dataset.checkin && this.dataset.checkout) {
            document.getElementById('bookCheckIn').value = this.dataset.checkin;
            document.getElementById('bookCheckOut').value = this.dataset.checkout;
        } else {
            document.getElementById('bookCheckIn').value = '';
            document.getElementById('bookCheckOut').value = '';
        }
        document.getElementById('bookingError').classList.add('d-none');
        // Reset steps
        document.getElementById('step1').style.display = 'block';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('prevBtn').style.display = 'none';
        document.getElementById('nextBtn').style.display = 'inline-block';
        document.getElementById('submitBtn').style.display = 'none';
        document.querySelector('.step[data-step="1"]').classList.add('active');
        document.querySelector('.step[data-step="2"]').classList.remove('active');
        new bootstrap.Modal(document.getElementById('bookingModal')).show();
    });
});

// ========== FACE VERIFICATION LOGIC ==========
let stream = null;
const video = document.getElementById('webcam');
const canvas = document.getElementById('canvas');
const startCameraBtn = document.getElementById('startCameraBtn');
const captureBtn = document.getElementById('captureBtn');
const verificationResultDiv = document.getElementById('verificationResult');
const nextBtn = document.getElementById('nextBtn');
const prevBtn = document.getElementById('prevBtn');
const submitBtn = document.getElementById('submitBtn');
const bookingForm = document.getElementById('bookingForm');

async function startCamera() {
    try {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
        startCameraBtn.disabled = true;
        captureBtn.disabled = false;
    } catch (err) {
        console.error("Camera error:", err);
        verificationResultDiv.innerHTML = '<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">Could not access camera. Please allow permissions.</div>';
    }
}
startCameraBtn.addEventListener('click', startCamera);

function readFileAsBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

captureBtn.addEventListener('click', async function() {
    const idFile = document.getElementById('gov_id').files[0];
    if (!idFile) {
        alert('Please upload your ID first (Step 1).');
        return;
    }
    if (!stream) {
        alert('Please start the camera first.');
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const selfieBase64 = canvas.toDataURL('image/jpeg');

    verificationResultDiv.innerHTML = '<div class="spinner-border text-warning" role="status"></div> <span style="font-size:0.85rem; margin-left:10px;">Verifying...</span>';
    captureBtn.disabled = true;

    try {
        const idBase64 = await readFileAsBase64(idFile);
        const formData = new FormData();
        formData.append('id_image_base64', idBase64);
        formData.append('selfie_base64', selfieBase64);

        const response = await fetch('verify_face.php', { method: 'POST', body: formData });
        const rawText = await response.text();
        console.log("Raw response:", rawText);
        const jsonStart = rawText.indexOf('{');
        const jsonEnd = rawText.lastIndexOf('}') + 1;
        if (jsonStart === -1 || jsonEnd === 0) throw new Error('No JSON found');
        const jsonStr = rawText.substring(jsonStart, jsonEnd);
        const result = JSON.parse(jsonStr);

        if (result.success && result.is_match) {
            verificationResultDiv.innerHTML = `<div class="alert alert-success" style="border-radius:0; font-size:0.85rem;">${result.message || 'Face match successful!'}</div>`;

            if (stream) stream.getTracks().forEach(track => track.stop());

            // Upload selfie to server (for record)
            const selfieBlob = await (await fetch(selfieBase64)).blob();
            const uploadForm = new FormData();
            uploadForm.append('selfie', selfieBlob, 'selfie.jpg');

            const uploadRes = await fetch('upload_selfie.php', {
                method: 'POST',
                body: uploadForm
            });

            const uploadRaw = await uploadRes.text();
            const uStart = uploadRaw.indexOf('{');
            const uEnd = uploadRaw.lastIndexOf('}') + 1;

            if (uStart !== -1 && uEnd > uStart) {
                const uploadResult = JSON.parse(uploadRaw.substring(uStart, uEnd));
                if (uploadResult.success) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'selfie_image';
                    hidden.value = uploadResult.filename;
                    bookingForm.appendChild(hidden);
                    console.log("Added selfie_image:", uploadResult.filename);
                }
            }

            // Show confirm booking button
            submitBtn.style.display = 'inline-block';
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
            captureBtn.disabled = true;
            startCameraBtn.disabled = true;

        } else if (result.success && !result.is_match) {
            verificationResultDiv.innerHTML = `<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">${result.message || 'Face not matched. Please try again.'}</div>`;
            captureBtn.disabled = false;
        } else {
            verificationResultDiv.innerHTML = `<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">${result.error || 'Verification failed.'}</div>`;
            captureBtn.disabled = false;
        }
    } catch (err) {
        console.error("Verification error:", err);
        verificationResultDiv.innerHTML = '<div class="alert alert-danger" style="border-radius:0; font-size:0.85rem;">Network error. Please try again.</div>';
        captureBtn.disabled = false;
    }
});

// Step navigation
nextBtn.addEventListener('click', function() {
    const roomId = document.getElementById('bookRoomId').value;
    const checkIn = document.getElementById('bookCheckIn').value;
    const checkOut = document.getElementById('bookCheckOut').value;
    const idFile = document.getElementById('gov_id').files[0];
    if (!roomId || !checkIn || !checkOut || !idFile) {
        alert('Please fill in all booking details and upload your ID.');
        return;
    }
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    prevBtn.style.display = 'inline-block';
    nextBtn.style.display = 'none';
    document.querySelector('.step[data-step="1"]').classList.remove('active');
    document.querySelector('.step[data-step="2"]').classList.add('active');
    startCamera();
});

prevBtn.addEventListener('click', function() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    prevBtn.style.display = 'none';
    nextBtn.style.display = 'inline-block';
    submitBtn.style.display = 'none';
    document.querySelector('.step[data-step="2"]').classList.remove('active');
    document.querySelector('.step[data-step="1"]').classList.add('active');
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
    verificationResultDiv.innerHTML = '';
    captureBtn.disabled = true;
    startCameraBtn.disabled = false;
});

// ========== FORM VALIDATION ==========
document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
    let checkIn = document.getElementById('bookCheckIn').value;
    let checkOut = document.getElementById('bookCheckOut').value;
    let today = '<?= date('Y-m-d') ?>';
    if (!checkIn || !checkOut) {
        e.preventDefault();
        document.getElementById('bookingError').innerHTML = 'Please select both dates.';
        document.getElementById('bookingError').classList.remove('d-none');
        return;
    }
    if (checkIn < today) {
        e.preventDefault();
        document.getElementById('bookingError').innerHTML = 'Check-in cannot be in the past.';
        document.getElementById('bookingError').classList.remove('d-none');
        return;
    }
    if (checkIn >= checkOut) {
        e.preventDefault();
        document.getElementById('bookingError').innerHTML = 'Check-out must be after check-in.';
        document.getElementById('bookingError').classList.remove('d-none');
        return;
    }
    let checkTime = document.getElementById('bookCheckInTime').value;
    if (!checkTime) {
        e.preventDefault();
        document.getElementById('bookingError').innerHTML = 'Please select a check-in time.';
        document.getElementById('bookingError').classList.remove('d-none');
    }
});

// Auto-open from chatbot link
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get('room_id');
    const checkIn = urlParams.get('check_in');
    const checkOut = urlParams.get('check_out');

    if (roomId && checkIn && checkOut) {
        fetch('get_room_details.php?room_id=' + roomId)
            .then(response => response.json())
            .then(room => {
                if (room) {
                    document.getElementById('bookRoomId').value = room.id;
                    document.getElementById('bookRoomInfo').value = room.type + ' Room (#' + room.room_number + ') - ₱' + parseFloat(room.price_per_night).toFixed(2) + '/night';
                    document.getElementById('bookCheckIn').value = checkIn;
                    document.getElementById('bookCheckOut').value = checkOut;
                    document.getElementById('bookingError').classList.add('d-none');
                    // Reset to step 1
                    document.getElementById('step1').style.display = 'block';
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('prevBtn').style.display = 'none';
                    document.getElementById('nextBtn').style.display = 'inline-block';
                    document.getElementById('submitBtn').style.display = 'none';
                    document.querySelector('.step[data-step="1"]').classList.add('active');
                    document.querySelector('.step[data-step="2"]').classList.remove('active');
                    new bootstrap.Modal(document.getElementById('bookingModal')).show();
                }
            });
    }
});

// ========== CHAT WIDGET ==========
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
            suggestions = ['Standard Twin', 'Standard Double', 'Deluxe', 'Superior',];
        } 
        else if (lowerMsg.includes('check‑in date')) {
            suggestions = ['Tomorrow', 'Next Monday', 'August 1, 2026'];
        }
        else if (lowerMsg.includes('check‑out date')) {
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