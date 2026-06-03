-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 01:17 AM
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
-- Database: `bisure_lets_chat`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_chats`
--

CREATE TABLE `archived_chats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_reasons`
--

CREATE TABLE `block_reasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `blocked_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `block_reasons`
--

INSERT INTO `block_reasons` (`id`, `user_id`, `reason`, `blocked_by`, `created_at`) VALUES
(1, 1, 'tooooo much', 2, '2026-06-03 18:03:52'),
(2, 1, 'when is it then', 2, '2026-06-03 20:29:01');

-- --------------------------------------------------------

--
-- Table structure for table `calls`
--

CREATE TABLE `calls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `caller_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `call_type` enum('voice','video') NOT NULL,
  `status` enum('ringing','answered','missed','declined','ended') DEFAULT 'ringing',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `calls`
--

INSERT INTO `calls` (`id`, `caller_id`, `receiver_id`, `call_type`, `status`, `started_at`, `ended_at`, `created_at`) VALUES
(1, 2, 1, 'video', 'ended', '2026-06-02 05:00:51', '2026-06-02 23:46:17', '2026-06-02 02:00:42'),
(2, 2, 1, 'voice', 'ended', '2026-06-02 05:01:36', '2026-06-02 05:02:14', '2026-06-02 02:01:31'),
(3, 1, 2, 'video', 'ended', '2026-06-02 05:02:29', '2026-06-02 05:02:54', '2026-06-02 02:02:29'),
(4, 1, 2, 'video', 'ended', '2026-06-02 05:03:03', '2026-06-02 05:03:18', '2026-06-02 02:02:58'),
(5, 2, 1, 'video', 'ended', '2026-06-02 05:03:26', '2026-06-02 05:03:29', '2026-06-02 02:03:21'),
(6, 1, 2, 'video', 'ended', '2026-06-02 05:03:37', '2026-06-02 05:03:51', '2026-06-02 02:03:33'),
(7, 2, 1, 'video', 'ended', '2026-06-02 05:09:54', '2026-06-02 05:10:18', '2026-06-02 02:09:47'),
(8, 1, 2, 'video', 'ended', '2026-06-02 05:39:11', '2026-06-02 05:39:25', '2026-06-02 02:39:11'),
(9, 1, 2, 'voice', 'ended', '2026-06-02 05:39:27', '2026-06-02 05:39:37', '2026-06-02 02:39:27'),
(10, 1, 2, 'video', 'ended', '2026-06-02 05:58:43', '2026-06-02 05:58:55', '2026-06-02 02:58:43'),
(11, 1, 2, 'video', 'ended', '2026-06-02 05:59:00', '2026-06-02 05:59:09', '2026-06-02 02:59:00'),
(12, 2, 1, 'video', 'declined', '2026-06-02 05:59:22', '2026-06-02 05:59:32', '2026-06-02 02:59:22'),
(13, 2, 1, 'video', 'ended', '2026-06-02 05:59:40', '2026-06-02 05:59:50', '2026-06-02 02:59:40'),
(14, 2, 1, 'video', 'ended', '2026-06-02 05:59:52', '2026-06-02 06:00:09', '2026-06-02 02:59:52'),
(15, 2, 1, 'video', 'declined', '2026-06-02 06:00:19', '2026-06-02 06:00:26', '2026-06-02 03:00:19'),
(16, 2, 1, 'video', 'ended', '2026-06-02 06:00:33', '2026-06-02 06:00:40', '2026-06-02 03:00:33'),
(17, 2, 1, 'video', 'ended', '2026-06-02 06:02:10', '2026-06-02 06:02:19', '2026-06-02 03:02:10'),
(18, 2, 1, 'video', 'declined', '2026-06-02 06:02:38', '2026-06-02 06:02:43', '2026-06-02 03:02:38'),
(19, 2, 1, 'video', 'ended', '2026-06-02 06:02:52', '2026-06-02 06:03:07', '2026-06-02 03:02:52'),
(20, 2, 1, 'video', 'ended', '2026-06-02 06:03:18', '2026-06-02 06:03:34', '2026-06-02 03:03:18'),
(21, 2, 1, 'video', 'ended', '2026-06-02 06:03:53', '2026-06-02 06:04:19', '2026-06-02 03:03:53'),
(22, 2, 1, 'video', 'ended', '2026-06-02 06:04:43', '2026-06-02 06:05:06', '2026-06-02 03:04:43'),
(23, 2, 1, 'video', 'ended', '2026-06-02 06:05:25', '2026-06-02 06:05:34', '2026-06-02 03:05:25'),
(24, 2, 1, 'video', 'ended', '2026-06-02 06:06:09', '2026-06-02 06:06:23', '2026-06-02 03:06:09'),
(25, 2, 1, 'video', 'ended', '2026-06-02 06:10:34', '2026-06-02 06:10:49', '2026-06-02 03:10:34'),
(26, 2, 1, 'video', 'ended', '2026-06-02 06:13:36', '2026-06-02 06:22:01', '2026-06-02 03:13:36'),
(27, 2, 1, 'video', 'declined', '2026-06-02 06:22:28', '2026-06-02 06:22:38', '2026-06-02 03:22:15'),
(28, 2, 1, 'video', 'ended', '2026-06-02 06:22:47', '2026-06-02 06:23:05', '2026-06-02 03:22:47'),
(29, 2, 1, 'video', 'ended', '2026-06-02 06:27:03', '2026-06-02 06:27:16', '2026-06-02 03:26:45'),
(30, 2, 1, 'voice', 'ended', '2026-06-02 06:27:24', '2026-06-02 06:27:27', '2026-06-02 03:27:20'),
(31, 2, 1, 'voice', 'ended', '2026-06-02 06:27:45', '2026-06-02 06:27:56', '2026-06-02 03:27:34'),
(32, 2, 1, 'video', 'ended', '2026-06-02 06:28:32', '2026-06-02 06:28:40', '2026-06-02 03:28:16'),
(33, 2, 1, 'video', 'declined', '2026-06-02 06:29:26', '2026-06-02 06:29:36', '2026-06-02 03:29:17'),
(34, 2, 1, 'video', 'declined', '2026-06-02 06:29:39', '2026-06-02 06:29:48', '2026-06-02 03:29:39'),
(35, 2, 1, 'video', 'answered', '2026-06-02 06:30:01', NULL, '2026-06-02 03:29:53'),
(36, 2, 1, 'video', 'ended', '2026-06-02 06:30:29', '2026-06-02 06:30:32', '2026-06-02 03:30:13'),
(37, 2, 1, 'video', 'missed', '2026-06-02 06:34:38', '2026-06-02 06:34:50', '2026-06-02 03:34:38'),
(38, 2, 1, 'video', 'ended', '2026-06-02 06:34:59', '2026-06-02 06:35:32', '2026-06-02 03:34:59'),
(39, 2, 1, 'voice', 'ended', '2026-06-02 06:36:11', '2026-06-02 06:36:24', '2026-06-02 03:36:11'),
(40, 2, 1, 'video', 'ended', '2026-06-02 06:38:06', '2026-06-02 06:38:18', '2026-06-02 03:38:06'),
(41, 2, 1, 'video', 'ended', '2026-06-02 06:38:50', '2026-06-02 06:39:04', '2026-06-02 03:38:50'),
(42, 2, 1, 'video', 'missed', '2026-06-02 06:40:25', '2026-06-02 06:41:32', '2026-06-02 03:40:25'),
(43, 2, 1, 'video', 'missed', '2026-06-02 06:42:30', '2026-06-02 06:43:36', '2026-06-02 03:42:30'),
(44, 2, 1, 'video', 'ringing', '2026-06-02 06:44:15', NULL, '2026-06-02 03:44:15'),
(45, 2, 1, 'video', 'ended', '2026-06-02 06:46:02', '2026-06-02 06:46:12', '2026-06-02 03:46:02'),
(46, 2, 1, 'video', 'ended', '2026-06-02 06:46:25', '2026-06-02 06:46:35', '2026-06-02 03:46:20'),
(47, 2, 1, 'video', 'ended', '2026-06-02 06:46:57', '2026-06-02 06:47:00', '2026-06-02 03:46:47'),
(48, 1, 2, 'video', 'declined', '2026-06-02 06:47:04', '2026-06-02 06:47:15', '2026-06-02 03:47:04'),
(49, 1, 2, 'video', 'ended', '2026-06-02 06:47:29', '2026-06-02 06:47:34', '2026-06-02 03:47:20'),
(50, 2, 1, 'video', 'ended', '2026-06-02 06:50:00', '2026-06-02 06:50:14', '2026-06-02 03:50:00'),
(51, 2, 1, 'video', 'declined', '2026-06-02 06:50:37', '2026-06-02 06:50:49', '2026-06-02 03:50:23'),
(52, 2, 1, 'video', 'ended', '2026-06-02 06:51:00', '2026-06-02 06:51:12', '2026-06-02 03:51:00'),
(53, 2, 1, 'video', 'ringing', '2026-06-02 06:51:18', NULL, '2026-06-02 03:51:18'),
(54, 2, 1, 'video', 'ended', '2026-06-02 06:51:37', '2026-06-02 06:51:43', '2026-06-02 03:51:32'),
(55, 2, 1, 'video', 'ended', '2026-06-02 06:51:57', '2026-06-02 06:52:01', '2026-06-02 03:51:49'),
(56, 2, 1, 'video', 'ended', '2026-06-02 06:52:28', '2026-06-02 06:52:44', '2026-06-02 03:52:28'),
(57, 2, 1, 'video', 'ringing', '2026-06-02 06:52:55', NULL, '2026-06-02 03:52:55'),
(58, 2, 1, 'video', 'ended', '2026-06-02 06:53:17', '2026-06-02 06:53:33', '2026-06-02 03:53:17'),
(59, 2, 1, 'video', 'ended', '2026-06-02 06:54:56', '2026-06-02 06:55:10', '2026-06-02 03:54:49'),
(60, 2, 1, 'voice', 'ended', '2026-06-02 06:55:22', '2026-06-02 06:55:30', '2026-06-02 03:55:13'),
(61, 2, 1, 'video', 'declined', '2026-06-02 06:55:59', '2026-06-02 06:56:07', '2026-06-02 03:55:51'),
(62, 2, 1, 'video', 'declined', '2026-06-02 06:56:18', '2026-06-02 06:57:15', '2026-06-02 03:56:18'),
(63, 2, 1, 'video', 'ended', '2026-06-02 06:57:53', '2026-06-02 06:58:04', '2026-06-02 03:57:53'),
(64, 2, 1, 'video', 'declined', '2026-06-02 06:58:44', '2026-06-02 06:59:03', '2026-06-02 03:58:44'),
(65, 2, 1, 'video', 'declined', '2026-06-02 07:01:54', '2026-06-02 07:01:59', '2026-06-02 04:01:49'),
(66, 2, 1, 'video', 'ringing', '2026-06-02 07:02:12', NULL, '2026-06-02 04:02:12'),
(67, 2, 1, 'video', 'declined', '2026-06-02 07:02:55', '2026-06-02 07:03:19', '2026-06-02 04:02:55'),
(68, 2, 1, 'video', 'ended', '2026-06-02 07:07:31', '2026-06-02 07:07:36', '2026-06-02 04:07:26'),
(69, 2, 1, 'video', 'declined', '2026-06-02 07:07:59', '2026-06-02 07:08:05', '2026-06-02 04:07:42'),
(70, 2, 1, 'video', 'ended', '2026-06-02 07:10:28', '2026-06-02 07:10:32', '2026-06-02 04:10:24'),
(71, 2, 1, 'video', 'ended', '2026-06-02 07:10:38', '2026-06-02 07:10:51', '2026-06-02 04:10:38'),
(72, 2, 1, 'video', 'ended', '2026-06-02 07:11:44', '2026-06-02 07:11:54', '2026-06-02 04:11:28'),
(73, 2, 1, 'video', 'ended', '2026-06-02 07:12:06', '2026-06-02 07:12:18', '2026-06-02 04:12:06'),
(74, 2, 1, 'video', 'declined', '2026-06-02 07:12:31', '2026-06-02 07:12:40', '2026-06-02 04:12:20'),
(75, 2, 1, 'video', 'declined', '2026-06-02 07:13:04', '2026-06-02 07:13:15', '2026-06-02 04:13:04'),
(76, 2, 1, 'video', 'declined', '2026-06-02 07:18:46', '2026-06-02 07:19:01', '2026-06-02 04:18:42'),
(77, 2, 1, 'video', 'declined', '2026-06-02 07:19:09', '2026-06-02 07:19:19', '2026-06-02 04:19:03'),
(78, 2, 1, 'video', 'answered', '2026-06-02 07:19:27', NULL, '2026-06-02 04:19:23'),
(79, 2, 1, 'video', 'ended', '2026-06-02 07:19:40', '2026-06-02 07:19:52', '2026-06-02 04:19:36'),
(80, 2, 1, 'video', 'ended', '2026-06-02 07:20:00', '2026-06-02 07:20:14', '2026-06-02 04:20:00'),
(81, 2, 1, 'video', 'ended', '2026-06-02 07:20:21', '2026-06-02 07:20:25', '2026-06-02 04:20:16'),
(82, 2, 1, 'video', 'ended', '2026-06-02 07:20:41', '2026-06-02 07:20:45', '2026-06-02 04:20:36'),
(83, 2, 1, 'video', 'ended', '2026-06-02 07:21:48', '2026-06-02 07:21:53', '2026-06-02 04:21:42'),
(84, 2, 1, 'video', 'ended', '2026-06-02 07:23:19', '2026-06-02 07:23:24', '2026-06-02 04:23:14'),
(85, 2, 1, 'video', 'declined', '2026-06-02 07:23:37', '2026-06-02 07:23:48', '2026-06-02 04:23:32'),
(86, 2, 1, 'video', 'ended', '2026-06-02 07:27:40', '2026-06-02 07:27:45', '2026-06-02 04:27:34'),
(87, 2, 1, 'video', 'ended', '2026-06-02 07:27:56', '2026-06-02 07:28:00', '2026-06-02 04:27:51'),
(88, 2, 1, 'video', 'ended', '2026-06-02 07:29:06', '2026-06-02 07:29:10', '2026-06-02 04:29:00'),
(89, 2, 1, 'video', 'ended', '2026-06-02 07:30:20', '2026-06-02 07:30:24', '2026-06-02 04:30:13'),
(90, 2, 1, 'video', 'ended', '2026-06-02 07:43:14', '2026-06-02 07:43:24', '2026-06-02 04:43:07'),
(91, 2, 1, 'video', 'declined', '2026-06-02 07:43:37', '2026-06-02 07:43:43', '2026-06-02 04:43:32'),
(92, 2, 1, 'video', 'ended', '2026-06-02 07:43:54', '2026-06-02 07:44:03', '2026-06-02 04:43:49'),
(93, 2, 1, 'video', 'declined', '2026-06-02 07:44:51', '2026-06-02 07:44:57', '2026-06-02 04:44:45'),
(94, 2, 1, 'video', 'ended', '2026-06-02 07:45:08', '2026-06-02 07:45:14', '2026-06-02 04:45:02'),
(95, 2, 1, 'video', 'ended', '2026-06-02 07:47:07', '2026-06-02 07:48:34', '2026-06-02 04:47:00'),
(96, 2, 1, 'video', 'ended', '2026-06-02 07:51:37', '2026-06-02 07:51:45', '2026-06-02 04:51:30'),
(97, 2, 1, 'video', 'ended', '2026-06-02 07:52:30', '2026-06-02 07:52:54', '2026-06-02 04:52:25'),
(98, 2, 1, 'video', 'ended', '2026-06-02 07:53:02', '2026-06-02 07:53:47', '2026-06-02 04:52:58'),
(99, 2, 1, 'video', 'ended', '2026-06-02 07:55:15', '2026-06-02 07:55:26', '2026-06-02 04:55:10'),
(100, 2, 1, 'video', 'ended', '2026-06-02 07:58:28', '2026-06-02 07:58:34', '2026-06-02 04:57:49'),
(101, 2, 1, 'video', 'ended', '2026-06-02 08:01:00', '2026-06-02 08:01:12', '2026-06-02 05:00:54'),
(102, 2, 1, 'video', 'ended', '2026-06-02 08:01:59', '2026-06-02 08:02:07', '2026-06-02 05:01:53'),
(103, 1, 2, 'video', 'answered', '2026-06-02 08:02:19', NULL, '2026-06-02 05:02:17'),
(104, 1, 2, 'video', 'declined', '2026-06-02 08:02:36', '2026-06-02 08:02:47', '2026-06-02 05:02:33'),
(105, 1, 2, 'video', 'ended', '2026-06-02 08:04:25', '2026-06-02 08:04:30', '2026-06-02 05:04:20'),
(106, 1, 2, 'video', 'declined', '2026-06-02 08:05:13', '2026-06-02 08:05:20', '2026-06-02 05:05:10'),
(107, 2, 1, 'video', 'ended', '2026-06-02 08:05:33', '2026-06-02 08:05:47', '2026-06-02 05:05:28'),
(108, 1, 2, 'video', 'declined', '2026-06-02 08:06:04', '2026-06-02 08:06:12', '2026-06-02 05:06:01'),
(109, 1, 2, 'video', 'ended', '2026-06-02 08:09:46', '2026-06-02 08:10:03', '2026-06-02 05:09:28'),
(110, 1, 2, 'video', 'declined', '2026-06-02 08:10:16', '2026-06-02 08:10:32', '2026-06-02 05:10:12'),
(111, 1, 2, 'video', 'declined', '2026-06-02 08:10:52', '2026-06-02 08:10:57', '2026-06-02 05:10:45'),
(112, 1, 2, 'voice', 'ended', '2026-06-02 08:11:24', '2026-06-02 08:11:34', '2026-06-02 05:11:16'),
(113, 2, 1, 'video', 'ended', '2026-06-02 08:11:51', '2026-06-02 08:11:57', '2026-06-02 05:11:42'),
(114, 1, 2, 'video', 'answered', '2026-06-02 08:14:56', '2026-06-02 08:14:46', '2026-06-02 05:14:34'),
(115, 1, 2, 'video', 'ended', '2026-06-02 08:15:22', '2026-06-02 08:15:33', '2026-06-02 05:15:13'),
(116, 1, 2, 'video', 'declined', '2026-06-02 08:15:40', '2026-06-02 08:15:46', '2026-06-02 05:15:36'),
(117, 2, 1, 'video', 'declined', '2026-06-02 08:18:07', '2026-06-02 08:18:18', '2026-06-02 05:18:07'),
(118, 2, 1, 'voice', 'declined', '2026-06-02 08:18:24', '2026-06-02 08:18:29', '2026-06-02 05:18:20'),
(119, 1, 2, 'video', 'ended', '2026-06-02 08:20:06', '2026-06-02 08:20:23', '2026-06-02 05:20:02'),
(120, 1, 2, 'video', 'ended', '2026-06-02 08:20:41', '2026-06-02 08:20:54', '2026-06-02 05:20:36'),
(121, 1, 2, 'video', 'ended', '2026-06-02 08:21:01', '2026-06-02 08:21:09', '2026-06-02 05:20:59'),
(122, 2, 1, 'video', 'ended', '2026-06-02 08:21:33', '2026-06-02 08:21:42', '2026-06-02 05:21:29'),
(123, 1, 2, 'video', 'ended', '2026-06-02 08:22:00', '2026-06-02 08:22:14', '2026-06-02 05:21:52'),
(124, 1, 2, 'video', 'declined', '2026-06-02 08:22:44', '2026-06-02 08:22:50', '2026-06-02 05:22:40'),
(125, 2, 1, 'video', 'ended', '2026-06-02 08:30:39', '2026-06-02 08:30:48', '2026-06-02 05:30:39'),
(126, 2, 1, 'video', 'ended', '2026-06-02 08:31:03', '2026-06-02 08:31:08', '2026-06-02 05:30:56'),
(127, 1, 2, 'video', 'ended', '2026-06-02 08:31:22', '2026-06-02 08:31:43', '2026-06-02 05:31:18'),
(128, 1, 2, 'video', 'declined', '2026-06-02 08:31:57', '2026-06-02 08:32:15', '2026-06-02 05:31:55'),
(129, 1, 2, 'video', 'declined', '2026-06-02 08:33:07', '2026-06-02 08:33:13', '2026-06-02 05:33:00'),
(130, 1, 2, 'video', 'ended', '2026-06-02 08:33:28', '2026-06-02 08:33:39', '2026-06-02 05:33:22'),
(131, 2, 1, 'video', 'ended', '2026-06-02 08:34:16', '2026-06-02 08:34:20', '2026-06-02 05:34:11'),
(132, 2, 1, 'video', 'ended', '2026-06-02 08:34:36', '2026-06-02 08:34:40', '2026-06-02 05:34:31'),
(133, 1, 2, 'video', 'answered', '2026-06-02 08:34:47', NULL, '2026-06-02 05:34:43'),
(134, 1, 2, 'video', 'declined', '2026-06-02 08:36:07', '2026-06-02 08:36:13', '2026-06-02 05:35:56'),
(135, 1, 2, 'video', 'ended', '2026-06-02 08:36:29', '2026-06-02 08:36:37', '2026-06-02 05:36:25'),
(136, 1, 2, 'video', 'ended', '2026-06-02 08:43:29', '2026-06-02 08:43:46', '2026-06-02 05:43:23'),
(137, 2, 1, 'video', 'answered', '2026-06-02 08:44:05', NULL, '2026-06-02 05:43:59'),
(138, 2, 1, 'video', 'ended', '2026-06-02 08:44:25', '2026-06-02 08:44:29', '2026-06-02 05:44:21'),
(139, 2, 1, 'video', 'ringing', '2026-06-02 23:46:26', NULL, '2026-06-02 20:46:26'),
(140, 2, 1, 'video', 'ended', '2026-06-03 11:03:24', '2026-06-03 11:05:25', '2026-06-03 08:03:24');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `contact_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `user_id`, `contact_user_id`, `created_at`) VALUES
(1, 2, 1, '2026-05-31 22:56:17'),
(2, 2, 2, '2026-06-03 14:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_type` enum('private','group') DEFAULT 'private',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `conversation_type`, `created_by`, `created_at`) VALUES
(2, 'group', 2, '2026-05-31 20:08:27'),
(3, 'group', 2, '2026-05-31 20:12:47'),
(4, 'group', 2, '2026-05-31 20:14:23'),
(5, 'group', 2, '2026-05-31 21:38:06'),
(6, 'private', 2, '2026-05-31 22:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `joined_at`) VALUES
(4, 2, 1, '2026-05-31 20:08:27'),
(5, 3, 2, '2026-05-31 20:12:47'),
(6, 3, 1, '2026-05-31 20:12:47'),
(7, 4, 2, '2026-05-31 20:14:23'),
(9, 5, 2, '2026-05-31 21:38:06'),
(10, 5, 1, '2026-05-31 21:38:06'),
(11, 6, 2, '2026-05-31 22:56:17'),
(12, 6, 1, '2026-05-31 22:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message_text` longtext NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `verification_token`, `verified_at`, `created_at`) VALUES
(1, 1, '44280bf1ba2d22216352e7c0cba17c73b1a2bd49a30259e995c626e046f0c5d3', NULL, '2026-05-31 19:09:24'),
(2, 2, '6410c6968451a3006dbcde7782c5eaee49fff8a9a08ffe94d5b3c5bb41759644', NULL, '2026-05-31 19:09:52');

-- --------------------------------------------------------

--
-- Table structure for table `groups_chat`
--

CREATE TABLE `groups_chat` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_photo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups_chat`
--

INSERT INTO `groups_chat` (`id`, `conversation_id`, `group_name`, `group_photo`, `description`, `created_by`, `created_at`) VALUES
(2, 2, 'Bisure222', NULL, 'another one', 2, '2026-05-31 20:08:27'),
(3, 3, 'GROUP 1', 'group_3_1780364043.png', 'CODING', 2, '2026-05-31 20:12:47'),
(4, 4, 'fff', 'group_4_1780363302.png', 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrffffffffffffffff', 2, '2026-05-31 20:14:23'),
(5, 5, '2two', 'group_5_1780364085.png', 'mine yudnmdvsj  nnnni n', 2, '2026-05-31 21:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(4, 2, 1, 'admin', '2026-05-31 20:08:27'),
(5, 3, 2, 'admin', '2026-05-31 20:12:47'),
(6, 3, 1, 'member', '2026-05-31 20:12:47'),
(7, 4, 2, 'admin', '2026-05-31 20:14:23'),
(9, 5, 2, 'admin', '2026-05-31 21:38:06'),
(10, 5, 1, 'admin', '2026-05-31 21:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_fullname` varchar(150) NOT NULL,
  `user_username` varchar(50) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_phone` varchar(30) DEFAULT NULL,
  `subject` varchar(255) DEFAULT 'Support Inquiry',
  `message` longtext NOT NULL,
  `status` enum('pending','read','replied','resolved','closed') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_response` longtext DEFAULT NULL,
  `responded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `user_id`, `user_fullname`, `user_username`, `user_email`, `user_phone`, `subject`, `message`, `status`, `priority`, `assigned_to`, `admin_response`, `responded_by`, `responded_at`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'isa', 'isak', 'byaruhangangaisamelk@gmail.com', '0757000001', 'Support Inquiry', 'this sysysystem', 'replied', 'medium', NULL, 'welll', 2, '2026-06-03 17:32:42', NULL, '2026-06-01 22:58:19', '2026-06-03 14:32:42'),
(2, 2, 'isa', 'isak', 'byaruhangangaisamelk@gmail.com', '0757000001', 'Support Inquiry', 'again ertyuiefghjkld\r\ncvbnm,dfghjkwtyui', 'replied', 'medium', NULL, 'like that', 2, '2026-06-02 02:15:44', NULL, '2026-06-01 23:07:23', '2026-06-01 23:15:44'),
(3, 2, 'isa', 'isak', 'byaruhangangaisamelk@gmail.com', '0757000001', 'Support Inquiry', 'wertyuioasdfghjklzxcvbnm', 'replied', 'medium', NULL, 'wertyuiop', 2, '2026-06-02 02:41:56', NULL, '2026-06-01 23:40:31', '2026-06-01 23:41:56');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `message_type` enum('text','image','video','file','voice') DEFAULT 'text',
  `message_text` longtext DEFAULT NULL,
  `reply_to_id` bigint(20) UNSIGNED DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message_type`, `message_text`, `reply_to_id`, `attachment_path`, `is_edited`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 4, 2, 'text', 'iiiiii', NULL, NULL, 0, 1, '2026-05-31 20:55:28', NULL),
(2, 4, 2, 'text', 'fffff', NULL, NULL, 0, 0, '2026-05-31 20:58:50', NULL),
(3, 4, 2, 'text', 'rrrrrrr', NULL, NULL, 0, 0, '2026-05-31 21:04:00', NULL),
(4, 4, 2, 'text', 'rtyuiop', NULL, NULL, 0, 0, '2026-05-31 21:11:49', NULL),
(5, 6, 2, 'text', 'This message was deleted', NULL, NULL, 0, 1, '2026-05-31 23:04:32', '2026-06-01 14:27:24'),
(6, 6, 2, 'text', 'dc meka??', NULL, NULL, 1, 0, '2026-05-31 23:04:43', '2026-06-01 20:09:23'),
(7, 6, 2, 'text', 'sssss', NULL, NULL, 0, 0, '2026-06-01 14:21:11', NULL),
(8, 6, 2, 'text', 'oh mg well', NULL, NULL, 1, 0, '2026-06-01 14:21:21', '2026-06-01 14:21:30'),
(9, 6, 2, 'text', 'fffff', NULL, NULL, 0, 0, '2026-06-01 14:28:13', NULL),
(10, 6, 2, 'text', 'mmmmmmmmmmmmmmmmmmmmmm', NULL, NULL, 1, 0, '2026-06-01 14:28:51', '2026-06-01 14:29:09'),
(11, 6, 2, 'text', 'sss', NULL, NULL, 0, 0, '2026-06-01 16:42:33', NULL),
(12, 6, 2, 'text', 'Yes', NULL, NULL, 0, 0, '2026-06-01 16:53:30', NULL),
(13, 6, 2, 'text', 'utututututut', NULL, NULL, 0, 0, '2026-06-01 17:02:51', NULL),
(14, 6, 2, 'text', 'This message was deleted', NULL, NULL, 0, 1, '2026-06-01 17:03:21', '2026-06-01 20:08:32'),
(15, 6, 2, 'text', 'Yes yes', NULL, NULL, 0, 0, '2026-06-01 17:03:30', NULL),
(16, 6, 1, 'text', 'Oh mg', NULL, NULL, 0, 0, '2026-06-01 17:04:45', NULL),
(17, 6, 2, 'text', 'yyyyyyyy', NULL, NULL, 0, 0, '2026-06-01 17:05:07', NULL),
(18, 6, 1, 'text', 'Hehe', NULL, NULL, 0, 0, '2026-06-01 17:05:18', NULL),
(19, 6, 1, 'text', 'Hello is', NULL, NULL, 0, 0, '2026-06-01 18:29:39', NULL),
(20, 6, 1, 'text', 'Hello', NULL, NULL, 0, 0, '2026-06-01 18:39:16', NULL),
(21, 6, 1, 'text', 'How are you', NULL, NULL, 0, 0, '2026-06-01 18:39:41', NULL),
(22, 6, 1, 'text', 'Yess', NULL, NULL, 0, 0, '2026-06-01 18:40:22', NULL),
(23, 6, 2, 'text', 'hello', NULL, NULL, 0, 0, '2026-06-01 20:04:58', NULL),
(24, 6, 2, 'text', 'hello', NULL, NULL, 0, 1, '2026-06-01 20:08:00', NULL),
(25, 6, 2, 'text', 'welll go', 11, NULL, 0, 0, '2026-06-01 20:09:43', NULL),
(26, 6, 1, 'text', 'Well now', NULL, NULL, 0, 0, '2026-06-01 20:15:17', NULL),
(27, 3, 2, 'text', 'heloooo', NULL, NULL, 0, 0, '2026-06-01 20:51:01', NULL),
(28, 3, 2, 'text', '989000', NULL, NULL, 0, 0, '2026-06-01 20:51:13', NULL),
(29, 2, 2, 'text', 'yanik', NULL, NULL, 0, 0, '2026-06-01 20:51:54', NULL),
(30, 6, 2, 'text', '📩 **SUPPORT INQUIRY**\n\n━━━━━━━━━━━━━━━━━━━━━━\n👤 **FROM:** isa\n📛 **Username:** @isak\n🆔 **User ID:** #2\n📧 **Email:** byaruhangangaisamelk@gmail.com\n📱 **Phone:** 0757000001\n📅 **Date:** June 2, 2026 at 12:47 AM\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **MESSAGE:**\ni have an inquirely\n\n━━━━━━━━━━━━━━━━━━━━━━\n✉️ _Sent via BisureChat Support System_', NULL, NULL, 0, 0, '2026-06-01 22:47:29', NULL),
(31, 6, 2, 'text', '🔔 **NEW SUPPORT INQUIRY**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #1\n👤 **From:** isa (@isak)\n📧 **Email:** byaruhangangaisamelk@gmail.com\n📱 **Phone:** 0757000001\n📅 **Submitted:** June 2, 2026 at 12:58 AM\n🏷️ **Status:** ⚠️ Pending\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Message Preview:**\nthis sysysystem\n\n━━━━━━━━━━━━━━━━━━━━━━\n🔗 **View full inquiry:** /admin/inquiries?view=1\n📋 _Use the Admin Panel to manage this inquiry_', NULL, NULL, 0, 0, '2026-06-01 22:58:19', NULL),
(32, 6, 2, 'text', '✅ **INQUIRY SUBMITTED**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #1\n📅 **Submitted:** June 2, 2026 at 12:58 AM\n🏷️ **Status:** Pending Review\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Your Message:**\nthis sysysystem\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 Our team will review your inquiry and respond shortly.\n🔗 Reference: #1', NULL, NULL, 0, 0, '2026-06-01 22:58:19', NULL),
(33, 6, 2, 'text', '🔔 **NEW SUPPORT INQUIRY**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #2\n👤 **From:** isa (@isak)\n📧 **Email:** byaruhangangaisamelk@gmail.com\n📱 **Phone:** 0757000001\n📅 **Submitted:** June 2, 2026 at 1:07 AM\n🏷️ **Status:** ⚠️ Pending\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Message Preview:**\nagain ertyuiefghjkld\r\ncvbnm,dfghjkwtyui\n\n━━━━━━━━━━━━━━━━━━━━━━\n🔗 **View full inquiry:** /admin/inquiries?view=2\n📋 _Use the Admin Panel to manage this inquiry_', NULL, NULL, 0, 0, '2026-06-01 23:07:24', NULL),
(34, 6, 2, 'text', '✅ **INQUIRY SUBMITTED**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #2\n📅 **Submitted:** June 2, 2026 at 1:07 AM\n🏷️ **Status:** Pending Review\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Your Message:**\nagain ertyuiefghjkld\r\ncvbnm,dfghjkwtyui\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 Our team will review your inquiry and respond shortly.\n🔗 Reference: #2', NULL, NULL, 0, 0, '2026-06-01 23:07:24', NULL),
(35, 6, 2, 'text', '✅ **INQUIRY RESPONSE #2**\n\n━━━━━━━━━━━━━━━━━━━━━━\n👤 **From:** Support Team\n📅 **Date:** June 2, 2026 at 1:15 AM\n🏷️ **Status:** Replied\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Response:**\nlike that\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 _This is a response to your inquiry. Reply here if you need further assistance._', NULL, NULL, 0, 0, '2026-06-01 23:15:44', NULL),
(36, 6, 2, 'text', '🔔 **NEW SUPPORT INQUIRY**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #3\n👤 **From:** isa (@isak)\n📧 **Email:** byaruhangangaisamelk@gmail.com\n📱 **Phone:** 0757000001\n📅 **Submitted:** June 2, 2026 at 1:40 AM\n🏷️ **Status:** ⚠️ Pending\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Message Preview:**\nwertyuioasdfghjklzxcvbnm\n\n━━━━━━━━━━━━━━━━━━━━━━\n🔗 **View full inquiry:** inquiries?view=3\n📋 _Use the Admin Panel to manage this inquiry_', NULL, NULL, 0, 0, '2026-06-01 23:40:31', NULL),
(37, 6, 2, 'text', '✅ **INQUIRY SUBMITTED**\n\n━━━━━━━━━━━━━━━━━━━━━━\n🆔 **Inquiry ID:** #3\n📅 **Submitted:** June 2, 2026 at 1:40 AM\n🏷️ **Status:** Pending Review\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Your Message:**\nwertyuioasdfghjklzxcvbnm\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 Our team will review your inquiry and respond shortly.\n🔗 Reference: #3', NULL, NULL, 0, 0, '2026-06-01 23:40:31', NULL),
(38, 6, 2, 'text', '✅ **INQUIRY RESPONSE #3**\n\n━━━━━━━━━━━━━━━━━━━━━━\n👤 **From:** Support Team\n📅 **Date:** June 2, 2026 at 1:41 AM\n🏷️ **Status:** Replied\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Response:**\nwertyuiop\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 _This is a response to your inquiry. Reply here if you need further assistance._', NULL, NULL, 0, 0, '2026-06-01 23:41:56', NULL),
(39, 6, 2, 'text', '✅ **INQUIRY RESPONSE #1**\n\n━━━━━━━━━━━━━━━━━━━━━━\n👤 **From:** Support Team\n📅 **Date:** June 3, 2026 at 4:32 PM\n🏷️ **Status:** Replied\n━━━━━━━━━━━━━━━━━━━━━━\n\n💬 **Response:**\nwelll\n\n━━━━━━━━━━━━━━━━━━━━━━\n📋 _This is a response to your inquiry. Reply here if you need further assistance._', NULL, NULL, 0, 0, '2026-06-03 14:32:42', NULL),
(40, 4, 2, 'text', 'dddd', NULL, NULL, 0, 0, '2026-06-03 15:26:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_reads`
--

CREATE TABLE `message_reads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_reads`
--

INSERT INTO `message_reads` (`id`, `message_id`, `user_id`, `read_at`) VALUES
(1, 5, 1, '2026-06-01 14:11:19'),
(2, 6, 1, '2026-06-01 14:11:19'),
(4, 7, 1, '2026-06-01 15:33:07'),
(5, 8, 1, '2026-06-01 15:33:07'),
(6, 9, 1, '2026-06-01 15:33:07'),
(7, 10, 1, '2026-06-01 15:33:07'),
(11, 11, 1, '2026-06-01 16:43:23'),
(12, 12, 1, '2026-06-01 17:04:36'),
(13, 13, 1, '2026-06-01 17:04:36'),
(14, 14, 1, '2026-06-01 17:04:36'),
(15, 15, 1, '2026-06-01 17:04:36'),
(19, 16, 2, '2026-06-01 17:04:51'),
(20, 17, 1, '2026-06-01 17:05:19'),
(21, 18, 2, '2026-06-01 18:28:28'),
(22, 19, 2, '2026-06-01 18:34:13'),
(23, 20, 2, '2026-06-01 18:40:34'),
(24, 21, 2, '2026-06-01 18:40:34'),
(25, 22, 2, '2026-06-01 18:40:34'),
(26, 23, 1, '2026-06-01 20:14:13'),
(27, 24, 1, '2026-06-01 20:14:13'),
(28, 25, 1, '2026-06-01 20:14:13'),
(29, 26, 2, '2026-06-01 22:48:03'),
(30, 30, 1, '2026-06-01 23:18:23'),
(31, 31, 1, '2026-06-01 23:18:23'),
(32, 32, 1, '2026-06-01 23:18:23'),
(33, 33, 1, '2026-06-01 23:18:23'),
(34, 34, 1, '2026-06-01 23:18:23'),
(35, 35, 1, '2026-06-01 23:18:23'),
(37, 36, 1, '2026-06-02 04:30:05'),
(38, 37, 1, '2026-06-02 04:30:05'),
(39, 38, 1, '2026-06-02 04:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_token`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, '7383424f991e18b63f70cfc3af53b80d7c4a7dcc8e9d76ed7b8311e58e2bef39', '2026-06-01 03:31:03', '2026-06-04 01:57:45', '2026-06-01 00:16:03'),
(2, 2, 'd5f1c6a0e20ffbd87045dfbcfcaf8c887a76a7c2b420f348b6e8463d8a6b0218', '2026-06-04 02:12:45', NULL, '2026-06-03 22:57:45'),
(3, 2, '8fc22c62429a458653a5af7a5f5450e9ca068033914ac91d2f5fd11cb6898179', '2026-06-04 02:16:10', NULL, '2026-06-03 23:01:10'),
(4, 2, 'e63fd32de3817a6e580aa3a99bcf6e21d4291feafb5bd09f927197ba7ef5002b', '2026-06-04 02:16:27', NULL, '2026-06-03 23:01:27'),
(5, 1, '7fa68222db36dac107fc23be6d541c185ae9494e3a9f2a2bfc71ad77239704ee', '2026-06-04 02:20:39', NULL, '2026-06-03 23:05:39'),
(6, 1, 'c9bf77fb68f80508ea56457659fb53a850dd91f708c2455e4ce8fe3534970f69', '2026-06-04 02:20:56', NULL, '2026-06-03 23:05:56'),
(7, 1, '51790ea9ecd9ba1838afdbaee88d2be2c9294921a931f053820d3c508ca20a41', '2026-06-04 02:21:23', NULL, '2026-06-03 23:06:23'),
(8, 1, 'a1234f7884e04774f86301216d5445d314df84652ddbf5ddfc4d51271b473800', '2026-06-04 02:21:47', '2026-06-04 02:11:01', '2026-06-03 23:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `bio` varchar(255) DEFAULT NULL,
  `status_message` varchar(255) DEFAULT 'Available',
  `role` enum('normal','admin') NOT NULL DEFAULT 'normal',
  `auth` varchar(5) NOT NULL DEFAULT 'yes',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_online` tinyint(1) DEFAULT 0,
  `last_seen` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `fullname`, `username`, `email`, `phone`, `password_hash`, `profile_photo`, `bio`, `status_message`, `role`, `auth`, `is_verified`, `is_online`, `last_seen`, `created_at`, `updated_at`) VALUES
(1, 'e659b716-462e-4bc8-8588-abe91e1b7387', 'Anthelem', 'anthelem', 'isamel.sparrow@gmail.com', '0757000000', '$2y$10$DgoJwZ4f7/8YGAtRVh0P5O7neD8R7SEeXr85Lb8DElLopKfMc3K.O', 'profile_1_1780361566.png', 'update Bio', 'Available', 'admin', 'no', 0, 1, '2026-06-03 19:11:14', '2026-05-31 19:09:22', '2026-06-03 23:11:01'),
(2, '8ef445a0-932f-492f-8228-bf1f2058f6ed', 'isamel', 'isak', 'byaruhangangaisamelk@gmail.com', '0757000001', '$2y$12$odnKWzmH5Z.mydIe6am/N.0eo0UVkBIvFSoJKQDVFC.iT4s9vJC/O', 'profile_2_1780359508.png', 'its my time', 'God knows', 'admin', 'yes', 0, 1, '2026-06-04 00:23:58', '2026-05-31 19:09:52', '2026-06-03 21:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `dark_mode` tinyint(1) DEFAULT 0,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sound_notifications` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `dark_mode`, `email_notifications`, `sound_notifications`, `created_at`) VALUES
(1, 1, 0, 1, 1, '2026-05-31 19:09:24'),
(2, 2, 0, 1, 1, '2026-05-31 19:09:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archived_chats`
--
ALTER TABLE `archived_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `block_reasons`
--
ALTER TABLE `block_reasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Indexes for table `calls`
--
ALTER TABLE `calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caller_id` (`caller_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_contact` (`user_id`,`contact_user_id`),
  ADD KEY `contact_user_id` (`contact_user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_user_id` (`recipient_user_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups_chat`
--
ALTER TABLE `groups_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `inquiries_ibfk_3` (`responded_by`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_reply_to` (`reply_to_id`);

--
-- Indexes for table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archived_chats`
--
ALTER TABLE `archived_chats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `block_reasons`
--
ALTER TABLE `block_reasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `calls`
--
ALTER TABLE `calls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groups_chat`
--
ALTER TABLE `groups_chat`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_reads`
--
ALTER TABLE `message_reads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `archived_chats`
--
ALTER TABLE `archived_chats`
  ADD CONSTRAINT `archived_chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `archived_chats_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `block_reasons`
--
ALTER TABLE `block_reasons`
  ADD CONSTRAINT `block_reasons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `block_reasons_ibfk_2` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calls`
--
ALTER TABLE `calls`
  ADD CONSTRAINT `calls_ibfk_1` FOREIGN KEY (`caller_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `calls_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`contact_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `groups_chat`
--
ALTER TABLE `groups_chat`
  ADD CONSTRAINT `groups_chat_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `groups_chat_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups_chat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inquiries_ibfk_3` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_reply_to` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `message_reads_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
