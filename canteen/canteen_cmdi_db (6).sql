-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2026 at 10:15 PM
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
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `target` varchar(120) DEFAULT NULL,
  `details` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 04:50:17'),
(2, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-13 04:51:03'),
(3, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 04:51:46'),
(4, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-13 04:56:53'),
(5, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 04:57:28'),
(6, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-13 05:07:55'),
(7, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 05:10:13'),
(8, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 15:35:13'),
(9, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-13 15:35:57'),
(10, 1, 'login', NULL, 'Logged in', '::1', '2026-10-13 16:12:58'),
(11, 1, 'reservation_confirmed', 'COPS-PPV7ME', NULL, '::1', '2026-10-13 16:14:35'),
(12, 1, 'reservation_confirmed', 'COPS-6H5URH', NULL, '::1', '2026-10-13 16:14:38'),
(13, 1, 'reservation_confirmed', 'COPS-877VA8', NULL, '::1', '2026-10-13 16:14:39'),
(14, 1, 'reservation_preparing', 'COPS-PPV7ME', NULL, '::1', '2026-10-13 16:14:40'),
(15, 1, 'reservation_preparing', 'COPS-6H5URH', NULL, '::1', '2026-10-13 16:14:42'),
(16, 1, 'reservation_ready', 'COPS-6H5URH', NULL, '::1', '2026-10-13 16:14:47'),
(17, 1, 'reservation_ready', 'COPS-PPV7ME', NULL, '::1', '2026-10-13 16:14:48'),
(18, 1, 'reservation_preparing', 'COPS-877VA8', NULL, '::1', '2026-10-13 16:14:49'),
(19, 1, 'reservation_completed', 'COPS-PPV7ME', NULL, '::1', '2026-10-13 16:14:50'),
(20, 1, 'reservation_completed', 'COPS-6H5URH', NULL, '::1', '2026-10-13 16:14:51'),
(21, 1, 'reservation_ready', 'COPS-877VA8', NULL, '::1', '2026-10-13 16:14:51'),
(22, 1, 'reservation_completed', 'COPS-877VA8', NULL, '::1', '2026-10-13 16:14:53'),
(23, 1, 'login', NULL, 'Logged in', '::1', '2026-10-15 00:59:50'),
(24, 1, 'login', NULL, 'Logged in', '::1', '2026-10-15 13:16:32'),
(25, 1, 'reservation_confirmed', 'COPS-NQNNLM', NULL, '::1', '2026-10-15 13:16:40'),
(26, 1, 'reservation_preparing', 'COPS-NQNNLM', NULL, '::1', '2026-10-15 13:16:41'),
(27, 1, 'reservation_ready', 'COPS-NQNNLM', NULL, '::1', '2026-10-15 13:16:42'),
(28, 1, 'reservation_completed', 'COPS-NQNNLM', NULL, '::1', '2026-10-15 13:16:45'),
(29, 1, 'login', NULL, 'Logged in', '::1', '2026-10-16 03:51:31'),
(30, 1, 'reservation_confirmed', 'COPS-5HSTES', NULL, '::1', '2026-10-16 03:51:33'),
(31, 1, 'reservation_preparing', 'COPS-5HSTES', NULL, '::1', '2026-10-16 03:51:34'),
(32, 1, 'reservation_ready', 'COPS-5HSTES', NULL, '::1', '2026-10-16 03:51:35'),
(33, 1, 'reservation_completed', 'COPS-5HSTES', NULL, '::1', '2026-10-16 03:51:35'),
(34, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-16 03:53:27'),
(35, 1, 'login', NULL, 'Logged in', '::1', '2026-10-17 06:11:56'),
(36, 1, 'reservation_confirmed', 'COPS-WRYX8P', NULL, '::1', '2026-10-17 06:12:00'),
(37, 1, 'reservation_preparing', 'COPS-WRYX8P', NULL, '::1', '2026-10-17 06:12:22'),
(38, 1, 'reservation_ready', 'COPS-WRYX8P', NULL, '::1', '2026-10-17 06:12:27'),
(39, 1, 'reservation_completed', 'COPS-WRYX8P', NULL, '::1', '2026-10-17 06:12:32'),
(40, 1, 'stock_restock', 'Banana Cue', '+50 (50 → 100) Delivery / purchase', '::1', '2026-10-17 06:12:52'),
(41, 1, 'stock_restock', 'Banana Cue', '+50 (100 → 150) Delivery / purchase', '::1', '2026-10-17 06:12:57'),
(42, 1, 'stock_adjust', 'Banana Cue', '-50 (150 → 100) Spoiled / expired', '::1', '2026-10-17 06:13:13'),
(43, 1, 'stock_restock', 'Bottled Water', '+2 (98 → 100) Delivery / purchase', '::1', '2026-10-17 06:13:20'),
(44, 1, 'stock_restock', 'Buko Juice', '+100 (50 → 150) Delivery / purchase', '::1', '2026-10-17 06:13:27'),
(45, 1, 'stock_adjust', 'Buko Juice', '-50 (150 → 100) Spoiled / expired', '::1', '2026-10-17 06:13:32'),
(46, 1, 'stock_restock', 'Burger', '+1 (39 → 40) Delivery / purchase', '::1', '2026-10-17 06:13:36'),
(47, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-17 06:27:10'),
(48, 1, 'login', NULL, 'Logged in', '::1', '2026-10-17 06:28:42'),
(49, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-17 06:41:18'),
(50, 1, 'login', NULL, 'Logged in', '::1', '2026-10-18 05:09:02'),
(51, 1, 'reservation_confirmed', 'COPS-P3ERVX', NULL, '::1', '2026-10-18 05:09:11'),
(52, 1, 'reservation_preparing', 'COPS-P3ERVX', NULL, '::1', '2026-10-18 05:09:12'),
(53, 1, 'reservation_ready', 'COPS-P3ERVX', NULL, '::1', '2026-10-18 05:09:13'),
(54, 1, 'reservation_completed', 'COPS-P3ERVX', NULL, '::1', '2026-10-18 05:09:21'),
(55, 1, 'reservation_confirmed', 'COPS-DQAPSF', NULL, '::1', '2026-10-18 05:10:47'),
(56, 1, 'reservation_preparing', 'COPS-DQAPSF', NULL, '::1', '2026-10-18 05:10:48'),
(57, 1, 'reservation_ready', 'COPS-DQAPSF', NULL, '::1', '2026-10-18 05:10:50'),
(58, 1, 'reservation_completed', 'COPS-DQAPSF', NULL, '::1', '2026-10-18 05:10:50'),
(59, 1, 'login', NULL, 'Logged in', '::1', '2026-10-20 10:51:57'),
(60, 1, 'logout', NULL, 'Logged out', '::1', '2026-10-20 10:54:07'),
(61, 1, 'login', NULL, 'Logged in', '::1', '2026-10-22 04:22:47'),
(62, 1, 'login', NULL, 'Logged in', '::1', '2026-10-26 04:23:39'),
(63, 1, 'reservation_confirmed', 'COPS-GM5BMV', NULL, '::1', '2026-10-26 04:23:45'),
(64, 1, 'reservation_preparing', 'COPS-GM5BMV', NULL, '::1', '2026-10-26 04:23:47'),
(65, 1, 'reservation_ready', 'COPS-GM5BMV', NULL, '::1', '2026-10-26 04:23:47'),
(66, 1, 'reservation_completed', 'COPS-GM5BMV', NULL, '::1', '2026-10-26 04:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'password_hash() output',
  `full_name` varchar(120) NOT NULL,
  `role` enum('staff','admin') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `staff_id`, `username`, `password`, `full_name`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'STF-0001', 'admin_prince', '$2y$10$RwUa0t/PgWGQaXLUUmY7We8de4KdH5MCuoxQdvdk1uCNaBn3Iya8i', 'Admin Prince', 'admin', 1, '2026-10-26 04:23:39', '2026-10-13 04:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `auth_events`
--

CREATE TABLE `auth_events` (
  `id` bigint(20) NOT NULL,
  `scope` enum('student','staff') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `identifier` varchar(100) NOT NULL DEFAULT '',
  `event` enum('login','logout','failed','blocked') NOT NULL,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auth_events`
--

INSERT INTO `auth_events` (`id`, `scope`, `user_id`, `identifier`, `event`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'staff', NULL, 'prince', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:49:57'),
(2, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:50:17'),
(3, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:51:03'),
(4, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:51:46'),
(5, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:56:53'),
(6, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 04:57:28'),
(7, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:07:55'),
(8, 'student', 1, '2026-0002', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:08:37'),
(9, 'student', NULL, 'jannel', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:09:02'),
(10, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:09:22'),
(11, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:10:01'),
(12, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 05:10:13'),
(13, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 15:35:13'),
(14, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 15:35:57'),
(15, 'staff', NULL, 'janneltorres', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 15:36:06'),
(16, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 15:36:14'),
(17, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 16:12:48'),
(18, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 16:12:58'),
(19, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-10-13 16:14:05'),
(20, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 00:59:50'),
(21, 'student', 1, 'admin', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 13:15:39'),
(22, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 13:16:32'),
(23, 'student', 1, '2026-0002', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-15 18:05:32'),
(24, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 03:50:54'),
(25, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 03:51:31'),
(26, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 03:53:27'),
(27, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:03:30'),
(28, 'student', 2, 'janneltorres', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:05:40'),
(29, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:05:44'),
(30, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:07:48'),
(31, 'student', NULL, 'jann', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:08:48'),
(32, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:08:55'),
(33, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 04:09:06'),
(34, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-16 05:24:43'),
(35, 'student', 2, 'janneltorres', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:10:39'),
(36, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:11:56'),
(37, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:27:11'),
(38, 'student', 2, '2026-0000', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:27:15'),
(39, 'student', 1, 'admin', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:27:51'),
(40, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:28:42'),
(41, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-17 06:41:18'),
(42, 'student', 4, 'johnpaul.pedron', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 05:07:11'),
(43, 'student', 4, 'johnpaul.pedron', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 05:07:32'),
(44, 'student', 4, 'johnpaul.pedron', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 05:07:36'),
(45, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-18 05:09:02'),
(46, 'student', 1, 'admin', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 10:50:15'),
(47, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 10:51:57'),
(48, 'staff', 1, 'admin_prince', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 10:54:07'),
(49, 'student', 1, '2026-0002', 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-20 10:54:40'),
(50, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 04:22:47'),
(51, 'student', 1, 'admin', 'failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-26 04:23:06'),
(52, 'student', 1, 'admin', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-26 04:23:10'),
(53, 'staff', 1, 'admin_prince', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-26 04:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `kind` enum('restock','adjust') NOT NULL,
  `change_qty` int(11) NOT NULL,
  `qty_before` int(11) NOT NULL,
  `qty_after` int(11) NOT NULL,
  `reason` varchar(60) DEFAULT NULL,
  `note` varchar(160) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `product_id`, `product_name`, `kind`, `change_qty`, `qty_before`, `qty_after`, `reason`, `note`, `admin_id`, `admin_name`, `created_at`) VALUES
(1, 10, 'Banana Cue', 'restock', 50, 50, 100, 'Delivery / purchase', NULL, 1, 'Admin Prince', '2026-10-17 06:12:52'),
(2, 10, 'Banana Cue', 'restock', 50, 100, 150, 'Delivery / purchase', NULL, 1, 'Admin Prince', '2026-10-17 06:12:57'),
(3, 10, 'Banana Cue', 'adjust', -50, 150, 100, 'Spoiled / expired', NULL, 1, 'Admin Prince', '2026-10-17 06:13:13'),
(4, 17, 'Bottled Water', 'restock', 2, 98, 100, 'Delivery / purchase', NULL, 1, 'Admin Prince', '2026-10-17 06:13:20'),
(5, 19, 'Buko Juice', 'restock', 100, 50, 150, 'Delivery / purchase', NULL, 1, 'Admin Prince', '2026-10-17 06:13:27'),
(6, 19, 'Buko Juice', 'adjust', -50, 150, 100, 'Spoiled / expired', NULL, 1, 'Admin Prince', '2026-10-17 06:13:32'),
(7, 13, 'Burger', 'restock', 1, 39, 40, 'Delivery / purchase', NULL, 1, 'Admin Prince', '2026-10-17 06:13:36');

-- --------------------------------------------------------

--
-- Table structure for table `personal_testimonials`
--

CREATE TABLE `personal_testimonials` (
  `id` int(11) NOT NULL,
  `client_name` varchar(120) NOT NULL,
  `client_location` varchar(120) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL COMMENT 'optional photo path/URL; empty = initial letter',
  `rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT '1 to 5 stars',
  `testimonial` text NOT NULL,
  `venture` varchar(120) DEFAULT NULL COMMENT 'e.g. Busy Bree''s Manju',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = shown on about-us.php',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pickup_slots`
--

CREATE TABLE `pickup_slots` (
  `id` int(11) NOT NULL,
  `label` varchar(60) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 20,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pickup_slots`
--

INSERT INTO `pickup_slots` (`id`, `label`, `start_time`, `end_time`, `capacity`, `is_active`, `sort_order`) VALUES
(1, 'Morning break', '09:30:00', '10:00:00', 20, 1, 1),
(2, 'Lunch', '12:00:00', '13:00:00', 40, 1, 2),
(3, 'Afternoon break', '15:00:00', '15:30:00', 20, 1, 3);

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
(1, 'RCE-001', 'Tapsilog', 'rice_meals', 'Beef tapa, garlic rice, and a sunny-side-up egg.', 65.00, 37, 10, 'assets/img/Tapsilog.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-18 05:08:42'),
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
(15, 'SNK-008', 'Egg Sandwich', 'snacks', 'Creamy egg spread on soft white bread.', 25.00, 38, 10, 'assets/img/Fresh.Egg.Sandwich.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-18 05:08:42'),
(16, 'SNK-009', 'Siopao Asado', 'snacks', 'Fluffy steamed bun filled with sweet asado pork.', 30.00, 29, 10, 'assets/img/Fresh.Siopao.Asado.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:11:23'),
(17, 'DRK-001', 'Bottled Water', 'drinks', 'Purified drinking water, 500 ml.', 15.00, 95, 20, 'assets/img/Bottled.Water.500ml.Clean.jpeg', 1, 1, '2026-10-12 02:12:14', '2026-10-18 05:10:42'),
(18, 'DRK-002', 'Iced Tea', 'drinks', 'Chilled house-blend iced tea.', 20.00, 80, 20, 'assets/img/Iced.Tea.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 07:07:49'),
(19, 'DRK-003', 'Buko Juice', 'drinks', 'Fresh coconut water with tender buko strips.', 25.00, 99, 15, 'assets/img/Fresh.Buko.Juice.Clean.jpeg', 1, 0, '2026-10-12 02:12:14', '2026-10-17 06:28:26'),
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
(105, 'SNK-026', 'Cheese Corn Dog', 'snacks', 'A sausage coated in thick golden batter with a melted cheese filling or topping, served on a stick.', 40.00, 40, 10, 'assets/img/Cheese.Corn.Dog.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57'),
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
(117, 'SNK-034', 'Chicken Popcorn', 'snacks', 'Bite-sized chicken pieces coated in seasoned breading and fried until golden and crunchy.', 45.00, 40, 10, 'assets/img/Chicken.Popcorn.Clean.jpeg', 1, 0, '2026-10-20 04:38:32', '2026-10-20 05:08:57');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `reference` varchar(16) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_name` varchar(120) NOT NULL,
  `student_ref` varchar(40) DEFAULT NULL,
  `pickup_slot_id` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `note` varchar(160) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','preparing','ready','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `status_reason` varchar(160) DEFAULT NULL,
  `handled_by_id` int(11) DEFAULT NULL,
  `handled_by_name` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `reference`, `user_id`, `student_name`, `student_ref`, `pickup_slot_id`, `pickup_date`, `note`, `total_amount`, `status`, `status_reason`, `handled_by_id`, `handled_by_name`, `created_at`, `updated_at`) VALUES
(1, 'COPS-JQAWHZ', 1, 'Prince Edward Guevara', '2026-0002', 1, '2026-10-13', NULL, 60.00, 'cancelled', 'Cancelled by the student', NULL, NULL, '2026-10-12 19:57:18', '2026-10-12 20:52:54'),
(2, 'COPS-UW9MTU', 1, 'Prince Edward Guevara', '2026-0002', 1, '2026-10-13', NULL, 60.00, 'cancelled', 'Cancelled by the student', NULL, NULL, '2026-10-12 20:51:30', '2026-10-12 20:52:52'),
(3, 'COPS-PPV7ME', 1, 'Prince Edward Guevara', '2026-0002', 1, '2026-10-13', NULL, 60.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-12 20:53:11', '2026-10-13 08:14:50'),
(4, 'COPS-6H5URH', 2, 'Jannel Torres', '2026-0000', 1, '2026-10-13', NULL, 60.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-12 21:09:42', '2026-10-13 08:14:51'),
(5, 'COPS-877VA8', 2, 'Jannel Torres', '2026-0000', 1, '2026-10-13', NULL, 60.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-13 08:14:15', '2026-10-13 08:14:53'),
(6, 'COPS-NQNNLM', 1, 'Prince Edward Guevara', '2026-0002', 1, '2026-10-15', NULL, 60.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-15 05:15:57', '2026-10-15 05:16:45'),
(7, 'COPS-5HSTES', 2, 'Jannel Torres', '2026-0000', 1, '2026-10-16', NULL, 60.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-15 19:50:57', '2026-10-15 19:51:35'),
(8, 'COPS-WRYX8P', 2, 'Jannel Torres', '2026-0000', 3, '2026-10-17', NULL, 95.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-16 22:11:23', '2026-10-16 22:12:32'),
(9, 'COPS-CC68KM', 1, 'Prince Edward Guevara', '2026-0002', 3, '2026-10-17', NULL, 115.00, 'pending', NULL, NULL, NULL, '2026-10-16 22:28:26', '2026-10-16 22:28:26'),
(10, 'COPS-MP6M4C', 1, 'Prince Edward Guevara', '2026-0002', 1, '2026-10-17', NULL, 15.00, 'pending', NULL, NULL, NULL, '2026-10-16 22:41:09', '2026-10-16 22:41:09'),
(11, 'COPS-P3ERVX', 4, 'John Paul Pedron', '2026-0010', 2, '2026-10-18', NULL, 265.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-17 21:08:42', '2026-10-17 21:09:21'),
(12, 'COPS-DQAPSF', 4, 'John Paul Pedron', '2026-0010', 3, '2026-10-18', NULL, 215.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-17 21:10:42', '2026-10-17 21:10:50'),
(13, 'COPS-GM5BMV', 1, 'Prince Edward Guevara', '2026-0002', 2, '2026-10-26', NULL, 55.00, 'completed', NULL, 1, 'Admin Prince', '2026-10-25 20:23:20', '2026-10-25 20:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_items`
--

CREATE TABLE `reservation_items` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(120) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_items`
--

INSERT INTO `reservation_items` (`id`, `reservation_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `line_subtotal`) VALUES
(1, 1, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(2, 2, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(3, 3, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(4, 4, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(5, 5, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(6, 6, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(7, 7, 2, 'Chicken Adobo with Rice', 1, 60.00, 60.00),
(8, 8, 13, 'Burger', 1, 35.00, 35.00),
(9, 8, 16, 'Siopao Asado', 1, 30.00, 30.00),
(10, 8, 17, 'Bottled Water', 2, 15.00, 30.00),
(11, 9, 1, 'Tapsilog', 1, 65.00, 65.00),
(12, 9, 15, 'Egg Sandwich', 1, 25.00, 25.00),
(13, 9, 19, 'Buko Juice', 1, 25.00, 25.00),
(14, 10, 17, 'Bottled Water', 1, 15.00, 15.00),
(15, 11, 1, 'Tapsilog', 2, 65.00, 130.00),
(16, 11, 8, 'Siomai (4 pcs)', 2, 25.00, 50.00),
(17, 11, 13, 'Burger', 1, 35.00, 35.00),
(18, 11, 15, 'Egg Sandwich', 1, 25.00, 25.00),
(19, 11, 20, 'Orange Juice', 1, 25.00, 25.00),
(20, 12, 3, 'Fried Chicken with Rice', 1, 65.00, 65.00),
(21, 12, 13, 'Burger', 1, 35.00, 35.00),
(22, 12, 14, 'French Fries', 1, 30.00, 30.00),
(23, 12, 17, 'Bottled Water', 4, 15.00, 60.00),
(24, 12, 20, 'Orange Juice', 1, 25.00, 25.00),
(25, 13, 89, 'Tuna with Rice', 1, 55.00, 55.00);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_status_log`
--

CREATE TABLE `reservation_status_log` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `from_status` varchar(20) DEFAULT NULL,
  `to_status` varchar(20) NOT NULL,
  `actor_type` varchar(10) NOT NULL,
  `actor_id` varchar(20) DEFAULT NULL,
  `actor_name` varchar(120) NOT NULL,
  `reason` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_status_log`
--

INSERT INTO `reservation_status_log` (`id`, `reservation_id`, `from_status`, `to_status`, `actor_type`, `actor_id`, `actor_name`, `reason`, `created_at`) VALUES
(1, 1, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-12 19:57:18'),
(2, 2, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-12 20:51:30'),
(3, 2, 'pending', 'cancelled', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-12 20:52:52'),
(4, 1, 'pending', 'cancelled', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-12 20:52:54'),
(5, 3, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-12 20:53:11'),
(6, 4, NULL, 'pending', 'student', '2', 'Jannel Torres', NULL, '2026-10-12 21:09:42'),
(7, 5, NULL, 'pending', 'student', '2', 'Jannel Torres', NULL, '2026-10-13 08:14:15'),
(8, 3, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:35'),
(9, 4, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:38'),
(10, 5, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:39'),
(11, 3, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:40'),
(12, 4, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:42'),
(13, 4, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:47'),
(14, 3, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:48'),
(15, 5, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:49'),
(16, 3, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:50'),
(17, 4, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:51'),
(18, 5, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:51'),
(19, 5, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-13 08:14:53'),
(20, 6, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-15 05:15:57'),
(21, 6, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 05:16:40'),
(22, 6, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 05:16:41'),
(23, 6, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 05:16:42'),
(24, 6, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 05:16:45'),
(25, 7, NULL, 'pending', 'student', '2', 'Jannel Torres', NULL, '2026-10-15 19:50:57'),
(26, 7, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 19:51:33'),
(27, 7, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 19:51:34'),
(28, 7, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 19:51:35'),
(29, 7, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-15 19:51:35'),
(30, 8, NULL, 'pending', 'student', '2', 'Jannel Torres', NULL, '2026-10-16 22:11:23'),
(31, 8, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-16 22:12:00'),
(32, 8, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-16 22:12:22'),
(33, 8, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-16 22:12:27'),
(34, 8, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-16 22:12:32'),
(35, 9, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-16 22:28:26'),
(36, 10, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-16 22:41:09'),
(37, 11, NULL, 'pending', 'student', '4', 'John Paul Pedron', NULL, '2026-10-17 21:08:42'),
(38, 11, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:09:11'),
(39, 11, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:09:12'),
(40, 11, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:09:13'),
(41, 11, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:09:21'),
(42, 12, NULL, 'pending', 'student', '4', 'John Paul Pedron', NULL, '2026-10-17 21:10:42'),
(43, 12, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:10:47'),
(44, 12, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:10:48'),
(45, 12, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:10:50'),
(46, 12, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-17 21:10:50'),
(47, 13, NULL, 'pending', 'student', '1', 'Prince Edward Guevara', NULL, '2026-10-25 20:23:20'),
(48, 13, 'pending', 'confirmed', 'staff', '1', 'Admin Prince', NULL, '2026-10-25 20:23:45'),
(49, 13, 'confirmed', 'preparing', 'staff', '1', 'Admin Prince', NULL, '2026-10-25 20:23:47'),
(50, 13, 'preparing', 'ready', 'staff', '1', 'Admin Prince', NULL, '2026-10-25 20:23:47'),
(51, 13, 'ready', 'completed', 'staff', '1', 'Admin Prince', NULL, '2026-10-25 20:23:48');

-- --------------------------------------------------------

--
-- Table structure for table `staff_accounts`
--

CREATE TABLE `staff_accounts` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'staff',
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_attempts`
--

CREATE TABLE `staff_login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `was_success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `grade_level` varchar(30) DEFAULT NULL,
  `section` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('student','staff','admin') NOT NULL DEFAULT 'student',
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `username`, `password`, `first_name`, `last_name`, `grade_level`, `section`, `email`, `phone`, `role`, `is_banned`, `created_at`, `last_login`) VALUES
(1, '2026-0002', 'admin', '$2y$10$O1Q1ao9kAflP8nH.s/Yal.gJxtCKvmMW6Bb0InntpZCeE2zLosOKK', 'Prince Edward', 'Guevara', '12', 'Indonesia', 'guevaraprinceedward@gmail.com', NULL, 'staff', 0, '2026-10-12 02:12:14', '2026-10-26 04:23:10'),
(2, '2026-0000', 'janneltorres', '$2y$10$0CRKq3I86bVZg6sLtbiULeInM6sM8ZT2L82/0vzhhS.egh5HJxXbi', 'Jannel', 'Torres', NULL, NULL, 'jannelmariatorres@gmail.com', NULL, 'staff', 0, '2026-10-12 02:12:14', '2026-10-17 06:10:39'),
(3, '2026-0001', 'juan.delacruz', '$2y$10$wTO90BM0J2phhmDsgu92eOdc/jh8F4ovbGgNXxQI1qvQeemFPdsd6', 'Juan', 'Dela Cruz', 'Grade 11', 'STEM-A', 'juan@student.local', NULL, 'student', 0, '2026-10-12 02:12:14', NULL),
(4, '2026-0010', 'johnpaul.pedron', '$2y$10$/au.k2KguLTwFayRIoyVQeLdyxuAL/j9bv.B3Fo8TP2Log9MqZZkq', 'John Paul', 'Pedron', 'Grade 12', 'Bhutan', 'johnpaulpedron@cmdi.edu', NULL, 'student', 0, '2026-10-12 02:12:14', '2026-10-18 05:07:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_admin` (`admin_id`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_staff_id` (`staff_id`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indexes for table `auth_events`
--
ALTER TABLE `auth_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auth_account` (`scope`,`event`,`identifier`,`ip_address`,`created_at`),
  ADD KEY `idx_auth_ip` (`scope`,`event`,`ip_address`,`created_at`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_product` (`product_id`,`created_at`),
  ADD KEY `idx_inv_created` (`created_at`);

--
-- Indexes for table `personal_testimonials`
--
ALTER TABLE `personal_testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_testimonials_active` (`is_active`,`sort_order`);

--
-- Indexes for table `pickup_slots`
--
ALTER TABLE `pickup_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_sku` (`sku`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_featured` (`is_featured`,`is_available`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pickup_date` (`pickup_date`,`status`),
  ADD KEY `pickup_slot_id` (`pickup_slot_id`,`pickup_date`);

--
-- Indexes for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservation_status_log`
--
ALTER TABLE `reservation_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`ip_address`,`attempted_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_student_id` (`student_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `auth_events`
--
ALTER TABLE `auth_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `personal_testimonials`
--
ALTER TABLE `personal_testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pickup_slots`
--
ALTER TABLE `pickup_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reservation_items`
--
ALTER TABLE `reservation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `reservation_status_log`
--
ALTER TABLE `reservation_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `staff_accounts`
--
ALTER TABLE `staff_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_slot` FOREIGN KEY (`pickup_slot_id`) REFERENCES `pickup_slots` (`id`);

--
-- Constraints for table `reservation_items`
--
ALTER TABLE `reservation_items`
  ADD CONSTRAINT `fk_item_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservation_status_log`
--
ALTER TABLE `reservation_status_log`
  ADD CONSTRAINT `fk_log_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
