-- Add event_end_time to event_tbl if not exists
ALTER TABLE `event_tbl` ADD COLUMN `event_end_time` TIME DEFAULT NULL AFTER `event_time`;

-- Create event_booking_tbl to track event bookings linked to reservations
CREATE TABLE IF NOT EXISTS `event_booking_tbl` (
  `event_booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `tracking_number` varchar(100) NOT NULL COMMENT 'Link to reservation tracking number',
  `event_id` int(11) NOT NULL,
  `number_of_guests` int(11) NOT NULL DEFAULT 1,
  `special_requests` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_notes` text DEFAULT NULL,
  `booked_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`event_booking_id`),
  FOREIGN KEY (`event_id`) REFERENCES `event_tbl` (`event_id`) ON DELETE CASCADE,
  INDEX `idx_tracking` (`tracking_number`),
  INDEX `idx_event_id` (`event_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
