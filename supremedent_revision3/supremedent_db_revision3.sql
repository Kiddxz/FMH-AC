-- SupremeDent database (revision 3, 2026-10-16) — import in phpMyAdmin (Import tab) or: mysql -u root < supremedent_db_revision3.sql
-- Creates/replaces the supremedent_db database. Back up your current database first if it has data you need.
CREATE DATABASE IF NOT EXISTS supremedent_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE supremedent_db;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(120) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(100) NOT NULL,
  `reason` text DEFAULT NULL,
  `marked_teeth` text DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `source` varchar(10) NOT NULL DEFAULT 'online',
  `walkin_status` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appointments_dentist` (`dentist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES
(1,7,NULL,'2026-08-28','18:40:00','Dental Cleaning','Regular dental checkup',NULL,'approved','2026-08-28 09:39:56',NULL,NULL,NULL,'2026-10-17 00:11:55','online',NULL,NULL),
(2,7,NULL,'2026-08-29','18:57:00','Dental Cleaning','Teeth Checking',NULL,'rejected','2026-08-28 10:58:28',NULL,NULL,NULL,'2026-10-17 00:11:55','online',NULL,NULL),
(3,7,NULL,'2026-08-30','19:16:00','Dental Cleaning','masakit ngipin ko',NULL,'approved','2026-08-28 11:16:56',NULL,NULL,NULL,'2026-10-17 00:11:55','online',NULL,NULL),
(4,7,NULL,'2026-08-31','10:48:00','Dental Checkup','masakit talaga ngipin ko',NULL,'approved','2026-08-28 13:48:45',NULL,NULL,NULL,'2026-10-17 00:11:55','online',NULL,NULL),
(5,8,NULL,'2026-08-28','22:30:00','Dental Checkup','wala',NULL,'rejected','2026-08-28 14:31:06',NULL,NULL,NULL,'2026-10-17 00:11:55','online',NULL,NULL);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `auth_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `purpose` enum('signup','login','reset','register') NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `request_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auth_otp_email_purpose` (`email`,`purpose`),
  KEY `idx_auth_otp_expires` (`expires_at`),
  KEY `idx_auth_otp_ip_time` (`request_ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `auth_otps` WRITE;
/*!40000 ALTER TABLE `auth_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_otps` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `clinic_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `clinic_settings` WRITE;
/*!40000 ALTER TABLE `clinic_settings` DISABLE KEYS */;
INSERT INTO `clinic_settings` VALUES
(1,'clinic_name','SupremeDent Dental Services','2026-10-17 00:11:55'),
(2,'clinic_phone','','2026-10-17 00:11:55'),
(3,'clinic_email','','2026-10-17 00:11:55'),
(4,'clinic_address','','2026-10-17 00:11:55'),
(5,'clinic_hours','Monday - Saturday | By appointment','2026-10-17 00:11:55'),
(6,'appointment_start_time','09:00','2026-10-17 00:11:55'),
(7,'appointment_end_time','18:00','2026-10-17 00:11:55'),
(8,'appointment_slot_minutes','30','2026-10-17 00:11:55'),
(9,'booking_lead_hours','0','2026-10-17 00:11:55'),
(10,'laboratory_enabled','0','2026-10-17 00:11:55'),
(21,'schema_revision','3','2026-10-17 00:11:55');
/*!40000 ALTER TABLE `clinic_settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(180) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','resolved') NOT NULL DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `dental_chart_teeth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dental_chart_teeth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chart_id` int(11) NOT NULL,
  `tooth_number` varchar(8) NOT NULL,
  `condition_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chart_id` (`chart_id`),
  KEY `tooth_number` (`tooth_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dental_chart_teeth` WRITE;
/*!40000 ALTER TABLE `dental_chart_teeth` DISABLE KEYS */;
/*!40000 ALTER TABLE `dental_chart_teeth` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `dental_charts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dental_charts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_role` varchar(20) DEFAULT NULL,
  `chart_date` date DEFAULT NULL,
  `chart_time` time DEFAULT NULL,
  `service` varchar(150) DEFAULT NULL,
  `examination_data` longtext DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  KEY `chart_date` (`chart_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dental_charts` WRITE;
/*!40000 ALTER TABLE `dental_charts` DISABLE KEYS */;
/*!40000 ALTER TABLE `dental_charts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `dental_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dental_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `procedure_name` varchar(150) NOT NULL,
  `diagnosis` varchar(255) DEFAULT NULL,
  `dentist_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `dental_records` WRITE;
/*!40000 ALTER TABLE `dental_records` DISABLE KEYS */;
INSERT INTO `dental_records` VALUES
(1,7,'2026-08-29','Dental Cleaning','Dental caries','JuanJr Delacruz','thank you for coming','2026-08-29 10:39:05');
/*!40000 ALTER TABLE `dental_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `laboratory_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `laboratory_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) DEFAULT NULL,
  `treatment_plan_id` int(11) DEFAULT NULL,
  `laboratory_name` varchar(150) NOT NULL,
  `case_type` varchar(150) NOT NULL,
  `request_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `status` enum('Pending','Sent','In Progress','Ready','Released','Cancelled') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  KEY `treatment_plan_id` (`treatment_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `laboratory_cases` WRITE;
/*!40000 ALTER TABLE `laboratory_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `laboratory_cases` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `email` varchar(180) NOT NULL,
  `failed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `lock_level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_failed_at` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`email`),
  KEY `locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','treatment','announcement','general') DEFAULT 'general',
  `appointment_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,7,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,1,'2026-08-28 08:46:57'),
(2,7,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,1,'2026-08-28 09:39:56'),
(3,7,'Appointment Approved','Your appointment request for Aug 28, 2026 at 06:40 PM has been approved.','appointment',NULL,1,'2026-08-28 10:52:56'),
(4,7,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,1,'2026-08-28 10:58:28'),
(5,7,'Appointment Request Declined','Your appointment request for Aug 29, 2026 at 06:57 PM could not be accommodated. Please choose another schedule.','appointment',NULL,1,'2026-08-28 10:59:40'),
(6,7,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,1,'2026-08-28 11:16:56'),
(7,7,'Appointment Approved','Your appointment request for Aug 30, 2026 at 07:16 PM has been approved.','appointment',NULL,1,'2026-08-28 11:17:41'),
(8,7,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,0,'2026-08-28 13:48:46'),
(9,7,'Appointment Approved','Your appointment request for Aug 31, 2026 at 10:48 AM has been approved.','appointment',NULL,0,'2026-08-28 13:49:32'),
(10,8,'Appointment Request','Your appointment request has been received successfully.','appointment',NULL,1,'2026-08-28 14:31:06'),
(11,8,'Appointment Request Declined','Your appointment request for Aug 28, 2026 at 10:30 PM could not be accommodated. Please choose another schedule.','appointment',NULL,0,'2026-08-28 14:34:27');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `odontogram_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `odontogram_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `tooth_number` int(11) NOT NULL,
  `condition_name` varchar(100) DEFAULT NULL,
  `treatment` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `dentist_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_patient_tooth` (`patient_id`,`tooth_number`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `odontogram_records` WRITE;
/*!40000 ALTER TABLE `odontogram_records` DISABLE KEYS */;
INSERT INTO `odontogram_records` VALUES
(1,7,9,'','','',5,'2026-08-29 13:51:46','2026-08-29 13:51:46');
/*!40000 ALTER TABLE `odontogram_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `patient_hidden_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_hidden_records` (
  `patient_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `hidden_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`patient_id`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patient_hidden_records` WRITE;
/*!40000 ALTER TABLE `patient_hidden_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_hidden_records` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `patient_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_profiles` (
  `user_id` int(11) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `dental_history` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patient_profiles` WRITE;
/*!40000 ALTER TABLE `patient_profiles` DISABLE KEYS */;
INSERT INTO `patient_profiles` VALUES
(7,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-17 00:11:55');
/*!40000 ALTER TABLE `patient_profiles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `patient_sms_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_sms_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sms_otp_user_phone` (`user_id`,`phone`),
  KEY `idx_sms_otp_expires` (`expires_at`),
  KEY `idx_sms_otp_ip_time` (`request_ip`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patient_sms_otps` WRITE;
/*!40000 ALTER TABLE `patient_sms_otps` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_sms_otps` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `patient_tooth_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_tooth_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `tooth_number` varchar(8) NOT NULL,
  `concern_type` varchar(80) NOT NULL,
  `severity` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `status` enum('new','reviewed','scheduled','resolved') NOT NULL DEFAULT 'new',
  `staff_note` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tooth_reports_patient` (`patient_id`),
  KEY `idx_tooth_reports_status` (`status`),
  KEY `idx_tooth_reports_reviewer` (`reviewed_by`),
  KEY `idx_tooth_reports_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `patient_tooth_reports` WRITE;
/*!40000 ALTER TABLE `patient_tooth_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `patient_tooth_reports` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `provider_unavailability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `provider_unavailability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `unavailable_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 1,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `unavailable_date` (`unavailable_date`),
  KEY `user_id_2` (`user_id`,`unavailable_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `provider_unavailability` WRITE;
/*!40000 ALTER TABLE `provider_unavailability` DISABLE KEYS */;
/*!40000 ALTER TABLE `provider_unavailability` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `restoration_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `restoration_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) NOT NULL,
  `treatment_plan_id` int(11) DEFAULT NULL,
  `restoration_type` enum('removable','fixed') NOT NULL,
  `order_no` varchar(50) DEFAULT NULL,
  `date_sent` date DEFAULT NULL,
  `date_due` date DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `sex` varchar(30) DEFAULT NULL,
  `age` varchar(20) DEFAULT NULL,
  `selected_teeth` text DEFAULT NULL,
  `work_options` longtext DEFAULT NULL,
  `teeth_material_options` longtext DEFAULT NULL,
  `shade` varchar(100) DEFAULT NULL,
  `mould` varchar(100) DEFAULT NULL,
  `staining_options` longtext DEFAULT NULL,
  `metal_design` text DEFAULT NULL,
  `pontic_design` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('Draft','Sent','In Progress','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `created_role` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  KEY `restoration_type` (`restoration_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `restoration_orders` WRITE;
/*!40000 ALTER TABLE `restoration_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `restoration_orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role` enum('patient','assistant','dentist','superadmin') NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'Dental Checkup','Routine oral examination and consultation.',1,'2026-10-17 00:11:55'),
(2,'Dental Cleaning','Professional cleaning and oral hygiene care.',1,'2026-10-17 00:11:55'),
(3,'Dental Restoration','Restorative procedures planned by the dentist.',1,'2026-10-17 00:11:55'),
(4,'Tooth Extraction','Tooth extraction when clinically indicated.',1,'2026-10-17 00:11:55');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `staff_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'appointment',
  `appointment_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`is_read`),
  KEY `appointment_id` (`appointment_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `staff_notifications` WRITE;
/*!40000 ALTER TABLE `staff_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `treatment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `treatment_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `dentist_id` int(11) DEFAULT NULL,
  `procedure_name` varchar(180) NOT NULL,
  `tooth_number` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('Proposed','Approved','In Progress','Completed','Cancelled') DEFAULT 'Proposed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `treatment_plans` WRITE;
/*!40000 ALTER TABLE `treatment_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `treatment_plans` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_messages` WRITE;
/*!40000 ALTER TABLE `user_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_messages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','assistant','dentist','superadmin') NOT NULL DEFAULT 'patient',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_no` int(11) DEFAULT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uniq_users_patient_no` (`patient_no`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Test','Assistant','assistant@supremedent.com','$2y$10$iAPDma2LKkAJDbpGzNZZa.m5Ch8eM2KVo8zYIrSNhI4COjL076ZZi','assistant','active','2026-08-25 02:53:41',NULL,0),
(2,'Super','Admin','admin@supremedent.com','$2y$10$TjAvXPuPyIGxMAMjElAnk.VfCQmWw4eoW6xLjucXxTlMHbu5WFwZy','superadmin','active','2026-08-25 03:41:24',NULL,0),
(5,'JuanJr','Delacruz','juan.dentist@supremedent.com','$2y$10$AQAf47tqjedfvqOXmJw.3eQ1GISHBaBbRRHZhBamUAdNqz.zHHBty','dentist','active','2026-08-25 04:19:08',NULL,0),
(7,'Marias','Delacruz','maria1@gmail.com','$2y$10$sTSFlZXX2vJ2yfSIl3e71O3KMjXpc5elk0jDbwARANGoWAOP8qImS','patient','active','2026-08-26 00:56:15',1,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

