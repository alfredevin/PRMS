-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 11:27 PM
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
-- Database: `prms`
--

-- --------------------------------------------------------

--
-- Table structure for table `boat_rental_fee_tbl`
--

CREATE TABLE `boat_rental_fee_tbl` (
  `rental_id` int(11) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `min_guest` int(11) NOT NULL DEFAULT 1,
  `max_guest` int(11) NOT NULL DEFAULT 10,
  `amount` decimal(10,2) NOT NULL,
  `island_hopping_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_logs_tbl`
--

CREATE TABLE `customer_logs_tbl` (
  `logs_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `login` varchar(30) DEFAULT NULL,
  `logout` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discount_tbl`
--

CREATE TABLE `discount_tbl` (
  `discount_id` int(11) NOT NULL,
  `discount_name` varchar(150) NOT NULL,
  `discount_percent` int(3) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `entrance_fee_tbl`
--

CREATE TABLE `entrance_fee_tbl` (
  `entrance_fee_id` int(1) NOT NULL,
  `entrance_fee_amount` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entrance_fee_tbl`
--

INSERT INTO `entrance_fee_tbl` (`entrance_fee_id`, `entrance_fee_amount`) VALUES
(1, '100');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_log_tbl`
--

CREATE TABLE `equipment_log_tbl` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `action_type` enum('Borrowed','Damaged','Returned') DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  `status_log` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_tbl`
--

CREATE TABLE `equipment_tbl` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `equipment_description` text NOT NULL,
  `equipment_quantity` int(11) NOT NULL,
  `equipment_price` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_tbl`
--

CREATE TABLE `event_tbl` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempts_id` int(11) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `last_attempt` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_type_tbl`
--

CREATE TABLE `payment_type_tbl` (
  `payment_type_id` int(11) NOT NULL,
  `payment_type_name` varchar(100) NOT NULL,
  `payment_type_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_type_tbl`
--

INSERT INTO `payment_type_tbl` (`payment_type_id`, `payment_type_name`, `payment_type_number`) VALUES
(1, 'GCASH', '09318543347');

-- --------------------------------------------------------

--
-- Table structure for table `rentals_tbl`
--

CREATE TABLE `rentals_tbl` (
  `rental_id` int(11) NOT NULL,
  `rental_name` varchar(255) NOT NULL,
  `rental_description` text DEFAULT NULL,
  `rental_price` decimal(10,2) NOT NULL,
  `rental_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resched_tbl`
--

CREATE TABLE `resched_tbl` (
  `resched_id` int(11) NOT NULL,
  `tracking_number` varchar(100) NOT NULL,
  `old_check_in` date NOT NULL,
  `old_check_out` date NOT NULL,
  `new_check_in` date NOT NULL,
  `new_check_out` date NOT NULL,
  `resched_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = Pending, 1 = Approved, 2 = Rejected'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_boat_rentals_tbl`
--

CREATE TABLE `reservation_boat_rentals_tbl` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `include_island` tinyint(1) DEFAULT 0,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_equipment_tbl`
--

CREATE TABLE `reservation_equipment_tbl` (
  `loan_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `quantity_loaned` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `loan_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `loan_status` varchar(50) DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_guests_tbl`
--

CREATE TABLE `reservation_guests_tbl` (
  `guest_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `age` int(11) NOT NULL,
  `category` enum('Child','Adult','Senior') NOT NULL,
  `pwd` enum('Yes','No') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_payments_tbl`
--

CREATE TABLE `reservation_payments_tbl` (
  `payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `amount` varchar(50) NOT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tracking_number` varchar(100) NOT NULL,
  `payment_option` varchar(20) DEFAULT 'full'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_rentals_tbl`
--

CREATE TABLE `reservation_rentals_tbl` (
  `rental_reservation_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tracking_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_services_tbl`
--

CREATE TABLE `reservation_services_tbl` (
  `service_reservation_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tracking_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_tbl`
--

CREATE TABLE `reservation_tbl` (
  `reservation_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `guest_email` varchar(255) NOT NULL,
  `guest_phone` varchar(50) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) NOT NULL,
  `total_nights` int(11) NOT NULL,
  `total_price` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `tracking_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews_tbl`
--

CREATE TABLE `reviews_tbl` (
  `review_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms_tbl`
--

CREATE TABLE `rooms_tbl` (
  `room_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `max_guest` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available` int(11) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `room_name` varchar(50) NOT NULL,
  `room_description` varchar(250) NOT NULL,
  `discount_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_type_tbl`
--

CREATE TABLE `room_type_tbl` (
  `room_type_id` int(11) NOT NULL,
  `room_type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services_tbl`
--

CREATE TABLE `services_tbl` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_description` text NOT NULL,
  `service_price` decimal(10,2) NOT NULL,
  `service_image` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userlogs_tbl`
--

CREATE TABLE `userlogs_tbl` (
  `log_id` int(11) NOT NULL,
  `userid` int(2) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tbl`
--

CREATE TABLE `user_tbl` (
  `userid` int(11) NOT NULL,
  `fullname` varchar(30) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(300) DEFAULT NULL,
  `usertype` int(1) DEFAULT NULL,
  `useractive` int(1) DEFAULT 1,
  `dateCreated` timestamp NULL DEFAULT current_timestamp(),
  `dateUpdated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contact_number` varchar(20) NOT NULL,
  `position` varchar(255) NOT NULL,
  `code` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tbl`
--

INSERT INTO `user_tbl` (`userid`, `fullname`, `email`, `username`, `password`, `usertype`, `useractive`, `dateCreated`, `dateUpdated`, `contact_number`, `position`, `code`) VALUES
(1, 'Aaron Kenneth', 'kenneth@gmail.com', 'admin', '$2y$10$mzwcKwrXLuYhPRoxV1lJGu5zuEgHfGLwhp0JFIvHRpJX.k1PVpso.', 1, 1, '2024-02-28 01:18:05', '2025-08-17 08:56:47', '09215813119', 'REGISTRAR', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `boat_rental_fee_tbl`
--
ALTER TABLE `boat_rental_fee_tbl`
  ADD PRIMARY KEY (`rental_id`);

--
-- Indexes for table `customer_logs_tbl`
--
ALTER TABLE `customer_logs_tbl`
  ADD PRIMARY KEY (`logs_id`);

--
-- Indexes for table `discount_tbl`
--
ALTER TABLE `discount_tbl`
  ADD PRIMARY KEY (`discount_id`);

--
-- Indexes for table `entrance_fee_tbl`
--
ALTER TABLE `entrance_fee_tbl`
  ADD PRIMARY KEY (`entrance_fee_id`);

--
-- Indexes for table `equipment_log_tbl`
--
ALTER TABLE `equipment_log_tbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `equipment_tbl`
--
ALTER TABLE `equipment_tbl`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `event_tbl`
--
ALTER TABLE `event_tbl`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempts_id`);

--
-- Indexes for table `payment_type_tbl`
--
ALTER TABLE `payment_type_tbl`
  ADD PRIMARY KEY (`payment_type_id`);

--
-- Indexes for table `rentals_tbl`
--
ALTER TABLE `rentals_tbl`
  ADD PRIMARY KEY (`rental_id`);

--
-- Indexes for table `resched_tbl`
--
ALTER TABLE `resched_tbl`
  ADD PRIMARY KEY (`resched_id`);

--
-- Indexes for table `reservation_boat_rentals_tbl`
--
ALTER TABLE `reservation_boat_rentals_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservation_equipment_tbl`
--
ALTER TABLE `reservation_equipment_tbl`
  ADD PRIMARY KEY (`loan_id`);

--
-- Indexes for table `reservation_guests_tbl`
--
ALTER TABLE `reservation_guests_tbl`
  ADD PRIMARY KEY (`guest_id`);

--
-- Indexes for table `reservation_payments_tbl`
--
ALTER TABLE `reservation_payments_tbl`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `reservation_rentals_tbl`
--
ALTER TABLE `reservation_rentals_tbl`
  ADD PRIMARY KEY (`rental_reservation_id`);

--
-- Indexes for table `reservation_services_tbl`
--
ALTER TABLE `reservation_services_tbl`
  ADD PRIMARY KEY (`service_reservation_id`);

--
-- Indexes for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `reviews_tbl`
--
ALTER TABLE `reviews_tbl`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `room_type_tbl`
--
ALTER TABLE `room_type_tbl`
  ADD PRIMARY KEY (`room_type_id`);

--
-- Indexes for table `services_tbl`
--
ALTER TABLE `services_tbl`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `userlogs_tbl`
--
ALTER TABLE `userlogs_tbl`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD PRIMARY KEY (`userid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boat_rental_fee_tbl`
--
ALTER TABLE `boat_rental_fee_tbl`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_logs_tbl`
--
ALTER TABLE `customer_logs_tbl`
  MODIFY `logs_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `discount_tbl`
--
ALTER TABLE `discount_tbl`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `entrance_fee_tbl`
--
ALTER TABLE `entrance_fee_tbl`
  MODIFY `entrance_fee_id` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `equipment_log_tbl`
--
ALTER TABLE `equipment_log_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_tbl`
--
ALTER TABLE `equipment_tbl`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_tbl`
--
ALTER TABLE `event_tbl`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempts_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `payment_type_tbl`
--
ALTER TABLE `payment_type_tbl`
  MODIFY `payment_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rentals_tbl`
--
ALTER TABLE `rentals_tbl`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resched_tbl`
--
ALTER TABLE `resched_tbl`
  MODIFY `resched_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_boat_rentals_tbl`
--
ALTER TABLE `reservation_boat_rentals_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservation_equipment_tbl`
--
ALTER TABLE `reservation_equipment_tbl`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_guests_tbl`
--
ALTER TABLE `reservation_guests_tbl`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reservation_payments_tbl`
--
ALTER TABLE `reservation_payments_tbl`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservation_rentals_tbl`
--
ALTER TABLE `reservation_rentals_tbl`
  MODIFY `rental_reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_services_tbl`
--
ALTER TABLE `reservation_services_tbl`
  MODIFY `service_reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews_tbl`
--
ALTER TABLE `reviews_tbl`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room_type_tbl`
--
ALTER TABLE `room_type_tbl`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services_tbl`
--
ALTER TABLE `services_tbl`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `userlogs_tbl`
--
ALTER TABLE `userlogs_tbl`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipment_log_tbl`
--
ALTER TABLE `equipment_log_tbl`
  ADD CONSTRAINT `equipment_log_tbl_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_tbl` (`equipment_id`);

--
-- Constraints for table `reservation_tbl`
--
ALTER TABLE `reservation_tbl`
  ADD CONSTRAINT `reservation_tbl_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms_tbl` (`room_id`);

--
-- Constraints for table `rooms_tbl`
--
ALTER TABLE `rooms_tbl`
  ADD CONSTRAINT `rooms_tbl_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `room_type_tbl` (`room_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
