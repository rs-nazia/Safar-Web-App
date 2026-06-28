<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Fetch all verified agencies with active package count in a single query
$query = "SELECT a.id as agency_id, a.company_name, u.name as agent_name, u.email, u.phone, u.bio, u.location, u.profile_image,
          (SELECT COUNT(*) FROM packages WHERE agency_id = a.id) as package_count 
          FROM agencies a 
          JOIN users u ON a.user_id = u.id 
          ORDER BY a.company_name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$agencies = $stmt->fetchAll();
?>

<div class="container my-4" style="max-width: 1200px; padding: 0 20px;">
    <div style="text-align: center; margin-bottom: 40px; margin-top: 20px;">
        <h1 style="color: var(--primary); font-weight: 800; font-size: 2.5rem; letter-spacing: -0.5px;">Registered Travel Agencies</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 10px auto 0;">
            Compare, consult, and book customized tour itineraries directly with our verified local agencies.
        </p>
    </div>

    <?php if (count($agencies) > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <?php foreach ($agencies as $agency): ?>
                <?php 
                $avatar_url = $agency['profile_image'] ? BASE_URL . '/' . $agency['profile_image'] : null;
                $bio_preview = $agency['bio'] ? htmlspecialchars($agency['bio']) : 'No bio details provided by the agency.';
                if (strlen($bio_preview) > 110) {
                    $bio_preview = substr($bio_preview, 0, 110) . '...';
                }
                $location = $agency['location'] ? htmlspecialchars($agency['location']) : 'Global Provider';
                ?>
                <div style="background: var(--card-bg); border-radius: 16px; border: 1px solid rgba(0,0,0,0.06); box-shadow: var(--shadow-sm); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.25s ease, box-shadow 0.25s ease;" 
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='var(--shadow-md)';" 
                     onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
                    
                    <!-- Top accent band -->
                    <div style="height: 6px; background: linear-gradient(90deg, var(--primary) 0%, #009688 100%);"></div>
                    
                    <div style="padding: 30px; display: flex; flex-direction: column; flex-grow: 1; text-align: center; align-items: center;">
                        <!-- Avatar / Logo -->
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo $avatar_url; ?>" alt="<?php echo htmlspecialchars($agency['company_name']); ?>" 
                                 style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 15px;">
                        <?php else: ?>
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: #f0fdfa; border: 3px solid #f1f5f9; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                <i class="fas fa-building" style="font-size: 2rem; color: #009688;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Names -->
                        <h3 style="font-size: 1.35rem; font-weight: 700; color: var(--text-main); margin-bottom: 3px;"><?php echo htmlspecialchars($agency['company_name']); ?></h3>
                        <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; display: block; margin-bottom: 12px;">Agent: <?php echo htmlspecialchars($agency['agent_name']); ?></span>
                        
                        <!-- Location badge -->
                        <div style="font-size: 0.85rem; color: #475569; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 15px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> <?php echo $location; ?>
                        </div>
                        
                        <!-- Bio preview -->
                        <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 20px; flex-grow: 1;">
                            <?php echo $bio_preview; ?>
                        </p>
                        
                        <!-- Stats bar -->
                        <div style="width: 100%; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-bottom: 20px; display: flex; justify-content: space-around; font-size: 0.9rem;">
                            <div>
                                <span style="display: block; font-weight: 700; color: var(--text-main); font-size: 1.1rem;"><?php echo $agency['package_count']; ?></span>
                                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Active Packages</span>
                            </div>
                        </div>
                        
                        <!-- Button Link -->
                        <a href="agency-details.php?id=<?php echo $agency['agency_id']; ?>" class="btn" style="width: 100%; border-radius: 10px; font-weight: 600; padding: 10px;">
                            View Agency Profile <i class="fas fa-arrow-right" style="margin-left: 5px; font-size: 0.85rem;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: var(--card-bg); border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); margin-bottom: 50px;">
            <i class="fas fa-store-slash" style="font-size: 3.5rem; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-main); font-size: 1.3rem;">No Registered Agencies</h3>
            <p style="color: var(--text-muted); margin-top: 5px;">There are no travel agencies registered on the platform at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
