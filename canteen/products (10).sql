-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2026 at 06:53 PM
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
-- Database: `canteen_cmdi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'snacks',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 50,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `image` varchar(500) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category`, `description`, `price`, `stock`, `reorder_level`, `image`, `is_available`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'RCE-001', 'Tapsilog', 'rice_meals', 'Beef tapa, garlic rice, and a sunny-side-up egg.', 65.00, 38, 10, 'assets/img/Tapsilog.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-30 14:44:56'),
(2, 'RCE-002', 'Chicken Adobo with Rice', 'rice_meals', 'Soy-vinegar braised chicken over steaming white rice.', 60.00, 35, 10, 'assets/img/Chicken.Adobo.with.Rice.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-16 06:04:39'),
(3, 'RCE-003', 'Fried Chicken with Rice', 'rice_meals', 'Crispy fried chicken leg served with rice and gravy.', 65.00, 34, 10, 'assets/img/Fried.Chicken.with.Rice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-18 05:10:42'),
(4, 'RCE-004', 'Longsilog', 'rice_meals', 'Sweet longganisa, garlic rice, and a fried egg.', 55.00, 35, 10, 'assets/img/Longsilog.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-16 06:07:07'),
(5, 'RCE-005', 'Pork Giniling Rice', 'rice_meals', 'Savory ground pork with potatoes and carrots over rice.', 55.00, 30, 10, 'assets/img/Pork.Giniling.with.Rice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 04:05:12'),
(6, 'RCE-006', 'Spaghetti', 'rice_meals', 'Filipino-style sweet spaghetti with hotdog slices and cheese.', 40.00, 40, 10, 'assets/img/Filipino.Style.Spaghetti.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-17 03:20:12'),
(7, 'RCE-007', 'Pancit Canton', 'rice_meals', 'Stir-fried noodles with vegetables and a squeeze of calamansi.', 30.00, 40, 10, 'assets/img/Pancit.Canton.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-16 06:06:42'),
(8, 'SNK-001', 'Siomai (4 pcs)', 'snacks', 'Steamed pork dumplings with chili-garlic sauce.', 25.00, 58, 15, 'assets/img/Fresh.Steamed.Siomai.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-18 05:08:42'),
(9, 'SNK-002', 'Fishball Cup', 'snacks', 'Fried fishballs with sweet or spicy sauce.', 20.00, 80, 20, 'assets/img/Fishball.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 04:57:40'),
(10, 'SNK-003', 'Banana Cue', 'snacks', 'Deep-fried saba banana coated in caramelized brown sugar.', 15.00, 100, 15, 'assets/img/Fresh.Banana.Cue.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:13:13'),
(11, 'SNK-004', 'Turon', 'snacks', 'Saba banana and langka wrapped in lumpia skin, fried golden.', 15.00, 50, 15, 'assets/img/Fresh.Fried.Turon.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 05:03:23'),
(12, 'SNK-005', 'Hotdog Sandwich', 'snacks', 'Grilled hotdog on a soft bun with ketchup and mayo.', 25.00, 40, 10, 'assets/img/Hotdog.Sandwich.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:01:11'),
(13, 'SNK-006', 'Burger', 'snacks', 'Beef patty burger with lettuce, tomato, and special sauce.', 35.00, 38, 10, 'assets/img/Normal.Fresh.Burger.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-18 05:10:42'),
(14, 'SNK-007', 'French Fries', 'snacks', 'Crispy fries with your choice of cheese, BBQ, or sour cream.', 30.00, 49, 15, 'assets/img/French.Fries.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-18 05:10:42'),
(15, 'SNK-008', 'Egg Sandwich', 'snacks', 'Creamy egg spread on soft white bread.', 25.00, 39, 10, 'assets/img/Fresh.Egg.Sandwich.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-30 14:44:56'),
(16, 'SNK-009', 'Siopao Asado', 'snacks', 'Fluffy steamed bun filled with sweet asado pork.', 30.00, 29, 10, 'assets/img/Fresh.Siopao.Asado.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:11:23'),
(17, 'DRK-001', 'Bottled Water', 'drinks', 'Purified drinking water, 500 ml.', 15.00, 96, 20, 'assets/img/Bottled.Water.500ml.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-30 14:44:47'),
(18, 'DRK-002', 'Iced Tea', 'drinks', 'Chilled house-blend iced tea.', 20.00, 80, 20, 'assets/img/Iced.Tea.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:07:49'),
(19, 'DRK-003', 'Buko Juice', 'drinks', 'Fresh coconut water with tender buko strips.', 25.00, 100, 15, 'assets/img/Fresh.Buko.Juice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-30 14:44:56'),
(20, 'DRK-004', 'Orange Juice', 'drinks', 'Sweet and tangy orange juice, served cold.', 25.00, 48, 15, 'assets/img/Fresh.Orange.Juice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-18 05:10:42'),
(21, 'DRK-005', 'Chocolate Milk', 'drinks', 'Cold chocolate milk, 200 ml pack.', 30.00, 40, 10, 'assets/img/Chocolate.Milk.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:03:01'),
(22, 'DRK-006', 'Softdrink (Mismo)', 'drinks', 'Chilled bottled softdrink, 295 ml.', 25.00, 60, 15, 'assets/img/Coke.Bottled.Mismo.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:08:49'),
(23, 'DRK-007', 'Sago\'t Gulaman', 'drinks', 'Brown sugar syrup drink with sago pearls and gulaman.', 20.00, 60, 15, 'assets/img/Fresh.Gulaman.Juice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:09:07'),
(24, 'DST-001', 'Leche Flan Cup', 'desserts', 'Silky caramel custard in a single-serve cup.', 25.00, 40, 10, 'assets/img/Leche.Flan.Cup.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:09:23'),
(25, 'DST-002', 'Halo-Halo', 'desserts', 'Shaved ice with sweet beans, fruits, leche flan, and ice cream.', 45.00, 30, 10, 'assets/img/Fresh.Traditional.Halo-Halo.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-17 07:09:48'),
(26, 'DST-003', 'Ice Cream Cone', 'desserts', 'Vanilla soft-serve in a crispy cone.', 20.00, 50, 15, 'assets/img/Vanilla.Ice.Cream.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:10:06'),
(27, 'DST-004', 'Mais con Yelo', 'desserts', 'Sweet corn, shaved ice, and milk.', 30.00, 30, 10, 'assets/img/Iced.Mais.con.Yelo.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:10:43'),
(28, 'RCE-008', 'Pork Menudo with Rice', 'rice_meals', 'Diced pork simmered in tomato sauce with potatoes, carrots, green peas, and bell peppers, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Pork.Menudo.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:03:30'),
(29, 'RCE-009', 'Chicken Afritada with Rice', 'rice_meals', 'Chicken pieces stewed in tomato sauce with potato chunks, carrot slices, and red bell peppers, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Chicken.Afritada.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:05:24'),
(30, 'RCE-010', 'Pork Sisig with Rice', 'rice_meals', 'Finely chopped cooked pork mixed with onions, chili, and savory seasonings, served with steamed white rice and calamansi.', 65.00, 40, 10, 'assets/img/Pork.Sisig.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:05:39'),
(31, 'RCE-011', 'Chicken Teriyaki Rice Bowl', 'rice_meals', 'Sliced chicken coated in glossy sweet-savory teriyaki sauce, topped with sesame seeds and spring onions over steamed white rice.', 65.00, 40, 10, 'assets/img/Chicken.Teriyaki.Rice.Bowl.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:06:13'),
(32, 'RCE-012', 'Pork Barbecue with Rice', 'rice_meals', 'Grilled pork skewers coated in a sweet and savory barbecue marinade, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Pork.Barbecue.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:06:31'),
(33, 'RCE-013', 'Lumpiang Shanghai with Rice', 'rice_meals', 'Small golden-fried spring rolls filled with seasoned ground pork and minced vegetables, served with steamed white rice and dipping sauce.', 55.00, 40, 10, 'assets/img/Lumpiang.Shanghai.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:06:52'),
(34, 'RCE-014', 'Chicken Curry with Rice', 'rice_meals', 'Chicken pieces simmered in creamy yellow curry sauce with potatoes and carrots, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Chicken.Curry.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:07:05'),
(35, 'RCE-015', 'Pork Chop with Rice', 'rice_meals', 'Breaded pork chop fried until golden brown, served with steamed white rice and a side of gravy.', 65.00, 40, 10, 'assets/img/Pork.Chop.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:07:16'),
(36, 'RCE-016', 'Beef Steak with Rice', 'rice_meals', 'Thin beef slices simmered in savory soy-calamansi sauce with sliced onions, served with steamed white rice.', 70.00, 40, 10, 'assets/img/Beef.Steak.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:07:45'),
(37, 'RCE-017', 'Chicken Fillet with Rice', 'rice_meals', 'Boneless breaded chicken fillet fried until crispy, served with steamed white rice and creamy gravy.', 65.00, 40, 10, 'assets/img/Chicken.Fillet.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:07:57'),
(38, 'RCE-018', 'Pork Tocino with Rice', 'rice_meals', 'Sweet cured pork slices caramelized during cooking, served with garlic fried rice and a fried egg.', 60.00, 40, 10, 'assets/img/Pork.Tocino.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:10'),
(39, 'RCE-019', 'Corned Beef with Rice', 'rice_meals', 'Saut?ed corned beef mixed with sliced onions, served with steamed white rice and an optional fried egg.', 55.00, 40, 10, 'assets/img/Corned.Beef.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:23'),
(40, 'RCE-020', 'Burger Steak with Rice', 'rice_meals', 'Pan-seared beef patties covered in brown mushroom gravy, served with steamed white rice.', 55.00, 40, 10, 'assets/img/Burger.Steak.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:35'),
(41, 'RCE-021', 'Chicken Nuggets with Rice', 'rice_meals', 'Bite-sized breaded chicken pieces fried until golden, served with steamed white rice and dipping sauce.', 55.00, 40, 10, 'assets/img/Chicken.Nuggets.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:44'),
(42, 'RCE-022', 'Fried Bangus with Rice', 'rice_meals', 'Milkfish marinated with garlic and seasonings, fried until crisp, and served with steamed white rice and a calamansi wedge.', 65.00, 40, 10, 'assets/img/Fried.Bangus.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(43, 'SNK-010', 'Cheese Sandwich', 'snacks', 'Sliced bread filled with creamy cheese spread and cut into halves.', 20.00, 40, 10, 'assets/img/Cheese.Sandwich.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:11:17'),
(44, 'SNK-011', 'Ham and Cheese Sandwich', 'snacks', 'Sliced bread layered with cooked ham and cheese slices.', 30.00, 40, 10, 'assets/img/Ham.and.Cheese.Sandwich.Clean.png', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:15:24'),
(45, 'SNK-012', 'Tuna Sandwich', 'snacks', 'Bread filled with flaked tuna mixed with mayonnaise and finely chopped onions.', 30.00, 40, 10, 'assets/img/Tuna.Sandwich.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:54:09'),
(46, 'SNK-013', 'Cheese Hotdog', 'snacks', 'Cooked hotdog topped with melted cheese sauce and served in a soft hotdog bun.', 35.00, 40, 10, 'assets/img/Cheese.Hotdog.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:11:01'),
(47, 'SNK-014', 'Hotdog on a Stick', 'snacks', 'A cooked hotdog skewered on a wooden stick and coated with a lightly browned exterior.', 20.00, 40, 10, 'assets/img/Hotdog.on.a.Stick.Clean.png', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:56:18'),
(48, 'SNK-015', 'Kikiam', 'snacks', 'Short cylindrical rolls made from seasoned fish paste or meat mixture, fried until golden brown and sliced for serving.', 20.00, 50, 15, 'assets/img/Kikiam.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:54:48'),
(49, 'SNK-016', 'Nachos with Cheese', 'snacks', 'Crispy triangular corn tortilla chips topped with warm melted cheese sauce.', 40.00, 40, 10, 'assets/img/Nachos.with.Cheese.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:57:00'),
(50, 'SNK-017', 'Cheese Sticks', 'snacks', 'Thin cheese-filled spring roll wrappers fried until crisp and golden, with melted cheese inside.', 25.00, 40, 10, 'assets/img/Cheese.Sticks.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:11:47'),
(51, 'SNK-018', 'Lumpiang Gulay', 'snacks', 'Filipino vegetable spring rolls filled with saut?ed cabbage, carrots, bean sprouts, and other vegetables, wrapped and fried until crispy.', 20.00, 40, 10, 'assets/img/Lumpiang.Gulay.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:55:18'),
(52, 'SNK-019', 'Pancakes', 'snacks', 'Small, round, fluffy pancakes cooked until golden on both sides and served with syrup.', 35.00, 40, 10, 'assets/img/Fresh.Pancakes.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:55:32'),
(53, 'SNK-020', 'Cheese Waffle', 'snacks', 'A golden waffle with a soft interior, topped with grated cheese or melted cheese sauce.', 40.00, 40, 10, 'assets/img/Cheese.Waffle.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:12:45'),
(54, 'SNK-021', 'Chicken Nuggets', 'snacks', 'Bite-sized chicken pieces coated in seasoned breading and fried until golden and crispy.', 40.00, 40, 10, 'assets/img/Chicken.Nuggets.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:13:37'),
(55, 'SNK-022', 'Potato Wedges', 'snacks', 'Thick-cut potato wedges seasoned with spices and fried or baked until crisp outside and tender inside.', 35.00, 40, 10, 'assets/img/Potato.Wedges.Clean.jpeg', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:55:44'),
(56, 'SNK-023', 'Empanada', 'snacks', 'A semicircular pastry filled with seasoned meat, diced vegetables, and potatoes, with crimped edges and a golden crust.', 30.00, 40, 10, 'assets/img/Ham.and.Cheese.Empanada.Clean.png', 1, 0, '2026-10-20 05:00:07', '2026-10-21 05:14:44'),
(57, 'DRK-008', 'Lemonade', 'drinks', 'Pale yellow lemon drink served cold with ice and a slice of lemon.', 25.00, 50, 15, 'assets/img/Fresh.Lemonade.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:45:56'),
(58, 'DRK-009', 'Mango Juice', 'drinks', 'Golden-yellow mango beverage with a smooth, slightly thick consistency, served cold.', 25.00, 50, 15, 'assets/img/Fresh.Mango.Juice.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:44:39'),
(59, 'DRK-010', 'Pineapple Juice', 'drinks', 'Yellow pineapple juice with a smooth consistency, served chilled in a clear cup.', 25.00, 50, 15, 'assets/img/Fresh.Pineapple.Juice.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:45:07'),
(60, 'DRK-011', 'Fresh Milk', 'drinks', 'White milk with a smooth, uniform appearance, served chilled in a glass or bottle.', 30.00, 40, 10, 'assets/img/Fresh.Milk.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:07:49'),
(61, 'DRK-012', 'Iced Coffee', 'drinks', 'Chilled brewed coffee mixed with milk and sweetener, served over ice in a transparent cup.', 35.00, 40, 10, 'assets/img/Iced.Coffee.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:44:25'),
(62, 'DRK-013', 'Hot Chocolate', 'drinks', 'Warm, dark-brown chocolate drink with a smooth consistency, served in a cup.', 30.00, 40, 10, 'assets/img/Hot.Chocolate.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:43:57'),
(63, 'DRK-014', 'Milo', 'drinks', 'Chocolate malt beverage mixed with milk or water, served hot or cold according to preparation.', 25.00, 50, 15, 'assets/img/Fresh.Milo.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:47:12'),
(64, 'DRK-015', 'Calamansi Juice', 'drinks', 'Light yellow-green citrus drink made with calamansi juice, water, and sugar, served over ice.', 20.00, 50, 15, 'assets/img/Calamansi.Juice.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:06:59'),
(65, 'DRK-016', 'Cucumber Lemonade', 'drinks', 'Pale green lemonade mixed with cucumber juice and lemon, served cold with ice.', 25.00, 40, 10, 'assets/img/Cucumber.Lemonade.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:07:30'),
(66, 'DRK-017', 'Strawberry Milk', 'drinks', 'Pink-colored milk beverage with strawberry flavor, served chilled in a clear cup or bottle.', 30.00, 40, 10, 'assets/img/Fresh.Strawberry.Milk.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:47:48'),
(67, 'DRK-018', 'Mango Shake', 'drinks', 'Thick, bright yellow blended mango drink with a smooth texture, served in a clear cup.', 40.00, 40, 10, 'assets/img/Fresh.Mango.Shake.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-22 04:46:17'),
(68, 'DRK-019', 'Banana Smoothie', 'drinks', 'Creamy pale-yellow drink made by blending ripe bananas with milk until smooth.', 40.00, 40, 10, 'assets/img/Banana.Smoothie.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:06:38'),
(69, 'DRK-020', 'Gulaman Drink', 'drinks', 'Sweet brown-sugar beverage containing translucent jelly cubes, served chilled in a clear cup.', 20.00, 50, 15, 'assets/img/Gulaman.Drink.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:08:20'),
(70, 'DRK-021', 'Fruit Tea', 'drinks', 'Fruit-flavored tea with a translucent colored appearance, served cold over ice.', 30.00, 40, 10, 'assets/img/Fruit.Tea.Clean.jpeg', 1, 0, '2026-10-20 05:00:29', '2026-10-21 06:08:02'),
(71, 'DST-005', 'Mango Graham Float', 'desserts', 'Chilled layered dessert made with graham crackers, ripe mango slices, and sweetened cream.', 40.00, 30, 10, 'assets/img/Mango.Graham.Float.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 05:01:00'),
(72, 'DST-006', 'Chocolate Brownie', 'desserts', 'Dense, square-shaped chocolate pastry with a dark-brown surface and a moist, fudgy interior.', 30.00, 30, 10, 'assets/img/Chocolate.Brownie.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:49:00'),
(73, 'DST-007', 'Chocolate Cupcake', 'desserts', 'Individual chocolate-flavored cake with a soft crumb, topped with chocolate frosting.', 30.00, 30, 10, 'assets/img/Chocolate.Cupcake.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:49:09'),
(74, 'DST-008', 'Vanilla Cupcake', 'desserts', 'Small vanilla-flavored cake with a light golden surface and a swirl of vanilla frosting.', 30.00, 30, 10, 'assets/img/Vanilla.Cupcake.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-23 03:36:37'),
(75, 'DST-009', 'Banana Cake Slice', 'desserts', 'Moist banana-flavored cake with a light-brown crust and a soft interior, cut into a single serving.', 30.00, 30, 10, 'assets/img/Banana.Cake.Sliced.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:45:24'),
(76, 'DST-010', 'Donut', 'desserts', 'Ring-shaped or filled fried dough with a golden exterior and a sugar, glaze, or chocolate coating.', 20.00, 40, 10, 'assets/img/Donut.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:50:08'),
(77, 'DST-011', 'Puto', 'desserts', 'Small, round Filipino steamed rice cakes with a soft, fluffy texture and a white or lightly colored surface.', 15.00, 40, 10, 'assets/img/Puto.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-23 03:23:09'),
(78, 'DST-012', 'Kutsinta', 'desserts', 'Small Filipino steamed rice cakes with a glossy, chewy, amber-brown appearance, traditionally topped with grated coconut.', 15.00, 40, 10, 'assets/img/Kutsinta.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 05:02:23'),
(79, 'DST-013', 'Buko Pandan', 'desserts', 'Chilled Filipino dessert containing green pandan-flavored gelatin cubes, young coconut strips, and sweetened cream.', 30.00, 30, 10, 'assets/img/Buko.Pandan.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:48:09'),
(80, 'DST-014', 'Fruit Salad Cup', 'desserts', 'Chilled mixture of fruit cocktail pieces, nata de coco, and creamy sweetened dressing served in an individual cup.', 35.00, 30, 10, 'assets/img/Fruit.Salad.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:50:55'),
(81, 'DST-015', 'Graham Balls', 'desserts', 'Small, round sweet treats made from crushed graham crackers and condensed milk, coated with fine graham crumbs.', 20.00, 40, 10, 'assets/img/Graham.Balls.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 05:01:09'),
(82, 'DST-016', 'Chocolate Pudding', 'desserts', 'Smooth, glossy chocolate dessert with a thick custard-like consistency, served chilled in a cup.', 25.00, 30, 10, 'assets/img/Chocolate.Pudding.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:49:20'),
(83, 'DST-017', 'Jelly Cups', 'desserts', 'Individual portions of translucent fruit-flavored gelatin with a smooth, firm texture.', 15.00, 40, 10, 'assets/img/Jelly.Cup.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 05:02:05'),
(84, 'DST-018', 'Ice Cream Cup', 'desserts', 'A single serving of frozen ice cream with a smooth texture in the selected flavor, placed in a small serving cup.', 25.00, 40, 15, 'assets/img/Ice.Cream.Vanilla.in.Cup.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 05:01:44'),
(85, 'DST-019', 'Cassava Cake', 'desserts', 'Filipino baked cassava dessert with a dense, moist, pale-yellow interior and a golden creamy topping.', 30.00, 30, 10, 'assets/img/Cassava.Cake.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:48:37'),
(86, 'DST-020', 'Ube Halaya Cup', 'desserts', 'Thick, smooth Filipino purple yam dessert with a deep violet color and a creamy, slightly dense consistency.', 30.00, 30, 10, 'assets/img/Ube.Halaya.in.Cup.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-23 03:45:00'),
(87, 'DST-021', 'Fruit Jelly', 'desserts', 'Chilled gelatin dessert containing small fruit pieces suspended in a translucent fruit-flavored jelly.', 20.00, 40, 10, 'assets/img/Fruit.Jelly.Clean.jpeg', 1, 0, '2026-10-20 05:00:34', '2026-10-22 04:50:31'),
(88, 'RCE-023', 'Beef Pares with Rice', 'rice_meals', 'Tender beef cubes braised in a dark, savory-sweet broth with star anise and garlic, served with white rice.', 75.00, 40, 10, 'assets/img/Beef.Pares.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(89, 'RCE-024', 'Tuna with Rice', 'rice_meals', 'Flaked tuna saut?ed with chopped onions, chili, and seasonings, served with steamed white rice.', 55.00, 39, 10, 'assets/img/Tuna.Sisig.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-26 04:23:20'),
(90, 'RCE-025', 'Beef Meatballs with Rice', 'rice_meals', 'Seasoned beef meatballs covered in brown gravy and served with steamed white rice.', 65.00, 40, 10, 'assets/img/Beef.Meatballs.with.Rice.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(91, 'DST-022', 'Caramel Cake', 'desserts', 'Soft layered cake with caramel frosting and a glossy caramel topping.', 45.00, 40, 10, 'assets/img/Caramel.Cake.Sliced.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(92, 'DST-023', 'Buko Pie', 'desserts', 'Golden flaky pastry filled with creamy young coconut custard.', 35.00, 40, 10, 'assets/img/Buko.Pie.Sliced.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(93, 'DST-024', 'Chocolate Chip Cookies', 'desserts', 'Round golden-brown cookies with visible chocolate chips and slightly crisp edges.', 20.00, 40, 10, 'assets/img/Chocolate.Chip.Cookies.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(94, 'DST-025', 'Mango Ice Candy', 'desserts', 'Frozen mango-flavored mixture in a narrow transparent plastic ice-candy wrapper.', 10.00, 40, 10, 'assets/img/Mango.Ice.Candy.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(95, 'DST-026', 'Strawberry Panna Cotta', 'desserts', 'Pale pink or cream-colored set panna cotta topped with strawberry sauce.', 40.00, 40, 10, 'assets/img/Strawberry.Panna.Cotta.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(96, 'DST-027', 'Crema de Fruta Cup', 'desserts', 'Layered sponge cake, custard, cream, fruit cocktail, and a glossy gelatin topping served in a cup.', 35.00, 40, 10, 'assets/img/Crema.de.Fruita.in.Cup.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-26 04:19:21'),
(97, 'DST-028', 'Ube Cheese Pandesal', 'desserts', 'Purple bread roll filled with sweet ube and a soft cheese center, with a lightly browned crust.', 20.00, 40, 10, 'assets/img/Ube.Cheese.Pandesal.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(98, 'DST-029', 'Oreo Cheesecake Cup', 'desserts', 'Individual layered dessert with crushed chocolate cookies, creamy cheesecake filling, and cookie crumbs on top.', 45.00, 40, 10, 'assets/img/Oreo.Cheesecake.Cup.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(99, 'DST-030', 'Chocolate Mousse Cup', 'desserts', 'Smooth, airy chocolate mousse served in a transparent dessert cup with a rich brown surface.', 35.00, 40, 10, 'assets/img/Chocolate.Mousse.Cup.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(100, 'DST-031', 'Red Velvet Cupcake', 'desserts', 'Deep red cupcake topped with a swirl of white cream cheese frosting.', 30.00, 40, 10, 'assets/img/Red.Velvet.Cupcake.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(101, 'DST-032', 'Mini Egg Tart', 'desserts', 'Small pastry shell filled with smooth yellow egg custard and a lightly browned top.', 20.00, 40, 10, 'assets/img/Mini.Egg.Tart.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(102, 'DST-033', 'Cinnamon Roll', 'desserts', 'Soft spiral-shaped sweet bread coated with cinnamon sugar and topped with white icing.', 35.00, 40, 10, 'assets/img/Cinnamon.Roll.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(103, 'SNK-024', 'Squid Balls', 'snacks', 'Round squid-flavored fish paste balls with a lightly crisp golden exterior, served on wooden skewers.', 20.00, 40, 10, 'assets/img/Squid.Balls.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(104, 'SNK-025', 'Mozzarella Sticks', 'snacks', 'Breaded mozzarella cheese sticks fried until golden, with melted cheese inside.', 35.00, 40, 10, 'assets/img/Mozzarella.Sticks.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(105, 'SNK-026', 'Cheese Corn Dog', 'snacks', 'A sausage coated in thick golden batter with a melted cheese filling or topping, served on a stick.', 40.00, 40, 10, 'assets/img/Cheese.Corn.Dog.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-11-03 01:52:50'),
(106, 'SNK-027', 'Siopao Bola-Bola', 'snacks', 'Soft steamed white bun filled with a seasoned pork meatball and savory filling.', 30.00, 40, 10, 'assets/img/Fresh.Siopao.Bola-Bola.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(107, 'SNK-028', 'Pork Siomai with Chili Garlic', 'snacks', 'Steamed pork dumplings topped with chili-garlic oil and served with soy-based dipping sauce.', 30.00, 40, 10, 'assets/img/Fresh.Pork.Siomai.with.Chili.Garlic.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(108, 'SNK-029', 'Potato Cheese Balls', 'snacks', 'Round mashed-potato snacks with a cheese center, coated in breadcrumbs and fried until golden.', 30.00, 40, 10, 'assets/img/Potato.Cheese.Balls.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(109, 'SNK-030', 'Spanish Bread', 'snacks', 'Soft, golden Filipino bread rolls filled with sweet buttery breadcrumbs.', 10.00, 40, 10, 'assets/img/Spanish.Bread.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(110, 'SNK-031', 'Ham & Cheese Bread Rolls', 'snacks', 'Soft bread wrapped around ham and cheese, baked until lightly golden.', 25.00, 40, 10, 'assets/img/Ham.and.Cheese.Bread.Rolls.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(111, 'SNK-032', 'Vegetable Tempura', 'snacks', 'Assorted vegetable slices coated in light batter and fried until crisp and golden.', 25.00, 40, 10, 'assets/img/Vegetable.Tempuras.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(112, 'SNK-033', 'Mini Pizza Bread', 'snacks', 'Small bread rounds topped with pizza sauce, melted cheese, and diced ham or sausage.', 30.00, 40, 10, 'assets/img/Mini.Pizza.Bread.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(113, 'DNK-022', 'Cookies & Cream Shake', 'drinks', 'Creamy milkshake blended with vanilla ice cream and dark chocolate cookie pieces.', 45.00, 40, 10, 'assets/img/Cookies.and.Cream.Shake.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(114, 'DNK-023', 'Chocolate Milkshake', 'drinks', 'Thick chocolate ice cream-based drink topped with a creamy chocolate-colored surface.', 45.00, 40, 10, 'assets/img/Chocolate.Milkshake.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(115, 'DNK-024', 'Wintermelon Milktea', 'drinks', 'Brown wintermelon-flavored milk tea served over ice, optionally with black tapioca pearls.', 45.00, 40, 10, 'assets/img/Wintermelon.Milktea.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(116, 'DNK-025', 'Taro Milktea', 'drinks', 'Light purple taro-flavored milk tea with a creamy appearance, served over ice.', 45.00, 40, 10, 'assets/img/Taro.Milktea.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(117, 'SNK-034', 'Chicken Popcorn', 'snacks', 'Bite-sized chicken pieces coated in seasoned breading and fried until golden and crunchy.', 45.00, 40, 10, 'assets/img/Chicken.Popcorn.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
(118, 'RCE-026', 'Chicken BBQ Rice Bowl', 'rice_meals', 'Grilled marinated chicken in sweet-savory barbecue sauce, served over steamed white rice.', 60.00, 40, 10, 'assets/img/Chicken.BBQ.Rice.Bowl.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:22:22'),
(119, 'RCE-027', 'Chicken Mushroom Rice Bowl', 'rice_meals', 'Sliced chicken with mushrooms in a savory sauce, served over steamed white rice.', 55.00, 40, 10, 'assets/img/Chicken.Mushroom.Rice.Bowl.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:23:32'),
(120, 'RCE-028', 'Chicken Tinola with Rice', 'rice_meals', 'Chicken simmered in a light ginger broth with green papaya and leafy greens, served with rice.', 55.00, 40, 10, 'assets/img/Chicken.Tinola.with.Rice.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:24:16'),
(121, 'RCE-029', 'Pork Sinigang with Rice', 'rice_meals', 'Sour tamarind-based pork soup with tomatoes, radish, string beans, and kangkong, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Pork.Sinigang.with.Rice.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:29:12'),
(122, 'RCE-030', 'Bicol Express', 'rice_meals', 'Pork slices simmered in coconut milk, bagoong, garlic, onions, and chili peppers, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Pork.Bicol.Express.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:21:11'),
(123, 'RCE-032', 'Sweet and Sour Pork with Rice', 'rice_meals', 'Pork pieces coated in a tangy sweet-and-sour sauce, served with steamed white rice.', 60.00, 40, 10, 'assets/img/Sweet.and.Sour.Pork.with.Rice.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:30:50'),
(124, 'RCE-033', 'Korean Fried Chicken', 'rice_meals', 'Crispy chicken pieces coated in a sweet, savory, and slightly spicy gochujang-garlic sauce, served with steamed white rice.', 65.00, 40, 10, 'assets/img/Korean.Fried.Chicken.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:28:05'),
(125, 'RCE-034', 'Japanese Chicken Katsu Curry', 'rice_meals', 'Crispy breaded chicken cutlet with rich Japanese curry sauce and steamed white rice.', 75.00, 40, 10, 'assets/img/Japanese.Chicken.Katsu.Curry.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:27:30'),
(126, 'RCE-035', 'Garlic Butter Shrimp', 'rice_meals', 'Juicy shrimp sautéed in garlic butter sauce and herbs, served with steamed white rice.', 70.00, 40, 10, 'assets/img/Garlic.Butter.Shrimp.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 05:27:06'),
(127, 'SNK-035', 'Beef Empanada', 'snacks', 'Golden pastry pocket filled with seasoned ground beef, diced potatoes, carrots, and onions.', 30.00, 40, 10, 'assets/img/Beef.Empanada.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:01:23'),
(128, 'SNK-036', 'Beef Shawarma Wrap', 'snacks', 'Flatbread filled with seasoned beef strips, onions, lettuce, tomatoes, and creamy garlic sauce.', 50.00, 40, 10, 'assets/img/Beef.Shawarma.Wrap.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:01:37'),
(129, 'SNK-037', 'Chicken Empanada', 'snacks', 'Golden pastry with a sealed crust and a savory chicken filling.', 25.00, 40, 10, 'assets/img/Chicken.Empanada.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:02:54'),
(130, 'SNK-038', 'Chicken Shawarma Wrap', 'snacks', 'Flatbread filled with seasoned chicken strips, vegetables, and creamy garlic sauce.', 45.00, 40, 10, 'assets/img/Chicken.Shawarma.Wrap.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:03:07'),
(131, 'SNK-039', 'Dynamite Cheese Sticks', 'snacks', 'Cheese-filled sticks coated in crispy wrapper and fried until golden, with a soft melted cheese center.', 30.00, 40, 10, 'assets/img/Dynamite.Cheese.Sticks.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:04:57'),
(132, 'SNK-040', 'Chicken Skin', 'snacks', 'Crispy seasoned chicken skin served with a dipping sauce.', 25.00, 40, 10, 'assets/img/Chicken.Skin.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:03:18'),
(133, 'SNK-041', 'Crispy Chicharon Bulaklak', 'snacks', 'Pork ruffle fat deep-fried until puffed, airy, and golden-brown. Served with spiced vinegar for dipping.', 65.00, 30, 10, 'assets/img/Crispy.Chicharon.Bulaklak.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:04:14'),
(134, 'SNK-042', 'Crispy Crab Sticks', 'snacks', 'Imitation crab sticks in a light seasoned batter, deep-fried until golden and crispy. Served with a dipping sauce.', 40.00, 40, 10, 'assets/img/Crispy.Crab.Sticks.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:04:37'),
(135, 'SNK-043', 'Takoyaki', 'snacks', 'Japanese round batter balls with a savory filling, topped with takoyaki sauce and mayo.', 35.00, 40, 10, 'assets/img/Japanese.Takoyaki.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:06:52'),
(136, 'SNK-044', 'Mini Chicken Quesadilla', 'snacks', 'Mini tortilla filled with chicken, vegetables, and melted cheese, toasted until golden.', 40.00, 40, 10, 'assets/img/Mini.Chicken.Quasadilla.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:03:29'),
(137, 'SNK-045', 'Cheese Quesadilla', 'snacks', 'Soft flour tortilla filled with melted cheese, toasted until golden and sliced into triangles.', 45.00, 40, 10, 'assets/img/Cheese.Quasadilla.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:02:30'),
(138, 'SNK-046', 'Garlic Bread', 'snacks', 'Bread slices spread with garlic butter and toasted until lightly crisp.', 25.00, 40, 10, 'assets/img/Garlic.Bread.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:05:14'),
(139, 'SNK-047', 'Mini Sausage Rolls', 'snacks', 'Bite-sized sausages wrapped in flaky puff pastry and baked until golden and crisp.', 30.00, 40, 10, 'assets/img/Mini.Sausage.Rolls.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:06:09'),
(140, 'SNK-048', 'Pork Barbecue Skewers', 'snacks', 'Pork pieces marinated in sweet and savory barbecue sauce, grilled on bamboo skewers with a smoky glaze.', 25.00, 40, 10, 'assets/img/Pork.Barbecue.Skewers.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:06:29'),
(141, 'SNK-049', 'Corn Cheese', 'snacks', 'Sweet corn kernels mixed with mayonnaise, butter, and melted cheese, served warm in a small bowl.', 30.00, 40, 10, 'assets/img/Corn.Cheese.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:03:50'),
(142, 'SNK-050', 'Mini Hotcake', 'snacks', 'Warm griddled mini hotcakes served with syrup.', 30.00, 40, 10, 'assets/img/Mini.Hotcakes.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:05:49'),
(143, 'DNK-026', 'Apple Iced Tea', 'drinks', 'Amber-colored iced tea infused with sweet green apple flavor, served with ice.', 20.00, 50, 15, 'assets/img/Apple.Iced.Tea.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:45:59'),
(144, 'DNK-027', 'Blueberry Milkshake', 'drinks', 'Thick, chilled blended drink made with blueberry and milk, served in a tall clear cup.', 40.00, 40, 10, 'assets/img/Blueberry.Milkshake.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:46:18'),
(145, 'DNK-028', 'Buko Pandan Drink', 'drinks', 'Chilled buko pandan drink with coconut strips, served in a clear cup.', 25.00, 50, 15, 'assets/img/Buko.Pandan.Drink.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:47:34'),
(146, 'DNK-029', 'Honey Lemon Tea', 'drinks', 'Chilled honey lemon tea served over ice in a clear cup.', 25.00, 50, 15, 'assets/img/Honey.Lemon.Tea.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:53:39'),
(147, 'DNK-030', 'Peach Iced Tea', 'drinks', 'Chilled peach iced tea served over ice in a clear cup.', 25.00, 50, 15, 'assets/img/Peach.Iced.Tea.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:52:37'),
(148, 'DNK-031', 'Lychee Yakult', 'drinks', 'Chilled lychee and Yakult drink, mixed until evenly combined and served cold.', 30.00, 40, 10, 'assets/img/Lychee.Yakult.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:52:14'),
(149, 'DNK-032', 'Pineapple Yakult', 'drinks', 'Chilled pineapple and Yakult drink, mixed until evenly combined and served cold.', 30.00, 40, 10, 'assets/img/Pineapple.Yakult.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:52:57'),
(150, 'DNK-033', 'Dark Chocolate Frappe', 'drinks', 'Chilled coffee-based drink blended with dark chocolate, served over ice or blended until smooth.', 55.00, 40, 10, 'assets/img/Dark.Chocolate.Frappe.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 20:51:23'),
(151, 'DST-034', 'Apple Pie', 'desserts', 'Golden flaky pastry filled with cinnamon-seasoned apple chunks and a soft fruit filling.', 30.00, 30, 10, 'assets/img/Apple.Pie.Sliced.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:12:00'),
(152, 'DST-035', 'Biko', 'desserts', 'Filipino sticky rice cake cooked with coconut milk and brown sugar, topped with caramelized coconut.', 20.00, 40, 10, 'assets/img/Biko.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:12:17'),
(153, 'DST-036', 'Brown Sugar Crème Caramel', 'desserts', 'Smooth steamed egg-and-milk custard topped with glossy caramel syrup.', 35.00, 30, 10, 'assets/img/Brown.Sugar.Crème.Caramel.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:13:50'),
(154, 'DST-037', 'Mini Churros', 'desserts', 'Freshly fried mini churros cooked until golden and rolled in cinnamon sugar.', 35.00, 40, 10, 'assets/img/Mini.Churros.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:15:29'),
(155, 'DST-038', 'Cream Puffs', 'desserts', 'Light choux pastry puffs filled with sweet cream.', 35.00, 30, 10, 'assets/img/Cream.Puffs.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:14:58'),
(156, 'DST-040', 'Strawberry Jelly Pudding', 'desserts', 'Creamy strawberry pudding layered with sweet strawberry jelly cubes, served chilled.', 35.00, 30, 10, 'assets/img/Strawberry.Jelly.Pudding.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:15:12'),
(157, 'DST-041', 'Coffee Jelly', 'desserts', 'Chilled coffee jelly cubes in a single-serve cup with sweetened cream.', 30.00, 30, 10, 'assets/img/Coffee.Jelly.Cup.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-03 01:43:16'),
(158, 'DST-042', 'Chocolate Eclair', 'desserts', 'Light, airy choux pastry filled with smooth vanilla cream and topped with chocolate glaze.', 40.00, 30, 10, 'assets/img/Chocolate.Eclair.Clean.jpeg', 1, 0, '2026-11-02 04:52:44', '2026-11-02 21:14:23'),
(159, 'DST-043', 'Mango Sago', 'desserts', 'Chilled dessert of sweet ripe mango chunks, chewy tapioca pearls, and creamy mango milk.', 30.00, 40, 10, 'assets/img/Mango.Sago.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:57:44'),
(160, 'DST-044', 'Oreo Brownies', 'desserts', 'Rich, fudgy chocolate brownies topped with crunchy Oreo cookie pieces.', 35.00, 30, 10, 'assets/img/Oreo.Brownies.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:58:37'),
(161, 'DST-045', 'Mini Sans Rival', 'desserts', 'Single-serve sans rival with layers of crisp cashew meringue and buttercream.', 35.00, 30, 10, 'assets/img/Mini.Sans.Rival.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:58:56'),
(162, 'DST-046', 'Maja Blanca', 'desserts', 'Creamy Filipino coconut pudding with sweet corn kernels, topped with toasted grated coconut.', 15.00, 40, 10, 'assets/img/Maja.Blanca.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:59:28'),
(163, 'DST-047', 'Sapin-Sapin', 'desserts', 'Colorful layered sticky rice cake made with glutinous rice flour and coconut milk, topped with toasted grated coconut.', 15.00, 40, 10, 'assets/img/Sapin-Sapin.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:28:12'),
(164, 'DST-048', 'Ginataang Bilo-Bilo', 'desserts', 'Chewy rice balls, sweet bananas, tapioca pearls, and jackfruit simmered in creamy coconut milk.', 25.00, 30, 10, 'assets/img/Ginataang.Bilo-Bilo.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:29:07'),
(165, 'DST-049', 'Palitaw', 'desserts', 'Glutinous rice cake boiled until it floats, coated with grated coconut, sugar, and toasted sesame seeds.', 15.00, 40, 10, 'assets/img/Palitaw.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:29:16'),
(166, 'DST-050', 'Coconut Macaroons', 'desserts', 'Soft and chewy coconut cookies made with shredded coconut and condensed milk, baked until golden brown.', 15.00, 40, 10, 'assets/img/Coconut.Macaroons.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:44:32'),
(167, 'DST-051', 'Mango Crepe Roll', 'desserts', 'Soft, thin crepes filled with fresh mango slices and whipped cream, rolled into bite-sized portions.', 30.00, 30, 10, 'assets/img/Mango.Crepe.Roll.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:58:02'),
(168, 'DST-052', 'Chocolate Swiss Roll', 'desserts', 'Soft chocolate sponge cake rolled with smooth chocolate cream filling and finished with chocolate coating.', 30.00, 30, 10, 'assets/img/Chocolate.Swiss.Roll.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:29:44'),
(169, 'DST-053', 'Ube Mochi Balls', 'desserts', 'Chewy rice flour balls infused with sweet ube flavor, soft and stretchy with a rich purple color.', 25.00, 40, 10, 'assets/img/Ube.Mochi.Balls.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:29:56'),
(170, 'DST-054', 'Mini Donut Bites', 'desserts', 'Bite-sized, fluffy mini donuts coated with powdered sugar.', 20.00, 40, 10, 'assets/img/Mini.Donut.Bites.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:30:07'),
(171, 'DST-055', 'Peanut Butter Cookies', 'desserts', 'Soft and chewy cookies made with creamy peanut butter, baked until lightly golden.', 20.00, 40, 10, 'assets/img/Peanut.Butter.Cookies.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:30:21'),
(172, 'DST-056', 'Banana Pudding Cup', 'desserts', 'Layered dessert of fresh banana slices, smooth vanilla pudding, and crushed vanilla cookies, served chilled in a cup.', 25.00, 30, 10, 'assets/img/Banana.Pudding.Cup.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:30:36'),
(173, 'DST-057', 'Mango Tapioca Pudding', 'desserts', 'Chilled creamy dessert with sweet mango chunks, soft tapioca pearls, and milk pudding.', 25.00, 30, 10, 'assets/img/Mango.Tapioca.Pudding.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:58:16'),
(174, 'DST-058', 'Cassava Cheese Pudding', 'desserts', 'Creamy cassava dessert made with grated cassava, coconut milk, condensed milk, and melted cheese.', 25.00, 30, 10, 'assets/img/Cassava.Cheese.Pudding.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:33:14'),
(175, 'DST-059', 'Pichi-Pichi', 'desserts', 'Soft and chewy cassava delicacy coated with freshly grated coconut.', 15.00, 40, 10, 'assets/img/Pichi-Pichi.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:33:24'),
(176, 'DST-060', 'Yema Cake', 'desserts', 'Soft and fluffy chiffon cake layered and topped with creamy yema custard.', 30.00, 30, 10, 'assets/img/Yema.Cake.Sliced.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:33:36'),
(177, 'DST-061', 'Strawberry Jelly Cheesecake', 'desserts', 'Creamy cheesecake with a buttery biscuit crust, smooth cream cheese filling, and strawberry jelly topping.', 40.00, 30, 10, 'assets/img/Strawberry.Jelly.Cheesecake.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:33:57'),
(178, 'DNK-035', 'Melon Milk Drink', 'drinks', 'Sweet and refreshing melon-flavored milk drink with ice, served chilled.', 25.00, 50, 15, 'assets/img/Melon.Milk.Drink.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:34:09'),
(179, 'SNK-051', 'Beef Nachos', 'snacks', 'Crispy tortilla chips topped with seasoned ground beef, melted cheese, diced tomatoes, and nacho sauce.', 45.00, 40, 10, 'assets/img/Beef.Nachos.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:37:16'),
(180, 'SNK-052', 'Tuna Pie', 'snacks', 'Golden, flaky pastry filled with creamy seasoned tuna, onions, and mayonnaise.', 30.00, 40, 10, 'assets/img/Tuna.Pie.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:34:31'),
(181, 'SNK-053', 'Tuna Melt Toast', 'snacks', 'Toasted bread topped with creamy seasoned tuna and melted cheese, served warm.', 35.00, 40, 10, 'assets/img/Tuna.Melt.Toast.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:34:43'),
(182, 'SNK-054', 'Mini Beef Sliders', 'snacks', 'Mini burger buns with juicy beef patties, melted cheese, lettuce, and burger sauce.', 40.00, 40, 10, 'assets/img/Mini.Beef.Sliders.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:34:59'),
(183, 'SNK-055', 'Sausage Cheese Roll', 'snacks', 'Savory sausage wrapped in soft bread dough with melted cheese, baked until golden brown.', 30.00, 40, 10, 'assets/img/Sausage.Cheese.Roll.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 01:35:12'),
(184, 'SNK-056', 'Potato Cheese Croquettes', 'snacks', 'Crispy golden potato balls filled with melted cheese, coated in breadcrumbs and deep-fried.', 30.00, 40, 10, 'assets/img/Potato.Cheese.Croquettes.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:57:10'),
(185, 'SNK-057', 'Crispy Bacon Potato Balls', 'snacks', 'Mashed potato balls mixed with crispy bacon bits, coated in breadcrumbs and deep-fried until golden.', 35.00, 40, 10, 'assets/img/Crispy.Bacon.Potato.Balls.Clean.jpeg', 1, 0, '2026-11-03 00:54:40', '2026-11-03 00:56:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_sku` (`sku`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_featured` (`is_featured`,`is_available`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
