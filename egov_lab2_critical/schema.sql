CREATE DATABASE IF NOT EXISTS egov_lab2_critical CHARACTER SET utf8mb4;
USE egov_lab2_critical;

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
  age TINYINT UNSIGNED NOT NULL,
  gender ENUM('male','female','other') NOT NULL,
  province VARCHAR(40) NOT NULL,
  district VARCHAR(60) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  category_id INT NOT NULL,
  severity ENUM('info','warning','critical') DEFAULT 'info',
  target_min_age TINYINT UNSIGNED NULL,
  target_max_age TINYINT UNSIGNED NULL,
  target_gender ENUM('all','male','female','other') DEFAULT 'all',
  target_province VARCHAR(40) NOT NULL DEFAULT 'All',
  target_district VARCHAR(60) NOT NULL DEFAULT '',
  status ENUM('draft','published','archived') DEFAULT 'draft',
  publish_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry_date DATE NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (created_by) REFERENCES admins(id)
);

-- admin login: admin / admin123   |   sample citizens: password citizen123
INSERT INTO admins (username, password_hash, full_name) VALUES ('admin', '$2y$10$AL/mzy6uwwhL7EVBapaHpOYo8DwwYWlIx75ti5QSoQ2yX.grRzWse', 'Government Officer');

INSERT INTO users (full_name,email,password_hash,age,gender,province,district) VALUES
('Ram Sharma','ram@example.com','$2y$10$y5oZUtWFjW/y7RcLUS/RVOuMZXmgtdrbbw0Aw2mUOfyGc0CClr4VG',25,'male','Bagmati','Kathmandu'),
('Sita Gurung','sita@example.com','$2y$10$y5oZUtWFjW/y7RcLUS/RVOuMZXmgtdrbbw0Aw2mUOfyGc0CClr4VG',34,'female','Gandaki','Kaski'),
('Hari Rai','hari@example.com','$2y$10$y5oZUtWFjW/y7RcLUS/RVOuMZXmgtdrbbw0Aw2mUOfyGc0CClr4VG',58,'male','Koshi','Morang');

INSERT INTO categories (name) VALUES ('Corruption'),('Human Rights'),('Crime'),('Environmental'),('Catastrophic Failure');

INSERT INTO reports (title,body,category_id,severity,target_min_age,target_max_age,target_gender,target_province,target_district,status,created_by) VALUES
('Anti-corruption complaint helpline launched','Citizens can report bribery or misuse of public funds through the national complaint helpline and online portal.',1,'info',NULL,NULL,'all','All','','published',1),
('Air quality alert: Kathmandu Valley','Air pollution has reached unhealthy levels. Avoid outdoor exercise and wear masks outdoors.',4,'warning',NULL,NULL,'all','Bagmati','Kathmandu','published',1),
('Online fraud targeting young people','Fake job and scholarship offers are circulating on social media. Never pay a fee to unknown accounts.',3,'warning',15,30,'all','All','','published',1),
('Free legal aid desk for women','Free legal assistance is available for domestic violence and discrimination cases at your district office.',2,'info',NULL,NULL,'female','All','','published',1),
('Flood warning: Koshi Province','Heavy rainfall may cause river flooding. Move to higher ground if you live near river banks.',5,'critical',NULL,NULL,'all','Koshi','','published',1),
('Senior citizen allowance verification','Citizens aged 55+ must verify their details at the ward office to continue receiving allowances.',2,'info',55,NULL,'all','All','','published',1);
