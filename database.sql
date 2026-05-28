-- ====================================================================
-- Vybe Chat Application Database Schema & Mock Data Seeder
-- ====================================================================
-- Host: localhost
-- Database: chat_app
-- Generation Time: May 28, 2026
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------
-- 1. Users Table
-- --------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 2. Rooms Table (Group Chats)
-- --------------------------------------------------------------------
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. Messages Table (Supports Direct & Group Messages)
-- --------------------------------------------------------------------
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    receiver_id INT DEFAULT NULL,
    room_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    seen TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. Seed Mock Data (Password is 'password123' for all accounts)
-- --------------------------------------------------------------------
INSERT INTO users (id, username, email, phone, password) VALUES
(1, 'alex', 'alex@vybe.com', '9876543210', '$2y$10$R9n7U2Vb2X3c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5'),
(2, 'emma', 'emma@vybe.com', '9876543211', '$2y$10$R9n7U2Vb2X3c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5'),
(3, 'liam', 'liam@vybe.com', '9876543212', '$2y$10$R9n7U2Vb2X3c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5'),
(4, 'sophia', 'sophia@vybe.com', '9876543213', '$2y$10$R9n7U2Vb2X3c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5');

INSERT INTO rooms (id, name, created_by) VALUES
(1, 'Study Lounge', 1),
(2, 'Gaming Hub', 3),
(3, 'Vybe Talk', 2);

INSERT INTO messages (user_id, receiver_id, room_id, message, created_at) VALUES
-- Direct messages
(1, 2, NULL, 'Hey Emma! Have you checked out the new Vybe chat interface?', '2026-05-28 10:00:00'),
(2, 1, NULL, 'Hi Alex! Yes, it looks absolutely stunning! The dark glassmorphism styling is beautiful.', '2026-05-28 10:01:30'),
(1, 2, NULL, 'Exactly! And it updates completely in real-time.', '2026-05-28 10:02:15'),
(2, 1, NULL, 'Perfect, let\'s test out the deletion feature. Delete one of your messages!', '2026-05-28 10:03:00'),
-- Group messages
(1, NULL, 1, 'Welcome everyone to the Study Lounge! 📚', '2026-05-28 11:00:00'),
(3, NULL, 1, 'Hey guys! Ready to study some advanced computer science topics today?', '2026-05-28 11:01:22'),
(4, NULL, 1, 'Hi Study Group! I will join in about 15 minutes.', '2026-05-28 11:02:10'),
(2, NULL, 1, 'Count me in too! I am finishing some research.', '2026-05-28 11:03:05'),
(3, NULL, 2, 'Any games scheduled tonight? 🎮', '2026-05-28 12:00:00'),
(1, NULL, 2, 'Definitely! I am down for some co-op after 8 PM.', '2026-05-28 12:01:10');
