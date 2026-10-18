-- Run this once in phpMyAdmin (skill_connect database -> SQL tab) if needed.
-- Note: helpers.php also auto-repairs and creates these tables automatically on page load!

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

DROP TABLE IF EXISTS saved_announcements;
CREATE TABLE saved_announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_saved_announcement (announcement_id, user_name)
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

DROP TABLE IF EXISTS peer_sessions;
CREATE TABLE peer_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  host_name VARCHAR(100) NOT NULL,
  title VARCHAR(180) NOT NULL,
  skill_topic VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  session_date DATE NOT NULL,
  session_time VARCHAR(30) NOT NULL,
  duration_mins INT NOT NULL DEFAULT 60,
  mode VARCHAR(20) NOT NULL DEFAULT 'Online',
  location_link VARCHAR(400) NOT NULL,
  max_seats INT NOT NULL DEFAULT 20,
  status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS peer_session_participants;
CREATE TABLE peer_session_participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_session_participant (session_id, user_name)
);
