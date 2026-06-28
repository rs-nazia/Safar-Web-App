<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$agency_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch agency details
$stmt = $pdo->prepare("SELECT a.id as agency_id, a.company_name, u.name as agent_name, u.email, u.phone, u.bio, u.location, u.profile_image 
                       FROM agencies a 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.id = ?");
$stmt->execute([$agency_id]);
$agency = $stmt->fetch();

if (!$agency) {
    header("Location: agencies.php");
    exit();
}

// Fetch all active packages for this specific agency
$stmt = $pdo->prepare("SELECT * FROM packages WHERE agency_id = ? ORDER BY created_at DESC");
$stmt->execute([$agency_id]);
$packages = $stmt->fetchAll();

$avatar_url = $agency['profile_image'] ? BASE_URL . '/' . $agency['profile_image'] : null;
$location = $agency['location'] ? htmlspecialchars($agency['location']) : 'Global Provider';
?>

<div class="container my-4" style="max-width: 1200px; padding: 0 20px;">
    <!-- Agency Profile Header Card -->
    <div style="background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(0,0,0,0.06); box-shadow: var(--shadow-sm); overflow: hidden; margin-top: 25px; margin-bottom: 40px; display: flex; flex-wrap: wrap;">
        <!-- Left: Profile branding -->
        <div style="flex: 1; min-width: 300px; padding: 40px; background: #fafafa; border-right: 1px solid #f1f5f9; display: flex; flex-direction: column; align-items: center; text-align: center; justify-content: center;">
            <?php if ($avatar_url): ?>
                <img src="<?php echo $avatar_url; ?>" alt="<?php echo htmlspecialchars($agency['company_name']); ?>" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #ffffff; box-shadow: var(--shadow-md); margin-bottom: 20px;">
            <?php else: ?>
                <div style="width: 120px; height: 120px; border-radius: 50%; background: #f0fdfa; border: 4px solid #ffffff; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: var(--shadow-md);">
                    <i class="fas fa-building" style="font-size: 3rem; color: #009688;"></i>
                </div>
            <?php endif; ?>

            <h2 style="font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px;"><?php echo htmlspecialchars($agency['company_name']); ?></h2>
            <span style="font-size: 0.95rem; color: var(--text-muted); font-weight: 500; margin-bottom: 15px;">Primary Representative: <?php echo htmlspecialchars($agency['agent_name']); ?></span>

            <div style="font-size: 0.9rem; color: #475569; background: #e2e8f0; padding: 5px 15px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> <?php echo $location; ?>
            </div>
        </div>

        <!-- Right: Profile Details -->
        <div style="flex: 2; min-width: 320px; padding: 40px; display: flex; flex-direction: column; justify-content: center;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 12px; border-bottom: 2px solid var(--primary); padding-bottom: 6px; align-self: flex-start;">About Agency</h3>
            <p style="font-size: 1rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 25px;">
                <?php echo $agency['bio'] ? nl2br(htmlspecialchars($agency['bio'])) : 'No biography or description has been provided by this travel agency yet.'; ?>
            </p>

            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 12px; border-bottom: 2px solid var(--primary); padding-bottom: 6px; align-self: flex-start;">Contact Information</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 20px 40px; font-size: 0.95rem; color: var(--text-muted);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 125, 75, 0.08); display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Email Address</span>
                        <a href="mailto:<?php echo htmlspecialchars($agency['email']); ?>" style="color: var(--text-main); font-weight: 600; text-decoration: none;"><?php echo htmlspecialchars($agency['email']); ?></a>
                    </div>
                </div>

                <?php if ($agency['phone']): ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(0, 150, 136, 0.08); display: flex; align-items: center; justify-content: center; color: #009688;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Phone Number</span>
                            <a href="tel:<?php echo htmlspecialchars($agency['phone']); ?>" style="color: var(--text-main); font-weight: 600; text-decoration: none;"><?php echo htmlspecialchars($agency['phone']); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Agency Listings Catalog Grid -->
    <div style="margin-bottom: 60px;">
        <div style="border-bottom: 2px dashed #e2e8f0; padding-bottom: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0;">Active Tour & stay Packages</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 5px;">Packages posted directly by <?php echo htmlspecialchars($agency['company_name']); ?>.</p>
            </div>
            <span class="badge badge-approved" style="font-size: 1rem; padding: 6px 15px;"><?php echo count($packages); ?> Packages Offered</span>
        </div>

        <?php if (count($packages) > 0): ?>
            <?php
            $fallback_images = [
                'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=500&q=60',
                'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=500&q=60',
                'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=500&q=60',
                'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=60',
            ];
            $fb_index = 0;
            ?>
            <div class="packages-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 25px;">
                <?php foreach ($packages as $pkg): ?>
                    <?php
                    $card_image = $pkg['image_url'] ?: $fallback_images[$fb_index % count($fallback_images)];
                    $fb_index++;
                    $type_label = $pkg['type'] ? ucfirst($pkg['type']) : 'Tour';
                    $type_color_bg = ($pkg['type'] === 'hotel') ? '#ecfdf5' : '#fff7ed';
                    $type_color_text = ($pkg['type'] === 'hotel') ? '#047857' : '#c2410c';
                    ?>
                    <div style="background: var(--card-bg); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: transform 0.25s ease;"
                         onmouseover="this.style.transform='translateY(-4px)';" onmouseout="this.style.transform='none';">
                        
                        <!-- Image wrapper with type badge -->
                        <div style="height: 200px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($card_image); ?>'); position: relative;">
                            <span style="position: absolute; top: 15px; left: 15px; background: <?php echo $type_color_bg; ?>; color: <?php echo $type_color_text; ?>; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?php echo $type_label; ?>
                            </span>
                        </div>

                        <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.85rem; color: var(--text-muted);">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                                <span>By <?php echo htmlspecialchars($agency['company_name']); ?></span>
                            </div>
                            
                            <h3 style="font-size: 1.2rem; margin-bottom: 8px; font-weight: 700; line-height: 1.4; color: var(--text-main);"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                            
                            <p style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 15px;">
                                $<?php echo number_format($pkg['price'], 2); ?>
                                <?php if ($pkg['type'] === 'hotel'): ?>
                                    <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);">/ night</span>
                                <?php endif; ?>
                            </p>
                            
                            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 20px; flex-grow: 1;">
                                <?php echo htmlspecialchars(substr($pkg['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div style="display: flex; gap: 10px; margin-top: auto;">
                                <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline" style="flex: 1; border-radius: 8px; padding: 8px;">View Details</a>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'traveler'): ?>
                                    <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn" style="flex: 1; border-radius: 8px; padding: 8px;">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn" style="flex: 1; border-radius: 8px; padding: 8px;">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; background: #fafafa; border-radius: 12px; border: 1px dashed #cbd5e1; color: var(--text-muted);">
                <i class="far fa-folder-open" style="font-size: 2.5rem; margin-bottom: 12px; color: #cbd5e1;"></i>
                <p style="font-size: 0.95rem;">No active travel packages currently offered by this agency.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
