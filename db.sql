-- Database: booking_system
CREATE DATABASE IF NOT EXISTS booking_system;
USE booking_system;

-- ===========================
-- 1. USERS TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'therapist', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO users (full_name, email, phone_number, password, role)
VALUES ('System Admin', 'admin@booking.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password

-- ===========================
-- 2. SERVICES TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sample services
INSERT INTO services (service_name, description, duration, price) VALUES
('Swedish Massage', 'Relaxing full body massage using long strokes and kneading techniques', 60, 1200.00),
('Deep Tissue Massage', 'Therapeutic massage targeting deep muscle layers', 90, 1800.00),
('Hot Stone Therapy', 'Massage using heated stones for deep relaxation', 75, 1600.00),
('Aromatherapy Massage', 'Massage with essential oils for holistic wellness', 60, 1400.00),
('Prenatal Massage', 'Gentle massage for expecting mothers', 60, 1500.00),
('Sports Massage', 'Targeted massage for athletes and active individuals', 60, 1300.00);

-- ===========================
-- 3. APPOINTMENTS TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    therapist_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (therapist_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- ===========================
-- 4. PAYMENTS TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'paypal', 'gcash') DEFAULT 'cash',
    payment_status ENUM('paid', 'unpaid', 'refunded') DEFAULT 'unpaid',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- ===========================
-- 5. AVAILABILITY TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    therapist_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    
    FOREIGN KEY (therapist_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ===========================
-- 6. REVIEWS TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ===========================
-- 7. PROMOTIONS TABLE
-- ===========================
CREATE TABLE IF NOT EXISTS promotions (
    promo_id INT AUTO_INCREMENT PRIMARY KEY,
    promo_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    discount_percent DECIMAL(5,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL
);

-- Sample promotions
INSERT INTO promotions (promo_code, description, discount_percent, start_date, end_date) VALUES
('WELCOME20', 'Welcome discount for new customers', 20.00, '2024-01-01', '2024-12-31'),
('RELAX15', 'Relax and save 15%', 15.00, '2024-01-01', '2024-12-31');

-- ===========================
-- Sample Therapists
-- ===========================
INSERT INTO users (full_name, email, phone_number, password, role) VALUES
('Maria Santos', 'maria@booking.com', '09171234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'therapist'),
('Juan Dela Cruz', 'juan@booking.com', '09181234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'therapist'),
('Anna Reyes', 'anna@booking.com', '09191234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'therapist');

-- ===========================
-- Sample Availability
-- ===========================
INSERT INTO availability (therapist_id, date, start_time, end_time) VALUES
(2, '2024-12-02', '09:00:00', '17:00:00'),
(2, '2024-12-03', '09:00:00', '17:00:00'),
(3, '2024-12-02', '10:00:00', '18:00:00'),
(3, '2024-12-03', '10:00:00', '18:00:00'),
(4, '2024-12-02', '08:00:00', '16:00:00'),
(4, '2024-12-03', '08:00:00', '16:00:00');