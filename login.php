<?php
// Include centralized database connection
require_once 'db.php';

// Redirect to chat if already logged in
if (isset($_SESSION['user_id'])) { //Does this variable exist and is it not null?
    $redirect_query = ''; //Creates an empty string variable.Because maybe URL has parameters, maybe not.
    // Creates empty string.Used to preserve URL parameters.
    if (!empty($_SERVER['QUERY_STRING'])) { //Everything after ? in URL.
        $redirect_query = '?' . $_SERVER['QUERY_STRING']; //This means:If query exists: Add: ? before it.
    }
    header('Location: index.php' . $redirect_query); //Location: index.php?redirect=chat
    exit;
}

$error = '';
$success = '';

// Check if redirected from registration with success
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success']; //copies the success message from the session to the $success variable, which is used to display the message on the login page.
    unset($_SESSION['reg_success']);//deletes the session messages so that it doesn't show again on page refresh or if the user navigates back to the login page without registering again.
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? ''); // trim=remove spaces and if the username is not set, it will default to an empty string.
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Both username and password are required.';
    } else {
        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?"); //stmt=statement mainly used to stored the prepared query.
        $stmt->execute([$username]); //SELECT * FROM users WHERE username='john'
        $user = $stmt->fetch(); //gets a single row.    
        
        if ($user && password_verify($password, $user['password'])) { // $users = did users exist amd password_verify() = entered password matches stored hash passwords.
        //$password==$user['password'] - the password should be hashed.
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // Regenerate session ID for security where old ID destroyed and new secure one created.
            session_regenerate_id(true); 
            
            //redirect after login to index.php
            $redirect_query = ''; //Keep URL parameters.
            if (!empty($_SERVER['QUERY_STRING'])) {
                $redirect_query = '?' . $_SERVER['QUERY_STRING'];
            }
            header('Location: index.php' . $redirect_query);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In – Vybe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=6.0">
</head>
<body class="auth-body">
    <div class="auth-overlay"></div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <div class="logo-animation-wrap main-logo">
                    <svg class="animated-logo-svg" viewBox="0 0 102 82" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logo-grad-auth" x1="0%" y1="100%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#00f5a0" />
                                <stop offset="100%" stop-color="#00d9f9" />
                            </linearGradient>
                            <filter id="neon-glow-auth" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="3" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                        </defs>
                        <g filter="url(#neon-glow-auth)" fill="none" stroke="url(#logo-grad-auth)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M 38,20 C 20,20 8,30 8,42.5 C 8,49.5 12.5,55.5 19,59 L 16,70 L 29,64 C 32,64.5 35,65 38,65 C 56,65 68,55 68,42.5 C 68,30 56,20 38,20 Z" />
                            <circle cx="26" cy="42.5" r="3.5" fill="url(#logo-grad-auth)" stroke="none" />
                            <circle cx="38" cy="42.5" r="3.5" fill="url(#logo-grad-auth)" stroke="none" />
                            <circle cx="50" cy="42.5" r="3.5" fill="url(#logo-grad-auth)" stroke="none" />
                            <path d="M 64,36 C 64,33 61.5,30.5 58,29 C 61,25 66.5,22 73.5,22 C 86,22 94,29 94,38.5 C 94,43.5 90.5,47.5 85.5,50 L 88,58 L 78.5,53.5 C 76.5,54 75,54 73.5,54 C 69.5,54 66,52.5 63.5,50" opacity="0.85" />
                            <circle cx="73.5" cy="38" r="3.5" fill="url(#logo-grad-auth)" stroke="none" opacity="0.85" />
                        </g>
                    </svg>
                </div>
                <h2>Welcome Back</h2>
                <p>Log in and catch the vybee ⚡</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="bx bx-check-circle alert-icon"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
             <!-- used to end the if block doesnt required any parathesis -->
             
            <?php if (!empty($error)): ?> <!-- empty() checks: Is variable empty?  means:Error is NOT empty.or:Error message exists.-->
                <div class="alert alert-danger">
                    <i class="bx bx-error-circle alert-icon"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" method="POST" class="auth-form" autocomplete="off"> <!-- stops the browser from auto-filling the form -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-user"></i>
                        </span>
                        <input type="text" id="username" name="username" placeholder="johndoe" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                             <i class="bx bx-lock-alt"></i>  <!--This is a Boxicons icon. -->
                        </span>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">Sign Up</a></p>
            </div>
        </div>
    </div>
</body>
</html>
