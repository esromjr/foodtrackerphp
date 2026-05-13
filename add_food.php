<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: landing.php"); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_unset(); session_destroy(); header("Location: landing.php?timeout=1"); exit;
}
$_SESSION['last_activity'] = time();

require_once 'config/database.php';

$success = '';
$error   = '';
$rate_limited = false;

// ===== RATE LIMITING: Check if user added food in last hour =====
$user_id = $_SESSION['user_id'];

$rate_check = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM foods 
    WHERE added_by = ? 
    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$rate_check->bind_param("i", $user_id);
$rate_check->execute();
$rate_result = $rate_check->get_result()->fetch_assoc();
$rate_check->close();

// If user already added 1+ foods in last hour, block them
if ($rate_result['count'] >= 1) {
    $rate_limited = true;
}

// ===== PROCESS FORM SUBMISSION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check rate limit first
    if ($rate_limited) {
        $error = ' RATE LIMIT: You can only add ONE food per hour. Please wait before adding another food.';
    } else {
        $name     = trim($_POST['food_name'] ?? '');
        $calories = (int)($_POST['calories_per_100g'] ?? 0);
        $protein  = (float)($_POST['protein_g'] ?? 0);
        $carbs    = (float)($_POST['carbs_g'] ?? 0);
        $fat      = (float)($_POST['fat_g'] ?? 0);
        $type     = trim($_POST['food_type'] ?? '');

        if (empty($name) || $calories <= 0 || empty($type)) {
            $error = 'Please provide food name, calories, and food type.';
        } else {
            // Check for duplicate food name
            $dup_check = $conn->prepare("SELECT id FROM foods WHERE LOWER(food_name) = LOWER(?)");
            $dup_check->bind_param("s", $name);
            $dup_check->execute();
            $dup_exists = $dup_check->get_result()->num_rows > 0;
            $dup_check->close();
            
            if ($dup_exists) {
                $error = ' This food already exists in the database!';
            } else {
                // Insert with user tracking (added_by)
                $stmt = $conn->prepare("INSERT INTO foods (food_name, calories_per_100g, protein_g, carbs_g, fat_g, food_type, added_by) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("siiddsi", $name, $calories, $protein, $carbs, $fat, $type, $user_id);
                if ($stmt->execute()) {
                    $success = "✓ \"$name\" was added successfully!";
                    // Reset rate limit check after successful insert
                    $rate_limited = true;
                } else {
                    $error = 'Failed to add food. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

// Recent foods (show last 8)
$recent = $conn->query("SELECT * FROM foods ORDER BY id DESC LIMIT 8");

include 'header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1> Add New Food</h1>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back to Dashboard</a>
    </div>

    <!-- Rate Limit Warning Banner -->
    <?php if ($rate_limited && !$success): ?>
    <div class="alert alert-warning" style="background:#fff3cd; color:#856404; border-left:4px solid #ffc107;">
         <strong>Rate Limit Active:</strong> You have already added a food in the last hour. 
    
    </div>
    <?php endif; ?>

    <div class="add-food-grid">

        <!-- FORM -->
        <div class="card">
            <div class="card-title">Food Details</div>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="food_name">Food Name *</label>
                    <input type="text" id="food_name" name="food_name" placeholder="e.g. Tibs, Injera..." required <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="food_type">Food Type *</label>
                    <select id="food_type" name="food_type" required <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                        <option value="">Select type...</option>
                        <option value="stew">Stew</option>
                        <option value="flatbread">Flatbread</option>
                        <option value="porridge">Porridge</option>
                        <option value="meat">Meat Dish</option>
                        <option value="salad">Salad</option>
                        <option value="drink">Drink</option>
                        <option value="snack">Snack</option>
                        <option value="side_dish">Side Dish</option>
                        <option value="sauce">Sauce</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="calories_per_100g">Calories per 100g *</label>
                    <input type="number" id="calories_per_100g" name="calories_per_100g" min="1" max="900" placeholder="e.g. 220" required <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                </div>

                <div style="margin-bottom:0.5rem;font-size:0.85rem;font-weight:600;color:var(--text-mid);">Macronutrients (per 100g)</div>
                <div class="macro-grid">
                    <div class="form-group">
                        <label for="protein_g">Protein (g)</label>
                        <input type="number" id="protein_g" name="protein_g" min="0" step="0.01" placeholder="0.00" <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label for="carbs_g">Carbs (g)</label>
                        <input type="number" id="carbs_g" name="carbs_g" min="0" step="0.01" placeholder="0.00" <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label for="fat_g">Fat (g)</label>
                        <input type="number" id="fat_g" name="fat_g" min="0" step="0.01" placeholder="0.00" <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:0.5rem" <?= $rate_limited && !$success ? 'disabled' : '' ?>>
                    <?= $rate_limited && !$success ? ' Wait 1 Hour' : 'Add Food →' ?>
                </button>
            </form>
        </div>

        <!-- RECENTLY ADDED -->
        <div class="card">
            <div class="card-title">Recently Added</div>
            <?php if ($recent->num_rows === 0): ?>
                <div class="empty-state"><div class="icon"></div><p>No foods yet.</p></div>
            <?php else: ?>
                <?php while ($r = $recent->fetch_assoc()): ?>
                <div style="padding:0.65rem 0;border-bottom:1px solid var(--cream-dark);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <div style="font-weight:600;font-size:0.95rem"><?= htmlspecialchars($r['food_name']) ?></div>
                        <span class="badge badge-<?= $r['food_type'] ?>"><?= str_replace('_', ' ', $r['food_type']) ?></span>
                        <?php if ($r['added_by'] == $user_id): ?>
                            <span style="font-size:0.7rem; background:#e8f5e9; padding:2px 6px; border-radius:12px; margin-left:6px;">by you</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.9rem;color:var(--green-dark);font-weight:700"><?= $r['calories_per_100g'] ?> kcal</div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>