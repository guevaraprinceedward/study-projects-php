<?php
include_once 'hotel-config.php';
$activePage = 'services';
$tier = currentTier();

$suites = [];
$res = $conn->query("SELECT id, slug, name FROM suites ORDER BY id ASC");
while ($row = $res->fetch_assoc()) $suites[$row['slug']] = $row;

$itemsBySuite = [];
$sr = $conn->query("SELECT * FROM services WHERE active = 1 AND type = 'minibar' ORDER BY suite_id ASC, id ASC");
while ($row = $sr->fetch_assoc()) $itemsBySuite[$row['suite_id']][] = $row;

$addonsByService = [];
$ar = $conn->query("SELECT * FROM service_addons WHERE active = 1 ORDER BY id ASC");
while ($row = $ar->fetch_assoc()) $addonsByService[$row['service_id']][] = $row;

$discoMoods = ['Jazz', 'Calm', 'Old Money', 'Decent'];
$discoPageSize = 6;

$tracks = [];
$tr = @$conn->query("SELECT * FROM disco_tracks WHERE active = 1 ORDER BY sort_order ASC, id ASC");
if ($tr) while ($row = $tr->fetch_assoc()) $tracks[] = $row;

$discoTrackPayload = [];
foreach ($tracks as $t) {
    if (($t['url'] ?? '') === '') continue;
    $mood = $t['category'] ?? 'Jazz';
    if (!in_array($mood, $discoMoods, true)) $mood = 'Jazz';
    $discoTrackPayload[] = [
        'id'     => (int)$t['id'],
        'title'  => $t['title'],
        'artist' => $t['artist'] ?? '',
        'url'    => $t['url'],
        'mood'   => $mood,
    ];
}

$wantedSlug = $_GET['suite'] ?? array_key_first($suites);
if (!isset($suites[$wantedSlug])) $wantedSlug = array_key_first($suites);

function svcPrice($s, $tier) { return $tier === 'vip' ? (float)$s['vip_price'] : (float)$s['base_price']; }
function addonPrice($a, $tier) { return $tier === 'vip' ? (float)$a['vip_price'] : (float)$a['price']; }

function render_minibar_card($s, $tier, $suiteName, $addons) {
    $price = svcPrice($s, $tier);
    $maxQty = $s['stock'] !== null ? (int)$s['stock'] : 20;
    ob_start(); ?>
    <div class="svc-card">
        <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" loading="lazy">
        <div class="svc-body">
            <h4><?= htmlspecialchars($s['name']) ?></h4>
            <p><?= htmlspecialchars($s['description']) ?></p>
            <div class="svc-price">₱<?= number_format($price) ?> <span>/ item<?= $tier === 'vip' ? ' · VIP' : '' ?></span></div>
            <div class="svc-order" data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>"
                 data-suite="<?= htmlspecialchars($suiteName) ?>" data-price="<?= $price ?>" data-unit="per_item">
                <?php if (!empty($addons)): ?>
                <div class="addon-block">
                    <label class="addon-label">Add-ons</label>
                    <div class="addon-list">
                        <?php foreach ($addons as $a): $ap = addonPrice($a, $tier); ?>
                        <label class="addon-item">
                            <input type="checkbox" class="svc-addon" value="<?= $a['id'] ?>" data-name="<?= htmlspecialchars($a['name']) ?>" data-price="<?= $ap ?>">
                            <span class="addon-name"><?= htmlspecialchars($a['name']) ?><br><small><?= htmlspecialchars($a['description']) ?></small></span>
                            <span class="addon-price">+₱<?= number_format($ap) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="field">
                        <label>Qty</label>
                        <div class="qty-control">
                            <button type="button" class="qty-minus">−</button>
                            <input type="number" class="svc-qty" value="1" min="1" max="<?= $maxQty ?>" readonly>
                            <button type="button" class="qty-plus">+</button>
                        </div>
                    </div>
                    <button type="button" class="btn-gold svc-add">Add to Order</button>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mini Bar &amp; Disco — Nocturne Manila Bay</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="hotel-theme.css">
<style>
.svc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px;max-width:1240px;margin:0 auto;padding:0 32px}
.svc-card{background:var(--h-card);border:1px solid var(--h-line);border-radius:4px;overflow:hidden;display:flex;flex-direction:column}
.svc-card img{height:150px;object-fit:cover}
.svc-body{padding:18px;display:flex;flex-direction:column;gap:8px}
.svc-body h4{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--h-champagne)}
.svc-body p{font-size:12px;color:var(--h-muted);line-height:1.6}
.svc-price{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--h-gold);font-weight:700}
.svc-price span{font-size:10px;font-family:'Jost',sans-serif;color:var(--h-muted);text-transform:uppercase}
.svc-order{border-top:1px solid var(--h-line);padding-top:12px;margin-top:4px;display:flex;flex-direction:column;gap:10px}
.svc-order .form-row{align-items:flex-end}
.svc-add{width:100%; margin-top:20px;}
.addon-block{display:flex;flex-direction:column;gap:6px}
.addon-label{font-size:10px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-gold-dim)}
.addon-list{display:flex;flex-direction:column;gap:6px}
.addon-item{display:flex;align-items:flex-start;gap:8px;font-size:11.5px;color:var(--h-text);padding:8px;border:1px solid var(--h-line);border-radius:3px;cursor:pointer}
.addon-item:hover{border-color:var(--h-gold-dim)}
.addon-item input{margin-top:2px}
.addon-name{flex:1}
.addon-name small{color:var(--h-muted);font-weight:300}
.addon-price{color:var(--h-gold);white-space:nowrap;font-weight:600}

/* ── DISCO ── */
.disco-section{max-width:1240px;margin:0 auto;padding:60px 32px 20px}
.disco-panel{background:linear-gradient(160deg,#171015,#0c0c0e);border:1px solid rgba(207,167,107,0.25);border-radius:10px;padding:40px;position:relative;overflow:hidden}
.disco-panel::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 15% 10%,rgba(207,167,107,0.14),transparent 55%),radial-gradient(circle at 90% 90%,rgba(122,46,58,0.18),transparent 50%);pointer-events:none}
.disco-panel::after{content:'';position:absolute;top:-140px;right:-140px;width:340px;height:340px;border-radius:50%;border:1px solid rgba(207,167,107,0.08);pointer-events:none}
.disco-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:6px;position:relative;flex-wrap:wrap}
.disco-head-left{display:flex;align-items:center;gap:12px}
.disco-dot{width:10px;height:10px;border-radius:50%;background:#e06b5d;animation:pulse 1.6s ease-in-out infinite;flex-shrink:0}
@keyframes pulse{0%,100%{opacity:0.4}50%{opacity:1}}
.disco-head h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--h-champagne);letter-spacing:0.01em}
.disco-count{font-family:'Jost',sans-serif;font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--h-gold-dim)}
.disco-sub{font-size:12.5px;color:var(--h-muted);margin:8px 0 26px;max-width:560px;position:relative;line-height:1.7}

.disco-moods{position:relative;display:flex;flex-wrap:wrap;gap:9px;margin-bottom:26px}
.disco-mood-btn{font-family:'Jost',sans-serif;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--h-muted);background:transparent;border:1px solid var(--h-line);border-radius:100px;padding:8px 18px;cursor:pointer;transition:all 0.2s}
.disco-mood-btn:hover{border-color:var(--h-gold-dim);color:var(--h-champagne)}
.disco-mood-btn.active{border-color:var(--h-gold);background:rgba(207,167,107,0.14);color:var(--h-gold)}
.disco-mood-btn .mood-tally{opacity:0.6;font-size:10px;margin-left:5px}

.disco-player{position:relative;display:flex;flex-direction:column;gap:18px}
.disco-track-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media (max-width:720px){.disco-track-grid{grid-template-columns:1fr}}
.disco-track{display:flex;align-items:center;gap:13px;padding:12px 14px;border:1px solid var(--h-line);border-radius:6px;background:rgba(255,255,255,0.02);cursor:pointer;transition:border-color 0.2s,background 0.2s,transform 0.2s}
.disco-track:hover{border-color:var(--h-gold-dim);transform:translateY(-1px)}
.disco-track.playing{border-color:var(--h-gold);background:rgba(207,167,107,0.09)}
.disco-vinyl{width:38px;height:38px;border-radius:50%;flex-shrink:0;background:radial-gradient(circle at center,#0a0a0c 0 4px,#cfa76b 4px 5px,#1c1416 5px 12px,#0a0a0c 12px 13px,#1c1416 13px 19px);display:flex;align-items:center;justify-content:center;transition:transform 0.6s ease}
.disco-track.playing .disco-vinyl{animation:spin 3.5s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.disco-track-info{display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
.disco-track-title{font-size:13px;color:var(--h-champagne);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.disco-track-meta{display:flex;align-items:center;gap:7px}
.disco-track-artist{font-size:11px;color:var(--h-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.disco-track-tag{font-family:'Jost',sans-serif;font-size:9px;letter-spacing:0.08em;text-transform:uppercase;color:var(--h-gold-dim);border:1px solid rgba(207,167,107,0.3);border-radius:100px;padding:1px 8px;flex-shrink:0}
.disco-play-icon{width:26px;height:26px;border-radius:50%;border:1px solid var(--h-gold-dim);display:flex;align-items:center;justify-content:center;color:var(--h-gold);flex-shrink:0}
.disco-track.playing .disco-play-icon{background:var(--h-gold);color:#0a0a0c;border-color:var(--h-gold)}
.disco-empty{font-size:12.5px;color:var(--h-muted);padding:30px 0;text-align:center}

.disco-pagination{display:flex;align-items:center;justify-content:center;gap:16px;padding-top:4px}
.disco-page-arrow{width:30px;height:30px;border-radius:50%;border:1px solid var(--h-line);background:transparent;color:var(--h-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s}
.disco-page-arrow:hover:not(:disabled){border-color:var(--h-gold);color:var(--h-gold)}
.disco-page-arrow:disabled{opacity:0.3;cursor:default}
.disco-page-nums{display:flex;align-items:center;gap:10px;font-family:'Jost',sans-serif;font-size:11.5px;letter-spacing:0.04em;color:var(--h-muted)}
.disco-page-num{background:none;border:none;color:inherit;cursor:pointer;padding:2px 4px;transition:color 0.2s}
.disco-page-num:hover{color:var(--h-champagne)}
.disco-page-num.active{color:var(--h-gold);font-weight:600}
.disco-page-sep{opacity:0.35}

.disco-now-playing{position:relative;display:flex;align-items:center;gap:14px;border-top:1px solid var(--h-line);padding-top:16px;margin-top:4px}
.disco-now-vinyl{width:30px;height:30px;border-radius:50%;flex-shrink:0;background:radial-gradient(circle at center,#0a0a0c 0 3px,#cfa76b 3px 4px,#1c1416 4px 10px,#0a0a0c 10px 11px,#1c1416 11px 15px)}
.disco-now-vinyl.spinning{animation:spin 3.5s linear infinite}
.disco-now-info{display:flex;flex-direction:column;flex:0 0 auto;min-width:130px}
.disco-now-label{font-family:'Jost',sans-serif;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--h-gold-dim)}
.disco-now-title{font-size:12.5px;color:var(--h-champagne)}
audio.disco-audio{flex:1;height:32px;filter:invert(0.85) opacity(0.85)}

.cart-fab{position:fixed;bottom:26px;right:26px;z-index:300;background:var(--h-gold);color:#0a0a0c;border:none;border-radius:100px;padding:14px 22px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 10px 30px rgba(0,0,0,0.4)}
.cart-drawer{position:fixed;top:0;right:0;height:100vh;width:360px;background:#0c0c0e;border-left:1px solid var(--h-line);z-index:301;transform:translateX(100%);transition:transform 0.35s ease;display:flex;flex-direction:column}
.cart-drawer.open{transform:translateX(0)}
.cart-head{display:flex;justify-content:space-between;align-items:center;padding:20px;border-bottom:1px solid var(--h-line)}
.cart-head h3{font-family:'Cormorant Garamond',serif;color:var(--h-champagne)}
.cart-head button{background:none;border:none;color:var(--h-muted);font-size:20px;cursor:pointer}
.cart-items{flex:1;overflow-y:auto;padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.cart-item{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--h-text);border-bottom:1px solid var(--h-line);padding-bottom:10px}
.cart-remove{background:none;border:none;color:var(--h-bad);cursor:pointer;margin-left:6px}
.cart-empty{color:var(--h-muted);font-size:12.5px}
.cart-foot{padding:18px 20px;border-top:1px solid var(--h-line)}
</style>
</head>
<body>
<?php include_once 'sidebar.php'; ?>
<?php include_once 'topheader.php'; ?>

<section class="page-hero">
    <div class="sec-eyebrow"><a href="hotel-services.php" style="color:var(--h-gold-dim);text-decoration:none">Spa, Shop &amp; Entertainment</a> / Mini Bar</div>
    <h1>Mini Bar &amp; <em>Disco</em></h1>
    <p>Wine and champagne, delivered with optional extras — and a private late-night lounge of your own.</p>
</section>

<div class="tabs" id="suiteTabs">
    <?php foreach ($suites as $slug => $s): ?>
    <button class="tab-btn <?= $slug === $wantedSlug ? 'active' : '' ?>" data-tab="<?= $slug ?>"><?= htmlspecialchars($s['name']) ?></button>
    <?php endforeach; ?>
</div>

<?php foreach ($suites as $slug => $suiteRow): $sid = $suiteRow['id']; ?>
<div class="tab-panel <?= $slug === $wantedSlug ? 'active' : '' ?>" data-panel="<?= $slug ?>">
    <div class="svc-grid">
        <?php foreach (($itemsBySuite[$sid] ?? []) as $s) echo render_minibar_card($s, $tier, $suiteRow['name'], $addonsByService[$s['id']] ?? []); ?>
    </div>
</div>
<?php endforeach; ?>

<section class="disco-section">
    <div class="disco-panel">
        <div class="disco-head">
            <div class="disco-head-left"><span class="disco-dot"></span><h3>The Disco</h3></div>
            <span class="disco-count" id="discoCount"></span>
        </div>
        <p class="disco-sub">A private late-night lounge feature for your suite. Choose a mood, pick a track, and let it play — add your own anytime via the <code>disco_tracks</code> table.</p>

        <div class="disco-player">
            <div class="disco-moods" id="discoMoods"></div>
            <div class="disco-track-grid" id="discoTrackGrid"></div>
            <div class="disco-pagination" id="discoPagination"></div>

            <div class="disco-now-playing" id="discoNowPlaying" style="display:none">
                <div class="disco-now-vinyl" id="discoNowVinyl"></div>
                <div class="disco-now-info">
                    <span class="disco-now-label">Now Spinning</span>
                    <span class="disco-now-title" id="discoNowTitle">—</span>
                </div>
                <audio class="disco-audio" id="discoAudio" controls></audio>
            </div>
        </div>
    </div>
</section>
<script>const DISCO_TRACKS = <?= json_encode($discoTrackPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
const DISCO_MOODS = <?= json_encode($discoMoods, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const DISCO_PAGE_SIZE = <?= (int)$discoPageSize ?>;</script>

<footer><p>© 2026 <span>Nocturne Manila Bay</span> — A Property of AyosCoffeeNegosyo Hospitality.</p></footer>

<button class="cart-fab" id="cartFab">🛎 Order (<span id="cartCount">0</span>)</button>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-head"><h3>Your Order</h3><button id="cartClose">&times;</button></div>
    <div class="cart-items" id="cartItems"><p class="cart-empty">No items yet.</p></div>
    <div class="cart-foot">
        <div class="summary-row total"><span>Total</span><span id="cartTotal">₱0</span></div>
        <?php if (!isHotelLoggedIn()): ?>
        <p style="font-size:12px;color:var(--h-muted);margin:12px 0">
            Please <a href="register.php?next=minibar.php" style="color:var(--h-gold)">create an account</a>
            or <a href="hotel-login.php?next=minibar.php" style="color:var(--h-gold)">log in</a> to complete your order.
        </p>
        <button class="btn-gold" style="width:100%" disabled>Confirm Order</button>
        <?php else: ?>
        <button class="btn-gold" style="width:100%;margin-top:14px" id="cartCheckout">Confirm Order</button>
        <?php endif; ?>
        <div id="cartMsg" style="font-size:12px;margin-top:8px"></div>
    </div>
</div>

<script>
document.querySelectorAll('#suiteTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#suiteTabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.querySelector(`.tab-panel[data-panel="${btn.dataset.tab}"]`).classList.add('active');
        history.replaceState(null, '', `?suite=${btn.dataset.tab}`);
    });
});

(function discoInit() {
    const grid = document.getElementById('discoTrackGrid');
    const moodsBar = document.getElementById('discoMoods');
    const pagination = document.getElementById('discoPagination');
    const countEl = document.getElementById('discoCount');
    if (!grid) return;

    let mood = 'All';
    let page = 1;
    let playingId = null;

    const moodOptions = ['All', ...DISCO_MOODS];

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function tallyFor(m) {
        return m === 'All' ? DISCO_TRACKS.length : DISCO_TRACKS.filter(t => t.mood === m).length;
    }

    function filtered() {
        return mood === 'All' ? DISCO_TRACKS : DISCO_TRACKS.filter(t => t.mood === mood);
    }

    function renderMoods() {
        moodsBar.innerHTML = moodOptions.map(m =>
            `<button type="button" class="disco-mood-btn ${m === mood ? 'active' : ''}" data-mood="${m}">${m}<span class="mood-tally">${tallyFor(m)}</span></button>`
        ).join('');
        moodsBar.querySelectorAll('.disco-mood-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                mood = btn.dataset.mood;
                page = 1;
                renderMoods();
                renderGrid();
            });
        });
    }

    function renderGrid() {
        const list = filtered();
        const totalPages = Math.max(1, Math.ceil(list.length / DISCO_PAGE_SIZE));
        if (page > totalPages) page = totalPages;
        const start = (page - 1) * DISCO_PAGE_SIZE;
        const pageItems = list.slice(start, start + DISCO_PAGE_SIZE);

        countEl.textContent = list.length ? `${list.length} track${list.length === 1 ? '' : 's'}` : '';

        if (!pageItems.length) {
            grid.innerHTML = `<div class="disco-empty" style="grid-column:1/-1">No tracks in this mood yet — insert a row into <code>disco_tracks</code> tagged "${esc(mood)}" to light this up.</div>`;
            pagination.innerHTML = '';
            return;
        }

        grid.innerHTML = pageItems.map(t => `
            <div class="disco-track ${t.id === playingId ? 'playing' : ''}" data-id="${t.id}">
                <div class="disco-vinyl"></div>
                <div class="disco-track-info">
                    <span class="disco-track-title">${esc(t.title)}</span>
                    <div class="disco-track-meta">
                        ${t.artist ? `<span class="disco-track-artist">${esc(t.artist)}</span>` : ''}
                        <span class="disco-track-tag">${esc(t.mood)}</span>
                    </div>
                </div>
                <div class="disco-play-icon">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </div>
            </div>
        `).join('');

        grid.querySelectorAll('.disco-track').forEach(el => {
            el.addEventListener('click', () => {
                const track = DISCO_TRACKS.find(t => t.id === +el.dataset.id);
                if (!track) return;
                playTrack(track);
            });
        });

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) { pagination.innerHTML = ''; return; }
        let nums = '';
        for (let i = 1; i <= totalPages; i++) {
            nums += `<button type="button" class="disco-page-num ${i === page ? 'active' : ''}" data-page="${i}">${String(i).padStart(2, '0')}</button>`;
            if (i < totalPages) nums += '<span class="disco-page-sep">—</span>';
        }
        pagination.innerHTML = `
            <button type="button" class="disco-page-arrow" id="discoPrev" ${page === 1 ? 'disabled' : ''}>‹</button>
            <div class="disco-page-nums">${nums}</div>
            <button type="button" class="disco-page-arrow" id="discoNext" ${page === totalPages ? 'disabled' : ''}>›</button>
        `;
        document.getElementById('discoPrev')?.addEventListener('click', () => { if (page > 1) { page--; renderGrid(); } });
        document.getElementById('discoNext')?.addEventListener('click', () => { if (page < totalPages) { page++; renderGrid(); } });
        pagination.querySelectorAll('.disco-page-num').forEach(btn => {
            btn.addEventListener('click', () => { page = +btn.dataset.page; renderGrid(); });
        });
    }

    function playTrack(track) {
        playingId = track.id;
        const audio = document.getElementById('discoAudio');
        const nowPlaying = document.getElementById('discoNowPlaying');
        const nowVinyl = document.getElementById('discoNowVinyl');
        const nowTitle = document.getElementById('discoNowTitle');
        nowPlaying.style.display = 'flex';
        nowVinyl.classList.add('spinning');
        nowTitle.textContent = track.artist ? `${track.title} — ${track.artist}` : track.title;
        audio.src = track.url;
        audio.play().catch(() => {});
        audio.onpause = () => nowVinyl.classList.remove('spinning');
        audio.onplay = () => nowVinyl.classList.add('spinning');
        renderGrid();
    }

    if (!DISCO_TRACKS.length) {
        grid.innerHTML = '<div class="disco-empty" style="grid-column:1/-1">No tracks added yet — insert rows into <code>disco_tracks</code> with a playable audio URL and a mood category to light this up.</div>';
        moodsBar.innerHTML = '';
        return;
    }

    renderMoods();
    renderGrid();
})();

let cart = [];
function renderCart() {
    const list = document.getElementById('cartItems');
    document.getElementById('cartCount').textContent = cart.length;
    if (!cart.length) { list.innerHTML = '<p class="cart-empty">No items yet.</p>'; document.getElementById('cartTotal').textContent = '₱0'; return; }
    let total = 0;
    list.innerHTML = cart.map((it, i) => {
        const addonsTotal = (it.addons || []).reduce((s, a) => s + a.price, 0);
        const lineTotal = (it.price + addonsTotal) * it.qty;
        total += lineTotal;
        const addonTxt = (it.addons || []).length ? `<br><span style="color:var(--h-gold);font-size:10.5px">+ ${it.addons.map(a=>a.name).join(', ')}</span>` : '';
        return `<div class="cart-item">
            <div><strong>${it.name}</strong><br><span style="color:var(--h-muted);font-size:11px">${it.suite} · ${it.qty} pc(s)</span>${addonTxt}</div>
            <div>₱${lineTotal.toLocaleString('en-PH')} <button type="button" data-i="${i}" class="cart-remove">✕</button></div>
        </div>`;
    }).join('');
    document.getElementById('cartTotal').textContent = '₱' + total.toLocaleString('en-PH');
    list.querySelectorAll('.cart-remove').forEach(b => b.addEventListener('click', () => { cart.splice(+b.dataset.i, 1); renderCart(); }));
}

document.querySelectorAll('.svc-order').forEach(box => {
    const minus = box.querySelector('.qty-minus'), plus = box.querySelector('.qty-plus'), qty = box.querySelector('.svc-qty');
    minus?.addEventListener('click', () => { qty.value = Math.max(1, parseInt(qty.value) - 1); });
    plus?.addEventListener('click', () => { qty.value = Math.min(parseInt(qty.max) || 99, parseInt(qty.value) + 1); });
    box.querySelector('.svc-add').addEventListener('click', () => {
        const addons = Array.from(box.querySelectorAll('.svc-addon:checked')).map(cb => ({
            id: cb.value, name: cb.dataset.name, price: parseFloat(cb.dataset.price)
        }));
        cart.push({
            id: box.dataset.id, name: box.dataset.name, suite: box.dataset.suite,
            price: parseFloat(box.dataset.price), unit: box.dataset.unit, qty: parseInt(qty.value),
            addons,
        });
        renderCart();
        document.getElementById('cartDrawer').classList.add('open');
    });
});

document.getElementById('cartFab').addEventListener('click', () => document.getElementById('cartDrawer').classList.toggle('open'));
document.getElementById('cartClose').addEventListener('click', () => document.getElementById('cartDrawer').classList.remove('open'));

document.getElementById('cartCheckout')?.addEventListener('click', async () => {
    
    if (!cart.length) return;

    const msg = document.getElementById('cartMsg');
    msg.style.color = 'var(--h-muted)'; msg.textContent = 'Processing...';
    
    try {
        
        const r = await fetch('process-service-order.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: cart })
        });

        const data = await r.json();
        
        if (data.ok) {
            msg.style.color = '#7fa876';
            msg.textContent = `Order confirmed — reference ${data.ref}.`;
            cart = []; renderCart();
        
        } else {
            msg.style.color = '#c0574a';
            msg.textContent = data.error || 'Something went wrong.';
        }
        
    } catch (e) { msg.style.color = '#c0574a'; msg.textContent = 'Network error, please try again.'; }
});
</script>
</body>
</html>