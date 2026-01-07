-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2026 at 08:36 PM
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
-- Database: `disaster_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `pejabatdaerah_aid_distribution`
--

CREATE TABLE `pejabatdaerah_aid_distribution` (
  `aid_distribution_id` int(11) NOT NULL,
  `pejabatdaerah_id` int(11) NOT NULL,
  `aid_type` varchar(255) NOT NULL,
  `penghulu_id` int(11) NOT NULL,
  `distribution_title` varchar(255) NOT NULL,
  `distribution_desc` varchar(255) NOT NULL,
  `distribution_date` date NOT NULL,
  `distribution_location` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pejabatdaerah_aid_distribution`
--

INSERT INTO `pejabatdaerah_aid_distribution` (`aid_distribution_id`, `pejabatdaerah_id`, `aid_type`, `penghulu_id`, `distribution_title`, `distribution_desc`, `distribution_date`, `distribution_location`, `created_at`) VALUES
(1, 9, 'transportation', 6, 'Transportation to home', 'on the way', '2026-01-08', 'Changlun', '2026-01-08 02:06:50'),
(2, 9, 'transportation', 6, 'Transportation to home', 'Success', '2026-01-08', 'Changlun', '2026-01-08 03:24:48'),
(3, 9, 'transportation', 6, 'Transportation to home', 'Success', '2026-01-08', 'Changlun', '2026-01-08 03:25:37'),
(4, 9, 'water', 6, 'Water supply truck', 'On the way', '2026-01-08', 'Sintok', '2026-01-08 03:26:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pejabatdaerah_aid_distribution`
--
ALTER TABLE `pejabatdaerah_aid_distribution`
  ADD PRIMARY KEY (`aid_distribution_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pejabatdaerah_aid_distribution`
--
ALTER TABLE `pejabatdaerah_aid_distribution`
  MODIFY `aid_distribution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
