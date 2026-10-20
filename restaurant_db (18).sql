-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2026 at 04:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'owner'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`, `role`) VALUES
(1, 'admin_user', '$2y$10$j.0DrmQOb4UaGh5t1a7RPO4pekyP1EquewANTUzROYawBMDUZ5P3W', '2026-04-23 01:28:53', 'owner'),
(653, 'Webmaster', '$2y$10$3pKIQJxxIzZtNcLeGnrfku2xbOUycFftu/OkB9Sg0OyNvnVmqRxjG', '2026-09-23 02:16:20', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('user','admin') NOT NULL DEFAULT 'user',
  `actor_role` varchar(20) DEFAULT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `date` date NOT NULL,
  `photo_in` text DEFAULT NULL,
  `photo_out` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `punctuality` varchar(20) DEFAULT NULL,
  `minutes_late` int(11) DEFAULT 0,
  `override_by` varchar(100) DEFAULT NULL,
  `upload_mode` tinyint(1) DEFAULT 0,
  `shift_notes` text DEFAULT NULL,
  `customers_served` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('owner','super_admin','admin','manager','staff') NOT NULL DEFAULT 'staff',
  `position` varchar(100) DEFAULT NULL,
  `branch` varchar(20) DEFAULT 'laguna',
  `pay_type` enum('daily','hourly','monthly') NOT NULL DEFAULT 'daily',
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_hired` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `admin_id`, `user_id`, `full_name`, `role`, `position`, `branch`, `pay_type`, `rate`, `date_hired`, `status`, `created_at`, `updated_at`) VALUES
(23, 1, NULL, 'admin_user', 'owner', NULL, 'laguna', 'daily', 0.00, NULL, 'active', '2026-09-23 02:16:20', '2026-09-23 02:16:20'),
(30, 653, NULL, 'Webmaster', 'staff', NULL, 'laguna', 'daily', 0.00, NULL, 'active', '2026-09-23 02:17:57', '2026-09-23 02:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `face_descriptors`
--

CREATE TABLE `face_descriptors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('user','admin') NOT NULL DEFAULT 'user',
  `descriptor` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `guest_gcash` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `branch` varchar(20) DEFAULT 'laguna',
  `receipt_code` varchar(40) DEFAULT NULL,
  `payment_ref` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `created_at`, `status`, `notes`, `guest_name`, `guest_phone`, `guest_gcash`, `payment_method`, `branch`, `receipt_code`, `payment_ref`) VALUES
(30, 12, 890.00, '2026-09-23 02:23:50', 'pending', 'Please add some extra sauce and napkins on the side. Also, the sauce please make it extra creamy', NULL, NULL, NULL, 'cash', 'laguna', 'AYS-20260923-00030', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `addons` text DEFAULT NULL,
  `addons_total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `addons`, `addons_total`) VALUES
(51, 30, 1, 1, 85.00, '[]', 0.00),
(52, 30, 5, 1, 145.00, '[]', 0.00),
(53, 30, 89, 1, 245.00, '[]', 0.00),
(54, 30, 92, 1, 415.00, '[]', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `days_present` int(11) DEFAULT 0,
  `days_late` int(11) DEFAULT 0,
  `total_hours` decimal(7,2) DEFAULT 0.00,
  `total_customers_served` int(11) DEFAULT 0,
  `base_pay` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `bonuses` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','approved','paid') NOT NULL DEFAULT 'pending',
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `branch` varchar(20) DEFAULT 'laguna',
  `type` varchar(20) DEFAULT 'food'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `category`, `description`, `image`, `stock`, `reorder_level`, `sku`, `branch`, `type`) VALUES
(1, 'Espresso', 85.00, 'mains', 'Bold and concentrated shot of pure espresso, brewed from freshly ground arabica beans.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Espresso.Product.png', 99, 10, '', 'laguna', 'beverage'),
(2, 'Americano', 100.00, 'mains', 'Espresso diluted with hot water for a smooth, full-bodied black coffee experience.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Americano.Product-V1.png', 90, 10, NULL, 'laguna', 'drink'),
(3, 'Cappuccino', 130.00, 'mains', 'Equal parts espresso, steamed milk, and thick velvety foam — a classic Italian favourite.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Cappucino.Coffee.Clean.jpeg', 100, 10, '', 'laguna', ''),
(4, 'Caffe Latte', 140.00, 'mains', 'Silky steamed milk poured over a double shot of espresso with a light layer of foam.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Caffe.Latte.Coffee.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(5, 'Flat White', 145.00, 'mains', 'Stronger and creamier than a latte — micro-foamed milk over a rich ristretto shot.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Flate.White.Latte.Coffee.Clean.jpeg', 99, 10, NULL, 'laguna', 'drink'),
(6, 'Caramel Macchiato', 155.00, 'mains', 'Vanilla-infused steamed milk, espresso, and a generous drizzle of rich caramel.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Caramel.Macchiato.Coffee.Clean.jpeg', 97, 10, NULL, 'laguna', 'drink'),
(7, 'Mocha', 150.00, 'mains', 'Espresso blended with chocolate syrup and steamed milk, topped with whipped cream.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Mocha.Coffee.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(8, 'Hazelnut Latte', 155.00, 'mains', 'Smooth latte infused with rich hazelnut syrup and topped with silky milk foam.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Hazelnut.Latte.Coffee.Clean.jpeg', 98, 10, NULL, 'laguna', 'drink'),
(9, 'White Chocolate Mocha', 160.00, 'mains', 'Espresso blended with creamy white chocolate and steamed milk, topped with whipped cream.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/White.Chocolate.Mocha.Coffee.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(10, 'Spanish Latte', 150.00, 'mains', 'Sweet and creamy latte made with condensed milk for a richer flavor profile.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Spanish.Latte.Coffee.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(11, 'Vanilla Latte', 145.00, 'mains', 'Classic latte enhanced with smooth vanilla syrup and steamed milk.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Vanilla.Latte.Coffe.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(12, 'Cortado', 135.00, 'mains', 'Equal parts espresso and warm milk, cutting the acidity for a balanced, velvety sip.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Cortado.Coffee.Clean.jpeg', 100, 10, NULL, 'laguna', 'drink'),
(13, 'Cold Brew', 160.00, 'drinks', 'Steeped 18 hours in cold water for a smooth, naturally sweet concentrate over ice.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cold.Brew.png', 100, 10, NULL, 'laguna', 'drink'),
(14, 'Iced Americano', 110.00, 'drinks', 'Double espresso pulled over ice and chilled water — clean, crisp, and refreshing.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Iced.Americano.png', 100, 10, NULL, 'laguna', 'drink'),
(15, 'Iced Caramel Latte', 165.00, 'drinks', 'Chilled latte with caramel syrup and milk poured over a glass of crushed ice.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Iced.Caramel.Latte.png', 100, 10, '', 'laguna', 'food'),
(16, 'Matcha Latte', 150.00, 'drinks', 'Ceremonial-grade matcha whisked with oat milk — earthy, smooth, and lightly sweet.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Matcha.Latte.png', 98, 10, NULL, 'laguna', 'drink'),
(17, 'Iced Mocha', 155.00, 'drinks', 'Chilled blend of espresso, chocolate syrup, and milk served over ice.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Iced.Mocha.png', 100, 10, NULL, 'laguna', 'drink'),
(18, 'Dirty Matcha', 165.00, 'drinks', 'Layered drink of matcha and espresso with milk for a bold, earthy caffeine kick.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Dirty.Matcha.png', 99, 10, NULL, 'laguna', 'drink'),
(19, 'Espresso Tonic', 150.00, 'drinks', 'Refreshing mix of espresso and tonic water served over ice with citrus notes.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Espresso.Tonic.png', 100, 10, NULL, 'laguna', 'drink'),
(20, 'Iced Hazelnut Latte', 165.00, 'drinks', 'Chilled hazelnut-infused latte poured over ice for a nutty, refreshing treat.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Iced.Hazelnut.Latte.png', 100, 10, NULL, 'laguna', 'drink'),
(21, 'Strawberry Lemonade', 120.00, 'drinks', 'Freshly squeezed lemonade blended with sweet strawberry purée over ice.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premiun.Strawberry.Lemondade.png', 100, 10, NULL, 'laguna', 'drink'),
(22, 'Sparkling Water', 80.00, 'drinks', 'Chilled sparkling mineral water, perfectly refreshing on its own or with a meal.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Sparkling.Water.png', 100, 10, NULL, 'laguna', 'drink'),
(23, 'Mango Soda Float', 145.00, 'drinks', 'Sweet mango soda topped with a scoop of vanilla ice cream for a tropical float.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Mango.Soda.Float.png', 100, 10, NULL, 'laguna', 'drink'),
(24, 'Hot Chocolate', 135.00, 'drinks', 'Rich and creamy hot chocolate made with real dark cocoa and steamed milk.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Hot.Chocolate.png', 100, 10, NULL, 'laguna', 'drink'),
(25, 'Croissant', 95.00, 'sides', 'Buttery, flaky all-butter croissant baked fresh every morning.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Croissant.png', 100, 10, NULL, 'laguna', 'food'),
(26, 'Banana Bread', 80.00, 'sides', 'Moist homemade banana bread with a golden crust, perfect with your morning coffee.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Banana.Bread.png', 98, 10, NULL, 'laguna', 'food'),
(27, 'Blueberry Muffin', 85.00, 'sides', 'Fluffy muffin bursting with fresh blueberries and a crumbly sugar topping.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Blueberry.Muffin.png', 96, 10, NULL, 'laguna', 'food'),
(28, 'Cinnamon Roll', 110.00, 'sides', 'Soft, sweet pastry swirled with cinnamon sugar and topped with light icing.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cinnamon.Roll.png', 100, 10, NULL, 'laguna', 'food'),
(29, 'Chocolate Chip Cookie', 70.00, 'sides', 'Freshly baked cookie loaded with rich chocolate chips and a soft chewy center.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Chocolate.Chip.Cookie.png', 100, 10, NULL, 'laguna', 'food'),
(30, 'Ham and Cheese Panini', 140.00, 'sides', 'Grilled panini stuffed with savory ham and melted cheese.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Ham-and-Cheese.Panini.png', 99, 10, NULL, 'laguna', 'food'),
(31, 'Breakfast Sandwich', 150.00, 'sides', 'Toasted sandwich with egg, cheese, and choice of ham or bacon for a filling start.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Breakfast.Sandwith.with.Black-Coffee.png', 100, 10, NULL, 'laguna', 'food'),
(32, 'Avocado Toast', 160.00, 'sides', 'Toasted sourdough topped with smashed avocado, sea salt, and a drizzle of olive oil.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Avocado.Toast.with.Black-Coffee.png', 100, 10, NULL, 'laguna', 'food'),
(33, 'Cheese Danish', 95.00, 'sides', 'Flaky pastry filled with sweet cream cheese and a light vanilla glaze.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cheese.Danish.png', 100, 10, NULL, 'laguna', 'food'),
(34, 'Granola Bar', 65.00, 'sides', 'House-made oat granola bar packed with honey, seeds, and dried fruits.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Granola.Bar.png', 100, 10, NULL, 'laguna', 'food'),
(35, 'Egg Salad Sandwich', 130.00, 'sides', 'Creamy egg salad on soft white bread, seasoned with herbs and a touch of mustard.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Egg.Salad.Sandwich.png', 100, 10, NULL, 'laguna', 'food'),
(36, 'Spinach and Feta Wrap', 145.00, 'sides', 'Whole wheat wrap filled with sautéed spinach, feta cheese, and sun-dried tomatoes.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Spinach-and-Feta.Wrap.png', 100, 10, NULL, 'laguna', 'food'),
(37, 'Tiramisu', 130.00, 'desserts', 'Classic Italian dessert with espresso-soaked ladyfingers and mascarpone cream.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Tiramisu.png', 99, 10, NULL, 'laguna', 'food'),
(38, 'Chocolate Lava Cake', 145.00, 'desserts', 'Warm dark chocolate cake with a molten centre, served with a dusting of cocoa powder.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Chocolate.Lava.Cake.png', 99, 10, NULL, 'laguna', 'food'),
(39, 'Affogato', 140.00, 'desserts', 'Vanilla ice cream drowned in a hot shot of espresso for a perfect hot-and-cold dessert.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Affogato.Coffee.png', 99, 10, NULL, 'laguna', 'food'),
(40, 'Cheesecake', 135.00, 'desserts', 'Creamy baked cheesecake with a buttery graham crust.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cheesecake.png', 99, 10, NULL, 'laguna', 'food'),
(41, 'Brownies', 90.00, 'desserts', 'Fudgy chocolate brownies with a crackly top and rich cocoa flavor.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Brownies.png', 100, 10, NULL, 'laguna', 'food'),
(42, 'Creme Brulee', 150.00, 'desserts', 'Silky vanilla custard topped with a perfectly caramelised sugar crust.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Creme.Brulee.png', 100, 10, NULL, 'laguna', 'food'),
(43, 'Mango Panna Cotta', 130.00, 'desserts', 'Silky Italian panna cotta crowned with a vibrant fresh mango coulis.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Mango.Panna.Cotta.png', 100, 10, NULL, 'laguna', 'food'),
(44, 'Strawberry Shortcake', 140.00, 'desserts', 'Light sponge cake layered with fresh strawberries and whipped cream.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Strawberry.Shortcake.png', 100, 10, NULL, 'laguna', 'food'),
(45, 'Macarons (3 pcs)', 120.00, 'desserts', 'Delicate French macarons in assorted flavours — crisp shell, chewy centre.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.French.Macarons.png', 100, 10, NULL, 'laguna', 'food'),
(46, 'Chocolate Mousse', 125.00, 'desserts', 'Airy dark chocolate mousse served chilled with a dusting of cocoa.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Chocolate.Mousse.png', 100, 10, NULL, 'laguna', 'food'),
(47, 'Leche Flan', 95.00, 'desserts', 'Classic Filipino-style caramel custard flan, silky smooth with a golden caramel top.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Leche.Flan.png', 99, 10, NULL, 'laguna', 'food'),
(48, 'Ube Cheesecake', 145.00, 'desserts', 'Creamy ube-flavoured cheesecake with a rich purple hue and buttery biscuit base.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Ube.Cheesecake.png', 100, 10, NULL, 'laguna', 'food'),
(49, 'Espresso Manila', 95.00, 'coffee', 'Rich and concentrated espresso with bold flavor and a smooth finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Espresso.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(50, 'Americano Manila', 105.00, 'coffee', 'Freshly brewed espresso blended with hot water for a clean, balanced taste.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Americano.Manila.png', 93, 10, NULL, 'manila', 'drink'),
(51, 'Cappuccino Manila', 125.00, 'coffee', 'Classic espresso topped with velvety steamed milk and a light foam crown.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cappuccino.Manila.png', 96, 10, NULL, 'manila', 'drink'),
(52, 'Caffe Latte Manila', 135.00, 'coffee', 'Smooth espresso combined with creamy steamed milk for a mellow coffee experience.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Cafe.Latte.Manila.png', 99, 10, NULL, 'manila', 'drink'),
(53, 'Mocha Manila', 150.00, 'coffee', 'A luxurious blend of espresso, chocolate, and steamed milk.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Mocha.Latte.Manila.png', 99, 10, NULL, 'manila', 'drink'),
(54, 'Flat White Manila', 140.00, 'coffee', 'Bold espresso paired with silky microfoam for a rich and balanced texture.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Flat.White.Latte.Manila.png', 98, 10, NULL, 'manila', 'drink'),
(55, 'Spanish Latte Manila', 110.00, 'coffee', 'Espresso mixed with sweetened milk for a creamy and indulgent flavor.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Spanish.Latte.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(56, 'Caramel Macchiato Manila', 145.00, 'coffee', 'Layered espresso with steamed milk and buttery caramel notes.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Caramel.Macchiato.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(57, 'Vanilla Latte Manila', 145.00, 'coffee', 'A comforting combination of espresso, steamed milk, and aromatic vanilla.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Vanilla.Latte.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(58, 'Hazelnut Latte Manila', 150.00, 'coffee', 'Smooth espresso infused with rich hazelnut flavor and creamy milk.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Hazelnut.Latte.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(59, 'Sea Salt Latte Manila', 160.00, 'coffee', 'A unique blend of espresso and milk enhanced with a delicate sea salt finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Sea.Salt.Latte.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(60, 'Matcha Espresso Fusion Manila', 170.00, 'coffee', 'Premium matcha and bold espresso combined for a vibrant and sophisticated drink.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Matcha.Espresso.Fusion.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(61, 'Cabernet Sauvignon Manila', 2499.00, 'champagnes', 'A full-bodied red wine with rich berry notes and a smooth, elegant finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Cabernet.Sauvignon.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(62, 'Merlot Reserve Manila', 2399.00, 'champagnes', 'Soft and velvety red wine featuring hints of plum and dark cherry.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Merlot.Reserve.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(63, 'Pinot Noir Manila', 2799.00, 'champagnes', 'A refined red wine with delicate fruit flavors and subtle earthy undertones.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Pinot.Noir.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(64, 'Shiraz Signature Manila', 2599.00, 'champagnes', 'Bold and expressive with notes of blackberries, spice, and oak.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Shiraz.Signature.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(65, 'Malbec Estate Manila', 2399.00, 'champagnes', 'Rich and balanced red wine with dark fruit aromas and a smooth texture.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Malbec.Estate.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(66, 'Chardonnay Premium Manila', 2199.00, 'champagnes', 'A classic white wine with crisp apple notes and a creamy finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Chardonnay.Premium.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(67, 'Sauvignon Blanc Manila', 2099.00, 'champagnes', 'Light and refreshing with vibrant citrus flavors and a clean finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Sauvignon.Blanc.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(68, 'Pinot Grigio Manila', 2199.00, 'champagnes', 'A crisp and elegant white wine with subtle pear and floral notes.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Pinot.Grigio.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(69, 'Rose Selection Manila', 1999.00, 'champagnes', 'A refreshing rose with delicate berry flavors and a bright finish.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Rose.Selection.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(70, 'Prosecco Prestige Manila', 2899.00, 'champagnes', 'Fine sparkling wine with lively bubbles and refreshing fruit notes.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Prosecco.Prestige.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(71, 'Champagne Brut Manila', 5999.00, 'champagnes', 'A luxurious champagne offering elegant bubbles and exceptional balance.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Champagne.Brut.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(72, 'Sparkling Moscato Manila', 2999.00, 'champagnes', 'Sweet and refreshing sparkling wine with notes of peach and tropical fruits.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/study-projects-php/admin/products/Premium.Sparkling.Moscato.Manila.png', 100, 10, NULL, 'manila', 'drink'),
(73, 'Grilled Salmon Manila', 1399.00, 'meals', 'Fresh grilled salmon served with seasonal sides.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Grilled.Salmon.Manila.png', 100, 10, NULL, 'manila', 'food'),
(74, 'Angus Ribeye Steak Manila', 2499.00, 'meals', 'Premium Angus ribeye steak cooked to perfection.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Angus.Ribeye.Steak.Manila.jpeg', 99, 10, NULL, 'manila', 'food'),
(75, 'Herb-Roasted Chicken Manila', 899.00, 'meals', 'Juicy roasted chicken seasoned with fresh herbs.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Herb-Roasted.Chicken.Manila.png', 99, 10, NULL, 'manila', 'food'),
(76, 'Truffle Mushroom Pasta Manila', 999.00, 'meals', 'Creamy pasta with earthy truffle and wild mushrooms.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Truffle.Mushroom.Pasta.Manila.png', 100, 10, NULL, 'manila', 'food'),
(77, 'Seafood Aglio Olio Manila', 1199.00, 'meals', 'Classic aglio olio pasta loaded with fresh seafood.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Seafood.Aglio.Olio.Manila.png', 100, 10, NULL, 'manila', 'food'),
(78, 'Beef Tenderloin Manila', 2199.00, 'meals', 'Tender beef fillet grilled and served with rich sauce.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Beef.Tendeloin.Manila.png', 100, 10, NULL, 'manila', 'food'),
(79, 'Shrimp Alfredo Manila', 1199.00, 'meals', 'Creamy Alfredo pasta topped with succulent shrimp.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Shrimp.Alfredo.Manila.png', 100, 10, NULL, 'manila', 'food'),
(80, 'Chicken Parmigiana Manila', 1199.00, 'meals', 'Breaded chicken breast with marinara sauce and melted cheese.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Chicken.Parmigiana.Manila.png', 100, 10, NULL, 'manila', 'food'),
(81, 'Braised Beef Short Ribs Manila', 1699.00, 'meals', 'Slow-braised beef short ribs in a rich, savory sauce.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Braised.Beef.Short-Ribs.Manila.png', 100, 10, NULL, 'manila', 'food'),
(82, 'Lobster Mac and Cheese Manila', 2199.00, 'meals', 'Indulgent mac and cheese elevated with premium lobster.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Lobster.Mac-and-Cheese.Manila.png', 100, 10, NULL, 'manila', 'food'),
(83, 'Lamb Chops Manila', 2499.00, 'meals', 'Perfectly grilled lamb chops with herb crust.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Lamb.Chops.png', 100, 10, NULL, 'manila', 'food'),
(84, 'Wild Mushroom Risotto Manila', 999.00, 'meals', 'Creamy Arborio risotto infused with a medley of wild mushrooms.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Premium.Wild.Mushroom.Risotto.Manila.png', 100, 10, NULL, 'manila', 'food'),
(85, 'Morning Brew Duo', 210.00, 'packages', 'Coffee + Sides. A creamy Caffe Latte paired with a buttery, flaky all-butter Croissant. Save ₱25 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Morning.Brew.Duo.Clean.jpeg', 99, 10, 'PKG-LAG-01', 'laguna', 'food'),
(86, 'Cappuccino & Banana Bread', 190.00, 'packages', 'Coffee + Sides. Velvety Cappuccino with a slice of moist homemade Banana Bread. Save ₱20 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Cappucino.with.Banana.Bread.Clean.jpeg', 100, 10, 'PKG-LAG-02', 'laguna', 'food'),
(87, 'Brunch Bundle', 275.00, 'packages', 'Coffee + Sides. A smooth Flat White with a hearty Avocado Toast on toasted sourdough. Save ₱30 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Brunch.Bundle.Clean.jpeg', 100, 10, 'PKG-LAG-03', 'laguna', 'food'),
(88, 'Panini Lunch Combo', 215.00, 'packages', 'Coffee + Sides. A grilled Ham and Cheese Panini with a clean, bold Americano. Save ₱25 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Panini.Lunch.Combo.Clean.jpeg', 100, 10, 'PKG-LAG-04', 'laguna', 'food'),
(89, 'Sweet Start Combo', 245.00, 'packages', 'Coffee + Desserts. A silky Caffe Latte with a classic Tiramisu made with espresso-soaked ladyfingers. Save ₱25 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Sweet.Start.Combo.Clean.jpeg', 99, 10, 'PKG-LAG-05', 'laguna', 'food'),
(90, 'Espresso & Lava Cake', 205.00, 'packages', 'Coffee + Desserts. A bold shot of Espresso with a warm Chocolate Lava Cake and its molten centre. Save ₱25 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Espresso.with.Lava.Cake.Clean.jpeg', 100, 10, 'PKG-LAG-06', 'laguna', 'food'),
(91, 'Iced Treat Pair', 270.00, 'packages', 'Drinks + Desserts. Chilled Iced Caramel Latte with a creamy baked Cheesecake on a buttery graham crust. Save ₱30 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Iced.Treat.Pair.Clean.jpeg', 100, 10, 'PKG-LAG-07', 'laguna', 'food'),
(92, 'Cafe Date Set for Two', 415.00, 'packages', 'Coffee + Desserts, good for two. A Cappuccino and a Spanish Latte, shared with Leche Flan and fudgy Brownies. Save ₱50 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Cafe.Date.Set.For.Two.Clean.jpeg', 99, 10, 'PKG-LAG-08', 'laguna', 'food'),
(93, 'Latte & Truffle Pasta', 1049.00, 'packages', 'Coffee + Meals. Creamy Truffle Mushroom Pasta with wild mushrooms, finished with a smooth Caffe Latte. Save ₱85 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Latte.and.Truffle.Pasta.Clean.jpeg', 99, 10, 'PKG-MNL-01', 'manila', 'food'),
(94, 'Ribeye & Americano Set', 2449.00, 'packages', 'Coffee + Meals. Premium Angus Ribeye Steak cooked to perfection, with a clean Americano to close the meal. Save ₱155 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Ribeye.and.Americano.Set.Clean.jpeg', 100, 10, 'PKG-MNL-02', 'manila', 'food'),
(95, 'Roast Chicken & Cappuccino', 949.00, 'packages', 'Coffee + Meals. Juicy Herb-Roasted Chicken with fresh herbs, served with a classic Cappuccino. Save ₱75 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Roast.Chicken.and.Cappuccino.Clean.jpeg', 100, 10, 'PKG-MNL-03', 'manila', 'food'),
(96, 'Champagne Dinner for Two', 3899.00, 'packages', 'Champagnes + Meals. A bottle of Prosecco Prestige with lively bubbles, paired with fresh Grilled Salmon and seasonal sides. Save ₱399 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Champagne.Dinner.for.Two.Clean.jpeg', 100, 10, 'PKG-MNL-04', 'manila', 'food'),
(97, 'Wine & Tenderloin Night', 4299.00, 'packages', 'Champagnes + Meals. Full-bodied Cabernet Sauvignon with tender Beef Tenderloin in a rich sauce. Save ₱399 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Wine.and.Tinderloin.Night.Clean.jpeg', 100, 10, 'PKG-MNL-05', 'manila', 'food'),
(98, 'Seafood & Chardonnay', 3099.00, 'packages', 'Champagnes + Meals. Crisp Chardonnay Premium with a Seafood Aglio Olio loaded with fresh seafood. Save ₱299 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Seafood.and.Chardonnay.Clean.jpeg', 100, 10, 'PKG-MNL-06', 'manila', 'food'),
(99, 'Risotto & Rose', 2699.00, 'packages', 'Champagnes + Meals. A refreshing Rose Selection with creamy Wild Mushroom Risotto. Save ₱299 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Risotto.and.Rose.Clean.jpeg', 100, 10, 'PKG-MNL-07', 'manila', 'food'),
(100, 'Royal Celebration', 9499.00, 'packages', 'Champagnes + Meals, for a group. A bottle of Champagne Brut with Lobster Mac and Cheese and grilled Lamb Chops. Save ₱1,198 versus ordering separately.', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/admin/products/Royal.Celebration.Clean.jpeg', 100, 10, 'PKG-MNL-08', 'manila', 'food');

-- --------------------------------------------------------

--
-- Table structure for table `product_addons`
--

CREATE TABLE `product_addons` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applies_to` varchar(20) DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_addons`
--

INSERT INTO `product_addons` (`id`, `name`, `price`, `applies_to`, `is_active`) VALUES
(1, 'Extra Shot', 30.00, 'coffee', 1),
(2, 'Extra Milk', 20.00, 'all', 1),
(3, 'Extra Ice', 10.00, 'drinks', 1),
(4, 'Whipped Cream', 25.00, 'all', 1),
(5, 'Oat Milk Upgrade', 35.00, 'coffee', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `reservation_code` varchar(40) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) NOT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `branch` varchar(20) NOT NULL DEFAULT 'laguna',
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `party_size` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'reserved',
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_ref` varchar(60) DEFAULT NULL,
  `card_last4` char(4) DEFAULT NULL,
  `cash_tendered` decimal(10,2) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `reservation_code`, `user_id`, `guest_name`, `guest_phone`, `branch`, `reservation_date`, `reservation_time`, `party_size`, `notes`, `total`, `status`, `payment_status`, `payment_method`, `payment_ref`, `card_last4`, `cash_tendered`, `order_id`, `created_at`, `paid_at`, `picked_up_at`, `cancelled_at`) VALUES
(1, 'AYS-RSV-20260921-00001', NULL, 'Kaily Quinio', '09910654627', 'laguna', '2026-09-21', '15:00:00', 1, '', 155.00, 'reserved', 'unpaid', NULL, NULL, NULL, NULL, NULL, '2026-09-21 00:18:28', NULL, NULL, NULL),
(2, 'AYS-RSV-20260922-00002', NULL, 'Kaily Quinio', '09910654627', 'manila', '2026-09-22', '08:30:00', 1, '', 105.00, 'reserved', 'unpaid', NULL, NULL, NULL, NULL, NULL, '2026-09-22 04:26:47', NULL, NULL, NULL),
(3, 'AYS-RSV-20260922-00003', 9, 'Kaily Quinio', '09910654627', 'laguna', '2026-09-22', '09:30:00', 2, '', 210.00, 'reserved', 'unpaid', NULL, NULL, NULL, NULL, NULL, '2026-09-22 04:27:34', NULL, NULL, NULL),
(4, 'AYS-RSV-20260922-00004', 9, 'Kaily Quinio', '09910654627', 'manila', '2026-09-22', '10:30:00', 2, '', 1049.00, 'reserved', 'unpaid', NULL, NULL, NULL, NULL, NULL, '2026-09-22 04:28:58', NULL, NULL, NULL),
(5, 'AYS-RSV-20260922-00005', 9, 'Kaily Quinio', '09910654627', 'manila', '2026-09-22', '10:35:00', 2, 'Wedding', 105.00, 'paid', 'paid', 'cash', NULL, NULL, 105.00, 29, '2026-09-22 04:36:23', '2026-09-22 05:20:52', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_items`
--

CREATE TABLE `reservation_items` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `addons` text DEFAULT NULL,
  `addons_total` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_items`
--

INSERT INTO `reservation_items` (`id`, `reservation_id`, `product_id`, `product_name`, `quantity`, `price`, `addons`, `addons_total`) VALUES
(1, 1, 8, 'Hazelnut Latte', 1, 155.00, '[]', 0.00),
(2, 2, 50, 'Americano Manila', 1, 105.00, '[]', 0.00),
(3, 3, 85, 'Morning Brew Duo', 1, 210.00, '[]', 0.00),
(4, 4, 93, 'Latte & Truffle Pasta', 1, 1049.00, '[]', 0.00),
(5, 5, 50, 'Americano Manila', 1, 105.00, '[]', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `branch` varchar(20) DEFAULT 'laguna',
  `rating` tinyint(1) DEFAULT 5,
  `message` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `branch`, `rating`, `message`, `is_approved`, `created_at`) VALUES
(1, 'Maria Santos', 'laguna', 5, 'The Caramel Macchiato here is hands down the best I have ever had. The service is so warm and welcoming — I feel at home every visit!', 1, '2026-05-01 20:21:17'),
(2, 'James Reyes', 'manila', 5, 'AyosCoffeeNegosyo Manila never disappoints. Their Flat White is perfect every single time. The ambiance is amazing too!', 1, '2026-05-01 20:21:17'),
(3, 'Sophia Cruz', 'laguna', 4, 'The Ube Cheesecake paired with their Espresso is an absolute dream combo. Will definitely be back for more!', 1, '2026-05-01 20:21:17'),
(4, 'Carlo Villanueva', 'manila', 5, 'Fresh pastries, great coffee, and fantastic staff. The Manila branch has become my regular work-from-cafe spot.', 1, '2026-05-01 20:21:17'),
(5, 'Angela Tan', 'laguna', 5, 'I ordered the Spanish Latte and Leche Flan — both were incredible. Authentically Filipino flavors in every bite and sip!', 1, '2026-05-01 20:21:17'),
(6, 'Miguel Fernandez', 'laguna', 4, 'Great place to relax and catch up with friends. The Cold Brew is super smooth and the Avocado Toast is top tier.', 1, '2026-05-01 20:21:17'),
(7, 'Bryan Escobar Torres', 'laguna', 5, 'I had a great experience at this restaurant. The service was efficient, and the staff were professional, friendly, and attentive. Orders were served promptly, showing good organization and teamwork. The restaurant was also clean and comfortable, creating a pleasant dining atmosphere. The food quality and presentation were excellent, making the overall experience enjoyable. I would highly recommend this restaurant and would definitely visit again.', 1, '2026-07-02 13:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_banned` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `created_at`, `is_banned`, `role`) VALUES
(12, 'reynalyncute', '$2y$10$qO8ZzB2DRAXeIWSBNVdJ2.BfNCzYsNk.hpY94KMz0MU.J1uMrKLN6', NULL, NULL, NULL, '2026-09-23 10:21:18', 0, 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `face_descriptors`
--
ALTER TABLE `face_descriptors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_addons`
--
ALTER TABLE `product_addons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_res_user` (`user_id`),
  ADD KEY `idx_res_when` (`reservation_date`,`reservation_time`),
  ADD KEY `idx_res_status` (`status`),
  ADD KEY `idx_res_order` (`order_id`),
  ADD KEY `idx_res_payref` (`payment_ref`);

--
-- Indexes for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resitem_res` (`reservation_id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=690;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `face_descriptors`
--
ALTER TABLE `face_descriptors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `product_addons`
--
ALTER TABLE `product_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservation_items`
--
ALTER TABLE `reservation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD CONSTRAINT `fk_resitem_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
