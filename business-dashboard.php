<?php

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_businesses.php';
require_once 'backend/function_appointments.php';
require_once 'backend/function_services.php';
require_once 'backend/function_employees.php';
require_once 'backend/function_reviews.php';
require_once 'backend/function_notifications.php';

// Check if business is logged in
if (!isBusinessLoggedIn()) {
    header('Location: login.php');
    exit;
}

$business = getCurrentBusiness();
$businessId = $business['business_id'] ?? $business['id'];

// Mark business notifications as read when viewing dashboard
if (isset($business['business_id'])) {
    markBusinessNotificationsAsRead($business['business_id']);
}

// Handle appointment status update with notification
// Handle appointment status update with notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $newStatus = sanitize($_POST['status']);
    
    $appointment = getAppointmentById($appointmentId);
    
    if ($appointment && updateAppointmentStatus($appointmentId, $newStatus)) {
        createAppointmentNotification(
            $appointment['customer_id'],
            $businessId,
            $appointmentId,
            $newStatus
        );
        
        $statusText = ucfirst($newStatus);
        
        if ($newStatus === 'confirmed') {
            $_SESSION['success'] = "Appointment {$statusText} successfully! Customer has been notified. Conflicting appointments have been marked as unavailable.";
        } else {
            $_SESSION['success'] = "Appointment {$statusText} successfully! Customer has been notified.";
        }
    } else {
        $_SESSION['error'] = 'Failed to update appointment status';
    }
    
    header('Location: business-dashboard.php');
    exit;
}

// Handle ADD service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $serviceData = [
        'business_id' => $businessId,
        'service_name' => sanitize($_POST['service_name']),
        'service_type' => sanitize($_POST['service_type'] ?? ''),
        'service_desc' => sanitize($_POST['service_desc']),
        'cost' => floatval($_POST['cost']),
        'duration' => intval($_POST['duration'])
    ];
    
    if (createService($serviceData)) {
        $_SESSION['success'] = 'Service added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add service';
    }
    
    header('Location: business-dashboard.php#services');
    exit;
}

// Handle UPDATE service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $serviceId = intval($_POST['service_id']);
    $serviceData = [
        'service_name' => sanitize($_POST['service_name']),
        'service_type' => sanitize($_POST['service_type'] ?? ''),
        'service_desc' => sanitize($_POST['service_desc']),
        'cost' => floatval($_POST['cost']),
        'duration' => intval($_POST['duration'])
    ];
    
    if (updateService($serviceId, $serviceData)) {
        $_SESSION['success'] = 'Service updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update service';
    }
    
    header('Location: business-dashboard.php#services');
    exit;
}

// Handle DELETE service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $serviceId = intval($_POST['service_id']);
    
    if (deleteService($serviceId)) {
        $_SESSION['success'] = 'Service deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete service. It may be associated with existing appointments.';
    }
    
    header('Location: business-dashboard.php#services');
    exit;
}

// Handle ADD staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    // Handle skills - convert array to comma-separated string
    $skills = '';
    if (isset($_POST['skills']) && is_array($_POST['skills'])) {
        $skills = implode(', ', $_POST['skills']);
    } elseif (isset($_POST['skills'])) {
        $skills = sanitize($_POST['skills']);
    }
    
    $staffData = [
        'business_id' => $businessId,
        'employ_fname' => sanitize($_POST['employ_fname']),
        'employ_lname' => sanitize($_POST['employ_lname']),
        'specialization' => sanitize($_POST['specialization']),
        'skills' => $skills,
        'employ_bio' => sanitize($_POST['employ_bio'] ?? ''),
        'employ_status' => 'available'
    ];
    
    if (createEmployee($staffData)) {
        $_SESSION['success'] = 'Staff member added successfully!';
    } else {
        $_SESSION['error'] = 'Failed to add staff member';
    }
    
    header('Location: business-dashboard.php#staff');
    exit;
}

// Handle UPDATE staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $employId = intval($_POST['employ_id']);
    
    // Handle skills - convert array to comma-separated string
    $skills = '';
    if (isset($_POST['skills']) && is_array($_POST['skills'])) {
        $skills = implode(', ', $_POST['skills']);
    } elseif (isset($_POST['skills'])) {
        $skills = sanitize($_POST['skills']);
    }
    
    $staffData = [
        'employ_fname' => sanitize($_POST['employ_fname']),
        'employ_lname' => sanitize($_POST['employ_lname']),
        'specialization' => sanitize($_POST['specialization']),
        'skills' => $skills,
        'employ_bio' => sanitize($_POST['employ_bio'] ?? ''),
        'employ_status' => sanitize($_POST['employ_status'])
    ];
    
    if (updateEmployee($employId, $staffData)) {
        $_SESSION['success'] = 'Staff member updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update staff member';
    }
    
    header('Location: business-dashboard.php#staff');
    exit;
}

// Handle DELETE staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $employId = intval($_POST['employ_id']);
    
    if (deleteEmployee($employId)) {
        $_SESSION['success'] = 'Staff member removed successfully!';
    } else {
        $_SESSION['error'] = 'Failed to remove staff member';
    }
    
    header('Location: business-dashboard.php#staff');
    exit;
}

// Handle review reply - FIXED: Now passes all 4 required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_review'])) {
    $reviewId = intval($_POST['review_id']);
    $replyText = sanitize($_POST['reply_text']);
    
    // Add reply as business with all 4 parameters: reviewId, senderType, senderId, replyText
    if (addReviewReply($reviewId, 'business', $businessId, $replyText)) {
        $_SESSION['success'] = 'Reply posted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to post reply';
    }
    
    header('Location: business-dashboard.php#reviews');
    exit;
}

$bookings = getBusinessAppointments($businessId);
$services = getBusinessServices($businessId);
$staff = getBusinessEmployees($businessId);
$reviews = getBusinessReviews($businessId);

// Calculate stats
$todayBookings = array_filter($bookings, function($b) {
    return date('Y-m-d', strtotime($b['appoint_date'])) == date('Y-m-d');
});

$pendingBookings = array_filter($bookings, function($b) {
    return $b['appoint_status'] == 'pending';
});

// Prepare analytics data for selected month (default to current month)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$monthStart = $selectedMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

// Get bookings for selected month
$monthBookings = array_filter($bookings, function($b) use ($monthStart, $monthEnd) {
    $bookingDate = date('Y-m-d', strtotime($b['appoint_date']));
    return $bookingDate >= $monthStart && $bookingDate <= $monthEnd;
});

// Prepare data for chart - group by day
$bookingsByDay = [];
$daysInMonth = date('t', strtotime($monthStart));
for ($day = 1; $day <= $daysInMonth; $day++) {
    $bookingsByDay[$day] = 0;
}

foreach ($monthBookings as $booking) {
    $day = intval(date('d', strtotime($booking['appoint_date'])));
    $bookingsByDay[$day]++;
}

// Convert to JSON for JavaScript
$chartLabels = json_encode(array_keys($bookingsByDay));
$chartData = json_encode(array_values($bookingsByDay));

// Calculate monthly statistics
$monthlyStats = [
    'total' => count($monthBookings),
    'confirmed' => count(array_filter($monthBookings, fn($b) => $b['appoint_status'] == 'confirmed')),
    'completed' => count(array_filter($monthBookings, fn($b) => $b['appoint_status'] == 'completed')),
    'cancelled' => count(array_filter($monthBookings, fn($b) => $b['appoint_status'] == 'cancelled'))
];

$pageTitle = 'Business Dashboard - BeautyGo';
include 'includes/header.php';
?>

<style>
.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--color-burgundy);
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.back-button:hover {
    background-color: var(--color-cream);
    color: var(--color-rose);
    transform: translateX(-4px);
}

.status-actions {
    display: flex;
    gap: 5px;
}

.status-actions form {
    margin: 0;
}

/* Review styling */
.review-images {
    margin-top: 0.5rem;
}

.review-image-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid #e0e0e0;
    transition: all 0.2s ease;
}

.review-image-thumb:hover {
    opacity: 0.8;
    transform: scale(1.05);
    border-color: var(--color-burgundy);
}

.review-reply {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    border-left: 3px solid var(--color-burgundy);
}

.replies-section {
    padding-left: 1rem;
    border-left: 3px solid #e0e0e0;
    margin-top: 1rem;
}

/* Analytics styling - UPDATED COLORS */
.analytics-stat-card {
    background: var(--color-burgundy);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(128, 0, 32, 0.2);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.analytics-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(128, 0, 32, 0.3);
}

.analytics-stat-card.total-bookings {
    background: linear-gradient(135deg, var(--color-burgundy) 0%, #a5002a 100%);
}

.analytics-stat-card.completed-bookings {
    background: linear-gradient(135deg, var(--color-rose) 0%, #e75480 100%);
}

.analytics-stat-card.confirmed-bookings {
    background: linear-gradient(135deg, var(--color-pink) 0%, #ff85a2 100%);
}

.analytics-stat-card h6 {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 5px;
}

.analytics-stat-card h3 {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

/* Add to your existing CSS */
.chart-container {
    position: relative;
    height: 300px;
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid var(--color-cream);
    min-height: 300px; /* Prevent jumping */
    overflow: hidden; /* Prevent content shift */
}

/* Prevent layout shift during chart loading */
.chart-container:not(:has(canvas)) {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.chart-container:not(:has(canvas))::before {
    content: "Loading chart...";
    color: #6c757d;
    font-style: italic;
}

.month-selector {
    max-width: 180px;
}

/* Chart color variables */
:root {
    --chart-primary: var(--color-burgundy);
    --chart-secondary: var(--color-rose);
    --chart-accent: var(--color-pink);
    --chart-background: var(--color-cream);
}
</style>

<main>
    <div class="container my-4">
     <!--  <a href="index.php" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Home</span>
        </a>   -->

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Business Dashboard</h2>
            </div>
        </div>
        
        <!-- Dashboard header/banner -->
        <div class="dashboard-header mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="dashboard-title"><?php echo htmlspecialchars($business['business_name'] ?? $business['business'] ?? 'My Business'); ?></h3>
                    <p class="small mb-0">Welcome back — here is a quick overview of your bookings and services.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="business-profile.php" class="btn btn-outline-burgundy btn-action-sm">
                        <i class="bi bi-gear"></i> Edit Profile
                    </a>
                    <a href="index.php?business=<?php echo $businessId; ?>" class="btn btn-burgundy btn-action-sm">
                        <i class="bi bi-box-arrow-up-right"></i> View Public Page
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards - ORIGINAL COLORS -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-day" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Today's Bookings</h6>
                                <h3 class="mb-0"><?php echo count($todayBookings); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card burgundy">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-hourglass-split" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Pending Bookings</h6>
                                <h3 class="mb-0"><?php echo count($pendingBookings); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card pink">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-scissors" style="font-size: 2rem; color: var(--color-burgundy);"></i>
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Total Services</h6>
                                <h3 class="mb-0"><?php echo count($services); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">
                    <i class="bi bi-calendar-check"></i> Bookings
                    <?php if (count($pendingBookings) > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo count($pendingBookings); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button">
                    <i class="bi bi-graph-up"></i> Analytics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button">
                    <i class="bi bi-scissors"></i> Services
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="staff-tab" data-bs-toggle="tab" data-bs-target="#staff" type="button">
                    <i class="bi bi-people"></i> Staff
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">
                    <i class="bi bi-star"></i> Reviews
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="dashboardTabContent">
            <!-- Bookings Tab -->
            <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3 dashboard-section-title">Manage Bookings</h4>
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No bookings yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive table-dashboard">
                                <table class="table table-hover table-dashboard">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date & Time</th>
                                            <th>Customer</th>
                                            <th>Phone</th>
                                            <th>Service</th>
                                            <th>Staff</th>
                                            <th>Amount</th>
                                            <th>Notes</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>#<?php echo $booking['appointment_id']; ?></td>
                                                <td>
                                                    <strong><?php echo date('M j, Y', strtotime($booking['appoint_date'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['appoint_date'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['customer_fname'] . ' ' . $booking['customer_lname']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['customer_phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                                <td><?php echo htmlspecialchars(($booking['staff_fname'] ?? '') . ' ' . ($booking['staff_lname'] ?? '') ?: 'Any Available'); ?></td>
                                                <td><strong>₱<?php echo number_format($booking['cost'] ?? 0, 2); ?></strong></td>
                                                <td>
                                                    <?php if (!empty($booking['appoint_desc'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['appoint_desc']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        // Map statuses to bootstrap color classes and pill styling
                                                        $statusClass = $booking['appoint_status'] === 'confirmed' ? 'bg-success text-white' : (
                                                            $booking['appoint_status'] === 'cancelled' ? 'bg-danger text-white' : (
                                                            $booking['appoint_status'] === 'completed' ? 'bg-info text-white' : (
                                                            $booking['appoint_status'] === 'unavailable' ? 'bg-secondary text-white' : 'bg-warning text-dark')));
                                                    ?>
                                                    <span class="status-badge-pill <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($booking['appoint_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="status-actions">
                                                        <?php if ($booking['appoint_status'] == 'pending'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="confirmed">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-success" title="Confirm & Notify Customer">
                                                                    <i class="bi bi-check-circle"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-danger" title="Cancel & Notify Customer" onclick="return confirm('Cancel this appointment? Customer will be notified.')">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>
                                                            </form>
                                                        <?php elseif ($booking['appoint_status'] == 'confirmed'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" name="update_status" class="btn btn-sm btn-info" title="Mark as Completed">
                                                                    <i class="bi bi-check-all"></i> Complete
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div class="tab-pane fade" id="analytics" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0 dashboard-section-title">Booking Analytics</h4>
                            <div class="month-selector">
                                <label for="monthSelect" class="form-label mb-1 small">Select Month:</label>
                                <input type="month" id="monthSelect" class="form-control" value="<?php echo $selectedMonth; ?>" max="<?php echo date('Y-m'); ?>">
                            </div>
                        </div>

            <!-- Monthly Statistics Cards - KEEP DARK RED FOR ANALYTICS ONLY -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="analytics-stat-card" style="background: linear-gradient(135deg, #800020 0%, #a5002a 100%);">
                        <h6>Total Bookings</h6>
                        <h3><?php echo $monthlyStats['total']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="analytics-stat-card" style="background: linear-gradient(135deg, #800020 0%, #a5002a 100%);">
                        <h6>Completed</h6>
                        <h3><?php echo $monthlyStats['completed']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="analytics-stat-card" style="background: linear-gradient(135deg, #800020 0%, #a5002a 100%);">
                        <h6>Confirmed</h6>
                        <h3><?php echo $monthlyStats['confirmed']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="analytics-stat-card" style="background: linear-gradient(135deg, #800020 0%, #a5002a 100%);">
                        <h6>Cancelled</h6>
                        <h3><?php echo $monthlyStats['cancelled']; ?></h3>
                    </div>
                </div>
            </div>

                        <!-- Line Chart -->
                        <div class="chart-container">
                            <canvas id="bookingsChart"></canvas>
                        </div>

                        <!-- Additional Info -->
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="bi bi-info-circle"></i> 
                            Showing booking trends for <strong><?php echo date('F Y', strtotime($monthStart)); ?></strong>. 
                            <?php if ($monthlyStats['total'] > 0): ?>
                                Average bookings per day: <strong><?php echo number_format($monthlyStats['total'] / $daysInMonth, 1); ?></strong>
                            <?php else: ?>
                                No bookings recorded for this month.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Tab -->
            <div class="tab-pane fade" id="services" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0 dashboard-section-title">Manage Services</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                <i class="bi bi-plus-circle"></i> Add Service
                            </button>
                        </div>
                        <?php if (empty($services)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-clipboard-x" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No services added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Service Name</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($service['service_type'] ?? 'General'); ?></td>
                                                <td><?php echo htmlspecialchars(substr($service['service_desc'] ?? '', 0, 50)); ?><?php echo strlen($service['service_desc'] ?? '') > 50 ? '...' : ''; ?></td>
                                                <td><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                                <td><strong>₱<?php echo number_format($service['cost'], 2); ?></strong></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" 
                                                            title="Edit Service">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this service?')">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                                        <button type="submit" name="delete_service" class="btn btn-sm btn-outline-danger" title="Delete Service">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Staff Tab -->
            <div class="tab-pane fade" id="staff" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0 dashboard-section-title">Manage Staff</h4>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                <i class="bi bi-person-plus"></i> Add Staff
                            </button>
                        </div>
                        <?php if (empty($staff)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No staff members added yet</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($staff as $member): ?>
                                    <?php 
                                        $employeeName = trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?: 'Staff Member';
                                        $specialty = $member['specialization'] ?? 'General Services';
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <?php if (isset($member['employ_img']) && !empty($member['employ_img'])): ?>
                                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($member['employ_img']); ?>" alt="<?php echo htmlspecialchars($employeeName); ?>" class="rounded-circle mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 80px; height: 80px;">
                                                        <i class="bi bi-person-fill text-white" style="font-size: 2rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <h6><?php echo htmlspecialchars($employeeName); ?></h6>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($specialty); ?></p>
                                                <span class="badge bg-<?php echo $member['employ_status'] === 'available' ? 'success' : 'secondary'; ?> mb-2">
                                                    <?php echo ucfirst($member['employ_status'] ?? 'available'); ?>
                                                </span>
                                                <div class="btn-group btn-group-sm d-block">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editStaff(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove this staff member?')">
                                                        <input type="hidden" name="employ_id" value="<?php echo $member['employ_id']; ?>">
                                                        <button type="submit" name="delete_staff" class="btn btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Tab - UPDATED WITH THREADED REPLIES -->
            <div class="tab-pane fade" id="reviews" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3 dashboard-section-title">Customer Reviews</h4>
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="bi bi-chat-square-text" style="font-size: 4rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No reviews yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['customer_fname'] . ' ' . $review['customer_lname']); ?></strong>
                                            <br><small class="text-muted"><?php echo formatDate($review['review_date']); ?></small>
                                        </div>
                                        <div class="rating" style="color: #ffc107;">
                                            <?php
                                            $rating = $review['rating'] ?? 0;
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="bi bi-star-fill"></i>';
                                                } else {
                                                    echo '<i class="bi bi-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                    
                                    <!-- Display Review Images -->
                                    <?php if (!empty($review['images'])): ?>
                                        <div class="review-images mb-3">
                                            <div class="d-flex gap-2 flex-wrap">
                                                <?php foreach ($review['images'] as $image): ?>
                                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                                         alt="Review image" 
                                                         class="review-image-thumb"
                                                         onclick="window.open('<?php echo htmlspecialchars($image); ?>', '_blank')">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Display existing replies -->
                                    <?php if (!empty($review['replies'])): ?>
                                        <div class="replies-section mt-3">
                                            <strong class="text-muted d-block mb-2">
                                                <i class="bi bi-chat-dots"></i> Conversation:
                                            </strong>
                                            <?php foreach ($review['replies'] as $reply): ?>
                                                <div class="review-reply mb-2">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <?php if ($reply['sender_type'] === 'business'): ?>
                                                            <i class="bi bi-shop text-primary"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-person-circle text-secondary"></i>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between">
                                                                <strong class="<?php echo $reply['sender_type'] === 'business' ? 'text-primary' : ''; ?>">
                                                                    <?php echo htmlspecialchars($reply['sender_name']); ?>
                                                                    <?php if ($reply['sender_type'] === 'business'): ?>
                                                                        <span class="badge bg-primary" style="font-size: 0.7rem;">You</span>
                                                                    <?php endif; ?>
                                                                </strong>
                                                                <small class="text-muted"><?php echo formatDate($reply['reply_date']); ?></small>
                                                            </div>
                                                            <p class="mb-0 mt-1"><?php echo htmlspecialchars($reply['reply_text']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Reply button - always visible -->
                                    <button class="btn btn-sm btn-outline-primary mt-2" 
                                            onclick="showReplyForm(<?php echo $review['review_id']; ?>)">
                                        <i class="bi bi-reply"></i> <?php echo !empty($review['replies']) ? 'Reply Again' : 'Reply'; ?>
                                    </button>
                                    
                                    <!-- Reply form (hidden by default) -->
                                    <form method="POST" id="replyForm<?php echo $review['review_id']; ?>" style="display: none;" class="mt-2">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <div class="input-group">
                                            <textarea class="form-control" name="reply_text" rows="2" placeholder="Write your reply..." required></textarea>
                                            <button type="submit" name="reply_review" class="btn btn-primary">
                                                <i class="bi bi-send"></i> Send
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="hideReplyForm(<?php echo $review['review_id']; ?>)">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="service_name" class="form-label">Service Name *</label>
                        <input type="text" class="form-control" id="service_name" name="service_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="service_type" class="form-label">Service Type</label>
                        <input type="text" class="form-control" id="service_type" name="service_type" placeholder="e.g., Hair, Nails, Massage">
                    </div>
                    <div class="mb-3">
                        <label for="service_desc" class="form-label">Description *</label>
                        <textarea class="form-control" id="service_desc" name="service_desc" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration" class="form-label">Duration (minutes) *</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="15" step="15" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cost" class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" class="form-control" id="cost" name="cost" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_service" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_service_id" name="service_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_service_name" class="form-label">Service Name *</label>
                        <input type="text" class="form-control" id="edit_service_name" name="service_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_service_type" class="form-label">Service Type</label>
                        <input type="text" class="form-control" id="edit_service_type" name="service_type" placeholder="e.g., Hair, Nails, Massage">
                    </div>
                    <div class="mb-3">
                        <label for="edit_service_desc" class="form-label">Description *</label>
                        <textarea class="form-control" id="edit_service_desc" name="service_desc" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_duration" class="form-label">Duration (minutes) *</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" min="15" step="15" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_cost" class="form-label">Price (₱) *</label>
                            <input type="number" step="0.01" class="form-control" id="edit_cost" name="cost" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_service" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employ_fname" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="employ_fname" name="employ_fname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="employ_lname" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="employ_lname" name="employ_lname" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization *</label>
                        <select class="form-select" id="specialization" name="specialization" required>
                            <option value="">Choose specialization...</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="General Services">General Services</option>
                        </select>
                        <small class="text-muted">Select from your services or choose General Services</small>
                    </div>
                    <div class="mb-3">
                        <label for="skills" class="form-label">Skills</label>
                        <select class="form-select" id="skills" name="skills" multiple size="4">
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple skills</small>
                    </div>
                    <div class="mb-3">
                        <label for="employ_bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="employ_bio" name="employ_bio" rows="2" placeholder="Brief description about the staff member"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_staff" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Add Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_employ_id" name="employ_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_employ_fname" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="edit_employ_fname" name="employ_fname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_employ_lname" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="edit_employ_lname" name="employ_lname" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_specialization" class="form-label">Specialization *</label>
                        <select class="form-select" id="edit_specialization" name="specialization" required>
                            <option value="">Choose specialization...</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="General Services">General Services</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_skills" class="form-label">Skills</label>
                        <select class="form-select" id="edit_skills" name="skills" multiple size="4">
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Cmd on Mac) to select multiple skills</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_employ_bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="edit_employ_bio" name="employ_bio" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_employ_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_employ_status" name="employ_status" required>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="on_leave">On Leave</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_staff" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Edit Service Function
function editService(service) {
    document.getElementById('edit_service_id').value = service.service_id;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_service_type').value = service.service_type || '';
    document.getElementById('edit_service_desc').value = service.service_desc;
    document.getElementById('edit_duration').value = service.duration;
    document.getElementById('edit_cost').value = service.cost;
    
    var editModal = new bootstrap.Modal(document.getElementById('editServiceModal'));
    editModal.show();
}

// Edit Staff Function
function editStaff(member) {
    document.getElementById('edit_employ_id').value = member.employ_id;
    document.getElementById('edit_employ_fname').value = member.employ_fname;
    document.getElementById('edit_employ_lname').value = member.employ_lname;
    document.getElementById('edit_specialization').value = member.specialization || '';
    document.getElementById('edit_employ_bio').value = member.employ_bio || '';
    document.getElementById('edit_employ_status').value = member.employ_status || 'available';
    
    // Handle skills multi-select
    const skillsSelect = document.getElementById('edit_skills');
    const skills = (member.skills || '').split(',').map(s => s.trim());
    for (let option of skillsSelect.options) {
        option.selected = skills.includes(option.value);
    }
    
    var editModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
    editModal.show();
}

// Show/Hide Reply Form
function showReplyForm(reviewId) {
    document.getElementById('replyForm' + reviewId).style.display = 'block';
}

function hideReplyForm(reviewId) {
    document.getElementById('replyForm' + reviewId).style.display = 'none';
}

// Initialize Chart
// Initialize Chart with better loading handling
const ctx = document.getElementById('bookingsChart');
let bookingsChart = null;

// Pre-define chart dimensions to prevent jumping
if (ctx) {
    // Set fixed dimensions
    ctx.style.width = '100%';
    ctx.style.height = '300px';
    
    // Small delay to ensure DOM is fully ready
    setTimeout(() => {
        bookingsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $chartLabels; ?>,
                datasets: [{
                    label: 'Total Bookings',
                    data: <?php echo $chartData; ?>,
                    borderColor: '#800020',
                    backgroundColor: 'rgba(128, 0, 32, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#800020',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Daily Booking Trends',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Bookings: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 15
                        }
                    }
                }
            }
        });
    }, 100);
}

// Month selector change handler
document.getElementById('monthSelect').addEventListener('change', function() {
    const selectedMonth = this.value;
    window.location.href = 'business-dashboard.php?month=' + selectedMonth + '#analytics';
});

// Handle multi-select for skills (convert to comma-separated string)
document.addEventListener('DOMContentLoaded', function() {
    // Handle skills multi-select for Add Staff form
    const addSkillsSelect = document.getElementById('skills');
    const addStaffForm = document.getElementById('addStaffModal').querySelector('form');
    
    if (addStaffForm) {
        addStaffForm.addEventListener('submit', function(e) {
            if (addSkillsSelect) {
                // Get all selected options
                const selectedOptions = Array.from(addSkillsSelect.selectedOptions);
                const selectedValues = selectedOptions.map(option => option.value);
                
                // Remove the multi-select from form submission
                addSkillsSelect.name = '';
                
                // Add hidden inputs for each selected skill
                selectedValues.forEach(value => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'skills[]';
                    hiddenInput.value = value;
                    addStaffForm.appendChild(hiddenInput);
                });
            }
        });
    }
    
    // Handle skills multi-select for Edit Staff form
    const editSkillsSelect = document.getElementById('edit_skills');
    const editStaffForm = document.getElementById('editStaffModal').querySelector('form');
    
    if (editStaffForm) {
        editStaffForm.addEventListener('submit', function(e) {
            if (editSkillsSelect) {
                // Get all selected options
                const selectedOptions = Array.from(editSkillsSelect.selectedOptions);
                const selectedValues = selectedOptions.map(option => option.value);
                
                // Remove the multi-select from form submission
                editSkillsSelect.name = '';
                
                // Add hidden inputs for each selected skill
                selectedValues.forEach(value => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'skills[]';
                    hiddenInput.value = value;
                    editStaffForm.appendChild(hiddenInput);
                });
            }
        });
    }
    
    // Activate analytics tab if hash is present
    if (window.location.hash === '#analytics') {
        const analyticsTab = document.getElementById('analytics-tab');
        if (analyticsTab) {
            const tab = new bootstrap.Tab(analyticsTab);
            tab.show();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>