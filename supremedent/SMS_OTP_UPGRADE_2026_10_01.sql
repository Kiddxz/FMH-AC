-- SupremeDent Patient SMS OTP upgrade
USE supremedent_db;

ALTER TABLE patient_profiles ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL AFTER phone;

CREATE TABLE IF NOT EXISTS patient_sms_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  request_ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sms_otp_user_phone (user_id,phone),
  INDEX idx_sms_otp_expires (expires_at),
  INDEX idx_sms_otp_ip_time (request_ip,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
