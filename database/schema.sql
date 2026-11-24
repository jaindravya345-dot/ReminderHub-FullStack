CREATE DATABASE IF NOT EXISTS reminderhub
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reminderhub;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reminders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL,
  reminder_date DATE NOT NULL,
  reminder_time TIME NOT NULL,
  category ENUM('Personal','Health','Work','Payments','Family') NOT NULL DEFAULT 'Personal',
  priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  notes VARCHAR(1000) NOT NULL DEFAULT '',
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reminders_user_schedule (user_id, reminder_date, reminder_time),
  CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
