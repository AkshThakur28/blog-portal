-- Blog CI schema and seed
-- Run this in phpMyAdmin (or MySQL client)
-- It creates the database, tables, and seeds 1 admin + 2 users + 2 sample posts.

CREATE DATABASE IF NOT EXISTS `blog_ci` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `blog_ci`;

-- Drop existing tables if re-running
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `users`;

-- Users table
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts table
CREATE TABLE `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `body` MEDIUMTEXT NOT NULL,
  `cover_media_url` VARCHAR(512) DEFAULT NULL,
  `media_type` ENUM('image','video') DEFAULT NULL,
  `status` ENUM('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed users
-- Passwords:
--   Admin: Admin@123
--   User1: User@123
--   User2: User@123
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `created_at`) VALUES
('Admin', 'admin@example.com', '$2b$12$F3CefWd.vSJdIWeUe.CHPeFdGaWNNLtVJbeL/7EUVMIfIrNYp0Lvm', 'admin', NOW()),
('John User', 'user1@example.com', '$2b$12$1NWtALXijeHfJVJb8x85YeufUKmt8.yXrb9vRLVs6qRDOtgolDZoa', 'user', NOW()),
('Jane User', 'user2@example.com', '$2b$12$fgKgPqcYW6zlaQIAcGvIe.RLWmBHGm4bjBDNE3AnyYZBPMg51Nu6G', 'user', NOW());

-- Sample posts (2)
INSERT INTO `posts`
(`user_id`, `title`, `slug`, `body`, `cover_media_url`, `media_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to the Blog', 'welcome-to-the-blog',
  '<p>This is the first sample post. Use the dashboard to create, edit, and soft-delete posts. Media attribution: Images/Videos via Pixabay.</p>',
  NULL, NULL, 'active', NOW(), NOW()),
(2, 'Getting Started with CI3', 'getting-started-with-ci3',
  '<p>CodeIgniter 3 is a lightweight PHP framework. This demo shows JWT auth, pixabay proxy, and a simple blog.</p>',
  NULL, NULL, 'active', NOW(), NOW());
