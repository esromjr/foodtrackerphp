<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: landing.php"); exit; }
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
    session_unset(); session_destroy(); header("Location: landing.php?timeout=1"); exit;
}
$_SESSION['last_activity'] = time();

require_once 'config/database.php';
$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');

$success = '';
$error   = '';

// ===== ADD LOG =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $food_id  = (int)$_POST['food_id'];
        $grams    = (int)$_POST['quantity_grams'];
        $meal     = trim($_POST['meal_type']);
        $log_date = $_POST['log_date'] ?: $today;

        if ($food_id > 0 && $grams > 0 && !empty($meal)) {
            $stmt = $conn->prepare("INSERT INTO food_logs (user_id, food_id, quantity_grams, meal_type, log_date) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iiiss", $user_id, $food_id, $grams, $meal, $log_date);
            $stmt->execute() ? $success = 'Meal logged!' : $error = 'Failed to log.';
            $stmt->close();
        } else {
            $error = 'Please fill all fields.';
        }
    }

    // ===== EDIT LOG =====
    if ($_POST['action'] === 'edit') {
        $log_id  = (int)$_POST['log_id'];
        $grams   = (int)$_POST['edit_grams'];
        $meal    = trim($_POST['edit_meal_type']);
        $log_date = $_POST['edit_log_date'] ?: $today;

        $stmt = $conn->prepare("UPDATE food_logs SET quantity_grams=?, meal_type=?, log_date=? WHERE id=? AND user_id=?");
        $stmt->bind_param("issii", $grams, $meal, $log_date, $log_id, $user_id);
        $stmt->execute() ? $success = 'Log updated!' : $error = 'Failed to update.';
        $stmt->close();
    }

    // ===== DELETE LOG =====
    if ($_POST['action'] === 'delete') {
        $log_id = (int)$_POST['log_id'];
        $stmt   = $conn->prepare("DELETE FROM food_logs WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $log_id, $user_id);
        $stmt->execute() ? $success = 'Entry deleted.' : $error = 'Failed to delete.';
        $stmt->close();
    }
}

// ===== FETCH FOODS =====
$foods = $conn->query("SELECT * FROM foods ORDER BY food_name ASC");
$foods_arr = [];
while ($row = $foods->fetch_assoc()) $foods_arr[] = $row;

// ===== FETCH LOG HISTORY =====
$logs_sql = "
    SELECT fl.id, fl.quantity_grams, fl.meal_type, fl.log_date, fl.food_id,
           f.food_name, f.calories_per_100g,
           ROUND(fl.quantity_grams * f.calories_per_100g / 100) AS total_cal
    FROM food_logs fl
    JOIN foods f ON fl.food_id = f.id
    WHERE fl.user_id = ?
    ORDER BY fl.log_date DESC, fl.id DESC
";
$ls = $conn->prepare($logs_sql);
$ls->bind_param("i", $user_id);
$ls->execute();
$logs = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
$ls->close();

include 'header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1> Meal Tracker</h1>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="tracker-grid">

        <!-- LOG FORM -->
        <div class="card">
            <div class="card-title">Log a Meal</div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Food</label>
                    <select name="food_id" required>
                        <option value="">Select food...</option>
                        <?php foreach ($foods_arr as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['food_name']) ?> (<?= $f['calories_per_100g'] ?> cal/100g)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity (grams)</label>
                    <input type="number" name="quantity_grams" min="1" max="3000" placeholder="e.g. 250" required>
                </div>
                <div class="form-group">
                    <label>Meal Type</label>
                    <select name="meal_type" required>
                        <option value="">Select...</option>
                        <option value="breakfast"> Breakfast</option>
                        <option value="lunch"> Lunch</option>
                        <option value="dinner"> Dinner</option>
                        <option value="snack"> Snack</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="log_date" value="<?= $today ?>">
                </div>
                <button type="submit" class="btn btn-primary">Log Meal →</button>
            </form>
        </div>

        <!-- HISTORY -->
        <div>
            <div class="section-title"> Meal History</div>
            <?php if (empty($logs)): ?>
                <div class="empty-state card">
                    <div class="icon"></div>
                    <h3>No meals logged yet</h3>
                    <p>Start logging your meals using the form!</p>
                </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Food</th>
                            <th>Grams</th>
                            <th>Meal</th>
                            <th>Calories</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;font-size:0.85rem"><?= date('d M Y', strtotime($log['log_date'])) ?></td>
                            <td style="font-weight:600"><?= htmlspecialchars($log['food_name']) ?></td>
                            <td><?= $log['quantity_grams'] ?>g</td>
                            <td><span class="badge meal-badge"><?= ucfirst($log['meal_type']) ?></span></td>
                            <td><strong><?= $log['total_cal'] ?></strong> kcal</td>
                            <td style="white-space:nowrap">
                                <button class="btn-icon btn-edit" title="Edit" 
                                    onclick="openEditModal(<?= $log['id'] ?>, <?= $log['food_id'] ?>, '<?= htmlspecialchars(addslashes($log['food_name'])) ?>', <?= $log['quantity_grams'] ?>, '<?= $log['meal_type'] ?>', '<?= $log['log_date'] ?>')">✏️</button>
                                <button class="btn-icon btn-delete" title="Delete"
                                    onclick="confirmDelete(<?= $log['id'] ?>)"></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3> Edit Log Entry</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="log_id" id="edit_log_id">
            <input type="hidden" name="edit_food_id" id="edit_food_id">
            <div class="form-group">
                <label>Food</label>
                <input type="text" id="edit_food_name_display" readonly style="background:var(--muted,#f5f5f5)">
            </div>
            <div class="form-group">
                <label>Quantity (grams)</label>
                <input type="number" name="edit_grams" id="edit_grams" min="1" max="3000" required>
            </div>
            <div class="form-group">
                <label>Meal Type</label>
                <select name="edit_meal_type" id="edit_meal_type" required>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                    <option value="snack">Snack</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="edit_log_date" id="edit_log_date" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM (hidden) -->
<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="log_id" id="delete_log_id">
</form>

<?php include 'footer.php'; ?>