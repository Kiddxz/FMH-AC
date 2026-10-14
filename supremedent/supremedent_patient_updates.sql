-- Optional explicit migration. Pages also create the table automatically when DB permissions permit.
-- A patient clicking Delete only hides an entry from their own view, preserving the original clinic record.
CREATE TABLE IF NOT EXISTS patient_hidden_records (
 patient_id INT NOT NULL,
 record_id INT NOT NULL,
 hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(patient_id,record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
