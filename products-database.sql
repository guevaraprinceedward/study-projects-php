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
(1, 'Espresso', 85.00, 'mains', 'Bold espresso', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Espresso.Product.png', 100, 10, NULL, 'laguna'),
(2, 'Americano', 100.00, 'mains', 'Smooth black coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Americano.Product-V1.png', 100, 10, NULL, 'laguna'),
(3, 'Cappuccino', 130.00, 'mains', 'Classic cappuccino', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cappuccino.Product.png', 99, 10, NULL, 'laguna'),
(4, 'Caffe Latte', 140.00, 'mains', 'Creamy latte', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caffe.Latte.png', 100, 10, NULL, 'laguna'),
(5, 'Flat White', 145.00, 'mains', 'Smooth flat white', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Flat-White.Product.png', 100, 10, NULL, 'laguna'),
(6, 'Caramel Macchiato', 155.00, 'mains', 'Caramel coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Caramel-Macchiato.Product.png', 98, 10, NULL, 'laguna'),
(7, 'Mocha', 150.00, 'mains', 'Chocolate coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Mocha.Product.png', 100, 10, NULL, 'laguna'),
(8, 'Hazelnut Latte', 155.00, 'mains', 'Nutty latte', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Hazelnut-Latte.Product.png', 100, 10, NULL, 'laguna'),
(9, 'White Chocolate Mocha', 160.00, 'mains', 'Sweet mocha', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/White-Chocolate-Mocha.Product.png', 100, 10, NULL, 'laguna'),
(10, 'Spanish Latte', 150.00, 'mains', 'Sweet latte', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Spanish-Latte.Product.png', 100, 10, NULL, 'laguna'),
(11, 'Vanilla Latte', 145.00, 'mains', 'Vanilla coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Vanilla-Latte.Product.png', 100, 10, NULL, 'laguna'),
(12, 'Cortado', 135.00, 'mains', 'Balanced coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cortado-Latte.Product.png', 100, 10, NULL, 'laguna'),
(13, 'Cold Brew', 160.00, 'drinks', 'Cold coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Cold-Brew.Product.png', 100, 10, NULL, 'laguna'),
(14, 'Iced Americano', 110.00, 'drinks', 'Iced coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Americano.Product.png', 100, 10, NULL, 'laguna'),
(15, 'Iced Caramel Latte', 165.00, 'drinks', 'Caramel iced', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Caramel-Latte.Product.png', 100, 10, NULL, 'laguna'),
(16, 'Matcha Latte', 150.00, 'drinks', 'Matcha drink', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Matcha-Latte.Product.png', 100, 10, NULL, 'laguna'),
(17, 'Iced Mocha', 155.00, 'drinks', 'Chocolate iced', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Mocha.Product.png', 100, 10, NULL, 'laguna'),
(18, 'Dirty Matcha', 165.00, 'drinks', 'Matcha espresso', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Dirty-Matcha.Product.png', 0, 10, NULL, 'laguna'),
(19, 'Espresso Tonic', 150.00, 'drinks', 'Tonic coffee', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Espresso-Tonic.Product.png', 100, 10, NULL, 'laguna'),
(20, 'Iced Hazelnut Latte', 165.00, 'drinks', 'Hazelnut iced', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Iced-Hazelnut-Latte.Product.png', 100, 10, NULL, 'laguna'),
(21, 'Strawberry Lemonade', 120.00, 'drinks', 'Fruit drink', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Strawberry-Lemonade.Product.png', 100, 10, NULL, 'laguna'),
(22, 'Sparkling Water', 80.00, 'drinks', 'Water', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Sparkling-Water.Product.png', 100, 10, NULL, 'laguna'),
(23, 'Mango Soda Float', 145.00, 'drinks', 'Mango float', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Mango-Soda-Float.Product.png', 100, 10, NULL, 'laguna'),
(24, 'Hot Chocolate', 130.00, 'drinks', 'Chocolate drink', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Hot-Chocolate.Product.png', 100, 10, NULL, 'laguna'),
(25, 'Croissant', 95.00, 'sides', 'Pastry', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Croissant.Product.png', 100, 10, NULL, 'laguna'),
(26, 'Banana Bread', 80.00, 'sides', 'Bread', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Banana-Bread.Product.png', 100, 10, NULL, 'laguna'),
(27, 'Blueberry Muffin', 85.00, 'sides', 'Muffin', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Blueberry-Muffin.Product.png', 100, 10, NULL, 'laguna'),
(28, 'Cinnamon Roll', 110.00, 'sides', 'Pastry', '', 100, 10, NULL, 'laguna'),
(29, 'Chocolate Chip Cookie', 70.00, 'sides', 'Cookie', '', 100, 10, NULL, 'laguna'),
(30, 'Ham and Cheese Panini', 140.00, 'sides', 'Panini', 'https://raw.githubusercontent.com/guevaraprinceedward/study-projects-php/refs/heads/main/Ham-and-Cheese-Panini.Product.png', 100, 10, NULL, 'laguna'),
(31, 'Breakfast Sandwich', 150.00, 'sides', 'Sandwich', '', 100, 10, NULL, 'laguna'),
(32, 'Avocado Toast', 160.00, 'sides', 'Toast', '', 100, 10, NULL, 'laguna'),
(33, 'Cheese Danish', 95.00, 'sides', 'Pastry', '', 100, 10, NULL, 'laguna'),
(34, 'Granola Bar', 65.00, 'sides', 'Snack', '', 100, 10, NULL, 'laguna'),
(35, 'Egg Salad Sandwich', 130.00, 'sides', 'Sandwich', '', 100, 10, NULL, 'laguna'),
(36, 'Spinach and Feta Wrap', 145.00, 'sides', 'Wrap', '', 100, 10, NULL, 'laguna'),
(37, 'Tiramisu', 130.00, 'desserts', 'Dessert', '', 100, 10, NULL, 'laguna'),
(38, 'Chocolate Lava Cake', 145.00, 'desserts', 'Cake', '', 100, 10, NULL, 'laguna'),
(39, 'Affogato', 140.00, 'desserts', 'Coffee dessert', '', 100, 10, NULL, 'laguna'),
(40, 'Cheesecake', 135.00, 'desserts', 'Cake', '', 100, 10, NULL, 'laguna'),
(41, 'Brownies', 90.00, 'desserts', 'Chocolate', '', 100, 10, NULL, 'laguna'),
(42, 'Creme Brulee', 150.00, 'desserts', 'Custard', '', 100, 10, NULL, 'laguna'),
(43, 'Mango Panna Cotta', 130.00, 'desserts', 'Dessert', '', 100, 10, NULL, 'laguna'),
(44, 'Strawberry Shortcake', 140.00, 'desserts', 'Cake', '', 100, 10, NULL, 'laguna'),
(45, 'Macarons (3 pcs)', 120.00, 'desserts', 'French dessert', '', 100, 10, NULL, 'laguna'),
(46, 'Chocolate Mousse', 125.00, 'desserts', 'Mousse', '', 100, 10, NULL, 'laguna'),
(47, 'Leche Flan', 95.00, 'desserts', 'Filipino dessert', '', 100, 10, NULL, 'laguna'),
(48, 'Ube Cheesecake', 145.00, 'desserts', 'Ube cake', '', 100, 10, NULL, 'laguna'),
(49, 'Espresso Manila', 90.00, 'coffee', 'Bold Manila-style espresso shot.', '', 100, 10, NULL, 'manila'),
(50, 'Americano Manila', 105.00, 'coffee', 'Classic black coffee, Manila style.', '', 100, 10, NULL, 'manila'),
(51, 'Cappuccino Manila', 135.00, 'coffee', 'Creamy cappuccino with local flair.', '', 100, 10, NULL, 'manila'),
(52, 'Caffe Latte Manila', 145.00, 'coffee', 'Smooth latte, Manila edition.', '', 100, 10, NULL, 'manila'),
(53, 'Mocha Manila', 155.00, 'coffee', 'Rich chocolate coffee blend.', '', 100, 10, NULL, 'manila'),
(54, 'Flat White Manila', 150.00, 'coffee', 'Strong and creamy flat white.', '', 100, 10, NULL, 'manila'),
(55, 'Croissant Manila', 100.00, 'sides', 'Freshly baked buttery croissant.', '', 100, 10, NULL, 'manila'),
(56, 'Banana Bread Manila', 85.00, 'sides', 'Moist homemade banana bread.', '', 100, 10, NULL, 'manila'),
(57, 'Ham Panini Manila', 145.00, 'sides', 'Grilled ham and cheese panini.', '', 100, 10, NULL, 'manila'),
(58, 'Avocado Toast Manila', 165.00, 'sides', 'Sourdough with smashed avocado.', '', 100, 10, NULL, 'manila'),
(59, 'Tiramisu Manila', 135.00, 'desserts', 'Classic Italian tiramisu.', '', 100, 10, NULL, 'manila'),
(60, 'Cheesecake Manila', 140.00, 'desserts', 'Creamy New York style cheesecake.', '', 100, 10, NULL, 'manila');

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
