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
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `assignment` varchar(255) DEFAULT NULL,
  `expense_title` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `assignment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense`
--

INSERT INTO `expense` (`id`, `date`, `assignment`, `expense_title`, `amount`, `assignment_id`) VALUES
(67, '2024-03-20', NULL, 'Moonlight Hotel, ZamZam hotel', 320.00, 9),
(70, '2024-03-23', NULL, 'boutique1883', 240.00, 9),
(71, '2024-04-13', NULL, 'Bukletler ucun taxi', 10.00, 9),
(72, '2024-04-20', NULL, 'Victor Chuvayev Kompensaciya', 230.00, 1),
(73, '2024-04-23', NULL, 'Online Mushterinin odenishi', 250.00, 9),
(74, '2024-04-23', NULL, '40 Manat Alike tur kecdiyine gore', 40.00, 9),
(75, '2024-04-29', NULL, 'Aydin bey turu kecdiyine gore', 40.00, 9),
(78, '2024-04-29', NULL, 'hEYDERE 90 AZN', 90.00, 1),
(79, '2024-05-06', NULL, 'Rovhsna - M10', 450.00, 9),
(80, '2024-05-08', NULL, 'Rovshan M10', 555.00, 9),
(81, '2024-05-09', NULL, 'Rovshan m10', 120.00, 9),
(82, '2024-05-10', NULL, '220 azn M10 odenish aldim qonaqdan mendedi pul (Rovshen)', 220.00, 9),
(83, '2024-05-10', NULL, 'Rovshan M10', 220.00, 9),
(84, '2024-05-11', NULL, 'Kompensaciya Gabala', 120.00, 9),
(85, '2024-05-16', NULL, 'su pulu', 20.00, 9),
(86, '2024-05-18', NULL, 'Rovshan M10', 101.00, 9),
(87, '2024-05-19', NULL, 'Rovshan M10', 994.00, 9),
(88, '2024-05-20', NULL, 'Hotel', 110.00, 1),
(89, '2024-05-21', NULL, 'Rovshan M10', 440.00, 1),
(90, '2024-05-25', NULL, 'Aydin ', 420.00, 9),
(91, '2024-05-27', NULL, 'Togrul - M10', 100.00, 9),
(92, '2024-05-27', NULL, 'Davud bəydə qalıq turdan', 190.00, 9),
(93, '2024-05-31', NULL, 'Togrul M10', 1072.00, 9),
(94, '2024-06-01', NULL, 'Alik guide', 30.00, 9),
(95, '2024-06-02', NULL, 'Rovshan M10', 540.00, 9),
(96, '2024-06-02', NULL, 'Korm', 10.00, 9),
(97, '2024-06-05', NULL, 'Askerov Aydin', 100.00, 9),
(98, '2024-06-06', NULL, 'Broshur', 12.50, 9),
(99, '2024-06-08', NULL, 'Rovshan M10', 760.00, 9),
(100, '2024-06-08', NULL, 'Rovshan bey bilir', 9.00, 9),
(101, '2024-06-08', NULL, 'Emin bey ', 66.00, 9),
(102, '2024-06-10', NULL, 'Hotel', 160.00, 9),
(103, '2024-06-10', NULL, '', 0.00, 1),
(104, '2024-06-10', NULL, 'Heydere', 201.00, 9),
(105, '2024-06-10', NULL, 'Abbas bey yanacaq', 20.00, 9),
(106, '2024-06-10', NULL, 'Hotel', 70.00, 9),
(107, '2024-06-13', NULL, 'Broshur', 10.00, 9),
(108, '2024-06-14', NULL, 'Rovshan M10', 120.00, 9),
(109, '2024-06-15', NULL, 'Rovshan M10', 385.00, 9),
(110, '2024-06-16', NULL, 'Zam Zam Hotel ', 110.00, 1),
(111, '2024-06-16', NULL, 'Rovshan M10', 480.00, 9),
(112, '2024-06-17', NULL, 'Nazperi qonagi', 220.00, 9),
(113, '2024-06-20', NULL, 'M10', 1120.00, 9),
(116, '2024-06-21', NULL, 'm10', 584.00, 9),
(117, '2024-06-21', NULL, 'SU140 - Kompressor', 450.00, 9),
(118, '2024-06-21', NULL, 'Mais - Taxi Pulu', 11.00, 9),
(119, '2024-06-21', NULL, 'Sultan Inn Hotel', 280.00, 1),
(128, '2024-06-22', NULL, 'M10', 740.00, 9),
(129, '2024-06-22', NULL, 'Aydin', 445.00, 9),
(130, '2024-06-22', NULL, 'Togrul', 350.00, 9),
(131, '2024-06-22', NULL, 'Refund', 40.00, 9),
(132, '2024-06-22', NULL, 'Xəyyam', 500.00, 9),
(133, '2024-06-23', NULL, 'Rovshan M10', 60.00, 9),
(134, '2024-06-25', NULL, 'Aydin ', 285.00, 9),
(135, '2024-06-25', NULL, 'Alik Guide', 30.00, 1),
(137, '2024-06-26', NULL, 'Rovshan M10', 535.00, 9),
(139, '2024-06-26', NULL, '25.06 tarixi ödənilib pulu', 220.00, 9),
(140, '2024-06-28', NULL, 'Rovshan M10', 60.00, 9),
(141, '2024-06-29', NULL, 'SU PULU', 20.00, 9),
(142, '2024-06-29', NULL, 'Alik', 35.00, 9),
(143, '2024-07-01', NULL, 'Togrul', 20.00, 9),
(144, '2024-07-03', NULL, 'Rovshan M10', 520.00, 9),
(145, '2024-07-05', NULL, 'Togrul - M10', 240.00, 9),
(146, '2024-07-06', NULL, 'Rovshan M10', 55.00, 9),
(147, '2024-07-07', NULL, 'Rovshan M10', 183.00, 9),
(154, '2024-07-10', NULL, 'heyder', 50.00, 1),
(155, '2024-07-10', NULL, 'Su karta ', 20.00, 9),
(156, '2024-07-10', NULL, 'baliga yemek ', 10.00, 9),
(157, '2024-07-10', NULL, 'kanistr su', 15.00, 9),
(158, '2024-07-10', NULL, 'm 10', 49.00, 9),
(159, '2024-07-11', NULL, 'Buklet', 9.00, 9),
(160, '2024-07-13', NULL, 'Rovshan M10', 420.00, 9),
(161, '2024-07-14', NULL, 'Heyder guide', 40.00, 9),
(166, '2024-07-14', NULL, 'Rovshan M 10', 400.00, 9),
(168, '2024-07-15', NULL, 'Taksi buklet', 9.00, 1),
(169, '2024-07-18', NULL, 'brochure', 10.00, 9),
(170, '2024-07-18', NULL, 'Rovshan M10', 220.00, 9),
(171, '2024-07-19', NULL, 'Ehtibar bey', 250.00, 9),
(172, '2024-07-20', NULL, 'm10', 130.00, 1),
(173, '2024-07-21', NULL, 'Rovshan M10', 240.00, 9),
(174, '2024-07-22', NULL, 'Rovshan M10', 340.00, 9),
(175, '2024-07-24', NULL, 'Rovshan M10', 200.00, 9),
(176, '2024-07-24', NULL, 'Heydar ', 50.00, 9),
(177, '2024-07-25', NULL, 'Broshur', 15.00, 9),
(178, '2024-07-26', NULL, 'Rovshan M10', 400.00, 9),
(179, '2024-07-27', NULL, 'Rovshan M10', 340.00, 9),
(180, '2024-07-28', NULL, 'Mais avans', 100.00, 1),
(181, '2024-07-29', NULL, 'refund', 100.00, 9),
(182, '2024-07-30', NULL, 'Togrul - M10', 320.00, 9),
(183, '2024-07-30', NULL, 'Lyubov Svetlova ayin 3 Lahic cancel (Mais)', 180.00, 9),
(184, '2024-07-31', NULL, 'Shirinniy', 100.00, 1),
(185, '2024-07-31', NULL, 'M10', 300.00, 1),
(186, '2024-08-01', NULL, 'Rovshan M10', 300.00, 9),
(187, '2024-08-02', NULL, 'Yeddi gozel', 852.00, 9),
(188, '2024-08-02', NULL, 'Ehtibar bey borc', 2.00, 9),
(189, '2024-08-03', NULL, 'Rovshan m10', 440.00, 9),
(191, '2024-08-03', NULL, 'Sema/Tuncay (Baku-night)', 12.00, 9),
(192, '2024-08-03', NULL, 'Sema Baku-night 01.08.24', 6.00, 9),
(193, '2024-08-03', NULL, 'Vüsalə - Qob+Ab. - 03.08 tarixi üçün xərc', 145.00, 9),
(194, '2024-08-04', NULL, 'Rovshan M10', 300.00, 9),
(195, '2024-08-04', NULL, 'Heyder Guide', 17.00, 1),
(196, '2024-08-05', NULL, 'Mushviq (Gabala-Sheki-Qakh)', 500.00, 9),
(197, '2024-08-05', NULL, 'Heydar', 50.00, 9),
(198, '2024-08-05', NULL, 'Mehman At turu xerci', 195.00, 9),
(199, '2024-08-06', NULL, 'Ashraf Guide - 06.08 - Qebele - Xerc', 505.00, 9),
(201, '2024-08-06', NULL, 'Abbas bey yemek pulu ', 6.00, 9),
(202, '2024-08-06', NULL, 'Sema Xanim oz cibinnen vermishdi', 155.00, 9),
(204, '2024-08-06', NULL, 'M10', 305.00, 9),
(206, '2024-08-07', NULL, 'Tuncay bey 18 PAX Gebelenin xerci', 629.00, 9),
(207, '2024-08-07', NULL, 'Akhmed and Alik guide', 70.00, 1),
(208, '2024-08-08', NULL, 'buklet taxi', 10.00, 9),
(209, '2024-08-08', NULL, 'Kamran Guide', 50.00, 9),
(211, '2024-08-08', NULL, 'Refund to Lars Karlsson ', 44.00, 9),
(212, '2024-08-08', NULL, 'Sema lunch', 6.00, 9),
(213, '2024-08-08', NULL, 'Solmaz', 379.00, 9),
(214, '2024-08-08', NULL, 'Etibar bey', 12.00, 9),
(215, '2024-08-08', NULL, 'Mais avans', 500.00, 9),
(216, '2024-08-09', NULL, 'Akhmed Guide', 46.00, 9),
(218, '2024-08-09', NULL, 'Mushviq bey ', 145.00, 9),
(219, '2024-08-09', NULL, 'Rovshan M10', 9.00, 9),
(220, '2024-08-09', NULL, 'Kamran Guide', 35.00, 9),
(223, '2024-08-09', NULL, 'M10', 175.00, 9),
(226, '2024-08-12', NULL, 'Sultan Inn Hotel - 1 PAX - Shahdagh - 13.08', 22.00, 5),
(228, '2024-08-14', NULL, 'Ahmad bey - 2 PAX - Old City - Sultan Inn guest', 52.00, 5),
(229, '2024-08-14', NULL, 'Rovshan M10', 17.00, 9),
(230, '2024-08-14', NULL, 'Alik guide', 35.00, 9),
(231, '2024-08-15', NULL, 'baliq yemek', 10.00, 9),
(232, '2024-08-15', NULL, 'Vusala', 6.00, 9),
(233, '2024-08-18', NULL, 'Rovshan leo bank', 90.00, 9),
(234, '2024-08-19', NULL, 'Vusala - Guide ', 240.00, 1),
(235, '2024-08-19', NULL, 'Togrul - M10', 360.00, 9),
(236, '2024-08-19', NULL, 'Buklet taksi pulu', 8.00, 1),
(238, '2024-08-20', NULL, 'refund Lahic Aliza Shanzay', 100.00, 9),
(239, '2024-08-21', NULL, 'Rovshan M10', 400.00, 1),
(240, '2024-08-22', NULL, 'aLIK GUIDE', 45.00, 9),
(241, '2024-08-22', NULL, 'ZIYA', 100.00, 9),
(242, '2024-08-23', NULL, 'Alik Guide', 58.00, 9),
(243, '2024-08-23', NULL, 'm 10 rovshan', 500.00, 9),
(249, '2024-08-24', NULL, 'Heyder Guide', 34.00, 9),
(251, '2024-08-24', NULL, 'Rovshan m 10', 1070.00, 9),
(252, '2024-08-26', NULL, 'Rovshan M10', 320.00, 9),
(253, '2024-08-27', NULL, 'Rovshan M10', 700.00, 9),
(254, '2024-08-31', NULL, 'Togrul ', 150.00, 9),
(255, '2024-09-02', NULL, 'Togrul M10', 120.00, 9);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
