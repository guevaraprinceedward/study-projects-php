<?php
include_once 'hotel-config.php';
$activePage = 'reserve';

$wantedSlug = $_GET['suite'] ?? 'veranda';
requireHotelLogin('hotel-reserve.php' . ($wantedSlug ? '?suite='.urlencode($wantedSlug) : ''));

$tier = currentTier();

// ── SUITES + IMAGES + ROOM COUNT ─────────────────────────────────────────
$suites = [];
$res = $conn->query("SELECT * FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $imgs = [];
    $ir = $conn->query("SELECT image_url FROM suite_images WHERE suite_id = {$row['id']} ORDER BY sort_order ASC");
    while ($i = $ir->fetch_assoc()) $imgs[] = $i['image_url'];
    if (empty($imgs)) $imgs[] = $row['cover_image'];
    $row['images'] = $imgs;
    $row['features_arr'] = array_filter(explode('|', $row['features']));
    $rc = $conn->query("SELECT COUNT(*) c FROM rooms WHERE suite_id = {$row['id']} AND status='available'");
    $row['room_count'] = (int)$rc->fetch_assoc()['c'];
    $suites[$row['slug']] = $row;
}
if (!isset($suites[$wantedSlug])) $wantedSlug = array_key_first($suites);

// ── TIME SLOTS ────────────────────────────────────────────────────────
$slots = [];
$sr = $conn->query("SELECT * FROM time_slots ORDER BY id ASC");
while ($row = $sr->fetch_assoc()) $slots[] = $row;

// ── OFFERS (global + per suite) for client-side price preview ───────────
$offers = [];
$today = date('Y-m-d');
$or = $conn->query("SELECT * FROM offers WHERE active=1 AND valid_from <= '$today' AND valid_to >= '$today'
                     AND (audience='all' OR audience='$tier') ORDER BY discount_percent DESC");
while ($row = $or->fetch_assoc()) $offers[] = $row;

// Data for JS price engine
$suitesJson = [];
foreach ($suites as $slug => $s) {
    $suitesJson[$slug] = [
        'id' => (int)$s['id'], 'name' => $s['name'],
        'base_price' => (float)$s['base_price'], 'vip_price' => (float)$s['vip_price'],
        'room_count' => $s['room_count'], 'max_guests' => (int)$s['max_guests'],
    ];
}
$slotsJson = array_map(fn($s) => [
    'id' => (int)$s['id'], 'label' => $s['label'], 'modifier' => (float)$s['price_modifier'],
    'checkin' => substr($s['checkin_time'],0,5), 'checkout' => substr($s['checkout_time'],0,5),
    'desc' => $s['description'],
], $slots);
$offersJson = array_map(fn($o) => [
    'id' => (int)$o['id'], 'title' => $o['title'], 'discount' => (float)$o['discount_percent'],
    'min_nights' => (int)$o['min_nights'], 'audience' => $o['audience'], 'suite_id' => $o['suite_id'] ? (int)$o['suite_id'] : null,
], $offers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reserve a Residence — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
/* PAGE-SPECIFIC: reserve */
.reserve-layout{position:relative;z-index:1;max-width:1240px;margin:0 auto;padding:0 32px 120px;display:grid;grid-template-columns:1.15fr 0.85fr;gap:40px}
.res-gallery .slider{aspect-ratio:16/11}
.res-info{margin-top:20px}
.res-info h2{font-family:'Cormorant Garamond',serif;font-size:30px;color:var(--h-champagne);margin-bottom:8px}
.res-meta{display:flex;gap:14px;font-size:12px;color:var(--h-muted);margin-bottom:14px}
.res-desc{font-size:13.5px;color:var(--h-muted);line-height:1.75;margin-bottom:16px}
.res-features{list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:9px}
.res-features li{font-size:12.5px;color:var(--h-text);display:flex;align-items:center;gap:8px}
.res-features li::before{content:'';width:4px;height:4px;background:var(--h-gold);border-radius:50%;flex-shrink:0}

.booking-panel{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;padding:30px;align-self:start;position:sticky;top:90px}
.booking-panel h3{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--h-champagne);margin-bottom:4px}
.booking-panel .tier-note{font-size:11.5px;color:var(--h-muted);margin-bottom:22px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
.avail-note{font-size:11.5px;margin:-6px 0 14px;color:var(--h-good)}
.avail-note.low{color:#e0a95d}
.avail-note.none{color:var(--h-bad)}

.room-picker-label{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.room-picker-label label{font-size:10px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim);margin-bottom:0}
.room-picker-count{font-size:11px;color:var(--h-muted)}
.room-picker{display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:8px;margin-bottom:6px}
.room-picker-empty{font-size:12px;color:var(--h-muted);padding:10px 0}
.room-chip{position:relative}
.room-chip input{position:absolute;opacity:0;inset:0;cursor:pointer;margin:0}
.room-chip span{display:flex;align-items:center;justify-content:center;padding:10px 6px;border:1px solid var(--h-line);border-radius:3px;font-size:12px;color:var(--h-text);background:rgba(255,255,255,0.02);transition:all 0.15s;text-align:center}
.room-chip input:checked + span{border-color:var(--h-gold);background:rgba(207,167,107,0.12);color:var(--h-gold);font-weight:600}
.room-picker-hint{display:block;margin:0 0 14px;font-size:11px;color:var(--h-muted)}

.summary{border-top:1px solid var(--h-line);margin-top:18px;padding-top:18px;display:flex;flex-direction:column;gap:9px}
.summary-row{display:flex;justify-content:space-between;font-size:12.5px;color:var(--h-muted)}
.summary-row.total{font-size:17px;color:var(--h-champagne);font-family:'Cormorant Garamond',serif;font-weight:700;border-top:1px solid var(--h-line);padding-top:12px;margin-top:4px}
.summary-row .discount-val{color:#7fa876}
.offer-hit{background:rgba(207,167,107,0.08);border:1px solid rgba(207,167,107,0.25);border-radius:3px;padding:9px 11px;font-size:11.5px;color:var(--h-gold);margin-top:4px}
.form-msg{font-size:12px;margin-top:10px;display:none;padding:10px 12px;border-radius:3px}
.form-msg.error{display:block;background:rgba(192,87,74,0.1);border:1px solid rgba(192,87,74,0.3);color:#f0c9c2}

.modal-overlay{position:fixed;inset:0;background:rgba(4,4,6,0.75);backdrop-filter:blur(6px);z-index:500;display:none;align-items:center;justify-content:center;padding:24px}
.modal-overlay.open{display:flex}
.confirm-modal{width:100%;max-width:460px;background:#101013;border:1px solid var(--h-gold-dim);border-radius:6px;padding:40px 36px;position:relative;box-shadow:0 30px 80px rgba(0,0,0,0.6);max-height:90vh;overflow-y:auto}
.confirm-icon{width:56px;height:56px;border-radius:50%;background:rgba(127,168,118,0.12);border:1px solid rgba(127,168,118,0.35);display:flex;align-items:center;justify-content:center;margin:0 auto 22px;color:var(--h-good)}
.confirm-modal h2{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--h-champagne);text-align:center;margin-bottom:6px}
.confirm-modal .confirm-sub{text-align:center;font-size:12.5px;color:var(--h-muted);margin-bottom:26px}
.confirm-ref{text-align:center;font-family:'Cormorant Garamond',serif;font-size:22px;letter-spacing:0.08em;color:var(--h-gold);border:1px dashed var(--h-gold-dim);border-radius:4px;padding:12px;margin-bottom:20px}
.confirm-rooms{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-bottom:24px}
.confirm-room-chip{font-size:11px;letter-spacing:0.04em;color:var(--h-champagne);background:rgba(207,167,107,0.08);border:1px solid rgba(207,167,107,0.25);border-radius:100px;padding:5px 12px}
.confirm-rows{display:flex;flex-direction:column;gap:10px;margin-bottom:26px}
.confirm-rows .r{display:flex;justify-content:space-between;font-size:12.5px;color:var(--h-muted)}
.confirm-rows .r strong{color:var(--h-text);font-weight:500}
.confirm-rows .r.total{border-top:1px solid var(--h-line);padding-top:12px;margin-top:2px;font-size:16px;color:var(--h-champagne);font-family:'Cormorant Garamond',serif;font-weight:700}
.confirm-rows .r.total strong{color:var(--h-gold);font-family:inherit}
.confirm-offer{font-size:11.5px;color:var(--h-gold);text-align:center;margin:-14px 0 20px}
.confirm-actions{display:flex;gap:10px}
.confirm-actions .btn-gold,.confirm-actions .btn-ghost{flex:1}
.modal-close{position:absolute;top:16px;right:16px;background:none;border:none;color:var(--h-muted);font-size:20px;cursor:pointer;line-height:1}
.modal-close:hover{color:var(--h-champagne)}

@media(max-width:980px){
    .reserve-layout{grid-template-columns:1fr}
    .booking-panel{position:static}
}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow">Book Your Stay</div>
    <h1>Reserve a <em>Residence</em></h1>
    <p>Each residence — Veranda, Atelier, and the Penthouse — keeps its own room inventory. Choose your dates, your check-in window, and the exact room number you'd like.</p>
</section>

<div class="tabs" id="suiteTabs">
    <?php foreach ($suites as $slug => $s): ?>
    <button class="tab-btn <?= $slug===$wantedSlug?'active':'' ?>" data-tab="<?= $slug ?>"><?= htmlspecialchars($s['name']) ?></button>
    <?php endforeach; ?>
</div>

<?php foreach ($suites as $slug => $s):
    $price = $tier === 'vip' ? $s['vip_price'] : $s['base_price']; ?>
<div class="tab-panel <?= $slug===$wantedSlug?'active':'' ?>" data-panel="<?= $slug ?>">
    <div class="reserve-layout">
        <div>
            <div class="res-gallery">
                <div class="slider" data-slider>
                    <div class="slider-track">
                        <?php foreach ($s['images'] as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy"><?php endforeach; ?>
                    </div>
                    <?php if (count($s['images']) > 1): ?>
                    <button class="slider-arrow prev" aria-label="Previous photo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></button>
                    <button class="slider-arrow next" aria-label="Next photo"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></button>
                    <div class="slider-dots"><?php foreach ($s['images'] as $di=>$_): ?><span class="<?= $di===0?'active':'' ?>"></span><?php endforeach; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="res-info">
                <h2><?= htmlspecialchars($s['name']) ?></h2>
                <div class="res-meta"><span><?= (int)$s['size_sqm'] ?> sqm</span><span>·</span><span><?= htmlspecialchars($s['view_desc']) ?></span><span>·</span><span>Up to <?= (int)$s['max_guests'] ?> guests</span></div>
                <p class="res-desc"><?= htmlspecialchars($s['description']) ?></p>
                <ul class="res-features"><?php foreach ($s['features_arr'] as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
            </div>
        </div>

        <form class="booking-panel" method="POST" action="process-reservation.php" data-suite="<?= $slug ?>">
            <h3>₱<?= number_format($price) ?> <span style="font-size:12px;color:var(--h-muted);font-family:'Jost',sans-serif">/ night base</span></h3>
            <div class="tier-note"><?= $tier==='vip' ? 'Nocturne Noir VIP rate applied automatically.' : 'Regular guest rate — upgrade to VIP through continued stays.' ?></div>

            <input type="hidden" name="suite_id" value="<?= $s['id'] ?>">

            <div class="form-row">
                <div class="field"><label>Check-in</label><input type="date" name="checkin_date" class="in-checkin" min="<?= date('Y-m-d') ?>" required></div>
                <div class="field"><label>Check-out</label><input type="date" name="checkout_date" class="in-checkout" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required></div>
            </div>

            <div class="field" style="margin-bottom:14px">
                <label>Check-in Window</label>
                <select name="time_slot_id" class="in-slot" required>
                    <?php foreach ($slots as $sl): ?>
                    <option value="<?= $sl['id'] ?>"><?= htmlspecialchars($sl['label']) ?> (<?= substr($sl['checkin_time'],0,5) ?>–<?= substr($sl['checkout_time'],0,5) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="in-slot-desc"><?= htmlspecialchars($slots[0]['description']) ?></small>
            </div>

            <div class="field" style="margin-bottom:4px">
                <div class="room-picker-label">
                    <label>Select Room(s)</label>
                    <span class="room-picker-count"><span class="rp-selected">0</span> selected</span>
                </div>
                <div class="room-picker" data-room-picker>
                    <div class="room-picker-empty">Select your check-in and check-out dates to see available room numbers.</div>
                </div>
                <small class="room-picker-hint">Tap a room number to select it. You may choose more than one.</small>
            </div>

            <div class="field">
                <label>Guests</label>
                <select name="guests_count" class="in-guests">
                    <?php for ($g=1; $g<=$s['max_guests']; $g++): ?><option value="<?= $g ?>"><?= $g ?> guest<?= $g>1?'s':'' ?></option><?php endfor; ?>
                </select>
            </div>

            <div class="avail-note" data-avail style="margin-top:14px">Select your dates to check live availability.</div>

            <div class="summary">
                <div class="summary-row"><span>Nights</span><span class="sum-nights">—</span></div>
                <div class="summary-row"><span>Nightly rate (<?= $tier==='vip'?'VIP':'Regular' ?>, window-adjusted)</span><span class="sum-rate">—</span></div>
                <div class="summary-row"><span>Subtotal (<span class="sum-qty">0</span> room × nights)</span><span class="sum-subtotal">—</span></div>
                <div class="summary-row"><span>Offer discount</span><span class="sum-discount discount-val">—</span></div>
                <div class="summary-row total"><span>Total</span><span class="sum-total">₱0</span></div>
                <div class="offer-hit" style="display:none"></div>
            </div>

            <button type="submit" class="btn-gold" style="width:100%;margin-top:18px" disabled>Confirm Reservation</button>
            <div class="form-msg error"></div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<div class="modal-overlay" id="confirmOverlay">
    <div class="confirm-modal">
        <button class="modal-close" id="confirmClose">&times;</button>
        <div class="confirm-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h2>Reservation Confirmed</h2>
        <p class="confirm-sub">A member of our team will be in touch shortly. Your booking details are below.</p>
        <div class="confirm-ref" id="confirmRef">NB-000000</div>
        <div class="confirm-rooms" id="confirmRooms"></div>
        <div class="confirm-offer" id="confirmOffer" style="display:none"></div>
        <div class="confirm-rows">
            <div class="r"><span>Nights</span><strong id="confirmNights">—</strong></div>
            <div class="r"><span>Nightly rate</span><strong id="confirmRate">—</strong></div>
            <div class="r"><span>Subtotal</span><strong id="confirmSubtotal">—</strong></div>
            <div class="r"><span>Discount</span><strong id="confirmDiscount" style="color:var(--h-good)">—</strong></div>
            <div class="r total"><span>Total</span><strong id="confirmTotal">₱0</strong></div>
        </div>
        <div class="confirm-actions">
            <a href="dashboard.php" class="btn-ghost">View My Bookings</a>
            <button type="button" class="btn-gold" id="confirmDone">Done</button>
        </div>
    </div>
</div>

<script>
const TIER = <?= json_encode($tier) ?>;
const SUITES = <?= json_encode($suitesJson) ?>;
const SLOTS = <?= json_encode($slotsJson) ?>;
const OFFERS = <?= json_encode($offersJson) ?>;
const PESO = n => '₱' + Math.round(n).toLocaleString('en-PH');

document.querySelectorAll('#suiteTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#suiteTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector(`.tab-panel[data-panel="${btn.dataset.tab}"]`).classList.add('active');
        history.replaceState(null, '', `?suite=${btn.dataset.tab}`);
    });
});

document.querySelectorAll('[data-slider]').forEach(slider => {
    const track = slider.querySelector('.slider-track');
    const slides = track.children.length;
    if (slides <= 1) return;
    let idx = 0;
    const dots = slider.querySelectorAll('.slider-dots span');
    function go(n){ idx = (n + slides) % slides; track.style.transform = `translateX(-${idx*100}%)`; dots.forEach((d,i)=>d.classList.toggle('active', i===idx)); }
    slider.querySelector('.slider-arrow.prev')?.addEventListener('click', ()=>go(idx-1));
    slider.querySelector('.slider-arrow.next')?.addEventListener('click', ()=>go(idx+1));
    dots.forEach((d,i)=>d.addEventListener('click', ()=>go(i)));
});

document.querySelectorAll('.booking-panel').forEach(form => {
    const slug = form.dataset.suite;
    const suite = SUITES[slug];
    const inCheckin = form.querySelector('.in-checkin');
    const inCheckout = form.querySelector('.in-checkout');
    const inSlot = form.querySelector('.in-slot');
    const slotDesc = form.querySelector('.in-slot-desc');
    const roomPicker = form.querySelector('[data-room-picker]');
    const rpSelectedCount = form.querySelector('.rp-selected');
    const availNote = form.querySelector('[data-avail]');
    const submitBtn = form.querySelector('button[type="submit"]');
    const msgBox = form.querySelector('.form-msg');

    function nights(){
        if (!inCheckin.value || !inCheckout.value) return 0;
        const a = new Date(inCheckin.value), b = new Date(inCheckout.value);
        const n = Math.round((b - a) / 86400000);
        return n > 0 ? n : 0;
    }

    function bestOffer(n){
        const applicable = OFFERS.filter(o =>
            n >= o.min_nights &&
            (o.audience === 'all' || o.audience === TIER) &&
            (o.suite_id === null || o.suite_id === suite.id)
        );
        if (!applicable.length) return null;
        return applicable.reduce((a,b) => a.discount > b.discount ? a : b);
    }

    function selectedRoomIds(){
        return Array.from(roomPicker.querySelectorAll('input[name="room_ids[]"]:checked')).map(el => el.value);
    }

    function renderRoomPicker(rooms){
        if (!rooms || !rooms.length) {
            roomPicker.innerHTML = '<div class="room-picker-empty">No rooms available for these dates — try a different date range.</div>';
            rpSelectedCount.textContent = '0';
            recalc();
            return;
        }
        roomPicker.innerHTML = rooms.map(r => `
            <label class="room-chip">
                <input type="checkbox" name="room_ids[]" value="${r.id}">
                <span>${r.room_number}</span>
            </label>
        `).join('');
        roomPicker.querySelectorAll('input[name="room_ids[]"]').forEach(cb => {
            cb.addEventListener('change', () => {
                rpSelectedCount.textContent = selectedRoomIds().length;
                recalc();
            });
        });
        rpSelectedCount.textContent = '0';
        recalc();
    }

    async function checkAvailability(){
        if (!inCheckin.value || !inCheckout.value || inCheckout.value <= inCheckin.value){
            availNote.textContent = 'Select your dates to check live availability.';
            availNote.className = 'avail-note';
            roomPicker.innerHTML = '<div class="room-picker-empty">Select your check-in and check-out dates to see available room numbers.</div>';
            rpSelectedCount.textContent = '0';
            recalc();
            return;
        }
        try {
            const r = await fetch(`availability.php?suite_id=${suite.id}&checkin=${inCheckin.value}&checkout=${inCheckout.value}`);
            const data = await r.json();
            if (data.ok){
                if (data.available <= 0){
                    availNote.textContent = 'Fully booked for these dates — try different dates.';
                    availNote.className = 'avail-note none';
                } else if (data.available <= 2){
                    availNote.textContent = `Only ${data.available} room${data.available>1?'s':''} left for these dates.`;
                    availNote.className = 'avail-note low';
                } else {
                    availNote.textContent = `${data.available} rooms available for these dates.`;
                    availNote.className = 'avail-note';
                }
                renderRoomPicker(data.rooms || []);
            }
        } catch(e){ /* keep prior state on network hiccup */ }
    }

    function recalc(){
        const n = nights();
        const slotId = parseInt(inSlot.value);
        const slot = SLOTS.find(s => s.id === slotId);
        slotDesc.textContent = slot ? slot.desc : '';
        const baseRate = TIER === 'vip' ? suite.vip_price : suite.base_price;
        const rate = slot ? baseRate * slot.modifier : baseRate;
        const qty = selectedRoomIds().length;
        const subtotal = rate * Math.max(n,0) * qty;
        const offer = (n > 0 && qty > 0) ? bestOffer(n) : null;
        const discount = offer ? subtotal * (offer.discount/100) : 0;
        const total = subtotal - discount;

        form.querySelector('.sum-nights').textContent = n > 0 ? n + (n===1?' night':' nights') : '—';
        form.querySelector('.sum-rate').textContent = PESO(rate);
        form.querySelector('.sum-qty').textContent = qty;
        form.querySelector('.sum-subtotal').textContent = (n > 0 && qty > 0) ? PESO(subtotal) : '—';
        form.querySelector('.sum-discount').textContent = discount > 0 ? '−' + PESO(discount) : '₱0';
        form.querySelector('.sum-total').textContent = PESO(Math.max(total,0));

        const offerBox = form.querySelector('.offer-hit');
        if (offer){
            offerBox.style.display = 'block';
            offerBox.textContent = `"${offer.title}" applied — ${offer.discount}% off.`;
        } else {
            offerBox.style.display = 'none';
        }

        submitBtn.disabled = !(n > 0 && qty > 0);
    }

    inCheckin.addEventListener('change', () => {
        if (inCheckout.value && inCheckout.value <= inCheckin.value){
            const d = new Date(inCheckin.value); d.setDate(d.getDate()+1);
            inCheckout.value = d.toISOString().slice(0,10);
        }
        inCheckout.min = new Date(new Date(inCheckin.value).getTime()+86400000).toISOString().slice(0,10);
        checkAvailability();
    });
    inCheckout.addEventListener('change', checkAvailability);
    inSlot.addEventListener('change', recalc);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        msgBox.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        try {
            const fd = new FormData(form);
            const r = await fetch('process-reservation.php', { method: 'POST', body: fd });
            const data = await r.json();

            if (data.ok) {
                document.getElementById('confirmRef').textContent = data.ref;
                document.getElementById('confirmNights').textContent = data.nights + (data.nights === 1 ? ' night' : ' nights');
                document.getElementById('confirmRate').textContent = PESO(data.nightly_rate);
                document.getElementById('confirmSubtotal').textContent = PESO(data.subtotal);
                document.getElementById('confirmDiscount').textContent = data.discount > 0 ? '−' + PESO(data.discount) : '₱0';
                document.getElementById('confirmTotal').textContent = PESO(data.total);

                const roomsBox = document.getElementById('confirmRooms');
                roomsBox.innerHTML = (data.rooms || []).map(rn => `<span class="confirm-room-chip">${rn}</span>`).join('');

                const offerLine = document.getElementById('confirmOffer');
                if (data.offer_title) {
                    offerLine.style.display = 'block';
                    offerLine.textContent = `"${data.offer_title}" applied to this booking.`;
                } else {
                    offerLine.style.display = 'none';
                }
                document.getElementById('confirmOverlay').classList.add('open');
                form.reset();
                roomPicker.innerHTML = '<div class="room-picker-empty">Select your check-in and check-out dates to see available room numbers.</div>';
                rpSelectedCount.textContent = '0';
                recalc();
            } else {
                msgBox.textContent = data.error || 'Something went wrong. Please try again.';
                msgBox.style.display = 'block';
                checkAvailability();
            }
        } catch (err) {
            msgBox.textContent = 'Network error — please check your connection and try again.';
            msgBox.style.display = 'block';
        } finally {
            submitBtn.textContent = 'Confirm Reservation';
            recalc();
        }
    });

    recalc();
});

document.getElementById('confirmClose').addEventListener('click', () => document.getElementById('confirmOverlay').classList.remove('open'));
document.getElementById('confirmDone').addEventListener('click', () => document.getElementById('confirmOverlay').classList.remove('open'));
</script>
</body>
</html>