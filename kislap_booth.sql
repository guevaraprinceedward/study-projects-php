-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 12:58 PM
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
-- Database: `kislap_booth`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','staff') NOT NULL DEFAULT 'staff',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `username`, `email`, `password_hash`, `role`, `last_login`, `created_at`) VALUES
(1, 'Booth Owner', 'admin', 'owner@kislapbooth.com', '$2y$10$610YYvyqrm4opVZEf4yqCu4vGFD60N/aMhYuVUbSot92gH2NhrWgO', 'super_admin', NULL, '2026-08-27 21:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_designs`
--

CREATE TABLE `carousel_designs` (
  `design_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `bg_color` varchar(20) NOT NULL DEFAULT '#FFFDF8',
  `accent_color` varchar(20) NOT NULL DEFAULT '#6C4AB6',
  `sticker_set` varchar(50) DEFAULT 'hearts',
  `extra_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carousel_designs`
--

INSERT INTO `carousel_designs` (`design_id`, `name`, `slug`, `description`, `bg_color`, `accent_color`, `sticker_set`, `extra_fee`, `display_order`, `is_active`) VALUES
(1, 'Classic Cream', 'classic', 'Timeless white borders, minimal and clean.', '#FFFDF8', '#2B2140', 'none', 0.00, 1, 1),
(2, 'Sakura Dream', 'sakura', 'Soft pink petals and a dreamy blush frame.', '#FFE1EC', '#FF5C8A', 'sakura', 30.00, 2, 1),
(3, 'Pastel Bear', 'pastel-bear', 'Lavender frame with cute little bear stamps.', '#EDE4FF', '#6C4AB6', 'bear', 30.00, 3, 1),
(4, 'Star Night', 'star-night', 'Deep plum backdrop scattered with tiny stars.', '#2B2140', '#FFD36E', 'stars', 30.00, 4, 1),
(5, 'Minty Fresh', 'minty', 'Cool mint frame with sparkle accents.', '#D8F6EF', '#2E9E86', 'sparkle', 30.00, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `email`, `phone_number`, `password_hash`, `created_at`) VALUES
(1, 'Prince', 'Guevara', 'guevaraprinceedward@gmail.com', '09910654627', '$2y$10$C5meh12rZJ.Q6DbTRw3aY.kejpVlSIl7yZ2oLjWUUERmg/HKLiv/S', '2026-08-30 07:11:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `photo_type_id` int(11) NOT NULL,
  `carousel_design_id` int(11) DEFAULT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_email` varchar(120) DEFAULT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `num_shots` tinyint(2) NOT NULL DEFAULT 3,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `design_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','gcash','credit_card') DEFAULT NULL,
  `payment_reference` varchar(80) DEFAULT NULL,
  `payment_status` enum('pending','verifying','paid','failed') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','processing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `final_image_path` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `customer_id`, `photo_type_id`, `carousel_design_id`, `customer_name`, `customer_email`, `customer_phone`, `num_shots`, `subtotal`, `design_fee`, `total_amount`, `payment_method`, `payment_reference`, `payment_status`, `order_status`, `final_image_path`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'KB260828-F38C7', NULL, 5, 1, 'Jannel Torres', 'jannelmariatorres@gmail.com', '0991 065 4627', 6, 650.00, 0.00, 650.00, 'cash', NULL, 'verifying', 'processing', 'uploads/orders/KB260828-F38C7/final-strip.png', NULL, '2026-08-28 20:59:23', '2026-08-28 20:59:26'),
(2, 'KB260828-51430', NULL, 3, 3, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', '0991 065 4627', 4, 280.00, 30.00, 310.00, NULL, NULL, 'pending', 'pending', 'uploads/orders/KB260828-51430/final-strip.png', NULL, '2026-08-28 21:04:29', '2026-08-28 21:04:29'),
(3, 'KB260828-9D6FE', NULL, 3, 3, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', '0991 065 4627', 4, 280.00, 30.00, 310.00, 'cash', NULL, 'verifying', 'processing', 'uploads/orders/KB260828-9D6FE/final-strip.png', NULL, '2026-08-28 21:05:37', '2026-08-28 21:05:48'),
(4, 'KB260829-609E0', NULL, 3, 4, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', '0991 065 4627', 4, 280.00, 30.00, 310.00, 'cash', NULL, 'verifying', 'processing', 'uploads/orders/KB260829-609E0/final-strip.png', NULL, '2026-08-29 10:58:46', '2026-08-29 10:58:54'),
(5, 'KB260830-B72E6', 1, 3, 2, 'Prince', 'guevaraprinceedward@gmail.com', '0991 065 4627', 4, 280.00, 30.00, 310.00, 'credit_card', NULL, 'verifying', 'processing', 'uploads/orders/KB260830-B72E6/final-strip.png', NULL, '2026-08-30 07:12:42', '2026-08-30 07:12:56');

-- --------------------------------------------------------

--
-- Table structure for table `order_photos`
--

CREATE TABLE `order_photos` (
  `photo_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `sort_order` tinyint(2) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_photos`
--

INSERT INTO `order_photos` (`photo_id`, `order_id`, `photo_path`, `sort_order`, `created_at`) VALUES
(1, 1, 'uploads/orders/KB260828-F38C7/shot-1.jpg', 0, '2026-08-28 20:59:23'),
(2, 1, 'uploads/orders/KB260828-F38C7/shot-2.jpg', 1, '2026-08-28 20:59:23'),
(3, 1, 'uploads/orders/KB260828-F38C7/shot-3.jpg', 2, '2026-08-28 20:59:23'),
(4, 1, 'uploads/orders/KB260828-F38C7/shot-4.jpg', 3, '2026-08-28 20:59:23'),
(5, 1, 'uploads/orders/KB260828-F38C7/shot-5.jpg', 4, '2026-08-28 20:59:23'),
(6, 1, 'uploads/orders/KB260828-F38C7/shot-6.jpg', 5, '2026-08-28 20:59:23'),
(7, 2, 'uploads/orders/KB260828-51430/shot-1.jpg', 0, '2026-08-28 21:04:29'),
(8, 2, 'uploads/orders/KB260828-51430/shot-2.jpg', 1, '2026-08-28 21:04:29'),
(9, 2, 'uploads/orders/KB260828-51430/shot-3.jpg', 2, '2026-08-28 21:04:29'),
(10, 2, 'uploads/orders/KB260828-51430/shot-4.jpg', 3, '2026-08-28 21:04:29'),
(11, 3, 'uploads/orders/KB260828-9D6FE/shot-1.jpg', 0, '2026-08-28 21:05:37'),
(12, 3, 'uploads/orders/KB260828-9D6FE/shot-2.jpg', 1, '2026-08-28 21:05:37'),
(13, 3, 'uploads/orders/KB260828-9D6FE/shot-3.jpg', 2, '2026-08-28 21:05:37'),
(14, 3, 'uploads/orders/KB260828-9D6FE/shot-4.jpg', 3, '2026-08-28 21:05:37'),
(15, 4, 'uploads/orders/KB260829-609E0/shot-1.jpg', 0, '2026-08-29 10:58:46'),
(16, 4, 'uploads/orders/KB260829-609E0/shot-2.jpg', 1, '2026-08-29 10:58:46'),
(17, 4, 'uploads/orders/KB260829-609E0/shot-3.jpg', 2, '2026-08-29 10:58:46'),
(18, 4, 'uploads/orders/KB260829-609E0/shot-4.jpg', 3, '2026-08-29 10:58:46'),
(19, 5, 'uploads/orders/KB260830-B72E6/shot-1.jpg', 0, '2026-08-30 07:12:42'),
(20, 5, 'uploads/orders/KB260830-B72E6/shot-2.jpg', 1, '2026-08-30 07:12:42'),
(21, 5, 'uploads/orders/KB260830-B72E6/shot-3.jpg', 2, '2026-08-30 07:12:42'),
(22, 5, 'uploads/orders/KB260830-B72E6/shot-4.jpg', 3, '2026-08-30 07:12:42');

-- --------------------------------------------------------

--
-- Table structure for table `photo_types`
--

CREATE TABLE `photo_types` (
  `type_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `shots_required` tinyint(2) NOT NULL DEFAULT 3,
  `max_people` tinyint(2) NOT NULL DEFAULT 1,
  `icon_emoji` varchar(10) DEFAULT '?',
  `accent_color` varchar(20) DEFAULT '#6C4AB6',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `photo_types`
--

INSERT INTO `photo_types` (`type_id`, `name`, `slug`, `description`, `base_price`, `shots_required`, `max_people`, `icon_emoji`, `accent_color`, `display_order`, `is_active`) VALUES
(1, 'Solo Session', 'solo', 'Just you, your best angles, and a whole lot of main-character energy.', 150.00, 3, 1, '🌟', '#FF7AA8', 1, 1),
(2, '2-in-1 Session', 'duo', 'Bestie photo op — perfect for besties, siblings, or your #1 partner in crime.', 250.00, 3, 2, '🎀', '#6C4AB6', 2, 1),
(3, 'Couple Session', 'couple', 'For the loved-up duos. Soft lighting, cute poses, forever memories.', 280.00, 4, 2, '💗', '#FF5C8A', 3, 1),
(4, 'Group Session', 'group', 'Squad of 3–5. Big laughs, bigger memories.', 400.00, 4, 5, '✨', '#5FB8A5', 4, 1),
(5, 'Barkada Session', 'barkada', 'The whole gang, 6–10 people. Maximum chaos, maximum cuteness.', 650.00, 6, 10, '🎉', '#FFB84D', 5, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `carousel_designs`
--
ALTER TABLE `carousel_designs`
  ADD PRIMARY KEY (`design_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_type` (`photo_type_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_payment` (`payment_status`),
  ADD KEY `orders_ibfk_3` (`carousel_design_id`);

--
-- Indexes for table `order_photos`
--
ALTER TABLE `order_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `idx_order_photos_order` (`order_id`);

--
-- Indexes for table `photo_types`
--
ALTER TABLE `photo_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carousel_designs`
--
ALTER TABLE `carousel_designs`
  MODIFY `design_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_photos`
--
ALTER TABLE `order_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `photo_types`
--
ALTER TABLE `photo_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`photo_type_id`) REFERENCES `photo_types` (`type_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`carousel_design_id`) REFERENCES `carousel_designs` (`design_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_photos`
--
ALTER TABLE `order_photos`
  ADD CONSTRAINT `order_photos_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
