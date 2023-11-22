-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2023 at 01:30 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `demo`
--

-- --------------------------------------------------------

--
-- Table structure for table `academics`
--

CREATE TABLE `academics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pre_employee_id` bigint(20) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `major_subject` varchar(100) NOT NULL,
  `institute` varchar(100) NOT NULL,
  `passing_year` varchar(100) NOT NULL,
  `grade_or_gpa` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_departments`
--

CREATE TABLE `announcement_departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applied_positions`
--

CREATE TABLE `applied_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pre_employee_id` bigint(20) NOT NULL,
  `applied_for_position` varchar(100) NOT NULL,
  `expected_salary` varchar(100) NOT NULL,
  `expected_joining_date` varchar(100) NOT NULL,
  `source_of_this_post` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  `work_shift_id` bigint(20) NOT NULL,
  `in_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'check in date time',
  `behavior` varchar(100) NOT NULL COMMENT 'O=>out, I=In',
  `status_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_adjustments`
--

CREATE TABLE `attendance_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `attendance_id` bigint(20) NOT NULL,
  `mark_type` varchar(100) NOT NULL COMMENT 'FullDay, HalfDay, Absent, LateIn, EarlyOut',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_summaries`
--

CREATE TABLE `attendance_summaries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `in_date` varchar(100) NOT NULL,
  `out_date` varchar(100) DEFAULT NULL,
  `behavior` varchar(100) NOT NULL COMMENT 'punch_in',
  `behavior_out` varchar(100) DEFAULT NULL COMMENT 'punch_out',
  `attendance_type` varchar(100) NOT NULL COMMENT 'Late-in, Half-day, Early-out',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `authorize_emails`
--

CREATE TABLE `authorize_emails` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email_title` varchar(100) NOT NULL,
  `to_emails` varchar(100) NOT NULL,
  `cc_emails` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authorize_emails`
--

INSERT INTO `authorize_emails` (`id`, `email_title`, `to_emails`, `cc_emails`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'new_employee_info', '[\"to_employee\"]', '[\"admin@demo.com\"]', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_holder_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `branch_code` varchar(100) NOT NULL,
  `iban` varchar(100) NOT NULL,
  `account` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `education` varchar(100) DEFAULT NULL,
  `last_employer_name` varchar(100) DEFAULT NULL,
  `last_salary` varchar(100) DEFAULT NULL,
  `last_designation` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=in-active',
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `body_types`
--

CREATE TABLE `body_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `body_type` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `body_types`
--

INSERT INTO `body_types` (`id`, `body_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Sedan', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Compact sedan', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Crossover', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Hatchback', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'Mini Vehicles', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Off-Road Vehicles', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Van', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(8, 'SUV', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(9, 'Single Cabin', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(10, 'Pick Up', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(11, 'Mini Van', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(12, 'High Roof', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(13, 'Double Cabin', 1, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `manager_id` bigint(20) DEFAULT NULL,
  `parent_department_id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `manager_id`, `parent_department_id`, `name`, `description`, `location`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 'Main Department', NULL, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `department_users`
--

CREATE TABLE `department_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `department_users`
--

INSERT INTO `department_users` (`id`, `department_id`, `user_id`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2023-11-22', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `department_work_shifts`
--

CREATE TABLE `department_work_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `work_shift_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `title`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Vice President - Business Unit Head', 'Vice President - Business Unit Head', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Director', 'Director', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'N/A', 'N/A', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Manager - Account & Finance', 'Manager - Account & Finance', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'CEO', 'CEO', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Senior Vice President (SVP) - Management Committee', 'Senior Vice President (SVP) - Management Committee', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Manager - Business Development', 'Manager - Business Development', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(8, 'Assistant Executive - Customer Support', 'Assistant Executive - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(9, 'Senior Manager - Business Development', 'Senior Manager - Business Development', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(10, 'Senior Manager - Customer Support', 'Senior Manager - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(11, '3D Animator', '3D Animator', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(12, 'Sr. Executive Vice President - SEVP', 'Sr. Executive Vice President - SEVP', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(13, 'Assistant Vice President - Customer Support', 'Assistant Vice President - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(14, 'Sr.Manager', 'Sr.Manager', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(15, 'Senior Executive - UI/UX Developer', 'Senior Executive - UI/UX Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(16, 'Sales Executive', 'Sales Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(17, 'Sales Executive - Customer Support', 'Sales Executive - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(18, 'Intern - Graphic Designer', 'Intern - Graphic Designer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(19, 'IT Support', 'IT Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(20, 'Senior Executive - Customer Support', 'Senior Executive - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(21, 'Business Development Executive', 'Business Development Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(22, 'Senior Business Development Executive', 'Senior Business Development Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(23, 'Assistant Manager - Customer Support', 'Assistant Manager - Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(24, 'Sweeper', 'Sweeper', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(25, 'Cleaning boys', 'Cleaning boys', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(26, 'Tea boys', 'Tea boys', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(27, 'Business Development  - Executive', 'Business Development  - Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `discrepancies`
--

CREATE TABLE `discrepancies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) DEFAULT NULL COMMENT 'Approved by',
  `user_id` bigint(20) NOT NULL,
  `attendance_id` bigint(20) NOT NULL,
  `date` datetime NOT NULL,
  `type` varchar(100) NOT NULL COMMENT 'late or early',
  `description` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved',
  `is_additional` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If employee fill aditional discrepancy.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_attachments`
--

CREATE TABLE `document_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `attachment` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_letters`
--

CREATE TABLE `employee_letters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `vehicle_user_id` bigint(20) DEFAULT NULL,
  `employee_id` bigint(20) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `validity_date` date DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_histories`
--

CREATE TABLE `employment_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pre_employee_id` bigint(20) NOT NULL,
  `company` varchar(100) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `salary` varchar(100) NOT NULL,
  `reason_of_leaving` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_statuses`
--

CREATE TABLE `employment_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(100) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `alias` varchar(100) DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employment_statuses`
--

INSERT INTO `employment_statuses` (`id`, `name`, `class`, `description`, `is_default`, `alias`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Probation', 'warning', 'Probation', 1, 'Probation', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Permanent', 'success', 'Permanent', 1, 'Permanent', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Terminated', 'danger', 'Terminated', 1, 'Terminated', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Full Time', 'info', 'Full Time', 1, 'Full Time', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'Contract', 'warning', 'Contract', 1, 'Contract', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Voluntary', 'info', 'Voluntary', 1, 'Voluntary', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Layoffs', 'warning', 'Layoffs', 1, 'Layoffs', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(8, 'Retirements', 'info', 'Retirements', 1, 'Retirements', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurances`
--

CREATE TABLE `insurances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `name_as_per_cnic` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `sex` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Male, 0=Female',
  `cnic_number` varchar(100) NOT NULL,
  `marital_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=>Single, 1=Double',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_metas`
--

CREATE TABLE `insurance_metas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `insurance_id` bigint(20) NOT NULL,
  `relationship` varchar(100) NOT NULL COMMENT 'e.g: wife, son, daughter',
  `name` varchar(100) NOT NULL,
  `sex` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Male, 0=Female',
  `cnic_number` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(100) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_histories`
--

CREATE TABLE `job_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `parent_designation_id` bigint(20) DEFAULT NULL,
  `designation_id` bigint(20) DEFAULT NULL,
  `employment_status_id` bigint(20) NOT NULL,
  `joining_date` date NOT NULL,
  `vehicle_name` varchar(100) DEFAULT NULL,
  `vehicle_cc` varchar(100) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_histories`
--

INSERT INTO `job_histories` (`id`, `created_by`, `user_id`, `parent_designation_id`, `designation_id`, `employment_status_id`, `joining_date`, `vehicle_name`, `vehicle_cc`, `end_date`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 1, 2, '2023-11-22', NULL, NULL, NULL, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL COMMENT 'Paid or unpaid',
  `amount` double(8,2) DEFAULT NULL COMMENT 'Amount of leaves of this type of leave.',
  `spacial_percentage` double(8,2) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `slug`, `type`, `amount`, `spacial_percentage`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Casual', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Medical', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Annual', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Half-Day', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'Other', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Marriage', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Miscellaneous', '', 'paid', 2.00, NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `letter_templates`
--

CREATE TABLE `letter_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `template` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_activities`
--

CREATE TABLE `log_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(100) NOT NULL,
  `url` varchar(100) NOT NULL,
  `method` varchar(100) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `agent` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_activities`
--

INSERT INTO `log_activities` (`id`, `subject`, `url`, `method`, `ip`, `agent`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Setting details updated', 'http://localhost/demo.local/settings/1', 'PATCH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Sa', 1, '2023-11-21 20:23:51', '2023-11-21 20:23:51'),
(2, 'Profile Updated', 'http://localhost/demo.local/profile/update/1', 'PATCH', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Sa', 1, '2023-11-21 20:24:29', '2023-11-21 20:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(100) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2023_05_03_005317_create_permission_tables', 1),
(6, '2023_05_03_185209_create_profiles_table', 1),
(7, '2023_05_03_200801_create_designations_table', 1),
(8, '2023_05_03_200843_create_positions_table', 1),
(9, '2023_05_03_200908_create_work_shifts_table', 1),
(10, '2023_05_03_200924_create_departments_table', 1),
(11, '2023_05_03_200939_create_announcements_table', 1),
(12, '2023_05_04_193435_create_employment_statuses_table', 1),
(13, '2023_05_04_224452_create_job_histories_table', 1),
(14, '2023_05_04_230534_create_salary_histories_table', 1),
(15, '2023_05_04_231506_create_statuses_table', 1),
(16, '2023_05_05_210039_create_log_activities_table', 1),
(17, '2023_05_08_211133_create_work_shift_details_table', 1),
(18, '2023_05_09_201743_create_department_work_shifts_table', 1),
(19, '2023_05_09_211150_create_department_users_table', 1),
(20, '2023_05_09_235844_create_announcement_departments_table', 1),
(21, '2023_05_11_180532_create_profile_cover_images_table', 1),
(22, '2023_05_12_233009_create_user_employment_statuses_table', 1),
(23, '2023_05_16_191325_create_user_contacts_table', 1),
(24, '2023_05_18_004538_create_settings_table', 1),
(25, '2023_05_19_001216_create_leave_types_table', 1),
(26, '2023_05_22_221454_create_documents_table', 1),
(27, '2023_05_22_223417_create_user_leaves_table', 1),
(28, '2023_05_23_192149_create_discrepancies_table', 1),
(29, '2023_05_23_192846_create_attendances_table', 1),
(30, '2023_05_23_205031_create_working_shift_users_table', 1),
(31, '2023_05_25_185727_create_bank_accounts_table', 1),
(32, '2023_07_13_011551_create_chats_table', 1),
(33, '2023_07_20_211718_create_authorize_emails_table', 1),
(34, '2023_07_21_165741_create_letter_templates_table', 1),
(35, '2023_07_21_170222_create_employee_letters_table', 1),
(36, '2023_07_25_012722_create_jobs_table', 1),
(37, '2023_07_25_014107_create_notifications_table', 1),
(38, '2023_07_27_002201_create_pre_employees_table', 1),
(39, '2023_07_27_002452_create_applied_positions_table', 1),
(40, '2023_07_27_002526_create_resumes_table', 1),
(41, '2023_07_27_002610_create_academics_table', 1),
(42, '2023_07_27_002648_create_references_table', 1),
(43, '2023_07_27_002723_create_employment_histories_table', 1),
(44, '2023_07_28_022758_create_vehicles_table', 1),
(45, '2023_07_28_022812_create_vehicle_users_table', 1),
(46, '2023_07_28_022826_create_vehicle_owners_table', 1),
(47, '2023_07_28_022837_create_vehicle_inspections_table', 1),
(48, '2023_07_28_023203_create_vehicle_allowances_table', 1),
(49, '2023_07_28_030004_create_vehicle_images_table', 1),
(50, '2023_07_28_030056_create_vehicle_rents_table', 1),
(51, '2023_07_28_030311_create_tickets_table', 1),
(52, '2023_07_28_030740_create_ticket_categories_table', 1),
(53, '2023_07_28_033114_create_ticket_reasons_table', 1),
(54, '2023_08_16_060748_create_insurances_table', 1),
(55, '2023_08_16_213336_create_insurance_metas_table', 1),
(56, '2023_08_18_021441_create_attendance_adjustments_table', 1),
(57, '2023_08_29_051325_create_resignations_table', 1),
(58, '2023_10_06_214134_create_document_attachments_table', 1),
(59, '2023_10_09_234723_create_monthly_salary_reports_table', 1),
(60, '2023_10_25_200720_create_attendance_summaries_table', 1),
(61, '2023_11_07_023718_add_indexes_to_attendances', 1),
(62, '2023_11_07_025245_add_indexes_to_discrepancies', 1),
(63, '2023_11_07_215037_create_body_types_table', 1),
(64, '2023_11_07_215819_create_w_f_h_employees_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(100) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(100) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_salary_reports`
--

CREATE TABLE `monthly_salary_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `month_year` varchar(100) NOT NULL,
  `actual_salary` bigint(20) NOT NULL DEFAULT 0,
  `car_allowance` bigint(20) NOT NULL DEFAULT 0,
  `earning_salary` bigint(20) NOT NULL DEFAULT 0,
  `approved_days_amount` bigint(20) NOT NULL DEFAULT 0,
  `deduction` bigint(20) NOT NULL DEFAULT 0,
  `net_salary` bigint(20) NOT NULL DEFAULT 0,
  `generated_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(100) NOT NULL,
  `notifiable_type` varchar(100) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `guard_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `label`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'employee salary details', 'employee_salary_details-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(2, 'employee leave requests', 'employee_leave_requests-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(3, 'employee leave requests', 'employee_leave_requests-create', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(4, 'employee leave requests', 'employee_leave_requests-edit', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(5, 'employee leave requests', 'employee_leave_requests-delete', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(6, 'employee leave report', 'employee_leave_report-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(7, 'employee attendance daily log', 'employee_attendance_daily_log-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(8, 'employee discrepancies', 'employee_discrepancies-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(9, 'employee summary', 'employee_summary-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(10, 'permissions', 'permissions-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(11, 'permissions', 'permissions-create', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(12, 'permissions', 'permissions-edit', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(13, 'permissions', 'permissions-delete', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(14, 'permissions', 'permissions-status', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(15, 'roles', 'roles-list', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(16, 'roles', 'roles-create', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(17, 'roles', 'roles-edit', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(18, 'roles', 'roles-delete', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(19, 'roles', 'roles-status', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(20, 'employee attendance filter', 'employee_attendance_filter-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(21, 'employees', 'employees-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(22, 'employees', 'employees-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(23, 'employees', 'employees-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(24, 'employees', 'employees-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(25, 'employees', 'employees-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(26, 'designations', 'designations-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(27, 'designations', 'designations-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(28, 'designations', 'designations-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(29, 'designations', 'designations-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(30, 'designations', 'designations-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(31, 'employment status', 'employment_status-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(32, 'employment status', 'employment_status-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(33, 'employment status', 'employment_status-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(34, 'employment status', 'employment_status-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(35, 'employment status', 'employment_status-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(36, 'positions', 'positions-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(37, 'positions', 'positions-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(38, 'positions', 'positions-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(39, 'positions', 'positions-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(40, 'positions', 'positions-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(41, 'work shifts', 'work_shifts-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(42, 'work shifts', 'work_shifts-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(43, 'work shifts', 'work_shifts-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(44, 'work shifts', 'work_shifts-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(45, 'work shifts', 'work_shifts-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(46, 'departments', 'departments-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(47, 'departments', 'departments-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(48, 'departments', 'departments-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(49, 'departments', 'departments-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(50, 'departments', 'departments-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(51, 'announcements', 'announcements-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(52, 'announcements', 'announcements-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(53, 'announcements', 'announcements-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(54, 'announcements', 'announcements-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(55, 'announcements', 'announcements-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(56, 'profile cover images', 'profile_cover_images-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(57, 'profile cover images', 'profile_cover_images-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(58, 'profile cover images', 'profile_cover_images-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(59, 'profile cover images', 'profile_cover_images-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(60, 'profile cover images', 'profile_cover_images-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(61, 'leave types', 'leave_types-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(62, 'leave types', 'leave_types-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(63, 'leave types', 'leave_types-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(64, 'leave types', 'leave_types-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(65, 'leave types', 'leave_types-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(66, 'bank accounts', 'bank_accounts-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(67, 'bank accounts', 'bank_accounts-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(68, 'bank accounts', 'bank_accounts-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(69, 'bank accounts', 'bank_accounts-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(70, 'bank accounts', 'bank_accounts-status', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(71, 'team members', 'team_members-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(72, 'setting', 'setting-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(73, 'setting', 'setting-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(74, 'vehicles', 'vehicles-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(75, 'vehicles', 'vehicles-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(76, 'vehicles', 'vehicles-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(77, 'vehicles', 'vehicles-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(78, 'vehicle owners', 'vehicle_owners-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(79, 'vehicle owners', 'vehicle_owners-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(80, 'vehicle owners', 'vehicle_owners-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(81, 'vehicle owners', 'vehicle_owners-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(82, 'vehicle inspections', 'vehicle_inspections-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(83, 'vehicle inspections', 'vehicle_inspections-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(84, 'vehicle inspections', 'vehicle_inspections-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(85, 'vehicle inspections', 'vehicle_inspections-delete', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(86, 'vehicle users', 'vehicle_users-list', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(87, 'vehicle users', 'vehicle_users-create', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(88, 'vehicle users', 'vehicle_users-edit', 'web', '2023-11-21 20:14:43', '2023-11-21 20:14:43'),
(89, 'vehicle users', 'vehicle_users-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(90, 'vehicle allowances', 'vehicle_allowances-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(91, 'vehicle allowances', 'vehicle_allowances-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(92, 'vehicle allowances', 'vehicle_allowances-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(93, 'vehicle allowances', 'vehicle_allowances-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(94, 'authorize emails', 'authorize_emails-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(95, 'authorize emails', 'authorize_emails-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(96, 'authorize emails', 'authorize_emails-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(97, 'authorize emails', 'authorize_emails-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(98, 'letter templates', 'letter_templates-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(99, 'letter templates', 'letter_templates-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(100, 'letter templates', 'letter_templates-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(101, 'letter templates', 'letter_templates-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(102, 'letter templates', 'letter_templates-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(103, 'employee letters', 'employee_letters-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(104, 'employee letters', 'employee_letters-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(105, 'employee letters', 'employee_letters-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(106, 'employee letters', 'employee_letters-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(107, 'employee letters', 'employee_letters-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(108, 'notifications', 'notifications-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(109, 'notifications', 'notifications-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(110, 'notifications', 'notifications-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(111, 'notifications', 'notifications-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(112, 'pre employees', 'pre_employees-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(113, 'pre employees', 'pre_employees-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(114, 'pre employees', 'pre_employees-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(115, 'pre employees', 'pre_employees-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(116, 'pre employees', 'pre_employees-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(117, 'tickets', 'tickets-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(118, 'tickets', 'tickets-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(119, 'tickets', 'tickets-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(120, 'tickets', 'tickets-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(121, 'tickets', 'tickets-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(122, 'vehicle rents', 'vehicle_rents-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(123, 'vehicle rents', 'vehicle_rents-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(124, 'vehicle rents', 'vehicle_rents-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(125, 'vehicle rents', 'vehicle_rents-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(126, 'vehicle rents', 'vehicle_rents-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(127, 'all tickets', 'all_tickets-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(128, 'team discrepancies', 'team_discrepancies-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(129, 'team leaves', 'team_leaves-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(130, 'generate pay slip', 'generate_pay_slip-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(131, 'insurances', 'insurances-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(132, 'insurances', 'insurances-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(133, 'insurances', 'insurances-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(134, 'insurances', 'insurances-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(135, 'insurances', 'insurances-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(136, 'mark attendance', 'mark_attendance-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(137, 'mark attendance', 'mark_attendance-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(138, 'mark attendance', 'mark_attendance-edit', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(139, 'mark attendance', 'mark_attendance-delete', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(140, 'mark attendance', 'mark_attendance-status', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(141, 'export insurance', 'export_insurance-create', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(142, 'wfh employee', 'wfh_employee-list', 'web', '2023-11-21 20:14:44', '2023-11-21 20:14:44'),
(143, 'wfh employee', 'wfh_employee-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(144, 'wfh employee', 'wfh_employee-edit', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(145, 'wfh employee', 'wfh_employee-delete', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(146, 'wfh employee', 'wfh_employee-status', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(147, 'resignations', 'resignations-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(148, 'resignations', 'resignations-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(149, 'resignations', 'resignations-edit', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(150, 'resignations', 'resignations-delete', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(151, 'resignations', 'resignations-status', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(152, 'employee rehire', 'employee_rehire-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(153, 'terminated employee summary', 'terminated_employee_summary-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(154, 'documents', 'documents-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(155, 'documents', 'documents-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(156, 'documents', 'documents-edit', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(157, 'documents', 'documents-delete', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(158, 'documents', 'documents-status', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(159, 'monthly salary report', 'monthly_salary_report-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(160, 'monthly salary report', 'monthly_salary_report-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(161, 'monthly salary report', 'monthly_salary_report-delete', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(162, 'monthly salary report', 'monthly_salary_report-status', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(163, 'filter summary', 'filter_summary-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(164, 'waiting for approval tickets', 'waiting_for_approval_tickets-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(165, 'admin vehicle users list', 'admin_vehicle_users_list-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(166, 'team tickets', 'team_tickets-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(167, 'admin team tickets', 'admin_team_tickets-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(168, 'admin all tickets', 'admin_all_tickets-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(169, 'admin employee re hire', 'admin_employee_re_hire-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(170, 'employee all letters', 'employee_all_letters-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(171, 'employee resignations', 'employee_resignations-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(172, 'manager team leaves', 'manager_team_leaves-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(173, 'admin leave reports', 'admin_leave_reports-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(174, 'admin attendance daily log', 'admin_attendance_daily_log-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(175, 'manager team discrepancies', 'manager_team_discrepancies-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(176, 'admin summary', 'admin_summary-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(177, 'admin attendance filter', 'admin_attendance_filter-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(178, 'manager team member', 'manager_team_member-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(179, 'attendance monthly report', 'attendance_monthly_report-list', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(180, 'export bank account', 'export_bank_account-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(181, 'allow approve additional discrepancies', 'allow_approve_additional_discrepancies-create', 'web', '2023-11-21 20:14:45', '2023-11-21 20:14:45'),
(182, 'employee', 'employee-show', 'web', '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(183, 'payslip editable', 'payslip_editable-create', 'web', '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(100) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `title`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Business Development - Mobile Apps', 'Business Development - Mobile Apps', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Creative Content Writers', 'Creative Content Writers', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Creative Graphic Designer', 'Creative Graphic Designer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'iOS App Developer', 'iOS App Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'Sr. Software Engineer', 'Sr. Software Engineer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Storyboard Artist', 'Storyboard Artist', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Editor & Proofreader', 'Editor & Proofreader', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(8, 'UI/UX Designer-Mobile Apps', 'UI/UX Designer-Mobile Apps', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(9, 'Unity 2D/3D Mobile App. Game Developer', 'Unity 2D/3D Mobile App. Game Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(10, 'Technical Support Executive', 'Technical Support Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(11, 'Business Analyst', 'Business Analyst', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(12, 'Solution Architect', 'Solution Architect', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(13, 'Project Manager', 'Project Manager', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(14, 'Digital - Brand Manager', 'Digital - Brand Manager', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(15, 'Social Media Executive', 'Social Media Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(16, 'Accountant', 'Accountant', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(17, 'Sales', 'Sales', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(18, 'Customer Support', 'Customer Support', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(19, 'SEO', 'SEO', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(20, 'PPC', 'PPC', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(21, 'Supply Chain/Logistics', 'Supply Chain/Logistics', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(22, 'Business Development Executive', 'Business Development Executive', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(23, 'Marketing', 'Marketing', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(24, 'Quality Assurance', 'Quality Assurance', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(25, 'IT', 'IT', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(26, 'HR', 'HR', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(27, 'Web Designer', 'Web Designer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(28, 'BPO', 'BPO', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(29, 'Account Manager - Mobile Applications', 'Account Manager - Mobile Applications', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(30, 'React Native Developer', 'React Native Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(31, 'Full Stack Developer', 'Full Stack Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(32, 'Quality Assurance - Mobile Applications', 'Quality Assurance - Mobile Applications', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(33, 'Chef', 'Chef', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(34, 'Chinese Sales Consultant', 'Chinese Sales Consultant', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(35, '2D Video Animator', '2D Video Animator', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(36, 'Senior PHP Developer', 'Senior PHP Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(37, 'Android Developer', 'Android Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(38, 'Web Developer', 'Web Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(39, 'Frontend UI/UX Developer', 'Frontend UI/UX Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(40, 'Senior Web Developer', 'Senior Web Developer', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `pre_employees`
--

CREATE TABLE `pre_employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `manager_id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_birth` varchar(100) NOT NULL,
  `cnic` varchar(100) NOT NULL,
  `contact_no` varchar(100) NOT NULL,
  `emergency_number` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `apartment` varchar(100) NOT NULL,
  `marital_status` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved, 2=rejected',
  `note` varchar(100) DEFAULT NULL COMMENT 'Note if any',
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `cnic` varchar(100) DEFAULT NULL,
  `employment_id` bigint(20) DEFAULT NULL,
  `cover_image_id` bigint(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `marital_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=married, 0=single',
  `fathers_name` varchar(100) DEFAULT NULL,
  `mothers_name` varchar(100) DEFAULT NULL,
  `social_security_number` varchar(100) DEFAULT NULL,
  `phone_number` varchar(100) DEFAULT NULL,
  `about_me` text DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `profile` varchar(100) DEFAULT NULL,
  `cnic_front` varchar(100) DEFAULT NULL,
  `cnic_back` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `cnic`, `employment_id`, `cover_image_id`, `joining_date`, `date_of_birth`, `gender`, `marital_status`, `fathers_name`, `mothers_name`, `social_security_number`, `phone_number`, `about_me`, `address`, `profile`, `cnic_front`, `cnic_back`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1145, NULL, '2023-11-22', NULL, 'male', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2023-11-21 20:14:46', '2023-11-21 20:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `profile_cover_images`
--

CREATE TABLE `profile_cover_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `references`
--

CREATE TABLE `references` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pre_employee_id` bigint(20) NOT NULL,
  `reference_name` varchar(100) NOT NULL,
  `company` varchar(100) NOT NULL,
  `contact_no` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resignations`
--

CREATE TABLE `resignations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `is_manager_approved` bigint(20) DEFAULT NULL,
  `is_concerned_approved` bigint(20) DEFAULT NULL,
  `employment_status_id` bigint(20) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `resignation_date` date NOT NULL,
  `reason_for_resignation` text DEFAULT NULL,
  `notice_period` varchar(100) NOT NULL,
  `last_working_day` varchar(100) NOT NULL,
  `comment` varchar(100) DEFAULT NULL,
  `rehire_eligibility` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'A boolean field indicating whether the employee is eligible for rehire in the future.',
  `is_rehired` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If a user re-hired it will set log',
  `resignation_letter` varchar(100) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved, 2-rejected',
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pre_employee_id` bigint(20) NOT NULL,
  `hobbies_and_interests` text NOT NULL,
  `achievements` text NOT NULL,
  `portfolio_link` varchar(100) DEFAULT NULL,
  `resume` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `guard_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(2, 'Department Manager', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(3, 'Employee', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42'),
(4, 'Developer', 'web', '2023-11-21 20:14:42', '2023-11-21 20:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 2),
(2, 3),
(3, 1),
(3, 2),
(3, 3),
(4, 1),
(4, 2),
(4, 3),
(5, 1),
(5, 2),
(5, 3),
(6, 2),
(6, 3),
(7, 2),
(7, 3),
(8, 1),
(8, 2),
(8, 3),
(9, 2),
(9, 3),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 2),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(62, 1),
(63, 1),
(64, 1),
(65, 1),
(66, 1),
(67, 1),
(67, 2),
(67, 3),
(68, 1),
(68, 2),
(68, 3),
(69, 1),
(70, 1),
(70, 2),
(71, 1),
(72, 1),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(86, 2),
(86, 3),
(87, 1),
(88, 1),
(89, 1),
(90, 1),
(91, 1),
(92, 1),
(93, 1),
(94, 1),
(95, 1),
(96, 1),
(97, 1),
(98, 1),
(99, 1),
(100, 1),
(101, 1),
(102, 1),
(103, 1),
(104, 1),
(105, 1),
(106, 1),
(107, 1),
(108, 1),
(108, 3),
(109, 1),
(110, 1),
(111, 1),
(112, 1),
(112, 2),
(113, 1),
(114, 1),
(115, 1),
(116, 1),
(117, 3),
(118, 1),
(118, 3),
(119, 1),
(119, 3),
(120, 1),
(120, 3),
(121, 1),
(122, 1),
(123, 1),
(124, 1),
(125, 1),
(126, 1),
(128, 1),
(129, 1),
(130, 1),
(130, 2),
(130, 3),
(131, 1),
(132, 1),
(132, 2),
(132, 3),
(133, 1),
(133, 2),
(133, 3),
(134, 1),
(135, 1),
(136, 1),
(137, 1),
(138, 1),
(139, 1),
(140, 1),
(141, 1),
(142, 1),
(143, 1),
(144, 1),
(145, 1),
(146, 1),
(147, 1),
(148, 1),
(148, 3),
(149, 1),
(149, 3),
(150, 1),
(150, 3),
(151, 1),
(151, 2),
(151, 3),
(152, 1),
(153, 1),
(154, 1),
(155, 1),
(156, 1),
(157, 1),
(158, 1),
(159, 1),
(160, 1),
(161, 1),
(162, 1),
(163, 2),
(164, 1),
(165, 1),
(166, 2),
(167, 1),
(168, 1),
(170, 2),
(170, 3),
(171, 2),
(171, 3),
(172, 2),
(173, 1),
(174, 1),
(175, 2),
(176, 1),
(177, 1),
(178, 2),
(179, 1),
(180, 1),
(182, 1),
(182, 2),
(182, 3);

-- --------------------------------------------------------

--
-- Table structure for table `salary_histories`
--

CREATE TABLE `salary_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `job_history_id` bigint(20) NOT NULL,
  `raise_salary` bigint(20) DEFAULT NULL,
  `salary` bigint(20) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `black_logo` varchar(100) NOT NULL,
  `slip_stamp` varchar(100) NOT NULL,
  `admin_signature` varchar(100) NOT NULL,
  `phone_number` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website_url` text DEFAULT NULL,
  `currency_symbol` varchar(100) NOT NULL,
  `favicon` varchar(100) NOT NULL,
  `banner` varchar(100) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `max_discrepancies` varchar(100) DEFAULT NULL,
  `max_leaves` varchar(100) DEFAULT NULL,
  `insurance_eligibility` int(11) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `facebook_link` text DEFAULT NULL,
  `instagram_link` text DEFAULT NULL,
  `linked_in_link` text DEFAULT NULL,
  `twitter_link` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `logo`, `black_logo`, `slip_stamp`, `admin_signature`, `phone_number`, `email`, `website_url`, `currency_symbol`, `favicon`, `banner`, `language`, `max_discrepancies`, `max_leaves`, `insurance_eligibility`, `country`, `area`, `city`, `state`, `zip_code`, `address`, `facebook_link`, `instagram_link`, `linked_in_link`, `twitter_link`, `created_at`, `updated_at`) VALUES
(1, 'Soft Nova Technologies', 'bdd165f6-4f32-440b-ac1d-541eaf0a4a63.png', 'a28b32da-6501-4a05-9e9e-a4150b8ef48b.png', '', '', '(123) - 45678900', 'info@demo.com', 'https://demo.com/', 'PKR', 'a5761d18-bdad-4612-99b0-633d6f66b280.png', 'default.png', 'English', '6', '6', 6, 'pakistan', 'Sindhi Muslim Cooperative Housing Society', 'Karachi', 'Sindh', '75100', 'Plot No, 123, Sindhi Muslim Cooperative Housing Society Block A Sindhi Muslim CHS (SMCHS), Karachi, Sindh 75100', NULL, NULL, NULL, NULL, '2023-11-21 20:14:42', '2023-11-21 20:23:51');

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `class` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `ticket_category_id` bigint(20) NOT NULL,
  `reason_id` bigint(20) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `attachment` varchar(100) DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved by RA, 3=completed',
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_categories`
--

CREATE TABLE `ticket_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_categories`
--

INSERT INTO `ticket_categories` (`id`, `name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'IT Equipment', NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'IT Rapid Support', NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Finance', NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Fleet', NULL, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_reasons`
--

CREATE TABLE `ticket_reasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_reasons`
--

INSERT INTO `ticket_reasons` (`id`, `name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Virus related', 'Virus related', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(2, 'Email Issue', 'Email Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(3, 'Fonts Installation', 'Fonts Installation', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(4, 'Other Software Installation', 'Other Software Installation', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(5, 'Spark Issue', 'Spark Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(6, 'Adobe Softwares Installation', 'Adobe Softwares Installation', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(7, 'Soft-phone issue', 'Soft-phone issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(8, 'Windows Expire', 'Windows Expire', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(9, 'MS Office Expire', 'MS Office Expire', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(10, 'Data Recovery', 'Data Recovery', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(11, 'Other Issue', 'Other Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(12, 'System running slow', 'System running slow', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(13, 'Sound not working', 'Sound not working', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(14, 'UPS issue', 'UPS issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(15, 'Mouse not working', 'Mouse not working', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(16, 'Keyboard not working', 'Keyboard not working', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(17, 'LED display issue', 'LED display issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(18, 'RAM upgrade', 'RAM upgrade', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(19, 'Additional Hard Disk Required', 'Additional Hard Disk Required', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(20, 'Other Issue', 'Other Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(21, 'Cisco not working', 'Cisco not working', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(22, 'Distortion on Calls', 'Distortion on Calls', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(23, 'Call not connecting', 'Call not connecting', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(24, 'Call Recording', 'Call Recording', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(25, 'Other Issue', 'Other Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(26, 'Internet working slow', 'Internet working slow', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(27, 'Wireless not connecting', 'Wireless not connecting', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(28, 'Other Issue', 'Other Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(29, 'Finger scanning not working', 'Finger scanning not working', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46'),
(30, 'Other Issue', 'Other Issue', 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `pre_emp_id` bigint(20) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `slug` varchar(100) NOT NULL,
  `is_employee` tinyint(1) NOT NULL DEFAULT 1,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `created_by`, `pre_emp_id`, `status`, `slug`, `is_employee`, `first_name`, `last_name`, `email`, `email_verified_at`, `password`, `remember_token`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 1, '', 0, 'Admin', 'Admin', 'admin@demo.org', NULL, '$2y$10$2PaiQmQ/WAk0CmgBRohwEepY7BMqsg2g0znNgTiud6VX6Jca7FFnO', NULL, NULL, '2023-11-21 20:14:42', '2023-11-21 20:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `user_contacts`
--

CREATE TABLE `user_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_employment_statuses`
--

CREATE TABLE `user_employment_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employment_status_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_employment_statuses`
--

INSERT INTO `user_employment_statuses` (`id`, `user_id`, `employment_status_id`, `start_date`, `end_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2023-11-22', NULL, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_leaves`
--

CREATE TABLE `user_leaves` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `is_applied` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If user apply advance is_applied = 1 else 0',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=approved, 2=rejected',
  `start_at` date NOT NULL,
  `end_at` date NOT NULL,
  `duration` double(8,2) DEFAULT NULL,
  `behavior_type` varchar(100) NOT NULL COMMENT 'e.g first_half, last_half, absent',
  `type` varchar(100) NOT NULL COMMENT 'e.g first_half, last_half, abset',
  `reason` varchar(100) DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `thumbnail` varchar(100) DEFAULT NULL,
  `model` varchar(100) NOT NULL,
  `body_type` varchar(100) NOT NULL,
  `assembly` varchar(100) NOT NULL,
  `model_year` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL,
  `transmission` varchar(100) NOT NULL,
  `engine_type` varchar(100) NOT NULL,
  `engine_number` varchar(100) NOT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `engine_capacity` varchar(100) NOT NULL,
  `mileage` varchar(100) NOT NULL,
  `registration_province` varchar(100) NOT NULL,
  `registration_city` varchar(100) NOT NULL,
  `registration_number` varchar(100) NOT NULL,
  `additional` varchar(100) DEFAULT NULL,
  `video` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_allowances`
--

CREATE TABLE `vehicle_allowances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `allowance` bigint(20) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(100) DEFAULT NULL,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_images`
--

CREATE TABLE `vehicle_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) NOT NULL,
  `image` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_inspections`
--

CREATE TABLE `vehicle_inspections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) NOT NULL,
  `vehicle_user_id` bigint(20) NOT NULL,
  `receive_date` date NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_details` text DEFAULT NULL,
  `inspection_details` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=pending, 1=completed',
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_owners`
--

CREATE TABLE `vehicle_owners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_rents`
--

CREATE TABLE `vehicle_rents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) NOT NULL,
  `rent` varchar(100) NOT NULL,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_users`
--

CREATE TABLE `vehicle_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `deliver_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `note` varchar(100) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `working_shift_users`
--

CREATE TABLE `working_shift_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `working_shift_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `working_shift_users`
--

INSERT INTO `working_shift_users` (`id`, `working_shift_id`, `user_id`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2023-11-22', NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `work_shifts`
--

CREATE TABLE `work_shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `type` enum('regular','scheduled') NOT NULL DEFAULT 'regular',
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_shifts`
--

INSERT INTO `work_shifts` (`id`, `name`, `start_date`, `end_date`, `start_time`, `end_time`, `type`, `description`, `is_default`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Night Shift (9 to 6)', '2023-11-22', NULL, '21:00:00', '06:00:00', 'regular', NULL, 1, 1, NULL, '2023-11-21 20:14:46', '2023-11-21 20:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `work_shift_details`
--

CREATE TABLE `work_shift_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `working_shift_id` bigint(20) NOT NULL,
  `weekday_key` varchar(100) NOT NULL,
  `weekday` varchar(100) NOT NULL,
  `is_weekend` tinyint(1) NOT NULL DEFAULT 0,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `w_f_h_employees`
--

CREATE TABLE `w_f_h_employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `note` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academics`
--
ALTER TABLE `academics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_departments_announcement_id_foreign` (`announcement_id`),
  ADD KEY `announcement_departments_department_id_foreign` (`department_id`);

--
-- Indexes for table `applied_positions`
--
ALTER TABLE `applied_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_user_id_index` (`user_id`),
  ADD KEY `attendances_in_date_index` (`in_date`);

--
-- Indexes for table `attendance_adjustments`
--
ALTER TABLE `attendance_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_summaries`
--
ALTER TABLE `attendance_summaries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `authorize_emails`
--
ALTER TABLE `authorize_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_accounts_user_id_foreign` (`user_id`);

--
-- Indexes for table `body_types`
--
ALTER TABLE `body_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_users`
--
ALTER TABLE `department_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_users_department_id_foreign` (`department_id`),
  ADD KEY `department_users_user_id_foreign` (`user_id`);

--
-- Indexes for table `department_work_shifts`
--
ALTER TABLE `department_work_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_work_shifts_department_id_foreign` (`department_id`),
  ADD KEY `department_work_shifts_work_shift_id_foreign` (`work_shift_id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `discrepancies`
--
ALTER TABLE `discrepancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discrepancies_user_id_index` (`user_id`),
  ADD KEY `discrepancies_date_index` (`date`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_attachments`
--
ALTER TABLE `document_attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_letters`
--
ALTER TABLE `employee_letters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employment_histories`
--
ALTER TABLE `employment_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `insurances`
--
ALTER TABLE `insurances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insurance_metas`
--
ALTER TABLE `insurance_metas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_histories`
--
ALTER TABLE `job_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `letter_templates`
--
ALTER TABLE `letter_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_activities`
--
ALTER TABLE `log_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `monthly_salary_reports`
--
ALTER TABLE `monthly_salary_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pre_employees`
--
ALTER TABLE `pre_employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profile_cover_images`
--
ALTER TABLE `profile_cover_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_cover_images_created_by_foreign` (`created_by`);

--
-- Indexes for table `references`
--
ALTER TABLE `references`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resignations`
--
ALTER TABLE `resignations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `salary_histories`
--
ALTER TABLE `salary_histories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_reasons`
--
ALTER TABLE `ticket_reasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_contacts`
--
ALTER TABLE `user_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_contacts_user_id_foreign` (`user_id`);

--
-- Indexes for table `user_employment_statuses`
--
ALTER TABLE `user_employment_statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_employment_statuses_user_id_foreign` (`user_id`),
  ADD KEY `user_employment_statuses_employment_status_id_foreign` (`employment_status_id`);

--
-- Indexes for table `user_leaves`
--
ALTER TABLE `user_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_leaves_approved_by_foreign` (`approved_by`),
  ADD KEY `user_leaves_department_id_foreign` (`department_id`),
  ADD KEY `user_leaves_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `user_leaves_user_id_foreign` (`user_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_allowances`
--
ALTER TABLE `vehicle_allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_owners`
--
ALTER TABLE `vehicle_owners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_rents`
--
ALTER TABLE `vehicle_rents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicle_users`
--
ALTER TABLE `vehicle_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `working_shift_users`
--
ALTER TABLE `working_shift_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `work_shifts`
--
ALTER TABLE `work_shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `work_shift_details`
--
ALTER TABLE `work_shift_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `w_f_h_employees`
--
ALTER TABLE `w_f_h_employees`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academics`
--
ALTER TABLE `academics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applied_positions`
--
ALTER TABLE `applied_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_adjustments`
--
ALTER TABLE `attendance_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_summaries`
--
ALTER TABLE `attendance_summaries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `authorize_emails`
--
ALTER TABLE `authorize_emails`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `body_types`
--
ALTER TABLE `body_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `department_users`
--
ALTER TABLE `department_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `department_work_shifts`
--
ALTER TABLE `department_work_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `discrepancies`
--
ALTER TABLE `discrepancies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_attachments`
--
ALTER TABLE `document_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_letters`
--
ALTER TABLE `employee_letters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_histories`
--
ALTER TABLE `employment_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurances`
--
ALTER TABLE `insurances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_metas`
--
ALTER TABLE `insurance_metas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_histories`
--
ALTER TABLE `job_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `letter_templates`
--
ALTER TABLE `letter_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_activities`
--
ALTER TABLE `log_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `monthly_salary_reports`
--
ALTER TABLE `monthly_salary_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `pre_employees`
--
ALTER TABLE `pre_employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `profile_cover_images`
--
ALTER TABLE `profile_cover_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `references`
--
ALTER TABLE `references`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resignations`
--
ALTER TABLE `resignations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `salary_histories`
--
ALTER TABLE `salary_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ticket_reasons`
--
ALTER TABLE `ticket_reasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_contacts`
--
ALTER TABLE `user_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_employment_statuses`
--
ALTER TABLE `user_employment_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_leaves`
--
ALTER TABLE `user_leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_allowances`
--
ALTER TABLE `vehicle_allowances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_inspections`
--
ALTER TABLE `vehicle_inspections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_owners`
--
ALTER TABLE `vehicle_owners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_rents`
--
ALTER TABLE `vehicle_rents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_users`
--
ALTER TABLE `vehicle_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `working_shift_users`
--
ALTER TABLE `working_shift_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_shifts`
--
ALTER TABLE `work_shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_shift_details`
--
ALTER TABLE `work_shift_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `w_f_h_employees`
--
ALTER TABLE `w_f_h_employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD CONSTRAINT `announcement_departments_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_departments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD CONSTRAINT `bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `department_users`
--
ALTER TABLE `department_users`
  ADD CONSTRAINT `department_users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `department_work_shifts`
--
ALTER TABLE `department_work_shifts`
  ADD CONSTRAINT `department_work_shifts_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_work_shifts_work_shift_id_foreign` FOREIGN KEY (`work_shift_id`) REFERENCES `work_shifts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_cover_images`
--
ALTER TABLE `profile_cover_images`
  ADD CONSTRAINT `profile_cover_images_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_contacts`
--
ALTER TABLE `user_contacts`
  ADD CONSTRAINT `user_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_employment_statuses`
--
ALTER TABLE `user_employment_statuses`
  ADD CONSTRAINT `user_employment_statuses_employment_status_id_foreign` FOREIGN KEY (`employment_status_id`) REFERENCES `employment_statuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_employment_statuses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_leaves`
--
ALTER TABLE `user_leaves`
  ADD CONSTRAINT `user_leaves_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_leaves_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_leaves_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_leaves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
