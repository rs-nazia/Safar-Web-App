<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

$edit_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'tour'; // Default to tour if creating
$package = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$edit_id]);
    $package = $stmt->fetch();
    if (!$package) {
        die("Package not found.");
    }
    $type = $package['type'] ?? 'tour';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    $pkg_type = $_POST['type'] ?? 'tour';
    $agency_id = $_POST['agency_id'] ?? null;
    
    if (empty($title) || empty($location) || empty($price) || empty($description) || empty($agency_id)) {
        $error = "Please fill in all required fields.";
    } else {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE packages SET agency_id=?, title=?, location=?, price=?, description=?, image_url=?, type=? WHERE id=?");
            $stmt->execute([$agency_id, $title, $location, $price, $description, $image_url, $pkg_type, $edit_id]);
            $success = "Package updated successfully.";
            $package = ['title'=>$title, 'location'=>$location, 'price'=>$price, 'description'=>$description, 'image_url'=>$image_url, 'type'=>$pkg_type, 'agency_id'=>$agency_id];
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (agency_id, title, location, price, description, image_url, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$agency_id, $title, $location, $price, $description, $image_url, $pkg_type]);
            $success = "New " . htmlspecialchars($pkg_type) . " added successfully.";
        }
    }
}

// Fetch all agencies to assign
$stmt = $pdo->query("SELECT id, company_name FROM agencies WHERE status = 'verified'");
$agencies = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container my-4">
    <div class="auth-form" style="max-width: 600px;">
        <h2 style="color: var(--primary);"><?php echo $edit_id ? 'Edit Package' : 'Create New ' . ucfirst(htmlspecialchars($type)); ?></h2>
        
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Package Type</label>
                <select name="type" class="form-control" required>
                    <option value="tour" <?php echo ($type === 'tour') ? 'selected' : ''; ?>>Tour Package</option>
                    <option value="hotel" <?php echo ($type === 'hotel') ? 'selected' : ''; ?>>Hotel Listing</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Assign to Agency</label>
                <select name="agency_id" class="form-control" required>
                    <option value="">-- Select Agency --</option>
                    <?php foreach ($agencies as $ag): ?>
                        <option value="<?php echo $ag['id']; ?>" <?php echo (isset($package['agency_id']) && $package['agency_id'] == $ag['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ag['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($package['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" required value="<?php echo htmlspecialchars($package['location'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($package['price'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Image URL (Optional)</label>
                <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($package['image_url'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn" style="flex: 1;"><?php echo $edit_id ? 'Update Package' : 'Create Package'; ?></button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
