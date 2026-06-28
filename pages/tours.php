<?php
require_once '../includes/db.php';
require_once '../includes/header.php';
?>

<div class="container my-4 dashboard-layout">
    <!-- Tours Sidebar Filters -->
    <aside class="dashboard-sidebar glass" style="align-self: flex-start; position: sticky; top: 100px; padding: 25px; border-radius: var(--radius);">
        <h3 style="color: var(--primary); margin-bottom: 20px; font-weight: 700;"><i class="fas fa-filter"></i> Tour Filters</h3>
        <form id="tours-filter-form">
            <div class="form-group">
                <label style="font-weight: 600; color: #475569;">Destination</label>
                <input type="text" id="filter-location" class="form-control" placeholder="Search destination...">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; color: #475569;">Tour Duration</label>
                <select id="filter-duration" class="form-control">
                    <option value="all">Any Duration</option>
                    <option value="short">Short (1 - 5 Days)</option>
                    <option value="medium">Medium (6 - 10 Days)</option>
                    <option value="long">Long (11+ Days)</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display: flex; justify-content: space-between; font-weight: 600; color: #475569;">
                    <span>Max Price</span>
                    <span id="price-display" style="color: var(--primary); font-weight: 700;">$5000</span>
                </label>
                <input type="range" id="filter-price" min="500" max="5000" step="100" value="5000" style="width: 100%; accent-color: var(--primary); padding: 0;">
            </div>
        </form>
    </aside>

    <!-- Tours Main Content -->
    <main class="dashboard-main" style="background: transparent; padding-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="color: var(--primary); font-weight: 800; margin: 0;">Guided Tours Catalog</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 5px;">Explore the world with our handpicked guided experiences.</p>
            </div>
            <span id="results-count" class="badge badge-approved" style="font-size: 1.05rem; padding: 6px 15px;">Loading Tours...</span>
        </div>
        
        <div class="grid" id="tours-grid">
            <!-- Inject cards here via AJAX -->
        </div>
    </main>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/tours.js"></script>
<?php require_once '../includes/footer.php'; ?>
