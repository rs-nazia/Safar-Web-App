<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('agency');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, status FROM agencies WHERE user_id = ?");
$stmt->execute([$user_id]);
$agency = $stmt->fetch();

if (!$agency || $agency['status'] !== 'verified') {
    die("Unauthorized or unverified agency. Please wait for administrator verification.");
}

$agency_id = $agency['id'];
$error = '';
$success = '';

// Support both 'edit' and 'id' query parameters for fetching packages
$edit_id = $_GET['edit'] ?? $_GET['id'] ?? null;
$package = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND agency_id = ?");
    $stmt->execute([$edit_id, $agency_id]);
    $package = $stmt->fetch();
    if (!$package) {
        die("Package not found.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $location = trim($_POST['location']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    if (isset($_POST['delete']) && $edit_id) {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ? AND agency_id = ?");
        $stmt->execute([$edit_id, $agency_id]);
        header("Location: agency.php");
        exit();
    }
    
    if (empty($title) || empty($location) || empty($price) || empty($description)) {
        $error = "Please fill in all required fields.";
    } else {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE packages SET title=?, location=?, price=?, description=?, image_url=? WHERE id=? AND agency_id=?");
            $stmt->execute([$title, $location, $price, $description, $image_url, $edit_id, $agency_id]);
            header("Location: agency.php?tab=packages&msg=updated");
            exit();
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (agency_id, title, location, price, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$agency_id, $title, $location, $price, $description, $image_url]);
            header("Location: agency.php?tab=packages&msg=created");
            exit();
        }
    }
}

require_once '../includes/header.php';
?>

<style>
    body {
        background-color: #f3f4f6;
        font-family: 'Inter', sans-serif;
    }

    .form-page-container {
        max-width: 650px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .form-card-custom {
        background: #ffffff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.04);
    }

    .form-card-custom h2 {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .form-card-custom p {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 30px;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-group-custom label {
        display: block;
        font-weight: 700;
        color: #475569;
        font-size: 0.85rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-input-custom {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        background: #f8fafc;
        transition: all 0.25s ease;
        color: #334155;
    }

    .form-input-custom:focus {
        border-color: var(--primary);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(255, 125, 75, 0.12);
        outline: none;
    }

    .form-textarea-custom {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        background: #f8fafc;
        transition: all 0.25s ease;
        color: #334155;
        resize: vertical;
        min-height: 120px;
    }

    .form-textarea-custom:focus {
        border-color: var(--primary);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(255, 125, 75, 0.12);
        outline: none;
    }

    .form-actions-row {
        display: flex;
        gap: 12px;
        margin-top: 30px;
    }

    .form-actions-row button, .form-actions-row a {
        padding: 12px 24px;
        font-weight: 700;
        font-size: 0.95rem;
        border-radius: 12px;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-submit-primary {
        background: var(--primary);
        color: #ffffff;
        border: none;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.25);
        transition: all 0.25s ease;
    }

    .btn-submit-primary:hover {
        background: #e06232;
        transform: translateY(-1px);
    }
</style>

<div class="form-page-container">
    <div class="form-card-custom">
        <h2><?php echo $edit_id ? 'Edit Travel Package' : 'Create New Travel Package'; ?></h2>
        <p>Fill in the details below to add or modify listings in the SAFAR marketplace.</p>
        
        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #ef4444; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; border: 1px solid #fee2e2;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group-custom">
                <label>Package Title</label>
                <input type="text" name="title" class="form-input-custom" required value="<?php echo htmlspecialchars($package['title'] ?? ''); ?>" placeholder="e.g. Cox's Bazar Getaway">
            </div>
            
            <div class="form-group-custom">
                <label>Location</label>
                <input type="text" name="location" class="form-input-custom" required value="<?php echo htmlspecialchars($package['location'] ?? ''); ?>" placeholder="e.g. Chittagong, Bangladesh">
            </div>
            
            <div class="form-group-custom">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" class="form-input-custom" required value="<?php echo htmlspecialchars($package['price'] ?? ''); ?>" placeholder="e.g. 299.00">
            </div>
            
            <div class="form-group-custom">
                <label>Image URL (Optional)</label>
                <input type="url" name="image_url" class="form-input-custom" placeholder="e.g. https://images.unsplash.com/..." value="<?php echo htmlspecialchars($package['image_url'] ?? ''); ?>">
            </div>
            
            <div class="form-group-custom">
                <label>Description (include duration e.g. 3D 2N)</label>
                <textarea name="description" class="form-textarea-custom" required placeholder="Describe the trip highlights, hotels, and schedule itinerary..."><?php echo htmlspecialchars($package['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions-row">
                <button type="submit" class="btn-submit-primary" style="flex: 1.5;"><?php echo $edit_id ? 'Update Package' : 'Publish Listing'; ?></button>
                
                <?php if ($edit_id): ?>
                    <button type="submit" name="delete" value="1" class="btn btn-danger" style="flex: 1;" onclick="return confirm('Are you sure you want to delete this package?');">Delete</button>
                <?php endif; ?>
                
                <a href="agency.php" class="btn btn-outline" style="flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
