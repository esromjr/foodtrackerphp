<?php
session_start();

// Generate CAPTCHA if not exists
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
    $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/database.php';

$login_error = '';
$signup_error = '';
$signup_success = '';
$active_tab = 'login'; // Default to login tab

// ===== HANDLE LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $active_tab = 'login';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $login_error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['last_activity'] = time();
            header("Location: dashboard.php");
            exit;
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}

// ===== HANDLE SIGNUP (WITH CAPTCHA) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $active_tab = 'signup';
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email_signup'] ?? '');
    $password = $_POST['password_signup'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $captcha  = (int)($_POST['captcha'] ?? 0);

    // CAPTCHA validation FIRST
    if ($captcha !== $_SESSION['captcha_answer']) {
        $signup_error = ' CAPTCHA answer is incorrect. Please try again.';
        // Generate new CAPTCHA for next attempt
        $_SESSION['captcha_num1'] = rand(1, 10);
        $_SESSION['captcha_num2'] = rand(1, 10);
        $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
    } elseif (empty($fullname) || empty($email) || empty($password) || empty($confirm)) {
        $signup_error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $signup_error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $signup_error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $signup_error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $fullname, $email, $hashed);

            if ($insert->execute()) {
                $signup_success = 'Account created successfully! Please log in.';
                $active_tab = 'login'; // Switch to login tab on success
                // Reset CAPTCHA after successful signup
                $_SESSION['captcha_num1'] = rand(1, 10);
                $_SESSION['captcha_num2'] = rand(1, 10);
                $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
            } else {
                $signup_error = 'Registration failed. Please try again.';
            }
            $insert->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethiopian Food Calorie Tracker — Welcome</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles for tabbed interface */
        .auth-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.8rem;
            border-bottom: 2px solid var(--cream-dark);
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-mid);
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px 8px 0 0;
            position: relative;
        }
        .tab-btn:hover {
            color: var(--green);
            background: rgba(45,106,79,0.05);
        }
        .tab-btn.active {
            color: var(--green);
            background: var(--cream);
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--green);
            border-radius: 2px;
        }
        .auth-form {
            display: none;
        }
        .auth-form.active {
            display: block;
        }
        .auth-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }
        .auth-card h2 {
            display: none;
        }
    </style>
</head>
<body class="landing-wrapper">

<div class="landing-hero">
    <h1> Ethiopian Food Calorie Tracker</h1>
    <p>Track the nutrition of your favourite traditional Ethiopian dishes and stay on top of your health goals.</p>
</div>

<?php if (isset($_GET['logout'])): ?>
    <div style="text-align:center;margin-bottom:1rem;">
        <div class="alert alert-info" style="display:inline-block;">You have been logged out successfully.</div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['timeout'])): ?>
    <div style="text-align:center;margin-bottom:1rem;">
        <div class="alert alert-error" style="display:inline-block;">Your session expired. Please log in again.</div>
    </div>
<?php endif; ?>

<div class="auth-cards" style="display: block;">
    <div class="auth-card">
        
        <!-- TABS -->
        <div class="auth-tabs">
            <button type="button" class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')"> Login</button>
            <button type="button" class="tab-btn <?= $active_tab === 'signup' ? 'active' : '' ?>" onclick="switchTab('signup')"> Create Account</button>
        </div>

        <!-- LOGIN FORM -->
        <div id="login-form" class="auth-form <?= $active_tab === 'login' ? 'active' : '' ?>">
            <?php if ($login_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST" action="landing.php">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group" style="text-align: right;">
                    <a href="forgot_password.php" style="font-size: 0.8rem; color: #C0533B;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary">Login →</button>
            </form>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signup-form" class="auth-form <?= $active_tab === 'signup' ? 'active' : '' ?>">
            <?php if ($signup_error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($signup_error) ?></div>
            <?php endif; ?>
            <?php if ($signup_success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($signup_success) ?></div>
            <?php endif; ?>
            <form method="POST" action="landing.php">
                <input type="hidden" name="action" value="signup">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Abebe Girma" required>
                </div>
                <div class="form-group">
                    <label for="email_signup">Email Address</label>
                    <input type="email" id="email_signup" name="email_signup" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password_signup">Password</label>
                    <input type="password" id="password_signup" name="password_signup" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>
                
                <!-- CAPTCHA FIELD -->
                <div class="form-group">
                    <label for="captcha"> CAPTCHA: What is <?= $_SESSION['captcha_num1'] ?> + <?= $_SESSION['captcha_num2'] ?> ?</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="captcha" name="captcha" placeholder="Enter the answer" required style="width: 120px;">
                        <button type="button" onclick="refreshCaptcha()" style="background: #f0f0f0; border: 1px solid #ccc; padding: 8px 12px; border-radius: 6px; cursor: pointer;">🔄 New Numbers</button>
                    </div>
                    <small style="color: #666;">Prove you're human by solving the math problem</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account →</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <p> Ethiopian Food Calorie Tracker</p>
</footer>

<script>
// Switch between Login and Signup tabs
function switchTab(tab) {
    // Update button styles
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (tab === 'login') {
        document.querySelector('.tab-btn:first-child').classList.add('active');
        document.getElementById('login-form').classList.add('active');
        document.getElementById('signup-form').classList.remove('active');
    } else {
        document.querySelector('.tab-btn:last-child').classList.add('active');
        document.getElementById('signup-form').classList.add('active');
        document.getElementById('login-form').classList.remove('active');
    }
}

// Refresh CAPTCHA
function refreshCaptcha() {
    location.reload();
}

// If there's an error on signup, show signup tab
<?php if ($signup_error && $active_tab === 'signup'): ?>
// Already on signup tab
<?php elseif ($signup_error): ?>
switchTab('signup');
<?php endif; ?>
</script>
<script src="js/script.js"></script>
</body>
</html>