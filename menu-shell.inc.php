<?php
/**
 * menu-shell.inc.php — the premium page layout shared by menu.php and reservation-menu.php.
 * The two pages only prepare the data and the $ui settings, then include this file.
 *
 * Needs: $isLoggedIn (bool), $branch (string), $products (array), $categories (array), $ui (array)
 */
$h      = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$isRes  = $ui['mode'] === 'res';
$counts = ['all' => count($products)];
foreach ($products as $p) { $c = (string)($p['category'] ?? ''); $counts[$c] = ($counts[$c] ?? 0) + 1; }
$ico = [
    'flame'  => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.07-2.14-.22-4.05 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.15.43-2.3 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    'sliders' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
    'moped'  => '<circle cx="5.5" cy="17.5" r="2.5"/><circle cx="18.5" cy="17.5" r="2.5"/><path d="M8 17.5h7l3-8h-4"/><path d="M5.5 15l2-6h4"/>',
    'clock'  => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'card'   => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
    'bag'    => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>',
];
$svg = fn($k, $s = 16) => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' . ($ico[$k] ?? '') . '</svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= $h($ui['pageTitle']) ?> — SIPPERÉ Café</title>
<meta name="theme-color" content="#0a0a08">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="menu-premium.css">
</head>
<body>

<div id="sidebarOverlay"></div>
<aside id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sb-header">
        <div class="sb-brand">SIPPERÉ Café<small>Est. 2026</small></div>
        <button class="sb-close" id="sidebarClose" aria-label="Close menu"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <nav class="sb-nav">
        <div class="sb-label">Explore</div>
        <a href="index.php" class="sb-link">Home</a>
        <a href="order-type.php" class="sb-link">Change Order Type</a>
        <a href="menu.php" class="sb-link <?= $isRes ? '' : 'active' ?>">Serve Menu</a>
        <a href="reservation-menu.php" class="sb-link <?= $isRes ? 'active' : '' ?>">Reservation Menu</a>
        <a href="testimonial.php" class="sb-link">Testimonials</a>
        <a href="includes/about-us.php" class="sb-link">About Us</a>
        <div class="sb-label">Branch</div>
        <a href="?branch=laguna" class="sb-link <?= $branch === 'laguna' ? 'active' : '' ?>">Laguna Branch</a>
        <a href="?branch=manila" class="sb-link <?= $branch === 'manila' ? 'active' : '' ?>">Manila Branch</a>
        <div class="sb-label"><?= $isRes ? 'Reservations' : 'Your Order' ?></div>
        <a href="<?= $h($ui['cartUrl']) ?>" class="sb-link"><?= $h($ui['cartName']) ?> <span class="sb-badge" id="sidebarCartCount">0</span></a>
        <?php if ($isLoggedIn): ?>
            <?php if ($isRes): ?><a href="my-reservations.php" class="sb-link">My Reservations</a><a href="reservation-orders.php" class="sb-link">Reservation Orders</a><?php endif; ?>
            <a href="orders.php" class="sb-link">My Orders</a>
            <a href="profile.php" class="sb-link">Profile</a>
            <?php if (!$isRes): ?><a href="dashboard.php" class="sb-link">Dashboard</a><a href="attendance.php" class="sb-link">Attendance</a><?php endif; ?>
        <?php endif; ?>
        <div class="sb-label">Industry</div>
        <a href="includes/hotel-industry.php" class="sb-link">Hotel Business</a>
    </nav>
    <div class="sb-footer">
        <?php if ($isLoggedIn): ?><a href="log-out.php" class="sb-logout">Logout</a>
        <?php else: ?><a href="log-in.php" class="sb-login">Login</a><a href="register.php" class="sb-register">Create Account</a><?php endif; ?>
    </div>
</aside>

<header class="site-header">
    <div class="hd">
        <div class="hd-left">
            <button class="menu-btn" id="sidebarToggle" aria-label="Open menu" aria-expanded="false"><div class="hb"><span></span><span></span><span></span></div></button>
            <nav class="hd-links" aria-label="Primary">
                <a href="index.php">Home</a>
                <a href="<?= $isRes ? 'reservation-menu.php' : 'menu.php' ?>" class="on">Menu</a>
                <a href="order-type.php">Order Type</a>
                <a href="testimonial.php">Stories</a>
            </nav>
        </div>
        <a href="index.php" class="wordmark" aria-label="SIPPERÉ Café home"><b>SIPPER<i>É</i></b><small>Café · Est. 2026</small></a>
        <div class="hd-right">
            <a href="<?= $h($ui['cartUrl']) ?>" class="cart-pill" aria-label="<?= $h($ui['cartName']) ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <?= $isRes ? 'Reservation' : 'Cart' ?> <span class="cnt" id="cartCount">0</span>
            </a>
        </div>
    </div>
</header>

<section class="hero">
    <div class="eyebrow"><?= $h($ui['eyebrow']) ?></div>
    <h1><?= $ui['h1'] /* trusted markup from the page file */ ?></h1>
    <p class="lead"><?= $h($ui['lead']) ?></p>
    <div class="orn"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 7.6L22 12l-7.6 2.4L12 22l-2.4-7.6L2 12l7.6-2.4z"/></svg></div>

    <div class="branch-switch <?= $branch === 'manila' ? 'm' : '' ?>" role="group" aria-label="Choose branch">
        <span class="slider" aria-hidden="true"></span>
        <a href="?branch=laguna" class="<?= $branch === 'laguna' ? 'active' : '' ?>"><span class="pin"></span>Laguna</a>
        <a href="?branch=manila" class="<?= $branch === 'manila' ? 'active' : '' ?>"><span class="pin"></span>Manila</a>
    </div>

    <div class="perks">
        <?php foreach ($ui['perks'] as [$icon, $text]): ?><div class="perk"><?= $svg($icon, 17) ?><?= $h($text) ?></div><?php endforeach; ?>
    </div>
</section>

<?php if (!empty($ui['note'])): ?>
<div class="note-bar"><div><span><?= $ui['note'] ?></span><a href="<?= $h($ui['noteLinkUrl']) ?>"><?= $h($ui['noteLinkText']) ?></a></div></div>
<?php endif; ?>

<div class="toolbar">
    <div class="tb">
        <div class="tabs" id="tabs" role="tablist" aria-label="Menu categories">
        <?php foreach ($categories as $key => $label): $n = $key === 'all' ? $counts['all'] : ($counts[$key] ?? 0); ?>
            <button type="button" class="tab <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= $h($key) ?>" role="tab"><?= $h($label) ?><small><?= $n ?></small></button>
        <?php endforeach; ?>
        </div>
        <label class="search" id="searchWrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
            <input type="search" id="q" placeholder="Search the menu" autocomplete="off" aria-label="Search the menu">
            <button type="button" class="clear" id="qClear" aria-label="Clear search"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </label>
    </div>
</div>

<div class="results"><span id="resCount"></span><span id="resPage"></span></div>

<main class="menu-section">
    <div class="menu-grid" id="menuGrid">
    <?php foreach ($products as $row):
        $stock = (int)($row['stock'] ?? 100); $reorder = (int)($row['reorder_level'] ?? 10);
        $isOos = $stock <= 0; $isLow = !$isOos && $stock <= $reorder;
        $cls = $isOos ? 's-red' : ($isLow ? 's-amber' : 's-green');
        $txt = $isOos ? 'Sold out' : ($isLow ? "Only {$stock} left" : 'Available');
        $cat = (string)($row['category'] ?? 'mains'); $hasSizes = (int)($row['size_count'] ?? 0) > 0;
        $isPkg = $cat === 'packages';
        $desc = !empty($row['description']) ? $row['description'] : 'A signature creation from our kitchen, prepared fresh to order.';
        $search = mb_strtolower($row['name'] . ' ' . $desc . ' ' . $cat);
        $pid = (int)$row['id'];
    ?>
        <article class="card <?= $isOos ? 'oos' : '' ?>" data-category="<?= $h($cat) ?>" data-stock="<?= $stock ?>" data-search="<?= $h($search) ?>">
            <div class="card-media">
                <?php if (!empty($row['image'])): ?>
                    <img src="<?= $h($row['image']) ?>" alt="<?= $h($row['name']) ?>" loading="lazy" onerror="phFallback(this)">
                <?php else: ?><div class="ph"><svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div><?php endif; ?>
                <?php if ($isOos): ?><span class="tag oos">Sold out</span>
                <?php elseif ($isPkg): ?><span class="tag pkg">Package Combo</span>
                <?php elseif ($hasSizes): ?><span class="tag">Choose your size</span><?php endif; ?>
                <?php if (!$isOos): ?>
                <button type="button" class="quick" aria-label="<?= $h($ui['addLabel'] . ' ' . $row['name']) ?>" onclick="<?= $h($ui['addFn']) ?>(<?= $pid ?>, this)">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="cat"><?= $h($isPkg ? 'Packages Combo' : $cat) ?></div>
                <h3><?= $h($row['name']) ?></h3>
                <p class="desc"><?= $h($desc) ?></p>
                <div class="rule"></div>
                <div class="foot">
                    <div class="price"><?php if ($hasSizes): ?><small>From</small><?php endif; ?><sup>₱</sup><?= number_format((float)$row['price'], 2) ?></div>
                    <div class="stock <?= $cls ?>"><i></i><span class="stock-text"><?= $h($txt) ?></span></div>
                </div>
                <?php if ($isOos): ?><button type="button" class="add-link" disabled>Unavailable</button>
                <?php else: ?><button type="button" class="add-link" onclick="<?= $h($ui['addFn']) ?>(<?= $pid ?>, this)"><span class="lbl"><?= $h($ui['addLabel']) ?></span></button><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</main>

<div class="pager" id="pager" style="display:none"><div class="pager-info" id="pagerInfo"></div><div class="pager-row" id="pagerRow"></div></div>

<a href="<?= $h($ui['cartUrl']) ?>" class="cart-float" id="cartFloat" aria-label="<?= $h($ui['cartName']) ?>"><span><?= $isRes ? 'View reservation' : 'View cart' ?></span><b id="cartFloatCount">0</b></a>

<div id="toast" role="status" aria-live="polite"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg><span id="toastMsg"></span></div>

<footer class="site-footer">
    <div class="ft">
        <div>
            <div class="wordmark"><b>SIPPER<i>É</i></b><small>Café · Est. 2026</small></div>
            <p>Carefully crafted coffee and dishes, made with the finest ingredients and served with care in Laguna and Manila.</p>
        </div>
        <div><h4>Explore</h4><ul>
            <li><a href="menu.php">Serve Menu</a></li><li><a href="reservation-menu.php">Reservations</a></li>
            <li><a href="testimonial.php">Testimonials</a></li><li><a href="includes/about-us.php">About Us</a></li></ul></div>
        <div><h4>Visit</h4><ul>
            <li><a href="?branch=laguna">Laguna Branch</a></li><li><a href="?branch=manila">Manila Branch</a></li>
            <li><a href="<?= $isLoggedIn ? 'orders.php' : 'log-in.php' ?>"><?= $isLoggedIn ? 'My Orders' : 'Sign In' ?></a></li></ul></div>
    </div>
    <div class="legal">
        <div>© 2026 <span>SIPPERÉ Café</span> — All rights reserved.</div>
        <div>Developed by <a href="https://www.instagram.com/_theprnx.gvara/" target="_blank" rel="noopener noreferrer">Prince Edward Guevara</a></div>
    </div>
</footer>

<?php $czMode = $ui['mode']; include 'customize-modal.inc.php'; ?>

<script>
var UI = <?= json_encode(['addFn' => $ui['addFn'], 'countUrl' => $ui['countUrl'], 'toastAdd' => $ui['toastAdd'], 'addLabel' => $ui['addLabel']], JSON_UNESCAPED_UNICODE) ?>;
var $ = function(i){return document.getElementById(i)};

/* ---------- drawer ---------- */
var sidebar=$('sidebar'),ovl=$('sidebarOverlay'),tgl=$('sidebarToggle');
function openSidebar(){sidebar.classList.add('open');ovl.classList.add('active');tgl.classList.add('open');tgl.setAttribute('aria-expanded','true');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');ovl.classList.remove('active');tgl.classList.remove('open');tgl.setAttribute('aria-expanded','false');document.body.style.overflow=''}
tgl.addEventListener('click',function(){sidebar.classList.contains('open')?closeSidebar():openSidebar()});
$('sidebarClose').addEventListener('click',closeSidebar);ovl.addEventListener('click',closeSidebar);
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeSidebar()});

/* ---------- image fallback ---------- */
function phFallback(img){var w=img.parentElement;img.remove();var d=document.createElement('div');d.className='ph';d.innerHTML='<svg width="54" height="54" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>';w.insertBefore(d,w.firstChild)}

/* ---------- filter + search + pagination ---------- */
var PER_PAGE=9,page=1,cat='all',query='';
var cards=Array.prototype.slice.call(document.querySelectorAll('.card')),grid=$('menuGrid');
function matches(c){return (cat==='all'||c.dataset.category===cat)&&(!query||c.dataset.search.indexOf(query)!==-1)}
function render(animate){
    var list=cards.filter(matches),total=list.length,pages=Math.max(1,Math.ceil(total/PER_PAGE));
    if(page>pages)page=pages;
    var start=(page-1)*PER_PAGE,slice=list.slice(start,start+PER_PAGE);
    function paint(){
        cards.forEach(function(c){c.classList.remove('visible');c.style.display='none'});
        var old=$('emptyBox');if(old)old.remove();
        slice.forEach(function(c,i){c.style.display='';setTimeout(function(){c.classList.add('visible')},40+i*70)});
        if(!total){var e=document.createElement('div');e.id='emptyBox';e.className='empty';e.innerHTML='<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg><h3>Nothing found</h3><p>'+(query?'Try a different word or clear the search.':'This category is being prepared. Please check back soon.')+'</p>';grid.appendChild(e)}
        grid.classList.remove('fading');
    }
    if(animate){grid.classList.add('fading');setTimeout(paint,260)}else paint();
    $('resCount').textContent=total+(total===1?' selection':' selections');
    $('resPage').textContent=pages>1?('Page '+page+' of '+pages):'';
    drawPager(total,pages,start,start+slice.length);
}
function btn(label,cls,dis,fn){var b=document.createElement('button');b.type='button';b.className='pg '+(cls||'');b.innerHTML=label;b.disabled=!!dis;if(fn)b.addEventListener('click',fn);return b}
function go(n){page=n;render(true);var t=document.querySelector('.toolbar');window.scrollTo({top:t.offsetTop-parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h'))+2,behavior:'smooth'})}
function drawPager(total,pages,from,to){
    var wrap=$('pager'),row=$('pagerRow');
    wrap.style.display=pages<=1?'none':'flex';
    $('pagerInfo').textContent=total?('Showing '+(from+1)+' – '+to+' of '+total):'';
    row.innerHTML='';
    row.appendChild(btn('←','arrow',page===1,function(){go(page-1)}));
    for(var i=1;i<=pages;i++){
        if(pages>7&&i>2&&i<pages-1&&Math.abs(i-page)>1){if(i===3||i===pages-2){var d=document.createElement('span');d.className='dots';d.textContent='…';row.appendChild(d)}continue}
        (function(n){row.appendChild(btn(n,n===page?'active':'',false,function(){go(n)}))})(i);
    }
    row.appendChild(btn('→','arrow',page===pages,function(){go(page+1)}));
}
document.querySelectorAll('.tab').forEach(function(t){t.addEventListener('click',function(){
    document.querySelectorAll('.tab').forEach(function(x){x.classList.remove('active')});
    t.classList.add('active');cat=t.dataset.filter;page=1;render(true);
    t.scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'});
})});
var qEl=$('q'),qWrap=$('searchWrap'),qTimer;
qEl.addEventListener('input',function(){clearTimeout(qTimer);qWrap.classList.toggle('has',!!qEl.value);qTimer=setTimeout(function(){query=qEl.value.trim().toLowerCase();page=1;render(true)},220)});
$('qClear').addEventListener('click',function(){qEl.value='';qWrap.classList.remove('has');query='';page=1;render(true);qEl.focus()});

/* ---------- cart badge, floating cart, toast ---------- */
function setCount(c){
    ['cartCount','sidebarCartCount','cartFloatCount'].forEach(function(id){var e=$(id);if(e){e.textContent=c;if(id==='cartCount'){e.classList.remove('bump');void e.offsetWidth;e.classList.add('bump')}}});
    $('cartFloat').classList.toggle('show',c>0);
}
window.setCartCount=setCount;
fetch(UI.countUrl,{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(d){setCount(d.count||0)}).catch(function(){});
var toastT;
function showToast(msg,isError){var t=$('toast');$('toastMsg').textContent=msg;t.className=isError?'error show':'show';clearTimeout(toastT);toastT=setTimeout(function(){t.classList.remove('show')},2600)}
window.showToast=showToast;

/* ---------- stock display ---------- */
function updateStockUI(card,stock){
    var low=stock<=10&&stock>0,oos=stock<=0,cls=oos?'s-red':(low?'s-amber':'s-green');
    var box=card.querySelector('.stock'),txt=card.querySelector('.stock-text');
    if(box)box.className='stock '+cls;
    if(txt)txt.textContent=oos?'Sold out':(low?'Only '+stock+' left':'Available');
    if(oos){card.classList.add('oos');var m=card.querySelector('.card-media');if(m&&!m.querySelector('.tag.oos')){var t=document.createElement('span');t.className='tag oos';t.textContent='Sold out';m.appendChild(t)}}
}
window.updateStockUI=updateStockUI;

/* ---------- "+" -> customization modal FIRST, cart only after "Add to cart" ---------- */
function addItem(id,btn){
    var card=btn.closest('.card');
    openCustomize(id,{onAdded:function(d){
        setCount(d.count||0);
        var stock=Math.max(0,d.stock);
        card.dataset.stock=stock;updateStockUI(card,stock);
        var q=card.querySelector('.quick'),a=card.querySelector('.add-link');
        if(stock<=0){[q,a].forEach(function(b){if(b)b.disabled=true});if(a)a.textContent='Unavailable'}
        else if(q){q.classList.add('added');q.innerHTML='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';setTimeout(function(){q.classList.remove('added');q.innerHTML='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'},1500)}
        showToast(UI.toastAdd,false);
    }});
}
window[UI.addFn]=addItem;

render(false);
</script>
</body>
</html>