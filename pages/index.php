<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

$is_searching = !empty($search) || !empty($price_max);

$packages = [];
if ($is_searching) {
    $query = "
        SELECT p.*, a.company_name,
        EXISTS(SELECT 1 FROM favorites WHERE user_id = ? AND package_id = p.id) as is_favorited 
        FROM packages p 
        JOIN agencies a ON p.agency_id = a.id 
        WHERE 1=1
    ";
    $params = [$user_id];
    
    if ($search) {
        $query .= " AND (p.location LIKE ? OR p.title LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($price_max) {
        $query .= " AND p.price <= ?";
        $params[] = $price_max;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();
} else {
    // Fetch 3 featured tours with favorite check
    $tours_stmt = $pdo->prepare("
        SELECT p.*, a.company_name, 
        EXISTS(SELECT 1 FROM favorites WHERE user_id = ? AND package_id = p.id) as is_favorited 
        FROM packages p 
        JOIN agencies a ON p.agency_id = a.id 
        WHERE p.type = 'tour' 
        ORDER BY p.created_at DESC 
        LIMIT 3
    ");
    $tours_stmt->execute([$user_id]);
    $featured_tours = $tours_stmt->fetchAll();

    // Fetch 3 featured hotels with favorite check
    $hotels_stmt = $pdo->prepare("
        SELECT p.*, a.company_name, 
        EXISTS(SELECT 1 FROM favorites WHERE user_id = ? AND package_id = p.id) as is_favorited 
        FROM packages p 
        JOIN agencies a ON p.agency_id = a.id 
        WHERE p.type = 'hotel' 
        ORDER BY p.created_at DESC 
        LIMIT 3
    ");
    $hotels_stmt->execute([$user_id]);
    $featured_hotels = $hotels_stmt->fetchAll();
}
?>

<style>
    /* Styling for tab search widget */
    .tab-container {
        max-width: 900px;
        margin: 30px auto 0;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .tab-headers {
        display: flex;
        background: #f8fafc;
        border-bottom: 1.5px solid #e2e8f0;
    }
    
    .tab-btn {
        flex: 1;
        padding: 16px 20px;
        font-weight: 700;
        font-size: 1.05rem;
        color: #64748b;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.25s ease;
        position: relative;
    }
    
    .tab-btn:hover {
        color: var(--primary);
        background: rgba(255, 125, 75, 0.03);
    }
    
    .tab-btn.active {
        color: var(--primary);
        background: #ffffff;
    }
    
    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1.5px;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--primary);
        border-radius: 3px 3px 0 0;
    }
    
    .tab-content-panel {
        padding: 30px;
    }
    
    .tab-panel-form {
        display: none;
        animation: fadeIn 0.4s ease;
    }
    
    .tab-panel-form.active {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group-custom {
        flex: 1;
        min-width: 200px;
        text-align: left;
    }
    
    .form-label-custom {
        font-weight: 700;
        color: #475569;
        font-size: 0.85rem;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
        letter-spacing: 0.5px;
    }

    .form-input-custom {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
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
    
    /* Hero Feature Badges */
    .hero-features-row {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 30px;
        flex-wrap: wrap;
        color: rgba(255, 255, 255, 0.95);
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .hero-feature-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(0, 0, 0, 0.25);
        padding: 8px 16px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Landing page premium sections styles */
    .landing-section {
        max-width: 1200px;
        margin: 5rem auto;
        padding: 0 25px;
    }

    .section-title-wrap {
        text-align: center;
        margin-bottom: 3.5rem;
        position: relative;
    }

    .section-title-wrap h2 {
        font-size: 2.2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
        letter-spacing: -0.5px;
    }

    .section-title-wrap p {
        color: #64748b;
        font-size: 1.05rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .title-line-decorator {
        width: 60px;
        height: 4px;
        background: var(--primary);
        margin: 15px auto 0;
        border-radius: 2px;
    }

    /* Why Choose Us Cards */
    .why-choose-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .why-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(0,0,0,0.01);
        transition: all 0.3s ease;
    }

    .why-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(255,125,75,0.08);
        border-color: rgba(255,125,75,0.15);
    }

    .why-icon-wrap {
        width: 70px;
        height: 70px;
        border-radius: 20px;
        background: #fff3ee;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 25px;
        transition: all 0.3s ease;
    }

    .why-card:hover .why-icon-wrap {
        background: var(--primary);
        color: #ffffff;
        transform: rotate(5deg);
    }

    .why-card h3 {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 12px;
    }

    .why-card p {
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.6;
    }

    /* How It Works Steps */
    .steps-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
        position: relative;
    }

    .step-card {
        text-align: center;
        position: relative;
    }

    .step-number-badge {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.3);
    }

    .step-card h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .step-card p {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
        padding: 0 10px;
    }

    /* Testimonials block */
    .testimonial-bubble-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 40px;
        max-width: 750px;
        margin: 0 auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        border: 1px solid #f1f5f9;
        text-align: center;
        position: relative;
    }

    .testimonial-quote {
        font-size: 1.2rem;
        font-style: italic;
        color: #334155;
        line-height: 1.7;
        margin-bottom: 25px;
        font-weight: 500;
    }

    .testimonial-author-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 12px;
        border: 3px solid #fff3ee;
    }

    .testimonial-stars {
        color: #fbbf24;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    /* Dynamic Call To Action */
    .cta-banner-container {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        border-radius: 30px;
        padding: 70px 40px;
        text-align: center;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(30, 27, 75, 0.2);
    }

    .cta-banner-container h2 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        letter-spacing: -0.5px;
    }

    .cta-banner-container p {
        color: #cbd5e1;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto 35px;
    }

    /* Heart overlay button */
    .fav-heart-btn {
        transition: transform 0.2s ease;
    }
    .fav-heart-btn:hover {
        transform: scale(1.1);
    }

    .landing-card-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    @media (max-width: 900px) {
        .why-choose-grid, .steps-grid, .landing-card-grid {
            grid-template-columns: 1fr;
        }
        .landing-section {
            margin: 3.5rem auto;
        }
        .cta-banner-container h2 {
            font-size: 2rem;
        }
    }
</style>

<!-- Hero Search Section -->
<section class="hero" style="margin-top: -110px; padding-top: 180px;">
    <div class="hero-slider">
        <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1280&q=50');"></div>
        <div class="hero-slide" style="background-image: url('https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1280&q=50');"></div>
    </div>
    
    <div class="container hero-content">
        <h1 style="font-weight: 800; letter-spacing: -1px; margin-bottom: 10px;">Discover Your Next Adventure</h1>
        <p style="font-size: 1.15rem; opacity: 0.95; margin-bottom: 25px;">Discover, compare, and book premium tours and hotels from verified local agencies globally.</p>
        
        <!-- Tabbed Search Panel -->
        <div class="tab-container">
            <div class="tab-headers">
                <button type="button" class="tab-btn active" id="btn-tab-tours">
                    <i class="fas fa-compass"></i> Tours
                </button>
                <button type="button" class="tab-btn" id="btn-tab-hotels">
                    <i class="fas fa-hotel"></i> Hotels
                </button>
            </div>
            
            <div class="tab-content-panel">
                <!-- Form 1: Tours -->
                <form class="tab-panel-form active" id="form-tours" method="GET" action="tours.php">
                    <div class="form-group-custom" style="flex: 2;">
                        <label class="form-label-custom"><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Where To?</label>
                        <input type="text" name="search" class="form-input-custom" placeholder="Search destinations, tour names..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group-custom" style="flex: 1; min-width: 140px;">
                        <label class="form-label-custom"><i class="far fa-calendar-alt" style="color: var(--primary);"></i> Duration</label>
                        <select name="duration" class="form-input-custom">
                            <option value="">Any Duration</option>
                            <option value="short">1 - 5 Days</option>
                            <option value="medium">6 - 10 Days</option>
                            <option value="long">11+ Days</option>
                        </select>
                    </div>
                    
                    <div style="flex: 0.8; min-width: 120px;">
                        <button type="submit" class="btn" style="width: 100%; height: 48px; font-weight: 700; border-radius: 10px; font-size: 1rem;">Search Now</button>
                    </div>
                </form>
                
                <!-- Form 2: Hotels -->
                <form class="tab-panel-form" id="form-hotels" method="GET" action="hotels.php">
                    <div class="form-group-custom" style="flex: 2;">
                        <label class="form-label-custom"><i class="fas fa-city" style="color: var(--primary);"></i> City / Hotel</label>
                        <input type="text" name="search" class="form-input-custom" placeholder="Search hotels, cities...">
                    </div>
                    
                    <div class="form-group-custom" style="flex: 1; min-width: 140px;">
                        <label class="form-label-custom"><i class="fas fa-filter" style="color: var(--primary);"></i> Price Filter</label>
                        <select name="price" class="form-input-custom">
                            <option value="5000">Any Price</option>
                            <option value="100">Under $100</option>
                            <option value="250">Under $250</option>
                            <option value="500">Under $500</option>
                        </select>
                    </div>
                    
                    <div style="flex: 0.8; min-width: 120px;">
                        <button type="submit" class="btn btn-accent" style="width: 100%; height: 48px; font-weight: 700; border-radius: 10px; font-size: 1rem;">Search Now</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Trust Badges Row -->
        <div class="hero-features-row">
            <div class="hero-feature-item">
                <i class="fas fa-headset" style="color: var(--primary);"></i> 24/7 SAFAR Support Team
            </div>
            <div class="hero-feature-item">
                <i class="fas fa-shield-alt" style="color: var(--primary);"></i> Secure & Easy Reservation
            </div>
            <div class="hero-feature-item">
                <i class="fas fa-award" style="color: var(--primary);"></i> Verified Local Agencies
            </div>
        </div>
    </div>
</section>

<!-- Hero Slider Javascript -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tab switching logic
    const btnTours = document.getElementById('btn-tab-tours');
    const btnHotels = document.getElementById('btn-tab-hotels');
    const formTours = document.getElementById('form-tours');
    const formHotels = document.getElementById('form-hotels');
    
    btnTours.addEventListener('click', function() {
        btnTours.classList.add('active');
        btnHotels.classList.remove('active');
        formTours.classList.add('active');
        formHotels.classList.remove('active');
    });
    
    btnHotels.addEventListener('click', function() {
        btnHotels.classList.add('active');
        btnTours.classList.remove('active');
        formHotels.classList.add('active');
        formTours.classList.remove('active');
    });

    // Hero background image slider
    const slides = document.querySelectorAll('.hero-slide');
    let currentSlide = 0;
    
    setInterval(() => {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }, 5000);
});
</script>

<?php if ($is_searching): ?>
    <!-- SEARCH RESULTS SECTION -->
    <section class="landing-section">
        <div class="section-title-wrap">
            <h2>Search Results</h2>
            <p>Packages matching your search inputs.</p>
            <div class="title-line-decorator"></div>
        </div>

        <?php if (count($packages) > 0): ?>
            <div class="landing-card-grid">
                <?php foreach ($packages as $pkg): ?>
                    <?php 
                    $pkg_img = $pkg['image_url'] ?: 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=500&q=50';
                    $isFav = intval($pkg['is_favorited']) === 1;
                    $hClass = $isFav ? 'fas' : 'far';
                    $hColor = $isFav ? '#ef4444' : '#64748b';
                    ?>
                    <div style="background: #ffffff; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; position: relative;">
                        <button type="button" class="fav-heart-btn" data-id="<?php echo $pkg['id']; ?>" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); color: <?php echo $hColor; ?>; font-size: 0.95rem; z-index: 5;">
                            <i class="<?php echo $hClass; ?> fa-heart"></i>
                        </button>
                        <div style="height: 200px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($pkg_img); ?>');"></div>
                        <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                                <span>By <?php echo htmlspecialchars($pkg['company_name']); ?></span>
                            </div>
                            <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                            <p style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 15px;">$<?php echo number_format($pkg['price'], 2); ?></p>
                            <div style="display: flex; gap: 10px; margin-top: auto;">
                                <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;">View Details</a>
                                <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1; text-align: center;">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="far fa-compass" style="font-size: 2.5rem; margin-bottom: 15px; color: #cbd5e1;"></i>
                <p>No listings match your search keywords. Please adjust filters.</p>
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <!-- LANDING PAGE SECTIONS (DEFAULT MAIN HOMEPAGE) -->

    <!-- Section 1: Featured Tour Packages -->
    <section class="landing-section">
        <div class="section-title-wrap">
            <h2>Featured Tour Packages</h2>
            <p>Experience guided tours around the most beautiful places with our verified local agencies.</p>
            <div class="title-line-decorator"></div>
        </div>

        <div class="landing-card-grid">
            <?php foreach ($featured_tours as $pkg): ?>
                <?php 
                $pkg_img = $pkg['image_url'] ?: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=500&q=50';
                $isFav = intval($pkg['is_favorited']) === 1;
                $hClass = $isFav ? 'fas' : 'far';
                $hColor = $isFav ? '#ef4444' : '#64748b';
                ?>
                <div style="background: #ffffff; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; position: relative;">
                    <button type="button" class="fav-heart-btn" data-id="<?php echo $pkg['id']; ?>" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); color: <?php echo $hColor; ?>; font-size: 0.95rem; z-index: 5;">
                        <i class="<?php echo $hClass; ?> fa-heart"></i>
                    </button>
                    <div style="height: 200px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($pkg_img); ?>');"></div>
                    <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                            <span>By <?php echo htmlspecialchars($pkg['company_name']); ?></span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                        <p style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 15px;">$<?php echo number_format($pkg['price'], 2); ?></p>
                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;">View Details</a>
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1; text-align: center;">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Section 2: Why Choose SAFAR -->
    <section style="background: #f8fafc; padding: 5rem 0;">
        <div class="landing-section" style="margin: 0 auto;">
            <div class="section-title-wrap">
                <h2>Why Choose SAFAR?</h2>
                <p>We provide the best local expertise, secure checkouts, and premium services to make your holidays memorable.</p>
                <div class="title-line-decorator"></div>
            </div>

            <div class="why-choose-grid">
                <div class="why-card">
                    <div class="why-icon-wrap">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <h3>Explore Unlimited Places</h3>
                    <p>Access thousands of curated tours and stay packages across hundreds of cities, verified by our agents.</p>
                </div>
                <div class="why-card">
                    <div class="why-icon-wrap">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>Secure Reservation</h3>
                    <p>Our safe payment gateway keeps your credentials private. Instant ticket reservations are backed by flexible cancellations.</p>
                </div>
                <div class="why-card">
                    <div class="why-icon-wrap">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Verified Local Agencies</h3>
                    <p>Deal directly with verified, certified tourism agencies. No middle-man pricing or hidden expenses.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 3: Featured Hotel Stays -->
    <section class="landing-section">
        <div class="section-title-wrap">
            <h2>Featured Stays & Hotels</h2>
            <p>Book premium hotel reservations, villas, and resort rooms worldwide.</p>
            <div class="title-line-decorator"></div>
        </div>

        <div class="landing-card-grid">
            <?php foreach ($featured_hotels as $pkg): ?>
                <?php 
                $pkg_img = $pkg['image_url'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=50';
                $isFav = intval($pkg['is_favorited']) === 1;
                $hClass = $isFav ? 'fas' : 'far';
                $hColor = $isFav ? '#ef4444' : '#64748b';
                ?>
                <div style="background: #ffffff; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; position: relative;">
                    <button type="button" class="fav-heart-btn" data-id="<?php echo $pkg['id']; ?>" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); color: <?php echo $hColor; ?>; font-size: 0.95rem; z-index: 5;">
                        <i class="<?php echo $hClass; ?> fa-heart"></i>
                    </button>
                    <div style="height: 200px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($pkg_img); ?>');"></div>
                    <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                            <span>By <?php echo htmlspecialchars($pkg['company_name']); ?></span>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                        <p style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 15px;">$<?php echo number_format($pkg['price'], 2); ?> <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-muted);">/ night</span></p>
                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;">View Details</a>
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1; text-align: center;">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Section 4: How It Works -->
    <section style="background: #fafafa; padding: 5rem 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
        <div class="landing-section" style="margin: 0 auto;">
            <div class="section-title-wrap">
                <h2>How It Works</h2>
                <p>Book your dream travel plan in three simple, quick steps.</p>
                <div class="title-line-decorator"></div>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number-badge">1</div>
                    <h3>Find Your Destination</h3>
                    <p>Search and compare premium guided tour packages and hotel bookings easily.</p>
                </div>
                <div class="step-card">
                    <div class="step-number-badge">2</div>
                    <h3>Book Your Vouchers</h3>
                    <p>Pick dates, provide basic travel details, and checkout securely in seconds.</p>
                </div>
                <div class="step-card">
                    <div class="step-number-badge">3</div>
                    <h3>Enjoy Your Vacation</h3>
                    <p>Receive tickets instantly and coordinate with your local certified agents.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Client Reviews & Testimonial -->
    <section class="landing-section">
        <div class="section-title-wrap">
            <h2>Traveler Reviews</h2>
            <p>See what our global travelers are saying about their vacation reservations.</p>
            <div class="title-line-decorator"></div>
        </div>

        <div class="testimonial-bubble-card">
            <p class="testimonial-quote">"SAFAR made my trip to Goa absolutely seamless. Bookmarked my package on the dashboard, booked in seconds, and had a verified local guide ready when I arrived. The redesigned traveler dashboard scheduler is a lifesaver!"</p>
            <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&h=100&fit=crop&q=80" alt="Reviewer User" class="testimonial-author-avatar">
            <div class="testimonial-stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
            </div>
            <h4 style="color: #0f172a; font-weight: 700; font-size: 0.95rem; margin: 0;">Nazia Rahman Shirin</h4>
            <span style="color: var(--text-muted); font-size: 0.8rem;">Travel Enthusiast</span>
        </div>
    </section>

    <!-- Section 6: Dynamic Call To Action -->
    <section class="landing-section">
        <div class="cta-banner-container">
            <h2>Ready For Your Next Vacation?</h2>
            <p>Join thousands of travelers bookmarking, scheduling, and enjoying custom holiday plans globally.</p>
            <a href="signup.php" class="btn" style="background: var(--primary); padding: 15px 40px; border-radius: 30px; font-weight: 700; font-size: 1.05rem; box-shadow: 0 4px 20px rgba(255, 125, 75, 0.4);">Register Account Now</a>
        </div>
    </section>
<?php endif; ?>

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
            .then(res => {
                if (res.status === 401) {
                    alert('Please log in to add favorites.');
                    window.location.href = 'login.php';
                    return;
                }
                return res.json();
            })
            .then(data => {
                if (data && data.status === 'success') {
                    if (data.action === 'added') {
                        icon.className = 'fas fa-heart';
                        this.style.color = '#ef4444';
                    } else {
                        icon.className = 'far fa-heart';
                        this.style.color = '#64748b';
                    }
                }
            })
            .catch(err => console.error(err));
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
