<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'booking_system';

$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error){
    die('Database Connection Failed: ' . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper function to check if user is therapist
function isTherapist() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'therapist';
}

// Helper function to get current user data
function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>