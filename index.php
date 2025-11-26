<?php
require 'config.php';

// Fetch list of services
$services = $conn->query("SELECT id, name, price FROM services ORDER BY name ASC");

// Form handling
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $service_id = intval($_POST['service_id']);
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];

    if ($customer_name && $service_id && $booking_date && $booking_time) {
        
        $stmt = $conn->prepare("INSERT INTO bookings (customer_name, service_id, booking_date, booking_time) 
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $customer_name, $service_id, $booking_date, $booking_time);

        if ($stmt->execute()) {
            $success = "Your booking has been submitted successfully!";
        } else {
            $error = "Error saving booking.";
        }

    } else {
        $error = "Please fill all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Simple Booking System</a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">

            <div class="col-md-6">

                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Book a Service</h4>
                    </div>

                    <div class="card-body">

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" name="customer_name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Select Service</label>
                                <select name="service_id" class="form-control" required>
                                    <option value="">-- Choose a Service --</option>

                                    <?php while ($row = $services->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>">
                                            <?= $row['name'] ?> (₱<?= number_format($row['price']) ?>)
                                        </option>
                                    <?php endwhile; ?>

                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Booking Date</label>
                                <input type="date" name="booking_date" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Booking Time</label>
                                <input type="time" name="booking_time" class="form-control" required>
                            </div>

                            <button class="btn btn-primary w-100">Submit Booking</button>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

</body>
</html>
