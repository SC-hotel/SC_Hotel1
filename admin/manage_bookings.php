<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$message = '';
$messageType = '';

// Handle POST actions (approve / cancel) with email notification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Security validation failed. Please reload and try again.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'];
        $booking_id = $_POST['booking_id'];
        
        // Fetch booking details and user email before updating
        $stmt = $pdo->prepare("
            SELECT b.*, u.email as user_email, u.name as user_name, r.type, r.room_number 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $bookingInfo = $stmt->fetch();
        
        if (!$bookingInfo) {
            $message = "Booking not found.";
            $messageType = "danger";
        } else {
            if ($action == 'approve') {
                $stmt = $pdo->prepare("UPDATE bookings SET status='approved' WHERE id=?");
                if ($stmt->execute([$booking_id])) {
                    $message = "Booking approved successfully!";
                    $messageType = "success";
                    // Send email notification
                    sendBookingStatusEmail(
                        $bookingInfo['user_email'],
                        $bookingInfo['user_name'],
                        $bookingInfo,
                        'approved',
                        ['type' => $bookingInfo['type'], 'room_number' => $bookingInfo['room_number']]
                    );
                } else {
                    $message = "Error approving booking.";
                    $messageType = "danger";
                }
            } elseif ($action == 'cancel') {
                $stmt = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
                if ($stmt->execute([$booking_id])) {
                    $message = "Booking cancelled successfully!";
                    $messageType = "success";
                    // Send email notification
                    sendBookingStatusEmail(
                        $bookingInfo['user_email'],
                        $bookingInfo['user_name'],
                        $bookingInfo,
                        'cancelled',
                        ['type' => $bookingInfo['type'], 'room_number' => $bookingInfo['room_number']]
                    );
                } else {
                    $message = "Error cancelling booking.";
                    $messageType = "danger";
                }
            }
        }
    }
    // Redirect back to same page (preserve filters)
    $redirect = "?tab=" . urlencode($_POST['tab']) . "&search=" . urlencode($_POST['search']) . "&page=" . $_POST['page'];
    header("Location: " . $redirect);
    exit;
}

// Get tab (pending, approved, cancelled, or all)
$active_tab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'all';
$valid_tabs = ['all', 'pending', 'approved', 'cancelled'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'all';
}

// Search and filter (from form)
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = ($active_tab != 'all') ? $active_tab : (isset($_GET['status']) ? sanitizeInput($_GET['status']) : '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(u.name LIKE ? OR u.email LIKE ? OR r.type LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'cancelled'])) {
    $whereClauses[] = "b.status = ?";
    $params[] = $status_filter;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count total for pagination
$countSQL = "SELECT COUNT(*) FROM bookings b 
             JOIN users u ON b.user_id = u.id 
             JOIN rooms r ON b.room_id = r.id 
             $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalBookings = $countStmt->fetchColumn();
$totalPages = ceil($totalBookings / $limit);

// Fetch bookings for current page
$sql = "SELECT b.*, u.name as user_name, u.email, r.type as room_type, r.room_number 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN rooms r ON b.room_id = r.id 
        $whereSQL 
        ORDER BY b.booking_date DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get counts for each status (for tabs)
$counts = [];
$statuses = ['pending', 'approved', 'cancelled'];
foreach ($statuses as $st) {
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = ?");
    $cntStmt->execute([$st]);
    $counts[$st] = $cntStmt->fetchColumn();
}
$totalAll = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - SC Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== SIDEBAR & MAIN STYLES (same as before) ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 280px;
            background: linear-gradient(135deg, #0b3d1a 0%, #145c2e 100%);
            z-index: 1000; transition: all 0.3s ease; box-shadow: 5px 0 30px rgba(0,0,0,0.1); overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: #0b3d1a; }
        .sidebar::-webkit-scrollbar-thumb { background: #2ecc71; border-radius: 5px; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-brand h3 { color: white; font-weight: 600; margin: 0; letter-spacing: 1px; }
        .sidebar-brand p { color: #a0d4a4; font-size: 0.8rem; margin-top: 8px; }
        .sidebar-nav { padding: 0 20px; }
        .sidebar-nav a {
            display: flex; align-items: center; padding: 12px 16px; margin: 8px 0;
            color: #cfdfed; text-decoration: none; border-radius: 12px;
            transition: all 0.3s ease; font-weight: 500;
        }
        .sidebar-nav a i { width: 28px; font-size: 1.2rem; margin-right: 12px; }
        .sidebar-nav a:hover { background: rgba(46, 204, 113, 0.2); color: white; transform: translateX(5px); }
        .sidebar-nav a.active { background: linear-gradient(90deg, #27ae60, #2ecc71); color: white; box-shadow: 0 4px 10px rgba(46,204,113,0.3); }
        .main-content { margin-left: 280px; min-height: 100vh; background: #f4f6f9; }
        .top-navbar {
            background: white; padding: 15px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999;
        }
        .page-title { font-size: 1.5rem; font-weight: 600; color: #2c3e50; margin: 0; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar-badge {
            width: 40px; height: 40px; background: #2ecc71; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;
        }
        .premium-card {
            background: white; border-radius: 20px; border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; overflow: hidden;
        }
        .premium-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .table th { font-weight: 600; border-top: none; }
        .btn-primary {
            background: linear-gradient(90deg, #27ae60, #2ecc71); border: none; transition: all 0.3s;
        }
        .btn-primary:hover { background: linear-gradient(90deg, #2ecc71, #27ae60); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46,204,113,0.3); }
        .btn-outline-primary { border-color: #27ae60; color: #27ae60; }
        .btn-outline-primary:hover { background: #27ae60; border-color: #27ae60; }
        .btn-success { background: #2ecc71; border: none; }
        .btn-success:hover { background: #27ae60; }
        .btn-warning { background: #f39c12; border: none; color: white; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; border: none; }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { border-radius: 8px; margin: 0 2px; }
        .badge.bg-success { background-color: #2ecc71 !important; }
        .badge.bg-warning { background-color: #f39c12 !important; }
        .badge.bg-danger { background-color: #e74c3c !important; }
        .pagination .page-item.active .page-link { background-color: #27ae60; border-color: #27ae60; }
        .pagination .page-link { color: #27ae60; }
        .pagination .page-link:hover { color: #2ecc71; }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 20px 20px 0 0; }
        .detail-label { font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .status-tabs {
            display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .status-tab {
            padding: 0.5rem 1.2rem; background: white; border-radius: 40px; text-decoration: none;
            color: #2c3e50; font-weight: 500; transition: all 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-tab.active { background: #27ae60; color: white; }
        .status-tab:hover:not(.active) { background: #e9ecef; transform: translateY(-2px); }
        .sidebar-logo { width: 120px; height: auto; background: #e9e9e4; padding: 5px; display: inline-block; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .main-content { margin-left: 80px; }
            .sidebar-brand h3, .sidebar-brand p, .sidebar-nav a span { display: none; }
            .sidebar-nav a i { margin-right: 0; font-size: 1.5rem; }
            .sidebar-nav a { justify-content: center; padding: 12px; }
        }
    </style>
</head>
<body>

<!-- Fixed Sidebar (Deep Green) -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo" class="sidebar-logo">
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
        <a href="manage_rooms.php"><i class="fas fa-bed"></i><span>Manage Rooms</span></a>
        <a href="manage_bookings.php" class="active"><i class="fas fa-calendar-check"></i><span>Manage Bookings</span></a>
        <a href="reports.php"><i class="fas fa-chart-line"></i><span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h4 class="page-title"><i class="fas fa-calendar-check me-2" style="color: #27ae60;"></i>Manage Bookings</h4>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar-badge">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="container-fluid p-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm rounded-3" role="alert">
                <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <a href="?tab=all" class="status-tab <?= $active_tab == 'all' ? 'active' : '' ?>">All <span class="badge bg-secondary"><?= $totalAll ?></span></a>
            <a href="?tab=pending" class="status-tab <?= $active_tab == 'pending' ? 'active' : '' ?>">Pending <span class="badge bg-danger"><?= $counts['pending'] ?></span></a>
            <a href="?tab=approved" class="status-tab <?= $active_tab == 'approved' ? 'active' : '' ?>">Approved <span class="badge bg-success"><?= $counts['approved'] ?></span></a>
            <a href="?tab=cancelled" class="status-tab <?= $active_tab == 'cancelled' ? 'active' : '' ?>">Cancelled <span class="badge bg-warning"><?= $counts['cancelled'] ?></span></a>
        </div>

        <!-- Search and Filter Card -->
        <div class="premium-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="<?= $active_tab ?>">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Guest name, email, or room type..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status Filter (override tab)</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" <?= $status_filter == 'pending' && $active_tab == 'all' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter == 'approved' && $active_tab == 'all' ? 'selected' : '' ?>>Approved</option>
                            <option value="cancelled" <?= $status_filter == 'cancelled' && $active_tab == 'all' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Apply Filters</button>
                        <a href="manage_bookings.php" class="btn btn-outline-secondary w-100 mt-2"><i class="fas fa-sync-alt me-2"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="premium-card">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0"><i class="fas fa-list me-2" style="color: #27ae60;"></i>
                    <?php 
                        if ($active_tab == 'pending') echo 'Pending Bookings';
                        elseif ($active_tab == 'approved') echo 'Approved Bookings';
                        elseif ($active_tab == 'cancelled') echo 'Cancelled Bookings';
                        else echo 'All Bookings';
                    ?>
                    (<?= $totalBookings ?> total)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Check-in Time</th><th>Total</th><th>Status</th><th>Booking Date</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings) > 0): ?>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['user_name']) ?><br><small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small></td>
                                    <td><?= htmlspecialchars($booking['room_type']) ?> (<?= $booking['room_number'] ?>)</td>
                                    <td><?= date('M d, Y', strtotime($booking['check_in'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['check_out'])) ?></td>
                                    <td><?= date('h:i A', strtotime($booking['check_in_time'])) ?></td>
                                    <td>₱<?= number_format($booking['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= 
                                            $booking['status'] == 'approved' ? 'success' : 
                                            ($booking['status'] == 'cancelled' ? 'danger' : 'warning') 
                                        ?> px-3 py-2">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['booking_date'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info text-white view-booking" 
                                                data-id="<?= $booking['id'] ?>"
                                                data-guest="<?= htmlspecialchars($booking['user_name']) ?>"
                                                data-email="<?= htmlspecialchars($booking['email']) ?>"
                                                data-room="<?= htmlspecialchars($booking['room_type']) ?> (<?= $booking['room_number'] ?>)"
                                                data-checkin="<?= date('F d, Y', strtotime($booking['check_in'])) ?>"
                                                data-checkout="<?= date('F d, Y', strtotime($booking['check_out'])) ?>"
                                                data-time="<?= date('h:i A', strtotime($booking['check_in_time'])) ?>"
                                                data-total="₱<?= number_format($booking['total_price'], 2) ?>"
                                                data-status="<?= ucfirst($booking['status']) ?>"
                                                data-id-doc="<?= htmlspecialchars($booking['id_document']) ?>"
                                                data-created="<?= date('F d, Y h:i A', strtotime($booking['booking_date'])) ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-success approve-btn"
                                                    data-id="<?= $booking['id'] ?>"
                                                    data-guest="<?= htmlspecialchars($booking['user_name']) ?>"
                                                    data-room="<?= htmlspecialchars($booking['room_type']) ?> (<?= $booking['room_number'] ?>)"
                                                    data-checkin="<?= date('M d, Y', strtotime($booking['check_in'])) ?>"
                                                    data-checkout="<?= date('M d, Y', strtotime($booking['check_out'])) ?>"
                                                    data-total="₱<?= number_format($booking['total_price'], 2) ?>"
                                                    data-tab="<?= $active_tab ?>"
                                                    data-search="<?= urlencode($search) ?>"
                                                    data-page="<?= $page ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger cancel-btn"
                                                    data-id="<?= $booking['id'] ?>"
                                                    data-guest="<?= htmlspecialchars($booking['user_name']) ?>"
                                                    data-room="<?= htmlspecialchars($booking['room_type']) ?> (<?= $booking['room_number'] ?>)"
                                                    data-checkin="<?= date('M d, Y', strtotime($booking['check_in'])) ?>"
                                                    data-checkout="<?= date('M d, Y', strtotime($booking['check_out'])) ?>"
                                                    data-total="₱<?= number_format($booking['total_price'], 2) ?>"
                                                    data-tab="<?= $active_tab ?>"
                                                    data-search="<?= urlencode($search) ?>"
                                                    data-page="<?= $page ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php elseif ($booking['status'] == 'approved'): ?>
                                            <button class="btn btn-sm btn-warning cancel-btn"
                                                    data-id="<?= $booking['id'] ?>"
                                                    data-guest="<?= htmlspecialchars($booking['user_name']) ?>"
                                                    data-room="<?= htmlspecialchars($booking['room_type']) ?> (<?= $booking['room_number'] ?>)"
                                                    data-checkin="<?= date('M d, Y', strtotime($booking['check_in'])) ?>"
                                                    data-checkout="<?= date('M d, Y', strtotime($booking['check_out'])) ?>"
                                                    data-total="₱<?= number_format($booking['total_price'], 2) ?>"
                                                    data-tab="<?= $active_tab ?>"
                                                    data-search="<?= urlencode($search) ?>"
                                                    data-page="<?= $page ?>">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">--</span>
                                        <?php endif; ?>
                                    </tr>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center text-muted py-5">No bookings found.<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white border-0 py-3">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?tab=<?= $active_tab ?>&search=<?= urlencode($search) ?>&page=<?= $page-1 ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?tab=<?= $active_tab ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?tab=<?= $active_tab ?>&search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Approve Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to approve this booking?</p>
                    <div class="alert alert-info">
                        <strong>Guest:</strong> <span id="approveGuest"></span><br>
                        <strong>Room:</strong> <span id="approveRoom"></span><br>
                        <strong>Dates:</strong> <span id="approveDates"></span><br>
                        <strong>Total:</strong> <span id="approveTotal"></span>
                    </div>
                    <p class="text-warning mb-0">This action cannot be undone.</p>
                    <input type="hidden" name="booking_id" id="approveBookingId">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="tab" id="approveTab">
                    <input type="hidden" name="search" id="approveSearch">
                    <input type="hidden" name="page" id="approvePage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Cancel Booking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <div class="alert alert-warning">
                        <strong>Guest:</strong> <span id="cancelGuest"></span><br>
                        <strong>Room:</strong> <span id="cancelRoom"></span><br>
                        <strong>Dates:</strong> <span id="cancelDates"></span><br>
                        <strong>Total:</strong> <span id="cancelTotal"></span>
                    </div>
                    <p class="text-danger mb-0">This action cannot be undone.</p>
                    <input type="hidden" name="booking_id" id="cancelBookingId">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="tab" id="cancelTab">
                    <input type="hidden" name="search" id="cancelSearch">
                    <input type="hidden" name="page" id="cancelPage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Details Modal (unchanged) -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Booking Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Guest Name</div>
                        <p id="modalGuestName" class="mb-3"></p>
                        <div class="detail-label">Email</div>
                        <p id="modalEmail" class="mb-3"></p>
                        <div class="detail-label">Room</div>
                        <p id="modalRoom" class="mb-3"></p>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Check-in Date</div>
                        <p id="modalCheckIn" class="mb-3"></p>
                        <div class="detail-label">Check-out Date</div>
                        <p id="modalCheckOut" class="mb-3"></p>
                        <div class="detail-label">Check-in Time</div>
                        <p id="modalTime" class="mb-3"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-label">Total Price</div>
                        <p id="modalTotal" class="mb-3"></p>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Status</div>
                        <p id="modalStatus" class="mb-3"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="detail-label">Government ID Document</div>
                        <p id="modalIdDoc" class="mb-3"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="detail-label">Booking Created</div>
                        <p id="modalCreated" class="mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Approve modal logic
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('approveGuest').innerText = this.dataset.guest;
            document.getElementById('approveRoom').innerText = this.dataset.room;
            document.getElementById('approveDates').innerText = `${this.dataset.checkin} to ${this.dataset.checkout}`;
            document.getElementById('approveTotal').innerText = this.dataset.total;
            document.getElementById('approveBookingId').value = this.dataset.id;
            document.getElementById('approveTab').value = this.dataset.tab;
            document.getElementById('approveSearch').value = this.dataset.search;
            document.getElementById('approvePage').value = this.dataset.page;
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        });
    });

    // Cancel modal logic
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('cancelGuest').innerText = this.dataset.guest;
            document.getElementById('cancelRoom').innerText = this.dataset.room;
            document.getElementById('cancelDates').innerText = `${this.dataset.checkin} to ${this.dataset.checkout}`;
            document.getElementById('cancelTotal').innerText = this.dataset.total;
            document.getElementById('cancelBookingId').value = this.dataset.id;
            document.getElementById('cancelTab').value = this.dataset.tab;
            document.getElementById('cancelSearch').value = this.dataset.search;
            document.getElementById('cancelPage').value = this.dataset.page;
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        });
    });

    // View booking details modal
    document.querySelectorAll('.view-booking').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modalGuestName').innerText = this.dataset.guest;
            document.getElementById('modalEmail').innerText = this.dataset.email;
            document.getElementById('modalRoom').innerText = this.dataset.room;
            document.getElementById('modalCheckIn').innerText = this.dataset.checkin;
            document.getElementById('modalCheckOut').innerText = this.dataset.checkout;
            document.getElementById('modalTime').innerText = this.dataset.time;
            document.getElementById('modalTotal').innerText = this.dataset.total;
            document.getElementById('modalStatus').innerHTML = `<span class="badge rounded-pill bg-${this.dataset.status === 'Approved' ? 'success' : (this.dataset.status === 'Cancelled' ? 'danger' : 'warning')} px-3 py-2">${this.dataset.status}</span>`;
            document.getElementById('modalCreated').innerText = this.dataset.created;
            
            const idDoc = this.dataset.idDoc;
            if (idDoc && idDoc !== '') {
                document.getElementById('modalIdDoc').innerHTML = `<a href="../assets/uploads/id_docs/${idDoc}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-alt me-1"></i> View Uploaded ID</a>`;
            } else {
                document.getElementById('modalIdDoc').innerHTML = '<span class="text-muted">No ID uploaded</span>';
            }
            new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
        });
    });
</script>
</body>
</html>