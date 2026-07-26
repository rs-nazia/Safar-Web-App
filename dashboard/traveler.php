<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('traveler');

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$tab = $_GET['tab'] ?? 'dashboard';

// Fetch traveler database profile details (avatar etc)
$user_stmt = $pdo->prepare("SELECT profile_image, role, bio, location FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_details = $user_stmt->fetch();

$avatar_url = !empty($user_details['profile_image']) ? BASE_URL . '/' . htmlspecialchars($user_details['profile_image']) : null;

// Query based on active tab
$bookings = [];
$favorited_packages = [];

if ($tab === 'dashboard') {
    // Fetch active bookings of the traveler with optional search
    $query = "
        SELECT b.id as booking_id, b.status, b.booking_date, p.id as package_id, p.title, p.price, p.location, p.image_url, a.company_name
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        JOIN agencies a ON p.agency_id = a.id
        WHERE b.traveler_id = ?
    ";
    $params = [$user_id];

    if ($search) {
        $query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $query .= " ORDER BY b.booking_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();

    // Fetch 3 trending tour packages dynamically
    $trending_stmt = $pdo->prepare("
        SELECT p.*, a.company_name 
        FROM packages p 
        JOIN agencies a ON p.agency_id = a.id 
        WHERE p.type = 'tour' 
        LIMIT 3
    ");
    $trending_stmt->execute();
    $trending_packages = $trending_stmt->fetchAll();
} elseif ($tab === 'favourites') {
    // Fetch all user favorited packages
    $fav_query = "
        SELECT p.*, a.company_name, f.created_at as favorited_at 
        FROM favorites f 
        JOIN packages p ON f.package_id = p.id 
        JOIN agencies a ON p.agency_id = a.id 
        WHERE f.user_id = ?
    ";
    $fav_params = [$user_id];
    if ($search) {
        $fav_query .= " AND (p.title LIKE ? OR p.location LIKE ?)";
        $fav_params[] = "%$search%";
        $fav_params[] = "%$search%";
    }
    $fav_query .= " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($fav_query);
    $stmt->execute($fav_params);
    $favorited_packages = $stmt->fetchAll();
}

// Fetch up to 4 schedule bookings for the right sidebar scheduler
$schedule_stmt = $pdo->prepare("
    SELECT b.booking_date, p.id as package_id, p.title, p.location, p.image_url
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    WHERE b.traveler_id = ? AND b.status IN ('pending', 'approved', 'confirmed')
    ORDER BY b.booking_date ASC
    LIMIT 4
");
$schedule_stmt->execute([$user_id]);
$schedule_bookings = $schedule_stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    /* Custom design matching Travigo dashboard */
    .dashboard-wrapper {
        display: flex;
        gap: 30px;
        max-width: 1350px;
        margin: 20px auto;
        padding: 0 20px;
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
    }
    
    /* Left Sidebar */
    .nav-sidebar {
        width: 250px;
        background: #ffffff;
        border-radius: 20px;
        padding: 30px 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 700px;
        align-self: flex-start;
        position: sticky;
        top: 100px;
    }

    .nav-logo-text {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-logo-text span {
        color: var(--primary, #FF7D4B);
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-menu-item {
        margin-bottom: 8px;
    }
    
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        color: #64748b;
        text-decoration: none;
        transition: all 0.25s ease;
    }
    
    .sidebar-link:hover {
        color: #0f172a;
        background: #f1f5f9;
    }
    
    .sidebar-link.active {
        background: var(--primary, #FF7D4B);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.25);
    }
    
    /* Discount Box */
    .discount-banner-card {
        background: #fff3ee;
        border-radius: 16px;
        padding: 20px;
        margin-top: 40px;
        position: relative;
        overflow: hidden;
        border: 1px dashed rgba(255, 125, 75, 0.2);
    }
    
    .discount-banner-card h4 {
        color: var(--primary, #FF7D4B);
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 5px;
    }
    
    .discount-banner-card p {
        color: #334155;
        font-size: 0.8rem;
        line-height: 1.4;
        margin-bottom: 12px;
    }
    
    .discount-circle-btn {
        width: 32px;
        height: 32px;
        background: var(--primary, #FF7D4B);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        border: none;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .discount-circle-btn:hover {
        transform: scale(1.1);
    }
    
    /* Center Main Section */
    .dashboard-center-panel {
        flex: 2;
        min-width: 500px;
    }
    
    .welcome-header {
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .welcome-header h2 {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    
    .welcome-header p {
        color: #64748b;
        font-size: 0.95rem;
        margin-top: 4px;
    }
    
    .search-bar-custom {
        display: flex;
        align-items: center;
        background: #ffffff;
        border-radius: 30px;
        padding: 5px 15px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 4px 10px rgba(0,0,0,0.01);
        width: 250px;
        transition: all 0.25s ease;
    }

    .search-bar-custom:focus-within {
        border-color: var(--primary, #FF7D4B);
        width: 280px;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.08);
    }
    
    .search-bar-custom input {
        border: none;
        outline: none;
        font-size: 0.9rem;
        padding: 8px;
        width: 100%;
        color: #334155;
    }
    
    .search-bar-custom i {
        color: #94a3b8;
        font-size: 1rem;
    }
    
    /* Trending Cards Row */
    .trending-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 35px;
    }
    
    .trending-card-item {
        height: 250px;
        border-radius: 20px;
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.03);
        transition: transform 0.25s ease;
    }
    
    .trending-card-item:hover {
        transform: translateY(-4px);
    }
    
    .trending-card-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 20px;
        background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.75) 100%);
        color: #ffffff;
        display: flex;
        flex-direction: column;
    }
    
    .trending-card-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 4px;
        letter-spacing: -0.3px;
    }
    
    .trending-card-location {
        font-size: 0.78rem;
        opacity: 0.85;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Bottom Layout Split */
    .bottom-split-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .bookings-list-card {
        flex: 1.5;
        background: #ffffff;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        min-width: 320px;
    }
    
    .bookings-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .bookings-header h3 {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
    }
    
    .booking-list-row {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .booking-list-row:last-child {
        border-bottom: none;
    }
    
    .booking-thumbnail {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .booking-details-info {
        flex: 1;
    }
    
    .booking-pkg-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #0f172a;
        margin-bottom: 3px;
    }
    
    .booking-pkg-meta {
        font-size: 0.78rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .booking-pkg-price {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        text-align: right;
    }
    
    .booking-pkg-price span {
        display: block;
        font-size: 0.7rem;
        color: #64748b;
        font-weight: normal;
        margin-top: 2px;
    }

    /* Promo Violet Card */
    .promo-violet-card {
        flex: 1;
        background: #1e1b4b;
        border-radius: 20px;
        padding: 30px 25px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        justify-content: space-between;
        color: #ffffff;
        min-width: 250px;
        min-height: 300px;
    }
    
    .promo-img-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        border: 1.5px dashed rgba(255,255,255,0.15);
    }
    
    .promo-violet-card h4 {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: 5px;
    }
    
    .promo-violet-card p {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-bottom: 20px;
    }
    
    .promo-green-btn {
        width: 100%;
        background: var(--primary, #FF7D4B);
        color: white;
        border-radius: 30px;
        font-weight: 700;
        padding: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.25);
    }

    .promo-green-btn:hover {
        background: #e06232;
        transform: translateY(-1px);
    }
    
    /* Right Sidebar Section */
    .dashboard-right-panel {
        width: 280px;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .user-profile-widget {
        background: #ffffff;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .widget-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary, #FF7D4B);
    }
    
    .widget-profile-info h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    
    .widget-profile-info span {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 500;
    }
    
    /* Calendar Card Widget */
    .calendar-widget-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
    }
    
    .calendar-header-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 700;
        font-size: 0.9rem;
        color: #0f172a;
        margin-bottom: 15px;
    }
    
    .calendar-grid-custom {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        text-align: center;
        font-size: 0.78rem;
    }
    
    .calendar-day-header {
        font-weight: 700;
        color: #94a3b8;
    }
    
    .calendar-day-number {
        padding: 5px;
        border-radius: 50%;
        cursor: pointer;
        color: #475569;
        font-weight: 500;
        transition: background 0.2s ease;
    }
    
    .calendar-day-number:hover {
        background: #f1f5f9;
    }
    
    .calendar-day-number.active-green {
        background: var(--primary, #FF7D4B);
        color: #ffffff;
        font-weight: 700;
    }

    .calendar-day-number.faded {
        color: #cbd5e1;
    }
    
    /* My Schedule Widget */
    .schedule-widget-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
    }
    
    .schedule-widget-card h3 {
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 15px;
    }
    
    .schedule-item-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        background: #fafafa;
        margin-bottom: 10px;
        border-left: 4.5px solid #009688;
    }
    
    .schedule-img-thumb {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        object-fit: cover;
    }
    
    .schedule-text-info {
        flex: 1;
    }
    
    .schedule-title-text {
        font-size: 0.8rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    
    .schedule-date-text {
        font-size: 0.72rem;
        color: #64748b;
    }

    /* Responsive Grid for Dashboard */
    @media (max-width: 1024px) {
        .dashboard-wrapper {
            flex-direction: column;
        }
        .nav-sidebar {
            width: 100%;
            min-height: auto;
            position: relative;
            top: 0;
        }
        .dashboard-right-panel {
            width: 100%;
        }
    }
</style>

<div class="dashboard-wrapper">
    <!-- Left Navigation Sidebar -->
    <aside class="nav-sidebar">
        <div>
            <div class="nav-logo-text">
                <i class="fas fa-paper-plane" style="color: var(--primary, #FF7D4B);"></i> SAFAR<span>.</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="traveler.php" class="sidebar-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="traveler.php" class="sidebar-link">
                        <i class="fas fa-route"></i> My Tickets
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="traveler.php?tab=favourites" class="sidebar-link <?php echo $tab === 'favourites' ? 'active' : ''; ?>">
                        <i class="far fa-heart"></i> Favourite
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="../pages/explore.php" class="sidebar-link">
                        <i class="far fa-comment-alt"></i> Message
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="traveler.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Transaction
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="../pages/profile.php" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
            
            <!-- Discount Card -->
            <div class="discount-banner-card">
                <h4>50% Discount!</h4>
                <p>Get a discount on certain days and don't miss it.</p>
                <a href="../pages/tours.php" class="discount-circle-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-arrow-right" style="font-size: 0.8rem;"></i>
                </a>
            </div>
        </div>
        
        <div>
            <a href="../pages/logout.php" class="sidebar-link" style="margin-top: 30px; color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </aside>

    <!-- Center main Content panel -->
    <div class="dashboard-center-panel">
        <div class="welcome-header">
            <div>
                <h2>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p>Welcome back and explore the world.</p>
            </div>
            
            <form method="GET" action="traveler.php" class="search-bar-custom">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <input type="text" name="search" placeholder="Search Destination..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <?php if ($tab === 'dashboard'): ?>
            <!-- Row of 3 Trending Destinations -->
            <div class="trending-cards-grid">
                <?php 
                $fallback_trending = [
                    ['title' => 'Goa', 'location' => 'Mumbai, India', 'img' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400&q=50'],
                    ['title' => 'Darjeeling', 'location' => 'West Bengal, India', 'img' => 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62?w=400&q=50'],
                    ['title' => 'Mount Everest', 'location' => 'Nepal', 'img' => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=400&q=50']
                ];
                
                for ($i = 0; $i < 3; $i++) {
                    if (isset($trending_packages[$i])) {
                        $pkg = $trending_packages[$i];
                        $img = $pkg['image_url'] ?: $fallback_trending[$i]['img'];
                        $title = $pkg['title'];
                        $loc = $pkg['location'];
                    } else {
                        $img = $fallback_trending[$i]['img'];
                        $title = $fallback_trending[$i]['title'];
                        $loc = $fallback_trending[$i]['location'];
                    }
                    ?>
                    <div class="trending-card-item" style="background-image: url('<?php echo htmlspecialchars($img); ?>');">
                        <div class="trending-card-overlay">
                            <span class="trending-card-title"><?php echo htmlspecialchars($title); ?></span>
                            <span class="trending-card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($loc); ?></span>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- Bookings & Promotion Split -->
            <div class="bottom-split-container">
                <!-- Traveler Bookings list -->
                <div class="bookings-list-card">
                    <div class="bookings-header">
                        <h3>Best Destination</h3>
                        <a href="../pages/tours.php" style="font-size: 0.85rem; color: #00c288; font-weight: 700; text-decoration: none;">See All <i class="fas fa-chevron-right" style="font-size: 0.75rem; margin-left: 2px;"></i></a>
                    </div>
                    
                    <?php if (count($bookings) > 0): ?>
                        <div style="display: flex; flex-direction: column;">
                            <?php foreach ($bookings as $booking): ?>
                                <?php 
                                $pkg_img = $booking['image_url'] ?: 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=100&q=50';
                                $status_bg = '#fef3c7'; // pending yellow
                                $status_fg = '#d97706';
                                if ($booking['status'] === 'approved' || $booking['status'] === 'confirmed') {
                                    $status_bg = '#d1fae5';
                                    $status_fg = '#059669';
                                } elseif ($booking['status'] === 'cancelled' || $booking['status'] === 'rejected') {
                                    $status_bg = '#fee2e2';
                                    $status_fg = '#dc2626';
                                }
                                ?>
                                <a href="../pages/package-details.php?id=<?php echo $booking['package_id']; ?>" class="booking-list-row" style="text-decoration: none; cursor: pointer;">
                                    <img src="<?php echo htmlspecialchars($pkg_img); ?>" alt="Thumbnail" class="booking-thumbnail">
                                    <div class="booking-details-info">
                                        <div class="booking-pkg-title"><?php echo htmlspecialchars($booking['title']); ?></div>
                                        <div class="booking-pkg-meta">
                                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?></span>
                                            <span style="margin-left: 10px; background: <?php echo $status_bg; ?>; color: <?php echo $status_fg; ?>; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                                <?php echo htmlspecialchars($booking['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="booking-pkg-price">
                                        $<?php echo number_format($booking['price'], 2); ?>
                                        <span>/ tour package</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #64748b; font-size: 0.9rem; padding: 20px 0;">No active reservations. <a href="../pages/tours.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Explore places now</a></p>
                    <?php endif; ?>
                </div>

                <!-- Purple Promotion Card -->
                <div class="promo-violet-card">
                    <div class="promo-img-circle">
                        <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300c288'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z'/></svg>" style="width: 60px; height: 60px;" />
                    </div>
                    <div>
                        <h4>Let's Explore The Beauty</h4>
                        <p>Get special offers & travel updates directly from our local agents.</p>
                    </div>
                    <a href="../pages/tours.php" class="promo-green-btn" style="text-decoration: none; display: block; text-align: center; font-weight: 700;">Join Now</a>
                </div>
            </div>
        <?php elseif ($tab === 'favourites'): ?>
            <!-- Favourites Tab Content -->
            <div style="background: #ffffff; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.04);">
                <div style="border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0;"><i class="fas fa-heart" style="color: var(--primary);"></i> My Bookmarked Favorites</h2>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">Packages you have pinned for quick booking later.</p>
                    </div>
                    <span class="badge badge-approved" style="font-size: 0.95rem; padding: 6px 15px;"><?php echo count($favorited_packages); ?> Bookmarks</span>
                </div>

                <?php if (count($favorited_packages) > 0): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ($favorited_packages as $pkg): ?>
                            <?php 
                            $pkg_img = $pkg['image_url'] ?: 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=400&q=50';
                            $type_label = $pkg['type'] ? ucfirst($pkg['type']) : 'Tour';
                            $type_bg = ($pkg['type'] === 'hotel') ? '#ecfdf5' : '#fff7ed';
                            $type_text = ($pkg['type'] === 'hotel') ? '#047857' : '#c2410c';
                            ?>
                            <div style="background: var(--white); border-radius: 16px; border: 1px solid #f1f5f9; overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); position: relative; transition: transform 0.2s ease;"
                                 onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='none';">
                                
                                <!-- Floating Heart Favorite toggler -->
                                <button type="button" class="fav-heart-btn active" data-id="<?php echo $pkg['id']; ?>" 
                                        style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: var(--shadow-sm); color: #ef4444; font-size: 1rem; z-index: 5;">
                                    <i class="fas fa-heart"></i>
                                </button>

                                <div style="height: 150px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($pkg_img); ?>');"></div>
                                <div style="padding: 15px; display: flex; flex-direction: column; flex-grow: 1;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 8px;">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                                        <span style="background: <?php echo $type_bg; ?>; color: <?php echo $type_text; ?>; font-weight: 700; padding: 2px 8px; border-radius: 8px; text-transform: uppercase; font-size: 0.65rem;">
                                            <?php echo $type_label; ?>
                                        </span>
                                    </div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 8px; line-height: 1.4; flex-grow: 1;"><?php echo htmlspecialchars($pkg['title']); ?></h4>
                                    <div style="font-weight: 800; color: var(--primary); font-size: 1.15rem; margin-bottom: 15px;">
                                        $<?php echo number_format($pkg['price'], 2); ?>
                                        <?php if ($pkg['type'] === 'hotel'): ?>
                                            <span style="font-size: 0.75rem; font-weight: normal; color: var(--text-muted);">/ night</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="../pages/package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1; font-size: 0.8rem; padding: 6px; border-radius: 8px;">Details</a>
                                        <a href="../pages/package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1; font-size: 0.8rem; padding: 6px; border-radius: 8px;">Book Now</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                        <i class="far fa-heart" style="font-size: 2.5rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                        <p style="font-size: 0.95rem; margin-bottom: 15px;">You have no bookmarked favorites yet.</p>
                        <a href="../pages/tours.php" class="btn" style="border-radius: 8px; padding: 8px 20px;">Explore Tours Catalog</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Profile & Calendar Widgets -->
    <div class="dashboard-right-panel">
        <!-- User Profile Widget -->
        <div class="user-profile-widget">
            <?php if ($avatar_url): ?>
                <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="widget-avatar">
            <?php else: ?>
                <div class="widget-avatar" style="background: #f0fdfa; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                    <i class="fas fa-user" style="font-size: 1.3rem;"></i>
                </div>
            <?php endif; ?>
            <div class="widget-profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                <span>Travel Enthusiast</span>
            </div>
        </div>

        <!-- Custom Calendar Widget -->
        <div class="calendar-widget-card">
            <div class="calendar-header-custom">
                <span>June 2026</span>
                <div style="display: flex; gap: 8px; color: #64748b; cursor: pointer;">
                    <i class="fas fa-chevron-left"></i>
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
            
            <div class="calendar-grid-custom">
                <span class="calendar-day-header">S</span>
                <span class="calendar-day-header">M</span>
                <span class="calendar-day-header">T</span>
                <span class="calendar-day-header">W</span>
                <span class="calendar-day-header">T</span>
                <span class="calendar-day-header">F</span>
                <span class="calendar-day-header">S</span>
                
                <!-- Mock calendar week days matching mockup design -->
                <span class="calendar-day-number faded">31</span>
                <span class="calendar-day-number">1</span>
                <span class="calendar-day-number">2</span>
                <span class="calendar-day-number">3</span>
                <span class="calendar-day-number">4</span>
                <span class="calendar-day-number">5</span>
                <span class="calendar-day-number">6</span>
                
                <span class="calendar-day-number">7</span>
                <span class="calendar-day-number active-green">8</span>
                <span class="calendar-day-number">9</span>
                <span class="calendar-day-number">10</span>
                <span class="calendar-day-number">11</span>
                <span class="calendar-day-number">12</span>
                <span class="calendar-day-number">13</span>
                
                <span class="calendar-day-number">14</span>
                <span class="calendar-day-number">15</span>
                <span class="calendar-day-number">16</span>
                <span class="calendar-day-number">17</span>
                <span class="calendar-day-number">18</span>
                <span class="calendar-day-number">19</span>
                <span class="calendar-day-number">20</span>
                
                <span class="calendar-day-number">21</span>
                <span class="calendar-day-number">22</span>
                <span class="calendar-day-number">23</span>
                <span class="calendar-day-number">24</span>
                <span class="calendar-day-number">25</span>
                <span class="calendar-day-number">26</span>
                <span class="calendar-day-number">27</span>
            </div>
        </div>

        <!-- Custom Scheduler Itinerary timeline widget -->
        <div class="schedule-widget-card">
            <h3>My Schedule</h3>
            
            <?php if (count($schedule_bookings) > 0): ?>
                <?php 
                $count = 0;
                foreach ($schedule_bookings as $booking): 
                    $count++;
                    $th_img = $booking['image_url'] ?: 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=100&q=50';
                    $dates_label = date('M d', strtotime($booking['booking_date'])) . ' - ' . date('M d, Y', strtotime($booking['booking_date'] . ' + 7 days'));
                    ?>
                    <a href="../pages/package-details.php?id=<?php echo $booking['package_id']; ?>" class="schedule-item-row" style="text-decoration: none; display: flex; border-left-color: <?php echo ($count % 2 === 0) ? '#ff7d4b' : '#009688'; ?>; cursor: pointer;">
                        <img src="<?php echo htmlspecialchars($th_img); ?>" alt="Thumb" class="schedule-img-thumb">
                        <div class="schedule-text-info">
                            <div class="schedule-title-text"><?php echo htmlspecialchars($booking['title']); ?></div>
                            <div class="schedule-date-text"><?php echo $dates_label; ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; font-size: 0.8rem; padding: 20px 0;">
                    <i class="far fa-calendar-alt" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                    <p>No trip itineraries scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AJAX Favorites Toggler Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fav-heart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const pkgId = this.getAttribute('data-id');
            const icon = this.querySelector('i');
            
            const formData = new FormData();
            formData.append('package_id', pkgId);
            
            fetch('../api/toggle_favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        icon.className = 'fas fa-heart';
                        this.style.color = '#ef4444';
                    } else {
                        this.classList.remove('active');
                        icon.className = 'far fa-heart';
                        this.style.color = '#64748b';
                        
                        // If we are currently inside the Favourites tab, remove the card dynamically
                        <?php if ($tab === 'favourites'): ?>
                            this.closest('div[style*="display: flex"]').remove();
                            // Refresh if empty
                            setTimeout(() => {
                                const container = document.querySelector('.dashboard-center-panel div[style*="display: grid"]');
                                if (container && container.children.length === 0) {
                                    window.location.reload();
                                }
                            }, 300);
                        <?php endif; ?>
                    }
                } else {
                    alert(data.message || 'An error occurred.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection error. Please try again.');
            });
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
