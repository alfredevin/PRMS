-- Migration: Add gender and tourist_type columns to reservation_tbl
-- This script adds fields to track guest demographics (gender) and tourism type (local/foreign)
-- Run this migration to enable the new guest booking features

ALTER TABLE `reservation_tbl` 
ADD COLUMN `gender` VARCHAR(50) DEFAULT 'Not Specified' AFTER `guest_phone`,
ADD COLUMN `tourist_type` VARCHAR(50) DEFAULT 'Local' AFTER `gender`;

-- Verify the columns were added (optional)
-- SELECT * FROM reservation_tbl LIMIT 1;
