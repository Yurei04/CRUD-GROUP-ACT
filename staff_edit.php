<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

$staff_id = $_GET['id'] ?? null;
$message = '';
$message_type = '';

// 1. Fetch current staff data
if ($staff_id) {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.full_name, 
            u.email, 
            st.specialization
        FROM users u
        JOIN staff st ON u.user_id = st.user_id
        WHERE st.staff_id = ?
    ");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();

    if (!$staff) {
        $message = "Staff member not found.";
        $message_type = 'danger';
        $staff_id = null; // Prevent update attempts
    }
} else {
    $message = "Invalid staff ID provided.";
    $message_type = 'danger';
}

// 2. Handle Update Submission
if ($staff_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_staff') {
    $user_id = $staff['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? ''; // Optional password
    $specialization = trim($_POST['specialization']);

    // Basic validation
    if (empty($full_name) || empty($email) || empty($specialization)) {
        $message = "Name, email, and specialization are required.";
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        
        try {
            // Check if new email already exists for another user
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                throw new Exception("This email is already registered to another user.");
            }
            $stmt->close();
            
            // A. Update users table
            $update_user_sql = "UPDATE users SET full_name = ?, email = ?";
            $params = [$full_name, $email];
            $types = "ss";

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_user_sql .= ", password = ?";
                $params[] = $hashed_password;
                $types .= "s";
            }
            
            $update_user_sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($update_user_sql);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Error updating user account: " . $stmt->error);
            }
            $stmt->close();
            
            // B. Update staff table
            $stmt = $conn->prepare("UPDATE staff SET specialization = ? WHERE user_id = ?");
            $stmt->bind_param("si", $specialization, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating staff details: " . $stmt->error);
            }
            $stmt->close();
            
            $conn->commit();
            $message = "Staff member details updated successfully!";
            $message_type = 'success';
            
            // Re-fetch data to show the updated values in the form
            $staff['full_name'] = $full_name;
            $staff['email'] = $email;
            $staff['specialization'] = $specialization;

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to update staff: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Redirect if staff not found after initial load
if ($staff_id && !$staff) {
    header("Location: admin_staff.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - Serenity Spa Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-yellow: #F5C542;
            --secondary-yellow: #FFD966;
            --dark-yellow: #D4A428;
            --light-yellow: #FFF9E6;
            --earth-brown: #8B7355;
            --text-dark: #2C2C2C;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--earth-brown) 0%, var(--text-dark) 100%);
            padding: 1rem 0;
        }
        
        .navbar-brand, .nav-link {
            color: white !important;
        }
        
        .sidebar {
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-link {
            padding: 1rem 1.5rem;
            color: var(--text-dark);
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover {
            background: var(--light-yellow);
            border-left-color: var(--primary-yellow);
        }
        
        .sidebar-link.active {
            background: var(--light-yellow);
            border-left-color: var(--primary-yellow);
            font-weight: 600;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-yellow);
            border-color: var(--primary-yellow);
            color: var(--text-dark);
            transition: background-color 0.3s;
        }
        .btn-primary-custom:hover {
            background-color: var(--dark-yellow);
            border-color: var(--dark-yellow);
            color: var(--text-dark);
        }
        .btn-outline-secondary-custom {
            border-color: var(--earth-brown);
            color: var(--earth-brown);
            transition: all 0.3s;
        }
        .btn-outline-secondary-custom:hover {
            background-color: var(--earth-brown);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-spa"></i> Serenity Spa - Admin
            </a>
            <div class="ms-auto">
                <span class="text-white me-3">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['full_name']) ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <a href="admin_dashboard.php" class="sidebar-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="admin_appointments.php" class="sidebar-link">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="admin_services.php" class="sidebar-link">
                        <i class="fas fa-spa"></i> Services
                    </a>
                    <a href="admin_categories.php" class="sidebar-link">
                        <i class="fas fa-sitemap"></i> Categories
                    </a>
                    <a href="admin_staff.php" class="sidebar-link active"> 
                        <i class="fas fa-user-tie"></i> Staff
                    </a>
                    <a href="admin_users.php" class="sidebar-link">
                        <i class="fas fa-users"></i> Users (Clients)
                    </a>
                    <a href="admin_payments.php" class="sidebar-link">
                        <i class="fas fa-money-bill"></i> Payments
                    </a>
                    <a href="admin_reviews.php" class="sidebar-link">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                    <a href="admin_promotions.php" class="sidebar-link">
                        <i class="fas fa-tags"></i> Promotions
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">
                    <i class="fas fa-edit"></i> Edit Staff Member 
                    <?php if ($staff_id && $staff): ?>
                        <small class="text-muted fs-5">(#<?= htmlspecialchars($staff_id) ?> - <?= htmlspecialchars($staff['full_name']) ?>)</small>
                    <?php endif; ?>
                </h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($staff_id && $staff): ?>
                    <!-- Staff Editing Form -->
                    <div class="content-card mb-4">
                        <h4 class="mb-3">Update Details</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_staff">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($staff['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address (Login)</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="specialization" class="form-label">Specialization/Role</label>
                                    <input type="text" class="form-control" id="specialization" name="specialization" value="<?= htmlspecialchars($staff['specialization']) ?>" placeholder="e.g., Massage Therapist, Esthetician" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">New Password (Leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                                </div>
                                
                                <div class="col-12 mt-4 d-flex justify-content-between">
                                    <a href="admin_staff.php" class="btn btn-outline-secondary-custom">
                                        <i class="fas fa-arrow-left"></i> Back to Staff List
                                    </a>
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>