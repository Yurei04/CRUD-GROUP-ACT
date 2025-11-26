<?php
require 'config.php';

// New password
$new_password = 'Password1234'; // <-- CHANGE THIS
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hashed);

if ($stmt->execute()) {
    echo "Admin password has been updated successfully!";
} else {
    echo "Error updating password.";
}
