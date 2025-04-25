<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Delete user token from database if it exists
    if (isset($_SESSION['token'])) {
        $token = $_SESSION['token'];
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page with success message
    header("Location: login.php?message=You have been successfully logged out");
    exit;
} else {
    // If not logged in, just redirect to login page
    header("Location: login.php");
    exit;
}