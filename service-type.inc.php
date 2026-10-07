<?php
/**
 * service-type.inc.php
 * -------------------------------------------------------------------------
 * Dine-in / Take-out / Pick-up / Delivery picker, used by checkout.php.
 * For Delivery, also collects the address, contact number, and a specific
 * delivery date & time — this is what lets the order enter the staff
 * approval pipeline in admin-orders.php (pending -> preparing -> ready ->
 * out for delivery -> completed).
 *
 *   include_once 'customize-lib.php';
 *   include_once 'service-type.inc.php';
 *   $svc = svcResolve($_POST);      // validate on the server
 *   svcWidget($selected, $address, $phone, $date, $time);   // inside the <form>
 *
 * In the page you may add  id="svcFeeRow"  to the delivery-fee row and
 * id="svcTotal" data-base="<?= $total ?>"  to the Total value; the widget
 * updates them live.
 */
if (!defined('DELIVERY_MIN_LEAD_MIN')) define('DELIVERY_MIN_LEAD_MIN', 30);
if (!defined('DELIVERY_MAX_DAYS_AHEAD')) define('DELIVERY_MAX_DAYS_AHEAD', 7);

if (!function_exists('svcResolve')) {

function svcResolve(array $p): array {
    $types = custOrderTypes();
    $t = (string)($p['service_type'] ?? '');
    if (!isset($types[$t])) {
        return ['ok' => false, 'error' => 'Please choose Dine-in, Take-out, Pick-up or Delivery.'];
    }
    $out = ['ok' => true, 'type' => $t, 'label' => $types[$t], 'address' => '', 'phone' => '', 'fee' => 0.0, 'date' => null, 'time' => null];
    if ($t === 'delivery') {
        $addr = mb_substr(trim((string)($p['delivery_address'] ?? '')), 0, 255);
        $ph   = trim((string)($p['delivery_phone'] ?? ''));
        $date = trim((string)($p['delivery_date'] ?? ''));
        $time = trim((string)($p['delivery_time'] ?? ''));
        if (preg_match('/^(\d{2}:\d{2})(:\d{2})?$/', $time, $m)) $time = $m[1];

        if (mb_strlen($addr) < 10) return ['ok' => false, 'error' => 'Please enter your complete delivery address.'];
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $ph)) return ['ok' => false, 'error' => 'Please enter a valid contact number for the delivery.'];

        $when = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        if (!$when || $when->format('Y-m-d H:i') !== $date . ' ' . $time) {
            return ['ok' => false, 'error' => 'Please choose a valid delivery date and time.'];
        }
        if ($when->getTimestamp() < time() + DELIVERY_MIN_LEAD_MIN * 60) {
            return ['ok' => false, 'error' => 'Please choose a delivery time at least ' . DELIVERY_MIN_LEAD_MIN . ' minutes from now.'];
        }
        if ($when->getTimestamp() > strtotime('+' . DELIVERY_MAX_DAYS_AHEAD . ' days')) {
            return ['ok' => false, 'error' => 'Delivery can be scheduled up to ' . DELIVERY_MAX_DAYS_AHEAD . ' days ahead.'];
        }

        $out['address'] = $addr;
        $out['phone']   = $ph;
        $out['fee']     = (float)DELIVERY_FEE;
        $out['date']    = $date;
        $out['time']    = $time . ':00';
    }
    return $out;
}

function svcWidget(string $selected = '', string $address = '', string $phone = '', string $date = '', string $time = '', bool $askPhone = true): void {
    $sub = ['dine-in' => 'Eat at the branch', 'take-out' => 'Packed to go', 'pick-up' => 'Pick up at the counter', 'delivery' => 'Brought to your address'];
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $minDate = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+' . DELIVERY_MAX_DAYS_AHEAD . ' days'));
    ?>
<style>
.svc-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.svc-opt{position:relative;display:flex;flex-direction:column;gap:2px;background:var(--surface);border:1px solid var(--border);border-radius:3px;padding:12px 14px;cursor:pointer;color:var(--muted);font-size:13px;font-weight:500;transition:all .2s;user-select:none}
.svc-opt small{font-size:11px;font-weight:300;color:var(--muted)}
.svc-opt input{position:absolute;opacity:0;pointer-events:none}
.svc-opt:has(input:checked){border-color:var(--gold);background:rgba(201,168,76,.08);color:var(--cream)}
.svc-opt:has(input:focus-visible){outline:2px solid var(--gold);outline-offset:2px}
.svc-delivery{margin-top:14px;background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:16px 16px 4px}
.svc-delivery[hidden]{display:none}
.svc-delivery label{display:block;font-size:11px;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.svc-delivery input,.svc-delivery textarea{width:100%;background:var(--card);border:1px solid var(--border);border-radius:3px;color:var(--cream);font-family:'Jost',sans-serif;font-size:14px;padding:10px 13px;outline:none;margin-bottom:14px;color-scheme:dark}
.svc-delivery input:focus,.svc-delivery textarea:focus{border-color:var(--gold-dim)}
.svc-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.svc-hint{font-size:11px;color:var(--muted);margin:-9px 0 14px}
@media(max-width:480px){.svc-grid{grid-template-columns:1fr}.svc-row2{grid-template-columns:1fr}}
</style>
<div class="svc-grid" role="radiogroup" aria-label="Dine-in, take-out, pick-up or delivery">
<?php $first = true; foreach (custOrderTypes() as $k => $lab): ?>
    <label class="svc-opt">
        <input type="radio" name="service_type" value="<?= $h($k) ?>" <?= $selected === $k ? 'checked' : '' ?> <?= $first ? 'required' : '' ?>>
        <span><?= $h($lab) ?></span><small><?= $h($sub[$k] ?? '') ?></small>
    </label>
<?php $first = false; endforeach; ?>
</div>
<div class="svc-delivery" id="svcDelivery" hidden>
    <label for="svcAddr">Delivery address</label>
    <textarea id="svcAddr" name="delivery_address" rows="2" maxlength="255" placeholder="House no., street, barangay, city"><?= $h($address) ?></textarea>
    <?php if ($askPhone): ?>
    <label for="svcPhone">Contact number</label>
    <input type="tel" id="svcPhone" name="delivery_phone" maxlength="20" placeholder="09XX-XXX-XXXX" value="<?= $h($phone) ?>">
    <?php endif; ?>
    <div class="svc-row2">
        <div>
            <label for="svcDate">Delivery date</label>
            <input type="date" id="svcDate" name="delivery_date" min="<?= $h($minDate) ?>" max="<?= $h($maxDate) ?>" value="<?= $h($date) ?>">
        </div>
        <div>
            <label for="svcTime">Delivery time</label>
            <input type="time" id="svcTime" name="delivery_time" value="<?= $h($time) ?>">
        </div>
    </div>
    <p class="svc-hint">Book at least <?= DELIVERY_MIN_LEAD_MIN ?> minutes from now, up to <?= DELIVERY_MAX_DAYS_AHEAD ?> days ahead. Your order is confirmed once a staff member accepts it and sets the preparation time — similar to a food delivery app.</p>
    <?php if ((float)DELIVERY_FEE > 0): ?><p style="font-size:12px;color:var(--gold);margin:-4px 0 14px">Delivery fee: ₱<?= number_format((float)DELIVERY_FEE, 2) ?></p><?php endif; ?>
</div>
<script>
(function(){
    var fee = <?= json_encode((float)DELIVERY_FEE) ?>;
    function upd(){
        var r = document.querySelector('input[name="service_type"]:checked'), d = !!r && r.value === 'delivery';
        document.getElementById('svcDelivery').hidden = !d;
        var a = document.getElementById('svcAddr'), p = document.getElementById('svcPhone'),
            dt = document.getElementById('svcDate'), tm = document.getElementById('svcTime');
        if (a) a.required = d; if (p) p.required = d; if (dt) dt.required = d; if (tm) tm.required = d;
        var row = document.getElementById('svcFeeRow'); if (row) row.style.display = d ? '' : 'none';
        var t = document.getElementById('svcTotal');
        if (t && t.dataset.base) t.textContent = '₱' + (parseFloat(t.dataset.base) + (d ? fee : 0)).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
    }
    document.querySelectorAll('input[name="service_type"]').forEach(function(i){ i.addEventListener('change', upd); });
    upd();
})();
</script>
<?php
}
}
