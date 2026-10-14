
-- SupremeDent complete workflow upgrade
USE supremedent_db;

ALTER TABLE appointments ADD COLUMN IF NOT EXISTS dentist_id INT NULL AFTER patient_id;
ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appointments_dentist (dentist_id);

CREATE TABLE IF NOT EXISTS patient_profiles (
  user_id INT NOT NULL PRIMARY KEY, phone VARCHAR(50) NULL, phone_verified_at DATETIME NULL, address TEXT NULL, birthday DATE NULL, gender VARCHAR(30) NULL, medical_history TEXT NULL, dental_history TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS treatment_plans (
 id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, dentist_id INT NULL, procedure_name VARCHAR(180) NOT NULL, tooth_number VARCHAR(50) NULL, description TEXT NULL, estimated_cost DECIMAL(10,2) DEFAULT 0, status ENUM('Proposed','Approved','In Progress','Completed','Cancelled') DEFAULT 'Proposed', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(dentist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS laboratory_cases (
 id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, dentist_id INT NULL, treatment_plan_id INT NULL, laboratory_name VARCHAR(150) NOT NULL, case_type VARCHAR(150) NOT NULL, request_date DATE NOT NULL, due_date DATE NULL, release_date DATE NULL, status ENUM('Pending','Sent','In Progress','Ready','Released','Cancelled') DEFAULT 'Pending', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(dentist_id), INDEX(treatment_plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, action VARCHAR(120) NOT NULL, module VARCHAR(100) NULL, details TEXT NULL, ip_address VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS clinic_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS services (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, description TEXT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS announcements (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, message TEXT NOT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS permissions (id INT AUTO_INCREMENT PRIMARY KEY, permission_key VARCHAR(100) NOT NULL UNIQUE, label VARCHAR(150) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS role_permissions (role ENUM('patient','assistant','dentist','superadmin') NOT NULL, permission_id INT NOT NULL, PRIMARY KEY(role,permission_id), INDEX(permission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO patient_profiles(user_id) SELECT id FROM users WHERE role='patient';
INSERT IGNORE INTO clinic_settings(setting_key,setting_value) VALUES
('clinic_name','SupremeDent Dental Services'),('clinic_phone',''),('clinic_email',''),('clinic_address',''),('clinic_hours','Monday - Saturday | By appointment');
INSERT INTO services(name,description) SELECT 'Dental Checkup','Routine oral examination and consultation.' WHERE NOT EXISTS (SELECT 1 FROM services LIMIT 1);
INSERT INTO services(name,description) SELECT 'Dental Cleaning','Professional cleaning and oral hygiene care.' WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Dental Cleaning');
INSERT INTO services(name,description) SELECT 'Dental Restoration','Restorative procedures planned by the dentist.' WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Dental Restoration');
INSERT INTO services(name,description) SELECT 'Tooth Extraction','Tooth extraction when clinically indicated.' WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Tooth Extraction');

-- Staff passwords are intentionally not overwritten by this SQL file.
-- 2026 adviser-feedback update: public inquiries, booking calendar settings, laboratory scope toggle
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL,
  phone VARCHAR(50) NULL,
  subject VARCHAR(180) NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(status), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO clinic_settings(setting_key,setting_value) VALUES
('appointment_start_time','09:00'),
('appointment_end_time','17:00'),
('appointment_slot_minutes','60'),
('booking_lead_hours','3'),
('laboratory_enabled','0');

-- SupremeDent 2026 security + patient 3D concern upgrade
CREATE TABLE IF NOT EXISTS auth_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL,
  purpose ENUM('signup','login','reset') NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  request_ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auth_otp_email_purpose (email,purpose),
  INDEX idx_auth_otp_expires (expires_at),
  INDEX idx_auth_otp_ip_time (request_ip,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patient_tooth_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  tooth_number VARCHAR(8) NOT NULL,
  concern_type VARCHAR(80) NOT NULL,
  severity TINYINT UNSIGNED NOT NULL DEFAULT 1,
  notes TEXT NULL,
  status ENUM('new','reviewed','scheduled','resolved') NOT NULL DEFAULT 'new',
  staff_note TEXT NULL,
  reviewed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tooth_reports_patient (patient_id),
  INDEX idx_tooth_reports_status (status),
  INDEX idx_tooth_reports_reviewer (reviewed_by),
  INDEX idx_tooth_reports_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2026-09-12 UI/calendar/chart compatibility upgrade
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS dentist_id INT NULL AFTER patient_id;
ALTER TABLE dental_charts MODIFY COLUMN dentist_id INT NULL;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER dentist_id;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS created_role VARCHAR(20) NULL AFTER created_by;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS chart_date DATE NULL AFTER created_role;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS examination_data LONGTEXT NULL AFTER chart_date;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER examination_data;
ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
UPDATE dental_charts SET chart_date=COALESCE(chart_date,DATE(created_at)) WHERE chart_date IS NULL;
UPDATE dental_charts SET created_by=COALESCE(created_by,dentist_id), created_role=COALESCE(NULLIF(created_role,''),'dentist') WHERE created_by IS NULL OR created_role IS NULL OR created_role='';
CREATE TABLE IF NOT EXISTS provider_unavailability (id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,unavailable_date DATE NOT NULL,start_time TIME NULL,end_time TIME NULL,all_day TINYINT(1) NOT NULL DEFAULT 1,reason VARCHAR(255) NULL,created_by INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(user_id),INDEX(unavailable_date),INDEX(user_id,unavailable_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- SMS OTP verification for patient contact numbers
ALTER TABLE patient_profiles ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL AFTER phone;
CREATE TABLE IF NOT EXISTS patient_sms_otps (
  id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, phone VARCHAR(20) NOT NULL, otp_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, request_ip VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id,phone), INDEX(expires_at), INDEX(request_ip,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
