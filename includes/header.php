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
</head>
<body>
    <nav class="navbar glass" id="main-nav">
        <div class="container nav-container">
            <a href="<?php echo BASE_URL; ?>/" class="brand" style="display: flex; align-items: center;">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="SAFAR Logo" style="height: 100px; width: auto; transition: transform 0.3s ease;">
            </a>
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>/explore.php">Explore</a></li>
                <li><a href="<?php echo BASE_URL; ?>/explore.php?type=tour">Tours</a></li>
                <li><a href="<?php echo BASE_URL; ?>/explore.php?type=hotel">Hotels</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'traveler'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/traveler.php">My Bookings</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'agency'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard/agency.php">Agency Dashboard</a></li>
                    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/index.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>/profile.php" class="nav-btn">Profile</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/logout.php" class="nav-btn btn-outline-nav">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/login.php" class="nav-btn btn-outline-nav">Log In</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/signup.php" class="nav-btn btn-gradient-nav">Sign Up</a></li>
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
