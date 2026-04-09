-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 09, 2026 at 12:18 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nookie`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `schedule_start` time DEFAULT NULL,
  `schedule_end` time DEFAULT NULL,
  `pay_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(5,2) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `absence` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_key`, `date`, `schedule_start`, `schedule_end`, `pay_code`, `amount`, `time_in`, `time_out`, `absence`, `transfer`) VALUES
(1, 'john_doe', '2026-04-01', '08:00:00', '17:00:00', 'Regular', 1.00, '08:02:00', '17:01:00', 'None', 'HQ'),
(2, 'john_doe', '2026-04-02', '08:00:00', '17:00:00', 'Regular', 1.00, '08:10:00', NULL, 'None', 'HQ'),
(3, 'john_doe', '2026-04-03', '08:00:00', '17:00:00', 'Regular', 1.00, '07:59:00', '17:05:00', 'None', 'HQ'),
(4, 'john_doe', '2026-04-04', '08:00:00', '17:00:00', 'Regular', 1.00, '08:20:00', '17:00:00', 'Late', 'HQ'),
(5, 'john_doe', '2026-04-05', '08:00:00', '17:00:00', 'Regular', 1.00, NULL, NULL, 'Absent', 'HQ'),
(6, 'ilele_ray', '2026-04-01', '07:00:00', '16:00:00', 'Regular', 1.00, '07:01:00', '16:02:00', 'None', 'Production'),
(7, 'ilele_ray', '2026-04-02', '07:00:00', '16:00:00', 'Regular', 1.00, '07:05:00', '16:00:00', 'None', 'Production'),
(8, 'ilele_ray', '2026-04-03', '07:00:00', '16:00:00', 'Regular', 1.00, '07:12:00', '16:05:00', 'Late', 'Production'),
(9, 'ilele_ray', '2026-04-04', '07:00:00', '16:00:00', 'Regular', 1.00, NULL, NULL, 'Absent', 'Production'),
(10, 'ilele_ray', '2026-04-05', '07:00:00', '16:00:00', 'Regular', 1.00, '07:00:00', '15:50:00', 'Undertime', 'Production'),
(11, 'maria_santos', '2026-04-01', '09:00:00', '18:00:00', 'Regular', 1.00, '09:00:00', '18:01:00', 'None', 'HR'),
(12, 'maria_santos', '2026-04-02', '09:00:00', '18:00:00', 'Regular', 1.00, '09:15:00', '18:00:00', 'Late', 'HR'),
(13, 'maria_santos', '2026-04-03', '09:00:00', '18:00:00', 'Regular', 1.00, '08:55:00', '18:10:00', 'None', 'HR'),
(14, 'maria_santos', '2026-04-04', '09:00:00', '18:00:00', 'Regular', 1.00, NULL, NULL, 'Absent', 'HR'),
(15, 'juan_cruz', '2026-04-01', '08:00:00', '17:00:00', 'Regular', 1.00, '08:00:00', '17:00:00', 'None', 'Warehouse'),
(16, 'juan_cruz', '2026-04-02', '08:00:00', '17:00:00', 'Regular', 1.00, '08:25:00', '17:00:00', 'Late', 'Warehouse'),
(17, 'juan_cruz', '2026-04-03', '08:00:00', '17:00:00', 'Regular', 1.00, '08:05:00', '16:30:00', 'Undertime', 'Warehouse'),
(18, 'juan_cruz', '2026-04-04', '08:00:00', '17:00:00', 'Regular', 1.00, NULL, NULL, 'Absent', 'Warehouse'),
(19, 'anna_reyes', '2026-04-01', '08:30:00', '17:30:00', 'Regular', 1.00, '08:30:00', '17:30:00', 'None', 'Finance'),
(20, 'anna_reyes', '2026-04-02', '08:30:00', '17:30:00', 'Regular', 1.00, '08:45:00', '17:30:00', 'Late', 'Finance'),
(21, 'anna_reyes', '2026-04-03', '08:30:00', '17:30:00', 'Regular', 1.00, '08:20:00', '17:40:00', 'None', 'Finance'),
(22, 'anna_reyes', '2026-04-04', '08:30:00', '17:30:00', 'Regular', 1.00, NULL, NULL, 'Absent', 'Finance'),
(23, 'john_doe', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '07:40:03', '07:41:23', 'None', 'HQ'),
(24, 'juan_cruz', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '07:57:26', '07:58:31', 'None', 'HQ'),
(25, 'maria_santos', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '08:01:41', NULL, 'Late', 'HQ'),
(26, 'ilele_ray', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '14:36:17', '14:36:50', 'Late', 'HQ'),
(27, '4I5PEI', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '15:11:45', '15:11:49', 'Late', 'HQ'),
(28, 'EIYF13', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '18:42:15', NULL, 'Late', 'HQ');

-- --------------------------------------------------------

--
-- Table structure for table `biometric_devices`
--

DROP TABLE IF EXISTS `biometric_devices`;
CREATE TABLE IF NOT EXISTS `biometric_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int DEFAULT '4370',
  `status` enum('connected','disconnected') COLLATE utf8mb4_unicode_ci DEFAULT 'disconnected',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `biometric_devices`
--

INSERT INTO `biometric_devices` (`id`, `device_name`, `ip_address`, `port`, `status`, `created_at`) VALUES
(1, 'Ztecko', '192.168.2.2', 4370, 'disconnected', '2026-04-09 06:27:22'),
(2, 'Ztecko', '192.168.2.2', 4370, 'disconnected', '2026-04-09 06:28:30');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_in` time NOT NULL,
  `shift_out` time NOT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_key` (`employee_key`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_key`, `name`, `department`, `position`, `email`, `mobile`, `shift_in`, `shift_out`, `status`) VALUES
(1, '4I5PEI', 'Mady Evangelista', 'IT', 'Wala lang', 'Biot@gmail.com', '093121312', '08:00:00', '17:00:00', 'Active'),
(2, 'XATOZX', 'Jey', 'HR', 'HR', 'dasdas@gmail.com', '098', '09:00:00', '19:00:00', 'Active'),
(3, 'EIYF13', 'Mr. Bean', 'IT', 'Consultant', 'bean@gmail.com', '097127362', '07:00:00', '21:00:00', 'Active');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
