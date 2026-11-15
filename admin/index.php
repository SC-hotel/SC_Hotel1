<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Pagination for recent bookings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total bookings for pagination
$totalBookingsCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalPages = ceil($totalBookingsCount / $limit);

// Fetch bookings for current page (including guest and room details)
$stmt = $pdo->prepare("
    SELECT b.*, u.name as user_name, r.type as room_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.booking_date DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recentBookings = $stmt->fetchAll();

// Dashboard statistics
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status='approved'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$approvedBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SC Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }

        /* Fixed Sidebar - Deep Green Theme */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #0b3d1a 0%, #145c2e 100%);
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 5px 0 30px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: #0b3d1a; }
        .sidebar::-webkit-scrollbar-thumb { background: #2ecc71; border-radius: 5px; }

        .sidebar-brand {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-brand h3 {
            color: white;
            font-weight: 600;
            margin: 0;
            letter-spacing: 1px;
        }

        .sidebar-brand p {
            color: #a0d4a4;
            font-size: 0.8rem;
            margin-top: 8px;
        }

        .sidebar-nav {
            padding: 0 20px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin: 8px 0;
            color: #cfdfed;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-nav a i {
            width: 28px;
            font-size: 1.2rem;
            margin-right: 12px;
        }

        .sidebar-nav a:hover {
            background: rgba(46, 204, 113, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav a.active {
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            color: white;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: #f4f6f9;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #2ecc71;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        /* Green Buttons */
        .btn-primary {
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border: none;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
        }
        .btn-warning {
            background: #f39c12;
            border: none;
            color: white;
        }
        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        .btn-info {
            background: #1abc9c;
            border: none;
            color: white;
        }
        .btn-info:hover {
            background: #16a085;
            transform: translateY(-2px);
        }
        .badge.bg-success {
            background-color: #2ecc71 !important;
        }
        .badge.bg-warning {
            background-color: #f39c12 !important;
        }
        .badge.bg-danger {
            background-color: #e74c3c !important;
        }
        .table th {
            font-weight: 600;
        }

        /* Pagination */
        .pagination .page-item.active .page-link {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        .pagination .page-link {
            color: #27ae60;
        }
        .pagination .page-link:hover {
            color: #2ecc71;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 80px; }
            .main-content { margin-left: 80px; }
            .sidebar-brand h3, .sidebar-brand p, .sidebar-nav a span { display: none; }
            .sidebar-nav a i { margin-right: 0; font-size: 1.5rem; }
            .sidebar-nav a { justify-content: center; padding: 12px; }
        }

        .sidebar-logo {
    width: 120px;
    height: auto;
    background: #e9e9e4;
    padding: 5px;
    display: inline-block;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
    </style>
</head>
<body>

<!-- Fixed Sidebar (Deep Green) -->
<!-- Fixed Sidebar (Deep Green) -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo" class="sidebar-logo">
        
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-nav">
        <!-- navigation links unchanged -->
        <a href="index.php" class="active">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="manage_users.php">
            <i class="fas fa-users"></i><span>Manage Users</span>
        </a>
        <a href="manage_rooms.php">
            <i class="fas fa-bed"></i><span>Manage Rooms</span>
        </a>
        <a href="manage_bookings.php">
            <i class="fas fa-calendar-check"></i><span>Manage Bookings</span>
        </a>
        <a href="reports.php">
            <i class="fas fa-chart-line"></i><span>Reports</span>
        </a>
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h4 class="page-title">Dashboard</h4>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="container-fluid p-4">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h2 class="mb-0"><?= $totalUsers ?></h2>
                        </div>
                        <i class="fas fa-users stat-icon" style="color: #27ae60;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Rooms</h6>
                            <h2 class="mb-0"><?= $totalRooms ?></h2>
                        </div>
                        <i class="fas fa-bed stat-icon" style="color: #2ecc71;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Bookings</h6>
                            <h2 class="mb-0"><?= $totalBookings ?></h2>
                            <small class="text-warning"><?= $pendingBookings ?> pending</small>
                        </div>
                        <i class="fas fa-calendar-check stat-icon" style="color: #f39c12;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h2 class="mb-0">₱<?= number_format($totalRevenue ?? 0, 2) ?></h2>
                        </div>
                        <i class="fas fa-peso-sign stat-icon" style="color: #1abc9c;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stat-card">
                    <h5 class="mb-3"><i class="fas fa-bolt" style="color: #27ae60;"></i> Quick Actions</h5>
                    <a href="manage_rooms.php" class="btn btn-primary me-2">
                        <i class="fas fa-plus"></i> Add New Room
                    </a>
                    <a href="manage_bookings.php?filter=pending" class="btn btn-warning me-2">
                        <i class="fas fa-clock"></i> Review Pending Bookings (<?= $pendingBookings ?>)
                    </a>
                    <a href="reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Generate Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table with Pagination -->
        <div class="stat-card">
            <h5 class="mb-3"><i class="fas fa-history" style="color: #27ae60;"></i> Recent Bookings</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentBookings) > 0): ?>
                            <?php foreach($recentBookings as $booking): ?>
                            <tr>
                                
                                <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                <td><?= htmlspecialchars($booking['room_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($booking['check_in'])) ?></td>
                                <td><?= date('M d, Y', strtotime($booking['check_out'])) ?></td>
                                <td>₱<?= number_format($booking['total_price'], 2) ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $booking['status']=='approved' ? 'success' : ($booking['status']=='cancelled' ? 'danger' : 'warning') ?> px-3 py-2">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="manage_bookings.php?edit=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No bookings found.<?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo; Previous</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?>" aria-label="Next">
                                    <span aria-hidden="true">Next &raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>