-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2024 at 04:10 PM
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
-- Database: `cpms`
--

-- --------------------------------------------------------

--
-- Table structure for table `ccs_rules`
--

CREATE TABLE `ccs_rules` (
  `id` int(11) NOT NULL,
  `project` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `role` varchar(100) NOT NULL,
  `tenure` varchar(100) NOT NULL,
  `case_chronology` text DEFAULT NULL,
  `consequences` varchar(50) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date NOT NULL,
  `supporting_doc_url` varchar(255) DEFAULT NULL,
  `status` enum('active','deactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ccs_rules`
--

INSERT INTO `ccs_rules` (`id`, `project`, `name`, `nik`, `role`, `tenure`, `case_chronology`, `consequences`, `effective_date`, `end_date`, `supporting_doc_url`, `status`, `created_at`) VALUES
(4, 'GEC_ST', 'testing', '222111', 'Agent', '2 years 7 months', 'lorem ipsum', 'Warning Letter 1', '2022-01-13', '2022-07-13', '/storage/ccs_docs/676a6d2622b7d_20241224.xlsx', 'active', '2024-12-24 08:13:26'),
(6, 'GEC_MOD', 'contoso', '232323', 'Admin', '0 days', 'asdfasf', 'WR1', '2024-12-25', '2025-12-24', 'uploads/ccs_docs/doc_676d1ce154a3a.xlsx', 'active', '2024-12-26 09:07:45'),
(8, 'GEC_ST', 'Aditya Ilham Farohi', '2242691', 'Agent', '2 months', 'asdfasfa', 'WR1', '2024-12-26', '2025-12-25', 'uploads/ccs_docs/doc_676d1db702a2d.xlsx', 'active', '2024-12-26 09:11:19'),
(9, 'GEC_ST', 'Aditya Ilham Farohi', '2242691', 'Agent', '2 months', 'adfafdasf', 'WR1', '2024-12-26', '2025-12-25', 'uploads/ccs_docs/doc_676d1fa30c4b6.xlsx', 'active', '2024-12-26 09:19:31');

-- --------------------------------------------------------

--
-- Table structure for table `employee_active`
--

CREATE TABLE `employee_active` (
  `NIK` varchar(20) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `employee_email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `project` varchar(100) NOT NULL,
  `join_date` date NOT NULL,
  `password` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_active`
--

INSERT INTO `employee_active` (`NIK`, `employee_name`, `employee_email`, `role`, `project`, `join_date`, `password`) VALUES
('2123123', 'asdfadfa', 'asdfasdf@gmail.com', 'Admin', 'GEC_MOD', '2024-12-24', '$2y$10$vrGpUGosPp3Vftl5MQsUHO9TgkNYNwBUqK6.AoVVqXD1ospPAp1Jq'),
('22105034', 'ASDFA', 'dsfda@gmail.com', 'Admin', 'GEC_MOD', '2024-12-26', '$2y$10$WwyBQzWLr8XkDp.T4GG4wOnyPCx.dLCdVGMfuRPE6prpit6CKDC1e'),
('2210507', 'Gabriel Dwi', 'gabriel.novian@trans-cosmos.co.id', 'Super_User', 'GEC_ST', '2022-05-12', '$2y$10$IObE8rrrgmPyP338FcKLCeo8pLhvKCWJyFPodlpyNqJokd3hoqrau'),
('2221111', 'testing', 'testing_usr@gmail.com', 'Agent', 'GEC_ST', '2022-05-12', '$2y$10$J3DOEEwxVcNLGzycnhW8S.vvgOaCYc.w3EU3M0zuZSUokNjGfsYIC'),
('2221211', 'testing_mod', 'testing_mod@gmail.com', 'Agent', 'GEC_MOD', '2022-05-12', '$2y$10$dbHVXk8cnbKwjoeLkCUHruVVuIrZDrlqJOUlNxjXUiEq0FRfv/evu'),
('2242691', 'Aditya Ilham Farohi', 'aditya.ilham@staging.com', 'Agent', 'GEC_ST', '2024-10-08', '$2y$10$xhoKNmv4dlz9juVLRv3/YODhhmyjsRwXRY799yErV/GZT//dshapa'),
('231434', 'asdfadfa', 'adsfdasf@gmail.com', 'Admin', 'GEC_MOD', '2024-12-24', '$2y$10$6tsyYhEl2fbu1GYGWZtqJu7Vl/26ytjutDch3mVdJDKKxDJ3LYKLG'),
('232323', 'contoso', 'contoso@gmail.com', 'Admin', 'GEC_MOD', '2024-12-26', '$2y$10$EIzmPw/9OjN9TFerISWISelNdmoiLPFDpGOvtCbH45K2HnB8uur2C'),
('2344', 'rqewrqewr', 'asdfa@gmail.com', 'Agent', 'GEC_MOD', '2024-12-27', '$2y$10$64VhfNByjFBTUtuyBawDMulh6spI0nTEO2PYPH9anS0nCzRFru09i'),
('85930434', 'newhire', 'hire@gmail.com', 'Admin', 'GEC_MOD', '2024-12-26', '$2y$10$oZZCcjBj.JoY7QSJHs5QHeovRtY1LSQySStU2vxNHNO0vW/TAXcRu');

-- --------------------------------------------------------

--
-- Table structure for table `individual_staging`
--

CREATE TABLE `individual_staging` (
  `id` int(11) NOT NULL,
  `NIK` varchar(20) DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `kpi_metrics` varchar(50) DEFAULT NULL,
  `queue` varchar(50) DEFAULT NULL,
  `january` decimal(5,2) DEFAULT NULL,
  `february` decimal(5,2) DEFAULT NULL,
  `march` decimal(5,2) DEFAULT NULL,
  `april` decimal(5,2) DEFAULT NULL,
  `may` decimal(5,2) DEFAULT NULL,
  `june` decimal(5,2) DEFAULT NULL,
  `july` decimal(5,2) DEFAULT NULL,
  `august` decimal(5,2) DEFAULT NULL,
  `september` decimal(5,2) DEFAULT NULL,
  `october` decimal(5,2) DEFAULT NULL,
  `november` decimal(5,2) DEFAULT NULL,
  `december` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `individual_staging`
--

INSERT INTO `individual_staging` (`id`, `NIK`, `employee_name`, `kpi_metrics`, `queue`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`, `created_at`) VALUES
(14, '2242691', 'Aditya Ilham Farohi', 'SL 01', 'Buyer', 100.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-23 13:10:24'),
(15, '2242691', 'Aditya Ilham Farohi', 'RSAT', 'Buyer', 100.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-23 13:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod`
--

CREATE TABLE `kpi_gec_mod` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `week1` decimal(10,2) DEFAULT NULL,
  `week2` decimal(10,2) DEFAULT NULL,
  `week3` decimal(10,2) DEFAULT NULL,
  `week4` decimal(10,2) DEFAULT NULL,
  `week5` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kpi_gec_mod`
--

INSERT INTO `kpi_gec_mod` (`id`, `queue`, `kpi_metrics`, `target`, `target_type`, `week1`, `week2`, `week3`, `week4`, `week5`) VALUES
(126, 'Live', 'AHT', '90', 'percentage', NULL, NULL, NULL, NULL, NULL),
(129, 'Product', 'AHT', '95', 'percentage', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod_individual`
--

CREATE TABLE `kpi_gec_mod_individual` (
  `id` int(11) NOT NULL,
  `NIK` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `week1` decimal(10,2) DEFAULT NULL,
  `week2` decimal(10,2) DEFAULT NULL,
  `week3` decimal(10,2) DEFAULT NULL,
  `week4` decimal(10,2) DEFAULT NULL,
  `week5` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod_individual_mon`
--

CREATE TABLE `kpi_gec_mod_individual_mon` (
  `id` int(11) NOT NULL,
  `NIK` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `january` decimal(10,2) DEFAULT NULL,
  `february` decimal(10,2) DEFAULT NULL,
  `march` decimal(10,2) DEFAULT NULL,
  `april` decimal(10,2) DEFAULT NULL,
  `may` decimal(10,2) DEFAULT NULL,
  `june` decimal(10,2) DEFAULT NULL,
  `july` decimal(10,2) DEFAULT NULL,
  `august` decimal(10,2) DEFAULT NULL,
  `september` decimal(10,2) DEFAULT NULL,
  `october` decimal(10,2) DEFAULT NULL,
  `november` decimal(10,2) DEFAULT NULL,
  `december` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kpi_gec_mod_individual_mon`
--

INSERT INTO `kpi_gec_mod_individual_mon` (`id`, `NIK`, `employee_name`, `queue`, `kpi_metrics`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`) VALUES
(1, '2221211', 'testing_mod', 'Live', 'AHT', 1.00, 2.00, 3.00, 4.00, 5.00, 6.00, 7.00, 8.00, 9.00, 10.00, 11.00, 12.00),
(2, '85930434', 'newhire', 'Live,Product', 'AHT', 12.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '2221211', 'testing_mod', 'Live,Product', 'AHT', 15.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod_mon`
--

CREATE TABLE `kpi_gec_mod_mon` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `january` decimal(10,2) DEFAULT NULL,
  `february` decimal(10,2) DEFAULT NULL,
  `march` decimal(10,2) DEFAULT NULL,
  `april` decimal(10,2) DEFAULT NULL,
  `may` decimal(10,2) DEFAULT NULL,
  `june` decimal(10,2) DEFAULT NULL,
  `july` decimal(10,2) DEFAULT NULL,
  `august` decimal(10,2) DEFAULT NULL,
  `september` decimal(10,2) DEFAULT NULL,
  `october` decimal(10,2) DEFAULT NULL,
  `november` decimal(10,2) DEFAULT NULL,
  `december` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kpi_gec_mod_mon`
--

INSERT INTO `kpi_gec_mod_mon` (`id`, `queue`, `kpi_metrics`, `target`, `target_type`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`) VALUES
(44, 'Live', 'AHT', '90', 'percentage', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(59, 'Product', 'AHT', '95', 'percentage', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod_mon_values`
--

CREATE TABLE `kpi_gec_mod_mon_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kpi_gec_mod_mon_values`
--

INSERT INTO `kpi_gec_mod_mon_values` (`id`, `kpi_id`, `month`, `value`) VALUES
(77, 44, 1, 1.00),
(78, 44, 2, 2.00),
(79, 44, 3, 1.00),
(80, 44, 4, 2.00),
(81, 44, 5, 1.00),
(82, 44, 6, 2.00),
(83, 44, 7, 1.00),
(84, 44, 8, 2.00),
(85, 44, 9, 1.00),
(86, 44, 10, 2.00),
(87, 44, 11, 1.00),
(88, 44, 12, 2.00),
(161, 59, 1, 95.00),
(162, 59, 2, 95.00),
(163, 59, 3, 95.00),
(164, 59, 4, 95.00),
(165, 59, 5, 95.00),
(166, 59, 6, 95.00),
(167, 59, 7, 95.00),
(168, 59, 8, 95.00),
(169, 59, 9, 95.00),
(170, 59, 10, 95.00),
(171, 59, 11, 95.00),
(172, 59, 12, 95.00);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_mod_values`
--

CREATE TABLE `kpi_gec_mod_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st`
--

CREATE TABLE `kpi_gec_st` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_individual`
--

CREATE TABLE `kpi_gec_st_individual` (
  `id` int(11) NOT NULL,
  `NIK` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `week1` decimal(10,2) DEFAULT NULL,
  `week2` decimal(10,2) DEFAULT NULL,
  `week3` decimal(10,2) DEFAULT NULL,
  `week4` decimal(10,2) DEFAULT NULL,
  `week5` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_individual_mon`
--

CREATE TABLE `kpi_gec_st_individual_mon` (
  `id` int(11) NOT NULL,
  `NIK` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `january` decimal(10,2) DEFAULT NULL,
  `february` decimal(10,2) DEFAULT NULL,
  `march` decimal(10,2) DEFAULT NULL,
  `april` decimal(10,2) DEFAULT NULL,
  `may` decimal(10,2) DEFAULT NULL,
  `june` decimal(10,2) DEFAULT NULL,
  `july` decimal(10,2) DEFAULT NULL,
  `august` decimal(10,2) DEFAULT NULL,
  `september` decimal(10,2) DEFAULT NULL,
  `october` decimal(10,2) DEFAULT NULL,
  `november` decimal(10,2) DEFAULT NULL,
  `december` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kpi_gec_st_individual_mon`
--

INSERT INTO `kpi_gec_st_individual_mon` (`id`, `NIK`, `employee_name`, `queue`, `kpi_metrics`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`) VALUES
(10, '2221877', 'Annisa Sulistyaningsih', 'Buyer ', 'Resolution Rate', 90.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '2221877', 'Annisa Sulistyaningsih', 'Buyer ', 'RSAT', 90.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '222111', 'testing', 'Buyer ', 'RSAT', 90.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_mon`
--

CREATE TABLE `kpi_gec_st_mon` (
  `id` int(11) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `kpi_metrics` varchar(255) NOT NULL,
  `target` varchar(50) NOT NULL,
  `target_type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_mon_values`
--

CREATE TABLE `kpi_gec_st_mon_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_gec_st_values`
--

CREATE TABLE `kpi_gec_st_values` (
  `id` int(11) NOT NULL,
  `kpi_id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_namelist`
--

CREATE TABLE `project_namelist` (
  `id` int(11) NOT NULL,
  `main_project` varchar(255) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `job_code` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `project_namelist`
--

INSERT INTO `project_namelist` (`id`, `main_project`, `project_name`, `unit_name`, `job_code`, `created_at`) VALUES
(1, 'TikTok', 'GEC_ST', 'Unit 4 OPG 5', '010101', '2024-12-15 05:43:25'),
(3, 'TikTok', 'GEC_MOD', '123123', '123123123', '2024-12-24 00:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `role_mgmt`
--

CREATE TABLE `role_mgmt` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `role_mgmt`
--

INSERT INTO `role_mgmt` (`id`, `role`) VALUES
(9, 'Admin'),
(10, 'Agent'),
(28, 'Conselor'),
(1, 'General Manager'),
(8, 'MIS Analyst'),
(4, 'Operational Manager'),
(6, 'Quality Analyst'),
(29, 'Quality Manager'),
(24, 'Quality Supervisor'),
(22, 'RTFM'),
(23, 'SME'),
(3, 'Sr. Operational Manager'),
(26, 'Supervisor'),
(21, 'Team Leader'),
(25, 'TnQ Leader'),
(5, 'TQA Manager'),
(7, 'Trainer'),
(2, 'Unit Manager'),
(27, 'WFM');

-- --------------------------------------------------------

--
-- Table structure for table `uac`
--

CREATE TABLE `uac` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `menu_access` text NOT NULL,
  `read` enum('0','1') DEFAULT '0',
  `write` enum('0','1') DEFAULT '0',
  `delete` enum('0','1') DEFAULT '0',
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uac`
--

INSERT INTO `uac` (`id`, `role_name`, `menu_access`, `read`, `write`, `delete`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(3, 'Operational Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\"]', '1', '1', '0', '2210507', '2024-12-21 08:52:48', '2210507', '2024-12-21 10:38:23'),
(4, 'Unit Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\",\"add_ccs_rules\",\"ccs_viewer\",\"project_namelist\",\"role_management\"]', '1', '1', '1', '2210507', '2024-12-21 08:59:57', '2210507', '2024-12-21 12:45:19'),
(5, 'General Manager', '[\"kpi_metrics\",\"kpi_viewer\",\"chart_generator\",\"employee_list\",\"add_ccs_rules\",\"ccs_viewer\",\"project_namelist\",\"role_management\"]', '1', '1', '1', '2210507', '2024-12-21 09:00:30', NULL, '0000-00-00 00:00:00'),
(6, 'Team Leader', '[\"add_ccs_rules\"]', '0', '0', '0', '2210507', '2024-12-21 09:52:16', '2210507', '2024-12-21 09:58:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ccs_rules`
--
ALTER TABLE `ccs_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_active`
--
ALTER TABLE `employee_active`
  ADD PRIMARY KEY (`NIK`);

--
-- Indexes for table `individual_staging`
--
ALTER TABLE `individual_staging`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`kpi_metrics`,`queue`);

--
-- Indexes for table `kpi_gec_mod`
--
ALTER TABLE `kpi_gec_mod`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_mod_individual`
--
ALTER TABLE `kpi_gec_mod_individual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_mod_individual_mon`
--
ALTER TABLE `kpi_gec_mod_individual_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_mod_mon`
--
ALTER TABLE `kpi_gec_mod_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_mod_mon_values`
--
ALTER TABLE `kpi_gec_mod_mon_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`month`);

--
-- Indexes for table `kpi_gec_mod_values`
--
ALTER TABLE `kpi_gec_mod_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`week`);

--
-- Indexes for table `kpi_gec_st`
--
ALTER TABLE `kpi_gec_st`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_individual`
--
ALTER TABLE `kpi_gec_st_individual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_individual_mon`
--
ALTER TABLE `kpi_gec_st_individual_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_kpi` (`NIK`,`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_mon`
--
ALTER TABLE `kpi_gec_st_mon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_kpi` (`queue`,`kpi_metrics`);

--
-- Indexes for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`month`);

--
-- Indexes for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_record` (`kpi_id`,`week`);

--
-- Indexes for table `project_namelist`
--
ALTER TABLE `project_namelist`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_mgmt`
--
ALTER TABLE `role_mgmt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role` (`role`);

--
-- Indexes for table `uac`
--
ALTER TABLE `uac`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ccs_rules`
--
ALTER TABLE `ccs_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `individual_staging`
--
ALTER TABLE `individual_staging`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kpi_gec_mod`
--
ALTER TABLE `kpi_gec_mod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `kpi_gec_mod_individual`
--
ALTER TABLE `kpi_gec_mod_individual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_gec_mod_individual_mon`
--
ALTER TABLE `kpi_gec_mod_individual_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kpi_gec_mod_mon`
--
ALTER TABLE `kpi_gec_mod_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `kpi_gec_mod_mon_values`
--
ALTER TABLE `kpi_gec_mod_mon_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `kpi_gec_mod_values`
--
ALTER TABLE `kpi_gec_mod_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=271;

--
-- AUTO_INCREMENT for table `kpi_gec_st`
--
ALTER TABLE `kpi_gec_st`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `kpi_gec_st_individual`
--
ALTER TABLE `kpi_gec_st_individual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_gec_st_individual_mon`
--
ALTER TABLE `kpi_gec_st_individual_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kpi_gec_st_mon`
--
ALTER TABLE `kpi_gec_st_mon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=511;

--
-- AUTO_INCREMENT for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=405;

--
-- AUTO_INCREMENT for table `project_namelist`
--
ALTER TABLE `project_namelist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `role_mgmt`
--
ALTER TABLE `role_mgmt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `uac`
--
ALTER TABLE `uac`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kpi_gec_mod_mon_values`
--
ALTER TABLE `kpi_gec_mod_mon_values`
  ADD CONSTRAINT `KPI_GEC_MOD_MON_VALUES_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_mod_mon` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_gec_mod_values`
--
ALTER TABLE `kpi_gec_mod_values`
  ADD CONSTRAINT `KPI_GEC_MOD_VALUES_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_mod` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_gec_st_mon_values`
--
ALTER TABLE `kpi_gec_st_mon_values`
  ADD CONSTRAINT `KPI_GEC_ST_MON_VALUES_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_st_mon` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpi_gec_st_values`
--
ALTER TABLE `kpi_gec_st_values`
  ADD CONSTRAINT `KPI_GEC_ST_VALUES_ibfk_1` FOREIGN KEY (`kpi_id`) REFERENCES `kpi_gec_st` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
