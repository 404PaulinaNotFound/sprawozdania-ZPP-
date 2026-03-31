-- ============================================
-- PBF SYSTEM ZPP2 - KOMPLETNA BAZA DANYCH
-- ============================================

-- 1. SYSTEM KONT I UŻYTKOWNIKÓW
-- ============================================

CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('player', 'mg', 'admin') DEFAULT 'player',
  approved BOOLEAN DEFAULT FALSE,
  email_verified BOOLEAN DEFAULT FALSE,
  verification_token VARCHAR(64),
  reset_token VARCHAR(64),
  reset_token_expires DATETIME,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  avatar VARCHAR(255),
  bio TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_verification (verification_token),
  INDEX idx_reset (reset_token),
  INDEX idx_activity (last_activity)
);

-- 2. PROFILE POSTACI (RPG)
-- ============================================

CREATE TABLE characters (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  avatar VARCHAR(255),
  strength INT DEFAULT 10,
  agility INT DEFAULT 10,
  intelligence INT DEFAULT 10,
  charisma INT DEFAULT 10,
  vitality INT DEFAULT 10,
  experience INT DEFAULT 0,
  level INT DEFAULT 1,
  history_points INT DEFAULT 0,
  status ENUM('active', 'inactive', 'dead') DEFAULT 'active',
  approved_by_mg BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_status (status)
);

CREATE TABLE character_skills (
  id INT PRIMARY KEY AUTO_INCREMENT,
  character_id INT NOT NULL,
  skill_name VARCHAR(100) NOT NULL,
  skill_description TEXT,
  skill_level INT DEFAULT 1,
  approved BOOLEAN DEFAULT FALSE,
  approved_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id),
  INDEX idx_character (character_id)
);

CREATE TABLE character_inventory (
  id INT PRIMARY KEY AUTO_INCREMENT,
  character_id INT NOT NULL,
  item_name VARCHAR(100) NOT NULL,
  item_description TEXT,
  quantity INT DEFAULT 1,
  equipped BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
  INDEX idx_character (character_id)
);

-- 3. FORUM I STRUKTURA ROZGRYWKI
-- ============================================

CREATE TABLE categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  access_role ENUM('all', 'player', 'mg', 'admin') DEFAULT 'all',
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order (display_order)
);

CREATE TABLE forums (
  id INT PRIMARY KEY AUTO_INCREMENT,
  category_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  access_role ENUM('all', 'player', 'mg', 'admin') DEFAULT 'all',
  archived BOOLEAN DEFAULT FALSE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  INDEX idx_category (category_id),
  INDEX idx_archived (archived)
);

CREATE TABLE threads (
  id INT PRIMARY KEY AUTO_INCREMENT,
  forum_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  author_id INT NOT NULL,
  pinned BOOLEAN DEFAULT FALSE,
  locked BOOLEAN DEFAULT FALSE,
  archived BOOLEAN DEFAULT FALSE,
  view_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id),
  INDEX idx_forum (forum_id),
  INDEX idx_author (author_id),
  INDEX idx_updated (updated_at)
);

CREATE TABLE thread_tags (
  id INT PRIMARY KEY AUTO_INCREMENT,
  thread_id INT NOT NULL,
  tag_name VARCHAR(50) NOT NULL,
  tag_color VARCHAR(7) DEFAULT '#007bff',
  FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  INDEX idx_thread (thread_id)
);

CREATE TABLE posts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  thread_id INT NOT NULL,
  author_id INT NOT NULL,
  content TEXT NOT NULL,
  quoted_post_id INT,
  edited BOOLEAN DEFAULT FALSE,
  edit_reason VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
  FOREIGN KEY (author_id) REFERENCES users(id),
  FOREIGN KEY (quoted_post_id) REFERENCES posts(id) ON DELETE SET NULL,
  INDEX idx_thread (thread_id),
  INDEX idx_author (author_id)
);

ALTER TABLE threads ADD FULLTEXT INDEX ft_title (title);
ALTER TABLE posts ADD FULLTEXT INDEX ft_content (content);

-- 4. KOMUNIKACJA I MODERACJA
-- ============================================

CREATE TABLE private_messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sender_id INT NOT NULL,
  recipient_id INT NOT NULL,
  subject VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (recipient_id) REFERENCES users(id),
  INDEX idx_recipient (recipient_id),
  INDEX idx_sender (sender_id)
);

CREATE TABLE reports (
  id INT PRIMARY KEY AUTO_INCREMENT,
  reporter_id INT NOT NULL,
  reported_type ENUM('post', 'user', 'thread') NOT NULL,
  reported_id INT NOT NULL,
  reason TEXT NOT NULL,
  status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
  reviewed_by INT,
  resolution TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id),
  INDEX idx_status (status)
);

CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  type ENUM('reply', 'mention', 'pm', 'system', 'event') NOT NULL,
  title VARCHAR(200) NOT NULL,
  content TEXT,
  link VARCHAR(255),
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_read (read_at)
);

CREATE TABLE activity_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT,
  action_type ENUM('post_create', 'post_edit', 'post_delete', 'thread_create', 'user_login', 'user_register', 'character_create') NOT NULL,
  target_type VARCHAR(50),
  target_id INT,
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_action (action_type),
  INDEX idx_created (created_at)
);

-- 5. SYSTEM WYDARZEŃ ŚWIATOWYCH
-- ============================================

CREATE TABLE events (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  event_type ENUM('war', 'mission', 'tournament', 'festival', 'other') DEFAULT 'other',
  start_date DATETIME NOT NULL,
  end_date DATETIME,
  created_by INT NOT NULL,
  status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming',
  participant_limit INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_dates (start_date, end_date),
  INDEX idx_status (status)
);

CREATE TABLE event_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  event_id INT NOT NULL,
  character_id INT NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
  UNIQUE KEY unique_participation (event_id, character_id)
);

CREATE TABLE world_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  event_date DATETIME NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_event_date (event_date)
);

-- 6. MISJE I FABUŁA
-- ============================================

CREATE TABLE missions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  difficulty ENUM('easy', 'medium', 'hard', 'legendary') DEFAULT 'medium',
  reward_exp INT DEFAULT 0,
  reward_hp INT DEFAULT 0,
  created_by INT NOT NULL,
  status ENUM('proposed', 'approved', 'active', 'completed', 'failed') DEFAULT 'proposed',
  approved_by INT,
  max_participants INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id),
  INDEX idx_status (status)
);

CREATE TABLE mission_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  mission_id INT NOT NULL,
  character_id INT NOT NULL,
  contribution_score INT DEFAULT 0,
  completed BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
  FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
  UNIQUE KEY unique_mission_participant (mission_id, character_id)
);

-- 7. RANKING I NAGRODY
-- ============================================

CREATE TABLE achievements (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  icon VARCHAR(255),
  category ENUM('combat', 'social', 'exploration', 'special') DEFAULT 'special',
  points INT DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE character_achievements (
  id INT PRIMARY KEY AUTO_INCREMENT,
  character_id INT NOT NULL,
  achievement_id INT NOT NULL,
  earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE,
  FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
  UNIQUE KEY unique_achievement (character_id, achievement_id)
);

-- 8. DOKUMENTACJA I ZASOBY
-- ============================================

CREATE TABLE lore_pages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  category VARCHAR(100),
  created_by INT NOT NULL,
  is_public BOOLEAN DEFAULT TRUE,
  view_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_category (category)
);

CREATE TABLE faq_entries (
  id INT PRIMARY KEY AUTO_INCREMENT,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  category VARCHAR(100),
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order (display_order)
);

CREATE TABLE proposals (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  proposal_type ENUM('mechanic', 'story', 'event', 'other') DEFAULT 'other',
  status ENUM('pending', 'approved', 'rejected', 'implemented') DEFAULT 'pending',
  votes_up INT DEFAULT 0,
  votes_down INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_status (status)
);

-- DANE POCZĄTKOWE
INSERT INTO users (username, email, password, role, approved, email_verified) VALUES 
('admin', 'admin@pbf.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE),
('mg', 'mg@pbf.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mg', TRUE, TRUE),
('testplayer', 'player@pbf.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player', TRUE, TRUE);

INSERT INTO categories (title, description, access_role, display_order) VALUES
('Ogólne', 'Dyskusje ogólne o grze', 'all', 1),
('Fabuła', 'Wątki fabularne i rozgrywka', 'player', 2),
('Panel MG', 'Strefa tylko dla Mistrzów Gry', 'mg', 3),
('Administracja', 'Zarządzanie systemem', 'admin', 4);

INSERT INTO forums (category_id, title, description, access_role) VALUES
(1, 'Powitania', 'Przedstaw się społeczności', 'all'),
(1, 'Ogłoszenia', 'Ważne informacje o systemie', 'all'),
(2, 'Tawerny i miasta', 'Rozgrywka w lokacjach miejskich', 'player'),
(2, 'Dzicz i podziemia', 'Eksploracja niebezpiecznych terenów', 'player'),
(3, 'Zarządzanie fabułą', 'Narzędzia dla MG', 'mg');

INSERT INTO achievements (name, description, category, points) VALUES
('Pierwszy krok', 'Stwórz pierwszą postać', 'special', 10),
('Weteran', 'Osiągnij 10 poziom', 'combat', 50),
('Eksplorator', 'Weź udział w 5 misjach', 'exploration', 30),
('Towarzyski', 'Napisz 100 postów na forum', 'social', 25);

INSERT INTO faq_entries (question, answer, category, display_order) VALUES
('Jak stworzyć postać?', 'Przejdź do sekcji "Postacie" i kliknij "Nowa postać". Wypełnij formularz ze statystykami.', 'Podstawy', 1),
('Jak działa system doświadczenia?', 'Zdobywasz XP poprzez udział w misjach i wydarzeniach fabularnych.', 'Rozgrywka', 2),
('Co to są Punkty Historii?', 'PH to specjalna waluta pozwalająca wpływać na fabułę gry.', 'Rozgrywka', 3);
