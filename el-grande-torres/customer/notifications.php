<?php
require_once __DIR__ . '/../config/app.php';
require_login();

$user_id = current_user_id();
$db = getDB();

$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$page_title = "Notifications — El Grande De La Torres";
$extra_css = SITE_URL . '/assets/css/account.css';
require_once __DIR__ . '/../includes/header.php';
$active_page = 'notifications';
require_once __DIR__ . '/../includes/customer-header.php';
?>

<div class="section-head" style="justify-content:flex-start; text-align:left; margin-bottom:1.5rem;">
    <h2 class="section-title" style="font-size:1.5rem;">Notifications</h2>
</div>

<?php if (empty($notifications)): ?>
    <div class="empty-state" style="text-align:left; padding:2rem 0;"><p>No notifications yet.</p></div>
<?php else: ?>
    <?php foreach ($notifications as $notif): ?>
    <div class="order-row" style="grid-template-columns:1fr auto;">
        <div>
            <p style="font-size:0.9rem;"><?= e($notif['title']) ?></p>
            <p style="font-size:0.8rem; color:var(--text-secondary); margin-top:0.3rem;"><?= e($notif['message']) ?></p>
            <p style="font-size:0.72rem; color:var(--text-secondary); margin-top:0.5rem;"><?= date('M j, Y g:i A', strtotime($notif['created_at'])) ?></p>
        </div>
        <span class="badge-lux"><?= ucfirst(e($notif['type'])) ?></span>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/customer-footer.php';
require_once __DIR__ . '/../includes/footer.php';
?>