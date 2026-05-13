<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: landing.php");
    exit;
}

// Session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_unset(); session_destroy();
    header("Location: landing.php?timeout=1"); exit;
}
$_SESSION['last_activity'] = time();

require_once 'config/database.php';
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$quick_success = '';
$quick_error   = '';

// ===== QUICK LOG SUBMIT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_log'])) {
    $food_id  = (int)($_POST['food_id'] ?? 0);
    $grams    = (int)($_POST['quantity_grams'] ?? 0);
    $meal     = trim($_POST['meal_type'] ?? '');
    $log_date = $_POST['log_date'] ?? $today;

    if ($food_id > 0 && $grams > 0 && !empty($meal)) {
        $stmt = $conn->prepare("INSERT INTO food_logs (user_id, food_id, quantity_grams, meal_type, log_date) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iiiss", $user_id, $food_id, $grams, $meal, $log_date);
        if ($stmt->execute()) {
            $quick_success = 'Food logged successfully!';
        } else {
            $quick_error = 'Failed to log food.';
        }
        $stmt->close();
    } else {
        $quick_error = 'Please fill all fields correctly.';
    }
}

// ===== TODAY STATS =====
$stats_sql = "
    SELECT 
        SUM(fl.quantity_grams * f.calories_per_100g / 100) AS total_calories,
        SUM(fl.quantity_grams * f.protein_g / 100) AS total_protein,
        SUM(fl.quantity_grams * f.carbs_g / 100) AS total_carbs,
        SUM(fl.quantity_grams * f.fat_g / 100) AS total_fat,
        COUNT(fl.id) AS entries
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.id
    WHERE fl.user_id = ? AND fl.log_date = ?
";
$s = $conn->prepare($stats_sql);
$s->bind_param("is", $user_id, $today);
$s->execute();
$stats = $s->get_result()->fetch_assoc();
$s->close();

$total_cal  = round($stats['total_calories'] ?? 0);
$total_prot = round($stats['total_protein'] ?? 0, 1);
$total_carb = round($stats['total_carbs'] ?? 0, 1);
$total_fat  = round($stats['total_fat'] ?? 0, 1);
$entries    = $stats['entries'] ?? 0;

$daily_goal  = 2000;
$cal_percent = $daily_goal > 0 ? ($total_cal / $daily_goal) * 100 : 0;

// ===== ALL FOODS =====
$foods = $conn->query("SELECT * FROM foods ORDER BY food_name ASC");
$foods_arr = [];
while ($row = $foods->fetch_assoc()) $foods_arr[] = $row;

include 'header.php';
?>

<div class="main-content">

    <div class="page-header">
        <h1> Dashboard</h1>
        <span style="color:var(--text-light);font-size:0.9rem;">
             Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong> &nbsp;|&nbsp; <?= date('D, d M Y') ?>
        </span>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label"> Calories Today</div>
            <div class="stat-value"><?= number_format($total_cal) ?></div>
            <div class="stat-unit">/ <?= number_format($daily_goal) ?> goal</div>
            <div class="calorie-bar-wrap" style="margin-top:0.6rem;">
                <div class="calorie-bar-bg">
                    <div class="calorie-bar-fill" data-percent="<?= $cal_percent ?>" style="width:0%"></div>
                </div>
            </div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-label"> Protein</div>
            <div class="stat-value"><?= $total_prot ?></div>
            <div class="stat-unit">grams</div>
        </div>
        <div class="stat-card terracotta">
            <div class="stat-label"> Carbs</div>
            <div class="stat-value"><?= $total_carb ?></div>
            <div class="stat-unit">grams</div>
        </div>
        <div class="stat-card brown">
            <div class="stat-label"> Fat</div>
            <div class="stat-value"><?= $total_fat ?></div>
            <div class="stat-unit">grams</div>
        </div>
    </div>

    <!-- QUICK LOG -->
    <div class="quick-log-panel">
        <h3> Quick Log Food</h3>
        <?php if ($quick_success): ?><div class="alert alert-success"><?= $quick_success ?></div><?php endif; ?>
        <?php if ($quick_error): ?><div class="alert alert-error"><?= $quick_error ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="quick_log" value="1">
            <div class="quick-log-grid">
                <div class="form-group" style="margin-bottom:0">
                    <label>Food</label>
                    <select name="food_id" required>
                        <option value="">Select food...</option>
                        <?php foreach ($foods_arr as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['food_name']) ?> (<?= $f['calories_per_100g'] ?> cal/100g)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Grams</label>
                    <input type="number" name="quantity_grams" min="1" max="2000" placeholder="e.g. 200" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Meal Type</label>
                    <select name="meal_type" required>
                        <option value="">Select...</option>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                        <option value="snack">Snack</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap">Log It </button>
                </div>
            </div>
        </form>
    </div>

    <!-- FOOD LIST -->
    <div class="page-header" style="margin-bottom:1rem;">
        <div class="section-title"> Ethiopian Foods (<?= count($foods_arr) ?>)</div>
        <div class="search-bar" style="margin-bottom:0">
            <input type="text" id="foodSearch" placeholder=" Search foods...">
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Food Name</th>
                    <th>Type</th>
                    <th>Cal/100g</th>
                    <th>Protein</th>
                    <th>Carbs</th>
                    <th>Fat</th>
                </tr>
            </thead>
            <tbody id="foodTableBody">
                <?php foreach ($foods_arr as $f): ?>
                <tr>
                    <td class="food-name" style="font-weight:600"><?= htmlspecialchars($f['food_name']) ?></td>
                    <td><span class="badge badge-<?= $f['food_type'] ?>"><?= str_replace('_', ' ', $f['food_type']) ?></span></td>
                    <td><strong><?= $f['calories_per_100g'] ?></strong> kcal</td>
                    <td><?= $f['protein_g'] ?>g</td>
                    <td><?= $f['carbs_g'] ?>g</td>
                    <td><?= $f['fat_g'] ?>g</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="noResults" style="display:none;" class="empty-state">
            <div class="icon"></div>
            <h3>No foods found</h3>
            <p>Try a different search term</p>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>