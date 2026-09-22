<?php
require_once __DIR__ . '/../config/app.php';
require_admin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    require_csrf_or_fail();
    $stmt = $db->prepare("UPDATE users SET status = IF(status='active','suspended','active') WHERE user_id = ?");
    $stmt->execute([(int)$_POST['user_id']]);
    header('Location: ' . SITE_URL . '/admin/customers.php');
    exit;
}

$search = clean_input($_GET['q'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id) AS order_count,
        (SELECT COALESCE(SUM(total_amount),0) FROM orders o WHERE o.user_id = u.user_id AND o.order_status != 'cancelled') AS total_spent
        FROM users u";
$params = [];
if ($search) { $sql .= " WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?"; $params = ["%$search%","%$search%","%$search%"]; }
$sql .= " ORDER BY u.created_at DESC LIMIT 100";
$stmt = $db->prepare($sql); $stmt->execute($params);
$customers = $stmt->fetchAll();

$admin_active = 'customers';
$page_title = "Customers — Admin";
require_once __DIR__ . '/../includes/admin-header.php';
?>
<div class="admin-topbar"><h1>Customers</h1></div>

<form method="GET" style="margin-bottom:1.5rem;">
    <input type="text" name="q" placeholder="Search by name or email..." value="<?= e($search) ?>" style="background:rgba(255,255,255,0.03); border:1px solid var(--border-color); color:var(--text-primary); padding:0.8rem 1.2rem; width:320px;">
</form>

<div class="admin-panel">
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
            <td><?= e($c['email']) ?></td>
            <td><?= (int)$c['order_count'] ?></td>
            <td style="color:var(--gold);"><?= CURRENCY_SYMBOL . number_format($c['total_spent'], 2) ?></td>
            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
            <td><?= $c['status'] === 'active' ? '<span style="color:#8FC9A0;">Active</span>' : '<span style="color:#E39A9A;">Suspended</span>' ?></td>
            <td>
                <form method="POST"><?= csrf_field() ?><input type="hidden" name="toggle_status" value="1"><input type="hidden" name="user_id" value="<?= $c['user_id'] ?>">
                    <button type="submit" class="admin-btn admin-btn-sm"><?= $c['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?><tr><td colspan="7" style="text-align:center; color:var(--text-secondary);">No customers found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>