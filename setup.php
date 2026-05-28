<?php
// setup.php - Database Auto-Setup Installer and Mock Data Seeder (GitHub Secure Version)
header('Content-Type: text/html; charset=utf-8');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password (Safe for GitHub)

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Vybe – Database Setup</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #0b0c10; color: #c5c6c7; padding: 40px; margin: 0; line-height: 1.6; }
        .card { background-color: #1f2833; padding: 30px; border-radius: 12px; max-width: 700px; margin: 0 auto; box-shadow: 0 8px 30px rgba(0,0,0,0.5); border: 1px solid #45f3ff33; }
        h1 { color: #66fcf1; border-bottom: 2px solid #66fcf1; padding-bottom: 10px; font-weight: 600; margin-top: 0; }
        .step { margin-bottom: 15px; padding-left: 24px; position: relative; }
        .step::before { content: '⚡'; position: absolute; left: 0; top: 0; color: #45f3ff; }
        .success { color: #2ecc71; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; padding: 15px; background: rgba(231,76,60,0.1); border-radius: 6px; border-left: 4px solid #e74c3c; }
        .btn { display: inline-block; background: linear-gradient(135deg, #00f5a0 0%, #00d9f9 100%); color: #0b0c10; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 25px; box-shadow: 0 4px 15px rgba(0, 245, 160, 0.2); transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0, 217, 249, 0.4); }
    </style>
</head>
<body>
<div class='card'>
    <h1>Vybe Database Installer</h1>";

try {
    // Connect to local MySQL
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<div class='step'>Connecting to MySQL Server... <span class='success'>SUCCESS</span></div>";

    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS chat_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='step'>Creating database 'chat_app'... <span class='success'>SUCCESS</span></div>";

    // 3. Connect to the created database
    $pdo->exec("USE chat_app");
    echo "<div class='step'>Switching to database 'chat_app'... <span class='success'>SUCCESS</span></div>";

    // 4. Create Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step'>Creating 'users' table... <span class='success'>SUCCESS</span></div>";

    // 5. Create Rooms Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step'>Creating 'rooms' (group chat) table... <span class='success'>SUCCESS</span></div>";

    // 6. Create Messages Table (with support for receiver_id OR room_id)
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        receiver_id INT DEFAULT NULL,
        room_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<div class='step'>Creating 'messages' table with group-room integration... <span class='success'>SUCCESS</span></div>";

    // 7. Seed Mock Data
    echo "<h2>Seeding Database with Premium Demo Data...</h2>";

    // Truncate tables first (in correct dependency order)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE messages;");
    $pdo->exec("TRUNCATE TABLE rooms;");
    $pdo->exec("TRUNCATE TABLE users;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Insert dummy users (passwords are 'password123')
    $users_data = [
        ['alex', 'alex@vybe.com', '+1 (555) 123-4567', password_hash('password123', PASSWORD_DEFAULT)],
        ['emma', 'emma@vybe.com', '+1 (555) 234-5678', password_hash('password123', PASSWORD_DEFAULT)],
        ['liam', 'liam@vybe.com', '+1 (555) 345-6789', password_hash('password123', PASSWORD_DEFAULT)],
        ['sophia', 'sophia@vybe.com', '+1 (555) 456-7890', password_hash('password123', PASSWORD_DEFAULT)]
    ];

    $stmt_user = $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
    foreach ($users_data as $u) {
        $stmt_user->execute($u);
    }
    echo "<div class='step'>Seeding mock users (alex, emma, liam, sophia)... <span class='success'>SUCCESS</span></div>";

    // Fetch user IDs
    $user_ids = [];
    $stmt = $pdo->query("SELECT id, username FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_ids[$row['username']] = $row['id'];
    }

    // Insert dummy rooms
    $rooms_data = [
        ['Study Lounge', $user_ids['alex']],
        ['Gaming Hub', $user_ids['liam']],
        ['Vybe Talk', $user_ids['emma']]
    ];

    $stmt_room = $pdo->prepare("INSERT INTO rooms (name, created_by) VALUES (?, ?)");
    foreach ($rooms_data as $r) {
        $stmt_room->execute($r);
    }
    echo "<div class='step'>Seeding group rooms (Study Lounge, Gaming Hub, Vybe Talk)... <span class='success'>SUCCESS</span></div>";

    // Fetch room IDs
    $room_ids = [];
    $stmt = $pdo->query("SELECT id, name FROM rooms");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $room_ids[$row['name']] = $row['id'];
    }

    // Insert dummy messages (Direct and Group)
    $messages_data = [
        // Direct messages (1-on-1)
        [$user_ids['alex'], $user_ids['emma'], null, 'Hey Emma! Have you checked out the new Vybe chat interface?', '2026-05-28 10:00:00'],
        [$user_ids['emma'], $user_ids['alex'], null, 'Hi Alex! Yes, it looks absolutely stunning! The dark glassmorphism styling is beautiful.', '2026-05-28 10:01:30'],
        [$user_ids['alex'], $user_ids['emma'], null, 'Exactly! And it updates completely in real-time.', '2026-05-28 10:02:15'],
        [$user_ids['emma'], $user_ids['alex'], null, 'Perfect, let\'s test out the deletion feature. Delete one of your messages!', '2026-05-28 10:03:00'],
        
        // Group messages in Study Lounge
        [$user_ids['alex'], null, $room_ids['Study Lounge'], 'Welcome everyone to the Study Lounge! 📚', '2026-05-28 11:00:00'],
        [$user_ids['liam'], null, $room_ids['Study Lounge'], 'Hey guys! Ready to study some advanced computer science topics today?', '2026-05-28 11:01:22'],
        [$user_ids['sophia'], null, $room_ids['Study Lounge'], 'Hi Study Group! I will join in about 15 minutes.', '2026-05-28 11:02:10'],
        [$user_ids['emma'], null, $room_ids['Study Lounge'], 'Count me in too! I am finishing some research.', '2026-05-28 11:03:05'],

        // Group messages in Gaming Hub
        [$user_ids['liam'], null, $room_ids['Gaming Hub'], 'Any games scheduled tonight? 🎮', '2026-05-28 12:00:00'],
        [$user_ids['alex'], null, $room_ids['Gaming Hub'], 'Definitely! I am down for some co-op after 8 PM.', '2026-05-28 12:01:10']
    ];

    $stmt_msg = $pdo->prepare("INSERT INTO messages (user_id, receiver_id, room_id, message, created_at) VALUES (?, ?, ?, ?, ?)");
    foreach ($messages_data as $m) {
        $stmt_msg->execute($m);
    }
    echo "<div class='step'>Seeding mock direct and group messages... <span class='success'>SUCCESS</span></div>";

    echo "<h2 style='color:#00f5a0; margin-top:25px;'>Database Installation Complete!</h2>
          <p>Mock Credentials for Testing (Password: <b>password123</b>):</p>
          <ul>
            <li>Username: <b>alex</b></li>
            <li>Username: <b>emma</b></li>
            <li>Username: <b>liam</b></li>
            <li>Username: <b>sophia</b></li>
          </ul>
          <a href='index.php' class='btn'>Launch Vybe Chat</a>";

} catch (PDOException $e) {
    echo "<div class='error'>
            <h3>Installation Failed!</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please check your local MySQL connection settings. Make sure MySQL is running in your XAMPP Control Panel.</p>
          </div>";
}

echo "</div>
</body>
</html>";
?>
