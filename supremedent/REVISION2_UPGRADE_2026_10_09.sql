-- SupremeDent revision 2 (2026-10-09) database upgrade.
-- Optional: the website applies these automatically on first page load (config/schema.php).
-- Back up the database before running this file manually in phpMyAdmin.
USE supremedent_db;

CREATE TABLE IF NOT EXISTS login_attempts (email VARCHAR(180) NOT NULL PRIMARY KEY, failed_count INT UNSIGNED NOT NULL DEFAULT 0, lock_level TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_failed_at DATETIME NULL, last_ip VARCHAR(45) NULL, INDEX(locked_until)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE auth_otps MODIFY COLUMN purpose ENUM('signup','login','reset','register') NOT NULL;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS marked_teeth TEXT NULL AFTER reason;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancelled_by INT NULL;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL;
ALTER TABLE appointments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS appointment_id INT NULL AFTER type;
ALTER TABLE patient_sms_otps ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL AFTER attempts;
UPDATE clinic_settings SET setting_value='30' WHERE setting_key='appointment_slot_minutes' AND setting_value='60';
UPDATE clinic_settings SET setting_value='18:00' WHERE setting_key='appointment_end_time' AND setting_value='17:00';
UPDATE appointments SET status='approved' WHERE status='pending';
CREATE TABLE IF NOT EXISTS staff_notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(150) NOT NULL, message TEXT NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'appointment', appointment_id INT NULL, patient_id INT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id,is_read), INDEX(appointment_id), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO clinic_settings (setting_key,setting_value) VALUES ('schema_revision','2') ON DUPLICATE KEY UPDATE setting_value='2';
