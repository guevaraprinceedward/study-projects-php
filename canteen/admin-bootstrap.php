<?php
/**
 * admin-bootstrap.php — shared start-up for the staff pages
 *   admin-dashboard.php · admin-reservations.php · admin-inventory.php
 *
 * Loads admin-config.php (staff session + database) and the same business rules the student
 * side uses (includes/reservations.php), so a status change made here behaves exactly like
 * the system expects: stock goes back to the menu when a reservation is rejected or cancelled,
 * every change is written to reservation_status_log, and students see it on their next refresh.
 */
require_once __DIR__ . '/admin-config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/reservations.php';

// Same clock as the student pages (config.php sets +08:00 too), so dates and "x min ago" always agree.
try { $adminConn->query("SET time_zone = '+08:00'"); } catch (Throwable $e) { error_log('COPS admin time_zone failed: ' . $e->getMessage()); }

/** The signed-in staff member. Browsers go to the login page; AJAX callers get a JSON 401. */
function admin_require(mysqli $db, bool $json = false): array
{
    $a = getLoggedInAdmin($db);
    if (!$a) {
        if ($json) json_out(['ok' => false, 'login' => true, 'error' => 'Your session has ended. Please log in again.'], 401);
        header('Location: admin-login.php');
        exit;
    }
    if (empty($_SESSION['admin_csrf'])) $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    return $a;
}

function admin_csrf_ok(): bool
{
    return hash_equals((string)($_SESSION['admin_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''));
}

function admin_category_label(string $key): string
{
    static $labels = ['rice_meals' => 'Rice Meals', 'snacks' => 'Snacks', 'drinks' => 'Drinks', 'desserts' => 'Desserts'];
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
}

/** Numbers for the little badges in the navigation: reservations waiting for staff, products low or out of stock. */
function admin_nav_counts(mysqli $db): array
{
    $pending = 0; $low = 0;
    try {
        $r = db_one($db, "SELECT COUNT(*) AS n FROM reservations WHERE status = 'pending'");
        $pending = (int)($r['n'] ?? 0);
        $r = db_one($db, "SELECT COUNT(*) AS n FROM products WHERE is_available = 1 AND stock <= reorder_level");
        $low = (int)($r['n'] ?? 0);
    } catch (Throwable $e) {
        error_log('COPS admin_nav_counts failed: ' . $e->getMessage());
    }
    return ['pending' => $pending, 'low' => $low];
}

/** Prints <html><head> + the staff header, and opens <main>. Pair it with admin_foot(). */
function admin_head(array $admin, string $active, string $title): void
{
    global $adminConn;
    $n = admin_nav_counts($adminConn);
    $links = [
        'dashboard'    => ['admin-dashboard.php',    'DASHBOARD',    0,            '',      ''],
        'reservations' => ['admin-reservations.php', 'RESERVATIONS', $n['pending'], 'nbRes', 'warn'],
        'inventory'    => ['admin-inventory.php',    'INVENTORY',    $n['low'],    'nbInv', 'bad'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — COPS Staff</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="cops-theme.css">
<link rel="stylesheet" href="cops-admin.css">
</head>
<body class="admin">
<header class="site-header">
    <div class="header-inner" style="max-width:1280px">
        <a href="admin-dashboard.php" class="brand">
            <span class="brand-mark"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#241a00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10M19 21V10M9 21V10M15 21V10M2 10l10-7 10 7z"/></svg></span>
            <span class="brand-name">COPS<span class="brand-sub">Staff console</span></span>
        </a>
        <nav class="main-nav" id="adminNav" aria-label="Staff">
            <?php foreach ($links as $key => [$href, $label, $count, $id, $tone]): ?>
                <a href="<?= $href ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= $label ?><?php if ($id !== ''): ?><span class="nb <?= $tone ?>" id="<?= $id ?>"<?= $count > 0 ? '' : ' hidden' ?>><?= (int)$count ?></span><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="who">
            <span class="live" id="live"><i></i><span>LIVE</span></span>
            <div class="who-name"><b><?= e($admin['full_name']) ?></b><span><?= e(ucfirst($admin['role'])) ?> &middot; <?= e($admin['staff_id']) ?></span></div>
            <a href="admin-logout.php" class="logout">LOG OUT</a>
            <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        </div>
    </div>
</header>
<main class="wrap dash">
<?php
}

/** Closes <main>, loads the shared script, then runs the page's own script. */
function admin_foot(string $inlineJs = ''): void
{
    ?>
</main>
<script src="cops-ui.js"></script>
<script>
<?= $inlineJs ?>
</script>
</body>
</html>
<?php
}