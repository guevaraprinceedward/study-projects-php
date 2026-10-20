<?php
/**
 * includes/menu_views.php — HTML for the product cards and the cart / checkout / confirmation / my-reservations panels.
 * Rendered on the server so the same markup works with and without JavaScript.
 */

const PANEL_TITLES = ['cart' => 'Reservation cart', 'checkout' => 'Review & reserve', 'orders' => 'My reservations', 'done' => 'Request sent'];

function panel_wrap(string $view, string $body, string $foot = ''): string
{
    return '<div class="panel" data-view="' . e($view) . '" data-title="' . e(PANEL_TITLES[$view]) . '">'
         . '<div class="drawer-body">' . $body . '</div>'
         . ($foot !== '' ? '<div class="drawer-foot">' . $foot . '</div>' : '')
         . '</div>';
}

function login_prompt(string $why, string $next): string
{
    return '<div class="empty">' . icon('user', 40) . '<h3>Log in to continue</h3><p>' . e($why) . ' Your cart is saved.</p>'
         . '<a class="btn btn-primary" href="log-in.php?next=' . urlencode($next) . '">Log in</a></div>';
}

/* ── Product card (menu page) ─────────────────────────────────────────────── */
function render_product_card(array $p): string
{
    $stock = (int)$p['stock'];
    $out   = $stock <= 0;
    $low   = !$out && $stock <= (int)$p['reorder_level'];
    $max   = max(1, min(COPS_MAX_QTY_PER_ITEM, $stock));
    $stockTxt = $out ? 'Sold out' : ($low ? 'Only ' . $stock . ' left' : $stock . ' available');
    $badge = '<span class="pv-badge' . ($out ? ' out' : '') . '"' . (($out || $low) ? '' : ' hidden') . '>' . ($out ? 'Sold out' : 'Few left') . '</span>';
    $dis = $out ? ' disabled' : '';

    return '<article class="product-card' . ($out ? ' is-soldout' : '') . '" id="item-' . (int)$p['id'] . '" data-id="' . (int)$p['id'] . '" data-stock="' . $stock . '" data-reorder="' . (int)$p['reorder_level'] . '">'
         . product_visual($p, '', $badge)
         . '<div class="pc-body"><div class="pc-cat">' . e(categoryLabel($p['category'])) . '</div>'
         . '<h3 class="pc-name">' . e($p['name']) . '</h3>'
         . '<p class="pc-desc">' . e($p['description'] ?? '') . '</p>'
         . '<div class="pc-meta"><span class="pc-price">' . money($p['price']) . '</span><span class="pc-stock' . ($out ? ' out' : ($low ? ' low' : '')) . '">' . e($stockTxt) . '</span></div>'
         . '<form class="pc-buy" method="post" action="menu.php" data-enhance>' . csrf_field()
         . '<input type="hidden" name="do" value="add"><input type="hidden" name="pid" value="' . (int)$p['id'] . '">'
         . '<div class="qty"><button type="button" data-step="-1" aria-label="Decrease quantity"' . $dis . '>' . icon('minus', 14) . '</button>'
         . '<input type="number" name="qty" value="1" min="1" max="' . $max . '" inputmode="numeric" aria-label="Quantity of ' . e($p['name']) . '"' . $dis . '>'
         . '<button type="button" data-step="1" aria-label="Increase quantity"' . $dis . '>' . icon('plus', 14) . '</button></div>'
         . '<button class="btn btn-primary btn-sm" type="submit"' . $dis . '>' . ($out ? 'Sold out' : 'Add to cart') . '</button>'
         . '</form></div></article>';
}

/* ── Cart ─────────────────────────────────────────────────────────────────── */
function render_cart_panel(mysqli $c): string
{
    $cart = cart_lines($c);
    if (!$cart['lines']) {
        return panel_wrap('cart',
            '<div class="empty">' . icon('cart', 44) . '<h3>Your cart is empty</h3><p>Pick something from the menu and press Add to cart.</p>'
          . '<a class="btn btn-outline" href="menu.php" data-close-panel>Browse the menu</a></div>');
    }
    $max = COPS_MAX_QTY_PER_ITEM;
    $body = '';
    foreach ($cart['lines'] as $l) {
        $cap   = max(0, min($max, $l['stock']));
        $minus = $l['qty'] - 1;
        $body .= '<div class="line">'
            . product_visual(['name' => $l['name'], 'category' => $l['category'], 'image' => $l['image']])
            . '<div><div class="line-name">' . e($l['name']) . '</div><div class="line-price">' . money($l['price']) . ' each</div>'
            . ($l['issue'] ? '<div class="line-issue">' . icon('alert', 14) . ' ' . e($l['issue']) . '</div>' : '') . '</div>'
            . '<div class="line-side"><div class="line-sub">' . money($l['subtotal']) . '</div>'
            . '<form method="post" action="menu.php" data-enhance class="inline-form">' . csrf_field() . '<input type="hidden" name="do" value="set"><input type="hidden" name="pid" value="' . $l['id'] . '">'
            . '<div class="qty"><button type="submit" name="qty" value="' . $minus . '" aria-label="Decrease quantity of ' . e($l['name']) . '">' . icon('minus', 14) . '</button>'
            . '<input type="text" value="' . $l['qty'] . '" readonly aria-label="Quantity" tabindex="-1">'
            . '<button type="submit" name="qty" value="' . ($l['qty'] + 1) . '" aria-label="Increase quantity of ' . e($l['name']) . '"' . ($l['qty'] >= $cap ? ' disabled' : '') . '>' . icon('plus', 14) . '</button></div></form>'
            . '<form method="post" action="menu.php" data-enhance>' . csrf_field() . '<input type="hidden" name="do" value="remove"><input type="hidden" name="pid" value="' . $l['id'] . '">'
            . '<button class="link-btn" type="submit" style="font-size:14px">Remove</button></form>'
            . '</div></div>';
    }
    $foot = '<div class="sum"><span>Total</span><b>' . money($cart['total']) . '</b></div>'
          . ($cart['problem']
              ? '<div class="alert alert-warn" role="alert">' . icon('alert', 18) . '<span>Some items are low or sold out. Update your cart to continue.</span></div>'
              : '<a class="btn btn-primary btn-block" href="menu.php?view=checkout" data-panel-link="checkout">Continue to reservation</a>')
          . '<form method="post" action="menu.php" data-enhance style="text-align:center">' . csrf_field() . '<input type="hidden" name="do" value="clear"><button class="link-btn" type="submit" style="font-size:14.5px">Clear cart</button></form>';
    return panel_wrap('cart', $body, $foot);
}

/* ── Checkout ─────────────────────────────────────────────────────────────── */
function render_checkout_panel(mysqli $c, ?array $user, ?string $error = null): string
{
    $cart = cart_lines($c);
    if (!$cart['lines']) {
        return panel_wrap('checkout', '<div class="empty">' . icon('cart', 44) . '<h3>Nothing to reserve</h3><p>Your cart is empty.</p><a class="btn btn-outline" href="menu.php?view=cart" data-panel-link="cart">Back to cart</a></div>');
    }
    if (!$user) return panel_wrap('checkout', login_prompt('Log in with your student account to send a reservation request.', 'menu.php?view=checkout'));

    $slots = slots_for_day($c, date('Y-m-d'));
    $recap = '<ul class="recap">';
    foreach ($cart['lines'] as $l) $recap .= '<li><span>' . $l['qty'] . ' × ' . e($l['name']) . '</span><span>' . money($l['subtotal']) . '</span></li>';
    $recap .= '<li class="tot"><span>Total</span><span>' . money($cart['total']) . '</span></li></ul>';

    $slotHtml = '';
    foreach ($slots as $s) {
        $left = $s['closed'] ? 'Closed for today' : ($s['remaining'] > 0 ? $s['remaining'] . ' spots left' : 'Full');
        $low  = $s['remaining'] > 0 && $s['remaining'] <= 5 ? ' low' : '';
        $slotHtml .= '<div class="slot-opt"><input type="radio" name="slot" id="slot' . $s['id'] . '" value="' . $s['id'] . '" required' . ($s['available'] ? '' : ' disabled') . '>'
                   . '<label for="slot' . $s['id'] . '"><span>' . e($s['label']) . '<small>' . e($s['time_label']) . '</small></span><span class="left' . $low . '">' . e($left) . '</span></label></div>';
    }
    if (!$slots) $slotHtml = '<div class="alert alert-warn">' . icon('alert', 18) . '<span>No pickup slots are open right now. Please ask the canteen staff.</span></div>';

    $body = ($error ? '<div class="alert alert-error" role="alert">' . icon('alert', 18) . '<span>' . e($error) . '</span></div>' : '')
          . ($cart['problem'] ? '<div class="alert alert-warn" role="alert">' . icon('alert', 18) . '<span>Some items changed. <a href="menu.php?view=cart" data-panel-link="cart">Review your cart</a> before reserving.</span></div>' : '')
          . '<form method="post" action="menu.php" data-enhance id="checkoutForm">' . csrf_field() . '<input type="hidden" name="do" value="checkout">'
          . '<div class="field"><span class="label">Reserving as</span><div style="font-size:17px">' . e($user['name']) . (!empty($user['student_id']) ? ' <span style="color:var(--muted)">· ' . e($user['student_id']) . '</span>' : '') . '</div></div>'
          . '<div class="field"><span class="label" id="slotLabel">Pickup time (today)</span><div class="slot-list" role="radiogroup" aria-labelledby="slotLabel">' . $slotHtml . '</div></div>'
          . '<div class="field"><label for="note">Note for the staff (optional)</label><textarea class="textarea" id="note" name="note" maxlength="160" rows="2" placeholder="e.g. No onions, please"></textarea></div>'
          . '<span class="label">Order summary</span>' . $recap
          . '<div class="notice">' . icon('info', 18) . '<span><b>This sends a request.</b> Your meal is not reserved until staff confirm it. ' . e(COPS_PAYMENT_NOTE) . '</span></div>'
          . '<button class="btn btn-primary btn-block" type="submit"' . (($cart['problem'] || !$slots) ? ' disabled' : '') . '>Submit reservation request</button>'
          . '</form>';
    return panel_wrap('checkout', $body);
}

/* ── Confirmation ─────────────────────────────────────────────────────────── */
function render_done_panel(string $reference): string
{
    $body = '<div class="done"><div class="seal">' . icon('check', 34) . '</div><h3 style="font-size:32px">Request sent</h3>'
          . '<div class="ref">' . e($reference) . '</div>'
          . '<p>Keep this reference. Your status is <span class="pill s-pending">Pending</span></p>'
          . '<p style="margin-top:10px">Staff will review your request. You will see <b>Confirmed</b> once your meal is reserved, and <b>Ready for pickup</b> when it is waiting for you.</p></div>'
          . '<div style="display:grid;gap:10px;margin-top:22px"><a class="btn btn-dark btn-block" href="menu.php?view=orders&amp;ref=' . e($reference) . '" data-panel-link="orders">Track my reservation</a>'
          . '<a class="btn btn-outline btn-block" href="menu.php" data-close-panel>Keep browsing</a></div>';
    return panel_wrap('done', $body);
}

/* ── My reservations ──────────────────────────────────────────────────────── */
function render_orders_panel(mysqli $c, ?array $user, ?string $ref = null): string
{
    if (!$user) return panel_wrap('orders', login_prompt('Log in to see your reservations and their status.', 'menu.php?view=orders'));

    $ref = $ref ? strtoupper(trim($ref)) : null;
    $rows = student_reservations($c, (int)$user['id'], 15, $ref);

    $body = '<form method="get" action="menu.php" class="lookup" data-panel-form><input type="hidden" name="view" value="orders">'
          . '<label class="sr-only" for="refInput">Reservation reference</label>'
          . '<input class="input" id="refInput" name="ref" value="' . e($ref ?? '') . '" placeholder="Reference, e.g. COPS-7K2M9X" maxlength="16" autocomplete="off">'
          . '<button class="btn btn-dark btn-sm" type="submit">Check</button></form>';

    if ($ref && !$rows) {
        $body .= '<div class="alert alert-error" role="alert">' . icon('alert', 18) . '<span>No reservation with that reference was found on your account.</span></div>'
               . '<a class="link-btn" href="menu.php?view=orders" data-panel-link="orders">Show all my reservations</a>';
    } elseif (!$rows) {
        $body .= '<div class="empty">' . icon('receipt', 44) . '<h3>No reservations yet</h3><p>When you send a request it appears here with its live status.</p><a class="btn btn-outline" href="menu.php" data-close-panel>Browse the menu</a></div>';
    }

    $flow = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
    $st = res_statuses();
    foreach ($rows as $r) {
        $s = $r['status'];
        $items = implode(', ', array_map(fn($i) => $i['qty'] . '× ' . e($i['name']), $r['items']));
        $tl = '';
        if (!in_array($s, ['rejected', 'cancelled'], true)) {
            $idx = array_search($s, $flow, true);
            $tl = '<ol class="timeline" aria-label="Progress">';
            foreach ($flow as $i => $f) {
                $cls = $i < $idx ? 'done' : ($i === $idx ? 'now' : '');
                $tl .= '<li class="' . $cls . '"' . ($i === $idx ? ' aria-current="step"' : '') . '>' . e($f === 'ready' ? 'Ready' : res_label($f)) . '</li>';
            }
            $tl .= '</ol>';
        }
        $cancel = '';
        if (in_array($s, ['pending', 'confirmed'], true)) {
            $cancel = '<details class="confirm" data-title="Cancel this reservation?" data-message="Reference ' . e($r['reference']) . ' will be cancelled and the items go back on the menu." data-confirm-label="Yes, cancel it">'
                    . '<summary class="btn btn-danger-outline btn-sm">Cancel reservation</summary>'
                    . '<form method="post" action="menu.php" data-enhance class="confirm-box">' . csrf_field() . '<input type="hidden" name="do" value="cancel"><input type="hidden" name="id" value="' . $r['id'] . '"><input type="hidden" name="reason" value="">'
                    . '<p>Cancel <b>' . e($r['reference']) . '</b>? This cannot be undone.</p><button class="btn btn-danger btn-sm" type="submit">Yes, cancel it</button></form></details>';
        }
        $body .= '<article class="order st-' . e($s) . ($ref && $ref === $r['reference'] ? ' is-highlight' : '') . '" data-id="' . $r['id'] . '" data-status="' . e($s) . '">'
              . '<div class="order-top"><span class="order-ref">' . e($r['reference']) . '</span><span class="pill s-' . e($s) . '">' . e($st[$s]['label']) . '</span></div>'
              . $tl . '<p class="order-say">' . e($st[$s]['student']) . '</p>'
              . '<div class="order-items">' . $items . '</div>'
              . '<div class="order-meta"><span>Pickup <b>' . e($r['slot_label']) . ', ' . e($r['time_label']) . '</b> · ' . e(date('M j', strtotime($r['pickup_date']))) . '</span><span>Total <b>' . money($r['total']) . '</b></span></div>'
              . ($r['note'] ? '<div class="order-meta" style="margin-top:4px"><span>Your note: ' . e($r['note']) . '</span></div>' : '')
              . (in_array($s, ['rejected', 'cancelled'], true) && $r['status_reason'] ? '<div class="order-reason">Reason: ' . e($r['status_reason']) . '</div>' : '')
              . $cancel . '</article>';
    }
    return panel_wrap('orders', $body);
}

/** Map of the student's reservation statuses, used by the page to detect changes. */
function orders_status_map(mysqli $c, ?array $user): array
{
    if (!$user) return [];
    $m = [];
    foreach (db_all($c, "SELECT id, reference, status FROM reservations WHERE user_id = ? ORDER BY id DESC LIMIT 15", 'i', [(int)$user['id']]) as $r) {
        $m[(int)$r['id']] = ['status' => $r['status'], 'ref' => $r['reference']];
    }
    return $m;
}

function stock_map(mysqli $c): array
{
    $m = [];
    foreach (db_all($c, "SELECT id, stock FROM products WHERE is_available = 1") as $r) $m[(int)$r['id']] = (int)$r['stock'];
    return $m;
}