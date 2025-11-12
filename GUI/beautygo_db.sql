-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 08:09 AM
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
-- Database: `beautygo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `album_id` varchar(50) NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `image_1` varchar(500) DEFAULT NULL,
  `image_2` varchar(500) DEFAULT NULL,
  `image_3` varchar(500) DEFAULT NULL,
  `image_4` varchar(500) DEFAULT NULL,
  `image_5` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `albums`
--

INSERT INTO `albums` (`album_id`, `business_id`, `image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `created_at`, `updated_at`) VALUES
('album_biz_001', 'biz_001', 'https://images.unsplash.com/photo-1560066984-138dadb4c035', 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e', 'https://images.unsplash.com/photo-1562322140-8baeececf3df', NULL, NULL, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('album_biz_002', 'biz_002', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874', 'https://images.unsplash.com/photo-1540555700478-4be289fbecef', 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d', NULL, NULL, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('album_biz_003', 'biz_003', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1', 'https://images.unsplash.com/photo-1621605815971-fbc98d665033', 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70', NULL, NULL, '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` varchar(50) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `service_id` varchar(50) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `appointment_datetime` datetime NOT NULL COMMENT 'Combined date and time of appointment',
  `status` varchar(20) DEFAULT 'pending' COMMENT 'pending, confirmed, cancelled, completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `business_id`, `service_id`, `employee_id`, `appointment_datetime`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('apt_001', 'cust_001', 'biz_001', 'svc_002', 'emp_001', '2025-01-20 10:00:00', 'confirmed', 'Client prefers warm tones', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('apt_002', 'cust_002', 'biz_003', 'svc_014', 'emp_005', '2025-01-18 14:30:00', 'pending', 'First time customer', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('apt_003', 'cust_003', 'biz_002', 'svc_007', 'emp_003', '2025-01-19 11:00:00', 'confirmed', 'Lower back pain', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('apt_004', 'cust_001', 'biz_002', 'svc_011', 'emp_004', '2025-01-22 15:00:00', 'pending', NULL, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('apt_005', 'cust_002', 'biz_001', 'svc_001', 'emp_002', '2025-01-21 09:00:00', 'confirmed', NULL, '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `business_id` varchar(50) NOT NULL,
  `business_email` varchar(255) NOT NULL,
  `business_password` varchar(255) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_type` varchar(100) DEFAULT NULL COMMENT 'salon, spa, barbershop, etc.',
  `business_desc` text DEFAULT NULL,
  `business_services` text DEFAULT NULL COMMENT 'Comma-separated list of main services',
  `business_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `location` point NOT NULL COMMENT 'Geographic coordinates (longitude, latitude)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`business_id`, `business_email`, `business_password`, `business_name`, `business_type`, `business_desc`, `business_services`, `business_address`, `city`, `location`, `created_at`, `updated_at`) VALUES
('biz_001', 'glam.salon@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Glam Beauty Salon', 'salon', 'Premier beauty salon offering hair, nails, and makeup services in the heart of Nasugbu.', 'Hair Styling, Hair Coloring, Manicure, Pedicure, Makeup', 'Brgy. 1, Nasugbu', 'Nasugbu', 0x0000000001010000002a3a92cb7f285e40f90fe9b7af232c40, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('biz_002', 'tranquil.spa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tranquil Day Spa', 'spa', 'Relaxing spa treatments including massage, facials, and body treatments.', 'Swedish Massage, Hot Stone Massage, Facial Treatment, Body Scrub', 'Brgy. Calayo, Nasugbu', 'Nasugbu', 0x000000000101000000713d0ad7a3285e40be9f1a2fdd242c40, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('biz_003', 'classic.barber@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Classic Cuts Barbershop', 'barbershop', 'Traditional barbershop specializing in men\'s haircuts and grooming.', 'Haircut, Beard Trim, Hot Towel Shave, Hair Treatment', 'Brgy. Bucana, Nasugbu', 'Nasugbu', 0x000000000101000000b81e85eb51285e40894160e5d0222c40, '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `celler_num` varchar(20) DEFAULT NULL,
  `celler_email` varchar(255) DEFAULT NULL,
  `face_shape` varchar(50) DEFAULT NULL,
  `skin_tone` varchar(50) DEFAULT NULL,
  `body_mass` varchar(50) DEFAULT NULL,
  `hair_type` varchar(50) DEFAULT NULL,
  `hair_color` varchar(50) DEFAULT NULL,
  `total_length` varchar(50) DEFAULT NULL COMMENT 'Desired hair length',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `email`, `password`, `name`, `surname`, `celler_num`, `celler_email`, `face_shape`, `skin_tone`, `body_mass`, `hair_type`, `hair_color`, `total_length`, `created_at`, `updated_at`) VALUES
('cust_001', 'maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', '09171234567', 'maria.santos@email.com', 'oval', 'medium', 'average', 'straight', 'black', 'medium', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('cust_002', 'juan.delacruz@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Dela Cruz', '09181234567', 'juan.delacruz@email.com', 'square', 'tan', 'athletic', 'wavy', 'brown', 'short', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('cust_003', 'ana.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana', 'Reyes', '09191234567', 'ana.reyes@email.com', 'heart', 'fair', 'slim', 'curly', 'brown', 'long', '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` varchar(50) NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `photo` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `business_id`, `employee_name`, `specialization`, `photo`, `bio`, `experience_years`, `created_at`, `updated_at`) VALUES
('emp_001', 'biz_001', 'Elena Cruz', 'Hair Coloring Specialist', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330', NULL, 8, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('emp_002', 'biz_001', 'Rosa Martinez', 'Bridal Makeup Artist', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80', NULL, 5, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('emp_003', 'biz_002', 'Carmen Lopez', 'Licensed Massage Therapist', 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91', NULL, 10, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('emp_004', 'biz_002', 'Sofia Ramos', 'Facial Specialist', 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e', NULL, 7, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('emp_005', 'biz_003', 'Miguel Santos', 'Master Barber', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e', NULL, 12, '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('emp_006', 'biz_003', 'Diego Fernandez', 'Barber & Grooming Expert', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d', NULL, 6, '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` varchar(50) NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `business_id`, `customer_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
('rev_001', 'biz_001', 'cust_001', 5, 'Amazing service! Elena is a true artist with hair coloring.', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('rev_002', 'biz_001', 'cust_003', 5, 'Beautiful salon and very professional staff. Highly recommend!', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('rev_003', 'biz_002', 'cust_002', 4, 'Very relaxing massage. The ambiance could be improved but overall great experience.', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('rev_004', 'biz_002', 'cust_001', 5, 'Best spa in Nasugbu! Carmen is excellent at deep tissue massage.', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('rev_005', 'biz_003', 'cust_002', 5, 'Miguel gave me the best haircut I\'ve had in years. Will definitely return!', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('rev_006', 'biz_003', 'cust_003', 4, 'Clean shop, friendly staff, good value for money.', '2025-10-26 07:09:14', '2025-10-26 07:09:14');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` varchar(50) NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL COMMENT 'Price in PHP',
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `business_id`, `service_name`, `description`, `cost`, `duration`, `category`, `created_at`, `updated_at`) VALUES
('svc_001', 'biz_001', 'Women\'s Haircut & Style', 'Professional haircut with styling', 500.00, 60, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_002', 'biz_001', 'Hair Color (Full)', 'Complete hair coloring service', 2500.00, 180, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_003', 'biz_001', 'Balayage/Highlights', 'Premium balayage or highlights', 3500.00, 240, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_004', 'biz_001', 'Manicure', 'Basic manicure with polish', 300.00, 45, 'Nails', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_005', 'biz_001', 'Pedicure', 'Relaxing pedicure with polish', 400.00, 60, 'Nails', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_006', 'biz_001', 'Bridal Makeup', 'Complete bridal makeup package', 5000.00, 120, 'Makeup', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_007', 'biz_002', 'Swedish Massage (60 min)', 'Relaxing full body massage', 1200.00, 60, 'Massage', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_008', 'biz_002', 'Swedish Massage (90 min)', 'Extended relaxation massage', 1800.00, 90, 'Massage', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_009', 'biz_002', 'Hot Stone Massage', 'Therapeutic hot stone treatment', 2000.00, 90, 'Massage', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_010', 'biz_002', 'Deep Tissue Massage', 'Intensive muscle therapy', 1500.00, 60, 'Massage', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_011', 'biz_002', 'Classic Facial', 'Cleansing and rejuvenating facial', 1000.00, 60, 'Facial', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_012', 'biz_002', 'Body Scrub', 'Exfoliating body treatment', 1500.00, 75, 'Body', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_013', 'biz_003', 'Men\'s Haircut', 'Classic men\'s haircut', 200.00, 30, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_014', 'biz_003', 'Haircut & Beard Trim', 'Haircut with beard grooming', 300.00, 45, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_015', 'biz_003', 'Hot Towel Shave', 'Traditional straight razor shave', 250.00, 30, 'Grooming', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_016', 'biz_003', 'Hair & Beard Color', 'Hair and beard coloring', 800.00, 60, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_017', 'biz_003', 'Scalp Treatment', 'Deep cleansing scalp treatment', 400.00, 45, 'Treatment', '2025-10-26 07:09:14', '2025-10-26 07:09:14'),
('svc_018', 'biz_003', 'Kids Haircut', 'Haircut for children (12 and under)', 150.00, 20, 'Hair', '2025-10-26 07:09:14', '2025-10-26 07:09:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`album_id`),
  ADD UNIQUE KEY `unique_business_album` (`business_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_appointment_datetime` (`appointment_datetime`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`business_id`),
  ADD UNIQUE KEY `business_email` (`business_email`),
  ADD SPATIAL KEY `location` (`location`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
