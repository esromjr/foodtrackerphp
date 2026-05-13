<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: landing.php"); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_unset(); session_destroy(); header("Location: landing.php?timeout=1"); exit;
}
$_SESSION['last_activity'] = time();

require_once 'config/database.php';
$user_id = $_SESSION['user_id'];

$success = '';
$error   = '';

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { session_unset(); session_destroy(); header("Location: landing.php"); exit; }

// ===== CHANGE PASSWORD =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_new'] ?? '';

    if (empty($current) || empty($new_pw) || empty($confirm)) {
        $error = 'Please fill in all password fields.';
    } elseif (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_pw) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $upd    = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $user_id);
        $upd->execute() ? $success = 'Password changed successfully!' : $error = 'Failed to update password.';
        $upd->close();
    }
}

// ===== DELETE ACCOUNT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    // Delete logs then user
    $d1 = $conn->prepare("DELETE FROM food_logs WHERE user_id = ?");
    $d1->bind_param("i", $user_id);
    $d1->execute(); $d1->close();

    $d2 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $d2->bind_param("i", $user_id);
    $d2->execute(); $d2->close();

    session_unset(); session_destroy();
    header("Location: landing.php");
    exit;
}

// Stat: total logs
$ls = $conn->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(fl.quantity_grams * f.calories_per_100g / 100),0) as total FROM food_logs fl JOIN foods f ON fl.food_id=f.id WHERE fl.user_id=?");
$ls->bind_param("i", $user_id);
$ls->execute();
$log_stats = $ls->get_result()->fetch_assoc();
$ls->close();

include 'header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1> My Profile</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="profile-grid">

        <!-- USER INFO -->
        <div class="card" style="text-align:center;">
            <div class="profile-avatar"><?= strtoupper(substr($user['fullname'], 0, 1)) ?></div>
            <div class="profile-info-row">
                <div class="profile-info-label">Full Name</div>
                <div class="profile-info-value"><?= htmlspecialchars($user['fullname']) ?></div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-label">Email Address</div>
                <div class="profile-info-value"><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-label">Member Since</div>
                <div class="profile-info-value"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-label">Total Meals Logged</div>
                <div class="profile-info-value"><?= $log_stats['cnt'] ?> entries</div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-label">Total Calories Tracked</div>
                <div class="profile-info-value"><?= number_format(round($log_stats['total'])) ?> kcal</div>
            </div>

            <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cream-dark);">
                <button class="btn btn-danger btn-sm" onclick="confirmDeleteAccount()"> Delete Account</button>
            </div>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="card">
            <div class="card-title"> Change Password</div>
            <form method="POST" style="margin-top:1rem">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_new" placeholder="Repeat new password" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password →</button>
            </form>
        </div>

    </div>
</div>

<!-- DELETE ACCOUNT FORM (hidden) -->
<form method="POST" id="deleteAccountForm" style="display:none">
    <input type="hidden" name="action" value="delete_account">
</form>

<?php include 'footer.php'; ?>