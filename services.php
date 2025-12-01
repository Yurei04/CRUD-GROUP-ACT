<?php
require 'config.php';

// Get filter parameters
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 10000;
$duration_filter = isset($_GET['duration']) ? $_GET['duration'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'service_name';

// Build query
$query = "SELECT * FROM services WHERE price >= ? AND price <= ?";
$params = [$price_min, $price_max];
$types = "dd";

if ($duration_filter) {
    if ($duration_filter == 'short') {
        $query .= " AND duration <= 60";
    } elseif ($duration_filter == 'medium') {
        $query .= " AND duration > 60 AND duration <= 90";
    } elseif ($duration_filter == 'long') {
        $query .= " AND duration > 90";
    }
}

// Add sorting
switch ($sort_by) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'duration':
        $query .= " ORDER BY duration ASC";
        break;
    default:
        $query .= " ORDER BY service_name ASC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$services = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Serenity Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-yellow: #F5C542;
            --secondary-yellow: #FFD966;
            --dark-yellow: #D4A428;
            --light-yellow: #FFF9E6;
            --earth-brown: #8B7355;
            --soft-green: #A8C69F;
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
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--earth-brown) !important;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-yellow) 0%, var(--secondary-yellow) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(245, 197, 66, 0.3);
        }
        
        .service-img {
            height: 220px;
            background: linear-gradient(135deg, var(--light-yellow) 0%, var(--secondary-yellow) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: var(--primary-yellow);
        }
        
        .service-card-body {
            padding: 1.5rem;
        }
        
        .service-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }
        
        .service-price {
            color: var(--dark-yellow);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .service-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }
        
        .service-description {
            color: #555;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .btn-book {
            background: var(--earth-brown);
            color: white;
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-book:hover {
            background: var(--text-dark);
            transform: translateY(-2px);
        }
        
        .filter-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-select, .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-yellow);
            box-shadow: 0 0 0 0.2rem rgba(245, 197, 66, 0.25);
        }
        
        .btn-filter {
            background: var(--primary-yellow);
            color: var(--text-dark);
            font-weight: 600;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            background: var(--dark-yellow);
            color: white;
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
                    <li class="nav-item"><a class="nav-link active" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">Book Now</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="display-4 fw-bold text-dark">Our Services</h1>
            <p class="lead text-dark">Choose from our curated selection of wellness treatments</p>
        </div>
    </div>

    <!-- Services Content -->
    <div class="container mb-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="filter-card">
                    <h5 class="fw-bold mb-3"><i class="fas fa-filter"></i> Filters</h5>
                    
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="filter-label">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="price_min" class="form-control" 
                                           placeholder="Min" value="<?= $price_min ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="price_max" class="form-control" 
                                           placeholder="Max" value="<?= $price_max ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="filter-label">Duration</label>
                            <select name="duration" class="form-select">
                                <option value="">All Durations</option>
                                <option value="short" <?= $duration_filter == 'short' ? 'selected' : '' ?>>
                                    Short (≤60 min)
                                </option>
                                <option value="medium" <?= $duration_filter == 'medium' ? 'selected' : '' ?>>
                                    Medium (60-90 min)
                                </option>
                                <option value="long" <?= $duration_filter == 'long' ? 'selected' : '' ?>>
                                    Long (>90 min)
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="filter-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="service_name" <?= $sort_by == 'service_name' ? 'selected' : '' ?>>
                                    Name (A-Z)
                                </option>
                                <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>
                                    Price (Low to High)
                                </option>
                                <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>
                                    Price (High to Low)
                                </option>
                                <option value="duration" <?= $sort_by == 'duration' ? 'selected' : '' ?>>
                                    Duration
                                </option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        
                        <a href="services.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Services Grid -->
            <div class="col-lg-9">
                <div class="mb-3">
                    <p class="text-muted">
                        Showing <?= $services->num_rows ?> service(s)
                    </p>
                </div>
                
                <div class="row">
                    <?php 
                    $icons = ['fa-hands', 'fa-hand-sparkles', 'fa-fire', 'fa-leaf', 'fa-baby', 'fa-running', 
                              'fa-heart', 'fa-star', 'fa-sun'];
                    $i = 0;
                    while ($service = $services->fetch_assoc()): 
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="service-card">
                            <div class="service-img">
                                <i class="fas <?= $icons[$i % count($icons)] ?>"></i>
                            </div>
                            <div class="service-card-body">
                                <h3 class="service-title"><?= htmlspecialchars($service['service_name']) ?></h3>
                                <div class="service-price">₱<?= number_format($service['price'], 2) ?></div>
                                <div class="service-meta">
                                    <span><i class="far fa-clock"></i> <?= $service['duration'] ?> min</span>
                                </div>
                                <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                                <a href="booking.php?service_id=<?= $service['service_id'] ?>" class="btn btn-book">
                                    <i class="fas fa-calendar-check"></i> Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $i++;
                    endwhile; 
                    ?>
                    
                    <?php if ($services->num_rows == 0): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle"></i> No services found matching your criteria.
                            <a href="services.php">Clear filters</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>