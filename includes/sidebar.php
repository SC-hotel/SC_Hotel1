<div class="sidebar">
    <div class="sidebar-brand">
        <h3>SC Hotel</h3>
        <p>Admin Panel</p>
    </div>
    <div class="sidebar-nav">
        <a href="index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="manage_users.php" <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-users"></i><span>Manage Users</span>
        </a>
        <a href="manage_rooms.php" <?= basename($_SERVER['PHP_SELF']) == 'manage_rooms.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-bed"></i><span>Manage Rooms</span>
        </a>
        <a href="manage_bookings.php" <?= basename($_SERVER['PHP_SELF']) == 'manage_bookings.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-calendar-check"></i><span>Manage Bookings</span>
        </a>
        <a href="reports.php" <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : '' ?>>
            <i class="fas fa-chart-line"></i><span>Reports</span>
        </a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>