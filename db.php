<?php
// db.php - Centralized database connection for Vybe (GitHub Secure Version)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard XAMPP defaults (Safe for GitHub)
$db_host = 'localhost';
$db_name = 'chat_app';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty. Enter your password here if needed locally.

try {
    // Create PDO connection with standard robust parameters
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Elegant fallback: If connection works but database doesn't exist, tell user to run setup
    try {
        $test_pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        die("<div style='font-family:sans-serif; text-align:center; padding: 50px;'>
                <h2 style='color:#ef4444;'>Database 'chat_app' not found!</h2>
                <p>Please run the auto-installer to set up the database and mock data.</p>
                <a href='setup.php' style='display:inline-block; background:#3b82f6; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold; margin-top:20px;'>Run Database Setup</a>
             </div>");
    } catch (PDOException $ex) {
        die("<div style='font-family:sans-serif; text-align:center; padding: 50px;'>
                <h2 style='color:#ef4444;'>Database Connection Failed!</h2>
                <p>Error: Make sure your MySQL server is running in the XAMPP Control Panel.</p>
             </div>");
    }
}
?>
