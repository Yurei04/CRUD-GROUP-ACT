<?php
require 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']); // Assuming categories exist and need selection

    // Basic validation
    if ($name !== "" && $price > 0 && $category_id > 0) {
        // NOTE: We need to define $conn in config.php
        $stmt = $conn->prepare("INSERT INTO services (name, description, price, category_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $name, $description, $price, $category_id);

        if ($stmt->execute()) {
            $success = "Service '{$name}' added successfully!";
            // Clear form data after success
            $name = $description = $price = '';
            // Ideally, redirect to admin_services.php
        } else {
            $error = "Error adding service: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please ensure all fields are filled out correctly (Price must be > 0 and Category selected).";
    }
}

// Fetch categories for the dropdown (assuming admin_categories.php is next)
$categories_result = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - Serenity Spa Admin</title>
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
        
        .sidebar-link:hover, .sidebar-link.active {
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
                    <a href="admin_appointments.php" class="sidebar-link">
                        <i class="fas fa-calendar"></i> Appointments
                    </a>
                    <a href="admin_services.php" class="sidebar-link active">
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
                <h2 class="mb-4"><i class="fas fa-plus-circle"></i> Add New Service</h2>
                
                <div class="content-card">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <small class="text-danger">No categories found. Please add a category first.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" required><?= htmlspecialchars($description ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (₱)</label>
                            <input type="number" name="price" id="price" class="form-control" required min="1" step="0.01" value="<?= htmlspecialchars($price ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save"></i> Save Service
                        </button>
                        <a href="admin_services.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> View Services
                        </a>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>