<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('admin');

$tab = $_GET['tab'] ?? 'overview';

// Handle agency actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['agency_id'])) {
    $action = $_POST['action'] === 'verify' ? 'verified' : 'rejected';
    $agency_id = $_POST['agency_id'];
    
    $stmt = $pdo->prepare("UPDATE agencies SET status = ? WHERE id = ?");
    $stmt->execute([$action, $agency_id]);
    header("Location: index.php?tab=" . urlencode($tab) . "&msg=agency_updated");
    exit();
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'], $_POST['booking_id'])) {
    $baction = $_POST['booking_action'] === 'approve' ? 'approved' : 'rejected';
    $bid = $_POST['booking_id'];
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$baction, $bid]);
    header("Location: index.php?tab=" . urlencode($tab) . "&msg=booking_updated");
    exit();
}

// Handle package actions
if (isset($_GET['delete_package'])) {
    $del_id = $_GET['delete_package'];
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: index.php?tab=packages&msg=package_deleted");
    exit();
}

// Fetch general stats
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$packages_count = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$bookings_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Fetch pending agencies
$stmt = $pdo->query("SELECT a.*, u.name, u.email FROM agencies a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending'");
$pending_agencies = $stmt->fetchAll();

// Fetch all verified agencies
$stmt = $pdo->query("SELECT a.*, u.name, u.email FROM agencies a JOIN users u ON a.user_id = u.id WHERE a.status = 'verified'");
$verified_agencies = $stmt->fetchAll();

// Fetch all users
$stmt = $pdo->query("SELECT id, name, email, role, created_at, profile_image FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();

// Fetch all bookings
$stmt = $pdo->query("
    SELECT b.*, u.name as traveler_name, u.profile_image, p.title as package_title, p.type as package_type, a.company_name 
    FROM bookings b 
    JOIN users u ON b.traveler_id = u.id 
    JOIN packages p ON b.package_id = p.id 
    JOIN agencies a ON p.agency_id = a.id 
    ORDER BY b.booking_date DESC
");
$all_bookings = $stmt->fetchAll();

// Fetch all packages
$stmt = $pdo->query("SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id ORDER BY p.created_at DESC");
$all_packages = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    body {
        background-color: #f3f4f6;
        font-family: 'Inter', sans-serif;
    }

    /* Core Admin Layout Grid matching Expinova Mockup */
    .admin-container {
        display: flex;
        max-width: 1400px;
        margin: 20px auto;
        padding: 0 20px;
        gap: 25px;
    }

    /* Left Sidebar Styling */
    .admin-sidebar-custom {
        width: 260px;
        background: #ffffff;
        border-radius: 24px;
        padding: 35px 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 850px;
        align-self: flex-start;
        position: sticky;
        top: 100px;
    }

    .sidebar-brand-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary, #FF7D4B); /* Brand orange */
        margin-bottom: 30px;
        padding: 0 10px;
    }
    
    .sidebar-section-header {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.5px;
        margin: 20px 10px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-menu-custom {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-item-custom {
        margin-bottom: 6px;
    }

    .sidebar-link-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
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

    /* Main Panel */
    .admin-main-custom {
        flex: 1;
        min-width: 0;
    }

    .admin-top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .admin-top-header h2 {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .admin-header-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .admin-search-box {
        display: flex;
        align-items: center;
        background: #ffffff;
        border-radius: 30px;
        padding: 6px 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
        width: 240px;
    }

    .admin-search-box input {
        border: none;
        outline: none;
        font-size: 0.88rem;
        padding: 6px;
        width: 100%;
        color: #334155;
    }

    .admin-search-box i {
        color: #94a3b8;
    }

    .admin-user-profile-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffffff;
        padding: 6px 14px;
        border-radius: 30px;
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
    }

    .badge-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Metrics Stat Row Grid */
    .kpi-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .kpi-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .kpi-card.purple-bg {
        background: linear-gradient(135deg, #ffa17a 0%, var(--primary, #FF7D4B) 100%);
        color: #ffffff;
    }

    .kpi-card-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .kpi-card.purple-bg .kpi-card-title {
        color: #ffeae0;
    }

    .kpi-card-val {
        font-size: 1.85rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 15px;
    }

    .kpi-card.purple-bg .kpi-card-val {
        color: #ffffff;
    }

    .kpi-growth-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        align-self: flex-start;
    }

    .kpi-growth-badge.up {
        background: #ecfdf5;
        color: #10b981;
    }

    .kpi-growth-badge.down {
        background: #fef2f2;
        color: #ef4444;
    }

    .kpi-card.purple-bg .kpi-growth-badge.up {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }

    .kpi-sparkline {
        position: absolute;
        bottom: 15px;
        right: 20px;
        width: 80px;
        height: 35px;
        stroke-width: 2;
        fill: none;
    }

    /* Split Dashboard Columns */
    .dashboard-split-columns {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .pending-tasks-panel {
        flex: 1;
        min-width: 320px;
    }

    .recent-bookings-panel {
        flex: 1.8;
        min-width: 500px;
        background: #ffffff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .panel-header-custom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .panel-header-custom h3 {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    /* Pending Task Card designs matching Expinova approval boxes */
    .pending-task-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid rgba(0, 0, 0, 0.03);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.01);
        margin-bottom: 20px;
    }

    .task-badge-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #0f172a;
        margin-bottom: 12px;
    }

    .task-amount {
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--primary, #FF7D4B);
        margin-bottom: 15px;
    }

    .task-participants-row {
        display: flex;
        justify-content: space-between;
        border-top: 1px solid #f1f5f9;
        padding-top: 15px;
        margin-bottom: 20px;
    }

    .task-participant-col {
        text-align: left;
    }

    .task-participant-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .task-participant-val {
        font-size: 0.88rem;
        font-weight: 700;
        color: #334155;
    }

    .task-actions-row {
        display: flex;
        gap: 10px;
    }

    /* Premium Expinova Tables */
    .premium-admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .premium-admin-table th {
        background: #f8fafc;
        padding: 12px 16px;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: 0.3px;
        border-bottom: 1px solid #e2e8f0;
    }

    .premium-admin-table td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.88rem;
        color: #334155;
        vertical-align: middle;
    }

    .premium-admin-table tr:last-child td {
        border-bottom: none;
    }

    .pkg-cell-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .pkg-cell-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .pkg-cell-info h4 {
        font-size: 0.88rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }

    .pkg-cell-info span {
        font-size: 0.75rem;
        color: #64748b;
    }

    .badge-premium {
        font-size: 0.72rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
    }

    .badge-premium.tour {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .badge-premium.hotel {
        background: #f0fdf4;
        color: #15803d;
    }

    .badge-status-p {
        font-weight: 700;
        color: #f59e0b;
    }

    .badge-status-a {
        font-weight: 700;
        color: #10b981;
    }

    .badge-status-r {
        font-weight: 700;
        color: #ef4444;
    }

    /* Tab Full View Card wrapper */
    .tab-full-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.01);
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    @media (max-width: 1024px) {
        .admin-container {
            flex-direction: column;
        }
        .admin-sidebar-custom {
            width: 100%;
            min-height: auto;
            position: relative;
            top: 0;
        }
        .kpi-metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .kpi-metrics-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-container">
    <!-- Sidebar Menu navigation -->
    <aside class="admin-sidebar-custom">
        <div>
            <div class="sidebar-brand-custom">
                <i class="fas fa-compass" style="font-size: 1.7rem;"></i>
                <span>Expinova</span>
            </div>
            
            <div class="sidebar-section-header">My View <i class="fas fa-chevron-down" style="font-size: 0.65rem;"></i></div>
            
            <ul class="sidebar-menu-custom">
                <li class="sidebar-item-custom">
                    <a href="index.php?tab=overview" class="sidebar-link-custom <?php echo $tab === 'overview' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="index.php?tab=packages" class="sidebar-link-custom <?php echo $tab === 'packages' ? 'active' : ''; ?>">
                        <i class="fas fa-suitcase"></i> Travel Listings
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="index.php?tab=approvals" class="sidebar-link-custom <?php echo $tab === 'approvals' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Agency Approvals
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="index.php?tab=users" class="sidebar-link-custom <?php echo $tab === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Accounts
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="index.php?tab=bookings" class="sidebar-link-custom <?php echo $tab === 'bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Transactions
                    </a>
                </li>
                <li class="sidebar-item-custom">
                    <a href="../pages/profile.php" class="sidebar-link-custom">
                        <i class="fas fa-cog"></i> My Settings
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-section-header">Admin View <i class="fas fa-chevron-right" style="font-size: 0.65rem;"></i></div>
        </div>

        <div style="border-top: 1px solid #f1f5f9; padding-top: 20px;">
            <a href="../pages/logout.php" class="sidebar-link-custom" style="color: #ef4444;">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </aside>

    <!-- Main Workspace Panel -->
    <main class="admin-main-custom">
        <!-- Dashboard Top Header Bar -->
        <header class="admin-top-header">
            <div>
                <h2><?php echo ucfirst($tab === 'overview' ? 'Home' : ($tab === 'packages' ? 'Travel Listings' : ($tab === 'approvals' ? 'Agency Approvals' : ($tab === 'users' ? 'User Accounts' : 'Transactions')))); ?></h2>
            </div>
            
            <div class="admin-header-actions">
                <div class="admin-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                
                <div class="admin-user-profile-badge">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&h=80&fit=crop&q=80" alt="Admin Avatar" class="badge-avatar">
                    <div style="text-align: left;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem;">Jayson</div>
                        <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 600;">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Messages / Alerts -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #ecfdf5; color: #047857; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; border: 1px solid #d1fae5;">
                <?php 
                $msg = $_GET['msg'];
                if ($msg === 'agency_updated') echo 'Agency registration status updated successfully.';
                elseif ($msg === 'booking_updated') echo 'Booking status updated successfully.';
                elseif ($msg === 'package_deleted') echo 'Travel package deleted successfully.';
                ?>
            </div>
        <?php endif; ?>

        <?php if ($tab === 'overview'): ?>
            <!-- OVERVIEW DASHBOARD TAB -->
            
            <!-- KPI Cards Row -->
            <section class="kpi-metrics-grid">
                <!-- KPI Card 1: Total Booked (Purple Gradient Theme) -->
                <div class="kpi-card purple-bg">
                    <span class="kpi-card-title">Total Booked</span>
                    <div class="kpi-card-val">$<?php echo number_format($bookings_count * 1200, 0); ?></div>
                    <div class="kpi-growth-badge up">
                        <i class="fas fa-arrow-up"></i> +12.08%
                    </div>
                    <!-- Mock sparkline svg matching mockup -->
                    <svg class="kpi-sparkline" style="stroke: #ffffff;">
                        <path d="M0 30 Q15 15 30 25 T60 5 T80 20" />
                    </svg>
                </div>

                <!-- KPI Card 2: Revenue -->
                <div class="kpi-card">
                    <span class="kpi-card-title">30 Days Revenue</span>
                    <div class="kpi-card-val">$<?php echo number_format($bookings_count * 934, 0); ?></div>
                    <div class="kpi-growth-badge down">
                        <i class="fas fa-arrow-down"></i> -12.08%
                    </div>
                    <svg class="kpi-sparkline" style="stroke: #f87171;">
                        <path d="M0 25 Q15 35 30 15 T60 30 T80 10" />
                    </svg>
                </div>

                <!-- KPI Card 3: Customers -->
                <div class="kpi-card">
                    <span class="kpi-card-title">Total Customers</span>
                    <div class="kpi-card-val"><?php echo $users_count; ?></div>
                    <div class="kpi-growth-badge up">
                        <i class="fas fa-arrow-up"></i> +12.08%
                    </div>
                    <svg class="kpi-sparkline" style="stroke: #34d399;">
                        <path d="M0 30 Q15 5 30 25 T60 10 T80 20" />
                    </svg>
                </div>

                <!-- KPI Card 4: Tour Packages -->
                <div class="kpi-card">
                    <span class="kpi-card-title">Tour Packages</span>
                    <div class="kpi-card-val"><?php echo $packages_count; ?></div>
                    <div class="kpi-growth-badge up">
                        <i class="fas fa-arrow-up"></i> +12.08%
                    </div>
                    <svg class="kpi-sparkline" style="stroke: #38bdf8;">
                        <path d="M0 20 Q15 30 30 10 T60 25 T80 5" />
                    </svg>
                </div>
            </section>

            <!-- Columns layout split -->
            <div class="dashboard-split-columns">
                
                <!-- Left column: Pending tasks (Agencies + bookings needing actions) -->
                <div class="pending-tasks-panel">
                    <div class="panel-header-custom">
                        <h3>Pending Task</h3>
                        <i class="fas fa-ellipsis-h" style="color: #94a3b8; cursor: pointer;"></i>
                    </div>

                    <!-- Pending Agency Approvals -->
                    <?php if (count($pending_agencies) > 0): ?>
                        <?php foreach ($pending_agencies as $agency): ?>
                            <div class="pending-task-card">
                                <div class="task-badge-row">
                                    <i class="fas fa-user-check" style="color: #4f46e5;"></i>
                                    <span>Pending Agency Registration</span>
                                </div>
                                <div class="task-amount"><?php echo htmlspecialchars($agency['company_name']); ?></div>
                                
                                <div class="task-participants-row">
                                    <div class="task-participant-col">
                                        <div class="task-participant-label">Representative</div>
                                        <div class="task-participant-val"><?php echo htmlspecialchars($agency['name']); ?></div>
                                    </div>
                                    <div class="task-participant-col">
                                        <div class="task-participant-label">Email Contact</div>
                                        <div class="task-participant-val" style="font-size: 0.75rem; word-break: break-all;"><?php echo htmlspecialchars($agency['email']); ?></div>
                                    </div>
                                </div>

                                <div class="task-actions-row">
                                    <form method="POST" action="" style="display: flex; gap: 8px; width: 100%; margin: 0;">
                                        <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-outline" style="flex: 1; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 0.8rem;">Reject</button>
                                        <button type="submit" name="action" value="verify" class="btn" style="flex: 1.5; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; background: #4f46e5; color: #ffffff;">Approve</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Pending Booking Approvals -->
                    <?php 
                    $pending_bookings_count = 0;
                    foreach ($all_bookings as $b) {
                        if ($b['status'] === 'pending') {
                            $pending_bookings_count++;
                            if ($pending_bookings_count > 2) break; // limit to 2 on task panel
                            ?>
                            <div class="pending-task-card">
                                <div class="task-badge-row">
                                    <i class="fas fa-receipt" style="color: #f59e0b;"></i>
                                    <span>Pending Trip Reservation</span>
                                </div>
                                <div class="task-amount">$<?php echo number_format($b['price'], 2); ?></div>

                                <div class="task-participants-row">
                                    <div class="task-participant-col">
                                        <div class="task-participant-label">Traveler</div>
                                        <div class="task-participant-val"><?php echo htmlspecialchars($b['traveler_name']); ?></div>
                                    </div>
                                    <div class="task-participant-col">
                                        <div class="task-participant-label">Destination</div>
                                        <div class="task-participant-val"><?php echo htmlspecialchars($b['package_title']); ?></div>
                                    </div>
                                </div>

                                <div class="task-actions-row">
                                    <form method="POST" action="" style="display: flex; gap: 8px; width: 100%; margin: 0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" name="booking_action" value="reject" class="btn btn-outline" style="flex: 1; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 0.8rem;">Reject</button>
                                        <button type="submit" name="booking_action" value="approve" class="btn" style="flex: 1.5; padding: 10px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; background: #4f46e5; color: #ffffff;">Approve</button>
                                    </form>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    if (count($pending_agencies) === 0 && $pending_bookings_count === 0) {
                        echo '<p style="color: #64748b; font-size: 0.9rem; background: #ffffff; padding: 30px; border-radius: 20px; text-align: center; border: 1px dashed #cbd5e1;">All caught up! No pending approvals.</p>';
                    }
                    ?>
                </div>

                <!-- Right column: Recent bookings table list -->
                <div class="recent-bookings-panel">
                    <div class="panel-header-custom">
                        <h3>Recent Booking</h3>
                        <i class="fas fa-ellipsis-h" style="color: #94a3b8; cursor: pointer;"></i>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="premium-admin-table">
                            <thead>
                                <tr>
                                    <th>Package Name</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $limit = 8;
                                $count = 0;
                                foreach ($all_bookings as $b): 
                                    $count++;
                                    if ($count > $limit) break;
                                    $trav_avatar = !empty($b['profile_image']) ? BASE_URL . '/' . htmlspecialchars($b['profile_image']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80&h=80&fit=crop&q=80';
                                    $status_class = 'badge-status-p';
                                    if ($b['status'] === 'approved' || $b['status'] === 'confirmed') $status_class = 'badge-status-a';
                                    elseif ($b['status'] === 'cancelled' || $b['status'] === 'rejected') $status_class = 'badge-status-r';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="pkg-cell-wrap">
                                                <img src="<?php echo $trav_avatar; ?>" alt="User Avatar" class="pkg-cell-avatar">
                                                <div class="pkg-cell-info">
                                                    <h4><?php echo htmlspecialchars($b['package_title']); ?></h4>
                                                    <span>By <?php echo htmlspecialchars($b['traveler_name']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-premium <?php echo strtolower($b['package_type'] ?: 'tour'); ?>">
                                                <?php echo ucfirst($b['package_type'] ?: 'Tour'); ?>
                                            </span>
                                        </td>
                                        <td style="font-weight: 700; color: #0f172a;">$<?php echo number_format($b['price'], 2); ?></td>
                                        <td class="<?php echo $status_class; ?>">
                                            <?php echo ucfirst($b['status']); ?>
                                        </td>
                                        <td>
                                            <a href="index.php?tab=bookings" style="color: #94a3b8; text-decoration: none;"><i class="fas fa-ellipsis-v"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (count($all_bookings) === 0): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px 0;">No reservation records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($tab === 'packages'): ?>
            <!-- TRAVEL LISTINGS TAB -->
            <div class="tab-full-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px;">
                    <div>
                        <h3 class="panel-header-custom" style="margin: 0; font-size: 1.3rem;">Travel Package Listings</h3>
                        <p style="color: #64748b; font-size: 0.88rem; margin-top: 5px;">Manage all active stay and tour packages in the marketplace.</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="manage-package.php?type=tour" class="btn" style="padding: 10px 20px; font-size: 0.85rem; font-weight: 700; border-radius: 10px;">+ Create Tour</a>
                        <a href="manage-package.php?type=hotel" class="btn btn-accent" style="padding: 10px 20px; font-size: 0.85rem; font-weight: 700; border-radius: 10px;">+ Create Hotel</a>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="premium-admin-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Agency</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_packages as $pkg): ?>
                                <tr>
                                    <td style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($pkg['title']); ?></td>
                                    <td>
                                        <span class="badge-premium <?php echo strtolower($pkg['type'] ?: 'tour'); ?>">
                                            <?php echo ucfirst($pkg['type'] ?? 'Tour'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($pkg['company_name']); ?></td>
                                    <td><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 4px;"></i> <?php echo htmlspecialchars($pkg['location']); ?></td>
                                    <td style="font-weight: 800; color: var(--primary);">$<?php echo number_format($pkg['price'], 2); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="manage-package.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">Edit</a>
                                            <a href="index.php?delete_package=<?php echo $pkg['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;" onclick="return confirm('Are you sure you want to delete this package?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($tab === 'approvals'): ?>
            <!-- AGENCY APPROVALS TAB -->
            <div class="tab-full-card" style="margin-bottom: 30px;">
                <h3 class="panel-header-custom" style="font-size: 1.3rem; border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">Pending Agencies</h3>
                
                <?php if (count($pending_agencies) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="premium-admin-table">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Representative</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_agencies as $agency): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                        <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline-flex; gap: 8px; margin: 0;">
                                                <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                                <button type="submit" name="action" value="reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">Reject</button>
                                                <button type="submit" name="action" value="verify" class="btn" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; background: var(--primary, #FF7D4B); color: #ffffff;">Approve</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #64748b; padding: 20px 0;">No pending agencies to approve.</p>
                <?php endif; ?>
            </div>

            <div class="tab-full-card">
                <h3 class="panel-header-custom" style="font-size: 1.3rem; border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">Verified Agencies</h3>
                
                <?php if (count($verified_agencies) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="premium-admin-table">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Representative</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verified_agencies as $agency): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                        <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                        <td>
                                            <span style="color: #10b981; font-weight: 700;"><i class="fas fa-check-circle"></i> Verified</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #64748b; padding: 20px 0;">No verified agencies registered.</p>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'users'): ?>
            <!-- USER ACCOUNTS TAB -->
            <div class="tab-full-card">
                <h3 class="panel-header-custom" style="font-size: 1.3rem; border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">Registered Accounts</h3>
                
                <div style="overflow-x: auto;">
                    <table class="premium-admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): ?>
                                <?php 
                                $user_avatar = !empty($u['profile_image']) ? BASE_URL . '/' . htmlspecialchars($u['profile_image']) : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=80&h=80&fit=crop&q=80';
                                ?>
                                <tr>
                                    <td>
                                        <div class="pkg-cell-wrap">
                                            <img src="<?php echo $user_avatar; ?>" alt="Avatar" class="pkg-cell-avatar">
                                            <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo strtolower($u['role']) === 'admin' ? '#fee2e2' : (strtolower($u['role']) === 'agency' ? '#d1fae5' : '#f1f5f9'); ?>; color: <?php echo strtolower($u['role']) === 'admin' ? '#dc2626' : (strtolower($u['role']) === 'agency' ? '#059669' : '#475569'); ?>; font-weight: 700; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem;">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($tab === 'bookings'): ?>
            <!-- TRANSACTIONS/RESERVATIONS TAB -->
            <div class="tab-full-card">
                <h3 class="panel-header-custom" style="font-size: 1.3rem; border-bottom: 2px dashed #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">All Marketplace Bookings</h3>
                
                <div style="overflow-x: auto;">
                    <table class="premium-admin-table">
                        <thead>
                            <tr>
                                <th>Traveler</th>
                                <th>Package Name</th>
                                <th>Agency</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_bookings as $b): ?>
                                <?php 
                                $status_class = 'badge-status-p';
                                if ($b['status'] === 'approved' || $b['status'] === 'confirmed') $status_class = 'badge-status-a';
                                elseif ($b['status'] === 'cancelled' || $b['status'] === 'rejected') $status_class = 'badge-status-r';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($b['traveler_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['package_title']); ?></td>
                                    <td><?php echo htmlspecialchars($b['company_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                    <td style="font-weight: 800; color: #0f172a;">$<?php echo number_format($b['price'], 2); ?></td>
                                    <td class="<?php echo $status_class; ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </td>
                                    <td>
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <form method="POST" action="" style="display: inline-flex; gap: 5px; margin: 0;">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" name="booking_action" value="reject" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.8rem; border-radius: 8px;">Reject</button>
                                                <button type="submit" name="booking_action" value="approve" class="btn" style="padding: 5px 12px; font-size: 0.8rem; border-radius: 8px; background: var(--primary, #FF7D4B); color: #ffffff;">Approve</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.85rem;"><i class="fas fa-check"></i> Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
