<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

// 1. Fetch Users (Clients)
// NOTE: Assuming your client users have the role 'client' or 'user'
$users_result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'user' ORDER BY full_name ASC");

$users = [];
if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// 2. Fetch Services
$services_result = $conn->query("SELECT service_id, name FROM services ORDER BY name ASC");

$services = [];
if ($services_result && $services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// 3. Handle POST Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $service_id = intval($_POST['service_id']);
    $booking_date = trim($_POST['booking_date']);
    $staff_id = intval($_POST['staff_id']); // Include staff assignment
    $status = trim($_POST['status']);
    
    // Basic validation
    if ($user_id > 0 && $service_id > 0 && !empty($booking_date) && !empty($status)) {
        
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, service_id, staff_id, booking_date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $service_id, $staff_id, $booking_date, $status);
        
        if ($stmt->execute()) {
            $success = "Booking successfully created and assigned.";
            // Clear form data after success or redirect
            // header("Location: admin_appointments.php");
            // exit;
        } else {
            $error = "Error adding booking: " . $conn->error;
        }
        $stmt->close();

    } else {
        $error = "Please fill out all required fields.";
    }
}

// 4. Fetch Staff (for assignment dropdown)
$staff_result = $conn->query("SELECT staff_id, full_name FROM users u JOIN staff s ON u.user_id = s.user_id ORDER BY full_name ASC");

$staff_members = [];
if ($staff_result && $staff_result->num_rows > 0) {
    while ($row = $staff_result->fetch_assoc()) {
        $staff_members[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Booking - Serenity Spa Admin</title>
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
                    <a href="admin_appointments.php" class="sidebar-link active">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="admin_services.php" class="sidebar-link">
                        <i class="fas fa-spa"></i> Services
                    </a>
                    <a href="admin_categories.php" class="sidebar-link">
                        <i class="fas fa-sitemap"></i> Categories
                    </a>
                    <a href="admin_staff.php" class="sidebar-link"> 
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
                <h2 class="mb-4"><i class="fas fa-calendar-plus"></i> Add New Booking</h2>
                
                <div class="content-card">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">Client User:</label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">-- Select Client --</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($users)): ?><small class="text-danger">No client users found.</small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="service_id" class="form-label">Service:</label>
                                <select name="service_id" id="service_id" class="form-select" required>
                                    <option value="">-- Select Service --</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= $s['service_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($services)): ?><small class="text-danger">No services found.</small><?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="booking_date" class="form-label">Date & Time:</label>
                                <input type="datetime-local" name="booking_date" id="booking_date" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="staff_id" class="form-label">Assign Staff Member:</label>
                                <select name="staff_id" id="staff_id" class="form-select" required>
                                    <option value="">-- Select Staff --</option>
                                    <?php foreach ($staff_members as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>"><?= htmlspecialchars($staff['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($staff_members)): ?><small class="text-danger">No staff members found.</small><?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="status" class="form-label">Status:</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save"></i> Save Booking
                        </button>
                        <a href="admin_appointments.php" class="btn btn-secondary">
                            <i class="fas fa-calendar"></i> View Appointments
                        </a>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>