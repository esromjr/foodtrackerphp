<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout: 1 hour
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_unset();
    session_destroy();
    header("Location: landing.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Get current page for active nav
$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethiopian Food Calorie Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <div class="header-inner">
        <a href__="dashboard.php" class="logo">
            <span></span> EthioCalorie
        </a>
        <nav>
            <a href="dashboard.php" class="<?= isActive('dashboard.php') ?>"> Dashboard</a>
            <a href="add_food.php" class="<?= isActive('add_food.php') ?>"> Add Food</a>
            <a href="tracker.php" class="<?= isActive('tracker.php') ?>"> Tracker</a>
            <a href="profile.php" class="<?= isActive('profile.php') ?>"> Profile</a>
            <a href="logout.php" class="btn-logout"> Logout</a>
        </nav>
    </div>
</header>