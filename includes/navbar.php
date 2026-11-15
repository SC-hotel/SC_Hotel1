<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<!--
    LUXURY NAVBAR – SC Hotel (Final Mobile Dropdown Fix)
-->
<style>
    /* ============================================================
       CSS VARIABLES
       ============================================================ */
    :root {
        --nav-gold: #c9a227;
        --nav-gold-light: #e6c85a;
        --nav-charcoal: #1a1a1a;
        --nav-cream: #faf8f5;
        --nav-cream-dark: #f0ece4;
        --nav-text-muted: #8a8a8a;
        --nav-shadow-sm: 0 2px 12px rgba(0,0,0,0.06);
        --nav-shadow-md: 0 8px 30px rgba(0,0,0,0.08);
        --nav-shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
        --nav-transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        --nav-font-body: 'Jost', 'Segoe UI', sans-serif;
    }

    html, body {
        overflow-x: hidden;
        width: 100%;
        margin: 0;
        padding: 0;
    }

    /* ============================================================
       NAVBAR BASE
       ============================================================ */
    .navbar-premium {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        max-width: 100vw; 
        z-index: 9999; 
        background: rgba(250, 248, 245, 0.95);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(201, 162, 39, 0.12);
        transition: var(--nav-transition);
        min-height: 80px;
        padding: 0.8rem 0;
        box-shadow: var(--nav-shadow-sm);
        font-family: var(--nav-font-body);
        box-sizing: border-box;
    }

    .navbar-premium.scrolled {
        background: rgba(250, 248, 245, 0.98);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: var(--nav-shadow-md);
        min-height: 66px;
        padding: 0.4rem 0;
    }

    .navbar-premium .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        position: relative;
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
        box-sizing: border-box;
    }

    /* ============================================================
       LOGO
       ============================================================ */
    .navbar-premium .navbar-brand {
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
        flex-shrink: 1;
        max-width: 60%;
    }

    .navbar-premium .navbar-brand img {
        height: 58px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        display: block;
        transition: height var(--nav-transition);
    }

    .navbar-premium.scrolled .navbar-brand img {
        height: 46px;
    }

    /* ============================================================
       CUSTOM TOGGLER (Hamburger)
       ============================================================ */
    .navbar-premium .custom-toggler {
        display: none; 
        border: none;
        padding: 0.4rem;
        background: transparent;
        cursor: pointer;
        z-index: 10002; 
        flex-shrink: 0;
    }

    .navbar-premium .custom-toggler:focus { outline: none; }

    .navbar-premium .custom-toggler .icon-bar {
        display: block;
        width: 26px;
        height: 2px;
        background-color: var(--nav-charcoal);
        margin: 6px 0;
        transition: 0.3s;
    }

    .navbar-premium .custom-toggler.active .icon-bar:nth-child(1) {
        transform: translate(0, 8px) rotate(-45deg);
    }
    .navbar-premium .custom-toggler.active .icon-bar:nth-child(2) {
        opacity: 0;
    }
    .navbar-premium .custom-toggler.active .icon-bar:nth-child(3) {
        transform: translate(0, -8px) rotate(45deg);
    }

    /* ============================================================
       NAVIGATION LINKS (Desktop)
       ============================================================ */
    .navbar-premium .navbar-collapse {
        display: flex !important;
        flex-basis: auto;
        flex-grow: 1;
        align-items: center;
        background: transparent;
        position: static;
        box-shadow: none;
        padding: 0;
    }

    .navbar-premium .navbar-nav {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.2rem;
        margin-left: auto;
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .navbar-premium .nav-link {
        font-family: var(--nav-font-body);
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--nav-charcoal) !important;
        padding: 0.6rem 1rem !important;
        transition: var(--nav-transition);
        position: relative;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
        background: none;
        border: none;
        cursor: pointer;
    }

    .navbar-premium .nav-link::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        width: 0;
        height: 1.5px;
        background: var(--nav-gold);
        transition: all 0.35s ease;
        transform: translateX(-50%);
        opacity: 0;
    }

    .navbar-premium .nav-link:hover::after,
    .navbar-premium .nav-link.active::after {
        width: 60%;
        opacity: 1;
    }

    .navbar-premium .nav-link.active { font-weight: 600; }

    .navbar-premium .nav-link i {
        font-size: 0.7rem;
        opacity: 0.55;
        color: var(--nav-charcoal);
    }

    .navbar-premium .nav-link:hover i,
    .navbar-premium .nav-link.active i {
        opacity: 1;
        color: var(--nav-gold);
    }

    /* ============================================================
       BUTTONS & DROPDOWNS
       ============================================================ */
    .navbar-premium .btn-reservation {
        background: var(--nav-charcoal);
        color: #fff !important;
        padding: 0.55rem 1.5rem !important;
        font-weight: 500;
        font-size: 0.72rem;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        border: 1px solid var(--nav-charcoal);
        transition: var(--nav-transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        margin-left: 0.6rem;
    }

    .navbar-premium .btn-reservation:hover {
        background: var(--nav-gold);
        border-color: var(--nav-gold);
        transform: translateY(-2px);
    }

    .navbar-premium .btn-admin {
        background: transparent;
        border: 1px solid rgba(26, 26, 26, 0.2);
        color: var(--nav-charcoal) !important;
        padding: 0.45rem 1.1rem !important;
        font-weight: 500;
        font-size: 0.68rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        transition: var(--nav-transition);
        text-decoration: none;
        margin-left: 0.3rem;
    }

    .navbar-premium .btn-admin:hover {
        background: var(--nav-charcoal);
        color: #fff !important;
    }

    .nav-dropdown { position: relative; }

    .nav-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        border: 1px solid rgba(201, 162, 39, 0.12);
        box-shadow: var(--nav-shadow-lg);
        padding: 0.5rem 0;
        min-width: 210px;
        margin-top: 0.8rem;
        z-index: 10001;
        flex-direction: column;
    }

    .nav-dropdown-menu.show { display: flex; }

    .nav-dropdown-item {
        padding: 0.65rem 1.4rem;
        font-size: 0.78rem;
        color: var(--nav-charcoal);
        display: flex;
        align-items: center;
        gap: 0.6rem;
        text-decoration: none;
        transition: 0.2s;
    }

    .nav-dropdown-item:hover {
        background: var(--nav-cream-dark);
        padding-left: 1.8rem;
    }

    .nav-dropdown-item i { color: var(--nav-gold); width: 20px; }

    .navbar-premium .user-dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.35rem 0.7rem;
        background: transparent;
        border: none;
        cursor: pointer;
    }

    .navbar-premium .user-avatar-nav {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 1.5px solid var(--nav-gold);
    }

    .navbar-premium .user-name-nav {
        font-size: 0.78rem;
        font-weight: 500;
        color: var(--nav-charcoal);
    }

    /* ============================================================
       MOBILE RESPONSIVENESS (The Dropdown Fix)
       ============================================================ */
    @media (max-width: 991.98px) {
        .navbar-premium {
            width: 100vw;
            max-width: 100vw;
            overflow: visible; /* CRITICAL FIX: Allow the dropdown to overflow outside the navbar */
        }

        .navbar-premium .custom-toggler {
            display: block;
        }

        .navbar-premium .container {
            position: static; /* CRITICAL FIX: Allow absolute positioning to be relative to the navbar, not the container */
        }

        /* FORCE the menu to display as a dropdown block */
        .navbar-premium .navbar-collapse {
            position: absolute;
            top: 100%; /* Exactly below the navbar */
            left: 0;
            width: 100vw; 
            max-width: 100vw;
            background: #ffffff !important; /* Solid white/cream background */
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-top: 1px solid rgba(201, 162, 39, 0.2);
            z-index: 10000; /* Sit above everything */
            box-sizing: border-box;
            
            /* Hidden state */
            display: block !important; 
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            padding: 0 1.2rem;
            transition: max-height 0.4s ease-in-out, opacity 0.4s ease-in-out, padding 0.4s ease;
        }

        /* OPEN State */
        .navbar-premium .navbar-collapse.show {
            max-height: calc(100vh - 68px);
            overflow-y: auto;
            opacity: 1;
            padding: 1.5rem 1.2rem;
        }

        .navbar-premium .navbar-nav {
            flex-direction: column;
            align-items: stretch;
            width: 100%;
            gap: 0.5rem;
            margin: 0;
        }

        .navbar-premium .nav-item {
            width: 100%;
        }

        .navbar-premium .nav-link {
            padding: 0.8rem 0.8rem !important;
            width: 100%;
            justify-content: flex-start;
            font-size: 0.85rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .navbar-premium .nav-link::after { display: none; }

        .navbar-premium .nav-link.active {
            background: var(--nav-cream-dark);
            border-radius: 4px;
        }

        .navbar-premium .btn-reservation {
            margin-left: 0;
            width: 100%;
            justify-content: center;
            margin-top: 1rem;
        }

        .navbar-premium .btn-admin {
            margin-left: 0;
            width: 100%;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .navbar-premium .user-dropdown-toggle {
            width: 100%;
            justify-content: flex-start;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-left: 0.8rem;
        }

        .nav-dropdown-menu {
            position: static;
            box-shadow: none;
            border: none;
            background: var(--nav-cream);
            width: 100%;
            min-width: 100%;
            margin-top: 0;
            padding: 0.5rem 0;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-premium" id="premiumNavbar">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= SITE_URL ?>">
            <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo">
        </a>

        <!-- Hamburger Toggler -->
        <button class="custom-toggler" type="button" id="mobileMenuBtn" aria-label="Toggle navigation">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>

        <!-- Navbar items -->
        <div class="navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>booking.php">
                        <i class="fas fa-calendar-alt"></i> Rooms
                    </a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $profilePic = 'default.png';
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userPic = $stmt->fetch();
                        if ($userPic && !empty($userPic['profile_pic'])) {
                            $profilePic = $userPic['profile_pic'];
                        }
                    }
                    ?>
                    <li class="nav-item nav-dropdown">
                        <button class="nav-link user-dropdown-toggle" id="userDropdownBtn" type="button">
                            <img src="<?= SITE_URL ?>assets/uploads/profiles/<?= htmlspecialchars($profilePic) ?>" 
                                 class="user-avatar-nav" 
                                 onerror="this.src='<?= SITE_URL ?>assets/uploads/profiles/default.png'">
                            <span class="user-name-nav"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        </button>
                        <div class="nav-dropdown-menu" id="userDropdownMenu">
                            <a class="nav-dropdown-item" href="<?= SITE_URL ?>profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                            <a class="nav-dropdown-item" href="<?= SITE_URL ?>mybookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
                            <hr style="margin: 0.3rem 1.2rem; border-color: rgba(201,162,39,0.15);">
                            <a class="nav-dropdown-item" href="<?= SITE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="btn-admin" href="<?= SITE_URL ?>admin/">
                                <i class="fas fa-tachometer-alt"></i> Admin
                            </a>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>login.php">
                            <i class="fas fa-key"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="btn-reservation" href="<?= SITE_URL ?>booking.php">
                        <i class="fas fa-calendar-check"></i> Reservation
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Scroll Effect
        const navbar = document.getElementById('premiumNavbar');
        function handleScroll() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        // 2. Mobile Menu Toggler
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navbarNav = document.getElementById('navbarNav');

        if (mobileMenuBtn && navbarNav) {
            mobileMenuBtn.addEventListener('click', function() {
                navbarNav.classList.toggle('show');
                mobileMenuBtn.classList.toggle('active');
            });
        }

        // 3. Profile Dropdown
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation(); 
                userDropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                    userDropdownMenu.classList.remove('show');
                }
            });
        }
    });
</script>