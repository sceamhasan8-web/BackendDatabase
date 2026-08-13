-- ScholasticBase MySQL Database Schema for XAMPP
CREATE DATABASE IF NOT EXISTS `ScholasticBase` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `ScholasticBase`;

-- Dynamic document storage table (replaces Firestore collections)
CREATE TABLE IF NOT EXISTS `app_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `collection_name` VARCHAR(64) NOT NULL,
  `doc_id` VARCHAR(128) NOT NULL,
  `school_id` VARCHAR(64) DEFAULT 'PROGGA_DEFAULT',
  `data` LONGTEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_doc` (`collection_name`, `doc_id`, `school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dedicated users table for login and user management
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` VARCHAR(128) PRIMARY KEY,
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(64) NULL,
  `display_name` VARCHAR(191) NULL,
  `role` VARCHAR(64) DEFAULT 'user',
  `school_id` VARCHAR(64) DEFAULT 'PROGGA_DEFAULT',
  `data` LONGTEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File upload logs
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
