<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch upcoming appointments
$upcoming = $conn->prepare("
    SELECT a.*, s.service_name, s.price, u.full_name as therapist_name, p.payment_status
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id
    WHERE a.user_id = ? AND a.status != 'canceled' AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.start_time ASC
");
$upcoming->bind_param("i", $user_id);
$upcoming->execute();
$upcoming_appointments = $upcoming->get_result();

// Fetch past appointments
$past = $conn->prepare("
    SELECT a.*, s.service_name, s.price, u.full_name as therapist_name, p.payment_status
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id
    WHERE a.user_id = ? AND (a.status = 'completed' OR a.appointment_date < CURDATE())
    ORDER BY a.appointment_date DESC, a.start_time DESC
    LIMIT 10
");
$past->bind_param("i", $user_id);
$past->execute();
$past_appointments = $past->get_result();

// Get user data
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Serenity Spa</title>
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
            background: linear-gradient(135deg, var(--primary-yellow) 0%, var(--secondary-yellow) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark) !important;
        }
        
        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 0.5rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-yellow) 0%, var(--secondary-yellow) 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .appointment-card {
            background: white;
            border-left: 4px solid var(--primary-yellow);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .appointment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(245, 197, 66, 0.2);
        }
        
        .appointment-card.pending {
            border-left-color: var(--primary-yellow);
        }
        
        .appointment-card.confirmed {
            border-left-color: var(--earth-brown);
        }
        
        .appointment-card.completed {
            border-left-color: #28a745;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: var(--light-yellow);
            color: var(--dark-yellow);
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #e9ecef;
            color: #495057;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
        }
        
        .btn-cancel:hover {
            background: #c82333;
        }
        
        .btn-review {
            background: var(--primary-yellow);
            color: var(--text-dark);
            border: none;
        }
        
        .btn-review:hover {
            background: var(--dark-yellow);
        }
        
        .profile-card {
            text-align: center;
            padding: 2rem;
        }
        
        .profile-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-yellow) 0%, var(--secondary-yellow) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: var(--earth-brown);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-spa"></i> Serenity Spa
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">Book Now</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="h3 fw-bold text-dark mb-0">Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="dashboard-card profile-card">
                    <div class="profile-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5 class="fw-bold"><?= htmlspecialchars($user['full_name']) ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    <a href="profile.php" class="btn btn-sm w-100 mb-2" style="background: var(--primary-yellow);">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="booking.php" class="btn btn-sm w-100" style="background: var(--earth-brown); color: white;">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Upcoming Appointments -->
                <div class="dashboard-card">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-calendar-alt"></i> Upcoming Appointments
                    </h4>
                    
                    <?php if ($upcoming_appointments->num_rows > 0): ?>
                        <?php while ($apt = $upcoming_appointments->fetch_assoc()): ?>
                        <div class="appointment-card <?= strtolower($apt['status']) ?>">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($apt['service_name']) ?></h5>
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-user-md"></i> 
                                        <?= htmlspecialchars($apt['therapist_name']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-calendar"></i> 
                                        <?= date('F j, Y', strtotime($apt['appointment_date'])) ?>
                                        <span class="ms-3">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('g:i A', strtotime($apt['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($apt['end_time'])) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= strtolower($apt['status']) ?>">
                                            <?= ucfirst($apt['status']) ?>
                                        </span>
                                        <?php if ($apt['payment_status']): ?>
                                        <span class="status-badge ms-2" style="background: #f8f9fa;">
                                            Payment: <?= ucfirst($apt['payment_status']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="h5 mb-3" style="color: var(--dark-yellow);">
                                        ₱<?= number_format($apt['price'], 2) ?>
                                    </div>
                                    <?php if ($apt['status'] === 'pending'): ?>
                                    <a href="cancel_appointment.php?id=<?= $apt['appointment_id'] ?>" 
                                       class="btn btn-sm btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No upcoming appointments</p>
                            <a href="booking.php" class="btn" style="background: var(--earth-brown); color: white;">
                                Book an Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Past Appointments -->
                <div class="dashboard-card">
                    <h4 class="fw-bold mb-4">
                        <i class="fas fa-history"></i> Past Appointments
                    </h4>
                    
                    <?php if ($past_appointments->num_rows > 0): ?>
                        <?php while ($apt = $past_appointments->fetch_assoc()): ?>
                        <div class="appointment-card completed">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($apt['service_name']) ?></h5>
                                    <div class="text-muted mb-2">
                                        <i class="fas fa-user-md"></i> 
                                        <?= htmlspecialchars($apt['therapist_name']) ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar"></i> 
                                        <?= date('F j, Y', strtotime($apt['appointment_date'])) ?>
                                        <span class="ms-3">
                                            <i class="fas fa-clock"></i> 
                                            <?= date('g:i A', strtotime($apt['start_time'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="mb-2">₱<?= number_format($apt['price'], 2) ?></div>
                                    <?php
                                    // Check if review exists
                                    $check_review = $conn->prepare("SELECT review_id FROM reviews WHERE appointment_id = ?");
                                    $check_review->bind_param("i", $apt['appointment_id']);
                                    $check_review->execute();
                                    $has_review = $check_review->get_result()->num_rows > 0;
                                    ?>
                                    <?php if (!$has_review && $apt['status'] === 'completed'): ?>
                                    <a href="add_review.php?appointment_id=<?= $apt['appointment_id'] ?>" 
                                       class="btn btn-sm btn-review">
                                        <i class="fas fa-star"></i> Leave Review
                                    </a>
                                    <?php elseif ($has_review): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> Reviewed
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No past appointments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>