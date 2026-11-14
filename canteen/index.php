<?php
include 'config.php';
require_once __DIR__ . '/includes/school-art.php';   // the CMDI school + canteen illustration

// ── SESSION (re-validated against the database) ───────────────────────────
$user       = getLoggedInUser($conn);
$isLoggedIn = $user !== null;
$firstName  = $isLoggedIn ? ($user['first_name'] ?? '') : '';

// ── LIVE NUMBERS FROM THE DATABASE ────────────────────────────────────────
$stats = ['items' => 0, 'categories' => 0, 'students' => 0];

$r = $conn->query ( " SELECT COUNT(*) AS n, COUNT(DISTINCT category) AS c FROM products WHERE is_available = 1 " );
if ($r && ($row = $r->fetch_assoc())) { $stats['items'] = (int)$row['n']; $stats['categories'] = (int)$row['c']; }

$r = $conn->query ( " SELECT COUNT(*) AS n FROM users WHERE role = 'student' AND is_banned = 0 " );

if ($r && ($row = $r->fetch_assoc())) { 
    
    $stats['students'] = (int)$row['n']; 
    
}


// ── RESERVATION / TRANSACTION COUNTS (final section) ──────────────────────
// One query, one table scan. `reservations.reference` is UNIQUE and every row is one
// submitted reservation, so COUNT(*) cannot double count.
// "Confirmed" = staff accepted it: confirmed, preparing, ready or completed.
// (pending, rejected and cancelled are NOT counted. Narrow the list to 'completed'
//  if you only want orders that were already picked up.)

$resv = ['total' => 0, 'confirmed' => 0];
$r = $conn->query ( " SELECT COUNT(*) AS total, COALESCE(SUM(status IN ('confirmed','preparing','ready','completed')), 0) AS confirmed FROM reservations " );

if ($r && ($row = $r->fetch_assoc())) { $resv['total'] = (int)$row['total']; $resv['confirmed'] = (int)$row['confirmed']; }

// ── FEATURED ITEMS ("Today's offerings") ──────────────────────────────────
$featured = [];
$r = $conn->query (
    
    " SELECT id, name, category, description, price, stock, reorder_level, image
        FROM products
        WHERE is_featured = 1 AND is_available = 1 AND stock > 0
        ORDER BY id ASC LIMIT 6 "
        
);

if ($r) { 
    
    while ($row = $r->fetch_assoc()) $featured[] = $row; 
    
}

// ── CATEGORIES WITH ITEM COUNTS ───────────────────────────────────────────
$categories = [];
$r = $conn->query ( " SELECT category, COUNT(*) AS n FROM products WHERE is_available = 1 GROUP BY category ORDER BY n DESC, category ASC " );

if ($r) { 
    
    while ($row = $r->fetch_assoc()) 
        
        $categories[] = $row; 
        
}

// ── CATEGORY SHOWCASE: four slides (Rice Meals · Snacks · Drinks · Desserts) ──
// MY IMAGES: put one image per category in assets/img/categories/ using these base names.
// .png  .webp  .jpg  .jpeg are all detected automatically. Until a file exists, a labelled placeholder is shown.
// Recommended: 4:5 portrait (about 900 x 1125 px), same framing on all four so the slides line up.

$slideDefs = [

    'rice_meals' => ['numeral' => 'I',   'title' => 'Rice Meals', 'kicker' => 'Hearty & filling',
                     'img'   => 'assets/img/categories/rice-meals',
                     'blurb' => 'The heart of the canteen: home-style viands over steaming rice, served hot so a short break still feels like a proper meal.'],
    'snacks'     => ['numeral' => 'II',  'title' => 'Snacks',     'kicker' => 'Quick bites',
                     'img'   => 'assets/img/categories/snacks',
                     'blurb' => 'Grab-and-go favorites for the short break, from fried classics to warm sandwiches you can eat on the way back to class.'],
    'drinks'     => ['numeral' => 'III', 'title' => 'Drinks',     'kicker' => 'Cold & refreshing',
                     'img'   => 'assets/img/categories/drinks',
                     'blurb' => 'Juices, teas and shakes, served chilled to carry you through the afternoon.'],
    'desserts'   => ['numeral' => 'IV',  'title' => 'Desserts',   'kicker' => 'Sweet endings',
                     'img'   => 'assets/img/categories/desserts',
                     'blurb' => 'Chilled cups and Filipino favorites to finish the meal, small enough for any budget.'],
];

$slides = [];

foreach ($slideDefs as $key => $d) {

    $d['key'] = $key; $d['n'] = 0; 
    $d['min'] = null; $d['items'] = []; 
    $d['src'] = null;

    foreach (['png', 'webp', 'jpg', 'jpeg'] as $ext) {

        if (is_file(__DIR__ . '/' . $d['img'] . '.' . $ext)) {
            
            $d['src'] = $d['img'] . '.' . $ext; break; 
            
        }

    }

    $slides[$key] = $d;

}

foreach ($categories as $c) { 
    
    if (isset($slides[$c['category']])) 
    
        $slides[$c['category']]['n'] = (int)$c['n']; 
        
}

$r = $conn->query ( " SELECT category, name, description, price FROM products WHERE is_available = 1 ORDER BY category, is_featured DESC, id ASC " );

if ($r) {

    while ($row = $r->fetch_assoc()) {

        $k = $row['category'];

        if (!isset($slides[$k])) 
            
        continue;
        $pr = (float)$row['price'];

        if ($slides[$k]['min'] === null || $pr < $slides[$k]['min']) 
            
            $slides[$k]['min'] = $pr;

        if (count($slides[$k]['items']) < 3) 
            
            $slides[$k]['items'][] = $row;

    }

}

$slides = array_values($slides);

// ── TESTIMONIALS (table: personal_testimonials) ───────────────────────────
// `client_location` is shown as the person's role (Student, Teacher, School Staff, ...).
$testimonials = [];
$r = @$conn->query( " SELECT client_name AS name, client_location AS role, avatar_url AS avatar, rating, testimonial AS text FROM personal_testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 12 " );

if ($r) { 
    
    while ($row = $r->fetch_assoc()) 
        $testimonials[] = $row; 
    
}

// SAMPLE TEXT ONLY: shown while personal_testimonials is empty. Replace with real feedback before going live.
$usingSampleTestimonials = empty($testimonials);
if ($usingSampleTestimonials) {

    $testimonials = [
        ['name' => 'Mark Andrei Vertudez',     'role' => 'Student — Strand: ICT (Indonesia)',                       'avatar' => 'assets/img/mark-andrei-vertudez.jpeg', 'rating' => 5, 'text' => 'What I like most is being able to monitor my reservation status. It gives me a better idea of when my order is ready and helps make the entire ordering process more organized.'],
        ['name' => 'Ralf Arenas',       'role' => 'Student — Strand: ICT (Indonesia)',                       'avatar' => 'assets/img/ralf-arenas.jpeg', 'rating' => 5, 'text' => 'What I appreciate about COPS is how everything is presented clearly and professionally. The layout makes it easier to understand the different sections, and I do not have to spend too much time figuring out where to find the information I need.'],
        ['name' => 'Ana Dela Pena',    'role' => 'School Staff',                  'avatar' => '', 'rating' => 5, 'text' => 'I really like the idea of having a digital record of my reservations because it gives me something I can refer to whenever I need to review my orders. It makes the entire experience feel more structured compared to relying entirely on memory.'],
        ['name' => 'Bonggaling Matthew',      'role' => 'Student — Strand: STEM (Thailand)',                 'avatar' => '', 'rating' => 5, 'text' => 'What I find interesting about the system is how it can help students become more aware of their food expenses. Having the opportunity to review available meals and their prices before making a purchase allows us to consider our budget and make more thoughtful decisions.'],
        ['name' => 'Joshua Ramirez',   'role' => 'Student',                       'avatar' => '', 'rating' => 4, 'text' => 'If something is sold out, the menu says so. No more wasted trips across campus.'],
        ['name' => 'Kyla Mendoza',     'role' => 'Student',                       'avatar' => '', 'rating' => 5, 'text' => 'Log in, pick my food, choose a pickup time. It takes less than a minute on my phone.'],
        ['name' => 'Daniel Villanueva','role' => 'Chief Executive Officer (CEO)', 'avatar' => '', 'rating' => 5, 'text' => 'A simple, practical system that respects the students\' time and the canteen\'s workload.'],
        ['name' => 'Carla Aquino',     'role' => 'Teacher',                       'avatar' => '', 'rating' => 4, 'text' => 'Easy for every grade level to use, and the lines are visibly shorter at break.'],
        ['name' => 'Jannel Torres',     'role' => 'Student — Strand: HUMMS (Myanmmar)',                       'avatar' => 'assets/img/jannel-torres.jpeg', 'rating' => 5, 'text' => 'I really appreciate how organized the platform is. Everything is easy to navigate, from browsing the menu to checking my reservations. It makes lunchtime more convenient.'],
    ];

}

// One testimonial card. Clones (for the seamless loop) are hidden from screen readers.
function testimonialCard(array $t, bool $clone): string {

    $name   = htmlspecialchars($t['name'] ?? '');
    $role   = htmlspecialchars($t['role'] ?? '');
    $text   = htmlspecialchars($t['text'] ?? '');
    $rating = max(1, min(5, (int)($t['rating'] ?? 5)));
    $ini    = htmlspecialchars(mb_strtoupper(mb_substr(trim($t['name'] ?? '?'), 0, 1)));
    $av     = !empty($t['avatar']) ? '<img src="' . htmlspecialchars($t['avatar']) . '" alt="" loading="lazy" onerror="this.remove()">' : '';
    $stars  = str_repeat('<i class="on">&#9733;</i>', $rating) . str_repeat('<i>&#9733;</i>', 5 - $rating);

    return '<figure class="t-card"' . ($clone ? ' aria-hidden="true"' : '') . '>'
         . '<span class="t-qm" aria-hidden="true">&ldquo;</span>'
         . '<blockquote class="t-text">' . $text . '</blockquote>'
         . '<figcaption class="t-who">'
         .   '<span class="t-stars" role="img" aria-label="Rated ' . $rating . ' out of 5">' . $stars . '</span>'
         .   '<span class="t-id"><span class="t-av"><b>' . $ini . '</b>' . $av . '</span>'
         .   '<span class="t-meta"><span class="t-name">' . $name . '</span><span class="t-role">' . $role . '</span></span></span>'
         . '</figcaption></figure>';

}

// Two identical halves so translateX(-50%) loops with no jump. The list is repeated until a half is wide enough.
function testimonialTrack(array $list, int $minPerHalf = 8): string {

    if (!$list) return '';

    $reps = max(1, (int)ceil($minPerHalf / count($list)));
    $out = '';

    for ($half = 0; $half < 2; $half++)

        for ($rep = 0; $rep < $reps; $rep++)
            foreach ($list as $t) $out .= testimonialCard($t, $half > 0 || $rep > 0);

    return $out;

}

$cut  = intdiv(count($testimonials), 2);
$row2 = array_merge(array_slice($testimonials, $cut), array_slice($testimonials, 0, $cut));   // second row starts mid-list

// ── CANTEEN QUEUE PHOTO (final section) ───────────────────────────────────
// Drop a photo of students lining up at the canteen into assets/img/ using one of these names.
// Until then, a labelled placeholder is shown.
$queueImg = null;
foreach (['jpg', 'jpeg', 'webp', 'png'] as $ext) {

    if (is_file(__DIR__ . '/assets/img/canteen-queue.' . $ext)) { 
        
        $queueImg = 'assets/img/canteen-queue.' . 
        $ext; break; 
        
    }

}

// Line icons used when a product has no photo yet
function catIcon(string $cat): string {

    $paths = [
        'rice_meals' => '<path d="M3 11h18a9 9 0 0 1-18 0z"/><path d="M8 7c0-1.5 1-1.5 1-3M12 7c0-1.5 1-1.5 1-3M16 7c0-1.5 1-1.5 1-3"/>',
        'snacks'     => '<path d="M4 10a8 5 0 0 1 16 0z"/><path d="M3 14h18"/><path d="M4 18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1H4z"/>',
        'drinks'     => '<path d="M6 8h12l-1.5 12h-9z"/><path d="M12 8V3l4-1"/>',
        'desserts'   => '<path d="M8 11l4 11 4-11"/><path d="M7 11a5 5 0 1 1 10 0z"/>',
    ];

    $p = $paths[$cat] ?? '<circle cx="12" cy="12" r="8"/>';
    return '<svg viewBox="0 0 24 24" width="58" height="58" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>COPS — Canteen Ordering Platform System · CARD-MRI Development Institute</title>
<link rel="icon" href="<?= SCHOOL_LOGO ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="cops-school.css">
<style>

    *,*::before,*::after {
        
        box-sizing:border-box;
        margin:0;
        padding:0

    }

    :root {

        /* White Carrara marble · Aegean navy · burnished gold */
        --bg:#faf8f2;
        --basalt:#f1ece0;
        --card:#ffffff;
        --border:#e5dcc6;
        --gold:#b08d3c;
        --gold-bright:#7a5c19;
        --gold-dim:#c9b373;
        --gold-deep:#8a6a1f;
        --navy:#13294b;
        --navy-2:#1f3d6e;
        --champagne:#f5ecd2;
        --text:#1c1f2a;
        --muted:#5b6070;
        --serif:'Cinzel',serif;
        --body:'EB Garamond',Georgia,serif;
        --ease:cubic-bezier(.2,.8,.2,1);

    }

    html {
        
        scroll-behavior:smooth
    
    }

    body {
        
        background:var(--bg);
        color:var(--text);
        font-family:var(--body);
        font-size:18px;
        line-height:1.65;
        min-height:100vh;
        overflow-x:hidden;
        -webkit-font-smoothing:antialiased;
        text-rendering:optimizeLegibility
    
    }

/* Carrara marble: grey-gold veining under a warm vignette */
    body::before {
        
        content:'';
        position:fixed;
        inset:0;
        pointer-events:none;
        z-index:-1;
        opacity:.55;

        background:

            radial-gradient(ellipse 80% 55% at 12% 0%,rgba(217,187,104,.18),transparent 60%),
            radial-gradient(ellipse 60% 60% at 100% 100%,rgba(44,90,160,.09),transparent 60%),
            url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='1000' height='1000'><filter id='v' x='0' y='0' width='100%25' height='100%25'><feTurbulence type='turbulence' baseFrequency='0.0022 0.0075' numOctaves='6' seed='11'/><feColorMatrix values='0 0 0 0 .5  0 0 0 0 .46  0 0 0 0 .4  -10 0 0 0 1.1'/></filter><rect width='100%25' height='100%25' filter='url(%23v)'/></svg>");

        background-size:auto,auto,1000px 1000px
    
    }

    a {
        
        color:inherit
    
    }

    :focus-visible {
        
        outline:2px solid var(--gold-bright);
        outline-offset:3px
    
    }

    .wrap {
        
        position:relative;z-index:1
    
    }

/* ornament primitives */
    .meander {
        
        height:16px;
        background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16'><path d='M0 13H16M13 13V3H3V10H10V6' fill='none' stroke='%23b08d3c' stroke-width='1.3'/></svg>") repeat-x center;
        opacity:.85

    }
    .dentil {
        
        height:9px;
        border-top:1px solid var(--gold-dim);
        background:repeating-linear-gradient(90deg,var(--gold-dim) 0 7px,transparent 7px 14px)
    
    }

/* header */
    .top-strip {
        
        position:relative;
        z-index:101;
        background:linear-gradient(180deg,var(--navy-2),var(--navy));
        border-bottom:2px solid var(--gold);
        text-align:center;
        padding:8px 16px;
        font-family:var(--serif);
        font-size:11px;
        letter-spacing:.3em;
        color:#f0dca0
    
    }

    header {
        
        position:sticky;
        top:0;
        z-index:100;
        background:rgba(255,255,255,.82);
        backdrop-filter:blur(14px);
        -webkit-backdrop-filter:blur(14px);
        border-bottom:1px solid transparent;
        transition:background .3s,border-color .3s,box-shadow .3s
    
    }

    header.scrolled {
        
        background:rgba(255,255,255,.96);
        border-bottom-color:var(--border);
        box-shadow:0 10px 30px -22px rgba(19,41,75,.4)
    
    }

    .header-inner {
        
        max-width:1200px;
        margin:0 auto;
        padding:0 28px;
        height:78px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:20px
    }

    .brand {
        
        display:flex;
        align-items:center;
        gap:13px;
        text-decoration:none
    
    }

    .brand-logo {
        
        width:50px;
        height:50px;
        border-radius:50%;
        background:#fff;
        padding:3px;
        box-shadow:0 0 0 2px var(--gold),0 8px 16px -8px rgba(19,41,75,.55);
        transition:transform .5s var(--ease)
    
    }

    .brand:hover .brand-logo {
        
        transform:rotate(-8deg) scale(1.05)
    
    }

    .brand-logo img {
        
        display:block;
        width:100%;
        height:100%;
        border-radius:50%;
        object-fit:cover
    
    }

    .brand-name {
        
        font-family:var(--serif);
        font-size:22px;
        font-weight:700;
        letter-spacing:.2em;
        color:var(--navy);
        line-height:1.1
    
    }

    .brand-name small {
        
        display:block;
        font-family:var(--body);
        font-style:italic;
        font-weight:400;
        font-size:13px;
        letter-spacing:0;color:var(--gold-bright)
    }
    
    nav {
        
        display:flex;
        align-items:center;
        gap:6px
    }

    nav a {
        
        font-family:var(--serif);
        font-size:12px;
        font-weight:500;
        letter-spacing:.14em;
        color:var(--muted);
        text-decoration:none;
        padding:9px 13px;
        position:relative;
        transition:color .2s
    }

    nav a:not(.cta)::after {
        
        content:'';
        position:absolute;
        left:13px;
        right:13px;
        bottom:4px;
        height:1px;
        background:var(--gold);
        transform:scaleX(0);
        transform-origin:left;
        transition:transform .3s var(--ease)
    }

    nav a:not(.cta):hover {
        
        color:var(--navy)
    }

    nav a:not(.cta):hover::after {
        
    transform:scaleX(1)
    
    }

    nav a.active {
        
        color:var(--gold-bright)
    
    }

    nav .hello {
        
        font-style:italic;
        font-family:var(--body);
        font-size:16px;
        color:var(--gold-bright);
        padding:0 10px
    
    }

    nav a.cta {
        
        margin-left:8px;
        padding:11px 22px;
        color:#1a1400;
        font-weight:700;
        background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);
        border:1px solid #d9b45a;
        box-shadow:0 10px 20px -12px rgba(138,106,31,.8);
        transition:filter .2s,transform .2s
    }

    nav a.cta:hover {
        
        filter:brightness(1.08);
        transform:translateY(-2px)
    
    }
    @media (max-width:860px) {
        
        nav a:not(.cta) :not(.keep),nav .hello {
        
            display:none
    
        }
    
        .brand-name small {
        
            display:none
    
        }

    }

/* buttons */
    .btn {
    
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:15px 30px;
        font-family:var(--serif);
        font-size:13px;
        font-weight:700;
        letter-spacing:.16em;
        text-decoration:none;
        transition:filter .2s,transform .25s var(--ease),border-color .2s,color .2s,background .2s}
    .btn-gold {
        
        color:#1a1400;
        background:linear-gradient(180deg,#f0d78e,#c9a24a 55%,#a9822c);
        border:1px solid #d9b45a;
        box-shadow:0 16px 28px -16px rgba(138,106,31,.85)
    
    }

    .btn-gold:hover {
        
        filter:brightness(1.07);
        transform:translateY(-3px)
    
    }

    .btn-ghost {
        
        color:var(--navy);border:1px solid var(--gold-dim);
        background:rgba(255,255,255,.6)
    
    }

    .btn-ghost:hover {
        
        color:var(--gold-bright);
        border-color:var(--gold);
        background:#fff;transform:translateY(-3px)
    
    }

/* hero */
    .hero {
        
        max-width:1240px;
        margin:0 auto;
        padding:64px 28px 40px;
        display:grid;
        grid-template-columns:.92fr 1.08fr;
        gap:52px;
        align-items:center
    
    }

    .hero-kicker {
        
        font-family:var(--serif);
        font-size:12px;
        letter-spacing:.24em;
        color:var(--gold-bright);
        margin-bottom:22px;
        display:flex;
        align-items:center;
        gap:14px
    
    }

    .hero-kicker::before {
        
        content:'';
        width:36px;height:1px;
        background:var(--gold)
    
    }

    .hero h1 {
        
        font-family:var(--serif);
        font-size:clamp(32px,4.2vw,56px);
        font-weight:600;
        line-height:1.16;
        color:var(--navy);
        letter-spacing:.01em;
        margin-bottom:14px
    
    }

    .hero h1 em {
        
        font-style:normal;
        background:linear-gradient(180deg,#e2c06c,#b8903a 50%,#8a6a1f);
        -webkit-background-clip:text;
        background-clip:text;
        -webkit-text-fill-color:transparent;
        color:transparent
    
    }

    .hero .latin {
        
        font-style:italic;
        font-size:22px;
        color:var(--gold-bright);
        margin-bottom:20px
    
    }

    .hero .lede {
        
        max-width:46ch;
        font-size:19px;
        color:var(--muted);
        margin-bottom:32px
    
    }

    .hero-ctas {
        
        display:flex;
        gap:14px;
        flex-wrap:wrap
    
    }

    .hero-stats {
        
        display:flex;margin-top:42px;
        border-top:1px solid var(--gold-dim);
        border-bottom:1px solid var(--gold-dim);
        max-width:520px;
        background:rgba(255,255,255,.55)
    
    }

    .hero-stats>div {
        
        flex:1;padding:18px 18px;
        border-right:1px solid var(--border)
    
    }

    .hero-stats>div:first-child {
        
        padding-left:4px
    
    }

    .hero-stats>div:last-child {
        
        border-right:none
    
    }

    .stat-n {
        
        font-family:var(--serif);font-size:32px;font-weight:600;color:var(--gold-bright);line-height:1.1
    
    }

    .stat-l {
        
        font-size:15px;color:var(--muted);line-height:1.3;margin-top:4px
    
    }

    .hero-scene {
        
        animation:sceneIn 1.3s var(--ease) .15s both
    
    }

    .hero-scene .scene-frame {
        
        margin-bottom:56px
    
    }
    @keyframes sceneIn {
        
        from {
            
            opacity:0;
            transform:translateY(26px) scale(.97)
        
        }
        
        to {
            
            opacity:1;
            transform:none
        
        }
    
    }

    .hero-copy>* {
        
        animation:fadeUp .9s var(--ease) both
    
    }

    .hero-copy>*:nth-child(1) {
        
        animation-delay:.05s
    
    }
    
    .hero-copy>*:nth-child(2) {
        
        animation-delay:.15s
    
        }
        
    .hero-copy>*:nth-child(3) {
        
        animation-delay:.25s
    }

    .hero-copy>*:nth-child(4) {
        
        animation-delay:.35s
    
    }
        
    .hero-copy>*:nth-child(5) {
        
        animation-delay:.45s
    
    }
    
    .hero-copy>*:nth-child(6) {
        
        animation-delay:.55s
    
    }

    @keyframes fadeUp {
        
        from {
            
            opacity:0;
            transform:translateY(18px)
        
        }
        
        to {
            
            opacity:1;
            transform:none
        
        }
    
    }

    @media(max-width:1000px) {
        
        .hero {
            
            grid-template-columns:1fr;
            padding-top:44px
        
        }
        
        .hero-scene {
            
            max-width:680px;
            margin:0 auto;
            width:100%
        
        }
    
    }

/* trust strip */
    .strip {
        
        background:linear-gradient(180deg,#fff,var(--champagne));
        border-top:1px solid var(--gold-dim);
        border-bottom:1px solid var(--gold-dim)
    }

    .strip-inner {
        
        max-width:1200px;
        margin:0 auto;
        padding:16px 28px;
        display:flex;
        justify-content:center;
        flex-wrap:wrap;
        gap:12px 34px;
        font-family:var(--serif);
        font-size:12px;
        letter-spacing:.2em;
        color:var(--gold-bright)
    }

    .strip-inner span {
        
        display:inline-flex;
        align-items:center;
        gap:12px
    }

    .strip-inner span + span::before {
        
        content:'';
        width:5px;
        height:5px;
        background:var(--gold);
        transform:rotate(45deg);
        opacity:.9
    
    }

/* sections */
    .section {
        
        max-width:1200px;
        margin:0 auto;
        padding:92px 28px
    
    }

    .sec-hd {
        
        text-align:center;
        max-width:620px;
        margin:0 auto 52px
    
    }

    .sec-hd h2 {
        
        font-family:var(--serif);
        font-size:clamp(26px,3.4vw,38px);
        font-weight:600;
        color:var(--navy);
        letter-spacing:.03em;
        line-height:1.25
    }

    .sec-hd p {

        margin-top:14px;
        color:var(--muted);
        font-size:18px

    }

    .orn {

        display:flex;
        align-items:center;
        justify-content:center;
        gap:12px;
        margin-top:20px

    }
    .orn span {

        width:70px;
        height:1px;
        background:linear-gradient(90deg,transparent,var(--gold))
    }

    .orn span:last-child {

        transform:scaleX(-1)

    }

    .orn svg {

        width:32px;
        height:32px;
        flex:none
    }

/* featured: arch-topped niches */
    .grid-3 {
        
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:26px
    
    }

    .item {
        
        background:linear-gradient(180deg,#fff,#fbf9f3);
        border:1px solid var(--border);
        padding:12px 12px 0;
        box-shadow:0 24px 50px -30px rgba(19,41,75,.45),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.3);
        display:flex;flex-direction:column;
        transition:border-color .3s,transform .3s var(--ease),box-shadow .3s
    
    }

    .item:hover {
        
        border-color:var(--gold-dim);
        transform:translateY(-6px);
        box-shadow:0 34px 56px -30px rgba(19,41,75,.55),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.5)
    
    }

    .item-media {
        
        position:relative;
        aspect-ratio:1/.8;
        margin:8px;
        border:1px solid var(--gold-dim);
        border-radius:999px 999px 2px 2px;
        overflow:hidden;background:linear-gradient(160deg,#f7f1e1,#efe6cf);
        display:flex;
        align-items:center;
        justify-content:center;
        color:var(--gold);
        box-shadow:inset 0 10px 24px -12px rgba(19,41,75,.35)
    
    }

    .item-media img {
        
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        object-fit:cover
    
    }

    .item-media::after {
        
        content:'';
        position:absolute;
        inset:0;
        background:linear-gradient(180deg,transparent 60%,rgba(19,41,75,.24));
        pointer-events:none
    
    }

    .item-body {
        
        padding:16px 16px 22px;
        text-align:center;
        display:flex;
        flex-direction:column;flex:1
    
    }

    .item-cat {
        
        font-size:15px;
        font-style:italic;
        color:var(--gold-bright)
    
    }

    .item-name {
        
        font-family:var(--serif);
        font-size:19px;
        font-weight:600;
        color:var(--navy);
        margin:4px 0 8px;
        letter-spacing:.03em
    
    }

    .item-desc {
        
        font-size:16px;color:var(--muted);
        line-height:1.5;
        flex:1;
        margin-bottom:16px
    
    }

    .item-foot {
        
        display:flex;
        justify-content:space-between;
        align-items:center;
        padding-top:14px;
        border-top:1px solid var(--border)
    
    }

    .price {
        
        font-family:var(--serif);
        font-size:20px;
        font-weight:600;
        color:var(--navy)
    
    }

    .price small {
        
        font-size:13px;
        color:var(--gold);
        margin-right:2px
    
    }

    .stock {
        
        font-size:15px;color:#2f7d4f
    
    }

    .stock.low {
        
        color:#b7791f
    
    }

    .empty {
        
        grid-column:1/-1;
        text-align:center;
        padding:48px 20px;
        border:1px dashed var(--gold-dim);
        color:var(--muted);
        background:rgba(255,255,255,.6)
    
    }

    @media(max-width:960px) {
        
        .grid-3 {
            
            grid-template-columns:1fr 1fr
        
        }
    
    }

    @media(max-width:620px) {
        
        .grid-3 {
            
            grid-template-columns:1fr;
            max-width:400px;
            margin-inline:auto
        
        }
    
    }

/* ── shared classical pieces: fluted columns (capital + shaft + base), framed images, reveal ── */
    .temple-sc,.skip-temple {
    
        --pw:clamp(34px,3.6vw,50px);
        --po:clamp(10px,1.4vw,24px);
        --pt:0px;
        --pb:10px

    }

    .temple-sc {
        
        --pt:30px

    }

    .sr-only {
        
        position:absolute;
        width:1px;
        height:1px;
        overflow:hidden;
        clip:rect(0 0 0 0);
        white-space:nowrap
    
    }

    .pil {
        
        display:none

    }

    @media(min-width:1100px) {

    /* columns stand between the top frieze and the bottom step, inside the temple, never outside it */
        .pil {
            
            display:block;
            position:absolute;
            top:var(--pt);
            bottom:var(--pb);
            width:var(--pw);
            z-index:1;
            pointer-events:none;

            background:repeating-linear-gradient(90deg,rgba(90,74,40,.11) 0 2px,transparent 2px 9px),
                    linear-gradient(90deg,rgba(120,104,70,.26),rgba(255,255,255,.92) 36%,rgba(255,255,255,.96) 52%,rgba(215,205,180,.7) 78%,rgba(104,90,56,.3));
            border-left:1px solid var(--gold-dim);border-right:1px solid var(--gold-dim)
        
        }

        .pil::before,.pil::after {
            
            content:'';
            position:absolute;
            left:-6px;
            right:-6px;
            height:16px;
            background:linear-gradient(180deg,#fff,#d6ccb2)
        
        }

        .pil::after{
            
            top:0;
            border-bottom:2px solid var(--gold)
        
        }      /* capital */

        .pil::before {
            
            bottom:0;
            border-top:2px solid var(--gold)
        
        }     /* base */

        .pil.l {
            
            left:var(--po)
        
        }
        
        .pil.r {
            
            right:var(--po)
        
        }

    }

    .art-frame {
        
        position:relative;
        aspect-ratio:4/5;
        overflow:hidden;
        border:1px solid var(--gold);
        border-radius:999px 999px 4px 4px;
        background:linear-gradient(160deg,#f7f1e1,#efe6cf);
        box-shadow:0 0 0 6px #fff,0 0 0 7px rgba(176,141,60,.5),0 44px 50px -34px rgba(19,41,75,.6)
    
    }

    .art-frame > img {
        
        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        object-fit:cover;
        object-position:center

    }

    .art-ph {
        
        position:absolute;
        inset:14px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:7px;
        text-align:center;
        padding:24px 20px 18px;
        border:1.5px dashed var(--gold);
        border-radius:999px 999px 2px 2px;
        color:var(--gold-bright)
    
    }

    .art-ph svg {
        
        width:42px;
        height:42px;
        flex:none
    
    }

    .art-ph b {
        
        font-family:var(--serif);
        font-size:12px;
        font-weight:600;
        letter-spacing:.16em
    
    }

    .art-ph code {
        
        font-family:ui-monospace,Consolas,monospace;
        font-size:12.5px;
        line-height:1.35;
        color:var(--navy);
        background:rgba(255,255,255,.85);
        padding:3px 7px;
        word-break:break-all
    
    }

    .art-ph small {
        
        font-size:14.5px;
        line-height:1.3;
        color:var(--muted);
        max-width:24ch

    }

    .js .rv {
        
        opacity:0;
        transform:translateY(22px);
        transition:opacity .9s var(--ease),transform .9s var(--ease)
    
    }

    .js .rv.in {
        
        opacity:1;
        transform:none
    
    }

    .btn-navy {
        
        color:#fff;
        background:linear-gradient(180deg,var(--navy-2),var(--navy));
        border:1px solid var(--navy);
        box-shadow:0 14px 24px -16px rgba(19,41,75,.8)

    }

    .btn-navy:hover {
        
        filter:brightness(1.12);
        transform:translateY(-3px)
    
    }

/* ── categories: one full-width temple, four slides ── */
    .section.wide {
        
        max-width:none;
        padding-left:0;
        padding-right:0
    
    }

    .section.wide .sec-hd {
        
        padding:0 28px
    
    }

    .temple-sc {
        
        --ts:clamp(34px,8.2vw,100px);
        position:relative;
        overflow:hidden;
        max-width:1920px;
        margin:0 auto;
        border:1px solid var(--gold-dim);
        border-left:0;
        border-right:0;
        background:radial-gradient(ellipse 40% 52% at 50% 62%,rgba(255,255,255,.95),transparent 72%),radial-gradient(ellipse 50% 50% at 12% 0%,rgba(217,187,104,.2),transparent 65%),linear-gradient(180deg,#fffefb,#f6efdc);
        box-shadow:inset 0 0 0 5px #fff,inset 0 0 0 6px rgba(176,141,60,.35),0 30px 56px -36px rgba(19,41,75,.5)
    
    }

    .tp-frieze {
        
        height:30px;
        padding-top:7px;
        border-bottom:1px solid var(--gold-dim)
    
    }

    .tp-body {
        
        position:relative;
        max-width:1280px;
        margin:0 auto;
        padding:0 18px

    }

    @media(min-width:1100px) {
        
        .tp-body {
            
            padding:0 calc(var(--pw) + var(--po) + 30px)
        
        }
    
    }

    .sc-viewport {
        
        overflow:hidden;
        touch-action:pan-y
    
    }

    .sc-track {
        
        display:flex;
        transition:transform .95s cubic-bezier(.65,0,.2,1);
        will-change:transform
    
    }

    .slide {
        
        flex:0 0 100%;
        min-width:0;
        display:grid;
        grid-template-columns:minmax(0,1fr) minmax(250px,320px) minmax(0,1fr);
        grid-template-areas:"hd hd hd" "cp ar cd";
        column-gap:clamp(24px,3vw,48px);
        align-items:center;padding:30px 0 40px
    
    }

    .sl-head {
        
        grid-area:hd;
        text-align:center;
        position:relative;
        z-index:1
    
    }

    .sl-kicker {
        
        font-family:var(--serif);
        font-size:12px;
        font-weight:600;
        letter-spacing:.3em;
        text-transform:uppercase;
        color:var(--gold-bright)
    
    }

    .sl-title {
        
        font-family:var(--serif);
        font-size:var(--ts);
        font-weight:700;
        line-height:1.02;
        letter-spacing:.015em;
        text-transform:uppercase;
        white-space:nowrap;
        color:var(--navy);
        margin-top:6px
    
    }

    .sl-art {
        
        grid-area:ar;
        position:relative;
        z-index:2;
        margin-top:calc(var(--ts) * -.24)
    
    }

    .sl-art .art-frame > img {
        
        transform:scale(1.07);
        transition:transform 1.5s var(--ease)
    
    }

    .slide.is-active .sl-art .art-frame > img {
        
        transform:none
    
    }
/* text hugs the arch on the left, dish cards hug it on the right: a symmetrical composition */

    .sl-copy {
        
        grid-area:cp;
        justify-self:end;
        width:100%;
        max-width:340px;
        min-width:0
    
    }

    .sl-copy p {
        
        font-size:17.5px;
        line-height:1.6;
        color:var(--muted);
        margin-bottom:12px
    
    }

    .sl-meta {
        
        font-style:italic;
        font-size:16px;
        color:var(--gold-bright);
        margin-bottom:20px

    }

    .sl-cards {
        
        grid-area:cd;
        justify-self:start;
        width:100%;
        max-width:340px;
        min-width:0;
        display:grid;
        grid-template-columns:minmax(0,1fr);
        gap:12px

    }

    .fcard {
        
        min-width:0;
        display:flex;
        align-items:center;
        gap:13px;
        padding:12px 14px;
        text-decoration:none;
        background:#fff;
        border:1px solid var(--border);
        box-shadow:0 14px 26px -22px rgba(19,41,75,.5),inset 0 0 0 3px #fff,inset 0 0 0 4px rgba(176,141,60,.22);
        transition:transform .35s var(--ease),border-color .3s,box-shadow .3s
    
    }
        
    .fic {
        
        flex:none;
        width:40px;
        height:40px;
        display:grid;
        place-items:center;
        color:#fff;
        background:linear-gradient(180deg,#d1ac55,#8a6a1f)
    
    }

    .fic svg {
        
        width:22px;
        height:22px;
        stroke-width:1.6
    
    }

    .fbody {
        
        flex:1;
        min-width:0;
        display:flex;
        flex-direction:column;
        line-height:1.3
    
    }

    .fbody b {
        
        font-family:var(--serif);
        font-size:14.5px;
        font-weight:600;
        letter-spacing:.03em;
        color:var(--navy);
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis
    
    }

    .fbody small {
        
        font-size:15px;
        color:var(--muted);
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        overflow:hidden
    
    }

    .fp {
            
        flex:none;font-family:var(--serif);
        font-size:14.5px;
        font-weight:600;
        color:var(--gold-bright);
        white-space:nowrap
    
    }

    .sl-empty {
        
        font-style:italic;
        color:var(--muted)

    }

/* each slide's content settles in once it becomes the active one; nothing moves layout */
    .sl-head,.sl-art,.sl-copy,.fcard {
        
        opacity:0;
        transform:translateY(16px);
        transition:opacity .5s ease,transform .5s ease
    
    }

    .slide.is-active .sl-head,.slide.is-active .sl-art,.slide.is-active .sl-copy,.slide.is-active .fcard {
        
        opacity:1;
        transform:none;
        transition:opacity .8s ease var(--d,.2s),transform .8s var(--ease) var(--d,.2s)
    
    }

    .slide.is-active .sl-art {
        
        --d:.3s
    
    } 
    
    .slide.is-active .sl-copy {
        
        --d:.42s
    
    }

    .slide.is-active .fcard:nth-child(1) {
        
        --d:.45s
    
    }
    
    .slide.is-active .fcard:nth-child(2) {
        
        --d:.55s
    
    }
    
    .slide.is-active .fcard:nth-child(3) {
        
        --d:.65s}

    .slide.is-active .fcard:hover {
        
        transform:translateX(-5px);
        border-color:var(--gold-dim);
        transition-delay:0s;
        box-shadow:0 20px 32px -22px rgba(19,41,75,.55),inset 0 0 0 3px #fff,inset 0 0 0 4px rgba(176,141,60,.5)
    
    }

    .sc-plinth {
        
        display:flex;
        align-items:center;
        justify-content:center;
        gap:14px;
        padding:6px 20px 26px
    
    }

    .sc-arrow {
        
        flex:none;
        width:44px;
        height:44px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        color:var(--navy);
        cursor:pointer;
        background:linear-gradient(180deg,#fff,#fbf9f3);
        border:1px solid #d4c69f;box-shadow:inset 0 0 0 2px #fff,inset 0 0 0 3px rgba(176,141,60,.25);
        transition:transform .3s var(--ease),border-color .25s,box-shadow .3s
    
    }

    .sc-arrow:hover {
        
        transform:translateY(-2px);
        border-color:var(--gold);
        box-shadow:0 10px 16px -12px rgba(138,106,31,.8)
    
    }

    .sc-arrow svg {
        
        width:18px;
        height:18px

    }

    .sc-tabs {
        
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        justify-content:center
    
    }

    .sc-tab {
        
        position:relative;
        display:inline-flex;
        align-items:center;
        gap:10px;
        padding:11px 18px;
        cursor:pointer;
        font-family:var(--serif);
        font-size:11.5px;
        font-weight:600;
        letter-spacing:.12em;
        text-transform:uppercase;
        color:var(--muted);
        background:linear-gradient(180deg,#fff,#fbf9f3);
        border:1px solid #d4c69f;
        box-shadow:inset 0 0 0 2px #fff,inset 0 0 0 3px rgba(176,141,60,.2);
        transition:transform .3s var(--ease),border-color .25s,color .25s,background .35s,box-shadow .3s
    
    }

    .sc-tab b {
        
        font-weight:600;
        font-size:12px;
        color:var(--gold-bright);
        transition:color .25s
    
    }
    
    .sc-tab::after {
        
        content:'';
        position:absolute;
        left:14px;
        right:14px;
        bottom:-1px;
        height:2px;
        background:var(--gold);
        transform:scaleX(0);
        transition:transform .45s var(--ease)

    }
    
    .sc-tab:hover {
        
        transform:translateY(-2px);
        border-color:var(--gold);
        color:var(--navy);
        box-shadow:0 10px 16px -12px rgba(138,106,31,.8),inset 0 0 0 2px #fff,inset 0 0 0 3px rgba(176,141,60,.35)

    }
    
    .sc-tab:hover::after {
        
        transform:scaleX(.5)

    }

    .sc-tab[aria-selected="true"] {
        
        color:#fff;
        border-color:var(--navy);
        background:linear-gradient(180deg,var(--navy-2),var(--navy));
        box-shadow:0 12px 20px -14px rgba(19,41,75,.85)}

    .sc-tab[aria-selected="true"] b {
        
        color:#f0dca0

    }

    .sc-tab[aria-selected="true"]::after {
        
        transform:scaleX(1);
        background:#d9b45a

    }

    .tp-base {
        
        height:10px;
        border-top:2px solid var(--gold);
        background:repeating-linear-gradient(90deg,var(--gold-dim) 0 7px,transparent 7px 14px)

    }


    @media(max-width:1099px) {

        .temple-sc {--ts:clamp(34px,11.5vw,92px)
    
        }
        
        .slide {
            
            grid-template-columns:minmax(0,1fr);
            grid-template-areas:"hd" "ar" "cp" "cd";
            row-gap:22px;
            justify-items:center;
            padding:26px 0 34px
    
        }
        
        .sl-art {
            
            width:min(300px,74%);
            margin-top:calc(var(--ts) * -.2)
    
        }
        
        .sl-copy,.sl-cards {
            
            justify-self:center;
            max-width:520px
    
        }
        
        .sl-copy {
            
            text-align:center
    
        }

    }

    @media(max-width:620px) {

        .sc-arrow {
            
            display:none
        
        }

        .sc-plinth {
            
            padding:4px 14px 22px
        
        }

        .sc-tabs {
            
            display:grid;
            grid-template-columns:1fr 1fr;
            width:100%;
            max-width:420px
        
        }

        .sc-tab {
            
            justify-content:center;
            padding:11px 10px
        
        }


    }

/* how it works */
    .steps {
        
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:0

    }

    .step {
        
        text-align:center;
        padding:10px 34px 14px;
        position:relative

    }

    .step + .step::before {
        
        content:'';
        position:absolute;
        left:0;
        top:10px;
        bottom:10px;
        width:1px;
        background:linear-gradient(180deg,transparent,var(--gold-dim),transparent)

    }

    .step-n {
        
        width:64px;
        height:64px;
        margin:0 auto 18px;
        border:1px solid var(--gold);
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        font-family:var(--serif);
        font-size:20px;
        font-weight:600;
        color:var(--gold-bright);
        background:#fff;
        box-shadow:inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.4),0 8px 18px -10px rgba(19,41,75,.35)

    }
    
    .step h3 {
        
        font-family:var(--serif);
        font-size:20px;
        font-weight:600;
        color:var(--navy);
        letter-spacing:.05em

    }

    .step .la {
        
        font-style:italic;
        color:var(--gold-bright);
        margin:2px 0 10px;
        font-size:18px

    }

    .step p {
        
        color:var(--muted);
        font-size:17px;
        max-width:30ch;
        margin:0 auto

    }

    @media(max-width:800px) {
        
        .steps {
            
            grid-template-columns:1fr;
            gap:34px
        
        }
        
        .step + .step::before {
            
            display:none
        
        }

    }

/* why COPS band */
    .band {
        
        background:radial-gradient(ellipse 70% 80% at 85% 20%,rgba(217,187,104,.2),transparent 60%),linear-gradient(180deg,#fff,var(--basalt));
        border-top:1px solid var(--gold-dim);
        border-bottom:1px solid var(--gold-dim)
    
    }

    .band .frieze {
        
        padding:10px 0 4px

    }

    .why {

        display:grid;
        grid-template-columns:1fr 1fr;
        gap:34px 56px

    }

    .why-item {

        border-left:2px solid var(--gold);
        padding:2px 0 2px 22px

    }

    .why-item h3 {

        font-family:var(--serif);
        font-size:18px;
        font-weight:600;
        color:var(--navy);
        letter-spacing:.05em;
        margin-bottom:6px

    }

    .why-item p {

        color:var(--muted);
        font-size:17px

    }

    .why-lead {

        max-width:62ch;
        margin:0 auto 46px;
        text-align:center;
        color:var(--muted);
        font-size:19px

    }

    @media(max-width:760px) {

        .why {

            grid-template-columns:1fr

        }

    }

/* testimonials: two-row conveyor, right to left */

    .tsection {

        padding:92px 0 80px;
        overflow:hidden

    }

    .tsection .sec-hd {

        padding:0 28px

    }

    .t-note {

        text-align:center;
        font-size:14px;
        font-style:italic;
        color:var(--muted);
        margin-top:18px;
        padding:0 20px

    }

    .marquee {

        position:relative;
        padding:12px 0;
        overflow:hidden;
        -webkit-mask-image:linear-gradient(90deg,transparent,#000 9%,#000 91%,transparent);
        mask-image:linear-gradient(90deg,transparent,#000 9%,#000 91%,transparent)

    }

    .marquee+.marquee {

        margin-top:6px

    }

    .track {

        display:flex;
        width:max-content;
        animation:marquee var(--dur,70s) linear infinite;
        will-change:transform

    }

    @keyframes marquee {

        to {

            transform:translateX(-50%)

        }

    }

    .marquee:hover .track,.marquee:focus-within .track {

        animation-play-state:paused

    }

    .t-card {

        flex:none;
        width:clamp(270px,27vw,370px);
        margin:0 24px 0 0;
        display:flex;
        flex-direction:column;
        padding:26px 26px 22px;
        background:linear-gradient(180deg,#fff,#fbf9f3);
        border:1px solid var(--border);
        box-shadow:0 20px 40px -28px rgba(19,41,75,.35),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.25);
        transition:transform .3s var(--ease),border-color .3s,box-shadow .3s

    }

    .t-card:hover {

        transform:translateY(-5px);
        border-color:var(--gold-dim);
        box-shadow:0 30px 48px -28px rgba(19,41,75,.5),inset 0 0 0 4px #fff,inset 0 0 0 5px rgba(176,141,60,.5)

    }

    .t-qm {

        font-family:var(--serif);
        font-size:52px;
        line-height:.6;
        height:22px;
        color:var(--gold);
        display:block

    }

    .t-text {

        flex:1;
        margin:12px 0 22px;
        font-size:19px;
        line-height:1.5;
        color:var(--text)

    }

    .t-who {

        display:flex;
        flex-direction:column;
        gap:12px;
        padding-top:16px;
        border-top:1px solid var(--border)

    }

    .t-stars {

        display:flex;
        gap:2px;
        font-size:16px;
        line-height:1

    }

    .t-stars i {

        font-style:normal;
        color:#e0d6bb

    }

    .t-stars i.on {

        color:var(--gold)

    }

    .t-id {

        display:flex;
        align-items:center;
        gap:12px

    }

    .t-av {

        position:relative;
        flex:none;
        width:46px;
        height:46px;
        border-radius:50%;
        overflow:hidden;
        display:flex;
        align-items:center;
        justify-content:center;
        background:linear-gradient(160deg,#f0d78e,#b8903a);
        color:#fff;
        box-shadow:0 0 0 2px #fff,0 0 0 3px var(--gold-dim)

    }

    .t-av b {

        font-family:var(--serif);
        font-size:18px;
        font-weight:600

    }

    .t-av img {

        position:absolute;
        inset:0;
        width:100%;
        height:100%;
        object-fit:cover

    }

    .t-meta {

        display:flex;
        flex-direction:column;
        min-width:0;
        line-height:1.25

    }

    .t-name {

        font-family:var(--serif);
        font-size:15px;
        font-weight:600;
        color:var(--navy);
        letter-spacing:.03em

    }

    .t-role {

        font-size:15px;
        font-style:italic;
        color:var(--muted)

    }

/* ── final section: skip the line (same full-width temple, landscape image) ── */
    .skip {
        
        max-width:1920px;
        margin:0 auto;
        padding:30px 0 96px
    
    }

    .skip-pediment {
        
        display:block;
        width:100%;
        height:clamp(44px,5.4vw,70px)
    
    }

    .skip-pediment polygon {
        
        vector-effect:non-scaling-stroke
    
    }

    .skip-temple {
        
        position:relative;
        overflow:hidden;
        border-bottom:1px solid var(--gold);
        background:radial-gradient(ellipse 50% 70% at 28% 40%,rgba(255,255,255,.95),transparent 70%),linear-gradient(180deg,#fff,var(--champagne));
        box-shadow:inset 0 0 0 5px #fff,inset 0 0 0 6px rgba(176,141,60,.35),0 34px 56px -38px rgba(19,41,75,.5)
    
    }

    .skip-inner {
        
        max-width:1280px;
        margin:0 auto;
        padding:clamp(34px,4vw,52px) 18px 0
    
    }

    @media(min-width:1100px) {
        
        .skip-inner {
            
            padding-left:calc(var(--pw) + var(--po) + 30px);
            padding-right:calc(var(--pw) + var(--po) + 30px)
        
        }
    }

    .skip-grid {
        
        display:grid;
        grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);
        gap:clamp(28px,4vw,56px);
        align-items:center
    
    }

    .skip-fig {
        
        position:relative;
        min-width:0
    
    }

/* landscape 16:9 image frame (YOUR IMAGE) */
    .wide-frame{
        
        position:relative;
        aspect-ratio:16/9;
        overflow:hidden;
        border:1px solid var(--gold);
        background:linear-gradient(160deg,#f7f1e1,#efe6cf);
        box-shadow:0 0 0 6px #fff,0 0 0 7px rgba(176,141,60,.5),0 40px 50px -34px rgba(19,41,75,.6);
        transition:transform .6s var(--ease),box-shadow .6s
    
    }

    .wide-frame>img{
        
        position:absolute;
        inset:0;
        width:100%;height:100%;
        object-fit:cover;
        object-position:center;
        transition:transform 1.4s var(--ease),clip-path 1.3s cubic-bezier(.65,0,.2,1)
    
    }

    .wide-frame .art-ph{
        
        inset:12px;
        border-radius:2px;
        padding:14px 18px;
        gap:6px

    }

    .skip-fig:hover .wide-frame{
        
        transform:translateY(-6px);
        box-shadow:0 0 0 6px #fff,0 0 0 7px rgba(176,141,60,.75),0 54px 56px -34px rgba(19,41,75,.65)
    
    }

    .skip-fig:hover .wide-frame > img {
        
        transform:scale(1.04)
    
    }

    .js .skip-fig .wide-frame > img {
        
        clip-path:inset(0 100% 0 0)
    
    }

    .js .skip-fig.in .wide-frame > img {
        
        clip-path:inset(0)
    
    }

    .skip-seal {
        
        position:absolute;
        right:-14px;
        bottom:-30px;
        z-index:3;
        width:72px;
        height:72px;
        border-radius:50%;
        background:#fff;
        padding:4px;
        box-shadow:0 0 0 2px #d9b45a,0 0 0 4px #fff,0 0 0 5px rgba(176,141,60,.65),0 16px 24px -10px rgba(19,41,75,.6)
    
    }

    .skip-seal img {
        
        display:block;
        width:100%;
        height:100%;
        border-radius:50%;
        object-fit:cover
    
    }

    .skip-eyebrow {
        
        display:flex;
        align-items:center;
        gap:14px;
        font-family:var(--serif);
        font-size:12px;
        letter-spacing:.24em;
        color:var(--gold-bright);
        margin-bottom:14px
    
    }

    .skip-eyebrow::before {
        
        content:'';
        width:36px;
        height:1px;
        background:var(--gold)
    
    }

    .skip-copy {
        
        min-width:0
    
    }

    .skip-copy h2 {
        
        font-family:var(--serif);
        font-size:clamp(28px,3.4vw,44px);
        font-weight:600;
        line-height:1.14;
        color:var(--navy);
        letter-spacing:.015em;
        margin-bottom:14px
    
    }

    .skip-copy h2 span {
        
        display:block
    
    }

    .skip-copy h2 span+span {
        
        color:var(--gold-bright)
    
    }

    .skip-copy > p {
        
        color:var(--muted);
        font-size:18px;
        line-height:1.65;
        max-width:52ch;
        margin-bottom:16px
    
    }

    .skip-points {
        
        list-style:none;
        margin:0;
        display:grid;
        gap:7px
    
    }

    .skip-points li {
        
        display:flex;
        align-items:center;
        gap:12px;
        font-size:17px;
        color:var(--text)
    }

    .skip-points li::before {
        
        content:'';
        flex:none;
        width:7px;
        height:7px;
        transform:rotate(45deg);
        background:linear-gradient(135deg,#f0d78e,#a9822c);
        box-shadow:0 0 0 3px #fff,0 0 0 4px rgba(176,141,60,.45)
    }

/* counters on the left, call to action on the right, spanning the whole temple */
    .skip-band {
        
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:22px 40px;
        margin-top:46px;
        padding:24px 0 clamp(30px,3.4vw,42px);
        border-top:1px solid var(--gold-dim)
    
    }
    
    .skip-stats { 
        
        display:flex;
        gap:clamp(28px,5vw,64px);
        flex-wrap:wrap
    
    }

    .skip-stats .stat-n {
        
        font-size:clamp(32px,3.6vw,42px);
        font-variant-numeric:tabular-nums

    }

    .skip-stats .stat-l {
        
        font-family:var(--serif);
        font-size:12px;
        letter-spacing:.14em;
        text-transform:uppercase;
        margin-top:6px

    }

    @media(max-width:899px){

        .skip {
            
            padding:10px 0 72px
        
        }

        .skip-grid {
            
            grid-template-columns:1fr;
            gap:30px
        
        }

        .skip-seal {
            
            right:10px
        
        }

        .skip-band {
            
            flex-direction:column;
            align-items:flex-start;
            margin-top:36px
        
        }

        .skip-band .btn {
            
            width:100%;
            justify-content:center
        
        }
    }

    @media(max-width:560px) {
        
        .wide-frame .art-ph svg,.wide-frame .art-ph small {
            
            display:none
        
        }
    
    }

/* footer */
    footer {
        
        background:linear-gradient(180deg,var(--navy-2),#0d1c36);
        border-top:3px solid var(--gold)
    
    }

    footer .frieze {
        
        padding:8px 0 0
    
    }

    .foot {
        
        max-width:1200px;
        margin:0 auto;
        padding:30px 28px 26px;
        display:flex;
        flex-wrap:wrap;
        justify-content:space-between;
        align-items:center;
        gap:18px
    
    }

    .foot-brand {
        
        display:flex;
        align-items:center;
        gap:14px;
        font-family:var(--serif);
        font-size:17px;
        font-weight:700;
        letter-spacing:.2em;
        color:#fff
    
    }

    .foot-brand img {
        
        width:52px;height:52px;
        border-radius:50%;
        background:#fff;
        padding:3px;
        box-shadow:0 0 0 2px #d9b45a
    
    }

    .foot-brand small {
        
        display:block;
        font-family:var(--body);
        font-size:15px;
        font-weight:400;
        letter-spacing:0;
        color:#f0dca0;
        font-style:italic
    
    }

    .foot-links {
        
        display:flex;
        gap:24px;
        flex-wrap:wrap
    
    }

    .foot-links a {
        
        font-family:var(--serif);
        font-size:12px;
        letter-spacing:.14em;
        color:rgba(255,255,255,.72);
        text-decoration:none;
        transition:color .2s

    }

    .foot-links a:hover {
        
        color:#d9bb68
    
    }

    .foot-bottom {
        
        max-width:1200px;
        margin:0 auto;
        padding:16px 28px 26px;
        border-top:1px solid rgba(217,187,104,.25);
        font-size:15px;
        color:rgba(255,255,255,.55)
    }

    @media(max-width:700px) {
        
        .section {
            
            padding:68px 20px
        
        }
        
        .header-inner {
            
            padding:0 16px
        
        }
        
        .hero {
            
            padding-left:20px;
            padding-right:20px
        
        }
        
        .tsection {
            
            padding:68px 0 60px
        
        }
    
    }

/* reduced motion: no animation anywhere; the conveyor becomes a swipeable/scrollable row without the duplicates */
    @media (prefers-reduced-motion:reduce) {

        * {
        
            animation:none!important;
            transition:none!important;
            scroll-behavior:auto!important
    
        }

        .marquee {
        
            overflow-x:auto;
            -webkit-mask-image:none;
            mask-image:none
    
        }
    
        .t-card [aria-hidden="true"] {
        
            display:none
    
        }

    }
</style>
</head>
<body>

<!-- shared gradients (used by the laurel ornaments) -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">

    <defs>
        <linearGradient id="gG" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f0d78e"/>
            <stop offset=".5" stop-color="#c9a24a"/>
            <stop offset="1" stop-color="#8a6a1f"/>
        
        </linearGradient>
    </defs>

</svg>

<div class="top-strip">
    
    CARD-MRI DEVELOPMENT INSTITUTE &nbsp;&middot;&nbsp; SCHOOL CANTEEN &nbsp;&middot;&nbsp; ORDER AHEAD, SKIP THE LINE

</div>

<header id="siteHeader">

    <div class="header-inner">
        
        <a href="index.php" class="brand" aria-label="COPS home">
            <span class="brand-logo"><img src="<?= SCHOOL_LOGO ?>" alt="CARD-MRI Development Institute logo"></span>

            <span class="brand-name">COPS<small>CMDI SPC Campus Canteen</small></span>

        </a>

        <nav aria-label="Main">

            <a href="index.php" class="active keep">HOME</a> <!-- HOME BUTTON -->
            <a href="menu.php">MENU</a> <!-- MENU BUTTON -->
            <a href="#how">HOW IT WORKS</a> <!-- HOW IT WORKS BUTTON -->
            <a href="#why">ABOUT</a> <!-- ABOUT-US BUTTON -->

            <?php if ($isLoggedIn): ?>
                <span class="hello">Hello, <?= htmlspecialchars($firstName) ?></span>
                <a href="log-out.php" class="keep">LOG OUT</a> <!-- LOG-OUT BUTTON -->
                <a href="menu.php" class="cta">ORDER NOW</a> <!-- ORDER NOW BUTTON -->
            <?php else: ?>
                <a href="log-in.php" class="keep">LOG IN</a>
                <a href="menu.php" class="cta">ORDER NOW</a>
            <?php endif; ?>
        </nav>
    </div>

</header>

<main class="wrap">

<!-- HERO -->
<section class="hero">

    <div class="hero-copy">

        <div class="hero-kicker">CANTEEN ORDERING PLATFORM SYSTEM</div>
        <h1>A sound mind begins with a <em>good meal.</em></h1>
        <p class="latin">Mens sana in corpore sano.</p>
        <p class="lede">COPS lets CMDI students browse the canteen menu, check prices, and reserve their food from a phone, so break time is for eating, not for standing in line.</p>
        
        <div class="hero-ctas">
            <?php if ($isLoggedIn): ?>
                <a href="menu.php" class="btn btn-gold">BROWSE THE MENU</a>
            <?php else: ?>
                <a href="log-in.php" class="btn btn-gold">LOG IN TO ORDER</a>
            <?php endif; ?>
            <a href="#how" class="btn btn-ghost">HOW IT WORKS</a>
        
        </div>

        <div class="hero-stats">
            <div><div class="stat-n"><?= number_format($stats['items']) ?></div><div class="stat-l">Items on the menu</div></div>
            <div><div class="stat-n"><?= number_format($stats['categories']) ?></div><div class="stat-l">Food categories</div></div>
            <div><div class="stat-n"><?= number_format($stats['students']) ?></div><div class="stat-l">Registered students</div></div>
        </div>

    </div>

    <!-- the CMDI campus: classroom wing, marble entrance, canteen wing -->
    <div class="hero-scene">

        <div class="scene-frame">
            <span class="scene-corner tl"></span><span class="scene-corner tr"></span><span class="scene-corner bl"></span><span class="scene-corner br"></span>
            <div class="scene-frame-in"><?= school_scene('wide', 'h') ?></div>
            <div class="scene-seal"><img src="<?= SCHOOL_LOGO ?>" alt="CARD-MRI Development Institute seal"></div>
        </div>

    </div>

</section>

<div class="strip">

    <div class="strip-inner">
        
        <span>MADE FOR CMDI STUDENTS</span>
        <span>MENU &amp; PRICES, ALWAYS CURRENT</span>
        <span>ORDER AHEAD</span>
        <span>FEWER MIX-UPS</span>

    </div>

</div>

<!-- FEATURED -->
<section class="section" id="featured">

    <div class="sec-hd">
                
        <h2>Today&rsquo;s offerings</h2>
        <p>Student favorites, straight from the canteen&rsquo;s menu.</p>

        <div class="orn">
            
            <span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span>
        
        </div>

    </div>

    <div class="grid-3">

        <?php if (empty($featured)): ?>

            <div class="empty">No featured items yet. Set <code>is_featured = 1</code> on a product to show it here.</div>
        
        <?php else: foreach ($featured as $p):
            $low = (int)$p['stock'] <= (int)$p['reorder_level'];
        ?>

        <article class="item">

            <div class="item-media">

                <?= catIcon($p['category']) ?>
                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.remove()">
                <?php endif; ?>
            
            </div>

            <div class="item-body">

                <div class="item-cat"><?= htmlspecialchars(categoryLabel($p['category'])) ?></div>
                <h3 class="item-name"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="item-desc"><?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 90, '…')) ?></p>
                <div class="item-foot">
                    <div class="price"><small>₱</small><?= number_format((float)$p['price'], 2) ?></div>
                    <div class="stock <?= $low ? 'low' : '' ?>"><?= $low ? 'Few left' : 'Available' ?></div>
                
                </div>
            </div>

        </article>

        <?php endforeach; endif; ?>

    </div>

</section>

<!-- CATEGORIES: ONE section, four slides (Rice Meals / Snacks / Drinks / Desserts).
     The old "Pick your perfect plate" block is merged in: every slide carries its own copy, button and top dishes.
     YOUR IMAGES go in assets/img/categories/ (rice-meals, snacks, drinks, desserts; png, webp, jpg or jpeg). -->

<section class="section wide" id="categories" style="padding-top:0">

    <div class="sec-hd">

        <h2>Find what you&rsquo;re craving</h2>
        <p>Four ways to start your break. Choose a category and go straight to the menu.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>

    </div>

    <div class="temple-sc" id="showcase" role="region" aria-roledescription="carousel" aria-label="Menu categories">

        <div class="tp-frieze" aria-hidden="true"><div class="meander"></div></div>
        <span class="pil l" aria-hidden="true"></span><span class="pil r" aria-hidden="true"></span>
        <div class="tp-body">
            <div class="sc-viewport">
                <div class="sc-track">
                <?php foreach ($slides as $i => $s): $act = ($i === 0); ?>
                    <section class="slide<?= $act ? ' is-active' : '' ?>" id="slide-<?= $s['key'] ?>" role="tabpanel" aria-labelledby="tab-<?= $s['key'] ?>"<?= $act ? '' : ' inert' ?>>
                        <div class="sl-head">
                            <div class="sl-kicker"><?= htmlspecialchars($s['kicker']) ?></div>
                            <h3 class="sl-title"><?= htmlspecialchars($s['title']) ?></h3>
                        </div>

                        <div class="sl-art">
                            <div class="art-frame">
                                <?php if ($s['src']): ?>
                                    <img src="<?= htmlspecialchars($s['src']) ?>" alt="<?= htmlspecialchars($s['title']) ?> from the CMDI canteen" width="900" height="1125"<?= $act ? '' : ' loading="lazy"' ?> decoding="async" onerror="this.remove()">
                                <?php else: ?>
                                    <!-- PLACEHOLDER: add your image at the path shown below and this box fills itself -->
                                    <div class="art-ph">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1"/><circle cx="9" cy="10" r="1.6"/><path d="M3 17l5-5 4 4 3-3 6 6"/></svg>
                                        <b><?= htmlspecialchars(strtoupper($s['title'])) ?> IMAGE</b>
                                        <code><?= htmlspecialchars($s['img']) ?>.png</code>
                                        <small>4:5 portrait, about 900 &times; 1125 px</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sl-copy">
                            <p><?= htmlspecialchars($s['blurb']) ?></p>
                            <?php if ($s['n'] > 0): ?>
                                <div class="sl-meta"><?= (int)$s['n'] ?> item<?= $s['n'] === 1 ? '' : 's' ?><?= $s['min'] !== null ? ' &middot; from &#8369;' . number_format($s['min'], 0) : '' ?></div>
                            <?php endif; ?>
                            <a href="menu.php?cat=<?= urlencode($s['key']) ?>" class="btn btn-navy">EXPLORE <?= htmlspecialchars(strtoupper($s['title'])) ?></a>
                        </div>

                        <div class="sl-cards">
                            <?php if (empty($s['items'])): ?>
                                <p class="sl-empty">Dishes for this category are coming soon.</p>
                            <?php else: foreach ($s['items'] as $it): ?>
                                <a class="fcard" href="menu.php?cat=<?= urlencode($s['key']) ?>">
                                    <span class="fic"><?= catIcon($s['key']) ?></span>
                                    <span class="fbody"><b><?= htmlspecialchars($it['name']) ?></b><small><?= htmlspecialchars(mb_strimwidth($it['description'] ?? '', 0, 80, '…')) ?></small></span>
                                    <span class="fp">&#8369;<?= number_format((float)$it['price'], 2) ?></span>
                                </a>
                            <?php endforeach; endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="sc-plinth">
            <button type="button" class="sc-arrow" id="scPrev" aria-label="Previous category"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
            <div class="sc-tabs" role="tablist" aria-label="Choose a category">
                <?php foreach ($slides as $i => $s): ?>
                    <button type="button" class="sc-tab" role="tab" id="tab-<?= $s['key'] ?>" aria-controls="slide-<?= $s['key'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>" data-i="<?= $i ?>"><b><?= $s['numeral'] ?></b><span><?= htmlspecialchars($s['title']) ?></span></button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="sc-arrow" id="scNext" aria-label="Next category"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
        </div>
        <p class="sr-only" id="scLive" aria-live="polite"></p>
        <div class="tp-base" aria-hidden="true"></div>
    </div>
    
</section>

<!-- HOW IT WORKS -->
<section class="section" id="how" style="padding-top:20px">
    <div class="sec-hd">
        <h2>Three steps to lunch</h2>
        <p>No shouting over the counter. No wrong orders.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="steps">
        <div class="step"><div class="step-n">I</div><h3>Read the menu</h3><div class="la">Lege</div><p>See what&rsquo;s available today, with prices, before you leave your seat.</p></div>
        <div class="step"><div class="step-n">II</div><h3>Reserve your order</h3><div class="la">Posce</div><p>Choose your items and a pickup time, then send your request to the canteen.</p></div>
        <div class="step"><div class="step-n">III</div><h3>Pick it up</h3><div class="la">Cape</div><p>Staff prepare it and mark it ready. Claim it with your code and pay at the counter.</p></div>
    </div>
</section>

<!-- WHY COPS -->
<div class="band" id="why">
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <section class="section">
        <div class="sec-hd">
            <h2>Why the canteen needs COPS</h2>
            <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
        </div>
        <p class="why-lead">Break time is short and the counter is crowded. Orders get mixed up, staff get overwhelmed, and nobody knows what&rsquo;s still available. COPS puts the menu, the orders, and the records in one place.</p>
        <div class="why">
            <div class="why-item"><h3>Shorter queues</h3><p>Orders come in before you reach the counter, so the line moves faster for everyone.</p></div>
            <div class="why-item"><h3>Fewer mistakes</h3><p>What you pick on screen is what staff see. No more miscommunication across the counter.</p></div>
            <div class="why-item"><h3>A menu you can trust</h3><p>Check items and prices first. Sold-out food is marked, so you don&rsquo;t wait for nothing.</p></div>
            <div class="why-item"><h3>Records that keep themselves</h3><p>Every transaction is saved, so staff can follow daily sales and stock without paper tallies.</p></div>
        </div>
    </section>
    <div class="frieze"><div class="meander"></div><div class="dentil" style="border-top:none;border-bottom:1px solid var(--gold-dim)"></div></div>
</div>

<!-- TESTIMONIALS: two-row conveyor.
     Cards come from `personal_testimonials` (is_active = 1). While that table is empty, SAMPLE text is shown. -->
<section class="tsection" aria-labelledby="tTitle">
    <div class="sec-hd">
        <h2 id="tTitle">What students say</h2>
        <p>Kind words from the CMDI community.</p>
        <div class="orn"><span></span><svg viewBox="-17 -17 34 34"><g data-wreath="12" data-pairs="6"></g></svg><span></span></div>
    </div>
    <div class="marquee" role="region" aria-label="Feedback from students and staff" tabindex="0">
        <div class="track" style="--dur:75s"><?= testimonialTrack($testimonials) ?></div>
    </div>
    <div class="marquee" aria-hidden="true">
        <div class="track" style="--dur:95s"><?= testimonialTrack($row2) ?></div>
    </div>
    <?php if ($usingSampleTestimonials): ?>
        <p class="t-note">Sample feedback shown until real testimonials are added.</p>
    <?php endif; ?>
</section>

<!-- SKIP THE LINE: queue image on the left (YOUR IMAGE), message + live counters on the right.
     Add the photo as assets/img/canteen-queue.jpg (or .jpeg / .webp / .png); the placeholder disappears on its own. -->
<section class="skip" id="skip" aria-labelledby="skipTitle">
    <svg class="skip-pediment" viewBox="0 0 440 74" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <polygon points="0,70 220,4 440,70" fill="#ffffff" stroke="url(#gG)" stroke-width="2"/>
        <polygon points="44,66 220,18 396,66" fill="none" stroke="#c9b373" stroke-width="1" stroke-dasharray="2 4"/>
        <path d="M220,30 l7,9 l-7,9 l-7,-9z" fill="url(#gG)"/>
        <rect x="0" y="68" width="440" height="6" fill="url(#gG)"/>
    </svg>
    <div class="skip-temple">
        <span class="pil l" aria-hidden="true"></span><span class="pil r" aria-hidden="true"></span>
        <div class="skip-inner">
            <div class="skip-grid">
                <figure class="skip-fig">
                    <div class="wide-frame">
                        <?php if ($queueImg): ?>
                            <img src="<?= htmlspecialchars($queueImg) ?>" alt="Students lining up at the school canteen counter during break" width="1600" height="900" loading="lazy" decoding="async">
                        <?php else: ?>
                            <!-- PLACEHOLDER for the queue image (landscape 16:9) -->
                            <div class="art-ph">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="1"/><circle cx="9" cy="10" r="1.6"/><path d="M3 17l5-5 4 4 3-3 6 6"/></svg>
                                <b>QUEUE IMAGE</b>
                                <code>assets/img/canteen-queue.jpg</code>
                                <small>Landscape 16:9, about 1600 &times; 900 px</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="skip-seal"><img src="<?= SCHOOL_LOGO ?>" alt="" loading="lazy"></div>
                </figure>

                <div class="skip-copy">
                    <div class="skip-eyebrow">ORDER AHEAD</div>
                    <h2 id="skipTitle"><span>Skip the line.</span> <span>Keep your break.</span></h2>
                    <p>Make every break more convenient with a smarter way to order. Browse the canteen menu, reserve your favorite meals, and secure your order before you reach the counter. Spend less time waiting in line and more time enjoying your break.</p>
                    <ul class="skip-points">
                        <li>Reserve from your phone before the bell</li>
                        <li>Pick the break slot that suits you</li>
                        <li>Collect with your code and pay at the counter</li>
                    </ul>
                </div>
            </div>

            <!-- live counts from the `reservations` table (see PHP at the top of this file) -->
            <div class="skip-band">
                <div class="skip-stats">
                    <div><div class="stat-n" data-count="<?= (int)$resv['total'] ?>"><?= number_format($resv['total']) ?></div><div class="stat-l">Student Reservations</div></div>
                    <div><div class="stat-n" data-count="<?= (int)$resv['confirmed'] ?>"><?= number_format($resv['confirmed']) ?></div><div class="stat-l">Confirmed Transactions</div></div>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="menu.php" class="btn btn-gold">BROWSE THE MENU</a>
                <?php else: ?>
                    <a href="log-in.php" class="btn btn-gold">LOG IN TO ORDER</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="tp-base" aria-hidden="true"></div>
    </div>
</section>

</main>

<footer>
    <div class="frieze"><div class="dentil"></div><div class="meander"></div></div>
    <div class="foot">
        <div class="foot-brand"><img src="<?= SCHOOL_LOGO ?>" alt="CMDI logo"><span>COPS<small>Canteen Ordering Platform System &middot; CARD-MRI Development Institute</small></span></div>
        <div class="foot-links">
            <a href="index.php">HOME</a>
            <a href="menu.php">MENU</a>
            <a href="about-us.php">ABOUT US</a>
            <?php if ($isLoggedIn): ?><a href="log-out.php">LOG OUT</a><?php else: ?><a href="log-in.php">LOG IN</a><?php endif; ?>
        </div>
    </div>
    <div class="foot-bottom">&copy; 2026 COPS. A CARD-MRI Development Institute canteen ordering project.</div>
</footer>

<script>
/* Laurel wreath: builds two mirrored branches of leaf pairs into every [data-wreath] group */
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    function el(n, a) { var e = document.createElementNS(NS, n); for (var k in a) e.setAttribute(k, a[k]); return e; }
    function wreath(g, R, pairs) {
        var rx = R * .23, ry = R * .095;
        [-1, 1].forEach(function (s) {
            var d = '';
            for (var i = 0; i <= 24; i++) {
                var th = (16 + (152 - 16) * i / 24) * Math.PI / 180;
                d += (i ? 'L' : 'M') + (s * R * Math.sin(th)).toFixed(2) + ' ' + (R * Math.cos(th)).toFixed(2);
            }
            g.appendChild(el('path', { d: d, fill: 'none', stroke: '#8a6a1f', 'stroke-width': Math.max(.5, R * .045), 'stroke-linecap': 'round' }));
            for (var j = 0; j < pairs; j++) {
                var th2 = (22 + (150 - 22) * j / (pairs - 1)) * Math.PI / 180;
                var px = s * R * Math.sin(th2), py = R * Math.cos(th2);
                var tan = Math.atan2(-Math.sin(th2), s * Math.cos(th2)) * 180 / Math.PI;
                [1, -1].forEach(function (o) {
                    var cx = px + s * Math.sin(th2) * o * R * .1, cy = py + Math.cos(th2) * o * R * .1;
                    g.appendChild(el('ellipse', {
                        cx: cx.toFixed(2), cy: cy.toFixed(2), rx: rx.toFixed(2), ry: ry.toFixed(2),
                        transform: 'rotate(' + (tan + o * s * 34).toFixed(1) + ' ' + cx.toFixed(2) + ' ' + cy.toFixed(2) + ')', fill: 'url(#gG)'
                    }));
                });
            }
            g.appendChild(el('circle', { cx: (s * R * Math.sin(.2)).toFixed(2), cy: (R * Math.cos(.2) + R * .1).toFixed(2), r: (R * .07).toFixed(2), fill: '#d9b45a' }));
        });
    }
    document.querySelectorAll('[data-wreath]').forEach(function (g) {
        wreath(g, parseFloat(g.getAttribute('data-wreath')), parseInt(g.getAttribute('data-pairs'), 10) || 7);
    });
})();

/* header gets a solid background once you scroll */
(function () {
    var h = document.getElementById('siteHeader');
    function f() { h.classList.toggle('scrolled', window.scrollY > 30); }
    window.addEventListener('scroll', f, { passive: true }); f();
})();

/* count-up for the reservation / transaction numbers once they scroll into view.
   The real number is already in the HTML, so without JS (or with reduced motion) it simply shows. */
(function () {
    var els = document.querySelectorAll('[data-count]');
    if (!els.length) return;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) return;
    function fmt(n) { return n.toLocaleString('en-US'); }
    function run(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (target === 0) return;
        var t0 = null, dur = 1500;
        function step(ts) {
            if (t0 === null) t0 = ts;
            var p = Math.min(1, (ts - t0) / dur);
            el.textContent = fmt(Math.round(target * (1 - Math.pow(1 - p, 3))));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            io.unobserve(en.target);
            run(en.target);
        });
    }, { threshold: .6 });
    els.forEach(function (el) { if ((parseInt(el.getAttribute('data-count'), 10) || 0) > 0) el.textContent = '0'; io.observe(el); });
})();

/* Category showcase: four slides, tabs + arrows + keyboard + swipe. No autoplay. */
(function () {
    var root = document.getElementById('showcase');
    if (!root) return;
    var track = root.querySelector('.sc-track');
    var slides = [].slice.call(root.querySelectorAll('.slide'));
    var tabs = [].slice.call(root.querySelectorAll('.sc-tab'));
    var live = document.getElementById('scLive');
    var n = slides.length, cur = 0;

    function show(i, focusTab) {
        cur = (i + n) % n;
        track.style.transform = 'translate3d(' + (-cur * 100) + '%,0,0)';
        slides.forEach(function (s, k) {
            var on = k === cur;
            s.classList.toggle('is-active', on);
            if (on) s.removeAttribute('inert'); else s.setAttribute('inert', '');
        });
        tabs.forEach(function (t, k) {
            t.setAttribute('aria-selected', k === cur ? 'true' : 'false');
            t.tabIndex = k === cur ? 0 : -1;
        });
        if (live) live.textContent = tabs[cur].querySelector('span').textContent + ', ' + (cur + 1) + ' of ' + n;
        if (focusTab) tabs[cur].focus();
    }
    tabs.forEach(function (t, k) {
        t.addEventListener('click', function () { show(k); });
        t.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); show(cur + 1, true); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); show(cur - 1, true); }
            else if (e.key === 'Home') { e.preventDefault(); show(0, true); }
            else if (e.key === 'End') { e.preventDefault(); show(n - 1, true); }
        });
    });
    document.getElementById('scPrev').addEventListener('click', function () { show(cur - 1); });
    document.getElementById('scNext').addEventListener('click', function () { show(cur + 1); });

    /* touch / pen swipe (horizontal intent only, so vertical scrolling is untouched) */
    var vp = root.querySelector('.sc-viewport'), x0 = 0, y0 = 0, tracking = false;
    vp.addEventListener('pointerdown', function (e) { if (e.pointerType === 'mouse') return; tracking = true; x0 = e.clientX; y0 = e.clientY; });
    vp.addEventListener('pointerup', function (e) {
        if (!tracking) return; tracking = false;
        var dx = e.clientX - x0, dy = e.clientY - y0;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) show(cur + (dx < 0 ? 1 : -1));
    });
    vp.addEventListener('pointercancel', function () { tracking = false; });
})();

/* Reveal on scroll: section headings, the category temple, the skip-the-line temple and its image */
(function () {
    document.documentElement.classList.add('js');
    var rv = [].slice.call(document.querySelectorAll('.sec-hd, .temple-sc, .skip-temple'));
    rv.forEach(function (e) { e.classList.add('rv'); });
    var all = rv.concat([].slice.call(document.querySelectorAll('.skip-fig')));
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) { all.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (es) {
        es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: .12, rootMargin: '0px 0px -6% 0px' });
    all.forEach(function (e) { io.observe(e); });
})();
</script>
</body>
</html>