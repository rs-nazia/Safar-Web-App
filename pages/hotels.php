<?php
require_once '../includes/db.php';
require_once '../includes/header.php';
?>

<div class="container my-4 dashboard-layout">
    <!-- Hotels Sidebar Filters -->
    <aside class="dashboard-sidebar glass" style="align-self: flex-start; position: sticky; top: 100px; padding: 25px; border-radius: var(--radius);">
        <h3 style="color: var(--primary); margin-bottom: 20px; font-weight: 700;"><i class="fas fa-filter"></i> Hotel Filters</h3>
        <form id="hotels-filter-form">
            <div class="form-group">
                <label style="font-weight: 600; color: #475569;">City / Location</label>
                <input type="text" id="filter-location" class="form-control" placeholder="e.g. Dubai, Tokyo...">
            </div>
            
            <div class="form-group">
                <label style="font-weight: 600; color: #475569;">Key Amenities</label>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 5px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.95rem; cursor: pointer;">
                        <input type="checkbox" class="filter-amenity" value="wifi" style="accent-color: var(--primary);"> Free WiFi
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.95rem; cursor: pointer;">
                        <input type="checkbox" class="filter-amenity" value="pool" style="accent-color: var(--primary);"> Swimming Pool
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.95rem; cursor: pointer;">
                        <input type="checkbox" class="filter-amenity" value="gym" style="accent-color: var(--primary);"> Fitness Center
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.95rem; cursor: pointer;">
                        <input type="checkbox" class="filter-amenity" value="spa" style="accent-color: var(--primary);"> Luxury Spa
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; justify-content: space-between; font-weight: 600; color: #475569;">
                    <span>Max Price</span>
                    <span id="price-display" style="color: var(--primary); font-weight: 700;">$5000</span>
                </label>
                <input type="range" id="filter-price" min="100" max="5000" step="100" value="5000" style="width: 100%; accent-color: var(--primary); padding: 0;">
            </div>
        </form>
    </aside>

    <!-- Hotels Main Content -->
    <main class="dashboard-main" style="background: transparent; padding-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="color: var(--primary); font-weight: 800; margin: 0;">Hotel Accommodations</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 5px;">Discover luxury stays and resort accommodations globally.</p>
            </div>
            <span id="results-count" class="badge badge-approved" style="font-size: 1.05rem; padding: 6px 15px;">Loading Hotels...</span>
        </div>
        
        <div class="grid" id="hotels-grid">
            <!-- Inject cards here via AJAX -->
        </div>
    </main>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/hotels.js"></script>
<?php require_once '../includes/footer.php'; ?>
