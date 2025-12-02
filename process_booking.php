<?php
require 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = intval($_POST['service_id']);
    $therapist_id = intval($_POST['therapist_id']);
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $payment_method = $_POST['payment_method'];
    $promo_code = isset($_POST['promo_code']) ? trim($_POST['promo_code']) : '';
    
    // Get service details
    $stmt = $conn->prepare("SELECT duration, price FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();
    
    if (!$service) {
        $_SESSION['error'] = "Invalid service selected.";
        header("Location: booking.php");
        exit;
    }
    
    // Calculate end time
    $start_datetime = new DateTime($start_time);
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval('PT' . $service['duration'] . 'M'));
    $end_time = $end_datetime->format('H:i:s');
    
    $amount = $service['price'];
    
    // Check for promo code
    if ($promo_code) {
        $promo_stmt = $conn->prepare("
            SELECT discount_percent FROM promotions 
            WHERE promo_code = ? AND start_date <= CURDATE() AND end_date >= CURDATE()
        ");
        $promo_stmt->bind_param("s", $promo_code);
        $promo_stmt->execute();
        $promo = $promo_stmt->get_result()->fetch_assoc();
        
        if ($promo) {
            $discount = ($amount * $promo['discount_percent']) / 100;
            $amount = $amount - $discount;
        }
    }
    
    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments (user_id, therapist_id, service_id, appointment_date, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iiisss", $user_id, $therapist_id, $service_id, $appointment_date, $start_time, $end_time);
    
    if ($stmt->execute()) {
        $appointment_id = $conn->insert_id;
        
        // Insert payment record
        $payment_stmt = $conn->prepare("
            INSERT INTO payments (appointment_id, amount, payment_method, payment_status) 
            VALUES (?, ?, ?, 'unpaid')
        ");
        $payment_stmt->bind_param("ids", $appointment_id, $amount, $payment_method);
        $payment_stmt->execute();
        
        $_SESSION['success'] = "Appointment booked successfully!";
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Failed to book appointment. Please try again.";
        header("Location: booking.php");
        exit;
    }
} else {
    header("Location: booking.php");
    exit;
}
?>