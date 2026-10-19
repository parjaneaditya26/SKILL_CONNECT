-- Skill Connect: core tables.
-- Import this ONCE in phpMyAdmin (select your database -> Import, or SQL tab -> paste).
-- The other tables (announcements, help_posts, peer_sessions, ...) are created automatically by helpers.php.
-- If you already have these tables on your local XAMPP, you can instead export them from phpMyAdmin
-- (Export -> Structure only) and import that file on the server. Either way works.

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  mobile VARCHAR(20) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  branch VARCHAR(100) DEFAULT NULL,
  year_of_study VARCHAR(50) DEFAULT NULL,
  teach_skill TEXT,
  learn_skill TEXT,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  reset_token VARCHAR(64) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_name (name),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  requester_name VARCHAR(100) NOT NULL,
  target_id INT NOT NULL,
  target_name VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_requests_target (target_id),
  KEY idx_requests_requester (requester_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rater_name VARCHAR(100) NOT NULL,
  target_id INT NOT NULL,
  target_name VARCHAR(100) NOT NULL,
  rating INT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ratings_target (target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_name VARCHAR(100) NOT NULL,
  target_id INT NOT NULL,
  target_name VARCHAR(100) NOT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  details TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_name VARCHAR(100) NOT NULL,
  receiver_name VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_pair (sender_name, receiver_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
