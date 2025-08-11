-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2025 at 11:33 AM
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
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type_id` int(11) NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` enum('pending','approved','cancelled','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `softcopy_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `document_type_id`, `purpose`, `appointment_date`, `appointment_time`, `status`, `admin_notes`, `softcopy_filename`, `created_at`) VALUES
(1, 3, 3, 'for cash assistance', '2025-08-11', '08:00:00', 'approved', NULL, '1754645539_soft_Screenshot (963).png', '2025-08-08 09:31:47');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `address`, `id_filename`, `is_verified`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@barangay.local', '$2y$10$e0NR7P2g1gQk9sYb6Z9kE.v2Qx6v4w6qF4o2c1Kq9y0JvZx3hP8yK', 'Barangay Hall', NULL, 1, 'admin', '2025-08-08 09:15:05'),
(2, 'admin1', 'admin1@barangay.local', '$2y$10$I0flH/5o6WL8JDn3w14g3.kqs71V4j71n2BZIXYcqCpsoz0rBKPHu', 'barangay balabag', NULL, 1, 'admin', '2025-08-08 09:23:54'),
(3, 'roberto delata', 'roberto@gmail.com', '$2y$10$mQJE0tqnB9KQNsQmMFyKXukIix8VawLVxTjnMMzlObcMlkS6du2pK', 'barangay balabag', '1754645358_Screenshot (967).png', 1, 'user', '2025-08-08 09:29:18');

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
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
