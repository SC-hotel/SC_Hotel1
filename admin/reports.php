<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

// Get selected period from URL (default: monthly)
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$currentYear = date('Y');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Breakdown pagination
$breakdown_page = isset($_GET['breakdown_page']) ? (int)$_GET['breakdown_page'] : 1;
$breakdown_limit = 10;
$breakdown_offset = ($breakdown_page - 1) * $breakdown_limit;

// Fetch data based on period (full dataset for summary and chart)
$reportData = [];
$labels = [];
$revenues = [];
$counts = [];

if ($period == 'weekly') {
    $sql = "SELECT 
                YEARWEEK(booking_date, 1) as week_key,
                DATE_FORMAT(booking_date, 'Week %v, %Y') as week_label,
                COUNT(*) as count,
                SUM(total_price) as revenue 
            FROM bookings 
            WHERE status='approved' 
            GROUP BY week_key 
            ORDER BY week_key DESC 
            LIMIT 12";
    $stmt = $pdo->query($sql);
    $reportData = $stmt->fetchAll();
    $reportTitle = "Weekly Revenue Report (Last 12 Weeks)";
} elseif ($period == 'yearly') {
    if ($selectedYear == 'all') {
        $sql = "SELECT 
                    YEAR(booking_date) as year,
                    COUNT(*) as count,
                    SUM(total_price) as revenue 
                FROM bookings 
                WHERE status='approved' 
                GROUP BY year 
                ORDER BY year DESC";
    } else {
        $sql = "SELECT 
                    MONTH(booking_date) as month,
                    DATE_FORMAT(booking_date, '%M %Y') as month_label,
                    COUNT(*) as count,
                    SUM(total_price) as revenue 
                FROM bookings 
                WHERE status='approved' AND YEAR(booking_date) = $selectedYear
                GROUP BY month 
                ORDER BY month";
    }
    $stmt = $pdo->query($sql);
    $reportData = $stmt->fetchAll();
    $reportTitle = ($selectedYear == 'all') ? "Yearly Revenue Report (All Years)" : "Monthly Revenue Report for $selectedYear";
} else { // monthly
    $sql = "SELECT 
                DATE_FORMAT(booking_date, '%Y-%m') as month_key,
                DATE_FORMAT(booking_date, '%M %Y') as month_label,
                COUNT(*) as count,
                SUM(total_price) as revenue 
            FROM bookings 
            WHERE status='approved' 
            GROUP BY month_key 
            ORDER BY month_key DESC 
            LIMIT 12";
    $stmt = $pdo->query($sql);
    $reportData = $stmt->fetchAll();
    $reportTitle = "Monthly Revenue Report (Last 12 Months)";
}

// Prepare chart data (full)
foreach (array_reverse($reportData) as $row) {
    if ($period == 'yearly' && $selectedYear == 'all') {
        $labels[] = $row['year'];
    } elseif ($period == 'yearly') {
        $labels[] = $row['month_label'];
    } elseif ($period == 'weekly') {
        $labels[] = $row['week_label'];
    } else {
        $labels[] = $row['month_label'];
    }
    $revenues[] = $row['revenue'];
    $counts[] = $row['count'];
}

// Paginate breakdown table
$total_breakdown_rows = count($reportData);
$total_breakdown_pages = ceil($total_breakdown_rows / $breakdown_limit);
$paginated_data = array_slice($reportData, $breakdown_offset, $breakdown_limit);

// Years for filter
$yearsStmt = $pdo->query("SELECT DISTINCT YEAR(booking_date) as y FROM bookings WHERE status='approved' ORDER BY y DESC");
$years = $yearsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SC Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        
        /* Fixed Sidebar - Deep Green Theme */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 280px;
            background: linear-gradient(135deg, #0b3d1a 0%, #145c2e 100%);
            z-index: 1000; transition: all 0.3s; box-shadow: 5px 0 30px rgba(0,0,0,0.1);
            overflow-y: auto;
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
            transition: all 0.3s; font-weight: 500;
        }
        .sidebar-nav a i { width: 28px; font-size: 1.2rem; margin-right: 12px; }
        .sidebar-nav a:hover { background: rgba(46, 204, 113, 0.2); color: white; transform: translateX(5px); }
        .sidebar-nav a.active { background: linear-gradient(90deg, #27ae60, #2ecc71); color: white; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3); }
        
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: all 0.2s; overflow: hidden;
        }
        .premium-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .stat-card {
            background: white; border-radius: 20px; padding: 20px;
            transition: all 0.3s; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .table th { font-weight: 600; border-top: none; }
        
        .btn-primary {
            background: linear-gradient(90deg, #27ae60, #2ecc71); border: none; transition: all 0.3s;
        }
        .btn-primary:hover { background: linear-gradient(90deg, #2ecc71, #27ae60); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46,204,113,0.3); }
        .btn-outline-primary {
            border-color: #27ae60; color: #27ae60;
        }
        .btn-outline-primary:hover { background: #27ae60; border-color: #27ae60; }
        .btn-success {
            background: #2ecc71; border: none;
        }
        .btn-success:hover { background: #27ae60; transform: translateY(-2px); }
        
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
        
        @media print {
            .sidebar, .top-navbar, .no-print, .btn, .carousel-control-prev, .carousel-control-next, .chat-widget-fixed { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .container-fluid { padding: 0 !important; }
            .stat-card, .premium-card { box-shadow: none; border: 1px solid #ddd; }
            body { background: white; }
        }
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
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo" class="sidebar-logo">
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-nav">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="manage_users.php"><i class="fas fa-users"></i><span>Manage Users</span></a>
        <a href="manage_rooms.php"><i class="fas fa-bed"></i><span>Manage Rooms</span></a>
        <a href="manage_bookings.php"><i class="fas fa-calendar-check"></i><span>Manage Bookings</span></a>
        <a href="reports.php" class="active"><i class="fas fa-chart-line"></i><span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h4 class="page-title"><i class="fas fa-chart-line me-2" style="color: #2ecc71;"></i>Financial Reports</h4>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <div class="user-avatar-badge"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        </div>
    </div>

    <div class="container-fluid p-4" id="reportContent">
        <!-- Summary Cards (currency changed to ₱) -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Period Revenue</h6>
                            <h2 class="mb-0 text-success">₱<?= number_format(array_sum($revenues), 2) ?></h2>
                            <small>From approved bookings</small>
                        </div>
                        <i class="fas fa-peso-sign fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Period Bookings</h6>
                            <h2 class="mb-0 text-primary"><?= array_sum($counts) ?></h2>
                            <small>Approved reservations</small>
                        </div>
                        <i class="fas fa-calendar-check fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Average Booking Value</h6>
                            <h2 class="mb-0 text-info">₱<?= number_format(array_sum($counts) > 0 ? array_sum($revenues) / array_sum($counts) : 0, 2) ?></h2>
                            <small>Per reservation</small>
                        </div>
                        <i class="fas fa-chart-bar fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Filter Controls -->
        <div class="premium-card mb-4 no-print">
            <div class="card-body">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Report Period</label>
                        <div class="d-flex gap-2">
                            <a href="?period=weekly" class="btn btn-sm <?= $period == 'weekly' ? 'btn-primary' : 'btn-outline-primary' ?>">Weekly</a>
                            <a href="?period=monthly" class="btn btn-sm <?= $period == 'monthly' ? 'btn-primary' : 'btn-outline-primary' ?>">Monthly</a>
                            <a href="?period=yearly&year=all" class="btn btn-sm <?= $period == 'yearly' && $selectedYear == 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Yearly (All)</a>
                        </div>
                    </div>
                    <?php if ($period == 'yearly'): ?>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Select Year</label>
                        <select class="form-select" id="yearSelect">
                            <option value="all" <?= $selectedYear == 'all' ? 'selected' : '' ?>>All Years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y['y'] ?>" <?= $selectedYear == $y['y'] ? 'selected' : '' ?>><?= $y['y'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="window.print();"><i class="fas fa-print me-2"></i>Print Report</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section (currency changed to ₱) -->
        <div class="premium-card mb-4">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2" style="color: #2ecc71;"></i><?= $reportTitle ?></h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>

        <!-- Revenue Breakdown Table (currency changed to ₱) -->
        <div class="premium-card">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="mb-0"><i class="fas fa-table me-2" style="color: #2ecc71;"></i>Revenue Breakdown (Showing <?= count($paginated_data) ?> of <?= $total_breakdown_rows ?> entries)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= ($period == 'yearly' && $selectedYear == 'all') ? 'Year' : 'Period' ?></th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>Average per Booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($paginated_data) > 0): ?>
                                <?php foreach ($paginated_data as $row): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?php
                                        if ($period == 'yearly' && $selectedYear == 'all') echo $row['year'];
                                        elseif ($period == 'yearly') echo $row['month_label'];
                                        elseif ($period == 'weekly') echo $row['week_label'];
                                        else echo $row['month_label'];
                                        ?>
                                    </td>
                                    <td><?= $row['count'] ?></td>
                                    <td class="text-success fw-bold">₱<?= number_format($row['revenue'], 2) ?></td>
                                    <td>₱<?= number_format($row['revenue'] / $row['count'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <td><td colspan="4" class="text-center text-muted py-5">No approved bookings found for this period.<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination for breakdown table -->
            <?php if ($total_breakdown_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 no-print">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($breakdown_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?period=<?= $period ?>&year=<?= $selectedYear ?>&breakdown_page=<?= $breakdown_page-1 ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_breakdown_pages; $i++): ?>
                            <li class="page-item <?= $i == $breakdown_page ? 'active' : '' ?>">
                                <a class="page-link" href="?period=<?= $period ?>&year=<?= $selectedYear ?>&breakdown_page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($breakdown_page < $total_breakdown_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?period=<?= $period ?>&year=<?= $selectedYear ?>&breakdown_page=<?= $breakdown_page+1 ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Render chart (currency changed to ₱)
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?= json_encode($revenues) ?>,
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderColor: '#27ae60',
                borderWidth: 1,
                borderRadius: 8,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: (ctx) => `₱${ctx.raw.toFixed(2)}` } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: (val) => '₱' + val } } }
        }
    });

    // Year select redirect (for yearly report)
    document.getElementById('yearSelect')?.addEventListener('change', function() {
        window.location.href = '?period=yearly&year=' + this.value + '&breakdown_page=1';
    });
</script>
</body>
</html>