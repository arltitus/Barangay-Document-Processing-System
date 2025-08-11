-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2025 at 02:57 PM
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
-- Database: `barangay_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `code`, `title`, `description`) VALUES
(1, 'CERT', 'Barangay Certificate', 'Official document certifying specific information about an individual, family, or entity within the barangay.'),
(2, 'CLEAR', 'Barangay Clearance', 'Confirms residency and good moral character within the barangay.'),
(3, 'INDIG', 'Certificate of Indigency', 'Certifies that an individual is indigent and a resident of the barangay.'),
(4, 'BUSPERM', 'Business Permit', 'Grants permission to operate a business within the barangay.'),
(5, 'NOSHOW', 'Certificate of No Show', 'Certifies that a resident was not present at a specific event or activity within the barangay.'),
(6, 'FILEACT', 'Certificate to File Action', 'Issued by the Lupon Tagapamayapa when a dispute cannot be settled.'),
(7, 'FTJ', 'First-Time Jobseeker Certificate', 'Verifies that the applicant is a first-time job seeker and a resident for at least six months.');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempted_at`, `success`) VALUES
(1, 'admin1@barangay.local', '::1', '2025-08-08 11:31:40', 1),
(2, 'admin1@barangay.local', '::1', '2025-08-08 11:33:22', 1),
(3, 'admin1@barangay.local', '::1', '2025-08-08 11:35:41', 1),
(4, 'roberto@gmail.com', '::1', '2025-08-08 11:40:12', 1),
(5, 'roberto@gmail.com', '::1', '2025-08-08 11:54:52', 1),
(6, 'admin1@barangay.local', '::1', '2025-08-08 12:04:24', 1),
(7, 'admin1@barangay.local', '::1', '2025-08-08 12:06:24', 1),
(8, 'admin1@barangay.local', '::1', '2025-08-08 12:06:50', 1),
(9, 'admin1@barangay.local', '::1', '2025-08-08 12:12:10', 1),
(10, 'admin1@barangay.local', '::1', '2025-08-08 12:33:21', 1),
(11, 'admin1@barangay.local', '::1', '2025-08-08 12:52:39', 1),
(12, 'roberto@gmail.com', '::1', '2025-08-08 12:52:45', 1),
(13, 'roberto@gmail.com', '::1', '2025-08-11 06:50:19', 1),
(14, 'roberto@gmail.com', '::1', '2025-08-11 08:00:33', 1),
(15, 'roberto@gmail.com', '::1', '2025-08-11 09:37:53', 1),
(16, 'admin1@barangay.local', '::1', '2025-08-11 09:38:11', 1),
(17, 'roberto@gmail.com', '::1', '2025-08-11 10:02:32', 1),
(18, 'roberto@gmail.com', '::1', '2025-08-11 11:02:09', 1),
(19, 'admin1@barangay.local', '::1', '2025-08-11 11:58:55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` enum('pending','approved','cancelled','completed','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `softcopy_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `document_type_id`, `purpose`, `appointment_date`, `appointment_time`, `status`, `admin_notes`, `softcopy_filename`, `created_at`) VALUES
(3, 3, 7, 'for cash job application', '2025-08-12', '08:00:00', 'approved', NULL, NULL, '2025-08-11 11:58:05'),
(4, 3, 6, 'for file action', '2025-08-13', '09:00:00', 'rejected', NULL, NULL, '2025-08-11 12:00:23'),
(5, 3, 2, 'for cash assistance', '2025-08-14', '09:25:00', 'approved', 'okay', NULL, '2025-08-11 12:26:02'),
(6, 3, 4, 'for tyange', '2025-08-15', '10:26:00', 'rejected', 'indi pwde', '', '2025-08-11 12:26:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `id_filename` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `address`, `id_filename`, `is_verified`, `role`, `created_at`, `failed_login_attempts`, `locked_until`, `session_id`, `last_login`) VALUES
(1, 'Admin User', 'admin@barangay.local', '$2y$10$e0NR7P2g1gQk9sYb6Z9kE.v2Qx6v4w6qF4o2c1Kq9y0JvZx3hP8yK', 'Barangay Hall', NULL, 1, 'admin', '2025-08-08 09:15:05', 0, NULL, NULL, NULL),
(2, 'admin1', 'admin1@barangay.local', '$2y$10$I0flH/5o6WL8JDn3w14g3.kqs71V4j71n2BZIXYcqCpsoz0rBKPHu', 'barangay balabag', NULL, 1, 'admin', '2025-08-08 09:23:54', 0, NULL, 'lpqoreobse4v8fuqebbdgdmt9a', '2025-08-11 19:58:55'),
(3, 'roberto delata', 'roberto@gmail.com', '$2y$10$mQJE0tqnB9KQNsQmMFyKXukIix8VawLVxTjnMMzlObcMlkS6du2pK', 'barangay balabag', '1754645358_Screenshot (967).png', 1, 'user', '2025-08-08 09:29:18', 0, NULL, 'lec64ibg1j4pmr7mdqikohuss9', '2025-08-11 19:02:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_ip` (`email`,`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `document_type_id` (`document_type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
