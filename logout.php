<?php
// Start session
if (session_status() === PHP_SESSION_NONE) { //make sure session is active (PHP_SESSION_NONE)= no session started , PHP_SESSION_ACTIVE=session is active.
    session_start();
}

// Unset all session variables
$_SESSION = array(); // used to remove all session data

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) { //ini_get()= Reads PHP configuration.
    $params = session_get_cookie_params(); //get cookie session configuration that returns an array containing session cookie settings like path, domain, secure, httponly. This is used to ensure the cookie is deleted correctly.
    setcookie(session_name(), '', time() - 42000, //mainly for the cookie expires Current time minus 42000 seconds.
        $params["path"], $params["domain"],//This tells where the cookie is valid inside the website./chat then cookie works only inside: mysite.com/chat not: mysite.com/login In logout.php Usually: $params["path"] = "/"
        // $params["domain"] - This tells which domain can use the cookie.
        $params["secure"], $params["httponly"]
        // Cookie sent only over: https:// only not the http 
        // Another security option.Controls:Can JavaScript access this cookie?
        // true JS cannot read cookie.Example: This fails: document.cookie for that session cookie. Good for security.
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
