-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2025 at 03:52 PM
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
-- Database: `crossword_game`
--

-- --------------------------------------------------------

--
-- Table structure for table `attempts`
--

CREATE TABLE `attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `puzzle_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `deadline_timestamp` datetime DEFAULT NULL,
  `answers` text NOT NULL,
  `time_taken` int(11) DEFAULT 0,
  `score` decimal(5,2) DEFAULT 0.00,
  `correct_answers` int(11) DEFAULT 0 COMMENT 'Number of correctly answered words',
  `correct_cells` int(11) DEFAULT 0 COMMENT 'Number of correct cells/letters',
  `wrong_answers` int(11) DEFAULT 0 COMMENT 'Number of incorrectly answered words',
  `wrong_cells` int(11) DEFAULT 0 COMMENT 'Number of wrong cells/letters',
  `total_questions` int(11) DEFAULT 0 COMMENT 'Total number of words in puzzle',
  `total_cells` int(11) DEFAULT 0 COMMENT 'Total number of fillable cells',
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attempt_status` enum('in_progress','completed','abandoned') DEFAULT 'in_progress',
  `locked` tinyint(1) DEFAULT 1,
  `result_published` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attempts`
--

INSERT INTO `attempts` (`id`, `student_id`, `puzzle_id`, `start_time`, `deadline_timestamp`, `answers`, `time_taken`, `score`, `correct_answers`, `correct_cells`, `wrong_answers`, `wrong_cells`, `total_questions`, `total_cells`, `completed_at`, `attempt_status`, `locked`, `result_published`) VALUES
(51, 5, 5, '2025-10-04 18:44:30', NULL, '{\"0-3\":\"\",\"1-0\":\"N\",\"1-1\":\"B\",\"1-2\":\"V\",\"1-3\":\"G\",\"1-4\":\"H\",\"1-5\":\"\",\"2-3\":\"\",\"2-4\":\"\",\"2-5\":\"\",\"2-8\":\"\",\"3-3\":\"\",\"3-4\":\"\",\"3-5\":\"\",\"3-6\":\"\",\"3-7\":\"\",\"3-8\":\"\",\"3-9\":\"\",\"3-10\":\"\",\"3-11\":\"\",\"4-4\":\"\",\"4-5\":\"\",\"4-7\":\"\",\"4-8\":\"\",\"4-10\":\"\",\"5-4\":\"\",\"5-7\":\"\",\"5-8\":\"\",\"5-9\":\"\",\"5-10\":\"\",\"6-7\":\"\",\"7-7\":\"\"}', 10, 0.00, 0, 0, 32, 0, 0, 0, '2025-10-04 13:14:41', 'completed', 1, 1),
(54, 5, 6, '2025-10-04 19:46:53', NULL, '{\"0-5\":\"G\",\"0-6\":\"\",\"0-9\":\"P\",\"1-2\":\"\",\"1-3\":\"\",\"1-5\":\"N\",\"1-6\":\"H\",\"1-7\":\"H\",\"1-9\":\"A\",\"2-2\":\"\",\"2-3\":\"\",\"2-4\":\"\",\"2-5\":\"\",\"2-6\":\"\",\"2-7\":\"\",\"2-8\":\"\",\"2-9\":\"S\",\"3-3\":\"\",\"3-5\":\"B\",\"3-6\":\"G\",\"3-7\":\"D\",\"3-8\":\"G\",\"3-9\":\"S\",\"3-10\":\"\",\"4-0\":\"K\",\"4-1\":\"A\",\"4-2\":\"L\",\"4-3\":\"I\",\"4-5\":\"\",\"4-6\":\"\",\"4-7\":\"\",\"4-9\":\"W\",\"5-2\":\"\",\"5-3\":\"\",\"5-4\":\"\",\"5-5\":\"\",\"5-6\":\"\",\"5-7\":\"\",\"5-9\":\"D\",\"5-10\":\"\",\"6-3\":\"\",\"6-4\":\"\",\"6-5\":\"\",\"6-6\":\"\",\"6-7\":\"\",\"6-8\":\"\",\"6-10\":\"\",\"7-3\":\"\",\"7-6\":\"\",\"7-7\":\"\",\"7-8\":\"\",\"7-9\":\"\",\"7-10\":\"\",\"8-10\":\"\"}', 66, 0.00, 0, 0, 54, 0, 0, 0, '2025-10-04 14:18:00', 'completed', 1, 0),
(56, 5, 4, '2025-10-05 18:48:33', NULL, '{\"0-9\":\"\",\"1-2\":\"\",\"1-7\":\"\",\"1-8\":\"\",\"1-9\":\"\",\"1-10\":\"\",\"2-2\":\"\",\"2-3\":\"\",\"2-5\":\"\",\"2-7\":\"\",\"2-9\":\"\",\"3-2\":\"\",\"3-3\":\"\",\"3-4\":\"\",\"3-5\":\"\",\"3-7\":\"\",\"3-8\":\"\",\"3-9\":\"\",\"3-10\":\"\",\"3-11\":\"\",\"4-0\":\"\",\"4-1\":\"\",\"4-2\":\"\",\"4-3\":\"\",\"4-4\":\"\",\"4-5\":\"\",\"4-6\":\"\",\"4-7\":\"\",\"4-8\":\"\",\"4-9\":\"\",\"4-10\":\"\",\"5-2\":\"\",\"5-3\":\"\",\"5-4\":\"\",\"5-5\":\"\",\"5-6\":\"\",\"5-7\":\"\",\"5-8\":\"\",\"5-9\":\"\",\"6-2\":\"\",\"6-3\":\"\",\"6-4\":\"\",\"6-5\":\"\",\"6-6\":\"\",\"6-7\":\"\",\"6-8\":\"\",\"6-9\":\"\",\"7-2\":\"\",\"7-4\":\"\",\"7-5\":\"\",\"7-6\":\"\",\"8-5\":\"\",\"8-6\":\"\"}', 0, 0.00, 0, 0, 0, 0, 0, 0, '2025-10-05 13:18:33', 'in_progress', 1, 0),
(57, 5, 7, '2025-10-05 19:06:50', NULL, '{\"0-1\":\"Y\",\"1-0\":\"\",\"1-1\":\"\",\"1-2\":\"\",\"2-1\":\"\"}', 24, 0.00, 0, 0, 5, 0, 0, 0, '2025-10-05 13:37:14', 'completed', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('success','failed') DEFAULT 'failed',
  `failure_reason` varchar(100) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_type`, `user_id`, `email`, `ip_address`, `user_agent`, `status`, `failure_reason`, `login_time`) VALUES
(1, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-03 23:14:08'),
(2, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-03 23:16:44'),
(3, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed', NULL, '2025-10-03 23:16:58'),
(4, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed', NULL, '2025-10-03 23:18:34'),
(5, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-03 23:18:46'),
(6, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-03 23:19:38'),
(7, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:13:41'),
(8, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:19:10'),
(9, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:19:38'),
(10, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:47:58'),
(11, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:54:10'),
(12, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 01:54:32'),
(13, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 02:00:25'),
(14, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 02:00:49'),
(15, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 09:54:09'),
(16, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 09:54:39'),
(17, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 09:55:02'),
(18, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 09:59:41'),
(19, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 10:00:03'),
(20, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 10:06:46'),
(21, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 10:12:36'),
(22, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 10:23:49'),
(23, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:09:11'),
(24, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:29:56'),
(25, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:31:27'),
(26, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:31:58'),
(27, 'student', 2, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:52:02'),
(28, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:54:17'),
(29, 'student', 0, 'raj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed', NULL, '2025-10-04 11:55:04'),
(30, 'student', 3, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:56:25'),
(31, 'student', 3, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 11:57:27'),
(32, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:03:53'),
(33, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:04:56'),
(34, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:12:35'),
(35, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:13:21'),
(36, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:17:02'),
(37, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:17:31'),
(38, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:25:09'),
(39, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:25:42'),
(40, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 12:26:05'),
(41, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 17:27:36'),
(42, 'teacher', 2, 'Raj@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'success', NULL, '2025-10-04 17:28:32'),
(43, 'teacher', 2, 'Raj@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'success', NULL, '2025-10-04 17:30:45'),
(44, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 17:31:04'),
(45, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'success', NULL, '2025-10-04 18:28:55'),
(46, 'student', 4, 'raj955198@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'success', NULL, '2025-10-04 18:38:37'),
(47, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 18:43:12'),
(48, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 18:44:24'),
(49, 'teacher', 2, 'Raj@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'success', NULL, '2025-10-04 19:39:51'),
(50, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 19:46:19'),
(51, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 21:36:07'),
(52, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-04 21:47:00'),
(53, 'teacher', 2, 'Raj@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:143.0) Gecko/20100101 Firefox/143.0', 'success', NULL, '2025-10-04 21:51:56'),
(54, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 14:09:52'),
(55, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 14:33:23'),
(56, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 17:20:52'),
(57, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:02:49'),
(58, 'teacher', 3, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:04:39'),
(59, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:06:32'),
(60, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:13:26'),
(61, 'teacher', 3, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:14:40'),
(62, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:20:57'),
(63, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 18:21:38'),
(64, 'student', 5, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:43:00'),
(65, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:43:52'),
(66, 'teacher', 3, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:47:09'),
(67, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:48:06'),
(68, 'student', 5, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:48:29'),
(69, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:49:59'),
(70, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:52:16'),
(71, 'teacher', 2, 'raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'failed', NULL, '2025-10-05 18:52:33'),
(72, 'teacher', 0, 'rajr@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'failed', NULL, '2025-10-05 18:53:03'),
(73, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:53:30'),
(74, 'student', 0, 'raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'failed', NULL, '2025-10-05 18:55:24'),
(75, 'student', 5, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 18:55:40'),
(76, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 19:05:33'),
(77, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 19:05:53'),
(78, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 19:06:08'),
(79, 'teacher', 2, 'Raj@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 19:06:17'),
(80, 'student', 5, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 19:06:47'),
(81, 'student', 5, 'raj955197@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 19:12:14'),
(82, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 19:15:13'),
(83, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'failed', NULL, '2025-10-05 19:15:48'),
(84, 'student', 5, 'raj955197@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'success', NULL, '2025-10-05 19:16:50'),
(85, 'student', 0, 'raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'failed', NULL, '2025-10-05 19:21:02'),
(86, 'teacher', 2, 'Raj@gmail.com', '192.168.137.88', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', 'success', NULL, '2025-10-05 19:21:22');

-- --------------------------------------------------------

--
-- Table structure for table `puzzles`
--

CREATE TABLE `puzzles` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `grid_size` int(11) DEFAULT 15,
  `time_limit` int(11) DEFAULT 30,
  `grid_data` text NOT NULL,
  `correct_answers` longtext DEFAULT NULL,
  `clues_across` text NOT NULL,
  `clues_down` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `puzzles`
--

INSERT INTO `puzzles` (`id`, `teacher_id`, `title`, `grid_size`, `time_limit`, `grid_data`, `correct_answers`, `clues_across`, `clues_down`, `is_active`, `is_published`, `created_at`) VALUES
(4, 2, 'CUSB TEST1', 12, 30, '[[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":2},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"G\",\"isBlack\":false,\"number\":7},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"K\",\"isBlack\":false,\"number\":8},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"U\",\"isBlack\":false,\"number\":4},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":6},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"C\",\"isBlack\":false,\"number\":12},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"M\",\"isBlack\":false,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":5},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":9},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"T\",\"isBlack\":false,\"number\":13},{\"letter\":\"O\",\"isBlack\":false,\"number\":null},{\"letter\":\"P\",\"isBlack\":false,\"number\":11},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"W\",\"isBlack\":false,\"number\":3},{\"letter\":\"O\",\"isBlack\":false,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"M\",\"isBlack\":false,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":15},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"P\",\"isBlack\":false,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":14},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"C\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}]]', NULL, '1. In Linux, /etc/shadow stores what information\r\n8. Linux distribution famous for ethical hacking\r\n9. The first process started in Linux.\r\n10. Linux command to compare two files.\r\n13. Which Linux command shows CPU and memory usage in real-time?', '2. Open-source 3D modeling software\r\n3. Which Linux command finds the location of an executable?\r\n4. Linux command to check kernel version.\r\n5. Which Linux file contains list of mounted filesystems?\r\n6. Default shell for most Linux distributions\r\n7. Which open-source software is alternative to Photoshop?\r\n11. Symbol used for piping commands.\r\n12. Linux command to schedule tasks automatically.\r\n14. Default package manager for Fedora.\r\n15. Command used to check available disk space', 0, 1, '2025-10-03 13:40:56'),
(5, 2, 'TEST', 12, 30, '[[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"G\",\"isBlack\":false,\"number\":5},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"K\",\"isBlack\":false,\"number\":6},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"U\",\"isBlack\":false,\"number\":3},{\"letter\":\"B\",\"isBlack\":false,\"number\":4},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"M\",\"isBlack\":false,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":8},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"P\",\"isBlack\":false,\"number\":1},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"W\",\"isBlack\":false,\"number\":2},{\"letter\":\"O\",\"isBlack\":false,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":9},{\"letter\":\"S\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"M\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"P\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":7},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"C\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}]]', '{\"12-8\":\"P\",\"12-9\":\"A\",\"12-10\":\"S\",\"12-11\":\"S\",\"12-12\":\"W\",\"12-13\":\"O\",\"12-14\":\"R\",\"12-15\":\"D\",\"12-16\":\"S\",\"13-12\":\"H\",\"14-12\":\"I\",\"15-12\":\"C\",\"16-12\":\"H\",\"10-9\":\"U\",\"11-9\":\"N\",\"13-9\":\"M\",\"14-9\":\"E\",\"10-10\":\"B\",\"11-10\":\"A\",\"13-10\":\"H\",\"9-8\":\"G\",\"10-8\":\"I\",\"11-8\":\"M\",\"10-5\":\"K\",\"10-6\":\"A\",\"10-7\":\"L\",\"14-13\":\"N\",\"14-14\":\"I\",\"14-15\":\"T\",\"11-13\":\"T\",\"13-13\":\"P\",\"13-15\":\"F\"}', '1. In Linux, /etc/shadow stores what information?\r\n6. Linux distribution famous for ethical hacking.\r\n7. The first process started in Linux.', '2. Which Linux command finds the location of an executable?\r\n3. Linux command to check kernel version.\r\n4. Default shell for most Linux distributions\r\n5. Which open-source software is alternative to Photoshop?\r\n8. Which Linux command shows CPU and memory usage in real-time?\r\n9. Command used to check available disk space.', 0, 0, '2025-10-04 05:59:23'),
(6, 2, 'NEW TEST', 11, 20, '[[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":6},{\"letter\":\"W\",\"isBlack\":false,\"number\":5},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"P\",\"isBlack\":false,\"number\":4},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":15},{\"letter\":\"M\",\"isBlack\":false,\"number\":2},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":3},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":1},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"U\",\"isBlack\":false,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":null},{\"letter\":\"U\",\"isBlack\":false,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"C\",\"isBlack\":false,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":13},{\"letter\":\"S\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"K\",\"isBlack\":false,\"number\":11},{\"letter\":\"A\",\"isBlack\":false,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"N\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"W\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"V\",\"isBlack\":false,\"number\":12},{\"letter\":\"A\",\"isBlack\":false,\"number\":9},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"C\",\"isBlack\":false,\"number\":null},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":10}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"D\",\"isBlack\":false,\"number\":null},{\"letter\":\"F\",\"isBlack\":false,\"number\":8},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"L\",\"isBlack\":false,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null},{\"letter\":\"V\",\"isBlack\":false,\"number\":14},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"R\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"B\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"W\",\"isBlack\":false,\"number\":7},{\"letter\":\"R\",\"isBlack\":false,\"number\":null},{\"letter\":\"I\",\"isBlack\":false,\"number\":null},{\"letter\":\"T\",\"isBlack\":false,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"E\",\"isBlack\":false,\"number\":null}]]', '{\"12-8\":\"N\",\"12-9\":\"A\",\"12-10\":\"U\",\"12-11\":\"T\",\"12-12\":\"I\",\"12-13\":\"L\",\"12-14\":\"U\",\"12-15\":\"S\",\"11-9\":\"M\",\"13-9\":\"R\",\"14-9\":\"I\",\"15-9\":\"A\",\"16-9\":\"D\",\"17-9\":\"B\",\"11-13\":\"B\",\"13-13\":\"E\",\"14-13\":\"N\",\"15-13\":\"D\",\"16-13\":\"E\",\"17-13\":\"R\",\"10-15\":\"P\",\"11-15\":\"A\",\"13-15\":\"S\",\"14-15\":\"W\",\"15-15\":\"D\",\"10-12\":\"W\",\"11-12\":\"H\",\"13-12\":\"C\",\"14-12\":\"H\",\"10-11\":\"F\",\"11-11\":\"S\",\"13-11\":\"A\",\"14-11\":\"B\",\"17-12\":\"W\",\"17-14\":\"I\",\"17-15\":\"T\",\"17-16\":\"E\",\"16-10\":\"F\",\"16-11\":\"I\",\"16-12\":\"L\",\"15-10\":\"R\",\"15-11\":\"C\",\"15-12\":\"H\",\"15-16\":\"F\",\"16-16\":\"R\",\"18-16\":\"E\",\"14-6\":\"K\",\"14-7\":\"A\",\"14-8\":\"L\",\"15-8\":\"V\",\"13-14\":\"F\",\"13-16\":\"F\",\"16-14\":\"V\",\"11-8\":\"L\"}', '1. Default file manager in GNOME\r\n7. In chmod 644, the owner has __ permission\r\n8. In Linux, every device is represented as a __.\r\n9. Linux distribution that follows a rolling release model\r\n11. Linux distribution focused on penetration testing.\r\n12. Directory containing system logs.\r\n13. GPL license is maintained by __ Foundation', '2. Open-source database forked from MySQL\r\n3. Open-source 3D graphics software.\r\n4. File that stores system-wide user accounts.\r\n5. Command to find the absolute path of a command.\r\n6. File in Linux that lists mounted filesystems\r\n10. Command to display memory usage\r\n14. Linux editor that starts in command mode\r\n15. Symbolic link in Linux is created using __ command.', 0, 0, '2025-10-04 14:15:40'),
(7, 2, 'Hui', 3, 10, '[[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"S\",\"isBlack\":false,\"number\":2},{\"letter\":\"\",\"isBlack\":true,\"number\":null}],[{\"letter\":\"W\",\"isBlack\":false,\"number\":1},{\"letter\":\"H\",\"isBlack\":false,\"number\":null},{\"letter\":\"Y\",\"isBlack\":false,\"number\":null}],[{\"letter\":\"\",\"isBlack\":true,\"number\":null},{\"letter\":\"U\",\"isBlack\":false,\"number\":null},{\"letter\":\"\",\"isBlack\":true,\"number\":null}]]', '{\"12-11\":\"W\",\"12-12\":\"H\",\"12-13\":\"Y\",\"11-12\":\"S\",\"13-12\":\"U\"}', '1. What', '2. Y', 1, 0, '2025-10-05 13:25:02');

-- --------------------------------------------------------

--
-- Table structure for table `registration_logs`
--

CREATE TABLE `registration_logs` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `registration_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `program` varchar(50) NOT NULL,
  `results_published` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Student account status (1=active, 0=disabled)',
  `can_retry_puzzle_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `email`, `password`, `program`, `results_published`, `is_active`, `can_retry_puzzle_id`, `created_at`) VALUES
(1, 'Demo Student', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MSc AI', 0, 1, NULL, '2025-10-02 19:21:09'),
(5, 'Raj', 'raj955197@gmail.com', '$2y$12$lCzp80Mf7TICew04aMIxNOLlFlnK8hPqi8R7liiHx/ykDyKX1.ZuO', 'MSc AI', 0, 1, NULL, '2025-10-04 13:14:11');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Demo Teacher', 'teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-10-02 19:21:09'),
(2, 'raj', 'Raj@gmail.com', '$2y$10$WSzAq58i9ohNd6.8uP2mLO/SLq/6SFXj9.nxkwDlyMrwnbBSxgm7O', '2025-10-02 19:34:36'),
(3, 'Raja', 'raj955197@gmail.com', '$2y$12$CrXhwR50j53yeVK/j.w2aeOg90o81.t0yCZJ4xg4k8ricTmW4kpnS', '2025-10-05 12:34:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attempts`
--
ALTER TABLE `attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attempt` (`student_id`,`puzzle_id`),
  ADD KEY `puzzle_id` (`puzzle_id`),
  ADD KEY `idx_student_attempts` (`student_id`,`puzzle_id`),
  ADD KEY `idx_attempt_status` (`student_id`,`puzzle_id`,`attempt_status`),
  ADD KEY `idx_student_puzzle_status` (`student_id`,`puzzle_id`,`attempt_status`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_type`,`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_time` (`login_time`),
  ADD KEY `idx_ip_time` (`ip_address`,`login_time`);

--
-- Indexes for table `puzzles`
--
ALTER TABLE `puzzles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `registration_logs`
--
ALTER TABLE `registration_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_time` (`registration_time`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_results_published` (`results_published`),
  ADD KEY `idx_active_lookup` (`is_active`,`name`,`program`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attempts`
--
ALTER TABLE `attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `puzzles`
--
ALTER TABLE `puzzles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registration_logs`
--
ALTER TABLE `registration_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attempts`
--
ALTER TABLE `attempts`
  ADD CONSTRAINT `attempts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attempts_ibfk_2` FOREIGN KEY (`puzzle_id`) REFERENCES `puzzles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `puzzles`
--
ALTER TABLE `puzzles`
  ADD CONSTRAINT `puzzles_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
