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
-- Database: `nocturne_hotel`
--
  
-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_ref` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(120) NOT NULL,
  `guest_email` varchar(120) NOT NULL,
  `suite_id` int(11) NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'number of rooms of this suite booked',
  `guests_count` int(11) NOT NULL DEFAULT 1,
  `tier_applied` enum('regular','vip') NOT NULL DEFAULT 'regular',
  `offer_id` int(11) DEFAULT NULL,
  `nightly_rate` decimal(10,2) NOT NULL,
  `nights` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `user_id`, `guest_name`, `guest_email`, `suite_id`, `time_slot_id`, `checkin_date`, `checkout_date`, `quantity`, `guests_count`, `tier_applied`, `offer_id`, `nightly_rate`, `nights`, `subtotal`, `discount_amount`, `total_price`, `status`, `created_at`) VALUES
(25, 'NB-F2C553', NULL, 'John Arthur Torres', '', 1, 5, '2026-07-28', '2026-07-27', 1, 2, 'regular', NULL, 19425.00, 1, 19425.00, 0.00, 19425.00, 'checked_out', '2026-07-28 02:57:06'),
(26, 'NB-E102A0', NULL, 'John Arthur Torres', '', 1, 4, '2026-07-28', '2026-07-27', 1, 2, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'checked_out', '2026-07-28 03:16:31'),
(27, 'NB-4F3533', NULL, 'John Arthur Torres', '', 1, 2, '2026-07-28', '2026-07-27', 1, 1, 'regular', NULL, 8325.00, 1, 8325.00, 0.00, 8325.00, 'checked_out', '2026-07-28 03:28:59'),
(28, 'NB-3415CD', NULL, 'John Arthur Torres', '', 1, 4, '2026-07-28', '2026-07-27', 1, 1, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'checked_out', '2026-07-28 03:31:59'),
(29, 'NB-A51447', NULL, 'John Arthur Torres', '', 1, 1, '2026-07-28', '2026-07-27', 1, 1, 'regular', NULL, 18500.00, 1, 18500.00, 0.00, 18500.00, 'checked_out', '2026-07-28 03:41:54'),
(30, 'NB-B08B35', NULL, 'John Arthur Torres', '', 1, 4, '2026-07-28', '2026-07-27', 1, 1, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'checked_out', '2026-07-28 03:55:22'),
(31, 'NB-C754DC', NULL, 'John Arthur Torres', '', 1, 1, '2026-07-28', '2026-07-29', 1, 2, 'regular', NULL, 18500.00, 1, 18500.00, 0.00, 18500.00, 'checked_out', '2026-07-28 04:02:00'),
(32, 'NB-27B31E', NULL, 'John Arthur Torres', '', 3, 1, '2026-07-28', '2026-07-29', 1, 2, 'regular', NULL, 68000.00, 1, 68000.00, 0.00, 68000.00, 'pending', '2026-07-28 04:14:28'),
(33, 'NB-0D1D6B', NULL, 'John Arthur Torres', '', 1, 4, '2026-07-31', '2026-09-01', 1, 1, 'regular', 2, 21275.00, 32, 680800.00, 122544.00, 558256.00, 'pending', '2026-07-31 04:49:53'),
(35, 'NB-D231B6', NULL, 'Jannel Maria Torres', 'jannelmariatorres@gmail.com', 1, 4, '2026-08-01', '2026-08-02', 2, 2, 'regular', NULL, 21275.00, 1, 42550.00, 0.00, 42550.00, 'checked_out', '2026-08-01 02:28:36'),
(36, 'NB-70FBF1', NULL, 'Jannel Maria Torres', 'jannelmariatorres@gmail.com', 1, 4, '2026-08-01', '2026-08-02', 1, 2, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'checked_out', '2026-08-01 03:32:35'),
(37, 'NB-A7F1E9', 7, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', 1, 4, '2026-08-01', '2026-08-02', 1, 2, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'checked_out', '2026-08-01 04:10:57'),
(38, 'NB-EF85C9', 7, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', 1, 4, '2026-08-01', '2026-08-02', 1, 2, 'regular', NULL, 21275.00, 1, 21275.00, 0.00, 21275.00, 'pending', '2026-08-01 04:12:15'),
(39, 'NB-FFACDB', NULL, 'Jannel Maria Torres', 'jannelmariatorres@gmail.com', 1, 4, '2026-08-10', '2026-08-14', 1, 2, 'regular', 1, 21275.00, 4, 85100.00, 7659.00, 77441.00, 'checked_out', '2026-08-10 15:03:57'),
(40, 'NB-5651CC', 7, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', 3, 4, '2026-08-23', '2026-08-25', 1, 2, 'regular', NULL, 78200.00, 2, 156400.00, 0.00, 156400.00, 'checked_out', '2026-08-23 05:52:20'),
(45, 'NB-C9D9A5', 7, 'Prince Edward Guevara', 'guevaraprinceedward@gmail.com', 1, 1, '2026-08-27', '2026-08-28', 1, 2, 'regular', NULL, 18500.00, 1, 18500.00, 0.00, 18500.00, 'pending', '2026-08-27 10:13:19');

-- --------------------------------------------------------

--
-- Table structure for table `booking_rooms`
--

CREATE TABLE `booking_rooms` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `guest_name` varchar(120) DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_rooms`
--

INSERT INTO `booking_rooms` (`id`, `booking_id`, `room_id`, `guest_name`, `checked_in_at`, `checked_out_at`) VALUES
(9, 25, 1, 'John Arthur Torres', NULL, '2026-07-28 03:16:10'),
(10, 26, 5, 'John Arthur Torres', NULL, '2026-07-28 03:27:35'),
(11, 27, 8, 'John Arthur Torres', NULL, '2026-07-28 03:29:26'),
(12, 28, 6, 'John Arthur Torres', NULL, '2026-07-28 03:36:28'),
(13, 29, 6, 'John Arthur Torres', NULL, '2026-07-28 03:42:01'),
(14, 30, 4, 'John Arthur Torres', NULL, '2026-07-28 03:55:27'),
(15, 31, 3, 'John Arthur Torres', NULL, '2026-07-28 04:02:06'),
(16, 32, 14, 'John Arthur Torres', NULL, NULL),
(17, 33, 6, 'John Arthur Torres', NULL, NULL),
(18, 35, 3, 'Jannel Maria Torres', NULL, '2026-08-01 03:01:14'),
(19, 35, 8, 'Jannel Maria Torres', NULL, '2026-08-01 03:01:14'),
(20, 36, 8, 'Jannel Maria Torres', NULL, '2026-08-01 03:33:46'),
(21, 37, 8, 'Prince Edward Guevara', NULL, '2026-08-01 04:11:22'),
(22, 38, 5, 'Prince Edward Guevara', NULL, NULL),
(23, 39, 8, 'Jannel Maria Torres', NULL, '2026-08-10 15:05:58'),
(24, 40, 15, 'Prince Edward Guevara', NULL, '2026-08-23 05:54:12'),
(25, 45, 8, 'Prince Edward Guevara', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `disco_tracks`
--

CREATE TABLE `disco_tracks` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `artist` varchar(150) DEFAULT NULL,
  `category` enum('Jazz','Calm','Old Money','Decent') NOT NULL DEFAULT 'Jazz',
  `url` varchar(500) NOT NULL COMMENT 'direct audio file URL or embeddable link',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disco_tracks`
--

INSERT INTO `disco_tracks` (`id`, `title`, `artist`, `category`, `url`, `active`, `sort_order`) VALUES
(1, 'Eyes Without a Face', 'Billy Idol', 'Calm', 'audio/billy-idol-eyes-without-a-face.mp3', 1, 1),
(2, 'Cheri Cheri Lady', 'Modern Talking', 'Old Money', 'audio/modern-talking-cheri-cheri-lady.mp3', 1, 2),
(3, 'Daddy Cool', 'Boney M.', 'Old Money', 'audio/boney-m.-daddy-cool.mp3', 1, 3),
(4, 'Apocalypse', 'Cigarettes After Sex', 'Calm', 'audio/cigarettes-after-sex-apocalypse.mp3', 1, 4),
(5, 'Cry', 'Cigarettes After Sex', 'Calm', 'audio/cigarettes-after-sex-cry.mp3', 1, 5),
(6, 'Raindance (Ft. Terms)', 'Dave', 'Decent', 'audio/dave-ft.-terms-raindance.mp3', 1, 6),
(7, 'Monaco', 'Bad Bunny', 'Old Money', 'audio/bad-bunny-monaco.mp3', 1, 7),
(8, 'Beanie Piano Version', 'Chezile', 'Calm', 'audio/chezile-beanie-piano-version.mp3', 1, 8),
(21, 'After The Last Train', 'Tom Jazz Lounge', 'Jazz', 'audio/after-the-last-train-toms-jazz-lounge.mp3', 1, 9),
(22, 'Letters Never Sent', 'Tom Jazz Lounge', 'Jazz', 'audio/letters-never-sent-toms-jazz-lounge.mp3', 1, 10),
(23, 'One More Glass Before Dawn', 'Tom Jazz Lounge', 'Jazz', 'audio/one-more-glass-before-dawn-toms-jazz-lounge.mp3', 1, 11),
(24, 'Rain Across Bourbon Street', 'Tom Jazz Lounge', 'Jazz', 'audio/rain-across-bourbon-street-toms-jazz-lounge.mp3', 1, 12),
(25, 'The Bartender Knows My Name', 'Tom Jazz Lounge', 'Jazz', 'audio/the-bartender-knows-my-name-toms-jazz-lounge.mp3', 1, 13),
(26, 'Take Five', 'Dave Brubeck', 'Jazz', 'audio/dave-brubeck-take-five.mp3', 1, 14),
(27, 'Smooth Operator', 'Sade', 'Old Money', 'audio/smooth-operator-sade.mp3', 1, 15),
(28, 'Fly Me To The Moon', 'Frank Sinatra', 'Jazz', 'audio/fly-me-to-the-moon-frank-sinatra.mp3', 1, 16),
(29, 'From The Start', 'Laufey', 'Decent', 'audio/from-the-start-laufey.mp3', 1, 17),
(30, 'Lover Girl', 'Laufey', 'Decent', 'audio/lover-girl-laufey.mp3', 1, 18);

-- --------------------------------------------------------

--
-- Table structure for table `membership_tiers`
--

CREATE TABLE `membership_tiers` (
  `id` int(11) NOT NULL,
  `tier` enum('regular','vip') NOT NULL,
  `label` varchar(60) NOT NULL,
  `min_lifetime_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `perks` text DEFAULT NULL COMMENT 'pipe-separated list'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_tiers`
--

INSERT INTO `membership_tiers` (`id`, `tier`, `label`, `min_lifetime_spend`, `perks`) VALUES
(1, 'regular', 'Nocturne Guest', 0.00, 'Standard nightly rates|Access to spa & shop|Standard check-in window'),
(2, 'vip', 'Nocturne Noir (VIP)', 150000.00, '15-20% off room rates|Priority check-in, any hour|Complimentary airport transfer|10% off spa & skincare|Late check-out on request');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `suite_id` int(11) DEFAULT NULL,
  `title` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `audience` enum('all','regular','vip') NOT NULL DEFAULT 'all',
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `min_nights` int(11) NOT NULL DEFAULT 1,
  `image_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `suite_id`, `title`, `description`, `discount_percent`, `audience`, `valid_from`, `valid_to`, `min_nights`, `image_url`, `active`) VALUES
(1, NULL, 'Bay Escape Weekend', 'Book Friday to Sunday and save on any residence.', 12.00, 'all', '2026-07-01', '2026-12-31', 2, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', 1),
(2, NULL, 'Long Stay, Long Rest', 'Stay 5 nights or more and unwind at a lower nightly rate.', 18.00, 'all', '2026-07-01', '2026-12-31', 5, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800', 1),
(3, NULL, 'Noir Season — VIP Exclusive', 'An additional seasonal discount reserved for Nocturne Noir members.', 10.00, 'vip', '2026-07-01', '2026-12-31', 1, 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(50) DEFAULT 'general',
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `branch` varchar(20) DEFAULT 'laguna'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `image`, `stock`, `created_at`, `reorder_level`, `branch`) VALUES
(1, 'Aromatherapy Massage (60 mins)', 'Full-body relaxation massage using signature Nocturne oils.', 1800.00, 'spa', NULL, 15, '2026-07-02 18:05:41', 10, 'laguna'),
(2, 'Manila Bay Sunset Cruise Add-on', 'Private evening cruise pass for hotel guests.', 2500.00, 'experience', NULL, 10, '2026-07-02 18:05:41', 10, 'laguna'),
(3, 'Nocturne Signature Robe', 'Plush hotel robe, available for purchase.', 1200.00, 'souvenir', NULL, 25, '2026-07-02 18:05:41', 10, 'laguna'),
(4, 'Poolside Cabana Rental (half-day)', 'Reserved shaded cabana with towel service.', 900.00, 'amenity', NULL, 8, '2026-07-02 18:05:41', 10, 'laguna');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `ref_code` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(120) NOT NULL,
  `guest_email` varchar(150) NOT NULL,
  `guest_phone` varchar(30) DEFAULT NULL,
  `room_type` varchar(80) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests_count` int(11) DEFAULT 1,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `suite_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `status` enum('available','maintenance','out_of_service') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `suite_id`, `room_number`, `status`) VALUES
(1, 1, 'VER-101', 'available'),
(2, 1, 'VER-102', 'available'),
(3, 1, 'VER-103', 'available'),
(4, 1, 'VER-104', 'available'),
(5, 1, 'VER-105', 'available'),
(6, 1, 'VER-106', 'available'),
(7, 1, 'VER-107', 'available'),
(8, 1, 'VER-108', 'available'),
(9, 2, 'ATL-201', 'available'),
(10, 2, 'ATL-202', 'available'),
(11, 2, 'ATL-203', 'available'),
(12, 2, 'ATL-204', 'available'),
(13, 2, 'ATL-205', 'available'),
(14, 3, 'PH-301', 'available'),
(15, 3, 'PH-302', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `suite_id` int(11) DEFAULT NULL,
  `category` enum('spa','product','dining') NOT NULL,
  `type` enum('spa','skincare','billiards','casino','minibar','snacks','breakfast','lunch','dinner','drink','champagne') NOT NULL DEFAULT 'spa',
  `gender` enum('unisex','men','women') NOT NULL DEFAULT 'unisex',
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_min` int(11) DEFAULT NULL COMMENT 'spa treatments only',
  `stock` int(11) DEFAULT NULL COMMENT 'products only, NULL = unlimited/spa',
  `base_price` decimal(10,2) NOT NULL,
  `vip_price` decimal(10,2) NOT NULL,
  `unit_type` enum('per_session','per_hour','per_item') NOT NULL DEFAULT 'per_session',
  `image_url` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `suite_id`, `category`, `type`, `gender`, `name`, `description`, `duration_min`, `stock`, `base_price`, `vip_price`, `unit_type`, `image_url`, `active`) VALUES
(101, 1, 'spa', 'spa', 'unisex', 'Veranda Deep Tissue Recovery', 'A firmer 75-minute massage targeting tension in the back, shoulders, and legs.', 75, NULL, 3800.00, 3230.00, 'per_session', 'https://images.unsplash.com/photo-1519823551278-64ac92734fb1?w=800', 1),
(102, 1, 'spa', 'spa', 'unisex', 'Veranda Aromatherapy Escape', 'A slow, oil-based ritual with a choice of lavender, ylang-ylang, or citrus blends.', 60, NULL, 3400.00, 2890.00, 'per_session', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 1),
(103, 1, 'spa', 'spa', 'unisex', 'Veranda Hot Stone Ritual', 'Heated basalt stones ease deep muscle tension over a 90-minute session.', 90, NULL, 4600.00, 3910.00, 'per_session', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800', 1),
(104, 2, 'spa', 'spa', 'unisex', 'Atelier Deep Tissue Recovery', 'A firmer 75-minute massage targeting tension in the back, shoulders, and legs.', 75, NULL, 4200.00, 3570.00, 'per_session', 'https://images.unsplash.com/photo-1519823551278-64ac92734fb1?w=800', 1),
(105, 2, 'spa', 'spa', 'unisex', 'Atelier Express Facial', 'A 45-minute deep-cleanse facial, with optional add-on procedures below.', 45, NULL, 2600.00, 2210.00, 'per_session', 'https://images.unsplash.com/photo-1616394158624-1c6f8b3c9a34?w=800', 1),
(106, 2, 'spa', 'spa', 'unisex', 'Atelier Aromatherapy Escape', 'A slow, oil-based ritual with a choice of lavender, ylang-ylang, or citrus blends.', 60, NULL, 3600.00, 3060.00, 'per_session', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 1),
(107, 3, 'spa', 'spa', 'unisex', 'Penthouse Deep Tissue Recovery', 'A firmer 75-minute massage targeting tension in the back, shoulders, and legs.', 75, NULL, 5400.00, 4590.00, 'per_session', 'https://images.unsplash.com/photo-1519823551278-64ac92734fb1?w=800', 1),
(108, 3, 'spa', 'spa', 'unisex', 'Penthouse Express Facial', 'A 45-minute deep-cleanse facial, with optional add-on procedures below.', 45, NULL, 3200.00, 2720.00, 'per_session', 'https://images.unsplash.com/photo-1616394158624-1c6f8b3c9a34?w=800', 1),
(109, 3, 'spa', 'spa', 'unisex', 'Penthouse Aromatherapy Escape', 'A slow, oil-based ritual with a choice of lavender, ylang-ylang, or citrus blends.', 60, NULL, 4400.00, 3740.00, 'per_session', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800', 1),
(150, 1, 'product', 'skincare', 'women', 'Veranda Hydra Glow Serum', 'Hyaluronic-acid serum for a dewy, hydrated finish.', NULL, 30, 1650.00, 1403.00, 'per_item', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800', 1),
(151, 1, 'product', 'skincare', 'women', 'Veranda Vitamin C Brightening Cream', 'Brightening day cream with vitamin C and niacinamide.', NULL, 28, 1900.00, 1615.00, 'per_item', 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=800', 1),
(152, 1, 'product', 'skincare', 'women', 'Veranda Silk Lip & Cheek Tint', 'A dual-use tint in a soft rose shade.', NULL, 40, 950.00, 808.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(153, 1, 'product', 'skincare', 'men', 'Veranda Charcoal Face Wash', 'Deep-cleansing charcoal wash for oily, travel-tired skin.', NULL, 35, 950.00, 808.00, 'per_item', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800', 1),
(154, 1, 'product', 'skincare', 'men', 'Veranda After-Shave Balm', 'Alcohol-free balm that calms razor burn and tightens pores.', NULL, 30, 1100.00, 935.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(155, 2, 'product', 'skincare', 'women', 'Atelier Retinol Night Cream', 'A gentle nightly retinol cream for fine lines.', NULL, 24, 2600.00, 2210.00, 'per_item', 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=800', 1),
(156, 2, 'product', 'skincare', 'women', 'Atelier Rosewater Facial Mist', 'A hydrating mist for mid-day refreshing.', NULL, 45, 850.00, 723.00, 'per_item', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800', 1),
(157, 2, 'product', 'skincare', 'women', 'Atelier Silk Hair Serum', 'Lightweight anti-frizz serum with argan oil.', NULL, 30, 1450.00, 1233.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(158, 2, 'product', 'skincare', 'men', 'Atelier Beard Oil', 'Softening beard oil with cedar and sandalwood notes.', NULL, 32, 1200.00, 1020.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(159, 2, 'product', 'skincare', 'men', 'Atelier Cooling Eye Roller', 'Metal roller with caffeine gel for tired eyes.', NULL, 20, 1350.00, 1148.00, 'per_item', 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=800', 1),
(160, 3, 'product', 'skincare', 'women', 'Penthouse 24K Gold Eye Cream', 'Gold-leaf eye cream for firmness and radiance.', NULL, 12, 4200.00, 3570.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(161, 3, 'product', 'skincare', 'women', 'Penthouse Diamond Peptide Serum', 'A concentrated peptide serum in a signature amber bottle.', NULL, 10, 6800.00, 5780.00, 'per_item', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800', 1),
(162, 3, 'product', 'skincare', 'men', 'Penthouse Executive Face Serum', 'Anti-fatigue serum formulated for red-eye arrivals.', NULL, 12, 3800.00, 3230.00, 'per_item', 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=800', 1),
(163, 3, 'product', 'skincare', 'men', 'Penthouse Straight-Razor Shave Set', 'A full wet-shave kit with brush, bowl, and blade.', NULL, 8, 5200.00, 4420.00, 'per_item', 'https://images.unsplash.com/photo-1571875257727-256c39da42af?w=800', 1),
(200, 1, 'product', 'billiards', 'unisex', 'Veranda Standard Table', 'Regulation 8-ball table, hourly rental.', NULL, NULL, 650.00, 552.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(201, 1, 'product', 'billiards', 'unisex', 'Veranda Snooker Table', 'Full-size snooker table with premium cloth.', NULL, NULL, 900.00, 765.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(202, 2, 'product', 'billiards', 'unisex', 'Atelier Standard Table', 'Regulation 8-ball table, hourly rental.', NULL, NULL, 800.00, 680.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(203, 2, 'product', 'billiards', 'unisex', 'Atelier VIP Felt Table', 'Tournament-grade felt with a private lounge seating area.', NULL, NULL, 1200.00, 1020.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(204, 3, 'product', 'billiards', 'unisex', 'Penthouse Grand Table', 'Estate-level table with an attendant on hand.', NULL, NULL, 1200.00, 1020.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(205, 3, 'product', 'billiards', 'unisex', 'Penthouse Private Lounge Table', 'A fully enclosed billiards lounge with its own bar.', NULL, NULL, 1800.00, 1530.00, 'per_hour', 'https://cdn.shopify.com/s/files/1/0263/0936/1717/t/3/assets/JEWEL.png?v=1592577017', 1),
(250, 1, 'product', 'casino', 'unisex', 'Veranda Blackjack Table', 'Reserved blackjack table with a house dealer, per session.', NULL, NULL, 1500.00, 1275.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(251, 1, 'product', 'casino', 'unisex', 'Veranda Poker Table', 'Texas Hold\'em table for up to 6 guests, per session.', NULL, NULL, 1800.00, 1530.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(252, 2, 'product', 'casino', 'unisex', 'Atelier Baccarat Table', 'Private baccarat table with a house dealer, per session.', NULL, NULL, 2200.00, 1870.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(253, 2, 'product', 'casino', 'unisex', 'Atelier Roulette Wheel', 'Full roulette setup for up to 8 guests, per session.', NULL, NULL, 2600.00, 2210.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(254, 3, 'product', 'casino', 'unisex', 'Penthouse Private Casino Suite', 'Full private casino room with a dedicated dealer, per session.', NULL, NULL, 4500.00, 3825.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(255, 3, 'product', 'casino', 'unisex', 'Penthouse Slot Machine Lounge', 'A private lounge of premium slot machines, per session.', NULL, NULL, 3000.00, 2550.00, 'per_session', 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=800', 1),
(300, 1, 'product', 'snacks', 'unisex', 'Club Sandwich', 'Triple-decker with chicken, bacon, and egg.', NULL, 40, 480.00, 408.00, 'per_item', 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=800', 1),
(301, 1, 'product', 'snacks', 'unisex', 'Tuna Melt Sandwich', 'Toasted sourdough with tuna and melted cheddar.', NULL, 35, 420.00, 357.00, 'per_item', 'https://images.unsplash.com/photo-1553909489-cd47e0ef937f?w=800', 1),
(302, 1, 'product', 'snacks', 'unisex', 'Iced Calamansi Juice', 'Fresh-pressed calamansi over ice.', NULL, 60, 180.00, 153.00, 'per_item', 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=800', 1),
(303, 1, 'product', 'snacks', 'unisex', 'Bottled Iced Tea', 'House-brewed black tea, lightly sweetened.', NULL, 60, 150.00, 128.00, 'per_item', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800', 1),
(304, 2, 'product', 'snacks', 'unisex', 'Grilled Cheese & Ham Sandwich', 'Buttery grilled sourdough with ham and gruyère.', NULL, 35, 460.00, 391.00, 'per_item', 'https://images.unsplash.com/photo-1528736235302-52922df5c122?w=800', 1),
(305, 2, 'product', 'snacks', 'unisex', 'Club Sandwich', 'Triple-decker with chicken, bacon, and egg.', NULL, 40, 520.00, 442.00, 'per_item', 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=800', 1),
(306, 2, 'product', 'snacks', 'unisex', 'Fresh Buko Juice', 'Young coconut, served chilled with the meat.', NULL, 40, 220.00, 187.00, 'per_item', 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=800', 1),
(307, 2, 'product', 'snacks', 'unisex', 'Sparkling Water', 'Chilled sparkling mineral water.', NULL, 60, 150.00, 128.00, 'per_item', 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=800', 1),
(308, 3, 'product', 'snacks', 'unisex', 'Wagyu Sliders (3pc)', 'Mini wagyu beef sliders with caramelized onion.', NULL, 20, 950.00, 808.00, 'per_item', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800', 1),
(309, 3, 'product', 'snacks', 'unisex', 'Smoked Salmon Sandwich', 'Open-faced rye with smoked salmon and dill cream.', NULL, 25, 680.00, 578.00, 'per_item', 'https://images.unsplash.com/photo-1553909489-cd47e0ef937f?w=800', 1),
(310, 3, 'product', 'snacks', 'unisex', 'Fresh Mango Shake', 'Ripe Philippine mango, blended fresh.', NULL, 30, 280.00, 238.00, 'per_item', 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=800', 1),
(311, 3, 'product', 'snacks', 'unisex', 'Estate Cold Brew Coffee', 'House cold brew, served over ice.', NULL, 40, 250.00, 213.00, 'per_item', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=800', 1),
(350, 1, 'product', 'minibar', 'unisex', 'House Red Wine', 'A medium-bodied red, delivered chilled or at room temp.', NULL, 20, 1200.00, 1020.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(351, 1, 'product', 'minibar', 'unisex', 'House White Wine', 'A crisp white, delivered well-chilled.', NULL, 20, 1150.00, 978.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(352, 2, 'product', 'minibar', 'unisex', 'Reserve Merlot', 'A fuller-bodied merlot from the estate cellar.', NULL, 15, 1800.00, 1530.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(353, 2, 'product', 'minibar', 'unisex', 'Chilled Champagne (Standard)', 'Classic brut champagne, delivered on ice.', NULL, 12, 2800.00, 2380.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(354, 3, 'product', 'minibar', 'unisex', 'Estate Reserve Champagne', 'A vintage reserve champagne from the estate cellar.', NULL, 8, 4800.00, 4080.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(355, 3, 'product', 'minibar', 'unisex', 'Champagne Magnum', 'Magnum-format champagne, estate cellar selection.', NULL, 6, 6500.00, 5525.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(400, NULL, 'dining', 'breakfast', 'unisex', 'Continental Breakfast', 'Pastries, fruit, eggs, and coffee — complimentary with your stay.', NULL, NULL, 0.00, 0.00, 'per_session', 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=800', 1),
(401, NULL, 'dining', 'lunch', 'unisex', 'Filipino Lunch Set', 'A rotating chef\'s lunch set served to your room.', NULL, NULL, 850.00, 723.00, 'per_session', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800', 1),
(402, NULL, 'dining', 'dinner', 'unisex', 'Chef\'s Dinner Tasting', 'A multi-course tasting menu, delivered in courses.', NULL, NULL, 2200.00, 1870.00, 'per_session', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 1),
(403, NULL, 'dining', 'drink', 'unisex', 'Signature Cocktail', 'A rotating house cocktail, served on ice.', NULL, NULL, 450.00, 383.00, 'per_item', 'https://images.unsplash.com/photo-1551538827-9c037cb4f32a?w=800', 1),
(404, NULL, 'dining', 'champagne', 'unisex', 'House Champagne (Bottle)', 'A crisp brut champagne, delivered chilled.', NULL, NULL, 2800.00, 2380.00, 'per_item', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=800', 1),
(405, NULL, 'dining', 'breakfast', 'unisex', 'Continental Breakfast', 'Pastries, seasonal fruit, eggs, and coffee — complimentary with your stay.', NULL, NULL, 0.00, 0.00, 'per_session', 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=800', 1),
(406, NULL, 'dining', 'breakfast', 'unisex', 'Filipino Silog Trio', 'Tapa, longganisa, and daing na bangus, each with garlic rice and a fried egg.', NULL, NULL, 650.00, 553.00, 'per_session', 'https://images.unsplash.com/photo-1615887023544-9f26e7f9d95d?w=800', 1),
(407, NULL, 'dining', 'breakfast', 'unisex', 'Eggs Benedict Nocturne', 'Poached eggs and smoked ham over English muffin, finished with hollandaise.', NULL, NULL, 780.00, 663.00, 'per_session', 'https://images.unsplash.com/photo-1608039790896-42d5b9d4b4b0?w=800', 1),
(408, NULL, 'dining', 'breakfast', 'unisex', 'Belgian Waffles & Berries', 'Warm waffles, mixed berries, and whipped mascarpone.', NULL, NULL, 620.00, 527.00, 'per_session', 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=800', 1),
(409, NULL, 'dining', 'breakfast', 'unisex', 'Smoked Salmon Bagel', 'Toasted bagel, cream cheese, capers, and Norwegian smoked salmon.', NULL, NULL, 850.00, 723.00, 'per_session', 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?w=800', 1),
(410, NULL, 'dining', 'breakfast', 'unisex', 'Tapsilog Deluxe', 'Marinated beef tapa, garlic fried rice, and a sunny-side-up egg.', NULL, NULL, 700.00, 595.00, 'per_session', 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=800', 1),
(411, NULL, 'dining', 'breakfast', 'unisex', 'Congee with Century Egg', 'Slow-cooked rice porridge with century egg, scallions, and crullers.', NULL, NULL, 580.00, 493.00, 'per_session', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 1),
(412, NULL, 'dining', 'breakfast', 'unisex', 'Avocado Toast & Poached Eggs', 'Sourdough, smashed avocado, chili flakes, and two poached eggs.', NULL, NULL, 690.00, 587.00, 'per_session', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=800', 1),
(413, NULL, 'dining', 'breakfast', 'unisex', 'Fresh Fruit Platter', 'A chilled selection of seasonal Philippine fruit.', NULL, NULL, 450.00, 383.00, 'per_session', 'https://images.unsplash.com/photo-1568909344668-6f14a07b56a0?w=800', 1),
(414, NULL, 'dining', 'breakfast', 'unisex', 'Pancake Stack & Maple Syrup', 'Buttermilk pancakes, butter, and warm maple syrup.', NULL, NULL, 600.00, 510.00, 'per_session', 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800', 1),
(415, NULL, 'dining', 'breakfast', 'unisex', 'Bacon & Cheese Omelette', 'Three-egg omelette with crisp bacon and melted cheddar.', NULL, NULL, 650.00, 553.00, 'per_session', 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=800', 1),
(416, NULL, 'dining', 'breakfast', 'unisex', 'Champorado with Tuyo', 'Sweet chocolate rice porridge paired with crisp dried fish.', NULL, NULL, 550.00, 468.00, 'per_session', 'https://images.unsplash.com/photo-1626074353765-517a681e40be?w=800', 1),
(417, NULL, 'dining', 'lunch', 'unisex', 'Filipino Lunch Set (Adobo)', 'Chicken and pork adobo, garlic rice, and atchara.', NULL, NULL, 850.00, 723.00, 'per_session', 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?w=800', 1),
(418, NULL, 'dining', 'lunch', 'unisex', 'Sinigang na Baboy', 'Tamarind-soured pork stew with vegetables and steamed rice.', NULL, NULL, 780.00, 663.00, 'per_session', 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800', 1),
(419, NULL, 'dining', 'lunch', 'unisex', 'Grilled Salmon with Rice', 'Herb-grilled salmon fillet, jasmine rice, and steamed greens.', NULL, NULL, 1200.00, 1020.00, 'per_session', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=800', 1),
(420, NULL, 'dining', 'lunch', 'unisex', 'Beef Caldereta', 'Braised beef in tomato sauce with liver spread, peppers, and olives.', NULL, NULL, 950.00, 808.00, 'per_session', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800', 1),
(421, NULL, 'dining', 'lunch', 'unisex', 'Chicken Inasal Meal', 'Charcoal-grilled chicken thigh, java rice, and papaya atchara.', NULL, NULL, 720.00, 612.00, 'per_session', 'https://images.unsplash.com/photo-1598515213692-5f252f47b9a5?w=800', 1),
(422, NULL, 'dining', 'lunch', 'unisex', 'Kare-Kare Set', 'Oxtail and vegetable stew in peanut sauce, served with bagoong.', NULL, NULL, 980.00, 833.00, 'per_session', 'https://images.unsplash.com/photo-1604909052743-94e838986d24?w=800', 1),
(423, NULL, 'dining', 'lunch', 'unisex', 'Club Sandwich & Fries', 'Triple-decker club sandwich with a side of crispy fries.', NULL, NULL, 650.00, 553.00, 'per_session', 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=800', 1),
(424, NULL, 'dining', 'lunch', 'unisex', 'Caesar Salad with Chicken', 'Grilled chicken breast over romaine, parmesan, and croutons.', NULL, NULL, 700.00, 595.00, 'per_session', 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=800', 1),
(425, NULL, 'dining', 'lunch', 'unisex', 'Seafood Paella (Serves 2)', 'Saffron rice with shrimp, mussels, squid, and chorizo.', NULL, NULL, 1600.00, 1360.00, 'per_session', 'https://images.unsplash.com/photo-1534080564583-6be75777b70a?w=800', 1),
(426, NULL, 'dining', 'lunch', 'unisex', 'Vegetable Pad Thai', 'Stir-fried rice noodles with tofu, egg, and crushed peanuts.', NULL, NULL, 680.00, 578.00, 'per_session', 'https://images.unsplash.com/photo-1559314809-0d155014e29e?w=800', 1),
(427, NULL, 'dining', 'lunch', 'unisex', 'Wagyu Beef Burger', 'Wagyu patty, aged cheddar, caramelized onion, brioche bun, fries.', NULL, NULL, 950.00, 808.00, 'per_session', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800', 1),
(428, NULL, 'dining', 'lunch', 'unisex', 'Laksa Noodle Soup', 'Coconut curry broth, rice noodles, prawns, and soft-boiled egg.', NULL, NULL, 750.00, 638.00, 'per_session', 'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=800', 1),
(429, NULL, 'dining', 'dinner', 'unisex', 'Chef\'s Dinner Tasting Menu', 'A multi-course tasting menu curated by the estate chef, served in courses.', NULL, NULL, 2200.00, 1870.00, 'per_session', 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800', 1),
(430, NULL, 'dining', 'dinner', 'unisex', 'Grilled US Ribeye Steak', '12oz ribeye, roasted potatoes, and peppercorn sauce.', NULL, NULL, 2800.00, 2380.00, 'per_session', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=800', 1),
(431, NULL, 'dining', 'dinner', 'unisex', 'Whole Roasted Chicken Inasal', 'A whole charcoal-roasted chicken, java rice, and dipping sauces.', NULL, NULL, 1800.00, 1530.00, 'per_session', 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=800', 1),
(432, NULL, 'dining', 'dinner', 'unisex', 'Lobster Thermidor', 'Half lobster in a creamy mustard-cheese sauce, gratinéed.', NULL, NULL, 3500.00, 2975.00, 'per_session', 'https://images.unsplash.com/photo-1559737558-2f5a35f4523b?w=800', 1),
(433, NULL, 'dining', 'dinner', 'unisex', 'Beef Wellington', 'Prime beef fillet wrapped in mushroom duxelles and puff pastry.', NULL, NULL, 3200.00, 2720.00, 'per_session', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800', 1),
(434, NULL, 'dining', 'dinner', 'unisex', 'Crispy Pata Feast', 'Deep-fried pork leg, garlic rice, and soy-vinegar dip, for sharing.', NULL, NULL, 2000.00, 1700.00, 'per_session', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800', 1),
(435, NULL, 'dining', 'dinner', 'unisex', 'Seafood Boil Platter', 'Crab, shrimp, mussels, corn, and sausage in Cajun butter sauce.', NULL, NULL, 2600.00, 2210.00, 'per_session', 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=800', 1),
(436, NULL, 'dining', 'dinner', 'unisex', 'Duck Confit with Red Wine Jus', 'Slow-cooked duck leg, gratin potatoes, and a reduced red wine sauce.', NULL, NULL, 2400.00, 2040.00, 'per_session', 'https://images.unsplash.com/photo-1432139555190-58524dae6a55?w=800', 1),
(437, NULL, 'dining', 'dinner', 'unisex', 'Lechon Belly Roll', 'Crackling-skin roasted pork belly roll with liver sauce.', NULL, NULL, 2200.00, 1870.00, 'per_session', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800', 1),
(438, NULL, 'dining', 'dinner', 'unisex', 'Truffle Mushroom Risotto', 'Creamy arborio rice, wild mushrooms, and shaved black truffle.', NULL, NULL, 1700.00, 1445.00, 'per_session', 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?w=800', 1),
(439, NULL, 'dining', 'dinner', 'unisex', 'Grilled Tiger Prawns', 'Butter-garlic grilled prawns with saffron rice.', NULL, NULL, 2100.00, 1785.00, 'per_session', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=800', 1),
(440, NULL, 'dining', 'dinner', 'unisex', 'Filipino Kamayan Feast (Serves 2)', 'A traditional boodle-fight spread of grilled meats, seafood, and rice.', NULL, NULL, 3000.00, 2550.00, 'per_session', 'https://images.unsplash.com/photo-1625938145312-c30e0a4b1c66?w=800', 1),
(441, NULL, 'dining', 'drink', 'unisex', 'Signature Nocturne Cocktail', 'The house signature cocktail, shaken to order.', NULL, NULL, 450.00, 383.00, 'per_item', 'https://images.unsplash.com/photo-1551538827-9c037cb4f32a?w=800', 1),
(442, NULL, 'dining', 'drink', 'unisex', 'Calamansi Mojito', 'White rum, calamansi, mint, and soda.', NULL, NULL, 380.00, 323.00, 'per_item', 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=800', 1),
(443, NULL, 'dining', 'drink', 'unisex', 'Old Fashioned', 'Bourbon, bitters, and orange peel, stirred over ice.', NULL, NULL, 500.00, 425.00, 'per_item', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=800', 1),
(444, NULL, 'dining', 'drink', 'unisex', 'Espresso Martini', 'Vodka, coffee liqueur, and fresh espresso.', NULL, NULL, 480.00, 408.00, 'per_item', 'https://images.unsplash.com/photo-1541976076758-347942db1970?w=800', 1),
(445, NULL, 'dining', 'drink', 'unisex', 'Mango Daiquiri', 'White rum blended with fresh Philippine mango.', NULL, NULL, 420.00, 357.00, 'per_item', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800', 1),
(446, NULL, 'dining', 'drink', 'unisex', 'Whiskey Sour', 'Bourbon, lemon, and a touch of egg white.', NULL, NULL, 460.00, 391.00, 'per_item', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?w=800', 1),
(447, NULL, 'dining', 'drink', 'unisex', 'Fresh Coconut Shake', 'Young coconut blended with ice and a touch of milk.', NULL, NULL, 280.00, 238.00, 'per_item', 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=800', 1),
(448, NULL, 'dining', 'drink', 'unisex', 'Iced Matcha Latte', 'Ceremonial-grade matcha over milk and ice.', NULL, NULL, 320.00, 272.00, 'per_item', 'https://images.unsplash.com/photo-1536013455962-8f78bfba8f79?w=800', 1),
(449, NULL, 'dining', 'drink', 'unisex', 'Sparkling Elderflower', 'Non-alcoholic elderflower cordial with soda, served chilled.', NULL, NULL, 300.00, 255.00, 'per_item', 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=800', 1),
(450, NULL, 'dining', 'drink', 'unisex', 'Craft Beer (Bottle)', 'A rotating local craft lager or ale.', NULL, NULL, 250.00, 213.00, 'per_item', 'https://images.unsplash.com/photo-1608270586620-248524c67de9?w=800', 1),
(451, NULL, 'dining', 'drink', 'unisex', 'House Sangria (Glass)', 'Red wine steeped with citrus and seasonal fruit.', NULL, NULL, 400.00, 340.00, 'per_item', 'https://images.unsplash.com/photo-1560508601-3fdbc7e6ad04?w=800', 1),
(452, NULL, 'dining', 'drink', 'unisex', 'Virgin Berry Mojito', 'A non-alcoholic mojito with muddled mixed berries.', NULL, NULL, 260.00, 221.00, 'per_item', 'https://images.unsplash.com/photo-1497534446932-c925b458314e?w=800', 1),
(453, NULL, 'dining', 'champagne', 'unisex', 'House Champagne (Bottle)', 'Classic brut champagne, delivered chilled on ice.', NULL, NULL, 2800.00, 2380.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(454, NULL, 'dining', 'champagne', 'unisex', 'Brut Reserve', 'A well-balanced brut reserve from an established maison.', NULL, NULL, 4200.00, 3570.00, 'per_item', 'https://images.unsplash.com/photo-1592483648228-58180cd0956a?w=800', 1),
(455, NULL, 'dining', 'champagne', 'unisex', 'Rosé Champagne', 'A delicate rosé with notes of red berries.', NULL, NULL, 4500.00, 3825.00, 'per_item', 'https://images.unsplash.com/photo-1567696911980-2eed69a46042?w=800', 1),
(456, NULL, 'dining', 'champagne', 'unisex', 'Estate Reserve Champagne', 'A vintage reserve champagne from the estate cellar.', NULL, NULL, 4800.00, 4080.00, 'per_item', 'https://images.unsplash.com/photo-1518099099425-4dc74ec7e0c1?w=800', 1),
(457, NULL, 'dining', 'champagne', 'unisex', 'Vintage Blanc de Blancs', '100% Chardonnay champagne with a crisp, mineral finish.', NULL, NULL, 5200.00, 4420.00, 'per_item', 'https://images.unsplash.com/photo-1547595628-c61a29f496f0?w=800', 1),
(458, NULL, 'dining', 'champagne', 'unisex', 'Champagne Magnum (1.5L)', 'Magnum-format champagne, ideal for sharing.', NULL, NULL, 6800.00, 5780.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1),
(459, NULL, 'dining', 'champagne', 'unisex', 'Prestige Cuvée', 'A top-tier prestige cuvée for special occasions.', NULL, NULL, 8500.00, 7225.00, 'per_item', 'https://images.unsplash.com/photo-1592483648228-58180cd0956a?w=800', 1),
(460, NULL, 'dining', 'champagne', 'unisex', 'Sweet Demi-Sec Champagne', 'A lightly sweet champagne, pairs well with dessert.', NULL, NULL, 3600.00, 3060.00, 'per_item', 'https://images.unsplash.com/photo-1567696911980-2eed69a46042?w=800', 1),
(461, NULL, 'dining', 'champagne', 'unisex', 'Sparkling Prosecco', 'A light, fruit-forward Italian sparkling wine.', NULL, NULL, 2200.00, 1870.00, 'per_item', 'https://images.unsplash.com/photo-1547595628-c61a29f496f0?w=800', 1),
(462, NULL, 'dining', 'champagne', 'unisex', 'Crémant Rosé', 'A French sparkling rosé, made in the traditional method.', NULL, NULL, 3000.00, 2550.00, 'per_item', 'https://images.unsplash.com/photo-1518099099425-4dc74ec7e0c1?w=800', 1),
(463, NULL, 'dining', 'champagne', 'unisex', 'Grower\'s Champagne', 'A small-production grower champagne with distinct character.', NULL, NULL, 3800.00, 3230.00, 'per_item', 'https://images.unsplash.com/photo-1592483648228-58180cd0956a?w=800', 1),
(464, NULL, 'dining', 'champagne', 'unisex', 'Vintage Millésime', 'A single-vintage champagne from an exceptional harvest year.', NULL, NULL, 7200.00, 6120.00, 'per_item', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800', 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_addons`
--

CREATE TABLE `service_addons` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vip_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_addons`
--

INSERT INTO `service_addons` (`id`, `service_id`, `name`, `description`, `price`, `vip_price`, `active`) VALUES
(1, 105, 'LED Light Therapy', 'Red/blue light pass to calm inflammation and support collagen.', 800.00, 680.00, 1),
(2, 105, 'Collagen Boost Mask', 'A hydrating mask sheet layered after cleansing.', 650.00, 553.00, 1),
(3, 105, 'Eye Contour Treatment', 'Cooling gel patches for tired, puffy under-eyes.', 450.00, 383.00, 1),
(4, 105, 'Deep Pore Extraction', 'Manual extraction for congested pores, done by our aesthetician.', 350.00, 298.00, 1),
(5, 108, 'LED Light Therapy', 'Red/blue light pass to calm inflammation and support collagen.', 800.00, 680.00, 1),
(6, 108, 'Collagen Boost Mask', 'A hydrating mask sheet layered after cleansing.', 650.00, 553.00, 1),
(7, 108, 'Eye Contour Treatment', 'Cooling gel patches for tired, puffy under-eyes.', 450.00, 383.00, 1),
(8, 108, 'Deep Pore Extraction', 'Manual extraction for congested pores, done by our aesthetician.', 350.00, 298.00, 1),
(9, 350, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 150.00, 128.00, 1),
(10, 350, 'Extra Wine Glasses (2pc)', 'Two additional glasses for sharing.', 200.00, 170.00, 1),
(11, 351, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 150.00, 128.00, 1),
(12, 351, 'Extra Wine Glasses (2pc)', 'Two additional glasses for sharing.', 200.00, 170.00, 1),
(13, 352, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 150.00, 128.00, 1),
(14, 353, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 180.00, 153.00, 1),
(15, 353, 'Fruit Garnish Plate', 'Sliced strawberries and citrus to pair with champagne.', 350.00, 298.00, 1),
(16, 354, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 180.00, 153.00, 1),
(17, 354, 'Fruit Garnish Plate', 'Sliced strawberries and citrus to pair with champagne.', 350.00, 298.00, 1),
(18, 355, 'Extra Ice Bucket', 'An additional bucket of ice delivered with your order.', 180.00, 153.00, 1),
(19, 355, 'Fruit Garnish Plate', 'Sliced strawberries and citrus to pair with champagne.', 350.00, 298.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_orders`
--

CREATE TABLE `service_orders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `guest_name` varchar(150) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_order_addons`
--

CREATE TABLE `service_order_addons` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suites`
--

CREATE TABLE `suites` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `size_sqm` int(11) NOT NULL,
  `view_desc` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `vip_price` decimal(10,2) NOT NULL,
  `total_rooms` int(11) NOT NULL DEFAULT 5,
  `max_guests` int(11) NOT NULL DEFAULT 2,
  `features` text DEFAULT NULL COMMENT 'pipe-separated list',
  `cover_image` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suites`
--

INSERT INTO `suites` (`id`, `slug`, `name`, `size_sqm`, `view_desc`, `description`, `base_price`, `vip_price`, `total_rooms`, `max_guests`, `features`, `cover_image`, `created_at`) VALUES
(1, 'veranda', 'The Veranda Suite', 52, 'Manila Bay, partial', 'A quiet corner room with a private balcony, dressed in linen and brass, facing the first light over the bay.', 18500.00, 15725.00, 8, 2, 'King bed, Egyptian linen|Soaking tub|Private balcony|24-hr butler call', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1200', '2026-07-03 01:55:28'),
(2, 'atelier', 'The Atelier Suite', 84, 'Manila Bay, full', 'A residence-scale suite with a working study and a sunken lounge, for guests who stay long enough to unpack properly.', 32000.00, 27200.00, 5, 3, 'Separate lounge & study|Walk-in dressing room|In-suite dining table|Dedicated butler', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200', '2026-07-03 01:55:28'),
(3, 'penthouse', 'The Nocturne Penthouse', 160, '360° skyline & bay', 'The top floor, entire. A private terrace pool, a wine room, and a hall that has hosted three state dinners.', 68000.00, 57800.00, 2, 6, 'Private rooftop pool|Personal wine cellar|Grand piano lounge|Round-the-clock estate staff', 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200', '2026-07-03 01:55:28');

-- --------------------------------------------------------

--
-- Table structure for table `suite_images`
--

CREATE TABLE `suite_images` (
  `id` int(11) NOT NULL,
  `suite_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suite_images`
--

INSERT INTO `suite_images` (`id`, `suite_id`, `image_url`, `sort_order`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1200', 1),
(2, 1, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=1200&sat=-20', 2),
(3, 1, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200', 3),
(4, 2, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200', 1),
(5, 2, 'https://images.unsplash.com/photo-1591088398332-8a7791972843?w=1200', 2),
(6, 2, 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=1200', 3),
(7, 3, 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=1200', 1),
(8, 3, 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200', 2),
(9, 3, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200', 3);

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
(1, 'Andrea Santos', 'manila', 5, 'Napakaganda ng view sa Manila Bay, ang linis ng kwarto at napakabait ng staff!', 1, '2026-07-02 18:05:41'),
(2, 'Miguel Reyes', 'laguna', 4, 'Relaxing yung Laguna branch, sulit yung spa package namin.', 1, '2026-07-02 18:05:41'),
(3, 'Kristine Uy', 'manila', 5, 'Sobrang worth it, babalik kami dito next year!', 1, '2026-07-02 18:05:41');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `label` varchar(60) NOT NULL,
  `checkin_time` time NOT NULL,
  `checkout_time` time NOT NULL,
  `duration_hours` int(11) NOT NULL,
  `price_modifier` decimal(4,2) NOT NULL DEFAULT 1.00,
  `description` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `label`, `checkin_time`, `checkout_time`, `duration_hours`, `price_modifier`, `description`) VALUES
(1, 'Overnight Stay (Standard)', '16:00:00', '12:00:00', 20, 1.00, 'Classic full-night stay, 4pm check-in to 12nn check-out.'),
(2, 'Day-Use Stay (6 hrs)', '10:00:00', '16:00:00', 6, 0.45, 'Short stay for layovers or day rest — six hours in the room.'),
(3, 'Half-Day Stay (12 hrs)', '08:00:00', '20:00:00', 12, 0.65, 'Half a day, ideal for spa-and-suite packages.'),
(4, 'Extended Stay (24 hrs)', '14:00:00', '14:00:00', 24, 1.15, 'A full 24 hours in-room, check-in to the same hour next day.'),
(5, 'Late Night Arrival', '22:00:00', '12:00:00', 14, 1.05, 'For red-eye arrivals — 10pm check-in, standard 12nn check-out.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `tier` enum('regular','vip') NOT NULL DEFAULT 'regular',
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `tier`, `loyalty_points`, `created_at`) VALUES
(7, 'princeedward.guevara', 'guevaraprinceedward@gmail.com', '$2y$10$3H1W8QopG.PtizqKE4hQiuOE21Ls86QA2ZiWbw69Cl/9eUEju.qPC', 'Prince Edward Guevara', '09910654627', 'regular', 0, '2026-08-01 04:10:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `time_slot_id` (`time_slot_id`),
  ADD KEY `offer_id` (`offer_id`),
  ADD KEY `idx_bookings_dates` (`checkin_date`,`checkout_date`),
  ADD KEY `idx_bookings_suite` (`suite_id`);

--
-- Indexes for table `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_room` (`booking_id`,`room_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `disco_tracks`
--
ALTER TABLE `disco_tracks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_disco_category_active` (`category`,`active`,`sort_order`);

--
-- Indexes for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tier` (`tier`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suite_id` (`suite_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suite_id` (`suite_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_addons`
--
ALTER TABLE `service_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `service_orders`
--
ALTER TABLE `service_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `idx_service_orders_booking` (`booking_id`);

--
-- Indexes for table `service_order_addons`
--
ALTER TABLE `service_order_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `suites`
--
ALTER TABLE `suites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `suite_images`
--
ALTER TABLE `suite_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suite_id` (`suite_id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `booking_rooms`
--
ALTER TABLE `booking_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `disco_tracks`
--
ALTER TABLE `disco_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `membership_tiers`
--
ALTER TABLE `membership_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=465;

--
-- AUTO_INCREMENT for table `service_addons`
--
ALTER TABLE `service_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `service_orders`
--
ALTER TABLE `service_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_order_addons`
--
ALTER TABLE `service_order_addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suites`
--
ALTER TABLE `suites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suite_images`
--
ALTER TABLE `suite_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`suite_id`) REFERENCES `suites` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD CONSTRAINT `booking_rooms_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_rooms_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `offers_ibfk_1` FOREIGN KEY (`suite_id`) REFERENCES `suites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`suite_id`) REFERENCES `suites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_addons`
--
ALTER TABLE `service_addons`
  ADD CONSTRAINT `service_addons_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_orders`
--
ALTER TABLE `service_orders`
  ADD CONSTRAINT `service_orders_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_order_addons`
--
ALTER TABLE `service_order_addons`
  ADD CONSTRAINT `soa_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `service_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soa_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `service_addons` (`id`);

--
-- Constraints for table `suite_images`
--
ALTER TABLE `suite_images`
  ADD CONSTRAINT `suite_images_ibfk_1` FOREIGN KEY (`suite_id`) REFERENCES `suites` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
