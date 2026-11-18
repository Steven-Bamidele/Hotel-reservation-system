-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2026 at 01:32 AM
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
-- Database: `ui_hotels_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `charges`
--

CREATE TABLE `charges` (
  `charge_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `description` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charges`
--

INSERT INTO `charges` (`charge_id`, `reservation_id`, `description`, `amount`, `created_at`) VALUES
(1, 1, 'Room Services', 5000.00, '2026-10-07 14:09:50'),
(2, 1, 'Laudry', 5000.00, '2026-10-07 14:10:17'),
(3, 2, 'Room Services', 5000.00, '2026-10-07 14:21:20'),
(4, 2, 'Laudry', 5000.00, '2026-10-07 14:21:31');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`guest_id`, `full_name`, `phone`, `email`, `address`) VALUES
(1, 'James', '07010000000', 'James26@gmail.com', NULL),
(2, 'Shalom', '08100000000', 'Shalom26@gmail.com', NULL),
(3, 'Damola', '08100000012', 'Damola26@gmail.com', NULL),
(4, 'Stephen', '08123456789', 'Stephen26@gmail.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('Cash','POS Transfer','Bank Transfer') NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `reservation_id`, `amount`, `method`, `paid_at`) VALUES
(1, 1, 90000.00, 'POS Transfer', '2026-10-07 14:10:21'),
(2, 2, 70000.00, 'Cash', '2026-10-07 14:21:41'),
(3, 3, 25000.00, 'Cash', '2026-10-07 14:24:46');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `status` enum('Confirmed','Checked-in','Completed','Cancelled') DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `guest_id`, `room_id`, `created_by`, `check_in`, `check_out`, `status`, `created_at`) VALUES
(1, 1, 7, 1, '2026-10-07', '2026-10-08', 'Completed', '2026-10-07 14:07:02'),
(2, 2, 6, 1, '2026-10-08', '2026-10-09', 'Completed', '2026-10-07 14:20:37'),
(3, 3, 1, 1, '2026-10-07', '2026-10-08', 'Completed', '2026-10-07 14:24:30'),
(4, 4, 1, 1, '2026-10-07', '2026-10-08', 'Checked-in', '2026-10-07 14:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `type_id` int(11) NOT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `type_id`, `status`) VALUES
(1, 'UI-101', 1, 'occupied'),
(2, 'UI-102', 1, 'available'),
(3, 'UI-201', 2, 'available'),
(4, 'UI-202', 2, 'available'),
(5, 'UI-301', 3, 'available'),
(6, 'UI-401', 4, 'available'),
(7, 'UI-501', 5, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(60) NOT NULL,
  `rate_per_night` decimal(10,2) NOT NULL,
  `max_occupancy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`type_id`, `type_name`, `rate_per_night`, `max_occupancy`) VALUES
(1, 'Double Room Deluxe', 25000.00, 2),
(2, 'Royal Standard', 28000.00, 2),
(3, 'Royal Executive', 35000.00, 2),
(4, 'Executive Suite', 60000.00, 3),
(5, 'Premium Royal Suite', 80000.00, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','receptionist') NOT NULL DEFAULT 'receptionist'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `password_hash`, `role`) VALUES
(1, 'System Administrator', 'admin', '$2y$10$4yv2ZkQ3eEwG6vJ4e1J8heTf97Bw2o2R0H8cO9g1z2b4x5w6v7u8y', 'admin'),
(2, 'Front Desk Clerk', 'reception', '$2y$10$4yv2ZkQ3eEwG6vJ4e1J8heTf97Bw2o2R0H8cO9g1z2b4x5w6v7u8y', 'receptionist');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `charges`
--
ALTER TABLE `charges`
  ADD PRIMARY KEY (`charge_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `charges`
--
ALTER TABLE `charges`
  MODIFY `charge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `charges`
--
ALTER TABLE `charges`
  ADD CONSTRAINT `charges_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `room_types` (`type_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
