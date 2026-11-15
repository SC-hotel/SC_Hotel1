<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

if (isBanned()) {
    session_destroy();
    header('Location: login.php?error=banned');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle POST cancellation (CSRF protected)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die('Security validation failed. Please refresh the page.');
    }
    $booking_id = $_POST['booking_id'];
    $status_filter = $_POST['status_filter'];
    $page = $_POST['page'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$booking_id, $user_id]);
    header("Location: mybookings.php?status=" . urlencode($status_filter) . "&page=" . $page);
    exit;
}

// Get status filter (default: all)
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$valid_statuses = ['all', 'pending', 'approved', 'cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Pagination: 6 bookings per page
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the WHERE clause for status filter
$status_condition = "";
$params = [$user_id];
if ($status_filter != 'all') {
    $status_condition = " AND b.status = ?";
    $params[] = $status_filter;
}

// Count total bookings for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b WHERE b.user_id = ? $status_condition");
$countStmt->execute($params);
$totalBookings = $countStmt->fetchColumn();
$totalPages = ceil($totalBookings / $limit);

// Fetch bookings with limit and offset
$sql = "
    SELECT b.*, r.type, r.room_number, r.image 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? $status_condition 
    ORDER BY b.booking_date DESC 
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$bindIndex = 1;
$stmt->bindValue($bindIndex++, $user_id, PDO::PARAM_INT);
if ($status_filter != 'all') {
    $stmt->bindValue($bindIndex++, $status_filter, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll();

// Get counts for each status (for badges)
$counts = [];
$statuses = ['pending', 'approved', 'cancelled'];
foreach ($statuses as $st) {
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = ?");
    $cntStmt->execute([$user_id, $st]);
    $counts[$st] = $cntStmt->fetchColumn();
}
$totalCount = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | SC Hotel</title>
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
            color: var(--charcoal);
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== HERO SECTION ========== */
        .page-header {
            position: relative;
            background: url('https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg?auto=compress&cs=tinysrgb&w=1920&h=1080&dpr=2') center/cover no-repeat;
            padding: 140px 0 100px;
            text-align: center;
            color: white;
        }
        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10,10,10,0.7), rgba(10,10,10,0.4));
        }
        .page-header .container {
            position: relative;
            z-index: 2;
        }
        .page-header h1 {
            font-family: var(--font-display);
            font-size: 3.8rem;
            font-weight: 400;
            text-shadow: 2px 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 15px;
        }
        .page-header p {
            font-size: 1rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.9;
        }

        /* ========== FILTER TABS ========== */
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        .filter-tab {
            background: #ffffff;
            border: 1px solid rgba(0,0,0,0.08);
            padding: 12px 24px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--charcoal);
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .filter-tab i {
            font-size: 0.85rem;
            color: var(--gold);
            transition: var(--transition);
        }
        .filter-tab.active {
            background: var(--charcoal);
            border-color: var(--charcoal);
            color: #ffffff;
        }
        .filter-tab.active i {
            color: var(--gold);
        }
        .filter-tab:hover:not(.active) {
            background: #ffffff;
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .badge-count {
            background: var(--cream-dark);
            color: var(--charcoal);
            padding: 2px 8px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-left: 4px;
        }
        .filter-tab.active .badge-count {
            background: rgba(255,255,255,0.15);
            color: var(--gold-light);
        }

        /* ========== BOOKING CARDS ========== */
        .booking-card {
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.1);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            height: 100%;
            position: relative;
        }
        .booking-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(201, 162, 39, 0.3);
        }
        .booking-img-wrapper {
            overflow: hidden;
            height: 220px;
            position: relative;
        }
        .booking-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .booking-card:hover .booking-img {
            transform: scale(1.08);
        }
        .booking-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 16px;
            font-family: var(--font-body);
            font-weight: 600;
            font-size: 0.7rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            z-index: 2;
            background: #ffffff;
            color: var(--charcoal);
        }
        .status-pending { border-left: 3px solid #f39c12; }
        .status-approved { border-left: 3px solid var(--gold); }
        .status-cancelled { border-left: 3px solid #dc3545; }
        
        .booking-body {
            padding: 28px;
        }
        .booking-title {
            font-family: var(--font-display);
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--charcoal);
            margin-bottom: 5px;
        }
        .booking-room-num {
            font-family: var(--font-body);
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .booking-info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.85rem;
            color: #555;
            font-weight: 300;
        }
        .booking-info-row i {
            color: var(--gold);
            width: 16px;
            text-align: center;
        }
        .booking-info-row strong {
            font-weight: 500;
            color: var(--charcoal);
        }
        .booking-total {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gold);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        /* ========== BUTTONS ========== */
        .btn-cancel {
            background: transparent;
            color: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
            width: 100%;
        }
        .btn-cancel:hover {
            background: #dc3545;
            color: #ffffff;
        }
        .btn-disabled {
            background: var(--cream-dark);
            color: var(--text-muted);
            border: 1px solid var(--cream-dark);
            border-radius: 0;
            padding: 12px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            width: 100%;
            cursor: not-allowed;
        }
        .btn-primary-custom {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 14px 32px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.78rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary-custom:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 80px 30px;
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: var(--shadow-sm);
        }
        .empty-state i {
            color: var(--gold);
            opacity: 0.4;
            margin-bottom: 24px;
        }
        .empty-state h3 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 400;
            margin-bottom: 12px;
        }

        /* ========== PAGINATION ========== */
        .pagination {
            gap: 6px;
        }
        .pagination .page-link {
            background: #ffffff;
            color: var(--charcoal);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 0 !important;
            padding: 10px 18px;
            font-family: var(--font-body);
            font-size: 0.8rem;
            font-weight: 500;
            transition: var(--transition);
            margin: 0;
        }
        .pagination .page-link:hover {
            background: var(--cream-dark);
            color: var(--charcoal);
            border-color: var(--gold);
        }
        .pagination .page-item.active .page-link {
            background: var(--charcoal);
            border-color: var(--charcoal);
            color: #ffffff;
        }

        /* ========== MODAL ========== */
        .modal-content {
            border-radius: 0;
            border: none;
            box-shadow: var(--shadow-lg);
        }
        .modal-header {
            background: var(--charcoal);
            color: #ffffff;
            padding: 20px 28px;
            border-bottom: none;
        }
        .modal-header .modal-title {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 600;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }
        .modal-body {
            padding: 30px;
            background: var(--cream);
        }
        .modal-body .alert {
            background: #ffffff;
            border-left: 3px solid var(--gold);
            border-radius: 0;
            font-size: 0.85rem;
            color: #555;
        }
        .modal-body .alert strong {
            color: var(--charcoal);
            font-weight: 500;
        }
        .modal-footer {
            background: #ffffff;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 20px 28px;
        }
        .modal-footer .btn-danger {
            background: #dc3545;
            border: 1px solid #dc3545;
            border-radius: 0;
            font-family: var(--font-body);
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 10px 24px;
        }
        .modal-footer .btn-danger:hover {
            background: #c0392b;
            border-color: #c0392b;
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
        }
        .modal-footer .btn-secondary:hover {
            background: #f0f0f0;
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
            .page-header h1 { font-size: 2.4rem; }
            .page-header { padding: 100px 0 70px; }
            .filter-tab { padding: 10px 16px; font-size: 0.7rem; }
            .chat-window { width: calc(100vw - 32px); right: 16px; bottom: 84px; height: 540px; }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- Hero Section -->
<div class="page-header">
    <div class="container">
        <h1>My <em>Bookings</em></h1>
        <p>View and manage your reservation history</p>
    </div>
</div>

<div class="container mb-5" style="margin-top: 40px;">
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?status=all" class="filter-tab <?= $status_filter == 'all' ? 'active' : '' ?>">
            <i class="fas fa-list"></i> All <span class="badge-count"><?= $totalCount ?></span>
        </a>
        <a href="?status=pending" class="filter-tab <?= $status_filter == 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Pending <span class="badge-count"><?= $counts['pending'] ?></span>
        </a>
        <a href="?status=approved" class="filter-tab <?= $status_filter == 'approved' ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Approved <span class="badge-count"><?= $counts['approved'] ?></span>
        </a>
        <a href="?status=cancelled" class="filter-tab <?= $status_filter == 'cancelled' ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i> Cancelled <span class="badge-count"><?= $counts['cancelled'] ?></span>
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times fa-4x"></i>
            <h3>No bookings found</h3>
            <p class="text-muted mb-4">
                <?php if ($status_filter == 'pending'): ?>
                    You have no pending bookings.
                <?php elseif ($status_filter == 'approved'): ?>
                    You have no approved bookings yet.
                <?php elseif ($status_filter == 'cancelled'): ?>
                    You have no cancelled bookings.
                <?php else: ?>
                    Start your journey with us and book your first stay.
                <?php endif; ?>
            </p>
            <?php if ($status_filter != 'cancelled'): ?>
                <a href="booking.php" class="btn-primary-custom mt-3">Book a Room <i class="fas fa-arrow-right ms-2"></i></a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($bookings as $booking): ?>
            <div class="col-md-6 col-lg-4">
                <div class="booking-card">
                    <div class="booking-img-wrapper">
                        <img src="assets/uploads/rooms/<?= htmlspecialchars($booking['image']) ?>" class="booking-img" alt="<?= $booking['type'] ?>" onerror="this.src='assets/uploads/rooms/default_room.jpg'">
                    </div>
                    <div class="booking-status status-<?= $booking['status'] ?>">
                        <?php if($booking['status'] == 'approved'): ?>
                            <i class="fas fa-check-circle" style="color: var(--gold);"></i>
                        <?php elseif($booking['status'] == 'cancelled'): ?>
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                        <?php else: ?>
                            <i class="fas fa-clock" style="color: #f39c12;"></i>
                        <?php endif; ?>
                        <?= ucfirst($booking['status']) ?>
                    </div>
                    <div class="booking-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <h5 class="booking-title"><?= htmlspecialchars($booking['type']) ?></h5>
                                <span class="booking-room-num">Room #<?= $booking['room_number'] ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="booking-info-row">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?= date('M d, Y', strtotime($booking['check_in'])) ?> &rarr; <?= date('M d, Y', strtotime($booking['check_out'])) ?></span>
                            </div>
                            <div class="booking-info-row" style="border-bottom: none;">
                                <i class="fas fa-receipt"></i>
                                <span>Booked on <?= date('M d, Y', strtotime($booking['booking_date'])) ?></span>
                            </div>
                        </div>

                        <div class="booking-total">
                            ₱<?= number_format($booking['total_price'], 2) ?> <small style="font-family: var(--font-body); font-size: 0.75rem; color: var(--text-muted); font-weight: 400;">total</small>
                        </div>
                        
                        <div class="d-grid">
                            <?php if ($booking['status'] == 'pending'): ?>
                                <button class="btn-cancel cancel-btn"
                                        data-id="<?= $booking['id'] ?>"
                                        data-room="<?= htmlspecialchars($booking['type']) ?> (<?= $booking['room_number'] ?>)"
                                        data-dates="<?= date('M d, Y', strtotime($booking['check_in'])) ?> → <?= date('M d, Y', strtotime($booking['check_out'])) ?>"
                                        data-total="₱<?= number_format($booking['total_price'], 2) ?>"
                                        data-status="<?= $status_filter ?>"
                                        data-page="<?= $page ?>">
                                    Cancel Booking
                                </button>
                            <?php else: ?>
                                <button class="btn-disabled" disabled>
                                    No Action Available
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?status=<?= urlencode($status_filter) ?>&page=<?= $page-1 ?>">Previous</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?status=<?= urlencode($status_filter) ?>&page=<?= $i ?>"><?= $i ?></a></li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?status=<?= urlencode($status_filter) ?>&page=<?= $page+1 ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p style="font-weight: 300; color: #555;">Are you sure you want to cancel this booking?</p>
                    <div class="alert">
                        <strong>Room:</strong> <span id="cancelRoom"></span><br>
                        <strong>Dates:</strong> <span id="cancelDates"></span><br>
                        <strong>Total:</strong> <span id="cancelTotal"></span>
                    </div>
                    <p class="text-danger mb-0" style="font-size: 0.85rem;"><i class="fas fa-exclamation-triangle me-1"></i> This action cannot be undone.</p>
                    <input type="hidden" name="booking_id" id="cancelBookingId">
                    <input type="hidden" name="status_filter" id="cancelStatusFilter">
                    <input type="hidden" name="page" id="cancelPage">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="cancel_booking" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
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
    // Cancel modal logic
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('cancelRoom').innerText = this.dataset.room;
            document.getElementById('cancelDates').innerText = this.dataset.dates;
            document.getElementById('cancelTotal').innerText = this.dataset.total;
            document.getElementById('cancelBookingId').value = this.dataset.id;
            document.getElementById('cancelStatusFilter').value = this.dataset.status;
            document.getElementById('cancelPage').value = this.dataset.page;
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        });
    });

    // Chat Widget JS
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
                suggestions = ['Standard Twin', 'Standard Double', 'Deluxe', 'Superior', 'Suite'];
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