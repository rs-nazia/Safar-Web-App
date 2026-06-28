<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/pages/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "/admin/index.php");
            } elseif ($user['role'] === 'agency') {
                header("Location: " . BASE_URL . "/dashboard/agency.php");
            } else {
                header("Location: " . BASE_URL . "/dashboard/traveler.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<style>
    /* Page wrapper styling to sit between header and footer */
    .login-page-wrapper {
        position: relative;
        min-height: calc(100vh - 110px);
        background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=80');
        background-size: cover;
        background-position: center;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 60px 20px;
        margin-top: -30px; /* Slight offset to align with navbar margin */
    }

    /* Overlay context restricted to wrapper */
    .login-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.25);
        z-index: 1;
    }

    /* Outer Glassmorphic Card Container */
    .login-glass-card {
        position: relative;
        z-index: 2;
        width: 1000px;
        max-width: 100%;
        min-height: 550px;
        backdrop-filter: blur(15px) saturate(120%);
        -webkit-backdrop-filter: blur(15px) saturate(120%);
        background-color: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        display: flex;
        overflow: hidden;
        color: #ffffff;
    }



    /* Left panel: Info & Dream Destination Branding */
    .login-left-panel {
        flex: 1.2;
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: left;
        background: rgba(0, 0, 0, 0.1);
        border-right: 1px solid rgba(255, 255, 255, 0.15);
    }

    .login-left-panel h1 {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 5px;
        color: #ffffff;
        letter-spacing: -1px;
    }

    .login-left-panel h1 span {
        display: block;
    }

    .login-left-panel p {
        font-family: 'Georgia', serif;
        font-style: italic;
        font-size: 1.5rem;
        opacity: 0.9;
        margin-top: 5px;
        color: rgba(255, 255, 255, 0.85);
    }

    /* Right panel: Login form inside an inner glass frame */
    .login-right-panel {
        flex: 1;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-form-box {
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        background-color: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: 40px 30px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }

    /* Tab styles */
    .login-tab-headers {
        display: flex;
        gap: 30px;
        margin-bottom: 30px;
        border-bottom: 1.5px solid rgba(255, 255, 255, 0.15);
        padding-bottom: 10px;
    }

    .login-tab-link {
        font-size: 1.4rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.65);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
    }

    .login-tab-link:hover {
        color: #ffffff;
    }

    .login-tab-link.active {
        color: #ffffff;
    }

    .login-tab-link.active::after {
        content: '';
        position: absolute;
        bottom: -11.5px;
        left: 0;
        right: 0;
        height: 3px;
        background: #ffffff;
        border-radius: 3px;
    }

    /* Input elements styling */
    .login-input-group {
        position: relative;
        margin-bottom: 22px;
    }

    .login-input-field {
        width: 100%;
        padding: 14px 45px 14px 20px;
        border-radius: 30px;
        border: 1.5px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.25s ease;
    }

    .login-input-field::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    .login-input-field:focus {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.6);
        box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
    }

    .login-input-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.75);
        font-size: 1.1rem;
    }

    /* Primary Log In button aligned with SAFAR brand color */
    .login-green-btn {
        width: 100%;
        background-color: var(--primary, #FF7D4B);
        color: #ffffff;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 14px;
        border-radius: 30px;
        border: none;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 15px rgba(255, 125, 75, 0.3);
        margin-top: 10px;
        margin-bottom: 20px;
    }

    .login-green-btn:hover {
        background-color: #e06232;
        box-shadow: 0 6px 20px rgba(255, 125, 75, 0.45);
        transform: translateY(-1px);
    }

    .login-green-btn:active {
        transform: translateY(0);
    }

    .login-forgot-link {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: color 0.2s ease;
    }

    .login-forgot-link:hover {
        color: #ffffff;
        text-decoration: underline;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .login-glass-card {
            flex-direction: column;
            width: 500px;
            max-width: 95%;
        }
        .login-left-panel {
            padding: 40px;
            text-align: center;
            border-right: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .login-left-panel h1 {
            font-size: 2.8rem;
        }
        .login-right-panel {
            padding: 40px 30px;
        }
    }

    @media (max-width: 575.98px) {
        .login-glass-card {
            border-radius: 16px;
        }
        .login-left-panel {
            padding: 30px 20px;
        }
        .login-left-panel h1 {
            font-size: 2.2rem;
        }
        .login-left-panel p {
            font-size: 1.1rem;
        }
        .login-right-panel {
            padding: 25px 15px;
        }
        .login-form-box {
            padding: 25px 20px;
            border-radius: 16px;
        }
        .login-tab-headers {
            margin-bottom: 20px;
            gap: 20px;
        }
        .login-tab-link {
            font-size: 1.2rem;
        }
    }
</style>

<div class="login-page-wrapper">
    <div class="login-overlay"></div>
    
    <div class="login-glass-card">
        <!-- Left Side Dream Destination Panel -->
        <div class="login-left-panel">
            <h1>
                <span>Dream</span>
                <span>Destination</span>
            </h1>
            <p>Travel with the best</p>
        </div>
        
        <!-- Right Side Form Panel -->
        <div class="login-right-panel">
            <div class="login-form-box">
                <div class="login-tab-headers">
                    <a href="login.php" class="login-tab-link active">Log in</a>
                    <a href="<?php echo BASE_URL; ?>/pages/signup.php" class="login-tab-link">Sign up</a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fecaca; border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 0.85rem; margin-bottom: 20px; text-align: center;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="login-input-group">
                        <input type="email" name="email" required placeholder="Enter username / email" class="login-input-field" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <i class="fas fa-user login-input-icon"></i>
                    </div>
                    
                    <div class="login-input-group">
                        <input type="password" name="password" required placeholder="Enter password" class="login-input-field">
                        <i class="fas fa-lock login-input-icon"></i>
                    </div>
                    
                    <button type="submit" class="login-green-btn">Log in</button>
                    
                    <a href="#" class="login-forgot-link">Forgot password?</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
