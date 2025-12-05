<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// --- Handle Add Staff Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $specialization = trim($_POST['specialization']);
    
    // Basic validation
    if (empty($full_name) || empty($email) || empty($password) || empty($specialization)) {
        $message = "All fields are required.";
        $message_type = 'danger';
    } else {
        // Start transaction for atomicity
        $conn->begin_transaction();
        
        try {
            // 1. Check if user already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                throw new Exception("A user with this email already exists.");
            }
            $stmt->close();
            
            // 2. Insert into users table as 'staff'
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'staff')");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating user account: " . $stmt->error);
            }
            $user_id = $conn->insert_id;
            $stmt->close();
            
            // 3. Insert into staff table
            $stmt = $conn->prepare("INSERT INTO staff (user_id, specialization) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $specialization);
            
            if (!$stmt->execute()) {
                throw new Exception("Error adding staff details: " . $stmt->error);
            }
            $stmt->close();
            
            $conn->commit();
            $message = "New staff member, " . htmlspecialchars($full_name) . ", added successfully!";
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Failed to add staff: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// --- Fetch all staff members ---
$staff_query = $conn->query("
    SELECT 
        st.staff_id, 
        st.specialization, 
        u.full_name, 
        u.email,
        u.created_at
    FROM staff st
    JOIN users u ON st.user_id = u.user_id
    ORDER BY u.full_name ASC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Serenity Spa Admin</title>
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
            min-height: 100vh; /* Set to 100vh for a full-height sidebar */
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
        
        .table-custom th {
            background: var(--light-yellow);
            color: var(--text-dark);
            font-weight: 600;
            border: none;
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
            <!-- Sidebar (Duplicated from admin_dashboard for consistency) -->
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
                <h2 class="mb-4"><i class="fas fa-user-tie"></i> Staff Management</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Staff Addition Form -->
                <div class="content-card mb-4">
                    <h4 class="mb-3">Add New Staff Member</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email Address (Login)</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Temporary Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="specialization" class="form-label">Specialization/Role</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" placeholder="e.g., Massage Therapist, Esthetician, Nail Technician" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-plus"></i> Add Staff
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Staff List -->
                <div class="content-card">
                    <h4 class="mb-4">Current Staff Roster (<?= $staff_query->num_rows ?>)</h4>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email (Login)</th>
                                    <th>Specialization</th>
                                    <th>Member Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($staff_query->num_rows > 0): ?>
                                    <?php while ($staff = $staff_query->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $staff['staff_id'] ?></td>
                                        <td><?= htmlspecialchars($staff['full_name']) ?></td>
                                        <td><?= htmlspecialchars($staff['email']) ?></td>
                                        <td><?= htmlspecialchars($staff['specialization']) ?></td>
                                        <td><?= date('M j, Y', strtotime($staff['created_at'])) ?></td>
                                        <td>
                                            <!-- Action buttons like Edit/Delete would go here -->
                                            <button class="btn btn-sm btn-outline-danger" title="Delete Staff (Not yet implemented)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No staff members found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>