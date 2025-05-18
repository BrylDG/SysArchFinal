-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2025 at 05:56 PM
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
-- Database: `sitinmanagement`
--
CREATE DATABASE IF NOT EXISTS `sitinmanagement` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sitinmanagement`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(50) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `created_at`) VALUES
(23, '22919594', 'Updated ALL PCs in Lab 517 to status: Available', '2025-05-13 09:09:07'),
(24, '22919594', 'Updated ALL PCs in Lab 517 to status: Used', '2025-05-13 09:09:49'),
(25, '22919594', 'Updated ALL PCs in Lab 517 to status: Available', '2025-05-13 09:09:57'),
(26, '22919594', 'Updated PC 49 in Lab 524 to status: Used', '2025-05-16 03:41:43'),
(27, '22919594', 'Updated PC 57 in Lab 524 to status: Available', '2025-05-16 03:41:49'),
(28, '22919594', 'Updated PC 1 in Lab 517 to status: Used', '2025-05-16 08:03:29'),
(29, '22919594', 'Updated PC 2 in Lab 517 to status: Used', '2025-05-16 08:03:34'),
(30, '22919594', 'Updated PC 3 in Lab 517 to status: Used', '2025-05-16 08:03:41'),
(31, '22919594', 'Updated PC 4 in Lab 517 to status: Used', '2025-05-16 08:03:46'),
(32, '22919594', 'Updated PC 5 in Lab 517 to status: Used', '2025-05-16 08:03:50');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`) VALUES
(3, 'Lab 517 Maintenance.', 'Lab 517 will be unavailable due to lab maintenance.', '2025-05-13 09:05:56');

-- --------------------------------------------------------

--
-- Table structure for table `lab_pcs`
--

CREATE TABLE `lab_pcs` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` enum('Available','Used','Maintenance') DEFAULT 'Available',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_pcs`
--

INSERT INTO `lab_pcs` (`id`, `lab_name`, `pc_number`, `status`, `last_updated`) VALUES
(1, 'Lab 517', 1, 'Used', '2025-05-16 08:03:29'),
(2, 'Lab 517', 2, 'Used', '2025-05-16 08:03:34'),
(3, 'Lab 517', 3, 'Used', '2025-05-16 08:03:41'),
(4, 'Lab 517', 4, 'Used', '2025-05-16 08:03:46'),
(5, 'Lab 517', 5, 'Used', '2025-05-16 08:03:50'),
(6, 'Lab 517', 6, 'Available', '2025-05-13 09:09:57'),
(7, 'Lab 517', 7, 'Available', '2025-05-13 09:09:57'),
(8, 'Lab 517', 8, 'Available', '2025-05-13 09:09:57'),
(9, 'Lab 517', 9, 'Available', '2025-05-13 09:09:57'),
(10, 'Lab 517', 10, 'Available', '2025-05-13 09:09:57'),
(11, 'Lab 517', 11, 'Available', '2025-05-13 09:09:57'),
(12, 'Lab 517', 12, 'Available', '2025-05-13 09:09:57'),
(13, 'Lab 517', 13, 'Available', '2025-05-13 09:09:57'),
(14, 'Lab 517', 14, 'Available', '2025-05-13 09:09:57'),
(15, 'Lab 517', 15, 'Available', '2025-05-13 09:09:57'),
(16, 'Lab 517', 16, 'Available', '2025-05-13 09:09:57'),
(17, 'Lab 517', 17, 'Available', '2025-05-13 09:09:57'),
(18, 'Lab 517', 18, 'Available', '2025-05-13 09:09:57'),
(19, 'Lab 517', 19, 'Available', '2025-05-13 09:09:57'),
(20, 'Lab 517', 20, 'Available', '2025-05-13 09:09:57'),
(21, 'Lab 517', 21, 'Available', '2025-05-13 09:09:57'),
(22, 'Lab 517', 22, 'Available', '2025-05-13 09:09:57'),
(23, 'Lab 517', 23, 'Available', '2025-05-13 09:09:57'),
(24, 'Lab 517', 24, 'Available', '2025-05-13 09:09:57'),
(25, 'Lab 517', 25, 'Available', '2025-05-13 09:09:57'),
(26, 'Lab 517', 26, 'Available', '2025-05-13 09:09:57'),
(27, 'Lab 517', 27, 'Available', '2025-05-13 09:09:57'),
(28, 'Lab 517', 28, 'Available', '2025-05-13 09:09:57'),
(29, 'Lab 517', 29, 'Available', '2025-05-13 09:09:57'),
(30, 'Lab 517', 30, 'Available', '2025-05-13 09:09:57'),
(31, 'Lab 517', 31, 'Available', '2025-05-13 09:09:57'),
(32, 'Lab 517', 32, 'Available', '2025-05-13 09:09:57'),
(33, 'Lab 517', 33, 'Available', '2025-05-13 09:09:57'),
(34, 'Lab 517', 34, 'Available', '2025-05-13 09:09:57'),
(35, 'Lab 517', 35, 'Available', '2025-05-13 09:09:57'),
(36, 'Lab 517', 36, 'Available', '2025-05-13 09:09:57'),
(37, 'Lab 517', 37, 'Available', '2025-05-13 09:09:57'),
(38, 'Lab 517', 38, 'Available', '2025-05-13 09:09:57'),
(39, 'Lab 517', 39, 'Available', '2025-05-13 09:09:57'),
(40, 'Lab 517', 40, 'Available', '2025-05-13 09:09:57'),
(41, 'Lab 517', 41, 'Available', '2025-05-13 09:09:57'),
(42, 'Lab 517', 42, 'Available', '2025-05-13 09:09:57'),
(43, 'Lab 517', 43, 'Available', '2025-05-13 09:09:57'),
(44, 'Lab 517', 44, 'Available', '2025-05-13 09:09:57'),
(45, 'Lab 517', 45, 'Available', '2025-05-13 09:09:57'),
(46, 'Lab 517', 46, 'Available', '2025-05-13 09:09:57'),
(47, 'Lab 517', 47, 'Available', '2025-05-13 09:09:57'),
(48, 'Lab 517', 48, 'Available', '2025-05-13 09:09:57'),
(49, 'Lab 524', 1, 'Used', '2025-04-26 02:38:58'),
(50, 'Lab 524', 2, 'Used', '2025-04-28 07:32:32'),
(51, 'Lab 524', 3, 'Used', '2025-04-28 07:42:24'),
(52, 'Lab 524', 4, 'Used', '2025-04-28 07:39:40'),
(53, 'Lab 524', 5, 'Used', '2025-04-28 07:39:40'),
(54, 'Lab 524', 6, 'Used', '2025-04-28 07:39:40'),
(55, 'Lab 524', 7, 'Used', '2025-04-28 07:39:40'),
(56, 'Lab 524', 8, 'Used', '2025-04-26 02:55:26'),
(57, 'Lab 524', 9, 'Available', '2025-05-16 03:41:49'),
(58, 'Lab 524', 10, 'Used', '2025-04-28 07:39:40'),
(59, 'Lab 524', 11, 'Used', '2025-04-28 07:39:40'),
(60, 'Lab 524', 12, 'Used', '2025-04-28 07:39:40'),
(61, 'Lab 524', 13, 'Used', '2025-04-28 07:39:40'),
(62, 'Lab 524', 14, 'Used', '2025-04-28 07:39:40'),
(63, 'Lab 524', 15, 'Used', '2025-04-27 13:02:00'),
(64, 'Lab 524', 16, 'Used', '2025-04-28 07:39:40'),
(65, 'Lab 524', 17, 'Used', '2025-04-28 07:39:40'),
(66, 'Lab 524', 18, 'Used', '2025-04-28 07:39:40'),
(67, 'Lab 524', 19, 'Used', '2025-04-28 07:39:40'),
(68, 'Lab 524', 20, 'Used', '2025-04-28 07:39:40'),
(69, 'Lab 524', 21, 'Used', '2025-04-28 07:39:40'),
(70, 'Lab 524', 22, 'Used', '2025-04-28 07:39:40'),
(71, 'Lab 524', 23, 'Used', '2025-04-28 07:39:40'),
(72, 'Lab 524', 24, 'Used', '2025-04-28 07:39:40'),
(73, 'Lab 524', 25, 'Used', '2025-04-28 07:39:40'),
(74, 'Lab 524', 26, 'Used', '2025-04-28 07:39:40'),
(75, 'Lab 524', 27, 'Used', '2025-04-28 07:39:40'),
(76, 'Lab 524', 28, 'Used', '2025-04-28 07:39:40'),
(77, 'Lab 524', 29, 'Used', '2025-04-28 07:39:40'),
(78, 'Lab 524', 30, 'Used', '2025-04-28 07:39:40'),
(79, 'Lab 524', 31, 'Used', '2025-04-28 07:39:40'),
(80, 'Lab 524', 32, 'Used', '2025-04-28 07:39:40'),
(81, 'Lab 524', 33, 'Used', '2025-04-28 07:39:40'),
(82, 'Lab 524', 34, 'Used', '2025-04-28 07:39:40'),
(83, 'Lab 524', 35, 'Used', '2025-04-28 07:39:40'),
(84, 'Lab 524', 36, 'Used', '2025-04-28 07:39:40'),
(85, 'Lab 524', 37, 'Used', '2025-04-28 07:39:40'),
(86, 'Lab 524', 38, 'Used', '2025-04-28 07:39:40'),
(87, 'Lab 524', 39, 'Used', '2025-04-28 07:39:40'),
(88, 'Lab 524', 40, 'Used', '2025-04-28 07:39:40'),
(89, 'Lab 524', 41, 'Used', '2025-04-28 07:39:40'),
(90, 'Lab 524', 42, 'Used', '2025-04-28 07:39:40'),
(91, 'Lab 524', 43, 'Used', '2025-04-28 07:39:40'),
(92, 'Lab 524', 44, 'Used', '2025-04-28 07:39:40'),
(93, 'Lab 524', 45, 'Used', '2025-04-28 07:39:40'),
(94, 'Lab 524', 46, 'Used', '2025-04-28 07:39:40'),
(95, 'Lab 524', 47, 'Used', '2025-04-28 07:39:40'),
(96, 'Lab 524', 48, 'Used', '2025-04-25 05:05:18'),
(97, 'Lab 526', 1, 'Used', '2025-04-27 06:16:40'),
(98, 'Lab 526', 2, 'Available', '2025-05-02 20:31:59'),
(99, 'Lab 526', 3, 'Used', '2025-04-26 02:48:34'),
(100, 'Lab 526', 4, 'Available', '2025-04-25 04:22:09'),
(101, 'Lab 526', 5, 'Available', '2025-05-02 20:22:30'),
(102, 'Lab 526', 6, 'Used', '2025-04-26 03:55:38'),
(103, 'Lab 526', 7, 'Used', '2025-04-26 02:57:19'),
(104, 'Lab 526', 8, 'Available', '2025-04-30 19:59:16'),
(105, 'Lab 526', 9, 'Available', '2025-04-25 04:22:09'),
(106, 'Lab 526', 10, 'Available', '2025-04-25 04:22:09'),
(107, 'Lab 526', 11, 'Available', '2025-04-25 04:22:09'),
(108, 'Lab 526', 12, 'Available', '2025-04-25 04:22:09'),
(109, 'Lab 526', 13, 'Available', '2025-04-25 04:22:09'),
(110, 'Lab 526', 14, 'Available', '2025-04-25 04:22:09'),
(111, 'Lab 526', 15, 'Available', '2025-04-25 04:22:09'),
(112, 'Lab 526', 16, 'Available', '2025-04-25 04:22:09'),
(113, 'Lab 526', 17, 'Available', '2025-04-25 04:22:09'),
(114, 'Lab 526', 18, 'Available', '2025-04-25 04:22:09'),
(115, 'Lab 526', 19, 'Available', '2025-04-25 04:22:09'),
(116, 'Lab 526', 20, 'Available', '2025-04-25 04:22:09'),
(117, 'Lab 526', 21, 'Available', '2025-04-25 04:22:09'),
(118, 'Lab 526', 22, 'Available', '2025-04-25 04:22:09'),
(119, 'Lab 526', 23, 'Available', '2025-04-25 04:22:09'),
(120, 'Lab 526', 24, 'Available', '2025-04-25 04:22:09'),
(121, 'Lab 526', 25, 'Available', '2025-04-25 04:22:09'),
(122, 'Lab 526', 26, 'Available', '2025-04-25 04:22:09'),
(123, 'Lab 526', 27, 'Available', '2025-04-25 04:22:09'),
(124, 'Lab 526', 28, 'Available', '2025-04-25 04:22:09'),
(125, 'Lab 526', 29, 'Available', '2025-04-25 04:22:09'),
(126, 'Lab 526', 30, 'Available', '2025-04-25 04:22:09'),
(127, 'Lab 526', 31, 'Available', '2025-04-25 04:22:09'),
(128, 'Lab 526', 32, 'Available', '2025-04-25 04:22:09'),
(129, 'Lab 526', 33, 'Available', '2025-04-25 04:22:09'),
(130, 'Lab 526', 34, 'Available', '2025-04-25 04:22:09'),
(131, 'Lab 526', 35, 'Available', '2025-04-25 04:22:09'),
(132, 'Lab 526', 36, 'Available', '2025-04-25 04:22:09'),
(133, 'Lab 526', 37, 'Available', '2025-04-25 04:22:09'),
(134, 'Lab 526', 38, 'Available', '2025-04-25 04:22:09'),
(135, 'Lab 526', 39, 'Available', '2025-04-25 04:22:09'),
(136, 'Lab 526', 40, 'Available', '2025-04-25 04:22:09'),
(137, 'Lab 526', 41, 'Available', '2025-04-25 04:22:09'),
(138, 'Lab 526', 42, 'Available', '2025-04-25 04:22:09'),
(139, 'Lab 526', 43, 'Available', '2025-04-25 04:22:09'),
(140, 'Lab 526', 44, 'Available', '2025-04-25 04:22:09'),
(141, 'Lab 526', 45, 'Available', '2025-04-25 04:22:09'),
(142, 'Lab 526', 46, 'Available', '2025-04-25 04:22:09'),
(143, 'Lab 526', 47, 'Available', '2025-04-25 04:22:09'),
(144, 'Lab 526', 48, 'Available', '2025-04-25 04:22:09'),
(145, 'Lab 528', 1, 'Used', '2025-04-28 07:32:50'),
(146, 'Lab 528', 2, 'Used', '2025-04-28 07:32:50'),
(147, 'Lab 528', 3, 'Used', '2025-04-28 07:32:50'),
(148, 'Lab 528', 4, 'Used', '2025-04-28 07:32:50'),
(149, 'Lab 528', 5, 'Used', '2025-04-28 07:32:50'),
(150, 'Lab 528', 6, 'Used', '2025-04-28 07:32:50'),
(151, 'Lab 528', 7, 'Used', '2025-04-28 07:32:50'),
(152, 'Lab 528', 8, 'Used', '2025-04-28 07:32:50'),
(153, 'Lab 528', 9, 'Used', '2025-04-28 07:32:50'),
(154, 'Lab 528', 10, 'Used', '2025-04-28 07:32:50'),
(155, 'Lab 528', 11, 'Used', '2025-04-28 07:32:50'),
(156, 'Lab 528', 12, 'Used', '2025-04-28 07:32:50'),
(157, 'Lab 528', 13, 'Used', '2025-04-28 07:32:50'),
(158, 'Lab 528', 14, 'Used', '2025-04-28 07:32:50'),
(159, 'Lab 528', 15, 'Used', '2025-04-28 07:32:50'),
(160, 'Lab 528', 16, 'Used', '2025-04-28 07:32:50'),
(161, 'Lab 528', 17, 'Used', '2025-04-28 07:32:50'),
(162, 'Lab 528', 18, 'Used', '2025-04-28 07:32:50'),
(163, 'Lab 528', 19, 'Used', '2025-04-28 07:32:50'),
(164, 'Lab 528', 20, 'Used', '2025-04-28 07:32:50'),
(165, 'Lab 528', 21, 'Used', '2025-04-28 07:32:50'),
(166, 'Lab 528', 22, 'Used', '2025-04-28 07:32:50'),
(167, 'Lab 528', 23, 'Used', '2025-04-28 07:32:50'),
(168, 'Lab 528', 24, 'Used', '2025-04-28 07:32:50'),
(169, 'Lab 528', 25, 'Used', '2025-04-28 07:32:50'),
(170, 'Lab 528', 26, 'Used', '2025-04-28 07:32:50'),
(171, 'Lab 528', 27, 'Used', '2025-04-28 07:32:50'),
(172, 'Lab 528', 28, 'Used', '2025-04-28 07:32:50'),
(173, 'Lab 528', 29, 'Used', '2025-04-28 07:32:50'),
(174, 'Lab 528', 30, 'Used', '2025-04-28 07:32:50'),
(175, 'Lab 528', 31, 'Used', '2025-04-28 07:32:50'),
(176, 'Lab 528', 32, 'Used', '2025-04-28 07:32:50'),
(177, 'Lab 528', 33, 'Used', '2025-04-28 07:32:50'),
(178, 'Lab 528', 34, 'Used', '2025-04-28 07:32:50'),
(179, 'Lab 528', 35, 'Used', '2025-04-28 07:32:50'),
(180, 'Lab 528', 36, 'Used', '2025-04-28 07:32:50'),
(181, 'Lab 528', 37, 'Used', '2025-04-28 07:32:50'),
(182, 'Lab 528', 38, 'Used', '2025-04-28 07:32:50'),
(183, 'Lab 528', 39, 'Used', '2025-04-28 07:32:50'),
(184, 'Lab 528', 40, 'Used', '2025-04-28 07:32:50'),
(185, 'Lab 528', 41, 'Used', '2025-04-28 07:32:50'),
(186, 'Lab 528', 42, 'Used', '2025-04-28 07:32:50'),
(187, 'Lab 528', 43, 'Used', '2025-04-28 07:32:50'),
(188, 'Lab 528', 44, 'Used', '2025-04-28 07:32:50'),
(189, 'Lab 528', 45, 'Used', '2025-04-28 07:32:50'),
(190, 'Lab 528', 46, 'Used', '2025-04-28 07:32:50'),
(191, 'Lab 528', 47, 'Used', '2025-04-28 07:32:50'),
(192, 'Lab 528', 48, 'Used', '2025-04-28 07:32:50'),
(193, 'Lab 530', 1, 'Used', '2025-04-27 12:53:03'),
(194, 'Lab 530', 2, 'Used', '2025-04-27 20:37:27'),
(195, 'Lab 530', 3, 'Available', '2025-05-02 20:48:45'),
(196, 'Lab 530', 4, 'Available', '2025-04-25 04:22:09'),
(197, 'Lab 530', 5, 'Available', '2025-04-25 04:22:09'),
(198, 'Lab 530', 6, 'Available', '2025-05-02 20:35:53'),
(199, 'Lab 530', 7, 'Available', '2025-05-02 20:33:12'),
(200, 'Lab 530', 8, 'Available', '2025-04-25 04:22:09'),
(201, 'Lab 530', 9, 'Available', '2025-04-25 04:22:09'),
(202, 'Lab 530', 10, 'Available', '2025-04-25 04:22:09'),
(203, 'Lab 530', 11, 'Available', '2025-04-25 04:22:09'),
(204, 'Lab 530', 12, 'Available', '2025-04-25 04:22:09'),
(205, 'Lab 530', 13, 'Available', '2025-04-25 04:22:09'),
(206, 'Lab 530', 14, 'Available', '2025-04-25 04:22:09'),
(207, 'Lab 530', 15, 'Available', '2025-04-25 04:22:10'),
(208, 'Lab 530', 16, 'Available', '2025-04-25 04:22:10'),
(209, 'Lab 530', 17, 'Available', '2025-04-25 04:22:10'),
(210, 'Lab 530', 18, 'Available', '2025-04-25 04:22:10'),
(211, 'Lab 530', 19, 'Available', '2025-04-25 04:22:10'),
(212, 'Lab 530', 20, 'Available', '2025-04-25 04:22:10'),
(213, 'Lab 530', 21, 'Available', '2025-04-25 04:22:10'),
(214, 'Lab 530', 22, 'Available', '2025-04-25 04:22:10'),
(215, 'Lab 530', 23, 'Available', '2025-04-25 04:22:10'),
(216, 'Lab 530', 24, 'Available', '2025-04-25 04:22:10'),
(217, 'Lab 530', 25, 'Available', '2025-04-25 04:22:10'),
(218, 'Lab 530', 26, 'Available', '2025-04-25 04:22:10'),
(219, 'Lab 530', 27, 'Available', '2025-04-25 04:22:10'),
(220, 'Lab 530', 28, 'Available', '2025-04-25 04:22:10'),
(221, 'Lab 530', 29, 'Available', '2025-04-25 04:22:10'),
(222, 'Lab 530', 30, 'Available', '2025-04-25 04:22:10'),
(223, 'Lab 530', 31, 'Available', '2025-04-25 04:22:10'),
(224, 'Lab 530', 32, 'Available', '2025-04-25 04:22:10'),
(225, 'Lab 530', 33, 'Available', '2025-04-25 04:22:10'),
(226, 'Lab 530', 34, 'Available', '2025-04-25 04:22:10'),
(227, 'Lab 530', 35, 'Available', '2025-04-25 04:22:10'),
(228, 'Lab 530', 36, 'Available', '2025-04-25 04:22:10'),
(229, 'Lab 530', 37, 'Available', '2025-04-25 04:22:10'),
(230, 'Lab 530', 38, 'Available', '2025-04-25 04:22:10'),
(231, 'Lab 530', 39, 'Available', '2025-04-25 04:22:10'),
(232, 'Lab 530', 40, 'Available', '2025-04-25 04:22:10'),
(233, 'Lab 530', 41, 'Available', '2025-04-25 04:22:10'),
(234, 'Lab 530', 42, 'Available', '2025-04-25 04:22:10'),
(235, 'Lab 530', 43, 'Available', '2025-04-25 04:22:10'),
(236, 'Lab 530', 44, 'Available', '2025-04-25 04:22:10'),
(237, 'Lab 530', 45, 'Available', '2025-04-25 04:22:10'),
(238, 'Lab 530', 46, 'Available', '2025-04-25 04:22:10'),
(239, 'Lab 530', 47, 'Available', '2025-04-25 04:22:10'),
(240, 'Lab 530', 48, 'Available', '2025-04-25 04:22:10'),
(241, 'Lab 542', 1, 'Available', '2025-05-02 20:31:12'),
(242, 'Lab 542', 2, 'Available', '2025-04-25 04:22:10'),
(243, 'Lab 542', 3, 'Available', '2025-04-25 04:22:10'),
(244, 'Lab 542', 4, 'Available', '2025-04-25 04:22:10'),
(245, 'Lab 542', 5, 'Available', '2025-04-25 04:22:10'),
(246, 'Lab 542', 6, 'Available', '2025-04-25 04:22:10'),
(247, 'Lab 542', 7, 'Available', '2025-04-25 04:22:10'),
(248, 'Lab 542', 8, 'Available', '2025-04-25 04:22:10'),
(249, 'Lab 542', 9, 'Available', '2025-04-25 04:22:10'),
(250, 'Lab 542', 10, 'Available', '2025-04-25 04:22:10'),
(251, 'Lab 542', 11, 'Available', '2025-04-25 04:22:10'),
(252, 'Lab 542', 12, 'Available', '2025-04-25 04:22:10'),
(253, 'Lab 542', 13, 'Available', '2025-04-25 04:22:10'),
(254, 'Lab 542', 14, 'Available', '2025-04-25 04:22:10'),
(255, 'Lab 542', 15, 'Available', '2025-04-25 04:22:10'),
(256, 'Lab 542', 16, 'Available', '2025-04-25 04:22:10'),
(257, 'Lab 542', 17, 'Available', '2025-04-25 04:22:10'),
(258, 'Lab 542', 18, 'Available', '2025-04-25 04:22:10'),
(259, 'Lab 542', 19, 'Available', '2025-04-25 04:22:10'),
(260, 'Lab 542', 20, 'Available', '2025-04-25 04:22:10'),
(261, 'Lab 542', 21, 'Available', '2025-04-25 04:22:10'),
(262, 'Lab 542', 22, 'Available', '2025-04-25 04:22:10'),
(263, 'Lab 542', 23, 'Available', '2025-04-25 04:22:10'),
(264, 'Lab 542', 24, 'Available', '2025-04-25 04:22:10'),
(265, 'Lab 542', 25, 'Available', '2025-04-25 04:22:10'),
(266, 'Lab 542', 26, 'Available', '2025-04-25 04:22:10'),
(267, 'Lab 542', 27, 'Available', '2025-04-25 04:22:10'),
(268, 'Lab 542', 28, 'Available', '2025-04-25 04:22:10'),
(269, 'Lab 542', 29, 'Available', '2025-04-25 04:22:10'),
(270, 'Lab 542', 30, 'Available', '2025-04-25 04:22:10'),
(271, 'Lab 542', 31, 'Available', '2025-04-25 04:22:10'),
(272, 'Lab 542', 32, 'Available', '2025-04-25 04:22:10'),
(273, 'Lab 542', 33, 'Available', '2025-04-25 04:22:10'),
(274, 'Lab 542', 34, 'Available', '2025-04-25 04:22:10'),
(275, 'Lab 542', 35, 'Available', '2025-04-25 04:22:10'),
(276, 'Lab 542', 36, 'Available', '2025-04-25 04:22:10'),
(277, 'Lab 542', 37, 'Available', '2025-04-25 04:22:10'),
(278, 'Lab 542', 38, 'Available', '2025-04-25 04:22:10'),
(279, 'Lab 542', 39, 'Available', '2025-04-25 04:22:10'),
(280, 'Lab 542', 40, 'Available', '2025-04-25 04:22:10'),
(281, 'Lab 542', 41, 'Available', '2025-04-25 04:22:10'),
(282, 'Lab 542', 42, 'Available', '2025-04-25 04:22:10'),
(283, 'Lab 542', 43, 'Available', '2025-04-25 04:22:10'),
(284, 'Lab 542', 44, 'Available', '2025-04-25 04:22:10'),
(285, 'Lab 542', 45, 'Available', '2025-04-25 04:22:10'),
(286, 'Lab 542', 46, 'Available', '2025-04-25 04:22:10'),
(287, 'Lab 542', 47, 'Available', '2025-04-25 04:22:10'),
(288, 'Lab 542', 48, 'Available', '2025-04-25 04:22:10'),
(289, 'Lab 544', 1, 'Available', '2025-04-29 01:47:40'),
(290, 'Lab 544', 2, 'Available', '2025-04-25 04:22:10'),
(291, 'Lab 544', 3, 'Available', '2025-05-02 20:32:36'),
(292, 'Lab 544', 4, 'Available', '2025-04-25 04:22:10'),
(293, 'Lab 544', 5, 'Used', '2025-04-26 03:56:18'),
(294, 'Lab 544', 6, 'Used', '2025-04-26 03:05:03'),
(295, 'Lab 544', 7, 'Used', '2025-04-26 03:04:43'),
(296, 'Lab 544', 8, 'Available', '2025-04-25 04:22:10'),
(297, 'Lab 544', 9, 'Available', '2025-04-25 04:22:10'),
(298, 'Lab 544', 10, 'Available', '2025-04-25 04:22:10'),
(299, 'Lab 544', 11, 'Available', '2025-04-25 04:22:10'),
(300, 'Lab 544', 12, 'Available', '2025-04-25 04:22:10'),
(301, 'Lab 544', 13, 'Available', '2025-04-25 04:22:10'),
(302, 'Lab 544', 14, 'Available', '2025-04-25 04:22:10'),
(303, 'Lab 544', 15, 'Available', '2025-04-25 04:22:10'),
(304, 'Lab 544', 16, 'Available', '2025-04-25 04:22:10'),
(305, 'Lab 544', 17, 'Available', '2025-04-25 04:22:10'),
(306, 'Lab 544', 18, 'Available', '2025-04-25 04:22:10'),
(307, 'Lab 544', 19, 'Available', '2025-04-25 04:22:10'),
(308, 'Lab 544', 20, 'Available', '2025-04-25 04:22:10'),
(309, 'Lab 544', 21, 'Available', '2025-04-25 04:22:10'),
(310, 'Lab 544', 22, 'Available', '2025-04-25 04:22:10'),
(311, 'Lab 544', 23, 'Available', '2025-04-25 04:22:10'),
(312, 'Lab 544', 24, 'Available', '2025-04-25 04:22:10'),
(313, 'Lab 544', 25, 'Available', '2025-04-25 04:22:10'),
(314, 'Lab 544', 26, 'Available', '2025-04-25 04:22:10'),
(315, 'Lab 544', 27, 'Available', '2025-04-25 04:22:10'),
(316, 'Lab 544', 28, 'Available', '2025-04-25 04:22:10'),
(317, 'Lab 544', 29, 'Available', '2025-04-25 04:22:10'),
(318, 'Lab 544', 30, 'Available', '2025-04-25 04:22:10'),
(319, 'Lab 544', 31, 'Available', '2025-04-25 04:22:10'),
(320, 'Lab 544', 32, 'Available', '2025-04-25 04:22:10'),
(321, 'Lab 544', 33, 'Available', '2025-04-25 04:22:10'),
(322, 'Lab 544', 34, 'Available', '2025-04-25 04:22:10'),
(323, 'Lab 544', 35, 'Available', '2025-04-25 04:22:10'),
(324, 'Lab 544', 36, 'Available', '2025-04-25 04:22:10'),
(325, 'Lab 544', 37, 'Available', '2025-04-25 04:22:10'),
(326, 'Lab 544', 38, 'Available', '2025-04-25 04:22:10'),
(327, 'Lab 544', 39, 'Available', '2025-04-25 04:22:10'),
(328, 'Lab 544', 40, 'Available', '2025-04-25 04:22:10'),
(329, 'Lab 544', 41, 'Available', '2025-04-25 04:22:10'),
(330, 'Lab 544', 42, 'Available', '2025-04-25 04:22:10'),
(331, 'Lab 544', 43, 'Available', '2025-04-25 04:22:10'),
(332, 'Lab 544', 44, 'Available', '2025-04-25 04:22:10'),
(333, 'Lab 544', 45, 'Available', '2025-04-25 04:22:10'),
(334, 'Lab 544', 46, 'Available', '2025-04-25 04:22:10'),
(335, 'Lab 544', 47, 'Available', '2025-04-25 04:22:10'),
(336, 'Lab 544', 48, 'Available', '2025-04-25 04:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(132, '20949194', 'You logged out without earning points this session. Remaining sessions: 29', 0, '2025-05-08 23:04:41'),
(133, '20949194', 'You logged out without earning points this session. Remaining sessions: 28', 0, '2025-05-08 23:48:29'),
(134, '20949194', 'You logged out without earning points this session. Remaining sessions: 29', 0, '2025-05-09 00:01:55'),
(135, '20949194', 'You gained 1 point for your sit-in session (Total: 1 point)', 0, '2025-05-13 09:17:52'),
(136, '21950195', 'Your reservation for Lab 517 has been approved!', 0, '2025-05-13 09:27:04'),
(137, '20949194', 'You gained 1 point for your sit-in session (Total: 2 points)', 0, '2025-05-16 03:40:16'),
(138, '21950195', 'You logged out without earning points this session. Remaining sessions: 29', 0, '2025-05-16 03:40:20'),
(139, '20949194', 'Your reservation for Lab 517 has been approved!', 0, '2025-05-16 03:47:09'),
(140, '20949194', 'You gained 1 point for your sit-in session (Total: 3 points)', 0, '2025-05-16 04:27:37'),
(141, '20949194', 'You earned +1 session for reaching 3 points! (Total: 30 sessions)', 0, '2025-05-16 04:27:37'),
(142, '21950195', 'Your reservation for Lab 517 has been approved!', 0, '2025-05-16 08:02:13'),
(143, '21950195', 'You gained 1 point for your sit-in session (Total: 1 point)', 0, '2025-05-16 08:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab_room` varchar(50) NOT NULL,
  `pc_number` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `time_in` time NOT NULL,
  `status` enum('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `student_id`, `purpose`, `lab_room`, `pc_number`, `reservation_date`, `time_in`, `status`, `created_at`, `admin_notes`) VALUES
(49, 26, 'C Programming', 'Lab 517', 1, '2025-05-13', '05:28:00', 'approved', '2025-05-13 09:26:44', ''),
(50, 25, 'Database', 'Lab 517', 1, '2025-05-16', '11:44:00', 'approved', '2025-05-16 03:42:48', ''),
(51, 25, 'Computer Application', 'Lab 526', 2, '2025-05-16', '15:58:00', 'pending', '2025-05-16 07:58:44', NULL),
(52, 26, 'C# Programming', 'Lab 517', 2, '2025-05-16', '16:01:00', 'approved', '2025-05-16 08:01:58', '');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `available_to` enum('all','students','admins') NOT NULL DEFAULT 'all',
  `uploaded_by` varchar(50) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `description`, `file_name`, `file_path`, `file_size`, `file_type`, `available_to`, `uploaded_by`, `upload_date`) VALUES
(8, 'Energreen', 'PDF file for energreen pitch', 'Energreen.pdf', 'resources/6826b3d3d0f10_Energreen.pdf', 3229207, 'pdf', 'all', '22919594', '2025-05-16 03:41:07');

-- --------------------------------------------------------

--
-- Table structure for table `rewards_log`
--

CREATE TABLE `rewards_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'References users.id',
  `points_earned` int(11) NOT NULL,
  `action` enum('sit_in_completion','admin_add','admin_remove') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards_log`
--

INSERT INTO `rewards_log` (`id`, `user_id`, `points_earned`, `action`, `created_at`) VALUES
(46, 25, 1, 'sit_in_completion', '2025-05-13 09:17:52'),
(47, 25, 1, 'sit_in_completion', '2025-05-16 03:40:16'),
(48, 25, 1, 'sit_in_completion', '2025-05-16 04:27:37'),
(49, 25, 1, '', '2025-05-16 04:27:37'),
(50, 26, 1, 'sit_in_completion', '2025-05-16 08:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `satisfaction_surveys`
--

CREATE TABLE `satisfaction_surveys` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `satisfaction` tinyint(4) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_records`
--

CREATE TABLE `sit_in_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `pc_number` int(11) DEFAULT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_in_records`
--

INSERT INTO `sit_in_records` (`id`, `student_id`, `purpose`, `lab`, `pc_number`, `start_time`, `end_time`, `feedback`) VALUES
(114, 25, 'Java Programming', 'Lab 544', 0, '2025-05-09 07:04:14', '2025-05-09 07:04:41', NULL),
(115, 25, 'C Programming', 'Lab 517', 0, '2025-05-09 07:48:11', '2025-05-09 07:48:29', NULL),
(116, 25, 'C Programming', 'Lab 524', 0, '2025-05-09 07:49:07', '2025-05-09 08:01:55', NULL),
(117, 25, 'Web Design', 'Lab 517', 0, '2025-05-13 17:08:43', '2025-05-13 17:17:52', NULL),
(118, 26, 'C Programming', 'Lab 517', 1, '2025-05-13 05:28:00', '2025-05-16 11:40:20', NULL),
(119, 25, 'Systems Integration & Architecture', 'Lab 524', 0, '2025-05-16 11:36:52', '2025-05-16 11:40:16', 'fuck you'),
(120, 25, 'Database', 'Lab 517', 1, '2025-05-16 11:44:00', '2025-05-16 12:27:37', 'very nice'),
(121, 26, 'C# Programming', 'Lab 517', 2, '2025-05-16 16:01:00', '2025-05-16 16:02:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `static_lab_schedules`
--

CREATE TABLE `static_lab_schedules` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `day_group` varchar(10) NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `status` enum('available','occupied') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `static_lab_schedules`
--

INSERT INTO `static_lab_schedules` (`id`, `lab_name`, `day_group`, `time_slot`, `status`) VALUES
(1, 'Lab 517', 'MW', '7:30 AM - 9:00 AM', 'available'),
(2, 'Lab 524', 'MW', '7:30 AM - 9:00 AM', 'available'),
(3, 'Lab 526', 'MW', '7:30 AM - 9:00 AM', 'available'),
(4, 'Lab 528', 'MW', '7:30 AM - 9:00 AM', 'available'),
(5, 'Lab 530', 'MW', '7:30 AM - 9:00 AM', 'available'),
(6, 'Lab 542', 'MW', '7:30 AM - 9:00 AM', 'available'),
(7, 'Lab 544', 'MW', '7:30 AM - 9:00 AM', 'available'),
(8, 'Lab 517', 'MW', '9:00 AM - 10:30 AM', 'available'),
(9, 'Lab 524', 'MW', '9:00 AM - 10:30 AM', 'available'),
(10, 'Lab 526', 'MW', '9:00 AM - 10:30 AM', 'available'),
(11, 'Lab 528', 'MW', '9:00 AM - 10:30 AM', 'available'),
(12, 'Lab 530', 'MW', '9:00 AM - 10:30 AM', 'available'),
(13, 'Lab 542', 'MW', '9:00 AM - 10:30 AM', 'available'),
(14, 'Lab 544', 'MW', '9:00 AM - 10:30 AM', 'available'),
(15, 'Lab 517', 'MW', '10:30 AM - 12:00 PM', 'occupied'),
(16, 'Lab 524', 'MW', '10:30 AM - 12:00 PM', 'available'),
(17, 'Lab 526', 'MW', '10:30 AM - 12:00 PM', 'available'),
(18, 'Lab 528', 'MW', '10:30 AM - 12:00 PM', 'occupied'),
(19, 'Lab 530', 'MW', '10:30 AM - 12:00 PM', 'available'),
(20, 'Lab 542', 'MW', '10:30 AM - 12:00 PM', 'available'),
(21, 'Lab 544', 'MW', '10:30 AM - 12:00 PM', 'occupied'),
(22, 'Lab 517', 'MW', '12:00 PM - 1:30 PM', 'available'),
(23, 'Lab 524', 'MW', '12:00 PM - 1:30 PM', 'available'),
(24, 'Lab 526', 'MW', '12:00 PM - 1:30 PM', 'available'),
(25, 'Lab 528', 'MW', '12:00 PM - 1:30 PM', 'available'),
(26, 'Lab 530', 'MW', '12:00 PM - 1:30 PM', 'available'),
(27, 'Lab 542', 'MW', '12:00 PM - 1:30 PM', 'available'),
(28, 'Lab 544', 'MW', '12:00 PM - 1:30 PM', 'available'),
(29, 'Lab 517', 'MW', '1:30 PM - 3:00 PM', 'available'),
(30, 'Lab 524', 'MW', '1:30 PM - 3:00 PM', 'available'),
(31, 'Lab 526', 'MW', '1:30 PM - 3:00 PM', 'available'),
(32, 'Lab 528', 'MW', '1:30 PM - 3:00 PM', 'available'),
(33, 'Lab 530', 'MW', '1:30 PM - 3:00 PM', 'available'),
(34, 'Lab 542', 'MW', '1:30 PM - 3:00 PM', 'available'),
(35, 'Lab 544', 'MW', '1:30 PM - 3:00 PM', 'available'),
(36, 'Lab 517', 'MW', '3:00 PM - 4:30 PM', 'available'),
(37, 'Lab 524', 'MW', '3:00 PM - 4:30 PM', 'available'),
(38, 'Lab 526', 'MW', '3:00 PM - 4:30 PM', 'available'),
(39, 'Lab 528', 'MW', '3:00 PM - 4:30 PM', 'available'),
(40, 'Lab 530', 'MW', '3:00 PM - 4:30 PM', 'available'),
(41, 'Lab 542', 'MW', '3:00 PM - 4:30 PM', 'available'),
(42, 'Lab 544', 'MW', '3:00 PM - 4:30 PM', 'available'),
(43, 'Lab 517', 'MW', '4:30 PM - 6:00 PM', 'available'),
(44, 'Lab 524', 'MW', '4:30 PM - 6:00 PM', 'available'),
(45, 'Lab 526', 'MW', '4:30 PM - 6:00 PM', 'available'),
(46, 'Lab 528', 'MW', '4:30 PM - 6:00 PM', 'available'),
(47, 'Lab 530', 'MW', '4:30 PM - 6:00 PM', 'available'),
(48, 'Lab 542', 'MW', '4:30 PM - 6:00 PM', 'available'),
(49, 'Lab 544', 'MW', '4:30 PM - 6:00 PM', 'available'),
(50, 'Lab 517', 'MW', '6:00 PM - 7:30 PM', 'available'),
(51, 'Lab 524', 'MW', '6:00 PM - 7:30 PM', 'available'),
(52, 'Lab 526', 'MW', '6:00 PM - 7:30 PM', 'available'),
(53, 'Lab 528', 'MW', '6:00 PM - 7:30 PM', 'available'),
(54, 'Lab 530', 'MW', '6:00 PM - 7:30 PM', 'available'),
(55, 'Lab 542', 'MW', '6:00 PM - 7:30 PM', 'available'),
(56, 'Lab 544', 'MW', '6:00 PM - 7:30 PM', 'available'),
(57, 'Lab 517', 'MW', '7:30 PM - 9:00 PM', 'available'),
(58, 'Lab 524', 'MW', '7:30 PM - 9:00 PM', 'available'),
(59, 'Lab 526', 'MW', '7:30 PM - 9:00 PM', 'available'),
(60, 'Lab 528', 'MW', '7:30 PM - 9:00 PM', 'available'),
(61, 'Lab 530', 'MW', '7:30 PM - 9:00 PM', 'available'),
(62, 'Lab 542', 'MW', '7:30 PM - 9:00 PM', 'available'),
(63, 'Lab 544', 'MW', '7:30 PM - 9:00 PM', 'available'),
(64, 'Lab 517', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(65, 'Lab 524', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(66, 'Lab 526', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(67, 'Lab 528', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(68, 'Lab 530', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(69, 'Lab 542', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(70, 'Lab 544', 'TTh', '7:30 AM - 9:00 AM', 'available'),
(71, 'Lab 517', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(72, 'Lab 524', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(73, 'Lab 526', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(74, 'Lab 528', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(75, 'Lab 530', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(76, 'Lab 542', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(77, 'Lab 544', 'TTh', '9:00 AM - 10:30 AM', 'available'),
(78, 'Lab 517', 'TTh', '10:30 AM - 12:00 PM', 'occupied'),
(79, 'Lab 524', 'TTh', '10:30 AM - 12:00 PM', 'occupied'),
(80, 'Lab 526', 'TTh', '10:30 AM - 12:00 PM', 'available'),
(81, 'Lab 528', 'TTh', '10:30 AM - 12:00 PM', 'available'),
(82, 'Lab 530', 'TTh', '10:30 AM - 12:00 PM', 'occupied'),
(83, 'Lab 542', 'TTh', '10:30 AM - 12:00 PM', 'occupied'),
(84, 'Lab 544', 'TTh', '10:30 AM - 12:00 PM', 'available'),
(85, 'Lab 517', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(86, 'Lab 524', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(87, 'Lab 526', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(88, 'Lab 528', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(89, 'Lab 530', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(90, 'Lab 542', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(91, 'Lab 544', 'TTh', '12:00 PM - 1:30 PM', 'available'),
(92, 'Lab 517', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(93, 'Lab 524', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(94, 'Lab 526', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(95, 'Lab 528', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(96, 'Lab 530', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(97, 'Lab 542', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(98, 'Lab 544', 'TTh', '1:30 PM - 3:00 PM', 'available'),
(99, 'Lab 517', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(100, 'Lab 524', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(101, 'Lab 526', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(102, 'Lab 528', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(103, 'Lab 530', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(104, 'Lab 542', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(105, 'Lab 544', 'TTh', '3:00 PM - 4:30 PM', 'available'),
(106, 'Lab 517', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(107, 'Lab 524', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(108, 'Lab 526', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(109, 'Lab 528', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(110, 'Lab 530', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(111, 'Lab 542', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(112, 'Lab 544', 'TTh', '4:30 PM - 6:00 PM', 'available'),
(113, 'Lab 517', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(114, 'Lab 524', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(115, 'Lab 526', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(116, 'Lab 528', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(117, 'Lab 530', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(118, 'Lab 542', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(119, 'Lab 544', 'TTh', '6:00 PM - 7:30 PM', 'available'),
(120, 'Lab 517', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(121, 'Lab 524', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(122, 'Lab 526', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(123, 'Lab 528', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(124, 'Lab 530', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(125, 'Lab 542', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(126, 'Lab 544', 'TTh', '7:30 PM - 9:00 PM', 'available'),
(127, 'Lab 517', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(128, 'Lab 524', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(129, 'Lab 526', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(130, 'Lab 528', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(131, 'Lab 530', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(132, 'Lab 542', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(133, 'Lab 544', 'Fri', '7:30 AM - 9:00 AM', 'available'),
(134, 'Lab 517', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(135, 'Lab 524', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(136, 'Lab 526', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(137, 'Lab 528', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(138, 'Lab 530', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(139, 'Lab 542', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(140, 'Lab 544', 'Fri', '9:00 AM - 10:30 AM', 'available'),
(141, 'Lab 517', 'Fri', '10:30 AM - 12:00 PM', 'available'),
(142, 'Lab 524', 'Fri', '10:30 AM - 12:00 PM', 'available'),
(143, 'Lab 526', 'Fri', '10:30 AM - 12:00 PM', 'occupied'),
(144, 'Lab 528', 'Fri', '10:30 AM - 12:00 PM', 'occupied'),
(145, 'Lab 530', 'Fri', '10:30 AM - 12:00 PM', 'available'),
(146, 'Lab 542', 'Fri', '10:30 AM - 12:00 PM', 'available'),
(147, 'Lab 544', 'Fri', '10:30 AM - 12:00 PM', 'available'),
(148, 'Lab 517', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(149, 'Lab 524', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(150, 'Lab 526', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(151, 'Lab 528', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(152, 'Lab 530', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(153, 'Lab 542', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(154, 'Lab 544', 'Fri', '12:00 PM - 1:30 PM', 'available'),
(155, 'Lab 517', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(156, 'Lab 524', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(157, 'Lab 526', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(158, 'Lab 528', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(159, 'Lab 530', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(160, 'Lab 542', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(161, 'Lab 544', 'Fri', '1:30 PM - 3:00 PM', 'available'),
(162, 'Lab 517', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(163, 'Lab 524', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(164, 'Lab 526', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(165, 'Lab 528', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(166, 'Lab 530', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(167, 'Lab 542', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(168, 'Lab 544', 'Fri', '3:00 PM - 4:30 PM', 'available'),
(169, 'Lab 517', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(170, 'Lab 524', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(171, 'Lab 526', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(172, 'Lab 528', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(173, 'Lab 530', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(174, 'Lab 542', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(175, 'Lab 544', 'Fri', '4:30 PM - 6:00 PM', 'available'),
(176, 'Lab 517', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(177, 'Lab 524', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(178, 'Lab 526', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(179, 'Lab 528', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(180, 'Lab 530', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(181, 'Lab 542', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(182, 'Lab 544', 'Fri', '6:00 PM - 7:30 PM', 'available'),
(183, 'Lab 517', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(184, 'Lab 524', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(185, 'Lab 526', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(186, 'Lab 528', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(187, 'Lab 530', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(188, 'Lab 542', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(189, 'Lab 544', 'Fri', '7:30 PM - 9:00 PM', 'available'),
(190, 'Lab 517', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(191, 'Lab 524', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(192, 'Lab 526', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(193, 'Lab 528', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(194, 'Lab 530', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(195, 'Lab 542', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(196, 'Lab 544', 'Sat', '7:30 AM - 9:00 AM', 'available'),
(197, 'Lab 517', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(198, 'Lab 524', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(199, 'Lab 526', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(200, 'Lab 528', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(201, 'Lab 530', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(202, 'Lab 542', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(203, 'Lab 544', 'Sat', '9:00 AM - 10:30 AM', 'available'),
(204, 'Lab 517', 'Sat', '10:30 AM - 12:00 PM', 'occupied'),
(205, 'Lab 524', 'Sat', '10:30 AM - 12:00 PM', 'available'),
(206, 'Lab 526', 'Sat', '10:30 AM - 12:00 PM', 'available'),
(207, 'Lab 528', 'Sat', '10:30 AM - 12:00 PM', 'occupied'),
(208, 'Lab 530', 'Sat', '10:30 AM - 12:00 PM', 'available'),
(209, 'Lab 542', 'Sat', '10:30 AM - 12:00 PM', 'occupied'),
(210, 'Lab 544', 'Sat', '10:30 AM - 12:00 PM', 'occupied'),
(211, 'Lab 517', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(212, 'Lab 524', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(213, 'Lab 526', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(214, 'Lab 528', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(215, 'Lab 530', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(216, 'Lab 542', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(217, 'Lab 544', 'Sat', '12:00 PM - 1:30 PM', 'available'),
(218, 'Lab 517', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(219, 'Lab 524', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(220, 'Lab 526', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(221, 'Lab 528', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(222, 'Lab 530', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(223, 'Lab 542', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(224, 'Lab 544', 'Sat', '1:30 PM - 3:00 PM', 'available'),
(225, 'Lab 517', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(226, 'Lab 524', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(227, 'Lab 526', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(228, 'Lab 528', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(229, 'Lab 530', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(230, 'Lab 542', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(231, 'Lab 544', 'Sat', '3:00 PM - 4:30 PM', 'available'),
(232, 'Lab 517', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(233, 'Lab 524', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(234, 'Lab 526', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(235, 'Lab 528', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(236, 'Lab 530', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(237, 'Lab 542', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(238, 'Lab 544', 'Sat', '4:30 PM - 6:00 PM', 'available'),
(239, 'Lab 517', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(240, 'Lab 524', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(241, 'Lab 526', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(242, 'Lab 528', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(243, 'Lab 530', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(244, 'Lab 542', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(245, 'Lab 544', 'Sat', '6:00 PM - 7:30 PM', 'available'),
(246, 'Lab 517', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(247, 'Lab 524', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(248, 'Lab 526', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(249, 'Lab 528', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(250, 'Lab 530', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(251, 'Lab 542', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(252, 'Lab 544', 'Sat', '7:30 PM - 9:00 PM', 'available'),
(253, 'Lab 517', 'MW', '09:00 AM - 10:30 AM', 'occupied'),
(254, 'Lab 524', 'MW', '12:00 PM - 01:30 PM', 'occupied'),
(255, 'Lab 524', 'MW', '01:30 PM - 03:00 PM', 'occupied'),
(256, 'Lab 526', 'MW', '07:30 AM - 09:00 AM', 'occupied'),
(257, 'Lab 526', 'MW', '09:00 AM - 10:30 AM', 'occupied'),
(258, 'Lab 517', 'MW', '06:00 PM - 07:30 PM', 'occupied'),
(259, 'Lab 517', 'MW', '04:30 PM - 06:00 PM', 'occupied'),
(260, 'Lab 528', 'MW', '12:00 PM - 01:30 PM', 'occupied'),
(261, 'Lab 526', 'MW', '06:00 PM - 07:30 PM', 'occupied'),
(262, 'Lab 526', 'MW', '07:30 PM - 09:00 PM', 'occupied'),
(263, 'Lab 528', 'MW', '04:30 PM - 06:00 PM', 'occupied'),
(264, 'Lab 530', 'MW', '09:00 AM - 10:30 AM', 'occupied'),
(265, 'Lab 530', 'MW', '12:00 PM - 01:30 PM', 'occupied'),
(266, 'Lab 542', 'MW', '07:30 AM - 09:00 AM', 'occupied'),
(267, 'Lab 542', 'MW', '01:30 PM - 03:00 PM', 'occupied'),
(268, 'Lab 542', 'MW', '03:00 PM - 04:30 PM', 'occupied'),
(269, 'Lab 542', 'MW', '07:30 PM - 09:00 PM', 'occupied'),
(270, 'Lab 544', 'MW', '12:00 PM - 01:30 PM', 'occupied'),
(271, 'Lab 544', 'MW', '04:30 PM - 06:00 PM', 'occupied'),
(272, 'Lab 517', 'TTh', '07:30 AM - 09:00 AM', 'occupied'),
(273, 'Lab 524', 'TTh', '09:00 AM - 10:30 AM', 'occupied'),
(274, 'Lab 517', 'TTh', '03:00 PM - 04:30 PM', 'occupied'),
(275, 'Lab 524', 'TTh', '04:30 PM - 06:00 PM', 'occupied'),
(276, 'Lab 526', 'TTh', '07:30 PM - 09:00 PM', 'occupied'),
(277, 'Lab 526', 'TTh', '01:30 PM - 03:00 PM', 'occupied'),
(278, 'Lab 526', 'TTh', '06:00 PM - 07:30 PM', 'occupied'),
(279, 'Lab 528', 'TTh', '07:30 AM - 09:00 AM', 'occupied'),
(280, 'Lab 528', 'TTh', '09:00 AM - 10:30 AM', 'occupied'),
(281, 'Lab 528', 'TTh', '03:00 PM - 04:30 PM', 'occupied'),
(282, 'Lab 530', 'TTh', '12:00 PM - 01:30 PM', 'occupied'),
(283, 'Lab 530', 'TTh', '04:30 PM - 06:00 PM', 'occupied'),
(284, 'Lab 542', 'TTh', '09:00 AM - 10:30 AM', 'occupied'),
(285, 'Lab 517', 'Fri', '07:30 AM - 09:00 AM', 'occupied'),
(286, 'Lab 517', 'Fri', '09:00 AM - 10:30 AM', 'occupied'),
(287, 'Lab 517', 'Fri', '01:30 PM - 03:00 PM', 'occupied'),
(288, 'Lab 517', 'Fri', '04:30 PM - 06:00 PM', 'occupied'),
(289, 'Lab 524', 'Fri', '07:30 AM - 09:00 AM', 'occupied'),
(290, 'Lab 524', 'Fri', '09:00 AM - 10:30 AM', 'occupied'),
(291, 'Lab 524', 'Fri', '03:00 PM - 04:30 PM', 'occupied'),
(292, 'Lab 524', 'Fri', '06:00 PM - 07:30 PM', 'occupied'),
(293, 'Lab 526', 'Fri', '12:00 PM - 01:30 PM', 'occupied'),
(294, 'Lab 526', 'Fri', '07:30 PM - 09:00 PM', 'occupied'),
(295, 'Lab 528', 'Fri', '09:00 AM - 10:30 AM', 'occupied'),
(296, 'Lab 528', 'Fri', '03:00 PM - 04:30 PM', 'occupied'),
(297, 'Lab 528', 'Fri', '04:30 PM - 06:00 PM', 'occupied'),
(298, 'Lab 530', 'Fri', '04:30 PM - 06:00 PM', 'occupied'),
(299, 'Lab 530', 'Fri', '06:00 PM - 07:30 PM', 'occupied'),
(300, 'Lab 530', 'Fri', '12:00 PM - 01:30 PM', 'occupied'),
(301, 'Lab 542', 'Fri', '07:30 AM - 09:00 AM', 'occupied'),
(302, 'Lab 542', 'Fri', '01:30 PM - 03:00 PM', 'occupied'),
(303, 'Lab 542', 'Fri', '07:30 PM - 09:00 PM', 'occupied'),
(304, 'Lab 544', 'Fri', '07:30 AM - 09:00 AM', 'occupied'),
(305, 'Lab 544', 'Fri', '06:00 PM - 07:30 PM', 'occupied'),
(306, 'Lab 517', 'Sat', '09:00 AM - 10:30 AM', 'available'),
(307, 'Lab 524', 'Sat', '12:00 PM - 01:30 PM', 'occupied'),
(308, 'Lab 524', 'Sat', '03:00 PM - 04:30 PM', 'occupied'),
(309, 'Lab 526', 'Sat', '04:30 PM - 06:00 PM', 'occupied'),
(310, 'Lab 526', 'Sat', '06:00 PM - 07:30 PM', 'occupied'),
(311, 'Lab 528', 'Sat', '12:00 PM - 01:30 PM', 'occupied'),
(312, 'Lab 530', 'Sat', '07:30 AM - 09:00 AM', 'occupied'),
(313, 'Lab 530', 'Sat', '09:00 AM - 10:30 AM', 'occupied'),
(314, 'Lab 530', 'Sat', '07:30 PM - 09:00 PM', 'occupied'),
(315, 'Lab 542', 'Sat', '01:30 PM - 03:00 PM', 'occupied'),
(316, 'Lab 544', 'Sat', '06:00 PM - 07:30 PM', 'occupied'),
(317, 'Lab 544', 'Sat', '09:00 AM - 10:30 AM', 'occupied'),
(318, 'Lab 526', 'Sat', '07:30 AM - 09:00 AM', 'available'),
(319, 'Lab 530', 'MW', '04:30 PM - 06:00 PM', 'occupied'),
(320, 'Lab 517', 'MW', '07:30 AM - 09:00 AM', 'available'),
(321, 'Lab 528', 'MW', '07:30 AM - 09:00 AM', 'occupied'),
(322, 'Lab 524', 'MW', '07:30 AM - 09:00 AM', 'available'),
(323, 'Lab 517', 'MW', '07:30 PM - 09:00 PM', 'occupied'),
(324, 'Lab 524', 'TTh', '07:30 AM - 09:00 AM', 'occupied'),
(325, 'Lab 517', 'MW', '07:30 AM-09:00 AM', 'occupied'),
(326, 'Lab 524', 'MW', '07:30 AM-09:00 AM', 'occupied'),
(327, 'Lab 517', 'TTh', '07:30 AM-09:00 AM', 'occupied');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `course` varchar(50) NOT NULL,
  `yearlevel` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `middlename` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `remaining_sessions` int(11) DEFAULT 30,
  `points` int(11) NOT NULL DEFAULT 0,
  `cover_photo` varchar(255) DEFAULT NULL,
  `survey_completed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `course`, `yearlevel`, `email`, `firstname`, `lastname`, `middlename`, `username`, `password`, `profile_picture`, `role`, `remaining_sessions`, `points`, `cover_photo`, `survey_completed`) VALUES
(1, 'ADMIN001', '', '', 'admin@example.com', '', '', NULL, 'admin', '123', 'Cha Hae-In _ Solo Leveling _ Season 2.jpg', 'admin', 30, 0, NULL, 0),
(24, '22919594', 'Bachelor of Science in Information Technology', '3', 'christine@gmail.com', 'Christine Anne', 'Alesna', 'A', 'christine', '$2y$10$6eM3dXbP9cGgVuJMe/5/sOLMndcrQN9Ai4QRPIZCsYtTjFA7JWVA6', '22919594.jpg', 'admin', 30, 0, NULL, 0),
(25, '20949194', 'Bachelor of Science in Information Technology', '3', 'brylgorgonio@gmail.com', 'Bryl', 'Gorgonio', 'Darel', 'bryl', '$2y$10$kctT1aCFnU/nTi7WdLokkeka5B1q9IalzS.vb28RF7w9cQjjA9bkK', '20949194_1747128827.jpg', 'student', 30, 3, NULL, 0),
(26, '21950195', 'Bachelor of Science in Information Technology', '3', 'kobe@gmail.com', 'Kobe Bryan', 'Amaro', 'A', 'kobe', '$2y$10$tRn0VyyfQZMYf3QCtfpk7OIR4iV0I87clBG.zcBGUEl4ysAr9uRtK', '21950195_1747128136.jpg', 'student', 28, 1, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_name` (`lab_name`,`pc_number`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `rewards_log`
--
ALTER TABLE `rewards_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `static_lab_schedules`
--
ALTER TABLE `static_lab_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_name` (`lab_name`,`day_group`,`time_slot`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idno` (`idno`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rewards_log`
--
ALTER TABLE `rewards_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `static_lab_schedules`
--
ALTER TABLE `static_lab_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=328;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`idno`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`idno`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rewards_log`
--
ALTER TABLE `rewards_log`
  ADD CONSTRAINT `rewards_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `satisfaction_surveys`
--
ALTER TABLE `satisfaction_surveys`
  ADD CONSTRAINT `satisfaction_surveys_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD CONSTRAINT `sit_in_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
