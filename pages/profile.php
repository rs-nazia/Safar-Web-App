<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $stmt->execute([$name, $email, $hashed, $profile_image, $phone, $bio, $location, $user_id]);
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
        $success = "Profile updated successfully.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once '../includes/header.php';
?>

<style>
    .profile-card {
        width: 100%;
        max-width: 550px;
        background: #ffffff;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.05);
        padding: 50px 40px;
        border-radius: 20px;
        border-top: 6px solid var(--primary);
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 30px -10px rgba(0,0,0,0.08);
    }

    .avatar-wrapper {
        position: relative;
        width: 130px;
        height: 130px;
        margin: 0 auto 25px;
    }

    .avatar-preview-box {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: var(--bg-light);
        overflow: hidden;
        border: 4px solid var(--primary);
        box-shadow: var(--shadow-sm);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .avatar-wrapper:hover .avatar-preview-box {
        transform: scale(1.03);
        border-color: var(--secondary);
    }

    .upload-trigger-btn {
        position: absolute;
        bottom: 2px;
        right: 2px;
        background: var(--primary);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(255, 125, 75, 0.3);
        transition: all 0.2s ease;
        font-size: 1rem;
    }

    .upload-trigger-btn:hover {
        background: var(--primary-dark);
        transform: scale(1.1);
    }

    .interactive-input {
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

    .interactive-input:focus {
        border-color: var(--primary);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(255, 125, 75, 0.12);
        outline: none;
    }

    .password-container {
        position: relative;
    }

    .password-toggle-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #64748b;
        transition: color 0.2s ease;
        font-size: 1.1rem;
    }

    .password-toggle-icon:hover {
        color: var(--primary);
    }

    .interactive-btn {
        width: 100%;
        margin-top: 25px;
        font-size: 1.05rem;
        font-weight: 700;
        padding: 14px;
        border-radius: 10px;
        background: var(--primary);
        color: white;
        border: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(255, 125, 75, 0.2);
    }

    .interactive-btn:hover {
        background: var(--primary-dark);
        box-shadow: 0 6px 18px rgba(255, 125, 75, 0.3);
        transform: translateY(-1px);
    }

    .interactive-btn:active {
        transform: translateY(0);
    }
</style>

<div style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 220px); padding: 50px 20px; background: #f8fafc;">
    <div class="profile-card">
        <h2 style="color: #0f172a; text-align: center; margin-bottom: 30px; font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px;">My Account Profile</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success" style="text-align: center; border-radius: 10px; margin-bottom: 25px; font-weight: 600; animation: fadeIn 0.4s ease;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="profile-form">
            <!-- Dynamic Avatar Uploader -->
            <div style="text-align: center; margin-bottom: 35px;">
                <div class="avatar-wrapper">
                    <div class="avatar-preview-box">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img id="profile-img-preview" src="<?php echo BASE_URL . '/' . htmlspecialchars($user['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <img id="profile-img-preview" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23FF7D4B'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;" />
                        <?php endif; ?>
                    </div>
                    <label for="profile-upload" class="upload-trigger-btn">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input id="profile-upload" type="file" name="profile_image" accept="image/*" style="display: none;">
                </div>
                <div id="upload-status" style="font-size: 0.85rem; color: #009688; font-weight: 700; height: 18px; margin-top: -10px;"></div>
            </div>

            <!-- Role Badge (Disabled Input) -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Account Role</label>
                <input type="text" disabled value="<?php echo ucfirst($user['role']); ?>" style="width: 100%; padding: 12px 16px; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 0.95rem; background-color: var(--bg-light); cursor: not-allowed; font-weight: 700; color: var(--primary);">
            </div>

            <!-- Name Input -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">Full Name</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="interactive-input" placeholder="e.g. Nazia Rahman">
            </div>

            <!-- Email Input -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">Email Address</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="interactive-input" placeholder="name@domain.com">
            </div>

            <!-- Phone Input -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="interactive-input" placeholder="+1 234 567 8900">
            </div>

            <!-- Location Input -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">Location (City, Country)</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" class="interactive-input" placeholder="e.g. Paris, France">
            </div>

            <!-- Bio Input -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">About Me / Bio</label>
                <textarea name="bio" rows="3" class="interactive-input" style="resize: vertical; min-height: 80px;" placeholder="Tell us about yourself or your agency..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <!-- Password Input with Show/Hide visibility icon -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 700; color: #475569; font-size: 0.85rem;">Update Password <span style="font-size: 0.78rem; color: #64748b; font-weight: 400; margin-left: 2px;">(Leave blank to keep current)</span></label>
                <div class="password-container">
                    <input id="password-input" type="password" name="password" minlength="6" placeholder="••••••••" class="interactive-input" style="padding-right: 45px;">
                    <i id="toggle-pwd-icon" class="fas fa-eye password-toggle-icon"></i>
                </div>
            </div>

            <button type="submit" class="interactive-btn">Save Changes</button>
        </form>
    </div>
</div>

<script>
    // 1. Instant Avatar Preview logic
    document.getElementById('profile-upload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert("File size limit is 5MB.");
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('profile-img-preview').src = event.target.result;
            }
            reader.readAsDataURL(file);
            document.getElementById('upload-status').innerText = '✓ Image selected! Save changes to apply.';
        }
    });

    // 2. Password show/hide visibility toggle
    document.getElementById('toggle-pwd-icon').addEventListener('click', function() {
        const pwdInput = document.getElementById('password-input');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            pwdInput.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
