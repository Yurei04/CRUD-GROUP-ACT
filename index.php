<?php
require 'config.php';

// Fetch popular services
$services = $conn->query("SELECT * FROM services ORDER BY service_id ASC LIMIT 6");

// Fetch recent reviews
$reviews = $conn->query("
    SELECT r.rating, r.comment, u.full_name, r.created_at
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serenity Spa & Wellness - Your Journey to Relaxation</title>
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
            color: var(--text-dark);
        }
        
        /* Navigation */
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
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(245, 197, 66, 0.9) 0%, rgba(255, 217, 102, 0.9) 100%),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f5c542" width="1200" height="600"/><path fill="%23ffd966" opacity="0.3" d="M0 300Q300 200 600 300T1200 300V600H0Z"/></svg>');
            background-size: cover;
            background-position: center;
            min-height: 600px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(255,255,255,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: var(--earth-brown);
            margin-bottom: 2rem;
        }
        
        .btn-primary-custom {
            background: var(--earth-brown);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 115, 85, 0.3);
        }
        
        .btn-primary-custom:hover {
            background: var(--text-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(139, 115, 85, 0.4);
        }
        
        .btn-secondary-custom {
            background: transparent;
            color: var(--text-dark);
            border: 2px solid var(--text-dark);
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary-custom:hover {
            background: var(--text-dark);
            color: white;
            transform: translateY(-3px);
        }
        
        /* Service Cards */
        .service-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(245, 197, 66, 0.3);
        }
        
        .service-img {
            height: 200px;
            background: linear-gradient(135deg, var(--light-yellow) 0%, var(--secondary-yellow) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-yellow);
        }
        
        .service-card-body {
            padding: 1.5rem;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .service-price {
            color: var(--dark-yellow);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .service-duration {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .service-description {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        /* Section Styling */
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-yellow) 0%, var(--secondary-yellow) 100%);
            border-radius: 2px;
        }
        
        /* Testimonial Cards */
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(245, 197, 66, 0.2);
        }
        
        .stars {
            color: var(--primary-yellow);
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .testimonial-text {
            font-style: italic;
            color: #555;
            margin-bottom: 1rem;
        }
        
        .testimonial-author {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-yellow) 0%, var(--dark-yellow) 100%);
            padding: 5rem 0;
            color: var(--text-dark);
        }
        
        /* Footer */
        footer {
            background: var(--text-dark);
            color: white;
            padding: 2rem 0;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease;
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
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="booking.php">Book Now</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content fade-in-up">
                    <h1 class="hero-title">Your Wellness Journey Starts Here</h1>
                    <p class="hero-subtitle">Experience tranquility and rejuvenation with our premium spa services</p>
                    <div class="d-flex gap-3">
                        <a href="booking.php" class="btn btn-primary-custom">Book Now</a>
                        <a href="services.php" class="btn btn-secondary-custom">View Services</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Overview -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Popular Services</h2>
                <p class="text-muted">Discover our range of therapeutic and relaxation treatments</p>
            </div>
            
            <div class="row g-4">
                <?php 
                $icons = ['fa-hands', 'fa-hand-sparkles', 'fa-fire', 'fa-leaf', 'fa-baby', 'fa-running'];
                $i = 0;
                while ($service = $services->fetch_assoc()): 
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card">
                        <div class="service-img">
                            <i class="fas <?= $icons[$i % 6] ?>"></i>
                        </div>
                        <div class="service-card-body">
                            <h3 class="service-title"><?= htmlspecialchars($service['service_name']) ?></h3>
                            <div class="service-price">₱<?= number_format($service['price'], 2) ?></div>
                            <div class="service-duration">
                                <i class="far fa-clock"></i> <?= $service['duration'] ?> minutes
                            </div>
                            <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                            <a href="booking.php?service_id=<?= $service['service_id'] ?>" class="btn btn-primary-custom w-100">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                $i++;
                endwhile; 
                ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="services.php" class="btn btn-secondary-custom">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <?php if ($reviews->num_rows > 0): ?>
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">What Our Clients Say</h2>
                <p class="text-muted">Real experiences from our valued customers</p>
            </div>
            
            <div class="row g-4">
                <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card">
                        <div class="stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star<?= $i < $review['rating'] ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-text">"<?= htmlspecialchars($review['comment']) ?>"</p>
                        <div class="testimonial-author">- <?= htmlspecialchars($review['full_name']) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="display-4 fw-bold mb-4">Ready to Begin Your Wellness Journey?</h2>
            <p class="lead mb-4">Book your first session today and experience the difference</p>
            <a href="register.php" class="btn btn-primary-custom btn-lg">Create an Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-spa"></i> Serenity Spa & Wellness</h5>
                    <p class="text-muted">Your wellness journey starts here</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 Serenity Spa. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>