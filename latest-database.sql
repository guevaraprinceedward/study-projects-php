-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2026 at 10:33 PM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uAt0/0UlW', '2026-04-23 01:28:53'),
(3, 'Webmaster', '$2y$10$wbZjAOvFhiuzfvjnwgJ9XeN1UDPXlv.Gk4P08AtS90pZoG3At/peq', '2026-04-23 18:49:25');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('user','admin') NOT NULL DEFAULT 'user',
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `date` date NOT NULL,
  `photo_in` text DEFAULT NULL,
  `photo_out` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'present',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `punctuality` varchar(20) DEFAULT NULL,
  `minutes_late` int(11) DEFAULT 0,
  `override_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `branch` varchar(20) DEFAULT 'laguna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `created_at`, `status`, `notes`, `guest_name`, `guest_phone`, `guest_gcash`, `payment_method`, `branch`) VALUES
(1, 1, 130.00, '2026-04-21 21:00:52', 'pending', '', NULL, NULL, NULL, 'cash', 'laguna'),
(2, 1, 12870.00, '2026-04-23 00:24:49', 'pending', '', NULL, NULL, NULL, 'cash', 'laguna'),
(3, 2, 130.00, '2026-04-23 01:51:49', 'pending', '', NULL, NULL, NULL, 'cash', 'laguna'),
(4, 2, 16500.00, '2026-04-23 01:54:21', 'pending', '', NULL, NULL, NULL, 'cash', 'laguna'),
(5, 2, 310.00, '2026-04-23 01:56:29', 'pending', '', NULL, NULL, NULL, 'cash', 'laguna');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 130.00),
(2, 2, 3, 99, 130.00),
(3, 3, 3, 1, 130.00),
(4, 4, 18, 100, 165.00),
(5, 5, 6, 2, 155.00);

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
(1, 'Espresso Manila', 90.00, 'coffee', 'Bold Manila-style espresso shot.', '', 100, 10, NULL, 'manila'),
(2, 'Americano Manila', 105.00, 'coffee', 'Classic black coffee, Manila style.', '', 100, 10, NULL, 'manila'),
(3, 'Cappuccino Manila', 135.00, 'coffee', 'Creamy cappuccino with local flair.', '', 100, 10, NULL, 'manila'),
(4, 'Caffe Latte Manila', 145.00, 'coffee', 'Smooth latte, Manila edition.', '', 100, 10, NULL, 'manila'),
(5, 'Mocha Manila', 155.00, 'coffee', 'Rich chocolate coffee blend.', '', 100, 10, NULL, 'manila'),
(6, 'Flat White Manila', 150.00, 'coffee', 'Strong and creamy flat white.', '', 100, 10, NULL, 'manila'),
(7, 'Croissant Manila', 100.00, 'sides', 'Freshly baked buttery croissant.', '', 100, 10, NULL, 'manila'),
(8, 'Banana Bread Manila', 85.00, 'sides', 'Moist homemade banana bread.', '', 100, 10, NULL, 'manila'),
(9, 'Ham Panini Manila', 145.00, 'sides', 'Grilled ham and cheese panini.', '', 100, 10, NULL, 'manila'),
(10, 'Avocado Toast Manila', 165.00, 'sides', 'Sourdough with smashed avocado.', '', 100, 10, NULL, 'manila'),
(11, 'Tiramisu Manila', 135.00, 'desserts', 'Classic Italian tiramisu.', '', 100, 10, NULL, 'manila'),
(12, 'Cheesecake Manila', 140.00, 'desserts', 'Creamy New York style cheesecake.', '', 100, 10, NULL, 'manila');

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
(6, 'Miguel Fernandez', 'laguna', 4, 'Great place to relax and catch up with friends. The Cold Brew is super smooth and the Avocado Toast is top tier.', 1, '2026-05-01 20:21:17');

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
(1, 'Webmaster', 'ayoscoffeenegosyo_admin', NULL, NULL, NULL, '2026-04-23 09:54:45', 0, 'admin'),
(2, 'Janela Ann', 'JanelaAnnIsles', NULL, NULL, NULL, '2026-04-23 09:54:45', 0, 'user'),
(3, 'KayePetil', '$2y$10$svOGF5YqLZ/5AXeEnojH0evrPSve4HhY714n5iiPBnaiWCsaZHK0i', NULL, NULL, NULL, '2026-04-24 02:46:22', 0, 'user');

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `face_descriptors`
--
ALTER TABLE `face_descriptors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
