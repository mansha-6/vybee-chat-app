<?php
// Include centralized database connection
require_once 'db.php';

// Redirect to chat if already logged in
if (isset($_SESSION['user_id'])) {
    $redirect_query = '';
    // Creates empty string.Used to preserve URL parameters.
    if (!empty($_SERVER['QUERY_STRING'])) { //Means:URL part after: ? example: register.php?theme=dark then Query string:theme=dark
        $redirect_query = '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: index.php' . $redirect_query);
    exit;
}

$error = ''; // intial empty but later $error='Email exists';
$success = '';
// For alert messages.Initially empty.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); //if username missing then use empty string
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Server-side validation
    if (empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //used to check valid email
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 2 || strlen($username) > 50) {
        $error = 'Username must be between 2 and 50 characters.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username, email, or phone number already exists
        $stmt = $pdo->prepare("SELECT id, username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $email, $phone]);
        $existing = $stmt->fetch();
        if ($existing) {
            if ($existing['username'] === $username) {
                $error = 'Username is already registered. Please log in.';
            } elseif ($existing['email'] === $email) {
                $error = 'Email Address is already registered. Please log in.';
            } else {
                $error = 'Phone Number is already registered. Please log in.';
            }
        } else {
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $phone, $hashed_password]);
            
            $_SESSION['reg_success'] = 'Registration successful! You can now log in.';
            $redirect_query = '';
            if (!empty($_SERVER['QUERY_STRING'])) {
                $redirect_query = '?' . $_SERVER['QUERY_STRING'];
            }
            header('Location: login.php' . $redirect_query);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up – Vybe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=9.0">
</head>
<body class="auth-body">
    <div class="auth-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-animation-wrap main-logo">
                    <svg class="animated-logo-svg" viewBox="0 0 102 82" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logo-grad-reg" x1="0%" y1="100%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#00f5a0" />
                                <stop offset="100%" stop-color="#00d9f9" />
                            </linearGradient>
                            <filter id="neon-glow-reg" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <g filter="url(#neon-glow-reg)" fill="none" stroke="url(#logo-grad-reg)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M 38,20 C 20,20 8,30 8,42.5 C 8,49.5 12.5,55.5 19,59 L 16,70 L 29,64 C 32,64.5 35,65 38,65 C 56,65 68,55 68,42.5 C 68,30 56,20 38,20 Z" />
                            <circle cx="26" cy="42.5" r="3.5" fill="url(#logo-grad-reg)" stroke="none" />
                            <circle cx="38" cy="42.5" r="3.5" fill="url(#logo-grad-reg)" stroke="none" />
                            <circle cx="50" cy="42.5" r="3.5" fill="url(#logo-grad-reg)" stroke="none" />
                            <path d="M 64,36 C 64,33 61.5,30.5 58,29 C 61,25 66.5,22 73.5,22 C 86,22 94,29 94,38.5 C 94,43.5 90.5,47.5 85.5,50 L 88,58 L 78.5,53.5 C 76.5,54 75,54 73.5,54 C 69.5,54 66,52.5 63.5,50" opacity="0.85" />
                            <circle cx="73.5" cy="38" r="3.5" fill="url(#logo-grad-reg)" stroke="none" opacity="0.85" />
                        </g>
                    </svg>
                </div>
                <h2>Vybee</h2>
                <p>Create an account and start the vybee ⚡</p>
            </div>
            
            <?php if (!empty($error)): ?> <!--$error='Email exists'Then:HTML:alert-danger appears. -->
                <div class="alert alert-danger">
                    <i class="bx bx-error-circle alert-icon"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?> <!--end the if statement -->

            <form action="register.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" method="POST" class="auth-form" autocomplete="off"> <!--register.php?redirect=chat --> 
             <!--ternary operator If query exists → keep it Otherwise → empty string.--> 
            <!-- Don't auto-suggest saved values. for the autocomplete -->
            <!--htmlspecialchars() a built-in PHP tool that converts special characters into their corresponding HTML entities -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-user"></i>
                        </span>
                        <input type="text" id="username" name="username" placeholder="johndoe" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div> <!-- If username exists → show it Else → empty. -->
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" placeholder="john@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"> <!--ternary operator -->
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-phone"></i>
                        </span>
                        <input type="tel" id="phone" name="phone" placeholder="9876543210" required maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">  <!--ternary operator -->
                     </div> 
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-lock-alt"></i>
                        </span>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">Log In</a></p> <!--login.php?redirect=chat -->
            </div>
        </div>
    </div>
</body>
</html>
