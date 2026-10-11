-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 12, 2026 at 06:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `soy_php_pdo`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `goat_id` varchar(50) DEFAULT NULL,
  `goat_pk` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `status` enum('success','failed') DEFAULT 'success',
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `goat_id`, `goat_pk`, `email`, `action`, `status`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, NULL, NULL, 'giovbars@gmail.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:41:37'),
(2, NULL, NULL, NULL, 'giovbars@gmail.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:41:45'),
(3, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:41:55'),
(4, 1, NULL, NULL, 'admin@example.com', 'user_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:46:22'),
(5, 4, NULL, NULL, 'giovbars@gmail.com', 'account_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:46:22'),
(6, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:46:30'),
(7, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:46:41'),
(8, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:48:41'),
(9, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:48:48'),
(10, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:48:58'),
(11, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:49:09'),
(12, 4, 'GT202', NULL, 'giovbars@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"4\",\"health_status\":\"sick\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:49:24'),
(13, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:49:29'),
(14, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:49:40'),
(15, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:50:12'),
(16, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 08:50:27'),
(17, 4, 'G-0001', NULL, 'giovbars@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"4\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:06:24'),
(18, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:06:40'),
(19, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:06:51'),
(20, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:07:25'),
(21, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:07:36'),
(22, 1, NULL, NULL, 'admin@example.com', 'user_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:08:18'),
(23, 5, NULL, NULL, 'silver@gmail.com', 'account_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:08:18'),
(24, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:08:21'),
(25, 5, NULL, NULL, 'silver@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:08:34'),
(26, 5, 'G-0002', NULL, 'silver@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"5\",\"health_status\":\"sick\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:10:04'),
(27, 5, NULL, NULL, 'silver@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:10:12'),
(28, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:10:21'),
(29, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:10:28'),
(30, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:10:33'),
(31, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:41:23'),
(32, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-19 09:41:34'),
(33, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-20 03:40:44'),
(34, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-20 03:40:48'),
(35, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-20 03:40:58'),
(36, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 03:41:24'),
(37, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 03:41:46'),
(38, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 03:42:03'),
(39, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 07:02:40'),
(40, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-20 08:13:32'),
(41, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:06:49'),
(42, 4, 'G-0001', NULL, 'giovbars@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:07:59'),
(43, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:10:24'),
(44, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:10:33'),
(45, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:11:13'),
(46, 5, NULL, NULL, 'silver@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:11:20'),
(47, 5, NULL, NULL, 'silver@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:12:14'),
(48, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:12:24'),
(49, 2, 'G-0002', NULL, 'manager@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:12:41'),
(50, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:12:58'),
(51, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:13:04'),
(52, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:13:56'),
(53, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:14:04'),
(54, 4, 'G-0002', NULL, 'giovbars@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"4\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:22:42'),
(55, 4, 'G-0003', NULL, 'giovbars@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"4\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:23:51'),
(56, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:23:59'),
(57, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:24:15'),
(58, 2, NULL, NULL, 'manager@example.com', 'user_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:24:47'),
(59, 6, NULL, NULL, 'ditir@gmail.com', 'account_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:24:47'),
(60, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:24:50'),
(61, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:24:56'),
(62, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"6\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 03:25:47'),
(63, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 04:00:26'),
(64, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 04:00:34'),
(65, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 08:11:25'),
(66, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 08:11:38'),
(67, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 08:14:15'),
(68, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 08:14:23'),
(69, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:38:59'),
(70, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:39:09'),
(71, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:39:18'),
(72, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:40:57'),
(73, NULL, NULL, NULL, 'manager@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:41:26'),
(74, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:41:32'),
(75, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:43:27'),
(76, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:43:49'),
(77, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 10:45:26'),
(78, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 13:07:58'),
(79, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 13:20:42'),
(80, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 13:20:53'),
(81, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 13:21:01'),
(82, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 23:24:54'),
(83, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 23:25:54'),
(84, NULL, NULL, NULL, 'manager@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 23:26:03'),
(85, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 23:26:10'),
(86, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:27:57'),
(87, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:28:27'),
(88, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:28:41'),
(89, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-23 23:32:20'),
(90, 1, 'G-0001', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:42:15'),
(91, 1, 'G-0003', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:45:01'),
(92, 1, 'G-0003', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 23:45:25'),
(93, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 06:18:53'),
(94, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"6\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 06:20:24'),
(95, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 06:21:09'),
(96, NULL, NULL, NULL, 'admin@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:20:32'),
(97, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:21:16'),
(98, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:33:04'),
(99, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:33:34'),
(100, 1, NULL, NULL, 'admin@example.com', 'user_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:38:06'),
(101, 7, NULL, NULL, 'khai@gmail.com', 'account_created', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:38:06'),
(102, 7, NULL, NULL, 'khai@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:38:22'),
(103, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:40:34'),
(104, 1, 'G-0002', NULL, 'admin@example.com', 'goat_marked_sold', 'success', '{\"previous\":\"active\",\"new\":\"sold\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:46:33'),
(105, 1, 'G-0002', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 07:46:34'),
(106, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 10:11:09'),
(107, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:23:11'),
(108, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:23:18'),
(109, 2, 'G-0002', NULL, 'manager@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:24:18'),
(110, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:24:43'),
(111, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:24:51'),
(112, 1, 'G-0002', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:24:57'),
(113, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:25:32'),
(114, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:25:40'),
(115, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:45:39'),
(116, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:45:47'),
(117, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:53:54'),
(118, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:54:00'),
(119, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:54:28'),
(120, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:54:34'),
(121, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:55:28'),
(122, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:55:34'),
(123, 4, 'GT202', NULL, 'giovbars@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:56:01'),
(124, 4, 'G-0001', NULL, 'giovbars@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 11:57:05'),
(125, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:08:21'),
(126, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:08:28'),
(127, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:21:12'),
(128, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:21:26'),
(129, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:33:48'),
(130, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:33:55'),
(131, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:35:41'),
(132, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 12:35:48'),
(133, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:00:03'),
(134, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:22:46'),
(135, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:22:54'),
(136, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:23:52'),
(137, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:28:04'),
(138, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_health_status_changed', 'success', '{\"previous\":\"healthy\",\"new\":\"sick\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:28:23'),
(139, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:28:31'),
(140, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_health_status_changed', 'success', '{\"previous\":\"sick\",\"new\":\"healthy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:29:02'),
(141, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:29:09'),
(142, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:35:30'),
(143, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:36:38'),
(144, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:37:04'),
(145, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:38:53'),
(146, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:39:05'),
(147, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:39:29'),
(148, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:39:37'),
(149, 2, NULL, NULL, 'manager@example.com', 'user_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:45:00'),
(150, 2, NULL, NULL, 'manager@example.com', 'user_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:46:40'),
(151, 2, NULL, NULL, 'manager@example.com', 'user_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:46:51'),
(152, 2, NULL, NULL, 'manager@example.com', 'user_updated', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:47:53'),
(153, 3, NULL, NULL, 'user@example.com', 'profile_updated', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:47:53'),
(154, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:48:49'),
(155, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:49:04'),
(156, 1, 'G-0002', NULL, 'admin@example.com', 'goat_health_status_changed', 'success', '{\"previous\":\"sick\",\"new\":\"unknown\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:50:35'),
(157, 1, 'G-0002', NULL, 'admin@example.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:50:37'),
(158, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:51:20'),
(159, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:51:35'),
(160, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:53:32'),
(161, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:53:39'),
(162, 6, 'G-0001', NULL, 'ditir@gmail.com', 'goat_updated', 'success', '{\"field\":\"info\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:54:37'),
(163, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:54:39'),
(164, 4, NULL, NULL, 'giovbars@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:54:46'),
(165, 4, NULL, NULL, 'giovbars@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:55:15'),
(166, NULL, NULL, NULL, 'manager@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:55:21'),
(167, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:55:27'),
(168, 2, 'GT202', NULL, 'manager@example.com', 'goat_health_status_changed', 'success', '{\"previous\":\"sick\",\"new\":\"healthy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:55:48'),
(169, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:56:23'),
(170, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-24 23:56:29'),
(171, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 00:01:00'),
(172, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:51:49'),
(173, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:53:51'),
(174, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:53:58'),
(175, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:54:01'),
(176, NULL, NULL, NULL, 'manager@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:54:08'),
(177, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:54:15'),
(178, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:55:14'),
(179, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:55:21'),
(180, 1, NULL, NULL, 'admin@example.com', 'user_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:56:22'),
(181, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:56:43'),
(182, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:56:52'),
(183, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:58:41'),
(184, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:58:51'),
(185, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 02:59:35'),
(186, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 10:30:38'),
(187, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 10:34:08'),
(188, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 10:50:09'),
(189, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-25 11:01:13'),
(190, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 03:29:51'),
(191, NULL, NULL, NULL, 'manager@example.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 03:30:16'),
(192, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 03:30:33'),
(193, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-09-01 03:00:31'),
(194, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-09-01 03:24:34'),
(195, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 03:24:50'),
(196, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-09-01 03:29:49'),
(197, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 04:21:28'),
(198, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 04:22:12'),
(199, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 04:59:24'),
(200, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-01 04:59:24'),
(201, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-01 04:59:35'),
(202, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-01 04:59:38'),
(203, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:00:09'),
(204, NULL, NULL, NULL, 'diti@gmail.com', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:05:28'),
(205, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:05:35'),
(206, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:16:06'),
(207, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-01 05:16:06'),
(208, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:16:25'),
(209, 2, NULL, NULL, 'manager@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:34:34'),
(210, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:35:16'),
(211, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 05:35:39'),
(212, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-01 05:35:39'),
(213, NULL, NULL, NULL, 'ad', 'login', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-06 01:21:38'),
(214, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-06 01:21:55'),
(215, 1, NULL, NULL, 'admin@example.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-06 02:07:22'),
(216, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-06 02:07:32'),
(217, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:44:47');
INSERT INTO `activity_logs` (`id`, `user_id`, `goat_id`, `goat_pk`, `email`, `action`, `status`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(218, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:45:08'),
(219, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:45:21'),
(220, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:46:00'),
(221, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1', '2026-09-08 02:46:30'),
(222, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:46:30'),
(223, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 02:54:28'),
(224, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_health_status_changed', 'success', '{\"previous\":\"healthy\",\"new\":\"sick\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 03:03:48'),
(225, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_viewed', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 07:38:26'),
(226, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 07:39:20'),
(227, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 07:41:04'),
(228, 6, 'G-0002', NULL, 'ditir@gmail.com', 'goat_health_status_changed', 'success', '{\"previous\":\"sick\",\"new\":\"healthy\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-08 07:41:30'),
(229, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 05:12:05'),
(230, 2, NULL, NULL, 'manager@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 05:12:20'),
(231, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 05:12:38'),
(232, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-10 10:08:55'),
(233, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-10 10:09:30'),
(234, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 01:38:00'),
(235, 6, NULL, NULL, 'ditir@gmail.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 02:06:49'),
(236, 6, 'G-0003', NULL, 'ditir@gmail.com', 'goat_registered', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 02:36:43'),
(237, 6, 'G-0003', NULL, 'ditir@gmail.com', 'goat_registered', 'failed', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 02:37:22'),
(238, 6, 'G-0003', NULL, 'ditir@gmail.com', 'goat_registered', 'success', '{\"owner_id\":\"6\",\"health_status\":\"healthy\",\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0', '2026-09-12 02:40:38'),
(239, 6, NULL, NULL, 'ditir@gmail.com', 'logout', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:30:27'),
(240, 1, NULL, NULL, 'admin@example.com', 'login', 'success', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-12 04:30:38');

-- --------------------------------------------------------

--
-- Table structure for table `goats`
--

CREATE TABLE `goats` (
  `id` int(11) NOT NULL,
  `goat_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goat_name` varchar(150) NOT NULL DEFAULT '',
  `tag_number` varchar(50) DEFAULT NULL,
  `sex` enum('male','female') NOT NULL,
  `breed` varchar(100) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `color` varchar(150) DEFAULT NULL,
  `identifying_characteristics` text DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `health_status` enum('healthy','sick','unknown') NOT NULL DEFAULT 'unknown',
  `status` enum('active','sold','deceased') NOT NULL DEFAULT 'active',
  `date_registered` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goats`
--

INSERT INTO `goats` (`id`, `goat_id`, `user_id`, `goat_name`, `tag_number`, `sex`, `breed`, `date_of_birth`, `color`, `identifying_characteristics`, `date_acquired`, `health_status`, `status`, `date_registered`, `created_at`, `updated_at`) VALUES
(1, 'GT202', 4, 'twentytwo', NULL, 'male', 'Nubian', '2026-03-16', NULL, NULL, '2026-08-16', 'healthy', 'active', '2026-08-19 16:49:24', '2026-08-19 08:49:24', '2026-08-24 23:55:48'),
(2, 'G-0001', 4, 'yan', NULL, 'male', 'Nubian', '2026-02-18', 'White', NULL, '2026-08-19', 'healthy', 'active', '2026-08-19 17:06:24', '2026-08-19 09:06:24', '2026-08-19 09:06:24'),
(3, 'G-0002', 5, 'sil', NULL, 'female', 'Nubian', NULL, 'White', NULL, '2026-08-19', 'unknown', 'sold', '2026-08-19 17:10:04', '2026-08-19 09:10:04', '2026-08-24 23:50:35'),
(19, 'G-0002', 4, 'twentytwo', NULL, 'female', 'Nubian', '2026-08-23', 'White', NULL, '2026-08-23', 'healthy', 'active', '2026-08-23 11:22:42', '2026-08-23 03:22:42', '2026-08-23 03:22:42'),
(20, 'G-0003', 4, 'wew', NULL, 'male', 'Native', '2026-03-10', 'Brown', NULL, '2026-08-23', 'healthy', 'active', '2026-08-23 11:23:51', '2026-08-23 03:23:51', '2026-08-23 03:23:51'),
(21, 'G-0001', 6, 'yumeko', NULL, 'male', 'Nubian', NULL, 'White', NULL, '2026-08-23', 'healthy', 'active', '2026-08-23 11:25:47', '2026-08-23 03:25:47', '2026-08-24 23:54:37'),
(22, 'G-0002', 6, 'yan', NULL, 'male', 'Native', '2026-11-06', 'Black', NULL, '2026-08-24', 'healthy', 'active', '2026-08-24 14:20:24', '2026-08-24 06:20:24', '2026-09-08 07:41:30'),
(23, 'G-0003', 6, 'xczxc', NULL, 'male', 'Nubian', '2026-09-12', 'brown', NULL, '2026-09-12', 'healthy', 'active', '2026-09-12 10:40:38', '2026-09-12 02:40:38', '2026-09-12 02:40:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','user') DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `role`, `status`, `is_verified`, `verification_token`, `email_verification_expires`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin', 'admin@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'admin', 'active', 1, NULL, NULL, '2026-08-19 08:39:58', '2026-08-19 08:39:58'),
(2, 'Farm Manager', 'manager', 'manager@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'manager', 'active', 1, NULL, NULL, '2026-08-19 08:39:58', '2026-08-19 08:39:58'),
(3, 'Juan Dela Cruz', 'jdelacruz', 'user@example.com', '$2y$10$HNfhClczEWBxcFuJwP53iu2Y75Tba7IEtmX8vX.1tp0dZ5EVt9CbO', 'user', 'inactive', 1, NULL, NULL, '2026-08-19 08:39:58', '2026-08-24 23:47:53'),
(4, 'Giovanny Barsobia', 'giovbars', 'giovbars@gmail.com', '$2y$10$Ms4WMr0zF4FPXH1lpYJTfOXyQPV6nKKMOHvp9lDEEcyBuiGLVpPLm', 'user', 'active', 1, NULL, NULL, '2026-08-19 08:46:22', '2026-08-19 08:46:22'),
(5, 'Silverio', 'silvs', 'silver@gmail.com', '$2y$10$rMLLfaQdOPey3iOQK6CyxuB8RSePLBSkt2CFfjkrNOm.IAvWGpDN.', 'user', 'active', 1, NULL, NULL, '2026-08-19 09:08:18', '2026-08-19 09:08:18'),
(6, 'Diether Labis', 'ditir', 'ditir@gmail.com', '$2y$10$j0hj8qHdN0VET8yPqTHU3ue.jBQzzNlxNtPBXZ3U6WKxMHzKwtkpu', 'user', 'active', 1, NULL, NULL, '2026-08-23 03:24:47', '2026-08-23 03:24:47'),
(7, 'Khai Gumonan', 'khai', 'khai@gmail.com', '$2y$10$hr6fEePiWVPZ16he6qQt/eS4Wydc41Vd0JjRPrzi.J4f0QcZJuTZC', 'user', 'active', 1, NULL, NULL, '2026-08-24 07:38:06', '2026-08-24 07:38:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_goat_id` (`goat_id`),
  ADD KEY `idx_activity_logs_goat_pk` (`goat_pk`);

--
-- Indexes for table `goats`
--
ALTER TABLE `goats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_owner_goat_id` (`user_id`,`goat_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_health_status` (`health_status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `goats`
--
ALTER TABLE `goats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `goats`
--
ALTER TABLE `goats`
  ADD CONSTRAINT `fk_goats_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
