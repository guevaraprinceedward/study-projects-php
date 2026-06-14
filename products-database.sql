-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 04:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `reorder_level` int(11) DEFAULT 10,
  `sku` varchar(100) DEFAULT NULL,
  `branch` varchar(20) DEFAULT 'laguna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `reorder_level`, `sku`, `branch`) VALUES

-- =============================================
-- LAGUNA BRANCH PRODUCTS
-- NOTE: category uses 'mains' for coffee here.
-- Change to 'coffee' if you want to match Manila.
-- =============================================

-- COFFEE (category: 'mains') --

(1,  'Espresso',             85.00,  'mains', 'Bold and concentrated shot of pure espresso.',                 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Espresso.Product.png',                    100, 10, NULL, 'laguna'),
(2,  'Americano',           100.00,  'mains', 'Espresso diluted with hot water for a smooth black coffee.',   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Americano.Product-V1.png',                100, 10, NULL, 'laguna'),
(3,  'Cappuccino',          130.00,  'mains', 'Equal parts espresso, steamed milk, and thick velvety foam.',  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cappuccino.Product.png',                 99, 10, NULL, 'laguna'),
(4,  'Caffe Latte',         140.00,  'mains', 'Silky steamed milk poured over a double shot of espresso.',    'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caffe.Latte.png',                       100, 10, NULL, 'laguna'),
(5,  'Flat White',          145.00,  'mains', 'Stronger and creamier than a latte.',                          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Flat-Latte.Product.png',               100, 10, NULL, 'laguna'),
(6,  'Caramel Macchiato',   155.00,  'mains', 'Vanilla-infused steamed milk, espresso, and caramel drizzle.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caramel-Macchiato.Product.png',        98, 10, NULL, 'laguna'),
(7,  'Mocha',               150.00,  'mains', 'Espresso blended with chocolate syrup and steamed milk.',      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Mocha.Product.png',                      100, 10, NULL, 'laguna'),
(8,  'Hazelnut Latte',      155.00,  'mains', 'Smooth latte infused with rich hazelnut syrup.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Hazelnut-Latte.Product.png',            100, 10, NULL, 'laguna'),
(9,  'White Chocolate Mocha',160.00, 'mains', 'Espresso with creamy white chocolate and steamed milk.',       'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/White-Chocolate-Mocha.Product.png',      100, 10, NULL, 'laguna'),
(10, 'Spanish Latte',       150.00,  'mains', 'Sweet and creamy latte made with condensed milk.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Spanish.Latte.Orig.png',                100, 10, NULL, 'laguna'),
(11, 'Vanilla Latte',       145.00,  'mains', 'Classic latte enhanced with smooth vanilla syrup.',            'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Vanilla.Latte.Orig.png',                100, 10, NULL, 'laguna'),
(12, 'Cortado',             135.00,  'mains', 'Equal parts espresso and warm milk.',                          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cortado.Latte.Orig.png',                100, 10, NULL, 'laguna'),

-- DRINKS --

(13, 'Cold Brew',           160.00, 'drinks', 'Steeped 18 hours in cold water.',                              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cold.Brew.png',                  100, 10, NULL, 'laguna'),
(14, 'Iced Americano',      110.00, 'drinks', 'Double espresso over ice and chilled water.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Iced.Americano.png',              100, 10, NULL, 'laguna'),
(15, 'Iced Caramel Latte',  165.00, 'drinks', 'Chilled latte with caramel syrup over ice.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Iced.Caramel.Latte.png',          100, 10, NULL, 'laguna'),
(16, 'Matcha Latte',        150.00, 'drinks', 'Ceremonial-grade matcha with oat milk.',                       'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Matcha.Latte.png',                100, 10, NULL, 'laguna'),
(17, 'Iced Mocha',          155.00, 'drinks', 'Chilled espresso with chocolate syrup over ice.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Iced.Mocha.png',                  100, 10, NULL, 'laguna'),
(18, 'Dirty Matcha',        165.00, 'drinks', 'Matcha and espresso with milk.',                               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Dirty.Matcha.png',                  0, 10, NULL, 'laguna'),
(19, 'Espresso Tonic',      150.00, 'drinks', 'Espresso and tonic water over ice.',                           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Espresso.Tonic.png',              100, 10, NULL, 'laguna'),
(20, 'Iced Hazelnut Latte', 165.00, 'drinks', 'Chilled hazelnut-infused latte over ice.',                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Iced.Hazelnut.Latte.png',         100, 10, NULL, 'laguna'),
(21, 'Strawberry Lemonade', 120.00, 'drinks', 'Lemonade blended with strawberry purée.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Strawberry.Lemonade.png',          100, 10, NULL, 'laguna'),
(22, 'Sparkling Water',      80.00, 'drinks', 'Chilled sparkling mineral water.',                              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Sparkling.Water.png',              100, 10, NULL, 'laguna'),
(23, 'Mango Soda Float',    145.00, 'drinks', 'Mango soda with vanilla ice cream.',                           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Mango.Soda.Float.png',             100, 10, NULL, 'laguna'),
(24, 'Hot Chocolate',       130.00, 'drinks', 'Rich hot chocolate with steamed milk.',                        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Hot.Chocolate.png',                100, 10, NULL, 'laguna'),

-- SIDES --

(25, 'Croissant',              95.00, 'sides', 'Buttery flaky croissant baked fresh.',                         'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Croissant.png',                   100, 10, NULL, 'laguna'),
(26, 'Banana Bread',           80.00, 'sides', 'Moist homemade banana bread.',                                 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Banana.Bread.png',                100, 10, NULL, 'laguna'),
(27, 'Blueberry Muffin',       85.00, 'sides', 'Fluffy muffin with fresh blueberries.',                        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Blueberry.Muffin.png',             100, 10, NULL, 'laguna'),
(28, 'Cinnamon Roll',         110.00, 'sides', 'Soft pastry with cinnamon sugar and icing.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cinnamon.Roll.png',                100, 10, NULL, 'laguna'),
(29, 'Chocolate Chip Cookie',  70.00, 'sides', 'Freshly baked cookie with chocolate chips.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Chocolate.Chip.Cookie.png',        100, 10, NULL, 'laguna'),
(30, 'Ham and Cheese Panini', 140.00, 'sides', 'Grilled panini with ham and cheese.',                          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Ham-and-Cheese.Panini.png',        100, 10, NULL, 'laguna'),
(31, 'Breakfast Sandwich',    150.00, 'sides', 'Toasted sandwich with egg and cheese.',                        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Breakfast.Sandwith.with.Black-Coffee.png', 100, 10, NULL, 'laguna'),
(32, 'Avocado Toast',         160.00, 'sides', 'Sourdough with smashed avocado.',                              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Avocado.Toast.with.Black-Coffee.png',  100, 10, NULL, 'laguna'),
(33, 'Cheese Danish',          95.00, 'sides', 'Flaky pastry with cream cheese filling.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cheese.Danish.png',                100, 10, NULL, 'laguna'),
(34, 'Granola Bar',            65.00, 'sides', 'House-made oat granola bar.',                                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Granola.Bar.png',                  100, 10, NULL, 'laguna'),
-- FIX Issue 3: corrected description for Egg Salad Sandwich (was copy-pasted from Granola Bar)
(35, 'Egg Salad Sandwich',    130.00, 'sides', 'Egg salad on toasted bread with lettuce.',                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Egg.Salad.Sandwich.png',           100, 10, NULL, 'laguna'),
(36, 'Spinach and Feta Wrap', 145.00, 'sides', 'Whole wheat wrap with spinach and feta.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Spinach-and-Feta.Wrap.png',        100, 10, NULL, 'laguna'),

-- DESSERTS --

(37, 'Tiramisu',              130.00, 'desserts', 'Classic Italian tiramisu.',                                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Tiramisu.png',                    100, 10, NULL, 'laguna'),
(38, 'Chocolate Lava Cake',   145.00, 'desserts', 'Warm chocolate cake with molten centre.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Chocolate.Lava.Cake.png',          100, 10, NULL, 'laguna'),
(39, 'Affogato',              140.00, 'desserts', 'Vanilla ice cream with hot espresso.',                       'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Affogato.Coffee.png',              100, 10, NULL, 'laguna'),
(40, 'Cheesecake',            135.00, 'desserts', 'Creamy baked cheesecake.',                                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cheesecake.png',                   100, 10, NULL, 'laguna'),
(41, 'Brownies',               90.00, 'desserts', 'Fudgy chocolate brownies.',                                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Brownies.png',                     100, 10, NULL, 'laguna'),
(42, 'Creme Brulee',          150.00, 'desserts', 'Silky vanilla custard with caramelised sugar.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Creme.Brulee.png',                 100, 10, NULL, 'laguna'),
(43, 'Mango Panna Cotta',     130.00, 'desserts', 'Silky panna cotta with mango coulis.',                       'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Mango.Panna.Cotta.png',            100, 10, NULL, 'laguna'),
(44, 'Strawberry Shortcake',  140.00, 'desserts', 'Sponge cake with strawberries and cream.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Strawberry.Shortcake.png',         100, 10, NULL, 'laguna'),
(45, 'Macarons (3 pcs)',      120.00, 'desserts', 'French macarons in assorted flavours.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.French.Macarons.png',              100, 10, NULL, 'laguna'),
(46, 'Chocolate Mousse',      125.00, 'desserts', 'Airy dark chocolate mousse.',                                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Chocolate.Mousse.png',             100, 10, NULL, 'laguna'),
(47, 'Leche Flan',             95.00, 'desserts', 'Classic Filipino caramel custard flan.',                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Leche.Flan.png',                   100, 10, NULL, 'laguna'),
(48, 'Ube Cheesecake',        145.00, 'desserts', 'Creamy ube-flavoured cheesecake.',                           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Ube.Cheesecake.png',               100, 10, NULL, 'laguna'),

-- =============================================
-- MANILA BRANCH PRODUCTS
-- =============================================

-- COFFEE --

(49, 'Espresso Manila',              95.00, 'mains', 'Rich and concentrated espresso with bold flavor and a smooth finish.',                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Espresso.Manila.png',              100, 10, NULL, 'manila'),
(50, 'Americano Manila',            105.00, 'mains', 'Freshly brewed espresso blended with hot water for a clean, balanced taste.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Americano.Manila.png',            100, 10, NULL, 'manila'),
(51, 'Cappuccino Manila',           125.00, 'mains', 'Classic espresso topped with velvety steamed milk and a light foam crown.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cappuccino.Manila.png',           100, 10, NULL, 'manila'),
(52, 'Caffe Latte Manila',          135.00, 'mains', 'Smooth espresso combined with creamy steamed milk for a mellow coffee experience.',        'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cafe.Latte.Manila.png',           100, 10, NULL, 'manila'),
(53, 'Mocha Manila',                150.00, 'mains', 'A luxurious blend of espresso, chocolate, and steamed milk.',                              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Mocha.Latte.Manila.png',          100, 10, NULL, 'manila'),
(54, 'Flat White Manila',           140.00, 'mains', 'Bold espresso paired with silky microfoam for a rich and balanced texture.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Flat.White.Latte.Manila.png',    100, 10, NULL, 'manila'),
(55, 'Spanish Latte Manila',        110.00, 'mains', 'Espresso mixed with sweetened milk for a creamy and indulgent flavor.',                    'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Spanish.Latte.Manila.png',       100, 10, NULL, 'manila'),
(56, 'Caramel Macchiato Manila',    145.00, 'mains', 'Layered espresso with steamed milk and buttery caramel notes.',                            'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Caramel.Macchiato.Manila.png',  100, 10, NULL, 'manila'),
(57, 'Vanilla Latte Manila',        145.00, 'mains', 'A comforting combination of espresso, steamed milk, and aromatic vanilla.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Vanilla.Latte.Manila.png',      100, 10, NULL, 'manila'),
(58, 'Hazelnut Latte Manila',       150.00, 'mains', 'Smooth espresso infused with rich hazelnut flavor and creamy milk.',                       'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Hazelnut.Latte.Manila.png',     100, 10, NULL, 'manila'),
(59, 'Sea Salt Latte Manila',       160.00, 'mains', 'A unique blend of espresso and milk enhanced with a delicate sea salt finish.',            'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Sea.Salt.Latte.Manila.png',      100, 10, NULL, 'manila'),
(60, 'Matcha Espresso Fusion Manila',170.00,'mains', 'Premium matcha and bold espresso combined for a vibrant and sophisticated drink.',          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Matcha.Espresso.Fusion.Manila.png', 100, 10, NULL, 'manila'),

-- CHAMPAGNES --

(61, 'Cabernet Sauvignon Manila',  2499.00, 'champagnes', 'A full-bodied red wine with rich berry notes and a smooth, elegant finish.',           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Cabernet.Sauvignon.Manila.png',  100, 10, NULL, 'manila'),
(62, 'Merlot Reserve Manila',      2399.00, 'champagnes', 'Soft and velvety red wine featuring hints of plum and dark cherry.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Merlot.Reserve.Manila.png',      100, 10, NULL, 'manila'),
(63, 'Pinot Noir Manila',          2799.00, 'champagnes', 'A refined red wine with delicate fruit flavors and subtle earthy undertones.',          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Pinot.Noir.Manila.png',          100, 10, NULL, 'manila'),
(64, 'Shiraz Signature Manila',    2599.00, 'champagnes', 'Bold and expressive with notes of blackberries, spice, and oak.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Shiraz.Signature.Manila.png',    100, 10, NULL, 'manila'),
(65, 'Malbec Estate Manila',       2399.00, 'champagnes', 'Rich and balanced red wine with dark fruit aromas and a smooth texture.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Malbec.Estate.Manila.png',       100, 10, NULL, 'manila'),
(66, 'Chardonnay Premium Manila',  2199.00, 'champagnes', 'A classic white wine with crisp apple notes and a creamy finish.',                     'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Chardonnay.Premium.Manila.png',  100, 10, NULL, 'manila'),
(67, 'Sauvignon Blanc Manila',     2099.00, 'champagnes', 'Light and refreshing with vibrant citrus flavors and a clean finish.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Sauvignon.Blanc.Manila.png',    100, 10, NULL, 'manila'),
(68, 'Pinot Grigio Manila',        2199.00, 'champagnes', 'A crisp and elegant white wine with subtle pear and floral notes.',                    'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Pinot.Grigio.Manila.png',        100, 10, NULL, 'manila'),
(69, 'Rosé Selection Manila',      1999.00, 'champagnes', 'A refreshing rosé with delicate berry flavors and a bright finish.',                   'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Rosé.Selection.Manila.png',      100, 10, NULL, 'manila'),
(70, 'Prosecco Prestige Manila',   2899.00, 'champagnes', 'Fine sparkling wine with lively bubbles and refreshing fruit notes.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Prosecco.Prestige.Manila.png',   100, 10, NULL, 'manila'),
(71, 'Champagne Brut Manila',      5999.00, 'champagnes', 'A luxurious champagne offering elegant bubbles and exceptional balance.',              'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Champagne.Brut.Manila.png',      100, 10, NULL, 'manila'),
(72, 'Sparkling Moscato Manila',   2999.00, 'champagnes', 'Sweet and refreshing sparkling wine with notes of peach and tropical fruits.',         'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Sparkling.Moscato.Manila.png',  100, 10, NULL, 'manila'),

-- MEALS --

(73, 'Grilled Salmon Manila',        1399.00, 'meals', 'Fresh salmon fillet grilled to perfection and served with seasonal vegetables.',          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Grilled.Salmon.Manila.png',          100, 10, NULL, 'manila'),
(74, 'Angus Ribeye Steak Manila',    2499.00, 'meals', 'Premium Angus ribeye steak, expertly grilled for a rich and tender bite.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Angus.Ribeye.Steak.Manila.jpeg',    100, 10, NULL, 'manila'),
(75, 'Herb-Roasted Chicken Manila',   999.00, 'meals', 'Juicy roasted chicken infused with aromatic herbs and savory seasonings.',                'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Herb-Roasted.Chicken.Manila.png',  100, 10, NULL, 'manila'),
(76, 'Truffle Mushroom Pasta Manila',  999.00, 'meals', 'Creamy pasta tossed with wild mushrooms and finished with truffle essence.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Truffle.Mushroom.Pasta.Manila.png', 100, 10, NULL, 'manila'),
(77, 'Seafood Aglio Olio Manila',    1199.00, 'meals', 'Classic aglio olio pasta combined with premium seafood and garlic-infused olive oil.',    'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Seafood.Aglio.Olio.Manila.png',   100, 10, NULL, 'manila'),
(78, 'Beef Tenderloin Manila',       2299.00, 'meals', 'Tender beef tenderloin cooked to your preference with a flavorful finish.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Beef.Tendeloin.Manila.png',       100, 10, NULL, 'manila'),
(79, 'Shrimp Alfredo Manila',        1199.00, 'meals', 'Creamy Alfredo pasta topped with succulent shrimp and parmesan cheese.',                  'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Shrimp.Alfredo.Manila.png',        100, 10, NULL, 'manila'),
(80, 'Chicken Parmigiana Manila',    1099.00, 'meals', 'Breaded chicken breast topped with tomato sauce and melted cheese.',                      'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Chicken.Parmigiana.Manila.png',    100, 10, NULL, 'manila'),
(81, 'Braised Beef Short Ribs Manila',1999.00,'meals', 'Slow-cooked beef short ribs with a rich sauce and melt-in-your-mouth texture.',           'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Braised.Beef.Short-Ribs.Manila.png', 100, 10, NULL, 'manila'),
(82, 'Lobster Mac and Cheese Manila',1699.00, 'meals', 'Creamy macaroni and cheese elevated with premium lobster meat.',                          'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Lobster.Mac-and-Cheese.Manila.png', 100, 10, NULL, 'manila'),
(83, 'Lamb Chops Manila',            2199.00, 'meals', 'Tender lamb chops seasoned and grilled for a bold and sophisticated flavor.',             'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Lamb.Chops.png',              100, 10, NULL, 'manila'),
(84, 'Wild Mushroom Risotto Manila', 2119.00, 'meals', 'Creamy Italian-style risotto featuring a blend of premium wild mushrooms.',               'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Premium.Wild.Mushroom.Risotto.Manila.png',  100, 10, NULL, 'manila');

-- DESSERTS --

(85, '')

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
