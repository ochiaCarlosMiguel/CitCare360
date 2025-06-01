-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 07:37 AM
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
-- Database: `cit_care`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_blocked_times`
--

CREATE TABLE `admin_blocked_times` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `blocked_date` date NOT NULL,
  `blocked_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_blocked_times`
--

INSERT INTO `admin_blocked_times` (`id`, `admin_id`, `blocked_date`, `blocked_time`, `created_at`) VALUES
(84, 138, '2025-05-13', '08:00:00', '2025-05-12 04:22:09'),
(85, 138, '2025-05-13', '09:00:00', '2025-05-12 04:22:09'),
(86, 138, '2025-05-16', '11:00:00', '2025-05-12 04:22:34'),
(87, 138, '2025-05-16', '12:00:00', '2025-05-12 04:22:34'),
(88, 138, '2025-05-16', '13:00:00', '2025-05-12 04:22:34');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password`, `user_role`, `profile_image`, `created_at`) VALUES
(138, 'Gijon Aron Evangelista', 'gijonevangelista1202@gmail.com', '$2y$10$5XNbGwidFnhskFSLUOFeo.fxi.qFDSEUNhJX.lhjoyR4obtrat/ZC', 'Admin', 'gijon.jpg', '2025-05-02 05:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `cit_students`
--

CREATE TABLE `cit_students` (
  `id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_persons`
--

CREATE TABLE `contact_persons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `relationship` enum('Parent (Mother/Father)','Guardian','Sibling (Brother/Sister)','Grandparent (Grandmother/Grandfather)','Aunt/Uncle','Cousin','Step-Parent (Step-Mother/Step-Father)','Step-Sibling (Step-Brother/Step-Sister)','Family Friend','Godparent','Neighbor','Teacher/Professor (with permission)','Classmate/Close Friend (if authorized)','Employer/Supervisor (if working student)') NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `telephone_number` varchar(15) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `complete_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_persons_2`
--

CREATE TABLE `contact_persons_2` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `telephone_number` varchar(15) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `relationship` enum('Parent (Mother/Father)','Guardian','Sibling (Brother/Sister)','Grandparent (Grandmother/Grandfather)','Aunt/Uncle','Cousin','Step-Parent (Step-Mother/Step-Father)','Step-Sibling (Step-Brother/Step-Sister)','Family Friend','Godparent','Neighbor','Teacher/Professor (with permission)','Classmate/Close Friend (if authorized)','Employer/Supervisor (if working student)') NOT NULL,
  `email` varchar(100) NOT NULL,
  `complete_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counseling_appointments`
--

CREATE TABLE `counseling_appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `preferred_counselor` varchar(255) DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `additional_notes` text DEFAULT NULL,
  `status` enum('NEW','DONE','CANCELED','DID NOT SHOW UP') DEFAULT 'NEW',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counseling_history`
--

CREATE TABLE `counseling_history` (
  `id` int(11) NOT NULL,
  `counseling_id` int(11) NOT NULL,
  `previous_status` enum('NEW','DONE','CANCELED','DID NOT SHOW UP') NOT NULL,
  `new_status` enum('NEW','DONE','CANCELED','DID NOT SHOW UP') NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Computer Technology', '2025-02-10 04:58:15', '2025-05-03 03:52:04'),
(2, 'Automotive Technology', '2025-02-10 04:58:48', '2025-02-10 05:04:26'),
(3, 'Food Processing Technology', '2025-02-10 05:00:21', '2025-02-10 05:04:26'),
(4, 'Electronics Technology', '2025-02-10 05:00:45', '2025-02-10 05:04:26'),
(5, 'Electrical Technology', '2025-02-10 05:00:56', '2025-02-10 05:04:26'),
(6, 'Mechanical Technology', '2025-02-10 05:01:13', '2025-02-10 05:04:26'),
(7, 'Drafting Technology', '2025-02-10 05:01:34', '2025-02-10 05:04:26'),
(8, 'Electronics & Communication Technology', '2025-02-10 05:02:04', '2025-02-10 05:04:26'),
(9, 'Mechatronics Technology', '2025-02-10 05:02:33', '2025-02-10 05:04:26'),
(10, 'Welding Technology', '2025-02-10 05:02:43', '2025-02-10 05:04:26'),
(16, 'HVAC/R', '2025-03-03 03:10:49', '2025-03-03 03:10:49');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `subject_report` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `evidence` text DEFAULT NULL,
  `status` enum('NEW','ACTIVE','RESOLVED','UNRESOLVED') DEFAULT 'NEW',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `video_evidence` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `meeting_schedule_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `full_name`, `student_number`, `email`, `phone_number`, `department`, `subject_report`, `description`, `evidence`, `status`, `created_at`, `video_evidence`, `user_id`, `meeting_schedule_id`) VALUES
(100, 'Ivan Claine', '2021102030', 'ivanclainevelasco0718@gmail.com', '09231232311', 'Computer Technology', 'Test Mail', '📄 Incident Report Template\r\n\r\nOn [5/3/2025, 3:24:58 PM], an incident took place at [insert exact location]. The individuals involved were [insert full names and their respective roles]. The incident unfolded as follows: [provide a clear and detailed account of what happened, including the sequence of events, contributing factors, and the situation before, during, and after the incident]. Witnesses present at the scene included [insert names and roles, or indicate \"none\" if no witnesses were present]. Following the incident, the immediate actions taken were: [explain any steps taken in response to the incident, such as reporting, first aid, or intervention]. I would like to respectfully recommend or request the following: [state any suggestions, preventive measures, or assistance needed].\r\n\r\nThis report is submitted by [Ivan Claine] on [5/3/2025, 3:24:58 PM].', '', 'NEW', '2025-05-03 07:25:06', NULL, 37, NULL),
(101, 'Ivan Claine', '2021102030', 'ivanclainevelasco0718@gmail.com', '09231232311', 'Computer Technology', 'TEST EMAIL SENT TO GIJON', '📄 Incident Report Template\r\n\r\nOn [5/3/2025, 3:30:00 PM], an incident took place at [insert exact location]. The individuals involved were [insert full names and their respective roles]. The incident unfolded as follows: [provide a clear and detailed account of what happened, including the sequence of events, contributing factors, and the situation before, during, and after the incident]. Witnesses present at the scene included [insert names and roles, or indicate \"none\" if no witnesses were present]. Following the incident, the immediate actions taken were: [explain any steps taken in response to the incident, such as reporting, first aid, or intervention]. I would like to respectfully recommend or request the following: [state any suggestions, preventive measures, or assistance needed].\r\n\r\nThis report is submitted by [Ivan Claine] on [5/3/2025, 3:30:00 PM].', '', 'NEW', '2025-05-03 07:31:11', NULL, 37, NULL),
(102, 'Ivan Claine', '2021102030', 'ivanclainevelasco0718@gmail.com', '09231232311', 'Computer Technology', 'Laboratory accidents', '📄 Incident Report Template\r\n\r\nOn [5/4/2025, 4:21:38 PM], an incident took place at [insert exact location]. The individuals involved were [insert full names and their respective roles]. The incident unfolded as follows: [provide a clear and detailed account of what happened, including the sequence of events, contributing factors, and the situation before, during, and after the incident]. Witnesses present at the scene included [insert names and roles, or indicate \"none\" if no witnesses were present]. Following the incident, the immediate actions taken were: [explain any steps taken in response to the incident, such as reporting, first aid, or intervention]. I would like to respectfully recommend or request the following: [state any suggestions, preventive measures, or assistance needed].\r\n\r\nThis report is submitted by [Ivan Claine] on [5/4/2025, 4:21:38 PM].', '../image/6817239c52da2_Student1.jpg,../image/6817239c53231_Student1.jpg', 'NEW', '2025-05-04 08:21:48', NULL, 37, 1);

-- --------------------------------------------------------

--
-- Table structure for table `incident_history`
--

CREATE TABLE `incident_history` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `previous_status` enum('NEW','ACTIVE','RESOLVED','UNRESOLVED') NOT NULL,
  `new_status` enum('NEW','ACTIVE','RESOLVED','UNRESOLVED') NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` enum('0','1') DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_schedules`
--

CREATE TABLE `meeting_schedules` (
  `id` int(11) NOT NULL,
  `incident_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meeting_schedules`
--

INSERT INTO `meeting_schedules` (`id`, `incident_id`, `admin_id`, `meeting_date`, `meeting_time`, `status`, `created_at`) VALUES
(1, 102, 138, '2025-05-05', '12:00:00', 'APPROVED', '2025-05-04 08:21:48');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_requests`
--

CREATE TABLE `password_reset_requests` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_requests`
--

INSERT INTO `password_reset_requests` (`id`, `email`, `request_date`, `status`, `processed_by`, `processed_date`) VALUES
(11, 'gijonevangelista1202@gmail.com', '2025-05-03 03:22:03', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'current_semester', '1', '2025-05-12 03:17:44', '2025-05-12 03:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `department` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_profile` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `middle_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone_number`, `department`, `student_number`, `password`, `user_profile`, `created_at`, `middle_name`) VALUES
(37, 'Ivan', 'Claine', 'ivanclainevelasco0718@gmail.com', '09231232311', 1, '2021102030', '$2y$10$sh0VQ9Qq9YfNG3iMR/4hc.EEDqqMQN7DJTjpAGVh2mkYt03uOzGb2', '../image/Student1.jpg', '2025-05-03 07:24:46', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `user_additional_info`
--

CREATE TABLE `user_additional_info` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `age` int(3) NOT NULL,
  `place_of_birth` varchar(100) NOT NULL,
  `civil_status` varchar(20) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `religion` varchar(50) NOT NULL,
  `height` varchar(10) NOT NULL,
  `weight` varchar(10) NOT NULL,
  `blood_type` varchar(10) NOT NULL,
  `pwd_with_special_needs` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `house_number` varchar(255) NOT NULL,
  `province` varchar(255) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `role_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '2025-02-07 11:23:53', '2025-03-28 16:25:20'),
(2, 'Counselor', '2025-02-07 11:23:53', '2025-03-01 03:28:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_blocked_times`
--
ALTER TABLE `admin_blocked_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`);

--
-- Indexes for table `cit_students`
--
ALTER TABLE `cit_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_cit_student_department` (`department_id`);

--
-- Indexes for table `contact_persons`
--
ALTER TABLE `contact_persons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_persons_2`
--
ALTER TABLE `contact_persons_2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `counseling_appointments`
--
ALTER TABLE `counseling_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `counseling_history`
--
ALTER TABLE `counseling_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `counseling_id` (`counseling_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meeting_schedule_id` (`meeting_schedule_id`);

--
-- Indexes for table `incident_history`
--
ALTER TABLE `incident_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`);

--
-- Indexes for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `department` (`department`);

--
-- Indexes for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_blocked_times`
--
ALTER TABLE `admin_blocked_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `cit_students`
--
ALTER TABLE `cit_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=391;

--
-- AUTO_INCREMENT for table `contact_persons`
--
ALTER TABLE `contact_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `contact_persons_2`
--
ALTER TABLE `contact_persons_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `counseling_appointments`
--
ALTER TABLE `counseling_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `counseling_history`
--
ALTER TABLE `counseling_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `incident_history`
--
ALTER TABLE `incident_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_blocked_times`
--
ALTER TABLE `admin_blocked_times`
  ADD CONSTRAINT `admin_blocked_times_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`),
  ADD CONSTRAINT `admin_blocked_times_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `cit_students`
--
ALTER TABLE `cit_students`
  ADD CONSTRAINT `fk_cit_student_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `contact_persons`
--
ALTER TABLE `contact_persons`
  ADD CONSTRAINT `contact_persons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `contact_persons_2`
--
ALTER TABLE `contact_persons_2`
  ADD CONSTRAINT `contact_persons_2_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counseling_appointments`
--
ALTER TABLE `counseling_appointments`
  ADD CONSTRAINT `counseling_appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counseling_history`
--
ALTER TABLE `counseling_history`
  ADD CONSTRAINT `counseling_history_ibfk_1` FOREIGN KEY (`counseling_id`) REFERENCES `counseling_appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`meeting_schedule_id`) REFERENCES `meeting_schedules` (`id`);

--
-- Constraints for table `incident_history`
--
ALTER TABLE `incident_history`
  ADD CONSTRAINT `incident_history_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meeting_schedules`
--
ALTER TABLE `meeting_schedules`
  ADD CONSTRAINT `meeting_schedules_ibfk_1` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`),
  ADD CONSTRAINT `meeting_schedules_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `password_reset_requests`
--
ALTER TABLE `password_reset_requests`
  ADD CONSTRAINT `password_reset_requests_ibfk_1` FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_additional_info`
--
ALTER TABLE `user_additional_info`
  ADD CONSTRAINT `user_additional_info_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
