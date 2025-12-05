<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

// Get statistics
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
// Assuming staff are also users, but we still count only 'customers' for customer count
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'paid'")->fetch_assoc()['total'] ?? 0;
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];

// --- UPDATED RECENT APPOINTMENTS QUERY ---
// The old query failed because 'appointments.therapist_id' no longer exists.
// We now join on the new 'appointments.staff_id' which links to the 'staff' table.
// We then join from 'staff' to 'users' (u2) to get the staff's name.
$recent_appointments = $conn->query("
    SELECT 
        a.*, 
        s.service_name, 
        u1.full_name as customer_name, 
        u2.full_name as staff_name -- Now fetching the staff member's name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u1 ON a.user_id = u1.user_id
    JOIN staff st ON a.staff_id = st.staff_id -- JOIN staff table first
    JOIN users u2 ON st.user_id = u2.user_id -- Then join staff to the users table (u2) to get the name
    ORDER BY a.created_at DESC
    LIMIT 10
");
// ----------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Serenity Spa</title>
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
            min-height: calc(100vh - 76px);
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
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-yellow);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        
        .table-custom {
            margin-bottom: 0;
        }
        
        .table-custom th {
            background: var(--light-yellow);
            color: var(--text-dark);
            font-weight: 600;
            border: none;
        }
        
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
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
                    <a href="admin_dashboard.php" class="sidebar-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="admin_appointments.php" class="sidebar-link">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="admin_services.php" class="sidebar-link">
                        <i class="fas fa-spa"></i> Services
                    </a>
                    <!-- NEW LINK: Categories is essential since you created the table -->
                    <a href="admin_categories.php" class="sidebar-link">
                        <i class="fas fa-sitemap"></i> Categories
                    </a>
                    <!-- UPDATED LINK: Renamed from Therapists to Staff to reflect the new table name -->
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
                <h2 class="mb-4">Dashboard Overview</h2>
                
                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-value"><?= $total_appointments ?></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-calendar"></i> All time
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="border-left-color: var(--earth-brown);">
                            <div class="stat-label">Total Customers</div>
                            <div class="stat-value"><?= $total_customers ?></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-users"></i> Registered
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="border-left-color: #28a745;">
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">₱<?= number_format($total_revenue, 2) ?></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-money-bill"></i> Paid
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="border-left-color: #ffc107;">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value"><?= $pending_appointments ?></div>
                            <div class="text-muted small mt-2">
                                <i class="fas fa-clock"></i> Awaiting confirmation
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Appointments -->
                <div class="content-card">
                    <h4 class="mb-4">Recent Appointments</h4>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Staff</th> <!-- UPDATED COLUMN HEADER -->
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($apt = $recent_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $apt['appointment_id'] ?></td>
                                    <td><?= htmlspecialchars($apt['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($apt['service_name']) ?></td>
                                    <td><?= htmlspecialchars($apt['staff_name']) ?></td> <!-- UPDATED VARIABLE -->
                                    <td><?= date('M j, Y', strtotime($apt['appointment_date'])) ?></td>
                                    <td><?= date('g:i A', strtotime($apt['start_time'])) ?></td>
                                    <td>
                                        <span class="badge badge-status bg-<?= 
                                            $apt['status'] === 'pending' ? 'warning' : 
                                            ($apt['status'] === 'confirmed' ? 'info' : 
                                            ($apt['status'] === 'completed' ? 'success' : 'danger'))
                                        ?>">
                                            <?= ucfirst($apt['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_appointment_view.php?id=<?= $apt['appointment_id'] ?>" 
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="admin_appointments.php" class="btn" style="background: var(--primary-yellow);">
                            View All Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>