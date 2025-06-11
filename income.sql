-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2024 at 11:28 AM
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
-- Database: `atioffice`
--

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `assignment` varchar(255) DEFAULT NULL,
  `income_title` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`id`, `date`, `assignment`, `income_title`, `amount`, `assignment_id`) VALUES
(58, '2024-03-23', NULL, 'Gunay', 181.00, 1),
(59, '2024-04-05', NULL, '04.04 tarixi kassası', 1568.00, 2),
(60, '2024-04-13', NULL, 'Sefden xirda daxil oldu', 200.00, 2),
(61, '2024-04-15', NULL, 'Muradeli turdan qaliq', 510.00, 1),
(63, '2024-08-03', NULL, 'Nazper - 02.08 Şahdağ turundan qalıq', 102.00, 1),
(64, '2024-08-03', NULL, 'Sofiya Valuyeva - 2 PAX - 02.08 - O/C + B/N - Qalıq borc', 140.00, 2),
(66, '2024-08-04', NULL, 'Ashraf guide (Gabala)', 49.00, 1),
(67, '2024-08-04', NULL, 'Ruslan guide (Gobustan)', 63.00, 1),
(68, '2024-08-05', NULL, 'Solmaz guide(Gobustan-Absheron price)', 132.00, 1),
(69, '2024-08-05', NULL, 'Aleksandra Len Old City', 30.00, 2),
(70, '2024-08-08', NULL, 'Valentin Rzayev', 80.00, 2),
(71, '2024-08-08', NULL, 'Ruslan Gobustan Absheron', 136.00, 1),
(72, '2024-08-09', NULL, 'Ruslan bey Baku-night 08.08', 140.00, 1),
(73, '2024-08-24', NULL, 'kASSADAN XIRDA PUL', 92.00, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
