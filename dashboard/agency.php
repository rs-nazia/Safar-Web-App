<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('agency');

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'packages'; // default to packages to match mockup view

// Get Agency Details
$stmt = $pdo->prepare("SELECT id, status, company_name FROM agencies WHERE user_id = ?");
$stmt->execute([$user_id]);
$agency = $stmt->fetch();

if (!$agency) {
    die("Error: Agency profile not found.");
}

$agency_id = $agency['id'];
$is_verified = $agency['status'] === 'verified';

// Get current user profile details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();

$avatar_url = !empty($current_user['profile_image']) ? BASE_URL . '/' . htmlspecialchars($current_user['profile_image']) : null;

// Handle package deletions
if (isset($_GET['delete_package'])) {
    $del_id = $_GET['delete_package'];
    // Verify ownership
    $check_stmt = $pdo->prepare("SELECT id FROM packages WHERE id = ? AND agency_id = ?");
    $check_stmt->execute([$del_id, $agency_id]);
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$del_id]);
        header("Location: agency.php?tab=packages&msg=deleted");
        exit();
    }
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $booking_id = $_POST['booking_id'];
    
    // Verify booking package ownership
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ? 
        WHERE id = ? AND package_id IN (SELECT id FROM packages WHERE agency_id = ?)
    ");
    $stmt->execute([$action, $booking_id, $agency_id]);
    header("Location: agency.php?tab=bookings&msg=booking_updated");
    exit();
}

// Handle profile updates (Settings Tab)
$profile_success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    $location = trim($_POST['location']);

    if (!empty($name) && !empty($email)) {
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["profile_image"]["tmp_name"];
            $filename = time() . '_' . basename($_FILES["profile_image"]["name"]);
            $destination = "../uploads/" . $filename;
            if (move_uploaded_file($tmp_name, $destination)) {
                $profile_image = "uploads/" . $filename;
            }
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            if ($profile_image) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, profile_image = ?, phone = ?, bio = ?, location = ? WHERE id = ?");
                $stmt->execute([name, $email, $hashed, $profile_image, $phone, $bio, $location, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, bio = ?, location = ? WHERE id = ?");
                $stmt->execute([$name, $email, $hashed, $phone, $bio, $location, $user_id]);
            }
        } else {
            if ($profile_image) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_image = ?, phone = ?, bio = ?, location = ? WHERE id = ?");
                $stmt->execute([$name, $email, $profile_image, $phone, $bio, $location, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, location = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $bio, $location, $user_id]);
            }
        }
        $_SESSION['user_name'] = $name;
        header("Location: agency.php?tab=settings&msg=profile_updated");
        exit();
    }
}

// Fetch Agency's Packages
$stmt = $pdo->prepare("SELECT * FROM packages WHERE agency_id = ? ORDER BY created_at DESC");
$stmt->execute([$agency_id]);
$packages = $stmt->fetchAll();

// Fetch Booking Requests for Agency
$stmt = $pdo->prepare("
    SELECT b.id as booking_id, b.status, b.booking_date, p.title, p.price, p.type as package_type, u.name as traveler_name, u.email as traveler_email, u.phone as traveler_phone, u.profile_image
    FROM bookings b
    JOIN packages p ON b.package_id = p.id
    JOIN users u ON b.traveler_id = u.id
    WHERE p.agency_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$agency_id]);
$bookings = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    body {
        background-color: #f3f4f6;
        font-family: 'Inter', sans-serif;
    }

    /* Core Agency Dashboard Layout Grid */
    .agency-wrapper {
        display: flex;
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 20px;
        gap: 25px;
    }

    /* Left Sidebar Styling matching Hale Travels mockup */
    .agency-sidebar-custom {
        width: 250px;
        background: #ffffff;
        border-radius: 24px;
        padding: 35px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 750px;
        align-self: flex-start;
        position: sticky;
        top: 100px;
    }

    .sidebar-brand-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--primary, #FF7D4B); /* Brand orange */
        margin-bottom: 40px;
        padding: 0 10px;
    }

    .sidebar-menu-custom {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }

    .sidebar-item-custom {
        margin-bottom: 8px;
    }

    .sidebar-link-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 14px;
        font-weight: 600;
        font-size: 0.95rem;
        color: #64748b;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .sidebar-link-custom:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .sidebar-link-custom.active {
        background: var(--primary, #FF7D4B);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.25);
    }

    /* Right main panel */
    .agency-main-panel {
        flex: 1;
        min-width: 0;
    }

    /* Top header search widgets bar */
    .agency-top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        background: #ffffff;
        padding: 15px 30px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .header-left-search {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .oval-search-box {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border-radius: 30px;
        padding: 5px 15px;
        border: 1px solid #e2e8f0;
        width: 200px;
    }

    .oval-search-box input {
        border: none;
        outline: none;
        font-size: 0.85rem;
        background: transparent;
        width: 100%;
        color: #334155;
    }

    .oval-search-box i {
        color: #94a3b8;
    }

    .header-datepicker-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        padding: 8px 16px;
        border-radius: 30px;
        border: 1px solid #e2e8f0;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
    }

    .icon-shortcut-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .shortcut-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .shortcut-icon-btn:hover {
        background: var(--primary, #FF7D4B);
        color: #ffffff;
        border-color: var(--primary, #FF7D4B);
    }

    .search-action-btn-green {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--primary, #FF7D4B);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(255, 125, 75, 0.2);
    }

    .header-user-badge-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .badge-icon-wrap {
        position: relative;
        font-size: 1.15rem;
        color: #64748b;
        cursor: pointer;
    }

    .badge-icon-counter {
        position: absolute;
        top: -6px;
        right: -6px;
        background: var(--primary, #FF7D4B);
        color: white;
        font-size: 0.65rem;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }

    .header-user-avatar-card {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-user-avatar-card img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .header-user-name {
        font-weight: 700;
        font-size: 0.88rem;
        color: #0f172a;
    }

    /* Add New Package Card Banner */
    .add-package-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .add-package-info h3 {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 5px;
    }

    .add-package-info p {
        color: #64748b;
        font-size: 0.9rem;
    }

    .floating-plus-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--primary, #FF7D4B);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.3);
        transition: transform 0.2s ease;
    }

    .floating-plus-btn:hover {
        transform: scale(1.08);
        color: #ffffff;
    }

    /* Main Content block */
    .all-packages-panel {
        background: #ffffff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .all-packages-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .all-packages-header h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .all-packages-header p {
        color: #94a3b8;
        font-size: 0.88rem;
        margin-top: 5px;
    }

    /* Premium Hale Travels table styles */
    .hale-travels-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .hale-travels-table th {
        padding: 12px 16px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #0f172a;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .hale-travels-table td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        color: #475569;
        vertical-align: middle;
    }

    .hale-travels-table tr:last-child td {
        border-bottom: none;
    }

    .host-cell-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .host-cell-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .host-cell-name {
        font-weight: 700;
        color: #0f172a;
    }

    .table-badge-type {
        font-weight: 600;
        color: #0f172a;
    }

    .row-actions-btn {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 5px;
        position: relative;
    }

    .row-actions-btn:hover {
        color: #0f172a;
    }

    /* Dropdown Actions Menu matching mockup */
    .dropdown-action-menu {
        display: none;
        position: absolute;
        right: 20px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
        z-index: 100;
        width: 110px;
        padding: 6px;
        text-align: left;
    }

    .dropdown-action-menu.active {
        display: block;
    }

    .dropdown-action-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        border-radius: 8px;
    }

    .dropdown-action-item:hover {
        background: #f8fafc;
        color: var(--primary, #FF7D4B);
    }

    .dropdown-action-item.delete:hover {
        color: #ef4444;
        background: #fef2f2;
    }

    @media (max-width: 1024px) {
        .agency-wrapper {
            flex-direction: column;
        }
        .agency-sidebar-custom {
            width: 100%;
            min-height: auto;
            position: relative;
            top: 0;
        }
        .agency-top-header {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        .header-left-search {
            flex-wrap: wrap;
        }
    }
</style>

<div class="agency-wrapper">
    <!-- Left Navigation Sidebar -->
    <aside class="agency-sidebar-custom">
        <div>
            <div class="sidebar-brand-custom">
                <i class="fas fa-suitcase-rolling" style="font-size: 1.7rem;"></i>
                <span>Hale Travels</span>
            </div>
            
            <ul class="sidebar-menu-custom">
                <li class="sidebar-item-custom">
                    <a href="agency.php?tab=packages" class="sidebar-link-custom <?php echo $tab === 'packages' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="agency.php?tab=packages" class="sidebar-link-custom <?php echo $tab === 'packages' ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i> Tour Packages
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="agency.php?tab=bookings" class="sidebar-link-custom <?php echo $tab === 'bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marked-alt"></i> Tours
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <ul class="sidebar-menu-custom" style="margin-bottom: 20px;">
                <li class="sidebar-item-custom">
                    <a href="agency.php?tab=settings" class="sidebar-link-custom <?php echo $tab === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
            <div style="border-top: 1px solid #f1f5f9; padding-top: 15px;">
                <a href="../pages/logout.php" class="sidebar-link-custom" style="color: #ef4444;">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Workspace -->
    <main class="agency-main-panel">
        
        <!-- Top header action bar matching mockup widgets -->
        <header class="agency-top-header">
            <div class="header-left-search">
                <div class="oval-search-box">
                    <i class="fas fa-map-marker-alt" style="color: var(--primary, #FF7D4B);"></i>
                    <input type="text" placeholder="Search">
                </div>
                
                <div class="header-datepicker-badge">
                    <i class="far fa-calendar-alt" style="color: var(--primary, #FF7D4B);"></i>
                    <span>24.01.2020 - 24.02.2020</span>
                    <i class="fas fa-chevron-down" style="font-size: 0.65rem; margin-left: 5px;"></i>
                </div>
                
                <div class="icon-shortcut-row">
                    <div class="shortcut-icon-btn"><i class="fas fa-random"></i></div>
                    <div class="shortcut-icon-btn"><i class="fas fa-walking"></i></div>
                    <div class="shortcut-icon-btn"><i class="fas fa-fish"></i></div>
                    <div class="shortcut-icon-btn"><i class="fas fa-wind"></i></div>
                </div>
                
                <button type="button" class="search-action-btn-green" style="background: var(--primary, #FF7D4B); box-shadow: 0 4px 10px rgba(255, 125, 75, 0.2);">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="header-user-badge-right">
                <div class="badge-icon-wrap">
                    <i class="far fa-comment-alt"></i>
                    <span class="badge-icon-counter" style="background: var(--primary, #FF7D4B);">5</span>
                </div>
                <div class="badge-icon-wrap">
                    <i class="far fa-bell"></i>
                    <span class="badge-icon-counter" style="background: var(--primary, #FF7D4B);">3</span>
                </div>
                
                <div class="header-user-avatar-card">
                    <div class="header-user-name">Jhon Doe</div>
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo $avatar_url; ?>" alt="Avatar">
                    <?php else: ?>
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&h=80&fit=crop&q=80" alt="Avatar">
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Messages / Alerts -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #fff3ee; color: var(--primary, #FF7D4B); padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; border: 1px solid rgba(255, 125, 75, 0.2);">
                <?php 
                $msg = $_GET['msg'];
                if ($msg === 'deleted') echo 'Travel package successfully deleted.';
                elseif ($msg === 'booking_updated') echo 'Reservation booking request status updated successfully.';
                elseif ($msg === 'profile_updated') echo 'Agency administrator profile settings updated successfully.';
                ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'packages'): ?>
            <!-- PACKAGES TAB VIEW -->
            
            <!-- Add New Package Card Banner -->
            <section class="add-package-card">
                <div class="add-package-info">
                    <h3>Add New Package</h3>
                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting</p>
                </div>
                <a href="manage-package.php" class="floating-plus-btn">
                    <i class="fas fa-plus"></i>
                </a>
            </section>

            <!-- All Packages Table Listing -->
            <section class="all-packages-panel">
                <div class="all-packages-header">
                    <div>
                        <h3>All Package</h3>
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting</p>
                    </div>
                    <div style="display: flex; gap: 8px; color: #cbd5e1; font-size: 1.15rem; cursor: pointer;">
                        <i class="fas fa-th-large" style="color: var(--primary, #FF7D4B);"></i>
                        <i class="fas fa-bars"></i>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="hale-travels-table">
                        <thead>
                            <tr>
                                <th>Host</th>
                                <th>Location</th>
                                <th>Package name</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $index => $pkg): ?>
                                <?php 
                                $host_img = !empty($current_user['profile_image']) ? BASE_URL . '/' . htmlspecialchars($current_user['profile_image']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80&h=80&fit=crop&q=80';
                                // Parse duration from description or fallback to mock duration matching image layout
                                $duration = '3D 2N';
                                if (preg_index_match('/(\d+)\s*d\s*(\d+)\s*n/i', $pkg['description'], $matches)) {
                                    $duration = strtoupper($matches[1] . 'D ' . $matches[2] . 'N');
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="host-cell-wrap">
                                            <img src="<?php echo $host_img; ?>" alt="Host Avatar" class="host-cell-avatar">
                                            <span class="host-cell-name"><?php echo htmlspecialchars($current_user['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pkg['location']); ?></td>
                                    <td style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($pkg['title']); ?></td>
                                    <td class="table-badge-type">Group</td>
                                    <td style="font-weight: 600; color: #0f172a;"><?php echo $duration; ?></td>
                                    <td><?php echo htmlspecialchars($current_user['phone'] ?: '(164)224-5824'); ?></td>
                                    <td style="font-size: 0.82rem; font-weight: 500;"><?php echo htmlspecialchars($current_user['email']); ?></td>
                                    <td style="text-align: right; position: relative;">
                                        <button type="button" class="row-actions-btn" onclick="toggleDropdown(event, 'dropdown-<?php echo $pkg['id']; ?>')">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        
                                        <!-- Actions Dropdown matching mockup view -->
                                        <div class="dropdown-action-menu" id="dropdown-<?php echo $pkg['id']; ?>">
                                            <a href="../pages/package-details.php?id=<?php echo $pkg['id']; ?>" class="dropdown-action-item">
                                                <i class="far fa-eye"></i> View
                                            </a>
                                            <a href="manage-package.php?id=<?php echo $pkg['id']; ?>" class="dropdown-action-item">
                                                <i class="far fa-edit" style="font-size: 0.75rem;"></i> Edit
                                            </a>
                                            <a href="agency.php?delete_package=<?php echo $pkg['id']; ?>" class="dropdown-action-item delete" onclick="return confirm('Are you sure you want to delete this package?');">
                                                <i class="far fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($packages) === 0): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px 0;">No active packages listed. Get started by adding a package!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($tab === 'bookings'): ?>
            <!-- BOOKINGS TAB VIEW -->
            <section class="all-packages-panel">
                <div class="all-packages-header" style="border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
                    <div>
                        <h3>Customer Trip Reservations</h3>
                        <p>Manage reservation booking requests from travelers booking your stay or tour packages.</p>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="hale-travels-table">
                        <thead>
                            <tr>
                                <th>Traveler</th>
                                <th>Contact Email</th>
                                <th>Phone</th>
                                <th>Package Name</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <?php 
                                $trav_img = !empty($b['profile_image']) ? BASE_URL . '/' . htmlspecialchars($b['profile_image']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80&h=80&fit=crop&q=80';
                                ?>
                                <tr>
                                    <td>
                                        <div class="host-cell-wrap">
                                            <img src="<?php echo $trav_img; ?>" alt="Avatar" class="host-cell-avatar">
                                            <span class="host-cell-name"><?php echo htmlspecialchars($b['traveler_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['traveler_email']); ?></td>
                                    <td><?php echo htmlspecialchars($b['traveler_phone'] ?: 'N/A'); ?></td>
                                    <td style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($b['title']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                    <td>
                                        <span style="font-weight: 700; color: <?php echo $b['status'] === 'approved' ? '#10b981' : ($b['status'] === 'rejected' ? '#ef4444' : '#f59e0b'); ?>;">
                                            <?php echo ucfirst($b['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display: inline-flex; gap: 5px; margin: 0;">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                                <button type="submit" name="action" value="reject" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem; border-radius: 8px;">Reject</button>
                                                <button type="submit" name="action" value="approve" class="btn" style="padding: 5px 12px; font-size: 0.8rem; border-radius: 8px; background: #00c288; color: #ffffff;">Approve</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;"><i class="fas fa-check"></i> Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($bookings) === 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px 0;">No booking reservation records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($tab === 'settings'): ?>
            <!-- SETTINGS TAB VIEW -->
            <section class="all-packages-panel" style="max-width: 700px; margin: 0 auto;">
                <div class="all-packages-header" style="border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
                    <div>
                        <h3>Agency Profile Settings</h3>
                        <p>Modify registration details, description bios, locations, and password credentials.</p>
                    </div>
                </div>

                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="position: relative; display: inline-block;">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo $avatar_url; ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary, #FF7D4B);">
                            <?php else: ?>
                                <div style="width: 100px; height: 100px; border-radius: 50%; background: #fff3ee; display: flex; align-items: center; justify-content: center; color: var(--primary, #FF7D4B); font-size: 2.2rem; border: 3px solid var(--primary, #FF7D4B);">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 15px;">
                            <label for="profile_image" style="background: #f1f5f9; color: #475569; padding: 8px 16px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; border: 1px solid #cbd5e1;">Change Logo</label>
                            <input type="file" id="profile_image" name="profile_image" style="display: none;" accept="image/*">
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">Agency Name</label>
                            <input type="text" name="name" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem;" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">Email Address</label>
                            <input type="email" name="email" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem;" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">Phone Contact</label>
                            <input type="text" name="phone" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem;" value="<?php echo htmlspecialchars($current_user['phone']); ?>">
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">Headquarters Location</label>
                            <input type="text" name="location" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem;" value="<?php echo htmlspecialchars($current_user['location']); ?>">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">Agency Bio / Description</label>
                        <textarea name="bio" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem; height: 100px; font-family: 'Inter', sans-serif; resize: none;"><?php echo htmlspecialchars($current_user['bio']); ?></textarea>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-weight: 700; color: #475569; font-size: 0.85rem; margin-bottom: 6px;">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" style="width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; font-size: 0.92rem;" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn" style="width: 100%; padding: 12px; font-weight: 700; font-size: 1rem; border-radius: 10px; background: var(--primary, #FF7D4B); color: #ffffff; box-shadow: 0 4px 15px rgba(255, 125, 75, 0.25);">Save Settings Updates</button>
                </form>
            </section>
        <?php endif; ?>

    </main>
</div>

<!-- Helper Dropdown script matching Expinova/Hale action triggers -->
<script>
function toggleDropdown(event, dropdownId) {
    event.preventDefault();
    event.stopPropagation();
    
    // Close other active dropdowns
    document.querySelectorAll('.dropdown-action-menu').forEach(menu => {
        if (menu.id !== dropdownId) {
            menu.classList.remove('active');
        }
    });
    
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close dropdowns on clicking elsewhere
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-action-menu').forEach(menu => {
        menu.classList.remove('active');
    });
});
</script>

<?php 
// Helper PHP function to perform regex matches directly in template
function preg_index_match($pattern, $subject, &$matches) {
    return preg_match($pattern, $subject, $matches);
}
require_once '../includes/footer.php'; 
?>
