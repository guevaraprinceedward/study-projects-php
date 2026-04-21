<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["user"])) { header("Location: log-in.php"); exit(); }
include 'config.php';

// Session validation
$uid   = (int)($_SESSION["user"]["id"] ?? 0);
$uname = $conn->real_escape_string($_SESSION["user"]["username"] ?? '');
$chk   = $conn->query("SELECT id FROM users WHERE id = $uid AND username = '$uname' LIMIT 1");
if (!$chk || $chk->num_rows === 0) {
    session_unset(); session_destroy();
    header("Location: log-in.php"); exit();
}

// Ensure stock columns exist
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT NOT NULL DEFAULT 100");
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 10");

$result = $conn->query("SELECT * FROM products ORDER BY id ASC");
$products = [];
while ($row = $result->fetch_assoc()) { $products[] = $row; }

// ── PER-USER ORDERED MAP ──────────────────────────────────────────────────
$userOrderedMap = [];
$orderedRes = $conn->query("
    SELECT oi.product_id, COALESCE(SUM(oi.quantity), 0) AS total_ordered
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.user_id = $uid
    AND o.status != 'cancelled'
    GROUP BY oi.product_id
");
if ($orderedRes) {
    while ($oRow = $orderedRes->fetch_assoc()) {
        $userOrderedMap[(int)$oRow['product_id']] = (int)$oRow['total_ordered'];
    }
}

if (empty($products)) {
    $products = [
        ["id"=>1,"name"=>"Espresso","price"=>85,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Espresso.Product.png","description"=>"Bold and concentrated shot of pure espresso, brewed from freshly ground arabica beans."],
        ["id"=>2,"name"=>"Americano","price"=>100,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Americano.Product-V1.png","description"=>"Espresso diluted with hot water for a smooth, full-bodied black coffee experience."],
        ["id"=>3,"name"=>"Cappuccino","price"=>130,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Cappuccino.Product.png","description"=>"Equal parts espresso, steamed milk, and thick velvety foam — a classic Italian favourite."],
        ["id"=>4,"name"=>"Caffè Latte","price"=>140,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Caffe.Latte.png","description"=>"Silky steamed milk poured over a double shot of espresso with a light layer of foam."],
        ["id"=>5,"name"=>"Flat White","price"=>145,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Flat.White.Product.png","description"=>"Stronger and creamier than a latte — micro-foamed milk over a rich ristretto shot."],
        ["id"=>6,"name"=>"Caramel Macchiato","price"=>155,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Caramel-Macchiato.Product.png","description"=>"Vanilla-infused steamed milk, espresso, and a generous drizzle of rich caramel."],
        ["id"=>7,"name"=>"Mocha","price"=>150,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Mocha.Product.png","description"=>"Espresso blended with chocolate syrup and steamed milk, topped with whipped cream."],
        ["id"=>8,"name"=>"Hazelnut Latte","price"=>155,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Hazelnut-Latte.Product.png","description"=>"Smooth latte infused with rich hazelnut syrup and topped with silky milk foam."],
        ["id"=>9,"name"=>"White Chocolate Mocha","price"=>160,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/White-Chocolate-Mocha.Product.png","description"=>"Espresso blended with creamy white chocolate and steamed milk, topped with whipped cream."],
        ["id"=>10,"name"=>"Spanish Latte","price"=>150,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Spanish-Latte.Product.png","description"=>"Sweet and creamy latte made with condensed milk for a richer flavor profile."],
        ["id"=>11,"name"=>"Vanilla Latte","price"=>145,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Vanilla-Latte.Product.png","description"=>"Classic latte enhanced with smooth vanilla syrup and steamed milk."],
        ["id"=>12,"name"=>"Cortado","price"=>135,"category"=>"mains","stock"=>100,"reorder_level"=>10,"image"=>"https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/main/Cortado-Latte.Product.png","description"=>"Equal parts espresso and warm milk, cutting the acidity for a balanced, velvety sip."],
        ["id"=>13,"name"=>"Cold Brew","price"=>160,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Steeped 18 hours in cold water for a smooth, naturally sweet concentrate over ice."],
        ["id"=>14,"name"=>"Iced Americano","price"=>110,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Double espresso pulled over ice and chilled water — clean, crisp, and refreshing."],
        ["id"=>15,"name"=>"Iced Caramel Latte","price"=>165,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Chilled latte with caramel syrup and milk poured over a glass of crushed ice."],
        ["id"=>16,"name"=>"Matcha Latte","price"=>150,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Ceremonial-grade matcha whisked with oat milk — earthy, smooth, and lightly sweet."],
        ["id"=>17,"name"=>"Iced Mocha","price"=>155,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Chilled blend of espresso, chocolate syrup, and milk served over ice."],
        ["id"=>18,"name"=>"Dirty Matcha","price"=>165,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Layered drink of matcha and espresso with milk for a bold, earthy caffeine kick."],
        ["id"=>19,"name"=>"Espresso Tonic","price"=>150,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Refreshing mix of espresso and tonic water served over ice with citrus notes."],
        ["id"=>20,"name"=>"Iced Hazelnut Latte","price"=>165,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Chilled hazelnut-infused latte poured over ice for a nutty, refreshing treat."],
        ["id"=>21,"name"=>"Strawberry Lemonade","price"=>120,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Freshly squeezed lemonade blended with sweet strawberry purée over ice."],
        ["id"=>22,"name"=>"Sparkling Water","price"=>80,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Chilled sparkling mineral water, perfectly refreshing on its own or with a meal."],
        ["id"=>23,"name"=>"Mango Soda Float","price"=>145,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Sweet mango soda topped with a scoop of vanilla ice cream for a tropical float."],
        ["id"=>24,"name"=>"Hot Chocolate","price"=>130,"category"=>"drinks","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Rich and creamy hot chocolate made with real dark cocoa and steamed milk."],
        ["id"=>25,"name"=>"Croissant","price"=>95,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Buttery, flaky all-butter croissant baked fresh every morning."],
        ["id"=>26,"name"=>"Banana Bread","price"=>80,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Moist homemade banana bread with a golden crust, perfect with your morning coffee."],
        ["id"=>27,"name"=>"Blueberry Muffin","price"=>85,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Fluffy muffin bursting with fresh blueberries and a crumbly sugar topping."],
        ["id"=>28,"name"=>"Cinnamon Roll","price"=>110,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Soft, sweet pastry swirled with cinnamon sugar and topped with light icing."],
        ["id"=>29,"name"=>"Chocolate Chip Cookie","price"=>70,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Freshly baked cookie loaded with rich chocolate chips and a soft chewy center."],
        ["id"=>30,"name"=>"Ham & Cheese Panini","price"=>140,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Grilled panini stuffed with savory ham and melted cheese."],
        ["id"=>31,"name"=>"Breakfast Sandwich","price"=>150,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Toasted sandwich with egg, cheese, and choice of ham or bacon for a filling start."],
        ["id"=>32,"name"=>"Avocado Toast","price"=>160,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Toasted sourdough topped with smashed avocado, sea salt, and a drizzle of olive oil."],
        ["id"=>33,"name"=>"Cheese Danish","price"=>95,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Flaky pastry filled with sweet cream cheese and a light vanilla glaze."],
        ["id"=>34,"name"=>"Granola Bar","price"=>65,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"House-made oat granola bar packed with honey, seeds, and dried fruits."],
        ["id"=>35,"name"=>"Egg Salad Sandwich","price"=>130,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Creamy egg salad on soft white bread, seasoned with herbs and a touch of mustard."],
        ["id"=>36,"name"=>"Spinach & Feta Wrap","price"=>145,"category"=>"sides","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Whole wheat wrap filled with sautéed spinach, feta cheese, and sun-dried tomatoes."],
        ["id"=>37,"name"=>"Tiramisu","price"=>130,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Classic Italian dessert with espresso-soaked ladyfingers and mascarpone cream."],
        ["id"=>38,"name"=>"Chocolate Lava Cake","price"=>145,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Warm dark chocolate cake with a molten centre, served with a dusting of cocoa powder."],
        ["id"=>39,"name"=>"Affogato","price"=>140,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Vanilla ice cream drowned in a hot shot of espresso for a perfect hot-and-cold dessert."],
        ["id"=>40,"name"=>"Cheesecake","price"=>135,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Creamy baked cheesecake with a buttery graham crust."],
        ["id"=>41,"name"=>"Brownies","price"=>90,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Fudgy chocolate brownies with a crackly top and rich cocoa flavor."],
        ["id"=>42,"name"=>"Crème Brûlée","price"=>150,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Silky vanilla custard topped with a perfectly caramelised sugar crust."],
        ["id"=>43,"name"=>"Mango Panna Cotta","price"=>130,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Silky Italian panna cotta crowned with a vibrant fresh mango coulis."],
        ["id"=>44,"name"=>"Strawberry Shortcake","price"=>140,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Light sponge cake layered with fresh strawberries and whipped cream."],
        ["id"=>45,"name"=>"Macarons (3 pcs)","price"=>120,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Delicate French macarons in assorted flavours — crisp shell, chewy centre."],
        ["id"=>46,"name"=>"Chocolate Mousse","price"=>125,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Airy dark chocolate mousse served chilled with a dusting of cocoa."],
        ["id"=>47,"name"=>"Leche Flan","price"=>95,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Classic Filipino-style caramel custard flan, silky smooth with a golden caramel top."],
        ["id"=>48,"name"=>"Ube Cheesecake","price"=>145,"category"=>"desserts","stock"=>100,"reorder_level"=>10,"image"=>"","description"=>"Creamy ube-flavoured cheesecake with a rich purple hue and buttery biscuit base."],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Our Menu — AyosCoffeeNegosyo</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg:#0b0b09;--surface:#131310;--card:#1a1a16;--border:#2c2c24;
    --gold:#c9a84c;--gold-dim:#8a6f2e;--green:#4a7a3a;--green-lt:#6aaa52;
    --cream:#f0ead8;--muted:#6b6b58;--text:#e8e4d8;--shadow:rgba(0,0,0,0.6);
    --sidebar-w:270px;--red:#c0392b;--amber:#d4820a;
}
html{scroll-behavior:smooth}
body{font-family:'Jost',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;position:relative;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(201,168,76,0.06) 0%,transparent 55%),radial-gradient(ellipse 50% 70% at 90% 100%,rgba(74,122,58,0.07) 0%,transparent 55%);pointer-events:none;z-index:0}

/* SIDEBAR OVERLAY */
#sidebarOverlay{position:fixed;inset:0;background:rgba(0,0,0,0);z-index:199;pointer-events:none;transition:background 0.4s ease}
#sidebarOverlay.active{background:rgba(0,0,0,0.55);pointer-events:all}

/* SIDEBAR */
#sidebar{position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;background:#111109;border-right:1px solid var(--border);z-index:200;display:flex;flex-direction:column;transform:translateX(calc(-1 * var(--sidebar-w)));transition:transform 0.42s cubic-bezier(0.4,0,0.2,1);will-change:transform}
#sidebar.open{transform:translateX(0);box-shadow:6px 0 40px rgba(0,0,0,0.7)}
.sb-header{padding:28px 24px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.sb-brand{display:flex;align-items:center;gap:11px}
.sb-brand-icon{width:34px;height:34px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-brand-name{font-family:'Cormorant Garamond',serif;font-size:15px;font-weight:600;color:var(--cream);letter-spacing:0.03em;line-height:1.2}
.sb-brand-name span{display:block;font-size:11px;font-weight:400;font-family:'Jost',sans-serif;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase}
.sb-close{width:30px;height:30px;border:1px solid var(--border);border-radius:50%;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color 0.2s,border-color 0.2s,background 0.2s;flex-shrink:0}
.sb-close:hover{color:var(--cream);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.sb-nav{flex:1;padding:20px 16px;display:flex;flex-direction:column;gap:4px;overflow-y:auto}
.sb-nav-label{font-size:9.5px;letter-spacing:0.18em;text-transform:uppercase;color:var(--muted);padding:4px 10px 10px;margin-top:6px}
.sb-link{display:flex;align-items:center;gap:13px;padding:11px 14px;border-radius:6px;text-decoration:none;color:var(--muted);font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;position:relative;border:1px solid transparent}
.sb-link:hover{color:var(--cream);background:rgba(255,255,255,0.04)}
.sb-link.active{color:var(--gold);background:rgba(201,168,76,0.08);border-color:rgba(201,168,76,0.12)}
.sb-link svg{flex-shrink:0;opacity:0.7;transition:opacity 0.2s}
.sb-link:hover svg,.sb-link.active svg{opacity:1}
.sb-link.active svg{color:var(--gold)}
.sb-badge{margin-left:auto;background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;padding:0 6px}
.sb-divider{height:1px;background:var(--border);margin:8px 0}
.sb-footer{padding:16px;border-top:1px solid var(--border)}
.sb-logout{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:6px;text-decoration:none;color:#c0574a;font-size:13.5px;font-weight:400;letter-spacing:0.02em;transition:color 0.2s,background 0.2s;border:1px solid transparent}
.sb-logout:hover{color:#e06b5d;background:rgba(192,87,74,0.08);border-color:rgba(192,87,74,0.14)}

/* HEADER */
header{position:sticky;top:0;z-index:100;background:rgba(11,11,9,0.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--border)}
.header-inner{max-width:1200px;margin:0 auto;padding:0 32px;height:68px;display:flex;align-items:center;justify-content:space-between}
.header-left{display:flex;align-items:center;gap:18px}
#sidebarToggle{width:40px;height:40px;border:1px solid var(--border);border-radius:6px;background:transparent;color:var(--muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:color 0.2s,border-color 0.2s,background 0.2s}
#sidebarToggle:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.hb{display:flex;flex-direction:column;gap:5px;width:18px}
.hb span{display:block;height:1.5px;background:currentColor;border-radius:2px;transition:transform 0.3s ease,opacity 0.3s ease,width 0.3s ease;transform-origin:center}
.hb span:nth-child(3){width:12px}
#sidebarToggle.open .hb span:nth-child(1){transform:translateY(6.5px) rotate(45deg)}
#sidebarToggle.open .hb span:nth-child(2){opacity:0;transform:scaleX(0)}
#sidebarToggle.open .hb span:nth-child(3){transform:translateY(-6.5px) rotate(-45deg);width:18px}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand-icon{width:36px;height:36px;border:1px solid var(--gold-dim);border-radius:50%;display:flex;align-items:center;justify-content:center}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);letter-spacing:0.04em}
.brand-name span{color:var(--gold)}
.header-right{display:flex;align-items:center;gap:10px}
.header-cart-pill{display:flex;align-items:center;gap:8px;padding:7px 16px;border:1px solid var(--border);border-radius:100px;background:transparent;color:var(--muted);font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.06em;text-decoration:none;transition:color 0.2s,border-color 0.2s,background 0.2s;cursor:pointer}
.header-cart-pill:hover{color:var(--gold);border-color:var(--gold-dim);background:rgba(201,168,76,0.06)}
.header-cart-count{background:var(--gold);color:#1a1400;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 4px}

/* HERO */
.hero{position:relative;z-index:1;text-align:center;padding:90px 32px 70px;max-width:700px;margin:0 auto}
.hero-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:var(--gold);margin-bottom:22px}
.hero-eyebrow::before,.hero-eyebrow::after{content:'';width:28px;height:1px;background:var(--gold-dim)}
.hero h1{font-family:'Cormorant Garamond',serif;font-size:clamp(44px,6vw,72px);font-weight:700;line-height:1.08;color:var(--cream);letter-spacing:-0.01em;margin-bottom:18px}
.hero h1 em{font-style:italic;color:var(--gold)}
.hero p{font-size:15px;color:var(--muted);line-height:1.7;font-weight:300}

/* FILTER */
.filter-bar{position:relative;z-index:1;display:flex;justify-content:center;gap:8px;padding:0 32px 48px;flex-wrap:wrap}
.filter-btn{font-family:'Jost',sans-serif;font-size:12px;font-weight:500;letter-spacing:0.1em;text-transform:uppercase;padding:8px 20px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:all 0.25s ease}
.filter-btn:hover,.filter-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.07)}

/* MENU GRID */
.menu-section{position:relative;z-index:1;max-width:1200px;margin:0 auto;padding:0 32px 60px}
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:24px;transition:opacity 0.25s ease}
.menu-grid.fading{opacity:0}

/* CARD */
.menu-card{background:var(--card);border:1px solid var(--border);border-radius:4px;overflow:hidden;display:flex;flex-direction:column;opacity:0;transform:translateY(18px);transition:opacity 0.35s ease,transform 0.35s ease,border-color 0.3s ease,box-shadow 0.3s ease}
.menu-card.visible{opacity:1;transform:translateY(0)}
.menu-card:hover{border-color:var(--gold-dim);box-shadow:0 20px 50px rgba(0,0,0,0.5),0 0 0 1px rgba(201,168,76,0.08)}
.menu-card.visible:hover{transform:translateY(-6px)}
.menu-card.out-of-stock{opacity:0.55}
.menu-card.out-of-stock.visible:hover{transform:none}

.card-img-wrap{position:relative;height:210px;overflow:hidden;background:var(--surface)}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;display:block}
.menu-card:hover .card-img-wrap img{transform:scale(1.06)}
.card-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(0,0,0,0.5) 100%)}
.img-placeholder{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:var(--muted);font-size:13px;letter-spacing:0.05em}
.img-placeholder svg{opacity:0.3}
.oos-badge{position:absolute;top:12px;left:12px;z-index:2;background:rgba(192,57,43,0.9);color:#fff;font-size:10px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;padding:4px 10px;border-radius:3px}

.card-body{padding:22px 24px 24px;display:flex;flex-direction:column;flex:1;gap:6px}
.card-category{font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:var(--gold);font-weight:500}
.card-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--cream);line-height:1.2;margin-bottom:4px}
.card-desc{font-size:13px;color:var(--muted);line-height:1.6;font-weight:300;flex:1;margin-bottom:10px}

/* STOCK BAR */
.stock-wrap{margin-bottom:10px}
.stock-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px}
.stock-label{font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)}
.stock-text{font-size:10px;font-weight:500}
.stock-text.s-green{color:var(--green-lt)}
.stock-text.s-amber{color:var(--amber)}
.stock-text.s-red{color:var(--red)}
.stock-bar-bg{height:3px;background:var(--border);border-radius:2px;overflow:hidden}
.stock-bar-fill{height:100%;border-radius:2px;transition:width 0.4s ease}
.stock-bar-fill.s-green{background:var(--green-lt)}
.stock-bar-fill.s-amber{background:var(--amber)}
.stock-bar-fill.s-red{background:var(--red)}

.card-footer{display:flex;align-items:center;justify-content:space-between;padding-top:16px;border-top:1px solid var(--border);margin-top:auto}
.card-price{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold);letter-spacing:-0.01em}
.card-price span{font-size:14px;font-weight:400;font-family:'Jost',sans-serif;color:var(--muted);margin-right:2px}

.add-btn{display:flex;align-items:center;gap:8px;padding:10px 18px;background:var(--green);border:none;border-radius:3px;font-family:'Jost',sans-serif;font-size:12.5px;font-weight:500;letter-spacing:0.08em;text-transform:uppercase;color:#fff;cursor:pointer;transition:background 0.2s,transform 0.15s;position:relative;overflow:hidden}
.add-btn:hover{background:var(--green-lt)}
.add-btn:active{transform:scale(0.95)}
.add-btn.added{background:var(--gold);color:#000}
.add-btn:disabled{background:var(--muted);cursor:not-allowed;opacity:0.5}
.add-btn .ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);transform:scale(0);animation:ripple 0.5s linear;pointer-events:none}
@keyframes ripple{to{transform:scale(4);opacity:0}}

/* EMPTY */
.empty-state{text-align:center;padding:80px 20px;color:var(--muted)}
.empty-state svg{opacity:0.2;margin-bottom:20px}
.empty-state h3{font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--text);margin-bottom:8px}

/* PAGINATION */
.pagination-wrap{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:14px;padding:32px 32px 80px}
.pagination-info{font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted)}
.pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center}
.page-btn{font-family:'Jost',sans-serif;font-size:13px;font-weight:500;min-width:38px;height:38px;padding:0 12px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all 0.2s ease}
.page-btn:hover:not(:disabled){border-color:var(--gold-dim);color:var(--gold);background:rgba(201,168,76,0.07)}
.page-btn.active{border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.12)}
.page-btn:disabled{opacity:0.3;cursor:not-allowed}
.page-ellipsis{color:var(--muted);padding:0 4px;font-size:14px;line-height:38px}

/* TOAST */
#toast{position:fixed;bottom:30px;right:30px;z-index:999;background:var(--card);border:1px solid var(--gold-dim);border-radius:4px;padding:14px 20px;display:flex;align-items:center;gap:12px;font-size:14px;color:var(--cream);box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:translateY(20px);opacity:0;transition:all 0.3s ease;pointer-events:none}
#toast.show{transform:translateY(0);opacity:1}
#toast .t-icon{color:var(--green-lt);flex-shrink:0}
#toast.error .t-icon{color:var(--red)}

/* FOOTER */
footer{position:relative;z-index:1;border-top:1px solid var(--border);padding:30px 32px;text-align:center}
footer p{font-size:12px;color:var(--muted);letter-spacing:0.06em}
footer p span{color:var(--gold-dim)}

@media(max-width:640px){
    .header-inner{padding:0 16px}
    .brand-name{font-size:18px}
    .hero{padding:60px 20px 50px}
    .menu-section{padding:0 16px 40px}
    .menu-grid{grid-template-columns:1fr;gap:16px}
    .pagination-wrap{padding:24px 16px 60px}
}
</style>
</head>
<body>

<div id="sidebarOverlay"></div>

<aside id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sb-header">
        <div class="sb-brand">
            <div class="sb-brand-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            </div>
            <div class="sb-brand-name">AyosCoffeeNegosyo<span>Est. 2026</span></div>
        </div>
        <button class="sb-close" id="sidebarClose" aria-label="Close sidebar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <nav class="sb-nav">
        <div class="sb-nav-label">Navigation</div>
        <a href="index.php" class="sb-link active">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Menu
        </a>
        <a href="profile.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </a>
        <a href="dashboard.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <div class="sb-divider"></div>
        <div class="sb-nav-label">Orders</div>
        <a href="orders.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            My Orders
        </a>
        <a href="cart.php" class="sb-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Cart
            <span class="sb-badge" id="sidebarCartCount">0</span>
        </a>
    </nav>
    <div class="sb-footer">
        <a href="log-out.php" class="sb-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>

<header>
    <div class="header-inner">
        <div class="header-left">
            <button id="sidebarToggle" aria-label="Open sidebar" aria-expanded="false">
                <div class="hb"><span></span><span></span><span></span></div>
            </button>
            <a href="index.php" class="brand">
                <div class="brand-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c9a84c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                </div>
                <span class="brand-name">My <span>AyosCoffeeNegosyo</span></span>
            </a>
        </div>
        <div class="header-right">
            <a href="cart.php" class="header-cart-pill" aria-label="View cart">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="header-cart-count" id="cartCount">0</span>
            </a>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-eyebrow">Today's Selection</div>
    <h1>Our <em>Menu</em></h1>
    <p>Carefully crafted dishes made with the finest ingredients.<br>Order your favorites and enjoy a taste worth remembering.</p>
</section>

<div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All Items</button>
    <button class="filter-btn" data-filter="mains">Mains</button>
    <button class="filter-btn" data-filter="sides">Sides</button>
    <button class="filter-btn" data-filter="drinks">Drinks</button>
    <button class="filter-btn" data-filter="desserts">Desserts</button>
</div>

<main class="menu-section">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
            <h3>No dishes available yet.</h3>
            <p>Check back soon — the kitchen is preparing something special.</p>
        </div>
    <?php else: ?>
        <div class="menu-grid" id="menuGrid">
            <?php foreach ($products as $row):
                // ── PER-USER STOCK ──
                $maxStock    = 100;
                $alreadyUsed = (int)($userOrderedMap[(int)$row['id']] ?? 0);
                $cartQty     = (int)($_SESSION['cart'][(int)$row['id']] ?? 0);
                $stock       = max(0, $maxStock - $alreadyUsed - $cartQty);
                $reorder     = (int)($row['reorder_level'] ?? 10);
                $pct         = max(0, min(100, ($stock / 100) * 100));
                $isOos       = $stock <= 0;
                $isLow       = !$isOos && $stock <= $reorder;
                $cls         = $isOos ? 's-red' : ($isLow ? 's-amber' : 's-green');
                $txt         = $isOos ? 'Out of stock' : ($isLow ? "Low — {$stock} left" : "{$stock} in stock");
            ?>
            <div class="menu-card <?= $isOos ? 'out-of-stock' : '' ?>"
                 data-category="<?= htmlspecialchars($row['category'] ?? 'mains') ?>"
                 data-stock="<?= $stock ?>">

                <div class="card-img-wrap">
                    <?php if ($isOos): ?><div class="oos-badge">Out of Stock</div><?php endif; ?>
                    <?php if (!empty($row['image'])): ?>
                        <img src="<?= htmlspecialchars($row['image']) ?>"
                             alt="<?= htmlspecialchars($row['name']) ?>"
                             loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><svg width=\'40\' height=\'40\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M3 11l19-9-9 19-2-8-8-2z\'/></svg><span>No Image</span></div>'">
                    <?php else: ?>
                        <div class="img-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if (!empty($row['category'])): ?>
                        <div class="card-category"><?= htmlspecialchars($row['category']) ?></div>
                    <?php endif; ?>
                    <h3 class="card-name"><?= htmlspecialchars($row['name']) ?></h3>
                    <p class="card-desc"><?= !empty($row['description']) ? htmlspecialchars($row['description']) : 'A signature dish from our kitchen, prepared fresh to order.' ?></p>

                    <!-- Stock Bar -->
                    <div class="stock-wrap">
                        <div class="stock-top">
                            <span class="stock-label">Stock</span>
                            <span class="stock-text <?= $cls ?>"><?= $txt ?></span>
                        </div>
                        <div class="stock-bar-bg">
                            <div class="stock-bar-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="card-price"><span>₱</span><?= number_format((float)$row['price'], 2) ?></div>
                        <?php if ($isOos): ?>
                            <button class="add-btn" disabled>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                Unavailable
                            </button>
                        <?php else: ?>
                            <button class="add-btn" onclick="addToCart(<?= (int)$row['id'] ?>, this)" data-id="<?= (int)$row['id'] ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Add
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div class="pagination-wrap" id="paginationWrap" style="display:none;">
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination" id="pagination"></div>
</div>

<div id="toast">
    <svg class="t-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="toastMsg">Added to cart!</span>
</div>

<footer><p>© 2026 <span>My AyosCoffeeNegosyo</span> — All rights reserved.</p></footer>

<script>
// Sidebar
const sidebar=document.getElementById('sidebar'),sidebarOverlay=document.getElementById('sidebarOverlay'),sidebarToggle=document.getElementById('sidebarToggle'),sidebarClose=document.getElementById('sidebarClose');
function openSidebar(){sidebar.classList.add('open');sidebarOverlay.classList.add('active');sidebarToggle.classList.add('open');sidebarToggle.setAttribute('aria-expanded','true');document.body.style.overflow='hidden'}
function closeSidebar(){sidebar.classList.remove('open');sidebarOverlay.classList.remove('active');sidebarToggle.classList.remove('open');sidebarToggle.setAttribute('aria-expanded','false');document.body.style.overflow=''}
sidebarToggle.addEventListener('click',()=>sidebar.classList.contains('open')?closeSidebar():openSidebar());
sidebarClose.addEventListener('click',closeSidebar);
sidebarOverlay.addEventListener('click',closeSidebar);
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&sidebar.classList.contains('open'))closeSidebar()});

// Grid
const ITEMS_PER_PAGE=6;
let currentPage=1,currentFilter='all';
const allCards=Array.from(document.querySelectorAll('.menu-card'));
const grid=document.getElementById('menuGrid');

function renderGrid(animate=true){
    const filtered=currentFilter==='all'?allCards:allCards.filter(c=>c.dataset.category===currentFilter);
    const totalItems=filtered.length,totalPages=Math.max(1,Math.ceil(totalItems/ITEMS_PER_PAGE));
    if(currentPage>totalPages)currentPage=totalPages;
    const start=(currentPage-1)*ITEMS_PER_PAGE,end=start+ITEMS_PER_PAGE,pageCards=filtered.slice(start,end);
    if(animate){
        grid.classList.add('fading');
        setTimeout(()=>{applyVis(allCards,pageCards);grid.classList.remove('fading');pageCards.forEach((c,i)=>{c.classList.remove('visible');setTimeout(()=>c.classList.add('visible'),i*60)})},250);
    }else{applyVis(allCards,pageCards);pageCards.forEach((c,i)=>setTimeout(()=>c.classList.add('visible'),i*60))}
    renderPagUI(totalItems,totalPages,start,end);
}

function applyVis(all,pageCards){all.forEach(c=>{c.classList.remove('visible');c.style.display='none'});pageCards.forEach(c=>c.style.display='')}

function renderPagUI(totalItems,totalPages,start,end){
    const wrap=document.getElementById('paginationWrap'),infoEl=document.getElementById('paginationInfo'),paginEl=document.getElementById('pagination');
    wrap.style.display=totalPages<=1?'none':'flex';
    infoEl.textContent='Showing '+(start+1)+'–'+Math.min(end,totalItems)+' of '+totalItems+' items';
    paginEl.innerHTML='';
    const prev=mkBtn('←',currentPage===1);
    prev.addEventListener('click',()=>{currentPage--;renderGrid();scrollUp()});paginEl.appendChild(prev);
    for(let i=1;i<=totalPages;i++){
        if(totalPages>7&&i>2&&i<totalPages-1&&Math.abs(i-currentPage)>1){if(i===3||i===totalPages-2){const el=document.createElement('span');el.className='page-ellipsis';el.textContent='…';paginEl.appendChild(el)}continue}
        const btn=mkBtn(i,false,i===currentPage);btn.addEventListener('click',()=>{currentPage=i;renderGrid();scrollUp()});paginEl.appendChild(btn);
    }
    const next=mkBtn('→',currentPage===totalPages);next.addEventListener('click',()=>{currentPage++;renderGrid();scrollUp()});paginEl.appendChild(next);
}

function mkBtn(label,disabled,active){const btn=document.createElement('button');btn.className='page-btn'+(active?' active':'');btn.textContent=label;btn.disabled=!!disabled;return btn}
function scrollUp(){document.querySelector('.filter-bar').scrollIntoView({behavior:'smooth',block:'start'})}

document.querySelectorAll('.filter-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');currentFilter=btn.dataset.filter;currentPage=1;renderGrid(true);
    });
});

// Cart badge
function updateCartBadge(){
    fetch('cart_handler.php?count=1').then(r=>r.json()).then(data=>{
        const c=data.count||0;
        document.getElementById('cartCount').textContent=c;
        document.getElementById('sidebarCartCount').textContent=c;
    }).catch(()=>{});
}
updateCartBadge();

// Add to cart with live stock update
function addToCart(id,btn){
    const ripple=document.createElement('span');ripple.className='ripple';
    const rect=btn.getBoundingClientRect(),size=Math.max(rect.width,rect.height);
    ripple.style.cssText='width:'+size+'px;height:'+size+'px;left:'+((rect.width-size)/2)+'px;top:'+((rect.height-size)/2)+'px';
    btn.appendChild(ripple);setTimeout(()=>ripple.remove(),600);
    btn.disabled=true;

    fetch('cart_handler.php?add='+id).then(r=>r.json()).then(data=>{
        if(data.success){
            const count=data.count||0;
            document.getElementById('cartCount').textContent=count;
            document.getElementById('sidebarCartCount').textContent=count;

            // Live stock bar update
            const card=btn.closest('.menu-card');
            let stock=Math.max(0,parseInt(card.dataset.stock||'100')-1);
            card.dataset.stock=stock;
            updateStockUI(card,stock);

            const orig=btn.innerHTML;
            btn.classList.add('added');
            btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Added';
            setTimeout(()=>{
                btn.classList.remove('added');
                if(stock<=0){
                    btn.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg> Unavailable';
                    btn.disabled=true;
                }else{btn.innerHTML=orig;btn.disabled=false}
            },1400);
            showToast('Added to cart!',false);
        }else{btn.disabled=false;showToast(data.message||'Error — please try again.',true)}
    }).catch(()=>{btn.disabled=false;showToast('Error — please try again.',true)});
}

function updateStockUI(card,stock){
    const reorder=10,pct=Math.max(0,Math.min(100,(stock/100)*100));
    const isOos=stock<=0,isLow=!isOos&&stock<=reorder;
    const cls=isOos?'s-red':(isLow?'s-amber':'s-green');
    const txt=isOos?'Out of stock':(isLow?'Low — '+stock+' left':stock+' in stock');
    const fill=card.querySelector('.stock-bar-fill'),stxt=card.querySelector('.stock-text');
    if(fill){fill.style.width=pct+'%';fill.className='stock-bar-fill '+cls}
    if(stxt){stxt.textContent=txt;stxt.className='stock-text '+cls}
    if(isOos){
        card.classList.add('out-of-stock');
        const iw=card.querySelector('.card-img-wrap');
        if(iw&&!iw.querySelector('.oos-badge')){const b=document.createElement('div');b.className='oos-badge';b.textContent='Out of Stock';iw.appendChild(b)}
    }
}

// Toast
let toastTimer;
function showToast(msg,isError=false){
    const toast=document.getElementById('toast');
    document.getElementById('toastMsg').textContent=msg;
    toast.className=isError?'error':'';toast.classList.add('show');
    clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.classList.remove('show'),2500);
}

renderGrid(false);
</script>
</body>
</html>
