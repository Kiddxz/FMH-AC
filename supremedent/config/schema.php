<?php
function ensure_schema(mysqli $conn): void {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('patient','assistant','dentist','superadmin') NOT NULL DEFAULT 'patient',
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            dentist_id INT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            appointment_type VARCHAR(100) NOT NULL,
            reason TEXT NULL,
            status ENUM('pending','approved','completed','cancelled','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(patient_id), INDEX(dentist_id), INDEX(status), INDEX(appointment_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('appointment','treatment','announcement','general') DEFAULT 'general',
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(patient_id), INDEX(is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS dental_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            record_date DATE NOT NULL,
            procedure_name VARCHAR(150) NOT NULL,
            diagnosis VARCHAR(255) DEFAULT NULL,
            dentist_name VARCHAR(150) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS odontogram_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            tooth_number INT NOT NULL,
            condition_name VARCHAR(100) DEFAULT NULL,
            treatment VARCHAR(150) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            dentist_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_patient_tooth (patient_id,tooth_number),
            INDEX(patient_id), INDEX(dentist_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS dental_charts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            dentist_id INT NOT NULL,
            chart_date DATE NOT NULL,
            examination_data LONGTEXT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(patient_id), INDEX(dentist_id), INDEX(chart_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS dental_chart_teeth (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chart_id INT NOT NULL,
            tooth_number VARCHAR(8) NOT NULL,
            condition_name VARCHAR(100) NOT NULL,
            INDEX(chart_id), INDEX(tooth_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS patient_profiles (user_id INT PRIMARY KEY, phone VARCHAR(50) NULL, phone_verified_at DATETIME NULL, address TEXT NULL, birthday DATE NULL, gender VARCHAR(30) NULL, medical_history TEXT NULL, dental_history TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS treatment_plans (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, dentist_id INT NULL, procedure_name VARCHAR(180) NOT NULL, tooth_number VARCHAR(50) NULL, description TEXT NULL, estimated_cost DECIMAL(10,2) DEFAULT 0, status ENUM('Proposed','Approved','In Progress','Completed','Cancelled') DEFAULT 'Proposed', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(dentist_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS laboratory_cases (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, dentist_id INT NULL, treatment_plan_id INT NULL, laboratory_name VARCHAR(150) NOT NULL, case_type VARCHAR(150) NOT NULL, request_date DATE NOT NULL, due_date DATE NULL, release_date DATE NULL, status ENUM('Pending','Sent','In Progress','Ready','Released','Cancelled') DEFAULT 'Pending', notes TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(dentist_id), INDEX(treatment_plan_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, action VARCHAR(120) NOT NULL, module VARCHAR(100) NULL, details TEXT NULL, ip_address VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS clinic_settings (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS services (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, description TEXT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS announcements (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, message TEXT NOT NULL, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS permissions (id INT AUTO_INCREMENT PRIMARY KEY, permission_key VARCHAR(100) NOT NULL UNIQUE, label VARCHAR(150) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS role_permissions (role ENUM('patient','assistant','dentist','superadmin') NOT NULL, permission_id INT NOT NULL, PRIMARY KEY(role,permission_id), INDEX(permission_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS contact_messages (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) NULL, subject VARCHAR(180) NULL, message TEXT NOT NULL, status ENUM('new','read','resolved') NOT NULL DEFAULT 'new', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(status), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS auth_otps (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(180) NOT NULL, purpose ENUM('signup','login','reset') NOT NULL, otp_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, request_ip VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(email,purpose), INDEX(expires_at), INDEX(request_ip,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS patient_sms_otps (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, phone VARCHAR(20) NOT NULL, otp_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, request_ip VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id,phone), INDEX(expires_at), INDEX(request_ip,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS patient_tooth_reports (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, tooth_number VARCHAR(8) NOT NULL, concern_type VARCHAR(80) NOT NULL, severity TINYINT UNSIGNED NOT NULL DEFAULT 1, notes TEXT NULL, status ENUM('new','reviewed','scheduled','resolved') NOT NULL DEFAULT 'new', staff_note TEXT NULL, reviewed_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(status), INDEX(reviewed_by), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS user_messages (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT NOT NULL, recipient_id INT NOT NULL, subject VARCHAR(180) NOT NULL, message TEXT NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(sender_id), INDEX(recipient_id), INDEX(is_read), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS restoration_orders (id INT AUTO_INCREMENT PRIMARY KEY, patient_id INT NOT NULL, dentist_id INT NOT NULL, treatment_plan_id INT NULL, restoration_type ENUM('removable','fixed') NOT NULL, order_no VARCHAR(50) NULL, date_sent DATE NULL, date_due DATE NULL, phone VARCHAR(50) NULL, sex VARCHAR(30) NULL, age VARCHAR(20) NULL, selected_teeth TEXT NULL, work_options LONGTEXT NULL, teeth_material_options LONGTEXT NULL, shade VARCHAR(100) NULL, mould VARCHAR(100) NULL, staining_options LONGTEXT NULL, metal_design VARCHAR(80) NULL, pontic_design VARCHAR(80) NULL, instructions TEXT NULL, status ENUM('Draft','Sent','In Progress','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Draft', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(patient_id), INDEX(dentist_id), INDEX(restoration_type), INDEX(status), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "ALTER TABLE patient_profiles ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL AFTER phone",
        "ALTER TABLE user_messages ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER is_read",
        "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS dentist_id INT NULL AFTER patient_id",
        "ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_appointments_dentist (dentist_id)",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS dentist_id INT NULL AFTER patient_id",
        "ALTER TABLE dental_charts MODIFY COLUMN dentist_id INT NULL",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS chart_date DATE NULL AFTER dentist_id",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS examination_data LONGTEXT NULL AFTER chart_date",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER examination_data",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER dentist_id",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS created_role VARCHAR(20) NULL AFTER created_by",
        "ALTER TABLE dental_charts ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        "UPDATE dental_charts SET chart_date = COALESCE(chart_date, DATE(created_at)) WHERE chart_date IS NULL",
        "UPDATE dental_charts SET created_by = COALESCE(created_by, dentist_id), created_role = COALESCE(NULLIF(created_role,''),'dentist') WHERE created_by IS NULL OR created_role IS NULL OR created_role=''",
        "CREATE TABLE IF NOT EXISTS provider_unavailability (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, unavailable_date DATE NOT NULL, start_time TIME NULL, end_time TIME NULL, all_day TINYINT(1) NOT NULL DEFAULT 1, reason VARCHAR(255) NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(unavailable_date), INDEX(user_id,unavailable_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($queries as $q) { @$conn->query($q); }

    // Revision 2 (2026-10-09): login lockout, staff notifications, appointment teeth/cancel data, SMS OTP lock.
    // Runs once per database (tracked in clinic_settings) so ALTER statements are not repeated on every request.
    $revRow = $conn->query("SELECT setting_value FROM clinic_settings WHERE setting_key='schema_revision' LIMIT 1");
    $schemaRevision = $revRow ? (int)($revRow->fetch_assoc()['setting_value'] ?? 0) : 0;
    if ($schemaRevision < 2) {
        $revision2 = [
            "CREATE TABLE IF NOT EXISTS login_attempts (email VARCHAR(180) NOT NULL PRIMARY KEY, failed_count INT UNSIGNED NOT NULL DEFAULT 0, lock_level TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_failed_at DATETIME NULL, last_ip VARCHAR(45) NULL, INDEX(locked_until)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "ALTER TABLE auth_otps MODIFY COLUMN purpose ENUM('signup','login','reset','register') NOT NULL",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS marked_teeth TEXT NULL AFTER reason",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancelled_by INT NULL",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS appointment_id INT NULL AFTER type",
            "ALTER TABLE patient_sms_otps ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL AFTER attempts",
            // Booking pop-up shows 30-minute slots from 9:00 AM to 5:30 PM (clinic hours 9 AM - 6 PM).
            "UPDATE clinic_settings SET setting_value='30' WHERE setting_key='appointment_slot_minutes' AND setting_value='60'",
            "UPDATE clinic_settings SET setting_value='18:00' WHERE setting_key='appointment_end_time' AND setting_value='17:00'",
            // Patient bookings are approved automatically now; release requests that were waiting for approval.
            "UPDATE appointments SET status='approved' WHERE status='pending'",
            "CREATE TABLE IF NOT EXISTS staff_notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(150) NOT NULL, message TEXT NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'appointment', appointment_id INT NULL, patient_id INT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id,is_read), INDEX(appointment_id), INDEX(created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
        $failed = false;
        foreach ($revision2 as $q) {
            try { if (@$conn->query($q) === false) $failed = true; }
            catch (Throwable $e) { $failed = true; error_log('[SupremeDent schema] ' . $e->getMessage()); }
        }
        if (!$failed) @$conn->query("INSERT INTO clinic_settings (setting_key,setting_value) VALUES ('schema_revision','2') ON DUPLICATE KEY UPDATE setting_value='2'");
    }

    // Revision 3 (2026-10-14): security hardening and dentist-to-patient record sharing.
    if ($schemaRevision < 3) {
        $revision3 = [
            "CREATE TABLE IF NOT EXISTS rate_limits (bucket VARCHAR(190) NOT NULL PRIMARY KEY, hits INT UNSIGNED NOT NULL DEFAULT 0, window_start INT UNSIGNED NOT NULL DEFAULT 0, INDEX(window_start)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL",
            "ALTER TABLE auth_otps MODIFY COLUMN purpose ENUM('signup','login','reset','register','setup') NOT NULL",
            "ALTER TABLE notifications MODIFY COLUMN type ENUM('appointment','treatment','announcement','general','record') DEFAULT 'general'",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS dentist_id INT NULL",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS chart_id INT NULL",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS appointment_id INT NULL",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS tooth_number VARCHAR(160) NULL",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS source VARCHAR(20) NOT NULL DEFAULT 'manual'",
            "ALTER TABLE dental_records ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
            "ALTER TABLE dental_records ADD INDEX IF NOT EXISTS idx_dental_records_chart (chart_id)",
            "ALTER TABLE dental_records ADD INDEX IF NOT EXISTS idx_dental_records_appointment (appointment_id)",
            "CREATE TABLE IF NOT EXISTS patient_hidden_records (patient_id INT NOT NULL, record_id INT NOT NULL, hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(patient_id,record_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "INSERT IGNORE INTO clinic_settings (setting_key,setting_value) VALUES ('login_otp_roles','superadmin')",
        ];
        $failed = false;
        foreach ($revision3 as $q) {
            try { if (@$conn->query($q) === false) $failed = true; }
            catch (Throwable $e) { $failed = true; error_log('[SupremeDent schema] ' . $e->getMessage()); }
        }
        if (!$failed) @$conn->query("INSERT INTO clinic_settings (setting_key,setting_value) VALUES ('schema_revision','3') ON DUPLICATE KEY UPDATE setting_value='3'");
    }

    $defaults = [
      ['clinic_name','SupremeDent Dental Services'],['clinic_phone',''],['clinic_email',''],['clinic_address',''],['clinic_hours','Monday - Saturday | By appointment'],
      ['appointment_start_time','09:00'],['appointment_end_time','18:00'],['appointment_slot_minutes','30'],['booking_lead_hours','3'],['laboratory_enabled','0']
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO clinic_settings (setting_key,setting_value) VALUES (?,?)");
    if ($stmt) { foreach ($defaults as $d) { $stmt->bind_param('ss',$d[0],$d[1]); $stmt->execute(); } $stmt->close(); }

    $services = [['Dental Checkup','Routine oral examination and consultation.'],['Dental Cleaning','Professional cleaning and oral hygiene care.'],['Dental Restoration','Restorative procedures planned by the dentist.'],['Tooth Extraction','Tooth extraction when clinically indicated.']];
    $stmt = $conn->prepare("INSERT INTO services (name,description) SELECT ?,? WHERE NOT EXISTS (SELECT 1 FROM services WHERE name=?)");
    if ($stmt) { foreach ($services as $d) { $stmt->bind_param('sss',$d[0],$d[1],$d[0]); $stmt->execute(); } $stmt->close(); }

    // Staff and superadmin accounts are never auto-created or password-reset here.
    // Create/manage them through the authenticated Super Admin portal.

}
function log_activity(mysqli $conn, ?int $userId, string $action, string $module='', string $details=''): void {
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $action = mb_substr($action, 0, 120);
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id,action,module,details,ip_address) VALUES (?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param('issss',$userId,$action,$module,$details,$ip); $stmt->execute(); $stmt->close(); }
}
/** Legacy helper kept for old pages: always delegates to the database-backed portal guard. */
function require_role(array $roles): void {
    global $conn;
    require_once __DIR__ . '/security.php';
    require_portal_role($conn, $roles, false);
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function clinic_settings(mysqli $conn): array {
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM clinic_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
function patient_profile_data(mysqli $conn, int $userId): array {
    $profile = ['phone'=>'','phone_verified_at'=>null,'address'=>'','birthday'=>'','gender'=>'','medical_history'=>'','dental_history'=>''];
    $stmt = $conn->prepare("SELECT phone,phone_verified_at,address,birthday,gender,medical_history,dental_history FROM patient_profiles WHERE user_id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i',$userId); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($row) $profile = array_merge($profile,$row);
    }
    return $profile;
}
function patient_profile_is_complete(mysqli $conn, int $userId): bool {
    $profile = patient_profile_data($conn,$userId);
    foreach (['phone','address','birthday','gender'] as $field) {
        if (trim((string)($profile[$field] ?? '')) === '') return false;
    }
    // A contact number counts as complete only after the patient proves ownership by SMS OTP.
    if (empty($profile['phone_verified_at'])) return false;
    return true;
}
/** One clinic-wide rule for bookings, reschedules, staff approvals and calendar displays. */
function clinic_open_day(string $date): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $day !== false && $day->format('Y-m-d') === $date && $day->format('w') !== '0';
}

function appointment_slot_settings(mysqli $conn): array {
    $s = clinic_settings($conn);
    $start = preg_match('/^\\d{2}:\\d{2}$/',(string)($s['appointment_start_time'] ?? '')) ? $s['appointment_start_time'] : '09:00';
    $end = preg_match('/^\\d{2}:\\d{2}$/',(string)($s['appointment_end_time'] ?? '')) ? $s['appointment_end_time'] : '17:00';
    $minutes = max(15,min(240,(int)($s['appointment_slot_minutes'] ?? 60)));
    $lead = max(0,min(72,(int)($s['booking_lead_hours'] ?? 3)));
    return ['start'=>$start,'end'=>$end,'minutes'=>$minutes,'lead_hours'=>$lead];
}
