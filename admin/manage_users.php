<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotAdmin();

$message = '';
$messageType = '';

// Get current tab (all, active, banned)
$tab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'all';
$valid_tabs = ['all', 'active', 'banned'];
if (!in_array($tab, $valid_tabs)) $tab = 'all';

// Handle Ban
if (isset($_GET['ban']) && is_numeric($_GET['ban'])) {
    $userId = $_GET['ban'];
    if ($userId == $_SESSION['user_id']) {
        $message = "You cannot ban yourself!";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET banned = 1 WHERE id = ?");
        if ($stmt->execute([$userId])) {
            $message = "User banned successfully!";
            $messageType = "success";
        } else {
            $message = "Error banning user.";
            $messageType = "danger";
        }
    }
}

// Handle Unban
if (isset($_GET['unban']) && is_numeric($_GET['unban'])) {
    $userId = $_GET['unban'];
    $stmt = $pdo->prepare("UPDATE users SET banned = 0 WHERE id = ?");
    if ($stmt->execute([$userId])) {
        $message = "User unbanned successfully!";
        $messageType = "success";
    } else {
        $message = "Error unbanning user.";
        $messageType = "danger";
    }
}

// Handle Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = $_GET['delete'];
    if ($userId == $_SESSION['user_id']) {
        $message = "You cannot delete your own account!";
        $messageType = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$userId])) {
            $message = "User deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting user.";
            $messageType = "danger";
        }
    }
}

// Search and pagination
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause based on tab and search
$whereConditions = [];
$params = [];

// Filter by banned status
if ($tab == 'active') {
    $whereConditions[] = "banned = 0";
} elseif ($tab == 'banned') {
    $whereConditions[] = "banned = 1";
}
// Always exclude admins from the list (we're managing regular users)
$whereConditions[] = "role = 'user'";

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total users
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSQL");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

// Fetch users for current page
$sql = "SELECT id, name, email, role, profile_pic, created_at, banned 
        FROM users 
        $whereSQL 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get counts for tabs
$totalAll = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalActive = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND banned = 0")->fetchColumn();
$totalBanned = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND banned = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SC Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* All existing styles remain as before (same as in manage_bookings) */
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
        .user-avatar {
            width: 40px; height: 40px; object-fit: cover; border-radius: 50%;
            border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th { font-weight: 600; border-top: none; }
        .btn-primary {
            background: linear-gradient(90deg, #27ae60, #2ecc71); border: none; transition: all 0.3s;
        }
        .btn-primary:hover { background: linear-gradient(90deg, #2ecc71, #27ae60); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(46,204,113,0.3); }
        .btn-outline-primary { border-color: #27ae60; color: #27ae60; }
        .btn-outline-primary:hover { background: #27ae60; border-color: #27ae60; }
        .btn-sm { border-radius: 8px; margin: 0 2px; }
        .pagination .page-item.active .page-link { background-color: #27ae60; border-color: #27ae60; }
        .pagination .page-link { color: #27ae60; }
        .pagination .page-link:hover { color: #2ecc71; }
        .modal-content { border-radius: 20px; border: none; }
        .modal-header { background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border-radius: 20px 20px 0 0; }
        .status-tabs {
            display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .status-tab {
            padding: 0.5rem 1.2rem; background: white; border-radius: 40px; text-decoration: none;
            color: #2c3e50; font-weight: 500; transition: all 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-tab.active { background: #27ae60; color: white; }
        .status-tab:hover:not(.active) { background: #e9ecef; transform: translateY(-2px); }
        .sidebar-logo {
            width: 120px; height: auto; background: #e9e9e4; padding: 5px; display: inline-block; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
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
        <a href="manage_users.php" class="active"><i class="fas fa-users"></i><span>Manage Users</span></a>
        <a href="manage_rooms.php"><i class="fas fa-bed"></i><span>Manage Rooms</span></a>
        <a href="manage_bookings.php"><i class="fas fa-calendar-check"></i><span>Manage Bookings</span></a>
        <a href="reports.php"><i class="fas fa-chart-line"></i><span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <h4 class="page-title"><i class="fas fa-users me-2" style="color: #27ae60;"></i>Manage Users</h4>
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
            <a href="?tab=all" class="status-tab <?= $tab == 'all' ? 'active' : '' ?>">All Users <span class="badge bg-secondary"><?= $totalAll ?></span></a>
            <a href="?tab=active" class="status-tab <?= $tab == 'active' ? 'active' : '' ?>">Active <span class="badge bg-success"><?= $totalActive ?></span></a>
            <a href="?tab=banned" class="status-tab <?= $tab == 'banned' ? 'active' : '' ?>">Banned <span class="badge bg-danger"><?= $totalBanned ?></span></a>
        </div>

        <!-- Search Bar Card -->
        <div class="premium-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="<?= $tab ?>">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Search Users</label>
                        <input type="text" name="search" class="form-control form-control-lg" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fas fa-search me-2"></i>Search</button>
                        <a href="?tab=<?= $tab ?>" class="btn btn-outline-secondary w-100 mt-2"><i class="fas fa-sync-alt me-2"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="premium-card">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="mb-0"><i class="fas fa-list me-2" style="color: #27ae60;"></i>
                    <?php 
                        if ($tab == 'active') echo 'Active Users';
                        elseif ($tab == 'banned') echo 'Banned Users';
                        else echo 'All Users';
                    ?>
                    (<?= $totalUsers ?> total)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Avatar</th><th>Name</th><th>Email</th><th>Status</th><th>Registered On</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><img src="../assets/uploads/profiles/<?= htmlspecialchars($user['profile_pic']) ?>" class="user-avatar" onerror="this.src='../assets/uploads/profiles/default.png'"></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['banned']): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-ban me-1"></i> Banned</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['banned']): ?>
                                                <button class="btn btn-sm btn-success unban-btn"
                                                        data-id="<?= $user['id'] ?>"
                                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-tab="<?= $tab ?>"
                                                        data-search="<?= urlencode($search) ?>"
                                                        data-page="<?= $page ?>">
                                                    <i class="fas fa-user-check"></i> Unban
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning ban-btn"
                                                        data-id="<?= $user['id'] ?>"
                                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-tab="<?= $tab ?>"
                                                        data-search="<?= urlencode($search) ?>"
                                                        data-page="<?= $page ?>">
                                                    <i class="fas fa-ban"></i> Ban
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger delete-btn"
                                                    data-id="<?= $user['id'] ?>"
                                                    data-name="<?= htmlspecialchars($user['name']) ?>"
                                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                                    data-tab="<?= $tab ?>"
                                                    data-search="<?= urlencode($search) ?>"
                                                    data-page="<?= $page ?>">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">No users found.<?php endif; ?>
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
                            <li class="page-item"><a class="page-link" href="?tab=<?= $tab ?>&search=<?= urlencode($search) ?>&page=<?= $page-1 ?>">Previous</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?tab=<?= $tab ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?tab=<?= $tab ?>&search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Ban Modal -->
<div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Ban User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong class="text-warning">ban</strong> this user?</p>
                <div class="alert alert-warning">
                    <strong>User:</strong> <span id="banName"></span><br>
                    <strong>Email:</strong> <span id="banEmail"></span>
                </div>
                <p class="text-danger mb-0">Banned users will not be able to log in or make new bookings.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="banConfirmBtn" class="btn btn-warning">Confirm Ban</a>
            </div>
        </div>
    </div>
</div>

<!-- Unban Modal -->
<div class="modal fade" id="unbanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Unban User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong class="text-success">unban</strong> this user?</p>
                <div class="alert alert-success">
                    <strong>User:</strong> <span id="unbanName"></span><br>
                    <strong>Email:</strong> <span id="unbanEmail"></span>
                </div>
                <p class="text-muted mb-0">The user will regain full access to the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="unbanConfirmBtn" class="btn btn-success">Confirm Unban</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Delete User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong class="text-danger">permanently delete</strong> this user?</p>
                <div class="alert alert-danger">
                    <strong>User:</strong> <span id="deleteName"></span><br>
                    <strong>Email:</strong> <span id="deleteEmail"></span>
                </div>
                <p class="text-danger mb-0">This action cannot be undone and will also remove all bookings made by this user.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Confirm Delete</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Ban modal logic
    document.querySelectorAll('.ban-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const tab = this.dataset.tab;
            const search = this.dataset.search;
            const page = this.dataset.page;
            document.getElementById('banName').innerText = name;
            document.getElementById('banEmail').innerText = email;
            document.getElementById('banConfirmBtn').href = `?ban=${userId}&tab=${tab}&search=${search}&page=${page}`;
            new bootstrap.Modal(document.getElementById('banModal')).show();
        });
    });

    // Unban modal logic
    document.querySelectorAll('.unban-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const tab = this.dataset.tab;
            const search = this.dataset.search;
            const page = this.dataset.page;
            document.getElementById('unbanName').innerText = name;
            document.getElementById('unbanEmail').innerText = email;
            document.getElementById('unbanConfirmBtn').href = `?unban=${userId}&tab=${tab}&search=${search}&page=${page}`;
            new bootstrap.Modal(document.getElementById('unbanModal')).show();
        });
    });

    // Delete modal logic
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const tab = this.dataset.tab;
            const search = this.dataset.search;
            const page = this.dataset.page;
            document.getElementById('deleteName').innerText = name;
            document.getElementById('deleteEmail').innerText = email;
            document.getElementById('deleteConfirmBtn').href = `?delete=${userId}&tab=${tab}&search=${search}&page=${page}`;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });
</script>
</body>
</html>