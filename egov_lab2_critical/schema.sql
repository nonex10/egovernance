CREATE DATABASE IF NOT EXISTS egov_g2c2g CHARACTER SET utf8mb4;
USE egov_g2c2g;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20) NOT NULL DEFAULT '',
  address VARCHAR(200) NOT NULL DEFAULT '',
  district VARCHAR(60) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NOT NULL DEFAULT '',
  hint VARCHAR(255) NOT NULL DEFAULT '',
  fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  needs_amount TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = citizen enters the amount (e.g. tax)
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_no VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  service_type_id INT NOT NULL,
  details TEXT NOT NULL,
  amount_due DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  paid_at DATETIME NULL,
  status ENUM('submitted','under_review','approved','rejected','delivered') NOT NULL DEFAULT 'submitted',
  admin_remarks TEXT NULL,
  handled_by INT NULL,
  delivered_at DATETIME NULL,
  document_no VARCHAR(30) NULL,
  delivery_note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (service_type_id) REFERENCES service_types(id),
  FOREIGN KEY (handled_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- admin login: admin / admin123   |   sample citizens: password citizen123
INSERT INTO admins (username, password_hash, full_name) VALUES
('admin', '$2y$10$Xc4B.y6AIYlQqdbfPBZN2.48yprVSPbnrRIBb5zFcEEKwgrUAEDe.', 'Government Officer');

INSERT INTO users (full_name,email,password_hash,phone,address,district) VALUES
('Ram Sharma','ram@example.com','$2y$10$vu5docyUoZ7uHTY34H/KpuwKpalJmTilyf7O3GyYfhhxKnv8kYlfG','9800000001','Ward 4, Baneshwor','Kathmandu'),
('Sita Gurung','sita@example.com','$2y$10$vu5docyUoZ7uHTY34H/KpuwKpalJmTilyf7O3GyYfhhxKnv8kYlfG','9800000002','Ward 8, Lakeside','Kaski');

INSERT INTO service_types (name,description,hint,fee,needs_amount) VALUES
('Tax Payment','Pay income, property or vehicle tax online.','Tax type (income/property/vehicle), fiscal year, PAN or reference number.',0,1),
('Citizenship Application','Apply for a new citizenship certificate.','Father/mother names, permanent address, date of birth, ward office recommendation no.',0,0),
('Birth Certificate','Register a birth and obtain a certificate.','Child name, date and place of birth, parents names, hospital/ward details.',100,0),
('Marriage Registration','Register a marriage and obtain a certificate.','Names of both parties, date of marriage, witnesses.',200,0);

INSERT INTO applications (ref_no,user_id,service_type_id,details,amount_due,payment_status,paid_at,status) VALUES
('EG-2026-A1B2C3',1,3,'Child: Aarav Sharma. Born 12 Oct 2026 at Patan Hospital. Father: Ram Sharma, Mother: Gita Sharma.',100,'unpaid',NULL,'submitted'),
('EG-2026-D4E5F6',1,1,'Property tax, fiscal year 2083/84, Ward 4 Baneshwor.',2500,'paid',NOW(),'under_review'),
('EG-2026-G7H8I9',2,2,'New citizenship by descent. Permanent address: Pokhara-8, Kaski.',0,'unpaid',NULL,'submitted');
