<?php
require 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'booking.php' . (isset($_GET['service_id']) ? '?service_id=' . $_GET['service_id'] : '');
    header("Location: login.php");
    exit;
}

// Fetch services
$services_result = $conn->query("SELECT * FROM services ORDER BY service_name ASC");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch therapists
$therapists_result = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'therapist' ORDER BY full_name ASC");
$therapists = [];
while ($row = $therapists_result->fetch_assoc()) {
    $therapists[] = $row;
}

// Get pre-selected service if any
$selected_service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Serenity Spa</title>
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
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        
        .booking-container {
            max-width: 900px;
            margin: 0 auto;
            padding-bottom: 3rem;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .progress-bar-line {
            position: absolute;
            top: 20px;
            left: 0;
            height: 4px;
            background: var(--primary-yellow);
            z-index: 1;
            transition: width 0.3s ease;
        }
        
        .step {
            background: white;
            border: 4px solid #e0e0e0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #999;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: var(--primary-yellow);
            border-color: var(--primary-yellow);
            color: var(--text-dark);
        }
        
        .step.completed {
            background: var(--earth-brown);
            border-color: var(--earth-brown);
            color: white;
        }
        
        .step-label {
            position: absolute;
            top: 50px;
            white-space: nowrap;
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
        }
        
        .step.active .step-label {
            color: var(--text-dark);
        }
        
        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .step-content {
            display: none;
        }
        
        .step-content.active {
            display: block;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-yellow);
            box-shadow: 0 0 0 0.2rem rgba(245, 197, 66, 0.25);
        }
        
        .service-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .service-option:hover {
            border-color: var(--primary-yellow);
            background: var(--light-yellow);
        }
        
        .service-option.selected {
            border-color: var(--primary-yellow);
            background: var(--light-yellow);
        }
        
        .service-option input[type="radio"] {
            display: none;
        }
        
        .therapist-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .therapist-card:hover {
            border-color: var(--primary-yellow);
        }
        
        .therapist-card.selected {
            border-color: var(--primary-yellow);
            background: var(--light-yellow);
        }
        
        .time-slot {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .time-slot:hover {
            border-color: var(--primary-yellow);
        }
        
        .time-slot.selected {
            background: var(--primary-yellow);
            border-color: var(--primary-yellow);
            color: var(--text-dark);
        }
        
        .time-slot.unavailable {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            text-decoration: line-through;
            opacity: 0.6;
            border-color: #f5f5f5;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-label {
            font-weight: 600;
            color: #666;
        }
        
        .summary-value {
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .total-price {
            font-size: 1.8rem;
            color: var(--dark-yellow);
            font-weight: 700;
        }
        
        .btn-next, .btn-submit {
            background: var(--earth-brown);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-next:hover, .btn-submit:hover {
            background: var(--text-dark);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-prev {
            background: transparent;
            color: var(--text-dark);
            border: 2px solid var(--text-dark);
            padding: 0.9rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-prev:hover {
            background: var(--text-dark);
            color: white;
        }

        .validation-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-weight: 600;
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
                    <li class="nav-item"><a class="nav-link active" href="booking.php">Book Now</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold text-dark">Book Your Appointment</h1>
            <p class="lead text-dark">Let's schedule your wellness journey</p>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="booking-container">
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="progress-bar-line" id="progressBar" style="width: 0%;"></div>
            <div>
                <div class="step active" data-step="1">1</div>
                <div class="step-label">Service & Therapist</div>
            </div>
            <div>
                <div class="step" data-step="2">2</div>
                <div class="step-label">Date & Time</div>
            </div>
            <div>
                <div class="step" data-step="3">3</div>
                <div class="step-label">Confirmation</div>
            </div>
        </div>

        <div class="booking-card">
            <!-- Added validation message container for Step 1 -->
            <div class="validation-message text-center mb-3" id="step1ValidationMessage"></div>
            
            <form id="bookingForm" method="POST" action="process_booking.php">
                <!-- Step 1: Service & Therapist -->
                <div class="step-content active" data-step="1">
                    <h3 class="mb-4">Select Service & Therapist</h3>
                    
                    <div class="mb-4">
                        <label class="form-label">Choose a Service</label>
                        <div id="serviceSelection">
                            <?php foreach ($services as $service): ?>
                            <label class="service-option <?= $service['service_id'] == $selected_service_id ? 'selected' : '' ?>">
                                <input type="radio" name="service_id" value="<?= $service['service_id'] ?>" 
                                        data-price="<?= $service['price'] ?>" 
                                        data-duration="<?= $service['duration'] ?>"
                                        data-name="<?= htmlspecialchars($service['service_name']) ?>"
                                        <?= $service['service_id'] == $selected_service_id ? 'checked' : '' ?> required>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($service['service_name']) ?></strong>
                                        <div class="text-muted small">
                                            <i class="far fa-clock"></i> <?= $service['duration'] ?> minutes
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="h5 mb-0" style="color: var(--dark-yellow);">
                                            ₱<?= number_format($service['price'], 2) ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Choose Your Therapist</label>
                        <div id="therapistSelection">
                            <?php foreach ($therapists as $therapist): ?>
                            <label class="therapist-card">
                                <input type="radio" name="therapist_id" value="<?= $therapist['user_id'] ?>"
                                        data-name="<?= htmlspecialchars($therapist['full_name']) ?>" required>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-user-circle fa-2x" style="color: var(--earth-brown);"></i>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($therapist['full_name']) ?></strong>
                                        <div class="text-muted small">Licensed Therapist</div>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Date & Time -->
                <div class="step-content" data-step="2">
                    <h3 class="mb-4">Choose Date & Time</h3>
                    
                    <div class="mb-4">
                        <label class="form-label">Select Date</label>
                        <input type="date" name="appointment_date" id="appointmentDate" 
                                class="form-control" required
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        <div class="validation-message" id="dateError"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Select Time</label>
                        <!-- Loading Indicator for AJAX -->
                        <div id="timeSlotsLoading" class="text-center my-3 text-muted" style="display: none;">
                            <i class="fas fa-spinner fa-spin me-2"></i> Checking availability...
                        </div>
                        <div class="row g-2" id="timeSlots">
                            <p class="text-muted mt-2 ps-3">Select a date and therapist to view available times.</p>
                        </div>
                        <div class="validation-message" id="timeError"></div>
                        <input type="hidden" name="start_time" id="selectedTime" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-prev" onclick="prevStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-next" onclick="nextStep()">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Confirmation -->
                <div class="step-content" data-step="3">
                    <h3 class="mb-4">Confirm Your Booking</h3>
                    
                    <div class="mb-4">
                        <div class="summary-item">
                            <span class="summary-label">Service:</span>
                            <span class="summary-value" id="summaryService">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Therapist:</span>
                            <span class="summary-value" id="summaryTherapist">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Date:</span>
                            <span class="summary-value" id="summaryDate">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Time:</span>
                            <span class="summary-value" id="summaryTime">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Duration:</span>
                            <span class="summary-value" id="summaryDuration">-</span>
                        </div>
                        <div class="summary-item border-0 mt-3">
                            <span class="h5 mb-0">Total Amount:</span>
                            <span class="total-price" id="summaryPrice">₱0.00</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">Choose payment method</option>
                            <option value="cash">Cash on Site</option>
                            <option value="credit_card">Credit Card (Pay Now)</option>
                            <option value="gcash">GCash (Pay Now)</option>
                        </select>
                        <div class="validation-message" id="paymentError"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Promo Code (Optional)</label>
                        <div class="input-group">
                            <input type="text" name="promo_code" class="form-control" 
                                    placeholder="Enter promo code">
                            <button type="button" class="btn" style="background: var(--primary-yellow);">
                                Apply
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-prev" onclick="prevStep()">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <button type="submit" class="btn btn-next btn-submit">
                            <i class="fas fa-check"></i> Confirm Booking
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 3;
        
        // Helper function for visual validation feedback (replacing alert())
        function showValidationMessage(message, elementId) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.style.display = message ? 'block' : 'none';
        }

        function clearValidationMessages() {
            document.querySelectorAll('.validation-message').forEach(el => el.textContent = '');
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        // Handle service selection
        document.querySelectorAll('.service-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.service-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                showValidationMessage('', 'step1ValidationMessage'); // Clear validation
                
                // If therapist and date are already set, re-fetch slots
                if (document.getElementById('appointmentDate').value && document.querySelector('input[name="therapist_id"]:checked')) {
                    fetchAvailableSlots();
                }
            });
        });
        
        // Handle therapist selection
        document.querySelectorAll('.therapist-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.therapist-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                showValidationMessage('', 'step1ValidationMessage'); // Clear validation

                // If service and date are already set, re-fetch slots
                if (document.getElementById('appointmentDate').value && document.querySelector('input[name="service_id"]:checked')) {
                    fetchAvailableSlots();
                }
            });
        });
        
        // Function to fetch and display available time slots (AJAX)
        async function fetchAvailableSlots() {
            const date = document.getElementById('appointmentDate').value;
            const service = document.querySelector('input[name="service_id"]:checked');
            const therapist = document.querySelector('input[name="therapist_id"]:checked');
            const container = document.getElementById('timeSlots');
            const loading = document.getElementById('timeSlotsLoading');
            const selectedTimeInput = document.getElementById('selectedTime');
            
            // Reset state
            container.innerHTML = '';
            selectedTimeInput.value = '';
            showValidationMessage('', 'timeError');
            
            if (!date || !service || !therapist) {
                container.innerHTML = '<p class="text-muted mt-2 ps-3">Please select a service, therapist, and date to check availability.</p>';
                return;
            }

            loading.style.display = 'block';

            const formData = new FormData();
            formData.append('appointment_date', date);
            formData.append('service_id', service.value);
            formData.append('therapist_id', therapist.value);

            try {
                const response = await fetch('fetch_slots.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                
                if (data.status === 'success') {
                    if (data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const isAvailable = slot.available;
                            const slotClass = isAvailable ? '' : 'unavailable';
                            
                            const col = document.createElement('div');
                            col.className = 'col-6 col-md-3';
                            col.innerHTML = `
                                <div class="time-slot ${slotClass}" data-time="${slot.time}">
                                    ${slot.time}
                                </div>
                            `;
                            container.appendChild(col);
                        });
                        
                        // Add click handlers for new slots
                        document.querySelectorAll('.time-slot:not(.unavailable)').forEach(slot => {
                            slot.addEventListener('click', function() {
                                document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                                this.classList.add('selected');
                                selectedTimeInput.value = this.dataset.time;
                                showValidationMessage('', 'timeError'); // Clear time validation on selection
                            });
                        });
                        
                    } else {
                        container.innerHTML = '<p class="text-danger mt-2 ps-3">No available slots for this date and therapist. Please try a different day or therapist.</p>';
                    }
                } else {
                    container.innerHTML = `<p class="text-danger mt-2 ps-3">Error fetching slots: ${data.message}</p>`;
                }

            } catch (error) {
                console.error('Fetch error:', error);
                container.innerHTML = '<p class="text-danger mt-2 ps-3">Could not connect to the server to check availability.</p>';
            } finally {
                loading.style.display = 'none';
            }
        }
        
        // Trigger fetch when date, service, or therapist changes
        document.getElementById('appointmentDate')?.addEventListener('change', fetchAvailableSlots);
        document.querySelectorAll('input[name="service_id"]').forEach(radio => radio.addEventListener('change', fetchAvailableSlots));
        document.querySelectorAll('input[name="therapist_id"]').forEach(radio => radio.addEventListener('change', fetchAvailableSlots));

        // Initial check for pre-selected service
        window.addEventListener('load', () => {
            if (document.querySelector('input[name="service_id"]:checked')) {
                // Pre-select the first therapist if a service is already selected, and we don't have one selected yet
                if (!document.querySelector('input[name="therapist_id"]:checked')) {
                    const firstTherapist = document.querySelector('.therapist-card input[type="radio"]');
                    if (firstTherapist) {
                        firstTherapist.checked = true;
                        firstTherapist.closest('.therapist-card').classList.add('selected');
                    }
                }
            }
        });


        function nextStep() {
            clearValidationMessages();
            let isValid = true;
            
            if (currentStep === 1) {
                // Step 1 Validation: Service and Therapist must be selected
                if (!document.querySelector('input[name="service_id"]:checked')) {
                    showValidationMessage('Please select a service.', 'step1ValidationMessage');
                    isValid = false;
                }
                if (!document.querySelector('input[name="therapist_id"]:checked')) {
                    if (isValid) showValidationMessage('Please select a therapist.', 'step1ValidationMessage');
                    isValid = false;
                }
            } else if (currentStep === 2) {
                // Step 2 Validation: Date and Time must be selected
                const dateInput = document.getElementById('appointmentDate');
                const timeInput = document.getElementById('selectedTime');
                
                if (!dateInput.value) {
                    dateInput.classList.add('is-invalid');
                    showValidationMessage('Please select an appointment date.', 'dateError');
                    isValid = false;
                }
                
                if (!timeInput.value) {
                    // Also check if slots were loaded and none were selected
                    const selectedSlot = document.querySelector('.time-slot.selected');
                    if (!selectedSlot) {
                        showValidationMessage('Please select an available time slot.', 'timeError');
                        isValid = false;
                    }
                }

            } else if (currentStep === 3) {
                // Step 3 Validation: Payment method must be selected
                const paymentSelect = document.querySelector('select[name="payment_method"]');
                if (!paymentSelect.value) {
                    paymentSelect.classList.add('is-invalid');
                    showValidationMessage('Please select a payment method.', 'paymentError');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                // If there's an error in step 1, scroll to the message
                if (currentStep === 1) {
                    document.getElementById('step1ValidationMessage').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                // Stop progression if validation fails
                return;
            }
            
            if (currentStep < totalSteps) {
                // Mark current as completed
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
                
                currentStep++;
                
                // Activate next step
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
                
                // Show next content
                document.querySelectorAll('.step-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
                
                // Update progress bar
                const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                document.getElementById('progressBar').style.width = progress + '%';
                
                // Update summary if on last step
                if (currentStep === 3) {
                    updateSummary();
                }
                
                // Scroll to top
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        }
        
        function prevStep() {
            clearValidationMessages();
            if (currentStep > 1) {
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
                
                currentStep--;
                
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('completed');
                document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
                
                document.querySelectorAll('.step-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
                
                const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
                document.getElementById('progressBar').style.width = progress + '%';
                
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        }
        
        function updateSummary() {
            const service = document.querySelector('input[name="service_id"]:checked');
            const therapist = document.querySelector('input[name="therapist_id"]:checked');
            const date = document.getElementById('appointmentDate').value;
            const time = document.getElementById('selectedTime').value;
            
            if (service) {
                document.getElementById('summaryService').textContent = service.dataset.name;
                document.getElementById('summaryDuration').textContent = service.dataset.duration + ' minutes';
                // Use the data-price for display
                const price = parseFloat(service.dataset.price);
                document.getElementById('summaryPrice').textContent = '₱' + price.toLocaleString('en-PH', {minimumFractionDigits: 2});
            }
            
            if (therapist) {
                document.getElementById('summaryTherapist').textContent = therapist.dataset.name;
            }
            
            if (date) {
                // Format the date nicely for the summary
                const dateObj = new Date(date + 'T00:00:00'); // Add time to prevent timezone issues
                document.getElementById('summaryDate').textContent = dateObj.toLocaleDateString('en-US', {
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
            }
            
            if (time) {
                document.getElementById('summaryTime').textContent = time;
            }
        }
    </script>
</body>
</html>