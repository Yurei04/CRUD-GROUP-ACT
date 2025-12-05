<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

$staff_id = $_GET['id'] ?? null;

if (!$staff_id) {
    // Redirect back if no ID is provided
    header("Location: admin_staff.php?message=" . urlencode("Invalid staff ID provided for deletion.") . "&type=danger");
    exit;
}

// Start transaction for atomicity
$conn->begin_transaction();

try {
    // 1. Fetch the user_id associated with the staff_id
    // This is crucial because staff details are spread across two tables (users and staff)
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.full_name 
        FROM users u 
        JOIN staff st ON u.user_id = st.user_id 
        WHERE st.staff_id = ?
    ");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff_data = $result->fetch_assoc();
    $stmt->close();

    if (!$staff_data) {
        throw new Exception("Staff member not found or already deleted.");
    }
    
    $user_id = $staff_data['user_id'];
    $full_name = $staff_data['full_name'];

    // 2. Delete from the 'staff' table (foreign key constraint might require this first)
    $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    if (!$stmt->execute()) {
        throw new Exception("Error deleting staff details: " . $stmt->error);
    }
    $stmt->close();
    
    // 3. Delete from the 'users' table (the actual login account)
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error deleting user account: " . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    $redirect_message = urlencode("Staff member '" . htmlspecialchars($full_name) . "' has been successfully deleted.");
    $redirect_type = 'success';
    
} catch (Exception $e) {
    $conn->rollback();
    $redirect_message = urlencode("Failed to delete staff member: " . $e->getMessage());
    $redirect_type = 'danger';
}

// Redirect back to the staff list page
header("Location: admin_staff.php?message={$redirect_message}&type={$redirect_type}");
exit;

?>