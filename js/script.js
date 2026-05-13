// ===== FOOD SEARCH FILTER =====
function initFoodSearch() {
    const searchInput = document.getElementById('foodSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#foodTableBody tr');
        let visible = 0;

        rows.forEach(row => {
            const name = row.querySelector('.food-name');
            if (!name) return;
            const match = name.textContent.toLowerCase().includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        const emptyMsg = document.getElementById('noResults');
        if (emptyMsg) emptyMsg.style.display = visible === 0 ? '' : 'none';
    });
}

// ===== EDIT MODAL =====
function openEditModal(id, foodId, foodName, grams, mealType, logDate) {
    document.getElementById('edit_log_id').value = id;
    document.getElementById('edit_food_id').value = foodId;
    document.getElementById('edit_food_name_display').textContent = foodName;
    document.getElementById('edit_grams').value = grams;
    document.getElementById('edit_meal_type').value = mealType;
    document.getElementById('edit_log_date').value = logDate;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

// ===== DELETE CONFIRM =====
function confirmDelete(logId) {
    if (confirm('Are you sure you want to delete this log entry?')) {
        document.getElementById('delete_log_id').value = logId;
        document.getElementById('deleteForm').submit();
    }
}

// ===== DELETE ACCOUNT CONFIRM =====
function confirmDeleteAccount() {
    if (confirm('⚠️ This will permanently delete your account and all your data. This cannot be undone.\n\nAre you sure?')) {
        document.getElementById('deleteAccountForm').submit();
    }
}

// ===== CALORIE BAR ANIMATION =====
function initCalorieBar() {
    const bar = document.querySelector('.calorie-bar-fill');
    if (!bar) return;
    const target = parseFloat(bar.dataset.percent) || 0;
    const capped = Math.min(target, 100);
    setTimeout(() => {
        bar.style.width = capped + '%';
        if (target > 100) bar.classList.add('over');
    }, 150);
}

// ===== CLOSE MODAL ON OVERLAY CLICK =====
function initModalClose() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
}

// ===== QUICK LOG: auto-hide success alert =====
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-success, .alert-error');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
}

// ===== INIT =====
document.addEventListener('DOMContentLoaded', function () {
    initFoodSearch();
    initCalorieBar();
    initModalClose();
    initAutoHideAlerts();
});