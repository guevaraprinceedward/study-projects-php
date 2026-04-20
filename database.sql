CREATE TABLE products (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100),
 price DECIMAL(10,2),
 image TEXT
);

CREATE TABLE orders (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT,
 total DECIMAL(10,2),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
 id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT,
 product_id INT,
 quantity INT
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL
);

ON DUPLICATE KEY UPDATE
  name          = VALUES(name),
  price         = VALUES(price),
  category      = VALUES(category),
  description   = VALUES(description),
  stock         = VALUES(stock),
  reorder_level = VALUES(reorder_level);

-- Verify
SELECT id, name, category, price, stock FROM products ORDER BY id;

-- ORDER-ITEMS_DATABASE SQL (INSERT) 
SELECT oi.*, p.name 
FROM order_items oi 
LEFT JOIN products p ON oi.product_id = p.id 
LIMIT 5;

-- RESTAURANT_DATABASE SQL (INSERT)
-- ══════════════════════════════════════════════════════
--  AyosCoffeeNegosyo — Insert all 48 products with images
--  Run this in phpMyAdmin > restaurant_db > SQL tab
-- ══════════════════════════════════════════════════════

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS category      VARCHAR(100) NOT NULL DEFAULT 'general',
  ADD COLUMN IF NOT EXISTS description   TEXT,
  ADD COLUMN IF NOT EXISTS image         VARCHAR(500) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS stock         INT NOT NULL DEFAULT 100,
  ADD COLUMN IF NOT EXISTS reorder_level INT NOT NULL DEFAULT 10;

INSERT INTO products (id, name, price, category, description, image, stock, reorder_level) VALUES
(1,  'Espresso',              85,  'mains',    'Bold and concentrated shot of pure espresso, brewed from freshly ground arabica beans.',        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Espresso.Product.png',              100, 10),
(2,  'Americano',             100, 'mains',    'Espresso diluted with hot water for a smooth, full-bodied black coffee experience.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Americano.Product-V1.png',          100, 10),
(3,  'Cappuccino',            130, 'mains',    'Equal parts espresso, steamed milk, and thick velvety foam — a classic Italian favourite.',      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cappuccino.Product.png',            100, 10),
(4,  'Caffe Latte',           140, 'mains',    'Silky steamed milk poured over a double shot of espresso with a light layer of foam.',           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caffe.Latte.png',                 100, 10),
(5,  'Flat White',            145, 'mains',    'Stronger and creamier than a latte — micro-foamed milk over a rich ristretto shot.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Flat-White.Product.png',           100, 10),
(6,  'Caramel Macchiato',     155, 'mains',    'Vanilla-infused steamed milk, espresso, and a generous drizzle of rich caramel.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caramel-Macchiato.Product.png',    100, 10),
(7,  'Mocha',                 150, 'mains',    'Espresso blended with chocolate syrup and steamed milk, topped with whipped cream.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Mocha.Product.png',                100, 10),
(8,  'Hazelnut Latte',        155, 'mains',    'Smooth latte infused with rich hazelnut syrup and topped with silky milk foam.',                 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Hazelnut-Latte.Product.png',       100, 10),
(9,  'White Chocolate Mocha', 160, 'mains',    'Espresso blended with creamy white chocolate and steamed milk, topped with whipped cream.',      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/White-Chocolate-Mocha.Product.png', 100, 10),
(10, 'Spanish Latte',         150, 'mains',    'Sweet and creamy latte made with condensed milk for a richer flavor profile.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Spanish-Latte.Product.png',        100, 10),
(11, 'Vanilla Latte',         145, 'mains',    'Classic latte enhanced with smooth vanilla syrup and steamed milk.',                             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Vanilla-Latte.Product.png',        100, 10),
(12, 'Cortado',               135, 'mains',    'Equal parts espresso and warm milk, cutting the acidity for a balanced, velvety sip.',           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cortado-Latte.Product.png',             100, 10),
(13, 'Cold Brew',             160, 'drinks',   'Steeped 18 hours in cold water for a smooth, naturally sweet concentrate over ice.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cold-Brew.Product.png', 100, 10),
(14, 'Iced Americano',        110, 'drinks',   'Double espresso pulled over ice and chilled water — clean, crisp, and refreshing.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Americano.Product.png', 100, 10),
(15, 'Iced Caramel Latte',    165, 'drinks',   'Chilled latte with caramel syrup and milk poured over a glass of crushed ice.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Caramel-Latte.Product.png', 100, 10),
(16, 'Matcha Latte',          150, 'drinks',   'Ceremonial-grade matcha whisked with oat milk — earthy, smooth, and lightly sweet.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Matcha-Latte.Product.png', 100, 10),
(17, 'Iced Mocha',            155, 'drinks',   'Chilled blend of espresso, chocolate syrup, and milk served over ice.',                          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Mocha.Product.png', 100, 10),
(18, 'Dirty Matcha',          165, 'drinks',   'Layered drink of matcha and espresso with milk for a bold, earthy caffeine kick.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Dirty-Matcha.Product.png', 100, 10),
(19, 'Espresso Tonic',        150, 'drinks',   'Refreshing mix of espresso and tonic water served over ice with citrus notes.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Espresso-Tonic.Product.png', 100, 10),
(20, 'Iced Hazelnut Latte',   165, 'drinks',   'Chilled hazelnut-infused latte poured over ice for a nutty, refreshing treat.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Hazelnut-Latte.Product.png', 100, 10),
(21, 'Strawberry Lemonade',   120, 'drinks',   'Freshly squeezed lemonade blended with sweet strawberry puree over ice.',                        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Strawberry-Lemonade.Product.png', 100, 10),
(22, 'Sparkling Water',       80,  'drinks',   'Chilled sparkling mineral water, perfectly refreshing on its own or with a meal.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Sparkling-Water.Product.png', 100, 10),
(23, 'Mango Soda Float',      145, 'drinks',   'Sweet mango soda topped with a scoop of vanilla ice cream for a tropical float.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Mango-Soda-Float.Product.png', 100, 10),
(24, 'Hot Chocolate',         130, 'drinks',   'Rich and creamy hot chocolate made with real dark cocoa and steamed milk.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Hot-Chocolate.Product.png', 100, 10),
(25, 'Croissant',             95,  'sides',    'Buttery, flaky all-butter croissant baked fresh every morning.',                                 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Croissant.Product.png', 100, 10),
(26, 'Banana Bread',          80,  'sides',    'Moist homemade banana bread with a golden crust, perfect with your morning coffee.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Banana-Bread.Product.png', 100, 10),
(27, 'Blueberry Muffin',      85,  'sides',    'Fluffy muffin bursting with fresh blueberries and a crumbly sugar topping.',                    'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Blueberry-Muffin.Product.png', 100, 10),
(28, 'Cinnamon Roll',         110, 'sides',    'Soft, sweet pastry swirled with cinnamon sugar and topped with light icing.',                   '', 100, 10),
(29, 'Chocolate Chip Cookie', 70,  'sides',    'Freshly baked cookie loaded with rich chocolate chips and a soft chewy center.',                 '', 100, 10),
(30, 'Ham and Cheese Panini', 140, 'sides',    'Grilled panini stuffed with savory ham and melted cheese.',                                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Ham-and-Cheese-Panini.Product.png', 100, 10),
(31, 'Breakfast Sandwich',    150, 'sides',    'Toasted sandwich with egg, cheese, and choice of ham or bacon for a filling start.',             '', 100, 10),
(32, 'Avocado Toast',         160, 'sides',    'Toasted sourdough topped with smashed avocado, sea salt, and a drizzle of olive oil.',          '', 100, 10),
(33, 'Cheese Danish',         95,  'sides',    'Flaky pastry filled with sweet cream cheese and a light vanilla glaze.',                        '', 100, 10),
(34, 'Granola Bar',           65,  'sides',    'House-made oat granola bar packed with honey, seeds, and dried fruits.',                        '', 100, 10),
(35, 'Egg Salad Sandwich',    130, 'sides',    'Creamy egg salad on soft white bread, seasoned with herbs and a touch of mustard.',             '', 100, 10),
(36, 'Spinach and Feta Wrap', 145, 'sides',    'Whole wheat wrap filled with sauteed spinach, feta cheese, and sun-dried tomatoes.',            '', 100, 10),
(37, 'Tiramisu',              130, 'desserts', 'Classic Italian dessert with espresso-soaked ladyfingers and mascarpone cream.',                 '', 100, 10),
(38, 'Chocolate Lava Cake',   145, 'desserts', 'Warm dark chocolate cake with a molten centre, served with a dusting of cocoa powder.',         '', 100, 10),
(39, 'Affogato',              140, 'desserts', 'Vanilla ice cream drowned in a hot shot of espresso for a perfect hot-and-cold dessert.',       '', 100, 10),
(40, 'Cheesecake',            135, 'desserts', 'Creamy baked cheesecake with a buttery graham crust.',                                          '', 100, 10),
(41, 'Brownies',              90,  'desserts', 'Fudgy chocolate brownies with a crackly top and rich cocoa flavor.',                            '', 100, 10),
(42, 'Creme Brulee',          150, 'desserts', 'Silky vanilla custard topped with a perfectly caramelised sugar crust.',                        '', 100, 10),
(43, 'Mango Panna Cotta',     130, 'desserts', 'Silky Italian panna cotta crowned with a vibrant fresh mango coulis.',                          '', 100, 10),
(44, 'Strawberry Shortcake',  140, 'desserts', 'Light sponge cake layered with fresh strawberries and whipped cream.',                          '', 100, 10),
(45, 'Macarons (3 pcs)',      120, 'desserts', 'Delicate French macarons in assorted flavours — crisp shell, chewy centre.',                    '', 100, 10),
(46, 'Chocolate Mousse',      125, 'desserts', 'Airy dark chocolate mousse served chilled with a dusting of cocoa.',                            '', 100, 10),
(47, 'Leche Flan',            95,  'desserts', 'Classic Filipino-style caramel custard flan, silky smooth with a golden caramel top.',          '', 100, 10),
(48, 'Ube Cheesecake',        145, 'desserts', 'Creamy ube-flavoured cheesecake with a rich purple hue and buttery biscuit base.',              '', 100, 10)

ON DUPLICATE KEY UPDATE
  name          = VALUES(name),
  price         = VALUES(price),
  category      = VALUES(category),
  description   = VALUES(description),
  image         = VALUES(image),
  stock         = VALUES(stock),
  reorder_level = VALUES(reorder_level);

SELECT id, name, category, image, stock FROM products ORDER BY id;
