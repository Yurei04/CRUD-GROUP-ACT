<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$user_id = $_SESSION['user_id'];

// Verify appointment
$stmt = $conn->prepare("
    SELECT a.*, s.service_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    WHERE a.appointment_id = ? AND a.user_id = ? AND a.status = 'completed'
");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    $_SESSION['error'] = "Invalid appointment or you cannot review this appointment.";
    header("Location: dashboard.php");
    exit;
}

// Check if already reviewed
$check = $conn->prepare("SELECT review_id FROM reviews WHERE appointment_id = ?");
$check->bind_param("i", $appointment_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You have already reviewed this appointment.";
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5.";
    } elseif (empty($comment)) {
        $error = "Please write a comment.";
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (appointment_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $appointment_id, $user_id, $rating, $comment);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Thank you for your review!";
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Failed to submit review. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Review - Serenity Spa</title>
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
            background: linear-gradient(135deg, var(--light-yellow) 0%, var(--secondary-yellow) 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .review-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .star-rating {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
        }
        
        .star-rating i.active {
            color: var(--primary-yellow);
        }
        
        .btn-submit {
            background: var(--earth-brown);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            font-weight: 600;
            border-radius: 10px;
        }
        
        .btn-submit:hover {
            background: var(--text-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="review-card">
            <h2 class="text-center mb-4">Leave a Review</h2>
            
            <div class="mb-4 p-3 bg-light rounded">
                <h5><?= htmlspecialchars($appointment['service_name']) ?></h5>
                <small class="text-muted">
                    <?= date('F j, Y', strtotime($appointment['appointment_date'])) ?>
                </small>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4 text-center">
                    <label class="form-label fw-bold">Your Rating</label>
                    <div class="star-rating" id="starRating">
                        <i class="fas fa-star" data-rating="1"></i>
                        <i class="fas fa-star" data-rating="2"></i>
                        <i class="fas fa-star" data-rating="3"></i>
                        <i class="fas fa-star" data-rating="4"></i>
                        <i class="fas fa-star" data-rating="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Your Review</label>
                    <textarea name="comment" class="form-control" rows="5" 
                              placeholder="Share your experience..." required></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-secondary flex-fill">Cancel</a>
                    <button type="submit" class="btn btn-submit flex-fill">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const stars = document.querySelectorAll('.star-rating i');
        const ratingInput = document.getElementById('ratingInput');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = 'var(--primary-yellow)';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = 'var(--primary-yellow)';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>