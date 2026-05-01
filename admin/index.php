<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('admin');

// Handle agency actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['agency_id'])) {
    $action = $_POST['action'] === 'verify' ? 'verified' : 'rejected';
    $agency_id = $_POST['agency_id'];
    
    $stmt = $pdo->prepare("UPDATE agencies SET status = ? WHERE id = ?");
    $stmt->execute([$action, $agency_id]);
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'], $_POST['booking_id'])) {
    $baction = $_POST['booking_action'] === 'approve' ? 'approved' : 'rejected';
    $bid = $_POST['booking_id'];
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$baction, $bid]);
}

// Fetch stats
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
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();

// Fetch all bookings
$stmt = $pdo->query("SELECT b.*, u.name as traveler_name, p.title as package_title, a.company_name FROM bookings b JOIN users u ON b.traveler_id = u.id JOIN packages p ON b.package_id = p.id JOIN agencies a ON p.agency_id = a.id ORDER BY b.booking_date DESC");
$all_bookings = $stmt->fetchAll();

// Fetch all packages for admin
if (isset($_GET['delete_package'])) {
    $del_id = $_GET['delete_package'];
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: index.php?msg=deleted");
    exit();
}

$stmt = $pdo->query("SELECT p.*, a.company_name FROM packages p JOIN agencies a ON p.agency_id = a.id ORDER BY p.created_at DESC");
$all_packages = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container my-4" style="display: flex; gap: 30px; align-items: flex-start;">
    <!-- Sidebar -->
    <aside style="width: 250px; background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--glass-border);">
        <h3 style="margin-bottom: 20px; color: var(--primary);">Admin Menu</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 10px;">
                <a href="index.php" style="display: block; padding: 10px; border-radius: 8px; background: var(--bg-light); color: var(--primary); font-weight: 600;"><i class="fas fa-chart-line"></i> Dashboard</a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="manage-package.php?type=tour" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-plus"></i> Create Tour</a>
            </li>
            <li style="margin-bottom: 10px;">
                <a href="manage-package.php?type=hotel" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-hotel"></i> Create Hotel</a>
            </li>
            <li>
                <a href="../profile.php" style="display: block; padding: 10px; border-radius: 8px; color: var(--text-main); transition: var(--transition);"><i class="fas fa-user-shield"></i> Admin Profile</a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div style="flex: 1; min-width: 0; width: 100%;">
        <h1 style="margin-bottom: 30px;">Admin Panel</h1>
    
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 40px; text-align: center;">
        <div class="card" style="padding: 20px;">
            <h3>Total Users</h3>
            <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $users_count; ?></p>
        </div>
        <div class="card" style="padding: 20px;">
            <h3>Total Packages</h3>
            <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $packages_count; ?></p>
        </div>
        <div class="card" style="padding: 20px;">
            <h3>Total Bookings</h3>
            <p style="font-size: 2rem; color: var(--primary); font-weight: bold;"><?php echo $bookings_count; ?></p>
        </div>
    </div>
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Package deleted successfully.</div>
    <?php endif; ?>

    <div class="card" style="padding: 30px; margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <h2 style="margin: 0;">Package Management</h2>
            <div style="display: flex; gap: 10px;">
                <a href="manage-package.php?type=tour" class="btn">Create Tour</a>
                <a href="manage-package.php?type=hotel" class="btn btn-accent">Create Hotel</a>
            </div>
        </div>
        
        <?php if (count($all_packages) > 0): ?>
            <div class="table-responsive">
                <table class="table">
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
                                <td><?php echo htmlspecialchars($pkg['title']); ?></td>
                                <td><span class="badge badge-pending"><?php echo ucfirst($pkg['type'] ?? 'Tour'); ?></span></td>
                                <td><?php echo htmlspecialchars($pkg['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($pkg['location']); ?></td>
                                <td>$<?php echo number_format($pkg['price'], 2); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="manage-package.php?id=<?php echo $pkg['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Edit</a>
                                        <a href="index.php?delete_package=<?php echo $pkg['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this package?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No packages found.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="padding: 30px; margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">Pending Agency Approvals</h2>
        <?php if (count($pending_agencies) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_agencies as $agency): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline-flex; gap: 5px;">
                                        <input type="hidden" name="agency_id" value="<?php echo $agency['id']; ?>">
                                        <button type="submit" name="action" value="verify" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Verify</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No pending agencies to approve.</p>
        <?php endif; ?>
    </div>
    
    <div class="card" style="padding: 30px;">
        <h2 style="margin-bottom: 20px;">Verified Agencies</h2>
        <?php if (count($verified_agencies) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($verified_agencies as $agency): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agency['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($agency['name']); ?></td>
                                <td><?php echo htmlspecialchars($agency['email']); ?></td>
                                <td><span class="badge badge-approved">Verified</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No verified agencies yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="card" style="padding: 30px; margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">User Management</h2>
        <?php if (count($all_users) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge badge-<?php echo strtolower($u['role']) === 'admin' ? 'approved' : 'pending'; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No users found.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="padding: 30px;">
        <h2 style="margin-bottom: 20px;">All Bookings</h2>
        <?php if (count($all_bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Traveler</th>
                            <th>Package</th>
                            <th>Agency</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bookings as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['traveler_name']); ?></td>
                                <td><?php echo htmlspecialchars($b['package_title']); ?></td>
                                <td><?php echo htmlspecialchars($b['company_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                        <?php echo ucfirst($b['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" action="" style="display: inline-flex; gap: 5px;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" name="booking_action" value="approve" class="btn" style="background: #10b981; padding: 5px 10px; font-size: 0.8rem;">Approve</button>
                                            <button type="submit" name="booking_action" value="reject" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No bookings found.</p>
        <?php endif; ?>
    </div>
    
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
