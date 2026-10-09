-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 09:28 AM
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
-- Table structure for table `personal_testimonials`
--

CREATE TABLE `personal_testimonials` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `client_location` varchar(120) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `testimonial` text NOT NULL,
  `venture` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `personal_testimonials`
--

INSERT INTO `personal_testimonials` (`id`, `client_name`, `client_location`, `avatar_url`, `rating`, `testimonial`, `venture`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Client Name Here', 'City, Province', NULL, 5, 'Replace this placeholder with an actual testimonial from a real client.', 'Project / venture name', 1, 10, '2026-10-03 20:26:22'),
(2, 'Client Name Here', 'City, Province', NULL, 5, 'Replace this placeholder with an actual testimonial from a real client.', 'Project / venture name', 1, 20, '2026-10-03 20:26:22'),
(3, 'Client Name Here', 'City, Province', NULL, 5, 'Replace this placeholder with an actual testimonial from a real client.', 'Project / venture name', 1, 30, '2026-10-03 20:26:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `personal_testimonials`
--
ALTER TABLE `personal_testimonials`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `personal_testimonials`
--
ALTER TABLE `personal_testimonials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
