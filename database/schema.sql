-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2026 at 10:40 AM
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
-- Database: `stepper_loans`
--

-- --------------------------------------------------------

--
-- Table structure for table `application_reviews`
--

CREATE TABLE `application_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_id` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('under_review','more_info_required','approved','rejected') NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `notes` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_rate_limits`
--

CREATE TABLE `auth_rate_limits` (
  `identifier_hash` char(64) NOT NULL,
  `window_started_at` datetime NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrower_profiles`
--

CREATE TABLE `borrower_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `national_id_ciphertext` varbinary(128) NOT NULL,
  `national_id_lookup_hash` char(64) NOT NULL,
  `national_id_last4` char(4) NOT NULL,
  `residential_address` varchar(220) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `institution` varchar(160) NOT NULL,
  `course` varchar(160) NOT NULL,
  `year_of_study` tinyint(3) UNSIGNED NOT NULL,
  `monthly_income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monthly_expenses` decimal(12,2) NOT NULL DEFAULT 0.00,
  `emergency_name` varchar(120) NOT NULL,
  `emergency_phone` varchar(24) NOT NULL,
  `emergency_address` varchar(220) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `subject` varchar(160) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('mpesa','bank','manual') NOT NULL DEFAULT 'mpesa',
  `provider_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','failed','reversed') NOT NULL DEFAULT 'pending',
  `initiated_by` bigint(20) UNSIGNED NOT NULL,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_ref` varchar(30) NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `borrower_id` bigint(20) UNSIGNED NOT NULL,
  `principal` decimal(12,2) NOT NULL,
  `interest_rate` decimal(7,4) NOT NULL,
  `interest_amount` decimal(12,2) NOT NULL,
  `total_payable` decimal(12,2) NOT NULL,
  `outstanding_balance` decimal(12,2) NOT NULL,
  `term_weeks` smallint(5) UNSIGNED NOT NULL,
  `pricing_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pricing_snapshot`)),
  `status` enum('offer_pending','accepted','disbursement_pending','active','overdue','repaid','cancelled') NOT NULL DEFAULT 'offer_pending',
  `offer_accepted_at` datetime DEFAULT NULL,
  `disbursed_at` datetime DEFAULT NULL,
  `maturity_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_ref` varchar(30) NOT NULL,
  `borrower_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `requested_term_weeks` smallint(5) UNSIGNED NOT NULL,
  `purpose` varchar(500) NOT NULL,
  `additional_details` text DEFAULT NULL,
  `status` enum('draft','submitted','under_review','more_info_required','approved','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

CREATE TABLE `loan_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(12,2) NOT NULL,
  `max_amount` decimal(12,2) NOT NULL,
  `max_term_weeks` smallint(5) UNSIGNED NOT NULL,
  `pricing_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`pricing_config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('application','loan','payment','system') NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_ref` varchar(40) NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `payer_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('mpesa','bank','cash','manual') NOT NULL DEFAULT 'mpesa',
  `provider_transaction_ref` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','failed','reversed') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `repayment_schedules`
--

CREATE TABLE `repayment_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `instalment_number` smallint(5) UNSIGNED NOT NULL,
  `due_date` date NOT NULL,
  `principal_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_due` decimal(12,2) NOT NULL,
  `principal_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `phone` varchar(24) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('borrower','admin') NOT NULL DEFAULT 'borrower',
  `account_status` enum('active','suspended','closed') NOT NULL DEFAULT 'active',
  `email_verified_at` datetime DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `consent_given_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `sms_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application_reviews`
--
ALTER TABLE `application_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_review_admin` (`reviewer_id`),
  ADD KEY `idx_review_application_date` (`application_id`,`reviewed_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_actor_date` (`actor_id`,`created_at`);

--
-- Indexes for table `auth_rate_limits`
--
ALTER TABLE `auth_rate_limits`
  ADD PRIMARY KEY (`identifier_hash`),
  ADD KEY `idx_auth_rate_window` (`window_started_at`);

--
-- Indexes for table `borrower_profiles`
--
ALTER TABLE `borrower_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `national_id_lookup_hash` (`national_id_lookup_hash`),
  ADD UNIQUE KEY `admission_number` (`admission_number`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contact_status_date` (`status`,`created_at`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `provider_reference` (`provider_reference`),
  ADD KEY `fk_disbursement_admin` (`initiated_by`),
  ADD KEY `idx_disbursement_loan_date` (`loan_id`,`initiated_at`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_ref` (`loan_ref`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `idx_loan_borrower_status` (`borrower_id`,`status`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_ref` (`application_ref`),
  ADD KEY `fk_application_product` (`product_id`),
  ADD KEY `idx_application_borrower_status` (`borrower_id`,`status`),
  ADD KEY `idx_application_status_date` (`status`,`created_at`);

--
-- Indexes for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_user_read` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_ref` (`payment_ref`),
  ADD UNIQUE KEY `provider_transaction_ref` (`provider_transaction_ref`),
  ADD KEY `idx_payment_loan_status` (`loan_id`,`status`),
  ADD KEY `idx_payment_payer` (`payer_id`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_schedule` (`payment_id`,`schedule_id`),
  ADD KEY `fk_allocation_schedule` (`schedule_id`);

--
-- Indexes for table `repayment_schedules`
--
ALTER TABLE `repayment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loan_instalment` (`loan_id`,`instalment_number`),
  ADD KEY `idx_schedule_due_status` (`due_date`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_user_role_status` (`role`,`account_status`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application_reviews`
--
ALTER TABLE `application_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `borrower_profiles`
--
ALTER TABLE `borrower_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_products`
--
ALTER TABLE `loan_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `repayment_schedules`
--
ALTER TABLE `repayment_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application_reviews`
--
ALTER TABLE `application_reviews`
  ADD CONSTRAINT `fk_review_admin` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_application` FOREIGN KEY (`application_id`) REFERENCES `loan_applications` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `borrower_profiles`
--
ALTER TABLE `borrower_profiles`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `fk_disbursement_admin` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disbursement_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loan_application` FOREIGN KEY (`application_id`) REFERENCES `loan_applications` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_loan_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `fk_application_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_application_product` FOREIGN KEY (`product_id`) REFERENCES `loan_products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_payer` FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `fk_allocation_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_allocation_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `repayment_schedules` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `repayment_schedules`
--
ALTER TABLE `repayment_schedules`
  ADD CONSTRAINT `fk_schedule_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
