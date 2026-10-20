<?php
/**
 * includes/reservations.php — business rules for the cart, checkout and reservation workflow.
 * Everything that matters (prices, stock, capacity, status changes) is decided HERE, on the server.
 */
require_once __DIR__ . '/db.php';

const RES_OPEN = ['pending', 'confirmed', 'preparing', 'ready'];

/* ══ Status workflow ════════════════════════════════════════════════════════ */

function res_statuses(): array
{
    return [
        'pending'   => ['label' => 'Pending',          'student' => 'Your request was sent. It is not reserved yet — staff still need to confirm it.'],
        'confirmed' => ['label' => 'Confirmed',        'student' => 'Staff accepted your reservation. Your meal is reserved for your pickup slot.'],
        'preparing' => ['label' => 'Preparing',        'student' => 'Your order is being prepared.'],
        'ready'     => ['label' => 'Ready for pickup', 'student' => 'Your order is ready. Show your reference at the counter.'],
        'completed' => ['label' => 'Completed',        'student' => 'Picked up. Enjoy your meal!'],
        'rejected'  => ['label' => 'Rejected',         'student' => 'Staff could not accept this request.'],
        'cancelled' => ['label' => 'Cancelled',        'student' => 'This reservation was cancelled.'],
    ];
}

/** The ONLY allowed moves. Anything else is refused by the server. */
function res_transitions(): array
{
    return [
        'pending'   => ['confirmed', 'rejected', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready'     => ['completed', 'cancelled'],
        'completed' => [],
        'rejected'  => [],
        'cancelled' => [],
    ];
}

function res_label(string $status): string
{
    return res_statuses()[$status]['label'] ?? ucfirst($status);
}

/** Moves a reservation to a new status, atomically. Used by BOTH staff and students. */
function res_transition(mysqli $c, int $id, string $to, array $actor, ?string $expected = null, ?string $reason = null, ?int $ownerId = null): array
{
    $reason = $reason === null ? null : trim(mb_substr($reason, 0, 160));
    if ($reason === '') $reason = null;

    $c->begin_transaction();
    try {
        $row = db_one($c, "SELECT id, reference, status, user_id FROM reservations WHERE id = ? FOR UPDATE", 'i', [$id]);
        if (!$row || ($ownerId !== null && (int)$row['user_id'] !== $ownerId)) throw new UserFacingError('Reservation not found.');

        $from = $row['status'];
        if ($expected !== null && $expected !== $from) {
            throw new UserFacingError('Already updated: this reservation is now ' . res_label($from) . '. The list has been refreshed.');
        }
        if (!isset(res_statuses()[$to])) throw new UserFacingError('Unknown status.');
        if ($from === $to) throw new UserFacingError('This reservation is already ' . res_label($from) . '.');
        if (!in_array($to, res_transitions()[$from] ?? [], true)) {
            throw new UserFacingError(res_label($from) . ' reservations cannot be changed to ' . res_label($to) . '.');
        }
        if ($actor['type'] === 'student' && ($to !== 'cancelled' || !in_array($from, ['pending', 'confirmed'], true))) {
            throw new UserFacingError('You can only cancel a reservation before staff start preparing it. Please talk to the canteen staff.');
        }
        if ($actor['type'] === 'staff' && empty($actor['id'])) throw new UserFacingError('Not authorized.');

        // Rejected / cancelled orders give their stock back to the menu
        if (in_array($to, ['rejected', 'cancelled'], true)) {
            $items = db_all($c, "SELECT product_id, quantity FROM reservation_items WHERE reservation_id = ? AND product_id IS NOT NULL ORDER BY product_id", 'i', [$id]);
            foreach ($items as $it) {
                db_exec($c, "UPDATE products SET stock = stock + ? WHERE id = ?", 'ii', [(int)$it['quantity'], (int)$it['product_id']]);
            }
        }

        $keepReason = in_array($to, ['rejected', 'cancelled'], true) ? $reason : null;
        if ($actor['type'] === 'staff') {
            db_exec($c, "UPDATE reservations SET status = ?, status_reason = ?, handled_by_id = ?, handled_by_name = ? WHERE id = ?",
                    'ssisi', [$to, $keepReason, (int)$actor['id'], (string)$actor['name'], $id]);
        } else {
            db_exec($c, "UPDATE reservations SET status = ?, status_reason = ? WHERE id = ?", 'ssi', [$to, $keepReason ?? 'Cancelled by the student', $id]);
        }
        db_exec($c, "INSERT INTO reservation_status_log (reservation_id, from_status, to_status, actor_type, actor_id, actor_name, reason) VALUES (?,?,?,?,?,?,?)",
                'issssss', [$id, $from, $to, $actor['type'], isset($actor['id']) ? (string)$actor['id'] : null, (string)$actor['name'], $keepReason]);
        $c->commit();
        return ['ok' => true, 'reference' => $row['reference'], 'from' => $from, 'to' => $to];
    } catch (UserFacingError $e) {
        $c->rollback();
        return ['ok' => false, 'error' => $e->getMessage()];
    } catch (Throwable $e) {
        $c->rollback();
        app_log('res_transition failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Something went wrong. Please try again.'];
    }
}

/* ══ Cart (kept in the session so it also works without JavaScript) ═══════ */

function cart_raw(): array
{
    $out = [];
    foreach (($_SESSION['cart'] ?? []) as $pid => $qty) {
        $pid = (int)$pid; $qty = (int)$qty;
        if ($pid > 0 && $qty > 0) $out[$pid] = min($qty, COPS_MAX_QTY_PER_ITEM);
    }
    return $out;
}

function cart_find_product(mysqli $c, int $pid): array
{
    $p = db_one($c, "SELECT id, name, category, price, stock, image, is_available FROM products WHERE id = ?", 'i', [$pid]);
    if (!$p || !(int)$p['is_available']) throw new UserFacingError('That item is not on the menu right now.');
    return $p;
}

function cart_add(mysqli $c, int $pid, int $qty): string
{
    $qty = max(1, min($qty, COPS_MAX_QTY_PER_ITEM));
    $p   = cart_find_product($c, $pid);
    if ((int)$p['stock'] <= 0) throw new UserFacingError($p['name'] . ' is sold out.');
    $cart = cart_raw();
    if (!isset($cart[$pid]) && count($cart) >= COPS_MAX_LINES) throw new UserFacingError('You can reserve at most ' . COPS_MAX_LINES . ' different items at a time.');
    $new = ($cart[$pid] ?? 0) + $qty;
    if ($new > COPS_MAX_QTY_PER_ITEM) throw new UserFacingError('You can reserve up to ' . COPS_MAX_QTY_PER_ITEM . ' of one item.');
    if ($new > (int)$p['stock']) throw new UserFacingError('Only ' . (int)$p['stock'] . ' of ' . $p['name'] . ' left.');
    $cart[$pid] = $new;
    $_SESSION['cart'] = $cart;
    return $qty . ' × ' . $p['name'] . ' added to your cart.';
}

function cart_set(mysqli $c, int $pid, int $qty): string
{
    $cart = cart_raw();
    if (!isset($cart[$pid])) throw new UserFacingError('That item is not in your cart.');
    if ($qty <= 0) { unset($cart[$pid]); $_SESSION['cart'] = $cart; return 'Item removed.'; }
    $p = cart_find_product($c, $pid);
    if ($qty > COPS_MAX_QTY_PER_ITEM) throw new UserFacingError('You can reserve up to ' . COPS_MAX_QTY_PER_ITEM . ' of one item.');
    if ($qty > (int)$p['stock']) throw new UserFacingError('Only ' . (int)$p['stock'] . ' of ' . $p['name'] . ' left.');
    $cart[$pid] = $qty;
    $_SESSION['cart'] = $cart;
    return 'Cart updated.';
}

function cart_remove(int $pid): string
{
    $cart = cart_raw();
    unset($cart[$pid]);
    $_SESSION['cart'] = $cart;
    return 'Item removed.';
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/** Cart lines priced from the DATABASE (never from the browser). */
function cart_lines(mysqli $c): array
{
    $cart  = cart_raw();
    $lines = []; $total = 0.0; $count = 0; $problem = false;
    foreach ($cart as $pid => $qty) {
        $p = db_one($c, "SELECT id, name, category, price, stock, image, is_available FROM products WHERE id = ?", 'i', [$pid]);
        if (!$p || !(int)$p['is_available']) { unset($cart[$pid]); continue; }   // item left the menu: drop it quietly
        $issue = null;
        if ((int)$p['stock'] <= 0) $issue = 'Sold out';
        elseif ($qty > (int)$p['stock']) $issue = 'Only ' . (int)$p['stock'] . ' left';
        $sub = round((float)$p['price'] * $qty, 2);
        $lines[] = ['id' => (int)$p['id'], 'name' => $p['name'], 'category' => $p['category'], 'image' => $p['image'], 'price' => (float)$p['price'],
                    'qty' => $qty, 'stock' => (int)$p['stock'], 'subtotal' => $sub, 'issue' => $issue];
        $total += $sub; $count += $qty; if ($issue) $problem = true;
    }
    $_SESSION['cart'] = $cart;
    return ['lines' => $lines, 'total' => round($total, 2), 'count' => $count, 'problem' => $problem];
}

/* ══ Pickup slots ═══════════════════════════════════════════════════════════ */

function slots_for_day(mysqli $c, string $date): array
{
    $rows = db_all($c,
        "SELECT s.id, s.label, s.start_time, s.end_time, s.capacity,
                (SELECT COUNT(*) FROM reservations r WHERE r.pickup_slot_id = s.id AND r.pickup_date = ? AND r.status NOT IN ('rejected','cancelled')) AS used
         FROM pickup_slots s WHERE s.is_active = 1 ORDER BY s.sort_order, s.start_time", 's', [$date]);
    $now = time();
    foreach ($rows as &$s) {
        $s['id']        = (int)$s['id'];
        $s['capacity']  = (int)$s['capacity'];
        $s['used']      = (int)$s['used'];
        $s['remaining'] = max(0, $s['capacity'] - $s['used']);
        $s['time_label'] = date('g:i A', strtotime($s['start_time'])) . ' – ' . date('g:i A', strtotime($s['end_time']));
        $s['closed'] = COPS_ENFORCE_SLOT_CUTOFF && $date === date('Y-m-d')
            && $now >= strtotime($date . ' ' . $s['start_time']) - COPS_SLOT_CUTOFF_MINUTES * 60;
        $s['available'] = $s['remaining'] > 0 && !$s['closed'];
    }
    unset($s);
    return $rows;
}

/* ══ Checkout ═══════════════════════════════════════════════════════════════ */

const REF_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O/1/I — easy to read aloud

function res_make_reference(): string
{
    $s = '';
    for ($i = 0; $i < 6; $i++) $s .= REF_ALPHABET[random_int(0, strlen(REF_ALPHABET) - 1)];
    return 'COPS-' . $s;
}

/**
 * Turns the session cart into a reservation. One transaction: either everything is saved or nothing is.
 * @return array{id:int,reference:string,total:float}
 * @throws UserFacingError
 */
function create_reservation(mysqli $c, array $user, int $slotId, ?string $note): array
{
    $cart = cart_raw();
    if (!$cart) throw new UserFacingError('Your cart is empty.');
    $note = $note === null ? null : trim(mb_substr(strip_tags($note), 0, 160));
    if ($note === '') $note = null;
    $date = date('Y-m-d');
    $uid  = (int)$user['id'];

    $c->begin_transaction();
    try {
        // 1. limit on open reservations per student
        $open = db_one($c, "SELECT COUNT(*) AS n FROM reservations WHERE user_id = ? AND status IN ('pending','confirmed','preparing','ready') FOR UPDATE", 'i', [$uid]);
        if ((int)$open['n'] >= COPS_MAX_ACTIVE_PER_STUDENT) {
            throw new UserFacingError('You already have ' . COPS_MAX_ACTIVE_PER_STUDENT . ' open reservations. Wait for one to be completed, or cancel one first.');
        }

        // 2. slot exists, is open, and still has room (row lock = two students cannot take the last spot together)
        $slot = db_one($c, "SELECT id, label, start_time, capacity FROM pickup_slots WHERE id = ? AND is_active = 1 FOR UPDATE", 'i', [$slotId]);
        if (!$slot) throw new UserFacingError('Please choose a pickup time.');
        if (COPS_ENFORCE_SLOT_CUTOFF && time() >= strtotime($date . ' ' . $slot['start_time']) - COPS_SLOT_CUTOFF_MINUTES * 60) {
            throw new UserFacingError($slot['label'] . ' is already closed for today. Please choose a later slot.');
        }
        $used = db_one($c, "SELECT COUNT(*) AS n FROM reservations WHERE pickup_slot_id = ? AND pickup_date = ? AND status NOT IN ('rejected','cancelled')", 'is', [$slotId, $date]);
        if ((int)$used['n'] >= (int)$slot['capacity']) throw new UserFacingError($slot['label'] . ' is full. Please choose another slot.');

        // 3. price + reserve stock, line by line (ordered by id so concurrent checkouts cannot deadlock)
        ksort($cart);
        $lines = []; $total = 0.0;
        foreach ($cart as $pid => $qty) {
            $p = db_one($c, "SELECT id, name, price, stock, is_available FROM products WHERE id = ? FOR UPDATE", 'i', [(int)$pid]);
            if (!$p || !(int)$p['is_available']) throw new UserFacingError('An item in your cart is no longer on the menu. Please review your cart.');
            if ($qty < 1 || $qty > COPS_MAX_QTY_PER_ITEM) throw new UserFacingError('Invalid quantity for ' . $p['name'] . '.');
            if ((int)$p['stock'] < $qty) throw new UserFacingError((int)$p['stock'] > 0 ? 'Only ' . (int)$p['stock'] . ' of ' . $p['name'] . ' left.' : $p['name'] . ' just sold out.');
            $price = (float)$p['price'];
            $sub   = round($price * $qty, 2);
            $total += $sub;
            $lines[] = [$p['id'], $p['name'], $qty, $price, $sub];
            db_exec($c, "UPDATE products SET stock = stock - ? WHERE id = ?", 'ii', [$qty, (int)$p['id']]);
        }

        // 4. the reservation itself, with a unique reference
        $rid = 0; $ref = '';
        for ($try = 0; $try < 8 && !$rid; $try++) {
            $ref = res_make_reference();
            try {
                $st = db_run($c, "INSERT INTO reservations (reference, user_id, student_name, student_ref, pickup_slot_id, pickup_date, note, total_amount, status)
                                  VALUES (?,?,?,?,?,?,?,?, 'pending')",
                             'sississd', [$ref, $uid, $user['name'], $user['student_id'] ?? null, $slotId, $date, $note, round($total, 2)]);
                $rid = (int)$c->insert_id;
                $st->close();
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() !== 1062) throw $e;     // 1062 = duplicate reference → try another one
            }
        }
        if (!$rid) throw new UserFacingError('Could not create the reservation. Please try again.');

        foreach ($lines as [$pid, $name, $qty, $price, $sub]) {
            db_exec($c, "INSERT INTO reservation_items (reservation_id, product_id, product_name, quantity, unit_price, line_subtotal) VALUES (?,?,?,?,?,?)",
                    'iisidd', [$rid, (int)$pid, $name, $qty, $price, $sub]);
        }
        db_exec($c, "INSERT INTO reservation_status_log (reservation_id, from_status, to_status, actor_type, actor_id, actor_name) VALUES (?, NULL, 'pending', 'student', ?, ?)",
                'iss', [$rid, (string)$uid, $user['name']]);
        $c->commit();
    } catch (Throwable $e) {
        $c->rollback();
        if ($e instanceof UserFacingError) throw $e;
        app_log('create_reservation failed: ' . $e->getMessage());
        throw new UserFacingError('We could not save your reservation. Please try again.');
    }

    cart_clear();
    return ['id' => $rid, 'reference' => $ref, 'total' => round($total, 2)];
}

/* ══ Reading reservations ═══════════════════════════════════════════════════ */

function res_attach_items(mysqli $c, array $rows): array
{
    if (!$rows) return [];
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in  = implode(',', $ids);                       // integers only
    $items = [];
    foreach (db_all($c, "SELECT reservation_id, product_name, quantity, unit_price, line_subtotal FROM reservation_items WHERE reservation_id IN ($in) ORDER BY id") as $it) {
        $items[(int)$it['reservation_id']][] = ['name' => $it['product_name'], 'qty' => (int)$it['quantity'], 'price' => (float)$it['unit_price'], 'subtotal' => (float)$it['line_subtotal']];
    }
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['total'] = (float)$r['total_amount'];
        $r['created_ts'] = (int)$r['created_ts'];
        $r['updated_ts'] = (int)$r['updated_ts'];
        $r['items'] = $items[$r['id']] ?? [];
        $r['time_label'] = isset($r['slot_start']) ? date('g:i A', strtotime($r['slot_start'])) : '';
    }
    unset($r);
    return $rows;
}

const RES_SELECT = "SELECT r.id, r.reference, r.status, r.status_reason, r.note, r.total_amount, r.pickup_date, r.student_name, r.student_ref,
                           r.handled_by_name, UNIX_TIMESTAMP(r.created_at) AS created_ts, UNIX_TIMESTAMP(r.updated_at) AS updated_ts,
                           s.label AS slot_label, s.start_time AS slot_start
                    FROM reservations r JOIN pickup_slots s ON s.id = r.pickup_slot_id";

/** A student's own reservations only. Ownership is enforced in the query itself. */
function student_reservations(mysqli $c, int $uid, int $limit = 15, ?string $ref = null): array
{
    if ($ref !== null && $ref !== '') {
        $rows = db_all($c, RES_SELECT . " WHERE r.user_id = ? AND r.reference = ? LIMIT 1", 'is', [$uid, strtoupper(trim($ref))]);
    } else {
        $rows = db_all($c, RES_SELECT . " WHERE r.user_id = ? ORDER BY r.id DESC LIMIT " . (int)$limit, 'i', [$uid]);
    }
    return res_attach_items($c, $rows);
}

/* ── Staff queries ── */

function staff_filters(array $in): array
{
    $date = $in['date'] ?? date('Y-m-d');
    if ($date !== 'all' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) $date = date('Y-m-d');
    $status = $in['status'] ?? 'all';
    if (!in_array($status, array_merge(['all', 'attention', 'open'], array_keys(res_statuses())), true)) $status = 'all';
    $sort = $in['sort'] ?? 'priority';
    if (!in_array($sort, ['priority', 'newest', 'oldest', 'pickup', 'total'], true)) $sort = 'priority';
    return ['date' => $date, 'status' => $status, 'q' => trim(mb_substr((string)($in['q'] ?? ''), 0, 60)), 'sort' => $sort];
}

function staff_counts(mysqli $c, string $date): array
{
    $counts = array_fill_keys(array_keys(res_statuses()), 0);
    $rows = $date === 'all'
        ? db_all($c, "SELECT status, COUNT(*) AS n FROM reservations GROUP BY status")
        : db_all($c, "SELECT status, COUNT(*) AS n FROM reservations WHERE pickup_date = ? GROUP BY status", 's', [$date]);
    foreach ($rows as $r) $counts[$r['status']] = (int)$r['n'];
    $counts['total'] = array_sum($counts);
    $earlier = db_one($c, "SELECT COUNT(*) AS n FROM reservations WHERE pickup_date < ? AND status IN ('pending','confirmed','preparing','ready')", 's', [date('Y-m-d')]);
    $counts['earlier_open'] = (int)$earlier['n'];
    return $counts;
}

function staff_reservations(mysqli $c, array $f): array
{
    $where = []; $types = ''; $params = [];
    if ($f['date'] !== 'all') { $where[] = 'r.pickup_date = ?'; $types .= 's'; $params[] = $f['date']; }
    if ($f['status'] === 'attention')      $where[] = "r.status IN ('pending','ready')";
    elseif ($f['status'] === 'open')       $where[] = "r.status IN ('pending','confirmed','preparing','ready')";
    elseif ($f['status'] !== 'all')        { $where[] = 'r.status = ?'; $types .= 's'; $params[] = $f['status']; }
    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(r.reference LIKE ? OR r.student_name LIKE ? OR r.student_ref LIKE ?)';
        $types .= 'sss'; array_push($params, $like, $like, $like);
    }
    $order = [
        'priority' => "FIELD(r.status,'pending','ready','preparing','confirmed','completed','rejected','cancelled'), r.created_at ASC",
        'newest'   => 'r.created_at DESC',
        'oldest'   => 'r.created_at ASC',
        'pickup'   => 's.start_time ASC, r.created_at ASC',
        'total'    => 'r.total_amount DESC',
    ][$f['sort']];
    $sql = RES_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT 300";
    $rows = res_attach_items($c, db_all($c, $sql, $types, $params));

    if ($rows) {
        $in = implode(',', array_column($rows, 'id'));
        $hist = [];
        foreach (db_all($c, "SELECT reservation_id, to_status, actor_type, actor_name, reason, UNIX_TIMESTAMP(created_at) AS ts FROM reservation_status_log WHERE reservation_id IN ($in) ORDER BY id") as $h) {
            $hist[(int)$h['reservation_id']][] = $h;
        }
        foreach ($rows as &$r) $r['history'] = $hist[$r['id']] ?? [];
        unset($r);
    }
    return $rows;
}

/* ── Public landing page data (real data only) ── */

function popular_products(mysqli $c): array
{
    return db_all($c,
        "SELECT p.id, p.name, p.category, p.price, p.image, p.stock, p.reorder_level, SUM(ri.quantity) AS units
         FROM reservation_items ri
         JOIN reservations r ON r.id = ri.reservation_id
         JOIN products p ON p.id = ri.product_id
         WHERE r.status IN ('confirmed','preparing','ready','completed') AND r.created_at >= (NOW() - INTERVAL 30 DAY)
           AND p.is_available = 1 AND p.stock > 0
         GROUP BY p.id, p.name, p.category, p.price, p.image, p.stock, p.reorder_level
         ORDER BY units DESC, p.name ASC LIMIT 4");
}