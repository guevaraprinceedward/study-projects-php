-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2026 at 12:57 PM
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
-- Database: `el_grande_torres`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_testimonials`
--

CREATE TABLE `about_testimonials` (
  `testimonial_id` int(11) NOT NULL,
  `client_name` varchar(120) NOT NULL,
  `client_branch` varchar(100) NOT NULL COMMENT 'City / branch the client is associated with, e.g. "Makati City"',
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `quote_text` text NOT NULL,
  `avatar_initial` varchar(4) DEFAULT NULL COMMENT 'Fallback initials shown when no photo is set',
  `avatar_image` varchar(255) DEFAULT NULL COMMENT 'Path/URL to the client photo; falls back to avatar_initial when empty',
  `row_group` enum('row1','row2') NOT NULL DEFAULT 'row1' COMMENT 'Which marquee row this card scrolls in',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_testimonials`
--

INSERT INTO `about_testimonials` (`testimonial_id`, `client_name`, `client_branch`, `rating`, `quote_text`, `avatar_initial`, `avatar_image`, `row_group`, `is_active`, `display_order`, `created_at`) VALUES
(1, 'Isabella Marasigan', 'Makati City', 5, 'Torres', 'IM', NULL, 'row1', 1, 1, '2026-08-14 10:22:16'),
(2, 'Rafael Domingo', 'Quezon City', 5, 'Torres', 'RD', NULL, 'row1', 1, 2, '2026-08-14 10:22:16'),
(3, 'Camille Suarez', 'Cebu City', 5, 'Torres', 'CS', NULL, 'row1', 1, 3, '2026-08-14 10:22:16'),
(4, 'Joaquin Ferrer', 'Davao City', 4, 'Torres', 'JF', NULL, 'row1', 1, 4, '2026-08-14 10:22:16'),
(5, 'Patricia Nolasco', 'Taguig City', 5, 'I bought the Torres Column Dress for my sister\'s wedding — got more compliments on the dress than the bride did, sorry not sorry.', 'PN', NULL, 'row1', 1, 5, '2026-08-14 10:22:16'),
(6, 'Miguel Salonga', 'San Pablo City', 5, 'Local customer here — the fact that a house this refined is based near us makes it even better. Their loafers are worth every peso.', 'MS', NULL, 'row1', 1, 6, '2026-08-14 10:22:16'),
(7, 'Andrea Villaflor', 'Pasig City', 4, 'Beautiful essentials collection. The perfume lasts all day and the packaging alone feels like a gift.', 'AV', NULL, 'row1', 1, 7, '2026-08-14 10:22:16'),
(8, 'Diego Reyes', 'Iloilo City', 5, 'Limited edition drops are no joke — copped the House Crest Bomber and it sold out within days. Glad I did not hesitate.', 'DR', NULL, 'row2', 1, 1, '2026-08-14 10:22:16'),
(9, 'Samantha Cruz', 'Baguio City', 5, 'The wool coat kept me warm through the entire Baguio trip and still looked sharp in every photo. Worth the investment.', 'SC', NULL, 'row2', 1, 2, '2026-08-14 10:22:16'),
(10, 'Nathaniel Ocampo', 'Muntinlupa City', 5, 'Customer support responded within minutes when I had a sizing question. That kind of care is rare these days.', 'NO', NULL, 'row2', 1, 3, '2026-08-14 10:22:16'),
(11, 'Beatriz Lim', 'Cagayan de Oro', 4, 'Gorgeous pieces, true to size, and the crest detailing is so subtle and tasteful. Will be ordering again this season.', 'BL', NULL, 'row2', 1, 4, '2026-08-14 10:22:16'),
(12, 'Gabriel Tantoco', 'Antipolo City', 5, 'The automatic watch I ordered is a masterpiece. Feels like something you pass down, not just wear.', 'GT', NULL, 'row2', 1, 5, '2026-08-14 10:22:16'),
(13, 'Renz Almario', 'Batangas City', 5, 'From browsing to unboxing, everything about The Atelier Noir feels intentional. This is how online fashion should feel.', 'RA', NULL, 'row2', 1, 6, '2026-08-14 10:22:16'),
(14, 'Louella Bautista', 'Lipa City', 4, 'Their essentials line quietly became my everyday go-to. The card holder and the crest pendant never leave my bag.', 'LB', NULL, 'row2', 1, 7, '2026-08-14 10:22:16');

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
  `role` enum('super_admin','manager','staff') NOT NULL DEFAULT 'staff',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `username`, `email`, `password_hash`, `role`, `last_login`, `created_at`) VALUES
(1, 'System Administrator', 'admin', 'admin@elgrandedelatorres.com', 'RENEBATERBONIA', 'super_admin', NULL, '2026-08-07 20:05:21');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-08-08 01:28:08', '2026-08-08 01:28:08'),
(2, 2, '2026-08-18 13:36:35', '2026-08-18 13:36:35'),
(3, 3, '2026-08-27 01:35:11', '2026-08-27 01:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_type` enum('clothing','essentials') NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(40) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_add` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `store_type` enum('clothing','essentials') NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `store_type`, `name`, `slug`, `description`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'clothing', 'T-Shirts', 't-shirts', NULL, 1, 1, '2026-08-07 20:05:20'),
(2, 'clothing', 'Shirts', 'shirts', NULL, 2, 1, '2026-08-07 20:05:20'),
(3, 'clothing', 'Hoodies & Jackets', 'hoodies-jackets', NULL, 3, 1, '2026-08-07 20:05:20'),
(4, 'clothing', 'Coats', 'coats', NULL, 4, 1, '2026-08-07 20:05:20'),
(5, 'clothing', 'Vests', 'vests', NULL, 5, 1, '2026-08-07 20:05:20'),
(6, 'clothing', 'Pants', 'pants', NULL, 6, 1, '2026-08-07 20:05:20'),
(7, 'clothing', 'Dresses', 'dresses', NULL, 7, 1, '2026-08-07 20:05:20'),
(8, 'clothing', 'Sneakers', 'sneakers', NULL, 8, 1, '2026-08-07 20:05:20'),
(9, 'clothing', 'Loafers', 'loafers', NULL, 9, 1, '2026-08-07 20:05:20'),
(10, 'essentials', 'Everyday Essentials', 'everyday-essentials', NULL, 1, 1, '2026-08-07 20:05:20'),
(11, 'essentials', 'Travel Essentials', 'travel-essentials', NULL, 2, 1, '2026-08-07 20:05:20'),
(12, 'essentials', 'Lifestyle', 'lifestyle', NULL, 3, 1, '2026-08-07 20:05:20'),
(13, 'essentials', 'Grooming', 'grooming', NULL, 4, 1, '2026-08-07 20:05:20'),
(14, 'essentials', 'Necklaces', 'necklaces', NULL, 6, 1, '2026-08-07 20:05:20'),
(15, 'essentials', 'Rings', 'rings', NULL, 7, 1, '2026-08-07 20:05:20'),
(16, 'essentials', 'Hats', 'hats', NULL, 9, 1, '2026-08-07 20:05:20'),
(17, 'essentials', 'Glasses', 'glasses', NULL, 10, 1, '2026-08-07 20:05:20'),
(18, 'essentials', 'Watches', 'watches', NULL, 11, 1, '2026-08-07 20:05:20'),
(19, 'essentials', 'Perfumes', 'perfumes', NULL, 5, 1, '2026-08-11 20:45:00'),
(20, 'essentials', 'Earrings', 'earrings', NULL, 8, 1, '2026-08-11 20:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `clothing_products`
--

CREATE TABLE `clothing_products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `sku` varchar(40) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `brand` varchar(80) DEFAULT 'El Grande De La Torres',
  `gender` enum('men','women','unisex') NOT NULL DEFAULT 'unisex',
  `material` varchar(150) DEFAULT NULL,
  `care_instructions` text DEFAULT NULL,
  `available_sizes` varchar(150) DEFAULT NULL,
  `available_colors` varchar(255) DEFAULT NULL,
  `primary_image` varchar(255) NOT NULL,
  `gallery_images` text DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_new_arrival` tinyint(1) NOT NULL DEFAULT 0,
  `is_best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_limited_edition` tinyint(1) NOT NULL DEFAULT 0,
  `limited_edition_note` varchar(150) DEFAULT NULL COMMENT 'e.g. "Only 50 pieces worldwide"',
  `season` varchar(40) DEFAULT NULL COMMENT 'e.g. Autumn 2026, for Seasonal Collection',
  `status` enum('active','draft','archived') NOT NULL DEFAULT 'active',
  `views_count` int(11) NOT NULL DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clothing_products`
--

INSERT INTO `clothing_products` (`product_id`, `category_id`, `sku`, `name`, `slug`, `short_description`, `description`, `price`, `compare_at_price`, `brand`, `gender`, `material`, `care_instructions`, `available_sizes`, `available_colors`, `primary_image`, `gallery_images`, `stock_quantity`, `is_new_arrival`, `is_best_seller`, `is_trending`, `is_featured`, `is_limited_edition`, `limited_edition_note`, `season`, `status`, `views_count`, `sales_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'EGT-TS-001', 'Signature Crest Tee', 'signature-crest-tee', 'Heavyweight cotton tee with the house crest.', 'Our house signature tee, cut from 260gsm heavyweight combed cotton and finished with an embroidered crest at the chest. A quiet everyday staple built to outlast trend cycles.', 2450.00, NULL, 'El Grande De La Torres', 'unisex', '100% Combed Cotton', 'Machine wash cold, hang dry.', 'XS,S,M,L,XL,XXL', 'Black,White,Stone', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=900&auto=format&fit=crop', NULL, 120, 0, 1, 1, 1, 0, NULL, NULL, 'active', 340, 88, '2026-06-01 09:00:00', '2026-08-01 09:00:00'),
(2, 2, 'EGT-SH-001', 'Ivory Oxford Shirt', 'ivory-oxford-shirt', 'Tailored oxford shirt in soft ivory.', 'A refined oxford shirt tailored for a clean silhouette, finished with mother-of-pearl buttons and a structured collar. Equally suited to the office or an evening out.', 4200.00, 4800.00, 'El Grande De La Torres', 'men', '100% Egyptian Cotton', 'Dry clean recommended.', 'S,M,L,XL', 'Ivory,Sky Blue', 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?q=80&w=900&auto=format&fit=crop', NULL, 60, 1, 0, 1, 1, 0, NULL, 'Autumn 2026', 'active', 211, 34, '2026-07-20 09:00:00', '2026-08-09 21:32:07'),
(3, 3, 'EGT-HJ-001', 'Atelier Bomber Jacket', 'atelier-bomber-jacket', 'Structured bomber in Italian wool blend.', 'Cut from an Italian wool-blend twill, the Atelier Bomber balances architectural structure with everyday ease. Ribbed cuffs and hem, fully lined in silk twill.', 8900.00, NULL, 'El Grande De La Torres', 'unisex', 'Wool Blend, Silk Lining', 'Dry clean only.', 'S,M,L,XL', 'Charcoal,Black', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=900&auto=format&fit=crop', NULL, 25, 1, 1, 1, 1, 1, 'Only 50 pieces worldwide', 'Autumn 2026', 'active', 512, 41, '2026-07-25 09:00:00', '2026-08-02 09:00:00'),
(4, 4, 'EGT-CT-001', 'Manila Wool Coat', 'manila-wool-coat', 'Full-length wool coat, hand-finished.', 'A full-length coat in double-faced wool, hand-finished at the atelier with horn buttons and a fully canvassed construction for structure that holds its shape for decades.', 15800.00, NULL, 'El Grande De La Torres', 'women', '100% Wool', 'Dry clean only.', 'XS,S,M,L', 'Camel,Black', 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?q=80&w=900&auto=format&fit=crop', NULL, 18, 0, 1, 0, 1, 0, NULL, NULL, 'active', 289, 52, '2026-05-10 09:00:00', '2026-07-15 09:00:00'),
(5, 6, 'EGT-PT-001', 'Tailored House Trousers', 'tailored-house-trousers', 'Slim-tapered wool trousers.', 'Slim-tapered trousers in Italian wool with a mid-rise waist and hidden hook closure. The everyday trouser for those who notice the details.', 3600.00, NULL, 'El Grande De La Torres', 'men', '100% Wool', 'Dry clean recommended.', '28,30,32,34,36', 'Black,Charcoal,Navy', 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?q=80&w=900&auto=format&fit=crop', NULL, 75, 0, 1, 0, 0, 0, NULL, NULL, 'active', 178, 63, '2026-04-18 09:00:00', '2026-06-20 09:00:00'),
(6, 7, 'EGT-DR-001', 'Torres Column Dress', 'torres-column-dress', 'Silk column dress with draped back.', 'An elegant column dress in mulberry silk, finished with a softly draped back and adjustable straps. Made for evenings that call for quiet confidence.', 9200.00, 10500.00, 'El Grande De La Torres', 'women', '100% Mulberry Silk', 'Dry clean only.', 'XS,S,M,L', 'Emerald,Black,Ivory', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=900&auto=format&fit=crop', NULL, 30, 1, 0, 1, 1, 0, NULL, 'Autumn 2026', 'active', 401, 29, '2026-07-28 09:00:00', '2026-08-03 09:00:00'),
(7, 8, 'EGT-SN-001', 'Maison Court Sneaker', 'maison-court-sneaker', 'Minimalist leather court sneaker.', 'A minimalist court sneaker crafted in full-grain leather with a hand-stitched sole. Designed to age gracefully with every wear.', 6800.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', 'Wipe clean with a damp cloth.', '38,39,40,41,42,43,44,45', 'White,Black', 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=900&auto=format&fit=crop', NULL, 90, 0, 1, 1, 0, 0, NULL, NULL, 'active', 624, 97, '2026-03-12 09:00:00', '2026-08-10 17:04:44'),
(8, 9, 'EGT-LF-001', 'Torres Signature Loafer', 'torres-signature-loafer', 'Hand-stitched leather loafer.', 'Hand-stitched in full-grain calfskin with a leather sole, the Signature Loafer is built on a last that balances comfort with a refined silhouette.', 7400.00, NULL, 'El Grande De La Torres', 'men', 'Calfskin Leather', 'Use a shoe tree; polish regularly.', '39,40,41,42,43,44,45', 'Brown,Black', 'https://images.unsplash.com/photo-1614252369475-531eba835eb1?q=80&w=900&auto=format&fit=crop', NULL, 40, 0, 0, 0, 0, 1, 'Only 30 pairs per season', 'Autumn 2026', 'active', 156, 12, '2026-06-15 09:00:00', '2026-08-11 20:46:38'),
(9, 3, 'EGT-HJ-002', 'Cloud Zip Hoodie', 'cloud-zip-hoodie', 'Brushed-back fleece zip hoodie.', 'A brushed-back fleece hoodie with a relaxed fit, ribbed hem, and subtle tonal embroidery. Effortless comfort with house-level finishing.', 3200.00, NULL, 'El Grande De La Torres', 'unisex', 'Cotton Fleece', 'Machine wash cold.', 'XS,S,M,L,XL,XXL', 'Grey,Black,Olive', 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=900&auto=format&fit=crop', NULL, 150, 1, 0, 0, 0, 0, NULL, NULL, 'active', 99, 15, '2026-08-01 09:00:00', '2026-08-08 01:12:26'),
(10, 5, 'EGT-VS-001', 'Tailored Wool Vest', 'tailored-wool-vest', 'Structured wool waistcoat.', 'A structured waistcoat in fine wool, cut for a modern silhouette and finished with horn buttons. Layer it over the Oxford Shirt for a complete look.', 4600.00, NULL, 'El Grande De La Torres', 'men', '100% Wool', 'Dry clean only.', 'S,M,L,XL', 'Charcoal,Navy', 'https://images.unsplash.com/photo-1592878849122-facb97520f9e?q=80&w=900&auto=format&fit=crop', NULL, 45, 1, 0, 0, 0, 0, NULL, 'Autumn 2026', 'active', 69, 8, '2026-08-03 09:00:00', '2026-08-09 20:44:28'),
(11, 2, 'EGT-SH-002', 'Charcoal Flannel Shirt', 'charcoal-flannel-shirt', 'Soft brushed flannel shirt.', 'A brushed flannel shirt in a soft charcoal weave, cut with a relaxed fit and mother-of-pearl buttons. A cold-weather staple with quiet detail.', 3800.00, NULL, 'El Grande De La Torres', 'men', 'Brushed Cotton Flannel', 'Machine wash cold.', 'S,M,L,XL,XXL', 'Charcoal,Forest Green', 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=900&auto=format&fit=crop', NULL, 80, 0, 1, 0, 0, 0, NULL, NULL, 'active', 132, 44, '2026-05-22 09:00:00', '2026-07-01 09:00:00'),
(12, 1, 'EGT-TS-002', 'Essential Long Sleeve Tee', 'essential-long-sleeve-tee', 'Ribbed long-sleeve cotton tee.', 'A ribbed long-sleeve tee in premium cotton jersey, designed as a foundational layer with the same discipline as every other house piece.', 2100.00, NULL, 'El Grande De La Torres', 'unisex', '100% Cotton Jersey', 'Machine wash cold.', 'XS,S,M,L,XL', 'Black,White,Grey', 'https://images.unsplash.com/photo-1571945153237-4929e783af4a?q=80&w=900&auto=format&fit=crop', NULL, 109, 1, 1, 0, 0, 0, NULL, NULL, 'active', 152, 28, '2026-08-04 09:00:00', '2026-08-11 19:33:56'),
(13, 1, 'EGT-TS-003', 'Classic Crew Tee', 'classic-crew-tee', 'Everyday crewneck in soft cotton jersey.', 'A relaxed crewneck tee built from mid-weight cotton jersey, designed to be the quiet foundation of any outfit.', 1950.00, NULL, 'El Grande De La Torres', 'unisex', '100% Cotton Jersey', 'Machine wash cold.', 'XS,S,M,L,XL', 'Black,White,Navy', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=900&auto=format&fit=crop', NULL, 130, 1, 0, 0, 0, 0, NULL, NULL, 'active', 40, 6, '2026-08-11 01:00:00', '2026-08-11 01:00:00'),
(14, 1, 'EGT-TS-004', 'Monochrome Graphic Tee', 'monochrome-graphic-tee', 'Tonal crest graphic on heavyweight cotton.', 'A tonal, screen-printed crest graphic on heavyweight 260gsm cotton — a subtler take on the house signature tee.', 2250.00, NULL, 'El Grande De La Torres', 'unisex', '100% Combed Cotton', 'Machine wash cold, inside out.', 'XS,S,M,L,XL,XXL', 'Black,Stone', 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?q=80&w=900&auto=format&fit=crop', NULL, 95, 1, 0, 1, 0, 0, NULL, NULL, 'active', 58, 9, '2026-08-11 01:05:00', '2026-08-11 01:05:00'),
(15, 1, 'EGT-TS-005', 'Striped Boatneck Tee', 'striped-boatneck-tee', 'Breton-striped tee in fine cotton.', 'A Breton-striped boatneck tee in fine combed cotton, cut with a relaxed drop shoulder for easy movement.', 2100.00, NULL, 'El Grande De La Torres', 'women', '100% Combed Cotton', 'Machine wash cold.', 'XS,S,M,L', 'Navy/White,Black/White', 'https://images.unsplash.com/photo-1554568218-0f1715e72254?q=80&w=900&auto=format&fit=crop', NULL, 70, 0, 0, 0, 0, 0, NULL, NULL, 'active', 33, 5, '2026-08-11 01:10:00', '2026-08-11 01:10:00'),
(16, 2, 'EGT-SH-003', 'Denim Western Shirt', 'denim-western-shirt', 'Rigid denim shirt with pearl snaps.', 'A rigid denim shirt with pearl snap closures and dual chest pockets, worked in for character from the first wear.', 4600.00, NULL, 'El Grande De La Torres', 'men', '100% Cotton Denim', 'Machine wash cold, hang dry.', 'S,M,L,XL,XXL', 'Indigo', 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=900&auto=format&fit=crop', NULL, 55, 0, 0, 0, 0, 0, NULL, NULL, 'active', 71, 14, '2026-08-11 01:15:00', '2026-08-11 01:15:00'),
(17, 2, 'EGT-SH-004', 'Silk Blouse', 'silk-blouse', 'Fluid mulberry silk blouse.', 'A fluid mulberry silk blouse with a soft draped neckline, equally suited to the office and evening.', 5400.00, NULL, 'El Grande De La Torres', 'women', '100% Mulberry Silk', 'Dry clean only.', 'XS,S,M,L', 'Ivory,Blush,Black', 'https://images.unsplash.com/photo-1551048632-24e444b48a3e?q=80&w=900&auto=format&fit=crop', NULL, 40, 1, 0, 0, 0, 0, NULL, 'Autumn 2026', 'active', 47, 7, '2026-08-11 01:20:00', '2026-08-11 01:20:00'),
(18, 3, 'EGT-HJ-003', 'Quilted Field Jacket', 'quilted-field-jacket', 'Waxed cotton field jacket, quilted lining.', 'A waxed-cotton field jacket with a quilted inner lining, corduroy collar, and multiple utility pockets.', 7200.00, NULL, 'El Grande De La Torres', 'men', 'Waxed Cotton, Quilted Lining', 'Wipe clean, re-wax as needed.', 'S,M,L,XL', 'Olive,Black', 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?q=80&w=900&auto=format&fit=crop', NULL, 35, 1, 0, 1, 0, 0, NULL, 'Autumn 2026', 'active', 88, 11, '2026-08-11 01:25:00', '2026-08-11 01:25:00'),
(19, 3, 'EGT-HJ-004', 'Cashmere Hoodie', 'cashmere-hoodie', 'Pure cashmere hoodie, effortlessly refined.', 'A pure cashmere hoodie that trades bulk for softness — casualwear elevated to house standard.', 9800.00, NULL, 'El Grande De La Torres', 'unisex', '100% Cashmere', 'Dry clean or hand wash cold.', 'XS,S,M,L,XL', 'Grey,Camel,Black', 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=900&auto=format&fit=crop', NULL, 28, 1, 0, 0, 1, 0, NULL, NULL, 'active', 64, 8, '2026-08-11 01:30:00', '2026-08-11 01:30:00'),
(20, 4, 'EGT-CT-002', 'Charcoal Overcoat', 'charcoal-overcoat', 'Single-breasted wool overcoat.', 'A single-breasted overcoat in brushed wool, cut for a lean silhouette and finished with horn buttons.', 13800.00, NULL, 'El Grande De La Torres', 'men', '100% Wool', 'Dry clean only.', 'S,M,L,XL', 'Charcoal,Navy', 'https://images.unsplash.com/photo-1544923246-77307dd654cb?q=80&w=900&auto=format&fit=crop', NULL, 22, 0, 0, 0, 1, 0, NULL, NULL, 'active', 102, 19, '2026-08-11 01:35:00', '2026-08-11 01:35:00'),
(21, 4, 'EGT-CT-003', 'Belted Trench Coat', 'belted-trench-coat', 'Classic cotton-gabardine trench.', 'A cotton-gabardine trench in the classic silhouette, finished with a storm flap and self-tie belt.', 11200.00, NULL, 'El Grande De La Torres', 'women', 'Cotton Gabardine', 'Dry clean recommended.', 'XS,S,M,L', 'Camel,Black', 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?q=80&w=900&auto=format&fit=crop', NULL, 26, 1, 0, 0, 0, 0, NULL, 'Autumn 2026', 'active', 76, 10, '2026-08-11 01:40:00', '2026-08-11 01:40:00'),
(22, 5, 'EGT-VS-002', 'Quilted Puffer Vest', 'quilted-puffer-vest', 'Lightweight quilted puffer vest.', 'A lightweight quilted puffer vest with a stand collar, made for layering across the colder months.', 3900.00, NULL, 'El Grande De La Torres', 'unisex', 'Nylon Shell, Down-Alternative Fill', 'Machine wash cold, gentle cycle.', 'XS,S,M,L,XL', 'Black,Olive,Stone', 'https://images.unsplash.com/photo-1544966503-7cc531be5cea?q=80&w=900&auto=format&fit=crop', NULL, 60, 1, 0, 0, 0, 0, NULL, NULL, 'active', 39, 6, '2026-08-11 01:45:00', '2026-08-11 01:45:00'),
(23, 5, 'EGT-VS-003', 'Silk Evening Vest', 'silk-evening-vest', 'Fitted silk waistcoat for evening.', 'A fitted silk waistcoat cut for evening wear, with a satin back panel and covered buttons.', 5200.00, NULL, 'El Grande De La Torres', 'women', '100% Silk', 'Dry clean only.', 'XS,S,M,L', 'Black,Emerald', 'https://images.unsplash.com/photo-1592878849122-facb97520f9e?q=80&w=900&auto=format&fit=crop', NULL, 24, 0, 0, 0, 0, 0, NULL, NULL, 'active', 21, 3, '2026-08-11 01:50:00', '2026-08-11 01:50:00'),
(24, 6, 'EGT-PT-002', 'Pleated Wide-Leg Trousers', 'pleated-wide-leg-trousers', 'Fluid wide-leg trousers, high-waisted.', 'High-waisted, pleated trousers in fluid wool crepe with a wide, fluid leg for a fashion-forward silhouette.', 4100.00, NULL, 'El Grande De La Torres', 'women', 'Wool Crepe', 'Dry clean recommended.', 'XS,S,M,L,XL', 'Black,Cream,Navy', 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=900&auto=format&fit=crop', NULL, 48, 1, 0, 0, 0, 0, NULL, NULL, 'active', 29, 4, '2026-08-11 01:55:00', '2026-08-11 01:55:00'),
(25, 6, 'EGT-PT-003', 'Relaxed Denim Trousers', 'relaxed-denim-trousers', 'Straight-leg Japanese selvedge denim.', 'Straight-leg trousers in Japanese selvedge denim, garment-washed for a broken-in feel from day one.', 4400.00, NULL, 'El Grande De La Torres', 'unisex', '100% Selvedge Denim', 'Machine wash cold, hang dry.', '28,30,32,34,36,38', 'Indigo,Black', 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?q=80&w=900&auto=format&fit=crop', NULL, 66, 0, 1, 0, 0, 0, NULL, NULL, 'active', 84, 22, '2026-08-11 02:00:00', '2026-08-11 02:00:00'),
(26, 7, 'EGT-DR-002', 'Wrap Midi Dress', 'wrap-midi-dress', 'Fluid wrap dress in crepe jersey.', 'A wrap midi dress in fluid crepe jersey, self-tied at the waist and cut to move with the body.', 6200.00, NULL, 'El Grande De La Torres', 'women', 'Crepe Jersey', 'Hand wash cold.', 'XS,S,M,L', 'Burgundy,Black,Navy', 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=900&auto=format&fit=crop', NULL, 38, 1, 0, 1, 0, 0, NULL, NULL, 'active', 91, 13, '2026-08-11 02:05:00', '2026-08-11 02:05:00'),
(27, 7, 'EGT-DR-003', 'Sequin Cocktail Dress', 'sequin-cocktail-dress', 'Hand-sequined mini cocktail dress.', 'A hand-sequined cocktail dress with a fitted bodice and mini hem, made for evenings under low light.', 10800.00, NULL, 'El Grande De La Torres', 'women', 'Sequin Mesh, Silk Lining', 'Dry clean only.', 'XS,S,M,L', 'Black,Gold', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=900&auto=format&fit=crop', NULL, 16, 1, 0, 0, 1, 0, NULL, 'Autumn 2026', 'active', 133, 12, '2026-08-11 02:10:00', '2026-08-11 02:10:00'),
(28, 8, 'EGT-SN-002', 'Retro Runner Sneaker', 'retro-runner-sneaker', 'Suede-and-mesh retro running silhouette.', 'A suede-and-mesh retro runner with a cushioned midsole, built for city miles without sacrificing polish.', 5900.00, NULL, 'El Grande De La Torres', 'unisex', 'Suede, Mesh', 'Wipe clean with a soft brush.', '38,39,40,41,42,43,44', 'Grey,Navy', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=900&auto=format&fit=crop', NULL, 70, 1, 0, 1, 0, 0, NULL, NULL, 'active', 118, 27, '2026-08-11 02:15:00', '2026-08-11 02:15:00'),
(29, 8, 'EGT-SN-003', 'Canvas Low-Top Sneaker', 'canvas-low-top-sneaker', 'Everyday canvas low-top.', 'A clean canvas low-top with a vulcanized rubber sole, the easiest sneaker in the house lineup.', 4200.00, NULL, 'El Grande De La Torres', 'unisex', 'Cotton Canvas, Rubber Sole', 'Spot clean.', '37,38,39,40,41,42,43,44,45', 'White,Black', 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?q=80&w=900&auto=format&fit=crop', NULL, 85, 0, 1, 0, 0, 0, NULL, NULL, 'active', 74, 31, '2026-08-11 02:20:00', '2026-08-11 02:20:00'),
(30, 9, 'EGT-LF-002', 'Tassel Suede Loafer', 'tassel-suede-loafer', 'Suede loafer with tasseled vamp.', 'A suede loafer with a tasseled vamp and leather sole, striking a balance between casual and refined.', 6900.00, NULL, 'El Grande De La Torres', 'men', 'Suede, Leather Sole', 'Use a suede brush; avoid moisture.', '39,40,41,42,43,44,45', 'Tan,Grey', 'https://images.unsplash.com/photo-1614252369475-531eba835eb1?q=80&w=900&auto=format&fit=crop', NULL, 33, 0, 0, 0, 0, 0, NULL, NULL, 'active', 52, 9, '2026-08-11 02:25:00', '2026-08-11 02:25:00'),
(31, 9, 'EGT-LF-003', 'Penny Driving Loafer', 'penny-driving-loafer', 'Rubber-studded driving loafer.', 'A rubber-studded driving loafer in soft calfskin, built for grip and comfort behind the wheel.', 5600.00, NULL, 'El Grande De La Torres', 'unisex', 'Calfskin Leather', 'Wipe clean with a damp cloth.', '38,39,40,41,42,43,44,45', 'Navy,Brown', 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?q=80&w=900&auto=format&fit=crop', NULL, 44, 1, 0, 0, 0, 0, NULL, NULL, 'active', 37, 5, '2026-08-11 02:30:00', '2026-08-11 02:30:00'),
(32, 1, 'EGT-LE-001', 'Black Longsleeve El Grande', 'black-longsleeve-el-grande-limited', 'Signature long sleeve, limited house run.', 'A heavyweight black long-sleeve tee from a single limited house run, finished with an embroidered crest and satin interior label numbered by piece.', 9500.00, 10500.00, 'El Grande De La Torres', 'unisex', '100% Heavyweight Cotton', 'Hand wash cold, hang dry.', 'XS,S,M,L,XL,XXL', 'Black', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=900&auto=format&fit=crop', NULL, 24, 1, 0, 1, 1, 1, 'Only 100 pieces worldwide', 'Autumn 2026', 'active', 210, 18, '2026-08-11 02:35:00', '2026-08-11 02:35:00'),
(33, 2, 'EGT-LE-002', 'Ivory Silk Limited Shirt', 'ivory-silk-limited-shirt', 'Hand-numbered silk shirt, limited run.', 'A hand-numbered shirt in pure mulberry silk, cut from a single limited bolt reserved exclusively for this collection.', 6800.00, 7800.00, 'El Grande De La Torres', 'men', '100% Mulberry Silk', 'Dry clean only.', 'S,M,L,XL', 'Ivory', 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?q=80&w=900&auto=format&fit=crop', NULL, 20, 0, 0, 0, 1, 1, 'Only 60 pieces worldwide', 'Autumn 2026', 'active', 96, 8, '2026-08-11 02:40:00', '2026-08-11 02:40:00'),
(34, 7, 'EGT-LE-003', 'Noir Column Gown', 'noir-column-gown-limited', 'Hand-draped silk column gown, limited run.', 'A hand-draped column gown in double silk crepe, made to order in a single limited run for the season.', 15800.00, 18500.00, 'El Grande De La Torres', 'women', '100% Silk Crepe', 'Dry clean only.', 'XS,S,M,L', 'Black', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=900&auto=format&fit=crop', NULL, 12, 0, 0, 0, 1, 1, 'Only 40 pieces worldwide', 'Autumn 2026', 'active', 158, 6, '2026-08-11 02:45:00', '2026-08-11 02:45:00'),
(35, 3, 'EGT-LE-004', 'House Crest Bomber — Limited', 'house-crest-bomber-limited', 'Numbered bomber, limited house run.', 'A numbered bomber jacket in Italian wool-blend twill with a hand-embroidered crest, produced in a single limited run.', 12000.00, 14000.00, 'El Grande De La Torres', 'unisex', 'Wool Blend, Silk Lining', 'Dry clean only.', 'S,M,L,XL', 'Charcoal', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=900&auto=format&fit=crop', NULL, 13, 0, 0, 1, 1, 1, 'Only 50 pieces worldwide', 'Autumn 2026', 'active', 191, 12, '2026-08-11 02:50:00', '2026-08-27 01:38:31'),
(36, 1, 'EGT-TS-006', 'Ribbed Muscle Tee', 'ribbed-muscle-tee', 'Fitted ribbed tee in stretch cotton.', 'A fitted ribbed tee cut from a stretch cotton blend, designed to hold its shape through the shoulder and taper cleanly at the waist.', 2350.00, NULL, 'El Grande De La Torres', 'men', '95% Cotton, 5% Elastane', 'Machine wash cold, hang dry.', 'XS,S,M,L,XL', 'Black,White,Charcoal', 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?q=80&w=900&auto=format&fit=crop', NULL, 87, 1, 0, 0, 0, 0, NULL, NULL, 'active', 14, 2, '2026-08-15 23:05:42', '2026-08-22 23:56:45'),
(37, 2, 'EGT-SH-005', 'Linen Resort Shirt', 'linen-resort-shirt', 'Breathable linen shirt for warm climates.', 'A relaxed-fit resort shirt in pure linen, finished with a camp collar and mother-of-pearl buttons — built for warm-weather ease without losing the house silhouette.', 3900.00, NULL, 'El Grande De La Torres', 'men', '100% Linen', 'Machine wash cold, iron while damp.', 'S,M,L,XL,XXL', 'White,Sand,Sage', 'https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?q=80&w=900&auto=format&fit=crop', NULL, 62, 1, 0, 1, 0, 0, NULL, NULL, 'active', 8, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(38, 3, 'EGT-HJ-005', 'Shearling Collar Jacket', 'shearling-collar-jacket', 'Suede jacket with faux shearling collar.', 'A suede-shell jacket finished with a faux-shearling collar and quilted lining — a cold-weather statement piece with unmistakable house detailing.', 10800.00, NULL, 'El Grande De La Torres', 'men', 'Suede, Faux Shearling', 'Professional leather clean only.', 'S,M,L,XL', 'Cognac,Black', 'https://images.unsplash.com/photo-1520975954732-35dd22299614?q=80&w=900&auto=format&fit=crop', NULL, 20, 1, 0, 0, 1, 0, NULL, 'Autumn 2026', 'active', 14, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(39, 4, 'EGT-CT-004', 'Double-Breasted Camel Coat', 'double-breasted-camel-coat', 'Statement camel wool overcoat.', 'A double-breasted overcoat in camel wool with peaked lapels and horn buttons — the definitive outerwear piece of the season.', 16500.00, NULL, 'El Grande De La Torres', 'women', '100% Wool', 'Dry clean only.', 'XS,S,M,L,XL', 'Camel', 'https://images.unsplash.com/photo-1591369822096-ffd140ec948f?q=80&w=900&auto=format&fit=crop', NULL, 16, 1, 0, 1, 1, 0, NULL, 'Autumn 2026', 'active', 21, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(40, 5, 'EGT-VS-004', 'Knit Merino Vest', 'knit-merino-vest', 'Fine-gauge merino knit waistcoat.', 'A fine-gauge merino knit vest, ideal layered over a shirt or worn alone — soft, breathable, and quietly refined.', 3200.00, NULL, 'El Grande De La Torres', 'unisex', '100% Merino Wool', 'Hand wash cold.', 'XS,S,M,L,XL', 'Navy,Grey,Burgundy', 'https://images.unsplash.com/photo-1608744882201-52a7f7f3dd60?q=80&w=900&auto=format&fit=crop', NULL, 54, 0, 0, 0, 0, 0, NULL, NULL, 'active', 6, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(41, 6, 'EGT-PT-004', 'Cargo Utility Trousers', 'cargo-utility-trousers', 'Tailored cargo trousers, technical cotton.', 'Tailored cargo trousers in technical cotton twill — utilitarian pockets refined with a slim, elevated leg.', 4800.00, NULL, 'El Grande De La Torres', 'men', 'Cotton Twill', 'Machine wash cold.', '28,30,32,34,36,38', 'Olive,Black,Stone', 'https://images.unsplash.com/photo-1517445312882-bc9910d016b7?q=80&w=900&auto=format&fit=crop', NULL, 58, 1, 0, 1, 0, 0, NULL, NULL, 'active', 17, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(42, 7, 'EGT-DR-004', 'Draped Satin Slip Dress', 'draped-satin-slip-dress', 'Bias-cut satin slip in mulberry silk.', 'A bias-cut slip dress in mulberry silk satin, finished with adjustable straps and a soft cowl back — evening minimalism at its finest.', 8600.00, NULL, 'El Grande De La Torres', 'women', '100% Silk Satin', 'Dry clean only.', 'XS,S,M,L', 'Champagne,Black,Emerald', 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?q=80&w=900&auto=format&fit=crop', NULL, 24, 1, 0, 0, 1, 0, NULL, 'Autumn 2026', 'active', 19, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(43, 8, 'EGT-SN-004', 'High-Top Leather Sneaker', 'high-top-leather-sneaker', 'Full-grain leather high-top.', 'A high-top sneaker in full-grain leather with a padded collar and hand-stitched sole — casual footwear elevated to house standard.', 7600.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', 'Wipe clean with a damp cloth.', '38,39,40,41,42,43,44,45', 'White,Black', 'https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?q=80&w=900&auto=format&fit=crop', NULL, 47, 1, 0, 0, 0, 0, NULL, NULL, 'active', 11, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(44, 9, 'EGT-LF-004', 'Horsebit Leather Loafer', 'horsebit-leather-loafer', 'Classic horsebit loafer, calfskin.', 'A classic horsebit loafer in polished calfskin with a leather sole — the definitive house dress shoe.', 8200.00, NULL, 'El Grande De La Torres', 'men', 'Calfskin Leather', 'Use a shoe tree; polish regularly.', '39,40,41,42,43,44,45', 'Black,Burgundy', 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?q=80&w=900&auto=format&fit=crop', NULL, 29, 0, 1, 0, 0, 0, NULL, NULL, 'active', 24, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(45, 1, 'EGT-TS-007', 'Oversized Crest Tee', 'oversized-crest-tee', 'Relaxed-fit tee, dropped shoulder.', 'An oversized fit tee with a dropped shoulder seam and rubberized crest print — a streetwear-informed take on the house signature.', 2600.00, NULL, 'El Grande De La Torres', 'unisex', '100% Cotton', 'Machine wash cold, inside out.', 'S,M,L,XL,XXL', 'Black,Washed Grey', 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?q=80&w=900&auto=format&fit=crop', NULL, 71, 1, 0, 1, 0, 0, NULL, NULL, 'active', 9, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(46, 6, 'EGT-PT-005', 'Silk-Blend Track Pants', 'silk-blend-track-pants', 'Fluid track pant, elevated fabrication.', 'A track-pant silhouette reworked in a fluid silk-cotton blend, with satin side taping for a quiet nod to sport heritage.', 4400.00, NULL, 'El Grande De La Torres', 'women', 'Silk-Cotton Blend', 'Hand wash cold.', 'XS,S,M,L', 'Black,Ivory', 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=900&auto=format&fit=crop', NULL, 33, 1, 0, 0, 0, 0, NULL, NULL, 'active', 5, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(47, 3, 'EGT-HJ-006', 'Technical Windbreaker', 'technical-windbreaker', 'Packable water-resistant windbreaker.', 'A packable, water-resistant windbreaker with taped seams and a stow-away hood — technical performance without sacrificing line.', 5600.00, NULL, 'El Grande De La Torres', 'unisex', 'Recycled Nylon', 'Machine wash cold, do not tumble dry.', 'XS,S,M,L,XL', 'Black,Navy', 'https://images.unsplash.com/photo-1544966503-7cc531be5cea?q=80&w=900&auto=format&fit=crop', NULL, 66, 0, 0, 1, 0, 0, NULL, NULL, 'active', 13, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(48, 7, 'EGT-DR-005', 'Tweed Mini Shift Dress', 'tweed-mini-shift-dress', 'Boucle tweed shift with gold-tone buttons.', 'A boucle tweed shift dress with gold-tone button detailing at the cuffs and collar — heritage tailoring reworked into a mini silhouette.', 9800.00, NULL, 'El Grande De La Torres', 'women', 'Boucle Wool Tweed', 'Dry clean only.', 'XS,S,M,L', 'Cream,Black', 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=900&auto=format&fit=crop', NULL, 21, 0, 0, 1, 1, 0, NULL, 'Autumn 2026', 'active', 16, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42'),
(49, 8, 'EGT-SN-005', 'Knit Sock Sneaker', 'knit-sock-sneaker', 'Seamless knit upper, sock-fit sneaker.', 'A sock-fit sneaker with a seamless knit upper and a lightweight EVA sole — engineered for movement without compromising the house clean profile.', 6200.00, NULL, 'El Grande De La Torres', 'unisex', 'Knit Textile, EVA Sole', 'Hand wash air dry.', '38,39,40,41,42,43,44', 'Black,Grey Marle', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?q=80&w=900&auto=format&fit=crop', NULL, 53, 1, 0, 0, 0, 0, NULL, NULL, 'active', 7, 0, '2026-08-15 23:05:42', '2026-08-15 23:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_reviews`
--

CREATE TABLE `customer_reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_type` enum('clothing','essentials') DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL COMMENT 'City shown on homepage testimonial, e.g. "Makati City"',
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review_title` varchar(150) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `is_homepage_testimonial` tinyint(1) NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `essentials_products`
--

CREATE TABLE `essentials_products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `sku` varchar(40) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `compare_at_price` decimal(10,2) DEFAULT NULL,
  `brand` varchar(80) DEFAULT 'El Grande De La Torres',
  `gender` enum('men','women','unisex') NOT NULL DEFAULT 'unisex',
  `material` varchar(150) DEFAULT NULL,
  `dimensions` varchar(150) DEFAULT NULL,
  `available_sizes` varchar(150) DEFAULT NULL,
  `available_colors` varchar(255) DEFAULT NULL,
  `primary_image` varchar(255) NOT NULL,
  `gallery_images` text DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `is_new_arrival` tinyint(1) NOT NULL DEFAULT 0,
  `is_best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `is_trending` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_limited_edition` tinyint(1) NOT NULL DEFAULT 0,
  `limited_edition_note` varchar(150) DEFAULT NULL,
  `season` varchar(40) DEFAULT NULL,
  `status` enum('active','draft','archived') NOT NULL DEFAULT 'active',
  `views_count` int(11) NOT NULL DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `essentials_products`
--

INSERT INTO `essentials_products` (`product_id`, `category_id`, `sku`, `name`, `slug`, `short_description`, `description`, `price`, `compare_at_price`, `brand`, `gender`, `material`, `dimensions`, `available_sizes`, `available_colors`, `primary_image`, `gallery_images`, `stock_quantity`, `is_new_arrival`, `is_best_seller`, `is_trending`, `is_featured`, `is_limited_edition`, `limited_edition_note`, `season`, `status`, `views_count`, `sales_count`, `created_at`, `updated_at`) VALUES
(1, 18, 'EGT-WT-001', 'Torres Automatic Watch', 'torres-automatic-watch', 'Swiss automatic movement, sapphire crystal.', 'A Swiss automatic movement housed in a brushed stainless case with sapphire crystal glass. The house dial marker replaces the twelve o\'clock numeral. A piece meant to be handed down.', 42000.00, 48000.00, 'El Grande De La Torres', 'unisex', 'Stainless Steel, Sapphire Crystal', '40mm case', NULL, 'Silver,Gold', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?q=80&w=900&auto=format&fit=crop', NULL, 15, 0, 1, 1, 1, 1, 'Only 100 pieces worldwide', NULL, 'active', 890, 22, '2026-06-10 09:00:00', '2026-08-01 09:00:00'),
(2, 14, 'EGT-NK-001', 'House Crest Pendant', 'house-crest-pendant', '18k gold-plated crest necklace.', 'An 18k gold-plated pendant bearing the house crest, suspended on a fine box-chain. Understated, but unmistakably the house.', 5200.00, NULL, 'El Grande De La Torres', 'unisex', '18k Gold-Plated Brass', '18-inch chain', NULL, 'Gold', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?q=80&w=900&auto=format&fit=crop', NULL, 50, 1, 0, 1, 1, 0, NULL, NULL, 'active', 312, 19, '2026-07-22 09:00:00', '2026-08-02 09:00:00'),
(3, 17, 'EGT-GL-001', 'Manila Sun Sunglasses', 'manila-sun-sunglasses', 'Acetate frame with polarized lenses.', 'Hand-polished Italian acetate frames fitted with polarized lenses, designed for all-day wear with a lightweight, balanced fit.', 6400.00, NULL, 'El Grande De La Torres', 'unisex', 'Italian Acetate, Polarized Lens', NULL, 'One Size', 'Tortoise,Black', 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?q=80&w=900&auto=format&fit=crop', NULL, 65, 0, 1, 0, 1, 0, NULL, NULL, 'active', 245, 38, '2026-05-14 09:00:00', '2026-06-30 09:00:00'),
(4, 16, 'EGT-HT-001', 'Wool Felt Fedora', 'wool-felt-fedora', 'Hand-blocked wool felt fedora.', 'A hand-blocked fedora in fine wool felt with a grosgrain ribbon band. Finished entirely by hand in the atelier.', 4800.00, NULL, 'El Grande De La Torres', 'unisex', '100% Wool Felt', NULL, 'S,M,L', 'Black,Camel', 'https://images.unsplash.com/photo-1521369909029-2afed882baee?q=80&w=900&auto=format&fit=crop', NULL, 35, 1, 0, 0, 0, 0, NULL, 'Autumn 2026', 'active', 90, 11, '2026-08-02 09:00:00', '2026-08-09 21:42:25'),
(5, 15, 'EGT-RG-001', 'Signet Crest Ring', 'signet-crest-ring', 'Sterling silver signet ring.', 'A sterling silver signet ring engraved with the house crest, cast and hand-finished by our jewellers.', 3400.00, NULL, 'El Grande De La Torres', 'men', 'Sterling Silver', NULL, '7,8,9,10,11,12', 'Silver', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=900&auto=format&fit=crop', NULL, 40, 0, 0, 0, 0, 1, 'Only 75 pieces worldwide', NULL, 'active', 176, 9, '2026-06-25 09:00:00', '2026-07-18 09:00:00'),
(6, 13, 'EGT-GR-001', 'Atelier Grooming Kit', 'atelier-grooming-kit', 'Travel grooming set in leather case.', 'A complete grooming set housed in a hand-stitched leather travel case — comb, nail set, and mirror, each finished to house standard.', 3900.00, NULL, 'El Grande De La Torres', 'men', 'Stainless Steel, Leather', '18cm case', NULL, 'Black,Brown', 'https://images.unsplash.com/photo-1621607512214-68297480165e?q=80&w=900&auto=format&fit=crop', NULL, 55, 0, 1, 0, 0, 0, NULL, NULL, 'active', 121, 26, '2026-04-30 09:00:00', '2026-06-10 09:00:00'),
(7, 11, 'EGT-TR-001', 'Weekender Leather Duffel', 'weekender-leather-duffel', 'Full-grain leather weekend bag.', 'A weekend duffel in full-grain leather with brass hardware and a detachable shoulder strap. Built for a lifetime of travel.', 12500.00, 14000.00, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', '50 x 28 x 24 cm', NULL, 'Cognac,Black', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=900&auto=format&fit=crop', NULL, 20, 1, 0, 1, 1, 0, NULL, NULL, 'active', 399, 17, '2026-07-30 09:00:00', '2026-08-09 20:41:35'),
(8, 10, 'EGT-EE-001', 'Everyday Leather Card Holder', 'everyday-leather-card-holder', 'Slim full-grain leather card holder.', 'A slim card holder in full-grain leather, hand-stitched with a subtle debossed crest. Made to fit six cards and fold flat in any pocket.', 1800.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', '10 x 7 cm', NULL, 'Black,Brown,Navy', 'https://images.unsplash.com/photo-1627123424574-724758594e93?q=80&w=900&auto=format&fit=crop', NULL, 100, 0, 1, 0, 0, 0, NULL, NULL, 'active', 267, 71, '2026-03-20 09:00:00', '2026-07-05 09:00:00'),
(9, 10, 'EGT-EE-002', 'Minimalist Money Clip', 'minimalist-money-clip', 'Brushed steel money clip.', 'A brushed stainless steel money clip with a debossed crest, slim enough to disappear into any pocket.', 1600.00, NULL, 'El Grande De La Torres', 'unisex', 'Stainless Steel', '7 x 2 cm', NULL, 'Silver,Gunmetal', 'https://images.unsplash.com/photo-1627123424574-724758594e93?q=80&w=900&auto=format&fit=crop', NULL, 90, 1, 0, 0, 0, 0, NULL, NULL, 'active', 22, 4, '2026-08-11 03:00:00', '2026-08-11 03:00:00'),
(10, 10, 'EGT-EE-003', 'Leather Keychain Fob', 'leather-keychain-fob', 'Hand-stitched leather key fob.', 'A hand-stitched leather key fob finished with a solid brass ring and debossed crest.', 1200.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather, Brass', '10 x 3 cm', NULL, 'Black,Brown,Tan', 'https://images.unsplash.com/photo-1611923134239-b9be5816e23f?q=80&w=900&auto=format&fit=crop', NULL, 110, 0, 1, 0, 0, 0, NULL, NULL, 'active', 41, 19, '2026-08-11 03:05:00', '2026-08-11 03:05:00'),
(11, 11, 'EGT-TR-002', 'Passport Holder Set', 'passport-holder-set', 'Leather passport holder with luggage tag.', 'A full-grain leather passport holder paired with a matching luggage tag, boxed as a travel-ready set.', 3200.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', '14.5 x 10 cm', NULL, 'Cognac,Black', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=900&auto=format&fit=crop', NULL, 48, 1, 0, 0, 0, 0, NULL, NULL, 'active', 36, 7, '2026-08-11 03:10:00', '2026-08-11 03:10:00'),
(12, 11, 'EGT-TR-003', 'Packable Garment Bag', 'packable-garment-bag', 'Foldable travel garment bag.', 'A foldable garment bag in water-resistant canvas with leather trim, keeping tailoring crease-free on the move.', 5800.00, NULL, 'El Grande De La Torres', 'unisex', 'Canvas, Leather Trim', '60 x 45 cm folded', NULL, 'Navy,Black', 'https://images.unsplash.com/photo-1524923524879-38d95278a3f1?q=80&w=900&auto=format&fit=crop', NULL, 30, 1, 0, 0, 0, 0, NULL, NULL, 'active', 28, 3, '2026-08-11 03:15:00', '2026-08-11 03:15:00'),
(13, 12, 'EGT-LS-001', 'Marble Desk Organizer', 'marble-desk-organizer', 'Hand-carved marble desk tray.', 'A hand-carved marble tray for the desk, finished with a brushed brass inlay bearing the house crest.', 4800.00, NULL, 'El Grande De La Torres', 'unisex', 'Marble, Brass Inlay', '24 x 14 x 3 cm', NULL, 'White,Black', 'https://images.unsplash.com/photo-1518051870910-a46e30d9db16?q=80&w=900&auto=format&fit=crop', NULL, 25, 1, 0, 0, 0, 0, NULL, NULL, 'active', 19, 2, '2026-08-11 03:20:00', '2026-08-11 03:20:00'),
(14, 12, 'EGT-LS-002', 'Leather Journal Cover', 'leather-journal-cover', 'Refillable leather journal cover.', 'A refillable journal cover in full-grain leather that softens and darkens beautifully with use.', 2600.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather', 'A5', NULL, 'Black,Brown,Olive', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?q=80&w=900&auto=format&fit=crop', NULL, 60, 0, 1, 0, 0, 0, NULL, NULL, 'active', 33, 12, '2026-08-11 03:25:00', '2026-08-11 03:25:00'),
(15, 12, 'EGT-LS-003', 'Crystal Whiskey Glass Set', 'crystal-whiskey-glass-set', 'Set of two hand-cut crystal glasses.', 'A set of two hand-cut crystal whiskey glasses, weighted for the hand and etched with the house crest.', 5400.00, NULL, 'El Grande De La Torres', 'unisex', 'Lead-Free Crystal', 'Set of 2', NULL, 'Clear', 'https://images.unsplash.com/photo-1470337458703-46ad1756a187?q=80&w=900&auto=format&fit=crop', NULL, 32, 1, 0, 0, 0, 0, NULL, NULL, 'active', 24, 5, '2026-08-11 03:30:00', '2026-08-11 03:30:00'),
(16, 13, 'EGT-GR-002', 'Sandalwood Shaving Kit', 'sandalwood-shaving-kit', 'Sandalwood-handled shaving set.', 'A sandalwood-handled shaving brush and safety razor set, boxed with a travel-ready canvas roll.', 4200.00, NULL, 'El Grande De La Torres', 'men', 'Sandalwood, Stainless Steel', '20cm roll', NULL, 'Natural', 'https://images.unsplash.com/photo-1621607512214-68297480165e?q=80&w=900&auto=format&fit=crop', NULL, 38, 0, 0, 0, 0, 0, NULL, NULL, 'active', 27, 6, '2026-08-11 03:35:00', '2026-08-11 03:35:00'),
(17, 13, 'EGT-GR-003', 'Travel Grooming Pouch', 'travel-grooming-pouch', 'Compact leather grooming pouch.', 'A compact leather pouch for the essentials — nail set, tweezers, and mirror — cut to slip into any bag.', 2200.00, NULL, 'El Grande De La Torres', 'unisex', 'Leather, Stainless Steel', '15cm pouch', NULL, 'Black,Tan', 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?q=80&w=900&auto=format&fit=crop', NULL, 52, 1, 0, 0, 0, 0, NULL, NULL, 'active', 18, 4, '2026-08-11 03:40:00', '2026-08-11 03:40:00'),
(18, 19, 'EGT-PF-001', 'Maison Oud Eau de Parfum', 'maison-oud-eau-de-parfum', 'Signature oud-and-amber fragrance, 50ml.', 'The house signature fragrance — oud, amber, and cedarwood layered over a warm musk base. 50ml.', 6200.00, NULL, 'El Grande De La Torres', 'unisex', 'Eau de Parfum, 50ml', NULL, NULL, NULL, 'https://images.unsplash.com/photo-1541643600914-78b084683601?q=80&w=900&auto=format&fit=crop', NULL, 45, 1, 1, 1, 1, 0, NULL, NULL, 'active', 210, 33, '2026-08-11 03:45:00', '2026-08-11 03:45:00'),
(19, 19, 'EGT-PF-002', 'Rose Noir Eau de Parfum', 'rose-noir-eau-de-parfum', 'Dark rose and black pepper, 50ml.', 'A dark rose accord layered with black pepper and patchouli — bold, modern, unmistakably house. 50ml.', 6400.00, NULL, 'El Grande De La Torres', 'women', 'Eau de Parfum, 50ml', NULL, NULL, NULL, 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?q=80&w=900&auto=format&fit=crop', NULL, 40, 1, 0, 0, 1, 0, NULL, NULL, 'active', 122, 15, '2026-08-11 03:50:00', '2026-08-11 03:50:00'),
(20, 19, 'EGT-PF-003', 'Vetiver Homme Cologne', 'vetiver-homme-cologne', 'Crisp vetiver and citrus, 50ml.', 'A crisp vetiver-and-citrus cologne with a dry woody finish, built for daily wear. 50ml.', 5600.00, NULL, 'El Grande De La Torres', 'men', 'Eau de Cologne, 50ml', NULL, NULL, NULL, 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?q=80&w=900&auto=format&fit=crop', NULL, 50, 0, 1, 0, 0, 0, NULL, NULL, 'active', 97, 21, '2026-08-11 03:55:00', '2026-08-11 03:55:00'),
(21, 14, 'EGT-NK-002', 'Layered Chain Necklace', 'layered-chain-necklace', 'Double-layer gold-plated chain.', 'A double-layer chain in 18k gold-plated brass, designed to be worn together or separately.', 4200.00, NULL, 'El Grande De La Torres', 'unisex', '18k Gold-Plated Brass', '16-20 inch adjustable', NULL, 'Gold', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=900&auto=format&fit=crop', NULL, 42, 1, 0, 0, 0, 0, NULL, NULL, 'active', 55, 9, '2026-08-11 04:00:00', '2026-08-11 04:00:00'),
(22, 14, 'EGT-NK-003', 'Pearl Drop Necklace', 'pearl-drop-necklace', 'Freshwater pearl pendant necklace.', 'A single freshwater pearl suspended on a delicate gold-filled chain — quiet, considered jewellery.', 3800.00, NULL, 'El Grande De La Torres', 'women', 'Freshwater Pearl, Gold-Filled Chain', '18-inch chain', NULL, 'Gold/White', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?q=80&w=900&auto=format&fit=crop', NULL, 36, 0, 1, 0, 0, 0, NULL, NULL, 'active', 63, 17, '2026-08-11 04:05:00', '2026-08-11 04:05:00'),
(23, 15, 'EGT-RG-002', 'Minimalist Band Ring', 'minimalist-band-ring', 'Slim sterling silver band.', 'A slim, hand-polished sterling silver band designed to be worn solo or stacked.', 2200.00, NULL, 'El Grande De La Torres', 'unisex', 'Sterling Silver', NULL, '6,7,8,9,10,11,12', 'Silver', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=900&auto=format&fit=crop', NULL, 55, 1, 0, 0, 0, 0, NULL, NULL, 'active', 29, 6, '2026-08-11 04:10:00', '2026-08-11 04:10:00'),
(24, 15, 'EGT-RG-003', 'Pearl Statement Ring', 'pearl-statement-ring', 'Freshwater pearl cocktail ring.', 'A freshwater pearl set in a sculptural gold-plated band — a statement piece for evenings out.', 3600.00, NULL, 'El Grande De La Torres', 'women', '18k Gold-Plated Brass, Pearl', NULL, '5,6,7,8,9', 'Gold', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=900&auto=format&fit=crop', NULL, 28, 0, 0, 1, 0, 0, NULL, NULL, 'active', 34, 5, '2026-08-11 04:15:00', '2026-08-11 04:15:00'),
(25, 20, 'EGT-ER-001', 'Gold Hoop Earrings', 'gold-hoop-earrings', '18k gold-plated hoop earrings.', 'Classic hoop earrings in 18k gold-plated brass, weighted to sit comfortably for all-day wear.', 2800.00, NULL, 'El Grande De La Torres', 'women', '18k Gold-Plated Brass', '3cm diameter', NULL, 'Gold', 'https://images.unsplash.com/photo-1630019852942-f89202989a59?q=80&w=900&auto=format&fit=crop', NULL, 60, 1, 1, 0, 0, 0, NULL, NULL, 'active', 88, 24, '2026-08-11 04:20:00', '2026-08-11 04:20:00'),
(26, 20, 'EGT-ER-002', 'Crest Stud Earrings', 'crest-stud-earrings', 'Sterling silver crest studs.', 'Sterling silver stud earrings engraved with the house crest — a minimal everyday piece.', 2400.00, NULL, 'El Grande De La Torres', 'unisex', 'Sterling Silver', '8mm', NULL, 'Silver', 'https://images.unsplash.com/photo-1589207212797-cfd41b820a94?q=80&w=900&auto=format&fit=crop', NULL, 65, 1, 0, 0, 0, 0, NULL, NULL, 'active', 41, 11, '2026-08-11 04:25:00', '2026-08-11 04:25:00'),
(27, 20, 'EGT-ER-003', 'Drop Pearl Earrings', 'drop-pearl-earrings', 'Freshwater pearl drop earrings.', 'Freshwater pearl drops on gold-filled ear wires — timeless, refined, and easy to wear daily.', 3400.00, NULL, 'El Grande De La Torres', 'women', 'Freshwater Pearl, Gold-Filled', '3cm drop', NULL, 'Gold/White', 'https://images.unsplash.com/photo-1611085583191-a3b181a88401?q=80&w=900&auto=format&fit=crop', NULL, 30, 0, 0, 0, 0, 0, NULL, NULL, 'active', 26, 4, '2026-08-11 04:30:00', '2026-08-11 04:30:00'),
(28, 16, 'EGT-HT-002', 'Structured Baseball Cap', 'structured-baseball-cap', 'Cotton twill cap with embroidered crest.', 'A structured six-panel cap in cotton twill with a subtly embroidered crest at the front.', 2200.00, NULL, 'El Grande De La Torres', 'unisex', 'Cotton Twill', 'One Size Adjustable', NULL, 'Black,Navy,Stone', 'https://images.unsplash.com/photo-1521369909029-2afed882baee?q=80&w=900&auto=format&fit=crop', NULL, 75, 1, 0, 0, 0, 0, NULL, NULL, 'active', 48, 14, '2026-08-11 04:35:00', '2026-08-11 04:35:00'),
(29, 16, 'EGT-HT-003', 'Wide Brim Sun Hat', 'wide-brim-sun-hat', 'Woven straw sun hat.', 'A woven straw hat with a wide brim and grosgrain band, made for long summer afternoons.', 3600.00, NULL, 'El Grande De La Torres', 'women', 'Woven Straw', 'One Size', NULL, 'Natural', 'https://images.unsplash.com/photo-1521369909029-2afed882baee?q=80&w=900&auto=format&fit=crop', NULL, 30, 1, 0, 0, 0, 0, NULL, NULL, 'active', 22, 3, '2026-08-11 04:40:00', '2026-08-11 04:40:00'),
(30, 17, 'EGT-GL-002', 'Aviator Sunglasses', 'aviator-sunglasses', 'Metal-frame aviators, polarized.', 'Classic metal-frame aviators with polarized lenses and a slim double-bridge.', 5800.00, NULL, 'El Grande De La Torres', 'unisex', 'Metal Frame, Polarized Lens', NULL, 'One Size', 'Gold/Green,Silver/Grey', 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?q=80&w=900&auto=format&fit=crop', NULL, 50, 0, 1, 0, 0, 0, NULL, NULL, 'active', 91, 26, '2026-08-11 04:45:00', '2026-08-11 04:45:00'),
(31, 17, 'EGT-GL-003', 'Round Acetate Sunglasses', 'round-acetate-sunglasses', 'Round acetate frames, UV400.', 'Round acetate sunglasses with a soft-touch finish and UV400 protection — an easy everyday shape.', 6100.00, NULL, 'El Grande De La Torres', 'women', 'Italian Acetate', NULL, 'One Size', 'Tortoise,Black', 'https://images.unsplash.com/photo-1577803645773-f96470509666?q=80&w=900&auto=format&fit=crop', NULL, 44, 1, 0, 0, 0, 0, NULL, NULL, 'active', 37, 9, '2026-08-11 04:50:00', '2026-08-11 04:50:00'),
(32, 18, 'EGT-WT-002', 'Minimalist Leather Strap Watch', 'minimalist-leather-strap-watch', 'Slim quartz watch, leather strap.', 'A slim quartz watch with a sunburst dial and genuine leather strap — understated everyday timekeeping.', 8800.00, NULL, 'El Grande De La Torres', 'unisex', 'Stainless Steel, Leather Strap', '36mm case', NULL, 'Black,Brown', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?q=80&w=900&auto=format&fit=crop', NULL, 40, 1, 0, 0, 0, 0, NULL, NULL, 'active', 66, 12, '2026-08-11 04:55:00', '2026-08-11 04:55:00'),
(33, 18, 'EGT-WT-003', 'Chronograph Steel Watch', 'chronograph-steel-watch', 'Stainless chronograph, sapphire crystal.', 'A stainless steel chronograph with sapphire crystal glass and a brushed link bracelet.', 24500.00, NULL, 'El Grande De La Torres', 'men', 'Stainless Steel, Sapphire Crystal', '42mm case', NULL, 'Silver,Black', 'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?q=80&w=900&auto=format&fit=crop', NULL, 20, 0, 1, 1, 0, 0, NULL, NULL, 'active', 145, 20, '2026-08-11 05:00:00', '2026-08-11 05:00:00'),
(34, 14, 'EGT-LE-101', 'Torres Diamond Pendant — Limited', 'torres-diamond-pendant-limited', 'Hand-set diamond pendant, limited run.', 'A hand-set diamond pendant on an 18k gold chain, produced in a strictly limited run and individually numbered.', 28000.00, 34000.00, 'El Grande De La Torres', 'women', '18k Gold, Diamond', '18-inch chain', NULL, 'Gold', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?q=80&w=900&auto=format&fit=crop', NULL, 8, 0, 0, 0, 1, 1, 'Only 25 pieces worldwide', NULL, 'active', 176, 5, '2026-08-11 05:05:00', '2026-08-11 05:05:00'),
(35, 18, 'EGT-LE-102', 'Gold Automatic Limited Watch', 'gold-automatic-limited-watch', 'Gold-cased automatic, limited run.', 'A gold-cased automatic movement watch with a sapphire display back, individually numbered from a strictly limited run.', 65000.00, 78000.00, 'El Grande De La Torres', 'men', '18k Gold-Plated Case, Sapphire Crystal', '40mm case', NULL, 'Gold', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?q=80&w=900&auto=format&fit=crop', NULL, 6, 0, 0, 0, 1, 1, 'Only 20 pieces worldwide', NULL, 'active', 203, 4, '2026-08-11 05:10:00', '2026-08-11 05:10:00'),
(36, 19, 'EGT-LE-103', 'Oud Royale Limited Perfume', 'oud-royale-limited-perfume', 'Rare oud blend, limited edition flacon, 100ml.', 'A rare, aged oud blend housed in a limited-edition engraved crystal flacon. 100ml, individually numbered.', 12500.00, 15000.00, 'El Grande De La Torres', 'unisex', 'Eau de Parfum, 100ml', NULL, NULL, NULL, 'https://images.unsplash.com/photo-1541643600914-78b084683601?q=80&w=900&auto=format&fit=crop', NULL, 18, 0, 0, 0, 1, 1, 'Only 150 flacons worldwide', NULL, 'active', 250, 9, '2026-08-11 05:15:00', '2026-08-15 07:07:01'),
(37, 18, 'RLX-PN-004', 'Rolex Cosmograph Daytona Paul Newman', 'rolex-cosmograph-daytona-paul-newman', 'Iconic vintage-inspired Rolex Daytona with a legendary Paul Newman dial.', 'A distinguished Rolex Cosmograph Daytona inspired by the legendary Paul Newman reference. Featuring a refined stainless steel case, sapphire crystal, and an iconic chronograph dial, this timepiece represents precision, heritage, and timeless craftsmanship. A collector-worthy piece designed to endure across generations.', 168000.00, 182000.00, 'Rolex', 'unisex', 'Stainless Steel, Sapphire Crystal', '40mm case', NULL, 'Silver,Black', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?q=80&w=900&auto=format&fit=crop', NULL, 14, 0, 1, 1, 1, 1, 'Limited collector edition', NULL, 'active', 891, 23, '2026-06-10 01:00:00', '2026-08-27 01:41:06'),
(38, 10, 'EGT-EE-004', 'Leather Belt', 'leather-belt', 'Full-grain leather belt, brass buckle.', 'A full-grain leather belt with a brushed brass buckle and debossed crest keeper — the kind of basic that never goes out of rotation.', 2400.00, NULL, 'El Grande De La Torres', 'unisex', 'Full-Grain Leather, Brass', '3.5cm width', '30,32,34,36,38', 'Black,Brown', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?q=80&w=900&auto=format&fit=crop', NULL, 70, 0, 1, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:00:00', '2026-08-18 01:00:00'),
(39, 11, 'EGT-TR-004', 'Toiletry Dopp Kit', 'toiletry-dopp-kit', 'Water-resistant travel toiletry case.', 'A compact, water-resistant dopp kit in waxed canvas with a leather-trimmed zip — built to keep the essentials organized on the road.', 2900.00, NULL, 'El Grande De La Torres', 'unisex', 'Waxed Canvas, Leather Trim', '22 x 12 cm', NULL, 'Olive,Black', 'https://images.unsplash.com/photo-1591561954557-26941169b49e?q=80&w=900&auto=format&fit=crop', NULL, 44, 1, 0, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:05:00', '2026-08-18 01:05:00'),
(40, 12, 'EGT-LS-004', 'Scented Candle Trio', 'scented-candle-trio', 'Set of three signature-scent candles.', 'Three hand-poured soy candles in the house signature scents — oud, cedar, and amber — boxed as a set.', 3200.00, NULL, 'El Grande De La Torres', 'unisex', 'Soy Wax, Glass Vessel', 'Set of 3, 120g each', NULL, 'Natural', 'https://images.unsplash.com/photo-1602874801007-bd458bb1b8b6?q=80&w=900&auto=format&fit=crop', NULL, 36, 1, 0, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:10:00', '2026-08-18 01:10:00'),
(41, 13, 'EGT-GR-004', 'Beard Oil &amp; Comb Set', 'beard-oil-comb-set', 'Grooming set with sandalwood comb.', 'A grooming duo — cedar-and-sandalwood beard oil paired with a hand-finished sandalwood comb, boxed together.', 2100.00, NULL, 'El Grande De La Torres', 'men', 'Natural Oils, Sandalwood', '30ml oil + comb', NULL, 'Natural', 'https://images.unsplash.com/photo-1621607512214-68297480165e?q=80&w=900&auto=format&fit=crop', NULL, 48, 0, 1, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:15:00', '2026-08-18 01:15:00'),
(42, 15, 'EGT-RG-004', 'Onyx Signet Ring', 'onyx-signet-ring', 'Sterling silver ring, black onyx face.', 'A sterling silver signet ring set with a polished black onyx face — a quieter alternative to the engraved crest signet.', 3900.00, NULL, 'El Grande De La Torres', 'men', 'Sterling Silver, Onyx', NULL, '7,8,9,10,11,12', 'Silver/Black', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=900&auto=format&fit=crop', NULL, 26, 0, 0, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:20:00', '2026-08-18 01:20:00'),
(43, 16, 'EGT-HT-004', 'Cashmere Beanie', 'cashmere-beanie', 'Ribbed cashmere beanie.', 'A ribbed-knit beanie in pure cashmere, finished with a folded cuff and a subtly woven crest tag.', 2600.00, NULL, 'El Grande De La Torres', 'unisex', '100% Cashmere', 'One Size', NULL, 'Black,Camel,Grey', 'https://images.unsplash.com/photo-1576871337622-98d48d1cf531?q=80&w=900&auto=format&fit=crop', NULL, 55, 1, 0, 0, 0, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:25:00', '2026-08-18 01:25:00'),
(44, 18, 'RLX-SUB-005', 'Rolex Submariner Date', 'rolex-submariner-date', 'The archetypal dive watch, Oystersteel.', 'The archetypal dive watch. Oystersteel case, unidirectional rotatable bezel with a Cerachrom insert, and water resistance to 300m — built for the depths and the boardroom alike.', 88000.00, 95000.00, 'Rolex', 'men', 'Oystersteel, Sapphire Crystal', '41mm case', NULL, 'Black,Green', 'https://images.unsplash.com/photo-1547996160-81dfa63595aa?q=80&w=900&auto=format&fit=crop', NULL, 10, 1, 1, 1, 1, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:30:00', '2026-08-18 01:30:00'),
(45, 18, 'RLX-GMT-006', 'Rolex GMT-Master II \"Batman\"', 'rolex-gmt-master-ii-batman', 'Dual time-zone icon, blue-and-black bezel.', 'A dual time-zone icon built for travelers, distinguished by its two-tone blue-and-black Cerachrom bezel — nicknamed \"Batman\" for the striking contrast.', 95000.00, 102000.00, 'Rolex', 'men', 'Oystersteel, Sapphire Crystal', '40mm case', NULL, 'Black/Blue', 'https://images.unsplash.com/photo-1533139502658-0198f920d8e8?q=80&w=900&auto=format&fit=crop', NULL, 8, 0, 1, 1, 1, 0, NULL, NULL, 'active', 0, 0, '2026-08-18 01:35:00', '2026-08-18 01:35:00'),
(46, 18, 'RLX-DJ-007', 'Rolex Datejust 41', 'rolex-datejust-41', 'The definitive classic, fluted bezel.', 'The watch that defined the modern wristwatch silhouette — a fluted bezel, the signature Cyclops date lens, and a jubilee bracelet in polished Oystersteel.', 68000.00, NULL, 'Rolex', 'unisex', 'Oystersteel, Sapphire Crystal', '41mm case', NULL, 'Silver,Blue Dial', 'https://images.unsplash.com/photo-1587836374828-4dbafa94cf0e?q=80&w=900&auto=format&fit=crop', NULL, 14, 1, 0, 0, 1, 0, NULL, NULL, 'active', 1, 0, '2026-08-18 01:40:00', '2026-08-18 13:35:37'),
(47, 18, 'RLX-DD-008', 'Rolex Day-Date \"President\"', 'rolex-day-date-president', '18k gold, day and date on the dial.', 'Reserved for an 18k gold or platinum case, the Day-Date spells out the day of the week in full on the dial — a piece long associated with heads of state.', 145000.00, 155000.00, 'Rolex', 'men', '18k Gold, Sapphire Crystal', '40mm case', NULL, 'Gold', 'https://images.unsplash.com/photo-1526045431048-f857369baa09?q=80&w=900&auto=format&fit=crop', NULL, 4, 0, 0, 0, 1, 1, 'Only 15 pieces available', NULL, 'active', 1, 1, '2026-08-18 01:45:00', '2026-08-18 13:37:21'),
(48, 18, 'RLX-SD-009', 'Rolex Sky-Dweller', 'rolex-sky-dweller', 'Annual calendar with dual time-zone.', 'One of Rolex\'s most complex movements — an annual calendar that tracks the date across months of different lengths, paired with a dual time-zone display via the rotatable Ring Command bezel.', 132000.00, NULL, 'Rolex', 'men', 'Everose Gold, Oystersteel', '42mm case', NULL, 'Rose Gold/Black', 'https://images.unsplash.com/photo-1509048191080-d2984bad6ae5?q=80&w=900&auto=format&fit=crop', NULL, 6, 1, 0, 0, 1, 0, NULL, NULL, 'active', 1, 0, '2026-08-18 01:50:00', '2026-08-21 22:21:13');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(120) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` enum('order','promo','system') NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'Welcome to El Grande De La Torres', 'Your account has been created. Explore the house at your leisure.', 'system', 1, '2026-08-08 01:28:08'),
(2, 1, 'Order Placed', 'Your order EGDLT-A60A7C3F has been received and is being processed.', 'order', 1, '2026-08-10 06:58:00'),
(3, 2, 'Welcome to El Grande De La Torres', 'Your account has been created. Explore the house at your leisure.', 'system', 1, '2026-08-18 13:36:35'),
(4, 2, 'Order Placed', 'Your order EGDLT-93E106DB has been received and is being processed.', 'order', 1, '2026-08-18 13:37:21'),
(5, 1, 'Order Placed', 'Your order EGDLT-3D50B64E has been received and is being processed.', 'order', 1, '2026-08-22 23:56:45'),
(6, 3, 'Welcome to El Grande De La Torres', 'Your account has been created. Explore the house at your leisure.', 'system', 0, '2026-08-27 01:35:11'),
(7, 3, 'Order Placed', 'Your order EGDLT-001BCC61 has been received and is being processed.', 'order', 0, '2026-08-27 01:38:31'),
(8, 3, 'Order Placed', 'Your order EGDLT-BCE7D187 has been received and is being processed.', 'order', 0, '2026-08-27 01:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(40) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','gcash','maya') NOT NULL,
  `payment_reference` varchar(80) DEFAULT NULL COMMENT 'GCash/Maya reference number, if provided',
  `order_status` enum('pending','processing','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
  `notes` varchar(255) DEFAULT NULL,
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Order date/time — printed on receipt',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `user_id`, `address_id`, `subtotal`, `shipping_fee`, `discount_amount`, `coupon_code`, `total_amount`, `payment_method`, `payment_reference`, `order_status`, `notes`, `placed_at`, `updated_at`) VALUES
(1, 'EGDLT-A60A7C3F', 1, 1, 2100.00, 250.00, 0.00, NULL, 2350.00, 'gcash', '09910654627', 'pending', NULL, '2026-08-10 06:58:00', '2026-08-10 06:58:00'),
(2, 'EGDLT-93E106DB', 2, 2, 145000.00, 0.00, 0.00, NULL, 145000.00, 'cod', NULL, 'pending', NULL, '2026-08-18 13:37:21', '2026-08-18 13:37:21'),
(3, 'EGDLT-3D50B64E', 1, 3, 2350.00, 250.00, 0.00, NULL, 2600.00, 'cod', NULL, 'pending', NULL, '2026-08-22 23:56:45', '2026-08-22 23:56:45'),
(4, 'EGDLT-001BCC61', 3, 4, 24000.00, 0.00, 0.00, NULL, 24000.00, 'cod', NULL, 'pending', NULL, '2026-08-27 01:38:31', '2026-08-27 01:38:31'),
(5, 'EGDLT-BCE7D187', 3, 5, 168000.00, 0.00, 0.00, NULL, 168000.00, 'cod', NULL, 'pending', NULL, '2026-08-27 01:41:06', '2026-08-27 01:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_type` enum('clothing','essentials') NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(40) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_type`, `product_id`, `product_name`, `product_image`, `size`, `color`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 'clothing', 12, 'Essential Long Sleeve Tee', 'https://images.unsplash.com/photo-1571945153237-4929e783af4a?q=80&w=900&auto=format&fit=crop', 'M', 'White', 1, 2100.00, 2100.00),
(2, 2, 'essentials', 47, 'Rolex Day-Date \"President\"', 'https://images.unsplash.com/photo-1526045431048-f857369baa09?q=80&w=900&auto=format&fit=crop', NULL, 'Gold', 1, 145000.00, 145000.00),
(3, 3, 'clothing', 36, 'Ribbed Muscle Tee', 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?q=80&w=900&auto=format&fit=crop', 'XS', 'Black', 1, 2350.00, 2350.00),
(4, 4, 'clothing', 35, 'House Crest Bomber — Limited', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=900&auto=format&fit=crop', 'L', 'Charcoal', 1, 12000.00, 12000.00),
(5, 4, 'clothing', 35, 'House Crest Bomber — Limited', 'https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=900&auto=format&fit=crop', 'S', 'Charcoal', 1, 12000.00, 12000.00),
(6, 5, 'essentials', 37, 'Rolex Cosmograph Daytona Paul Newman', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?q=80&w=900&auto=format&fit=crop', NULL, 'Black', 1, 168000.00, 168000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cod','gcash','maya') NOT NULL,
  `reference_number` varchar(80) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `reference_number`, `amount`, `payment_status`, `paid_at`, `created_at`) VALUES
(1, 1, 'gcash', '09910654627', 2350.00, 'paid', '2026-08-10 08:58:00', '2026-08-10 06:58:00'),
(2, 2, 'cod', NULL, 145000.00, 'pending', NULL, '2026-08-18 13:37:21'),
(3, 3, 'cod', NULL, 2600.00, 'pending', NULL, '2026-08-22 23:56:45'),
(4, 4, 'cod', NULL, 24000.00, 'pending', NULL, '2026-08-27 01:38:31'),
(5, 5, 'cod', NULL, 168000.00, 'pending', NULL, '2026-08-27 01:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_addresses`
--

CREATE TABLE `shipping_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(40) DEFAULT 'Home',
  `recipient_name` varchar(120) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address_line1` varchar(200) NOT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(80) NOT NULL,
  `province` varchar(80) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(80) NOT NULL DEFAULT 'Philippines',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_addresses`
--

INSERT INTO `shipping_addresses` (`address_id`, `user_id`, `label`, `recipient_name`, `phone_number`, `address_line1`, `address_line2`, `city`, `province`, `postal_code`, `country`, `is_default`, `created_at`) VALUES
(1, 1, 'Home', 'Jannel Torres', '09910654627', 'San Lucas 1, San Pablo City, Laguna', 'San Lucas 1', 'San Pablo City', 'Laguna', '4000', 'Philippines', 1, '2026-08-10 06:58:00'),
(2, 2, 'Home', 'Xia Bella Guevara', '09910654627', 'San Lucas 1, San Pablo City, Laguna', 'San Lucas 1', 'San Pablo City', 'Laguna', '4000', 'Philippines', 1, '2026-08-18 13:37:21'),
(3, 1, 'Home', 'Jannel Torres', '09910654627', 'San Lucas 1, San Pablo City, Laguna', 'San Lucas 1', 'San Pablo City', 'Laguna', '4000', 'Philippines', 1, '2026-08-22 23:56:45'),
(4, 3, 'Home', 'jannel guevara', '0913166474', 'nha street', 'barangay 3-c', 'San Pablo City', 'Laguna', '1242', 'Philippines', 1, '2026-08-27 01:38:31'),
(5, 3, 'Home', 'jannel guevara', '0913166474', 'bahay ni doki supot tite', 'San Lucas 1', 'San Pablo City', 'Laguna', '4000', 'Philippines', 1, '2026-08-27 01:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `username`, `email`, `phone_number`, `password_hash`, `remember_token`, `reset_token`, `reset_token_expires`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Jannel', 'Torres', 'jannel.torres', 'janneltorres@gmail.com', '09910654627', '$2y$10$hdxD25WVB9hOJNAGfJ7xKu0d47ocw3bPL0UMpKt9oDEkxp4NaSv1u', NULL, NULL, NULL, NULL, 'active', '2026-08-08 01:28:08', '2026-08-08 01:28:08'),
(2, 'Prince Edward', 'Guevara', 'princeedward.guevara', 'guevaraprinceedward@gmail.com', '09910654627', '$2y$10$cl1sjJtPFuFa5Zm58HNQgOIHic4WoUEs7UutHBjA0YxyeGf8e24vm', NULL, NULL, NULL, NULL, 'active', '2026-08-18 13:36:35', '2026-08-18 13:36:35'),
(3, 'jannel', 'guevara', 'doki', 'shenshenwkwk@gmaill.com', '0913166474', '$2y$10$TidNmUvg1VXagG2Zdzubk.sReuYFcsc5UTEL4hq2XZ3ogd3Fo8F46', NULL, NULL, NULL, NULL, 'active', '2026-08-27 01:35:11', '2026-08-27 01:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_type` enum('clothing','essentials') NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_testimonials`
--
ALTER TABLE `about_testimonials`
  ADD PRIMARY KEY (`testimonial_id`),
  ADD KEY `idx_about_testi_active` (`is_active`,`row_group`,`display_order`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `uniq_user_cart` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `idx_cart_items_cart` (`cart_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `clothing_products`
--
ALTER TABLE `clothing_products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_clothing_category` (`category_id`),
  ADD KEY `idx_clothing_status` (`status`),
  ADD KEY `idx_clothing_gender` (`gender`);
ALTER TABLE `clothing_products` ADD FULLTEXT KEY `ft_clothing_search` (`name`,`short_description`,`description`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `customer_reviews`
--
ALTER TABLE `customer_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_reviews_product` (`product_type`,`product_id`),
  ADD KEY `idx_reviews_homepage` (`is_homepage_testimonial`,`is_approved`);

--
-- Indexes for table `essentials_products`
--
ALTER TABLE `essentials_products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_essentials_category` (`category_id`),
  ADD KEY `idx_essentials_status` (`status`),
  ADD KEY `idx_essentials_gender` (`gender`);
ALTER TABLE `essentials_products` ADD FULLTEXT KEY `ft_essentials_search` (`name`,`short_description`,`description`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`subscriber_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `uniq_wishlist_item` (`user_id`,`product_type`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_testimonials`
--
ALTER TABLE `about_testimonials`
  MODIFY `testimonial_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `clothing_products`
--
ALTER TABLE `clothing_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_reviews`
--
ALTER TABLE `customer_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `essentials_products`
--
ALTER TABLE `essentials_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE;

--
-- Constraints for table `clothing_products`
--
ALTER TABLE `clothing_products`
  ADD CONSTRAINT `clothing_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `customer_reviews`
--
ALTER TABLE `customer_reviews`
  ADD CONSTRAINT `customer_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_reviews_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

--
-- Constraints for table `essentials_products`
--
ALTER TABLE `essentials_products`
  ADD CONSTRAINT `essentials_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `shipping_addresses` (`address_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD CONSTRAINT `shipping_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
