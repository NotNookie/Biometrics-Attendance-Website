-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 08, 2026 at 11:52 PM
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
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(23, 'john_doe', '2026-04-09', '08:00:00', '17:00:00', 'Regular', 1.00, '07:40:03', '07:41:23', 'None', 'HQ');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_key` (`employee_key`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_key`, `name`) VALUES
(1, 'john_doe', 'John Doe'),
(2, 'ilele_ray', 'Ilele Ray'),
(3, 'maria_santos', 'Maria Santos'),
(4, 'juan_cruz', 'Juan Cruz'),
(5, 'anna_reyes', 'Anna Reyes');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
