<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAFAR - Travel Booking & Management</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Dropdown Navigation Styles */
        .nav-dropdown-wrapper {
            position: relative;
        }
        
        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 14px;
            padding: 8px 0;
            min-width: 160px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            list-style: none;
            margin: 0;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
        }
        
        .nav-dropdown-wrapper:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        
        .nav-dropdown-menu li {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .nav-dropdown-menu li a {
            display: flex !important;
            align-items: center;
            padding: 10px 20px !important;
            color: #475569 !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
            background: transparent !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        .nav-dropdown-menu li a:hover {
            background: rgba(255, 125, 75, 0.06) !important;
            color: var(--primary, #FF7D4B) !important;
            padding-left: 24px !important;
        }
    </style>
</head>
<body>
    <nav class="navbar glass" id="main-nav">
        <div class="container nav-container">
            <a href="<?php echo BASE_URL; ?>/pages/index.php" class="brand" style="display: flex; align-items: center;">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="SAFAR Logo" style="height: 100px; width: auto; transition: transform 0.3s ease;">
            </a>
            <ul class="nav-links">
                <li class="nav-dropdown-wrapper">
                    <a href="<?php echo BASE_URL; ?>/pages/explore.php">Explore <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i></a>
                    <ul class="nav-dropdown-menu">
                        <li><a href="<?php echo BASE_URL; ?>/pages/tours.php"><i class="fas fa-route" style="margin-right: 8px;"></i> Tours</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/pages/hotels.php"><i class="fas fa-hotel" style="margin-right: 8px;"></i> Hotels</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo BASE_URL; ?>/pages/agencies.php">Agencies</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'traveler'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/traveler.php">My Bookings</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'agency'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/agency.php">Agency Dashboard</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/index.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>/pages/profile.php" class="nav-btn">Profile</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/logout.php" class="nav-btn btn-outline-nav">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/pages/login.php" class="nav-btn btn-outline-nav">Log In</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/signup.php" class="nav-btn btn-gradient-nav">Sign Up</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    <main class="main-content">
    
    <script>
        // Sticky Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('main-nav');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    </script>
