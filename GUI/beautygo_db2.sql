-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 05:05 AM
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
-- Database: `beautygo_db2`
--

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `album_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `logo` longblob DEFAULT NULL,
  `image1` longblob DEFAULT NULL,
  `image2` longblob DEFAULT NULL,
  `image3` longblob DEFAULT NULL,
  `image4` longblob DEFAULT NULL,
  `image5` longblob DEFAULT NULL,
  `image6` longblob DEFAULT NULL,
  `image7` longblob DEFAULT NULL,
  `image8` longblob DEFAULT NULL,
  `image9` longblob DEFAULT NULL,
  `image10` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `albums`
--

INSERT INTO `albums` (`album_id`, `business_id`, `logo`, `image1`, `image2`, `image3`, `image4`, `image5`, `image6`, `image7`, `image8`, `image9`, `image10`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employ_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `set_date` timestamp NULL DEFAULT current_timestamp() COMMENT 'when it was created',
  `appoint_date` datetime NOT NULL COMMENT 'Combined date and time of appointment',
  `end_date` datetime DEFAULT NULL COMMENT 'when the appointment changed status to completed',
  `appoint_status` varchar(255) NOT NULL COMMENT 'pending, confirmed, cancelled, completed',
  `appoint_desc` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `customer_id`, `employ_id`, `service_id`, `set_date`, `appoint_date`, `end_date`, `appoint_status`, `appoint_desc`) VALUES
(1, 1, 1, 2, '2025-10-26 07:09:14', '2025-01-20 10:00:00', NULL, 'confirmed', 'Client prefers warm tones'),
(2, 2, 5, 13, '2025-10-26 07:09:14', '2025-01-18 14:30:00', NULL, 'pending', 'First time customer'),
(3, 3, 3, 7, '2025-10-26 07:09:14', '2025-01-19 11:00:00', NULL, 'confirmed', 'Lower back pain'),
(4, 1, 4, 11, '2025-10-26 07:09:14', '2025-01-22 15:00:00', NULL, 'pending', NULL),
(5, 2, 2, 6, '2025-10-26 07:09:14', '2025-01-21 09:00:00', NULL, 'confirmed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `column_name` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` varchar(255) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `business_id` int(11) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `business_desc` text DEFAULT NULL,
  `business_num` varchar(255) DEFAULT NULL,
  `business_email` varchar(255) DEFAULT NULL,
  `business_password` varchar(255) DEFAULT NULL,
  `business_address` varchar(255) DEFAULT NULL,
  `opening_hour` time NOT NULL,
  `closing_hour` time NOT NULL,
  `city` varchar(50) NOT NULL,
  `location` point DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`business_id`, `business_name`, `business_type`, `business_desc`, `business_num`, `business_email`, `business_password`, `business_address`, `opening_hour`, `closing_hour`, `city`, `location`) VALUES
(1, 'Glam Beauty Salon', 'salon', 'Premier beauty salon offering hair, nails, and makeup services in the heart of Nasugbu.', '0968736411', 'glam.salon@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brgy. 1, Nasugbu', '07:00:00', '08:00:00', 'Nasugbu', 0x0000000001010000002a3a92cb7f285e40f90fe9b7af232c40),
(2, 'Tranquil Day Spa', 'spa', 'Relaxing spa treatments including massage, facials, and body treatments.', '09773656712', 'tranquil.spa@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brgy. Calayo, Nasugbu', '07:00:00', '08:00:00', 'Nasugbu', 0x000000000101000000713d0ad7a3285e40be9f1a2fdd242c40),
(3, 'Classic Cuts Barbershop', 'barbershop', 'Traditional barbershop specializing in men\'s haircuts and grooming.', '09987633341', 'classic.barber@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brgy. Bucana, Nasugbu', '07:00:00', '06:00:00', 'Nasugbu', 0x000000000101000000b81e85eb51285e40894160e5d0222c40);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `mname` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `cstmr_num` varchar(255) DEFAULT NULL,
  `cstmr_email` varchar(255) DEFAULT NULL,
  `cstmr_password` varchar(255) DEFAULT NULL,
  `cstmr_address` varchar(255) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `fname`, `mname`, `surname`, `cstmr_num`, `cstmr_email`, `cstmr_password`, `cstmr_address`, `registration_date`, `profile_pic`) VALUES
(1, 'Maria', NULL, 'Santos', '09171234567', 'maria.santos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2025-10-26 07:09:14', NULL),
(2, 'Juan', NULL, 'Dela Cruz', '09181234567', 'juan.delacruz@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2025-10-26 07:09:14', NULL),
(3, 'Ana', NULL, 'Reyes', '09191234567', 'ana.reyes@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2025-10-26 07:09:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employ_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `employ_fname` varchar(255) NOT NULL,
  `employ_lname` varchar(255) NOT NULL,
  `employ_bio` text DEFAULT NULL,
  `specialization` varchar(50) NOT NULL,
  `skills` varchar(255) NOT NULL,
  `employ_status` varchar(255) NOT NULL,
  `employ_img` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employ_id`, `service_id`, `business_id`, `employ_fname`, `employ_lname`, `employ_bio`, `specialization`, `skills`, `employ_status`, `employ_img`) VALUES
(1, NULL, 1, 'Elena', 'Cruz', NULL, 'Hair Coloring Specialist', 'Hair Coloring Specialist', 'available', NULL),
(2, NULL, 1, 'Rosa', 'Martinez', NULL, 'Bridal Makeup Artist', 'Bridal Makeup Artist', 'available', NULL),
(3, NULL, 2, 'Carmen', 'Lopez', NULL, 'Licensed Massage Therapist', 'Licensed Massage Therapist', 'available', NULL),
(4, NULL, 2, 'Sofia', 'Ramos', NULL, 'Facial Specialist', 'Facial Specialist', 'available', NULL),
(5, NULL, 3, 'Miguel', 'Santos', NULL, 'Master Barber', 'Master Barber', 'available', NULL),
(6, NULL, 3, 'Diego', 'Fernandez', NULL, 'Barber & Grooming Expert', 'Barber & Grooming Expert', 'available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `notif_title` varchar(50) NOT NULL COMMENT 'new/successful/cancelled appointment or new reviews',
  `notif_text` text NOT NULL,
  `notif_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `profile_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `face_shape` varchar(255) DEFAULT NULL,
  `body_type` varchar(255) DEFAULT NULL,
  `eye_color` varchar(255) DEFAULT NULL,
  `skin_tone` varchar(255) DEFAULT NULL,
  `hair_type` varchar(255) DEFAULT NULL,
  `hair_color` varchar(255) DEFAULT NULL,
  `current_hair_length` varchar(255) DEFAULT NULL,
  `desired_hair_length` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`profile_id`, `customer_id`, `face_shape`, `body_type`, `eye_color`, `skin_tone`, `hair_type`, `hair_color`, `current_hair_length`, `desired_hair_length`) VALUES
(1, 1, 'oval', 'average', NULL, 'medium', 'straight', 'black', NULL, 'medium'),
(2, 2, 'square', 'athletic', NULL, 'tan', 'wavy', 'brown', NULL, 'short'),
(3, 3, 'heart', 'slim', NULL, 'fair', 'curly', 'brown', NULL, 'long');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT current_timestamp() COMMENT 'when it was created',
  `rating` int(11) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `review_img1` longblob DEFAULT NULL,
  `review_img2` longblob DEFAULT NULL,
  `review_img3` longblob DEFAULT NULL,
  `review_img4` longblob DEFAULT NULL,
  `review_img5` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `customer_id`, `business_id`, `review_date`, `rating`, `review_text`, `review_img1`, `review_img2`, `review_img3`, `review_img4`, `review_img5`) VALUES
(1, 1, 1, '2025-10-26 07:09:14', 5, 'Amazing service! Elena is a true artist with hair coloring.', NULL, NULL, NULL, NULL, NULL),
(2, 3, 1, '2025-10-26 07:09:14', 5, 'Beautiful salon and very professional staff. Highly recommend!', NULL, NULL, NULL, NULL, NULL),
(3, 2, 2, '2025-10-26 07:09:14', 4, 'Very relaxing massage. The ambiance could be improved but overall great experience.', NULL, NULL, NULL, NULL, NULL),
(4, 1, 2, '2025-10-26 07:09:14', 5, 'Best spa in Nasugbu! Carmen is excellent at deep tissue massage.', NULL, NULL, NULL, NULL, NULL),
(5, 2, 3, '2025-10-26 07:09:14', 5, 'Miguel gave me the best haircut I\'ve had in years. Will definitely return!', NULL, NULL, NULL, NULL, NULL),
(6, 3, 3, '2025-10-26 07:09:14', 4, 'Clean shop, friendly staff, good value for money.', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `business_id` int(11) DEFAULT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `service_type` varchar(255) DEFAULT NULL,
  `service_desc` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL COMMENT 'Price in PHP',
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `business_id`, `service_name`, `service_type`, `service_desc`, `cost`, `duration`) VALUES
(1, 1, 'Women\'s Haircut & Style', 'Hair', 'Professional haircut with styling', 500.00, 60),
(2, 1, 'Hair Color (Full)', 'Hair', 'Complete hair coloring service', 2500.00, 180),
(3, 1, 'Balayage/Highlights', 'Hair', 'Premium balayage or highlights', 3500.00, 240),
(4, 1, 'Manicure', 'Nails', 'Basic manicure with polish', 300.00, 45),
(5, 1, 'Pedicure', 'Nails', 'Relaxing pedicure with polish', 400.00, 60),
(6, 1, 'Bridal Makeup', 'Makeup', 'Complete bridal makeup package', 5000.00, 120),
(7, 2, 'Swedish Massage (60 min)', 'Massage', 'Relaxing full body massage', 1200.00, 60),
(8, 2, 'Swedish Massage (90 min)', 'Massage', 'Extended relaxation massage', 1800.00, 90),
(9, 2, 'Hot Stone Massage', 'Massage', 'Therapeutic hot stone treatment', 2000.00, 90),
(10, 2, 'Deep Tissue Massage', 'Massage', 'Intensive muscle therapy', 1500.00, 60),
(11, 2, 'Classic Facial', 'Facial', 'Cleansing and rejuvenating facial', 1000.00, 60),
(12, 2, 'Body Scrub', 'Body', 'Exfoliating body treatment', 1500.00, 75),
(13, 3, 'Men\'s Haircut', 'Hair', 'Classic men\'s haircut', 200.00, 30),
(14, 3, 'Haircut & Beard Trim', 'Hair', 'Haircut with beard grooming', 300.00, 45),
(15, 3, 'Hot Towel Shave', 'Grooming', 'Traditional straight razor shave', 250.00, 30),
(16, 3, 'Hair & Beard Color', 'Hair', 'Hair and beard coloring', 800.00, 60),
(17, 3, 'Scalp Treatment', 'Treatment', 'Deep cleansing scalp treatment', 400.00, 45),
(18, 3, 'Kids Haircut', 'Hair', 'Haircut for children (12 and under)', 150.00, 20);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`album_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employ_id` (`employ_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`business_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employ_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `business_id` (`business_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `business_id` (`business_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `album_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `business_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employ_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`employ_id`) REFERENCES `employees` (`employ_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`business_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
