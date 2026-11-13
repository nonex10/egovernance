CREATE DATABASE IF NOT EXISTS egov_broadcast CHARACTER SET utf8mb4;
USE egov_broadcast;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  category_id INT NOT NULL,
  priority ENUM('normal','important','urgent') DEFAULT 'normal',
  status ENUM('draft','published','archived') DEFAULT 'draft',
  publish_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry_date DATE NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (created_by) REFERENCES admins(id)
);

-- default login: admin / admin123
INSERT INTO admins (username, password_hash, full_name)
VALUES ('admin', '$2y$10$AL/mzy6uwwhL7EVBapaHpOYo8DwwYWlIx75ti5QSoQ2yX.grRzWse', 'Broadcast Officer');

INSERT INTO categories (name) VALUES
('Public Notice'),('Health'),('Education'),('Weather & Disaster'),('Tax & Finance'),('Election');

INSERT INTO messages (title, body, category_id, priority, status, created_by) VALUES
('Monsoon flood alert', 'Residents near river banks are advised to move to safe shelters.', 4, 'urgent', 'published', 1),
('Free vaccination camp', 'Free vaccination camp at the ward office from 10 AM to 3 PM this Saturday.', 2, 'important', 'published', 1),
('Tax filing deadline', 'Annual income tax returns must be filed before the end of the fiscal year.', 5, 'normal', 'published', 1);
