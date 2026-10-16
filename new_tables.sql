-- Run this once in phpMyAdmin (skill_connect database -> SQL tab)

CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  category VARCHAR(30) NOT NULL DEFAULT 'General',
  link VARCHAR(500) DEFAULT NULL,
  deadline DATE DEFAULT NULL,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS announcement_interest (
  id INT AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_interest (announcement_id, user_name)
);

CREATE TABLE IF NOT EXISTS help_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(100) NOT NULL,
  type VARCHAR(20) NOT NULL,
  title VARCHAR(150) NOT NULL,
  details TEXT NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS help_responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  responder_name VARCHAR(100) NOT NULL,
  message VARCHAR(300) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_response (post_id, responder_name)
);
