<?php
/**
 * menu.php — student menu + reservation.
 *   GET  menu.php                      → the menu page (filters: ?q= &cat= &sort=)
 *   GET  menu.php?view=cart|checkout|orders|done   → page with the panel open (JS: JSON when X-Requested-With: fetch)
 *   GET  menu.php?action=state         → JSON: stock + cart count + my reservation statuses
 *   POST menu.php  do=add|set|remove|clear|checkout|cancel
 *
 * Product photos: every card goes through card_html(), which takes whatever render_product_card()
 * returns and makes sure the photo from products.image is in it (see includes/product-image.php).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/school-art.php';
require_once __DIR__ . '/includes/product-image.php';

const VIEWS = ['cart', 'checkout', 'orders', 'done'];
$user = getLoggedInUser($conn);

/** Finds an asset in the site root or assets/css|js (whichever exists) and returns its URL with a cache-buster. */
function find_asset(string $file): ?string
{
    $dirs = ['', str_ends_with($file, '.css') ? 'assets/css/' : 'assets/js/'];
    foreach ($dirs as $d) {
        $path = __DIR__ . '/' . $d . $file;
        if (is_file($path)) return $d . $file . '?v=' . filemtime($path);
    }
    return null;
}

/**
 * One product card with its photo guaranteed.
 * - drops any <img> render_product_card() produced (wrong path / lazy placeholder / hidden)
 * - puts the correct <img> inside the card's .pv photo box, or creates that box if the card has none
 */
function card_html(array $p): string
{
    $html = render_product_card($p);
    $src  = product_image_src($p['image'] ?? '');
    $none = $src === PRODUCT_PLACEHOLDER;
    $img  = '<img class="cops-photo cops-photo-in" src="' . e($src) . '" alt="' . e((string)$p['name']) . '"'
          . ' loading="eager" decoding="async" referrerpolicy="no-referrer" data-fallback="' . e(PRODUCT_PLACEHOLDER) . '">';

    $html = preg_replace('~<img\b[^>]*>~i', '', $html);

    // card already has a .pv photo box (match "pv" exactly, not "pv-badge")
    if (preg_match('~<(?:div|figure|a|span)\b[^>]*class="[^"]*(?<![\w-])pv(?![\w-])[^"]*"[^>]*>~i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        return substr_replace($html, $img, $pos, 0);
    }
    // otherwise create the photo box right after the card's opening tag
    if (preg_match('~^\s*<[a-z][^>]*>~i', $html, $m)) {
        $box = '<div class="pv ' . ($none ? 'no-photo' : 'has-photo') . '">' . $img . '</div>';
        return substr_replace($html, $box, strlen($m[0]), 0);
    }
    return $html;
}

function panel_html(mysqli $conn, ?array $user, string $view, ?string $ref = null): string
{
    switch ($view) {
        case 'checkout': return render_checkout_panel($conn, $user);
        case 'orders':   return render_orders_panel($conn, $user, $ref);
        case 'done':
            $r = (string)($_SESSION['last_ref'] ?? '');
            return $r !== '' ? render_done_panel($r) : render_orders_panel($conn, $user, null);
        default:         return render_cart_panel($conn);
    }
}

function inject_alert(string $html, string $type, string $msg): string
{
    $needle = '<div class="drawer-body">';
    $pos = strpos($html, $needle);
    if ($pos === false) return $html;
    $ok = $type === 'ok';
    $alert = '<div class="alert alert-' . ($ok ? 'ok' : 'error') . '" role="alert">' . icon($ok ? 'check' : 'alert', 18) . '<span>' . e($msg) . '</span></div>';
    return substr_replace($html, $alert, $pos + strlen($needle), 0);
}

/* ── POST actions ─────────────────────────────────────────────────────────── */
if (is_post()) {
    $do  = (string)($_POST['do'] ?? '');
    $pid = (int)($_POST['pid'] ?? 0);
    $ok = false; $msg = null; $view = null; $needLogin = null;

    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new UserFacingError('Your session expired. Please refresh the page and try again.');
        switch ($do) {
            case 'add':
                $msg = cart_add($conn, $pid, (int)($_POST['qty'] ?? 1));
                break;
            case 'set':
                $view = 'cart';
                $msg = cart_set($conn, $pid, (int)($_POST['qty'] ?? 0));
                break;
            case 'remove':
                $view = 'cart';
                $msg = cart_remove($pid);
                break;
            case 'clear':
                $view = 'cart';
                cart_clear();
                $msg = 'Cart cleared.';
                break;
            case 'checkout':
                $view = 'checkout';
                if (!$user) { $needLogin = 'menu.php?view=checkout'; throw new UserFacingError('Please log in to send your reservation.'); }
                $res = create_reservation($conn, $user, (int)($_POST['slot'] ?? 0), isset($_POST['note']) ? (string)$_POST['note'] : null);
                $_SESSION['last_ref'] = $res['reference'];
                $view = 'done';
                $msg = 'Reservation request sent.';
                break;
            case 'cancel':
                $view = 'orders';
                if (!$user) { $needLogin = 'menu.php?view=orders'; throw new UserFacingError('Please log in first.'); }
                $r = res_transition($conn, (int)($_POST['id'] ?? 0), 'cancelled',
                        ['type' => 'student', 'id' => (int)$user['id'], 'name' => $user['name']],
                        null, isset($_POST['reason']) ? (string)$_POST['reason'] : null, (int)$user['id']);
                if (!$r['ok']) throw new UserFacingError($r['error']);
                $msg = 'Reservation ' . $r['reference'] . ' cancelled.';
                break;
            default:
                throw new UserFacingError('Unknown action.');
        }
        $ok = true;
    } catch (UserFacingError $e) {
        $msg = $e->getMessage();
    } catch (Throwable $e) {
        app_log('menu.php [' . $do . '] ' . $e->getMessage());
        $msg = 'Something went wrong. Please try again.';
    }

    if (is_ajax()) {
        $out = ['ok' => $ok, 'message' => $msg, 'stock' => stock_map($conn), 'count' => cart_lines($conn)['count'], 'orders' => orders_status_map($conn, $user)];
        if ($needLogin) { $out['login'] = true; $out['next'] = $needLogin; }
        elseif ($view) {
            $html = panel_html($conn, $user, $view);
            if (!$ok && $msg) $html = inject_alert($html, 'error', $msg);
            $out['view'] = $view; $out['html'] = $html;
        }
        json_out($out);
    }

    // No JavaScript: remember the message, then redirect (Post/Redirect/Get)
    if ($needLogin) redirect('log-in.php?next=' . urlencode($needLogin));
    if ($msg) flash_set($ok ? 'ok' : 'error', $msg);
    redirect($view ? 'menu.php?view=' . $view : 'menu.php' . ($pid ? '#item-' . $pid : ''));
}

/* ── GET: live state for the page ─────────────────────────────────────────── */
if (($_GET['action'] ?? '') === 'state') {
    json_out(['ok' => true, 'loggedIn' => (bool)$user, 'stock' => stock_map($conn), 'count' => cart_lines($conn)['count'], 'orders' => orders_status_map($conn, $user)]);
}

$view = in_array($_GET['view'] ?? '', VIEWS, true) ? (string)$_GET['view'] : '';
$ref  = isset($_GET['ref']) ? substr((string)$_GET['ref'], 0, 16) : null;

if ($view !== '' && is_ajax()) {
    json_out(['ok' => true, 'view' => $view, 'html' => panel_html($conn, $user, $view, $ref), 'count' => cart_lines($conn)['count']]);
}

/* ── Page data ────────────────────────────────────────────────────────────── */
$all = db_all($conn, "SELECT id, name, category, description, price, stock, reorder_level, image
                      FROM products WHERE is_available = 1
                      ORDER BY FIELD(category,'rice_meals','snacks','drinks','desserts') = 0, FIELD(category,'rice_meals','snacks','drinks','desserts'), name ASC");
$catCounts = [];
foreach ($all as $p) $catCounts[$p['category']] = ($catCounts[$p['category']] ?? 0) + 1;

$q    = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
$cat  = (string)($_GET['cat'] ?? 'all');
if ($cat !== 'all' && !isset($catCounts[$cat])) $cat = 'all';
$sort = (string)($_GET['sort'] ?? 'menu');
if (!in_array($sort, ['menu', 'low', 'high', 'az'], true)) $sort = 'menu';

$items = array_values(array_filter($all, function ($p) use ($q, $cat) {
    if ($cat !== 'all' && $p['category'] !== $cat) return false;
    if ($q !== '' && stripos($p['name'], $q) === false && stripos((string)$p['description'], $q) === false) return false;
    return true;
}));
if ($sort === 'low')       usort($items, fn($a, $b) => (float)$a['price'] <=> (float)$b['price']);
elseif ($sort === 'high')  usort($items, fn($a, $b) => (float)$b['price'] <=> (float)$a['price']);
elseif ($sort === 'az')    usort($items, fn($a, $b) => strcasecmp($a['name'], $b['name']));

$state = ['q' => $q, 'cat' => $cat, 'sort' => $sort];
$url = function (array $o = []) use ($state): string {
    $s = array_merge($state, $o); $qs = [];
    if ($s['q'] !== '')        $qs['q'] = $s['q'];
    if ($s['cat'] !== 'all')   $qs['cat'] = $s['cat'];
    if ($s['sort'] !== 'menu') $qs['sort'] = $s['sort'];
    return 'menu.php' . ($qs ? '?' . http_build_query($qs) : '');
};

// Flash + (no-JS) open panel
$flash = flash_get();
$drawerOpen = $view !== '';
$panel = $drawerOpen ? panel_html($conn, $user, $view, $ref) : '';
if ($drawerOpen && $flash && $flash['type'] === 'error') $panel = inject_alert($panel, 'error', $flash['message']);
if ($drawerOpen) $flash = null;

$slots    = slots_for_day($conn, date('Y-m-d'));
$cartInfo = cart_lines($conn);

// scripts: the original menu.js first, then the motion + photo helpers (only those that exist)
$scripts = ['assets/js/menu.js'];
foreach (['cops-menu.js', 'cops-photos.js', 'cops-pager.js'] as $f) { if ($a = find_asset($f)) $scripts[] = $a; }

$page = ['title' => 'Menu & reservations', 'active' => 'menu', 'cart' => true, 'cart_count' => $cartInfo['count'], 'scripts' => $scripts];
include __DIR__ . '/includes/header.php';

// extra stylesheets, loaded after the header's own (so they refine it): school scene → menu polish → extra elegance
foreach (['cops-school.css', 'cops-menu.css', 'cops-menu-plus.css', 'cops-pager.css'] as $f) {
    if ($a = find_asset($f)) echo '<link rel="stylesheet" href="' . e($a) . '">' . "\n";
}


?>

<section class="menu-hero is-school" style="padding: 54px 0 25px !important;">
    <div class="container">
        <div class="hero-copy">
            <div class="hero-logo">
                <img src="<?= SCHOOL_LOGO ?>" alt="CMDI logo">
                <span>CARD-MRI<br>DEVELOPMENT INSTITUTE</span>
            </div>
            <h1>Menu &amp; reservations</h1>
            <p>Pick your food, choose a pickup time, and send the request. Staff will confirm it and mark it ready.</p>
            <div class="meander" aria-hidden="true"></div>
        </div>
        <div class="hero-scene">
            <div class="scene-frame">
                <i class="scene-corner tl"></i><i class="scene-corner tr"></i><i class="scene-corner bl"></i><i class="scene-corner br"></i>
                <div class="scene-frame-in"><?= school_scene('wide', 'mh') ?></div>
                <div class="scene-seal"><img src="<?= SCHOOL_LOGO ?>" alt=""></div>
                <div class="scene-caption">THE CMDI CANTEEN</div>
            </div>
        </div>
    </div>
</section>

<?php if ($slots): ?>
<div class="strip" aria-label="Today's pickup slots">
    <div class="strip-inner">
        <div class="strip-title"><?= icon('clock', 16) ?> PICKUP SLOTS TODAY</div>
        <div class="slot-chips">
            <?php foreach ($slots as $s): ?>
                <div class="slot-chip<?= $s['available'] ? '' : ' full' ?>">
                    <b><?= e($s['label']) ?></b><span><?= e($s['time_label']) ?></span>
                    <span class="left"><?= $s['closed'] ? 'Closed' : ($s['remaining'] > 0 ? $s['remaining'] . ' left' : 'Full') ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="toolbar">
    <div class="container toolbar-in">
        <form class="search search-field" method="get" action="menu.php" role="search">
            <?= icon('search', 18) ?>
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search the menu" aria-label="Search the menu">
            <?php if ($cat !== 'all'): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
            <?php if ($sort !== 'menu'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
        </form>

        <div class="chips" role="group" aria-label="Categories">
            <a class="chip" href="<?= e($url(['cat' => 'all'])) ?>"<?= $cat === 'all' ? ' aria-current="true"' : '' ?>>All <small><?= count($all) ?></small></a>
            <?php foreach ($catCounts as $key => $n): ?>
                <a class="chip" href="<?= e($url(['cat' => $key])) ?>"<?= $cat === $key ? ' aria-current="true"' : '' ?>><?= e(categoryLabel($key)) ?> <small><?= $n ?></small></a>
            <?php endforeach; ?>
        </div>

        <form class="sort" method="get" action="menu.php">
            <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
            <?php if ($cat !== 'all'): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
            <label class="sr-only" for="sort">Sort</label>
            <select class="select" id="sort" name="sort" onchange="this.form.submit()">
                <option value="menu"<?= $sort === 'menu' ? ' selected' : '' ?>>Menu order</option>
                <option value="low"<?= $sort === 'low' ? ' selected' : '' ?>>Price: low to high</option>
                <option value="high"<?= $sort === 'high' ? ' selected' : '' ?>>Price: high to low</option>
                <option value="az"<?= $sort === 'az' ? ' selected' : '' ?>>Name: A to Z</option>
            </select>
            <noscript><button class="btn btn-outline btn-sm" type="submit">Sort</button></noscript>
        </form>
    </div>
</div>

<div class="menu-wrap" data-max-qty="<?= COPS_MAX_QTY_PER_ITEM ?>">
    <div class="container">
        <?php if ($flash): ?>
            <div class="flash-bar"><div class="alert alert-<?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>" role="status"><?= icon($flash['type'] === 'ok' ? 'check' : 'alert', 18) ?><span><?= e($flash['message']) ?></span></div></div>
        <?php endif; ?>

        <?php if (!$items): ?>
            <div class="no-results">
                <h3><?= $all ? 'Nothing matches that' : 'The menu is being prepared' ?></h3>
                <p><?= $all ? 'Try another word or category.' : 'No items yet. Check back soon.' ?></p>
                <?php if ($all): ?><p style="margin-top:14px"><a class="btn btn-outline btn-sm" href="menu.php">Clear filters</a></p><?php endif; ?>
            </div>
        <?php elseif ($sort === 'menu'): ?>
            <?php
            $groups = [];
            foreach ($items as $p) $groups[$p['category']][] = $p;
            foreach ($groups as $key => $list): ?>
                <section class="cat-section">
                    <h2><?= e(categoryLabel($key)) ?> <small><?= count($list) ?> item<?= count($list) === 1 ? '' : 's' ?></small></h2>
                    <div class="grid-products"><?php foreach ($list as $p) echo card_html($p); ?></div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="grid-products"><?php foreach ($items as $p) echo card_html($p); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Reservation panel (cart · checkout · my reservations) -->
<div class="veil<?= $drawerOpen ? ' is-open' : '' ?>" id="veil"></div>
<aside class="drawer<?= $drawerOpen ? ' is-open static' : '' ?>" id="drawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle" aria-hidden="<?= $drawerOpen ? 'false' : 'true' ?>">
    <div class="drawer-head">
        <h2 id="drawerTitle"><?= $drawerOpen ? e(PANEL_TITLES[$view]) : 'Reservation cart' ?></h2>
        <a class="icon-btn" href="<?= e($url()) ?>" id="drawerClose" aria-label="Close panel"><?= icon('close', 20) ?></a>
    </div>
    <nav class="drawer-tabs" aria-label="Panel">
        <a href="menu.php?view=cart" data-panel-link="cart" aria-current="<?= in_array($view, ['cart', 'checkout'], true) || !$drawerOpen ? 'true' : 'false' ?>">CART</a>
        <a href="menu.php?view=orders" data-panel-link="orders" aria-current="<?= in_array($view, ['orders', 'done'], true) ? 'true' : 'false' ?>">MY RESERVATIONS</a>
    </nav>
    <div id="drawerPanel"><?= $panel ?></div>
</aside>

<?php include __DIR__ . '/includes/footer.php'; ?>