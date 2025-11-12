<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isCustomerLoggedIn()) {
    $_SESSION['error'] = 'Please login to make a booking';
    header('Location: login.php');
    exit;
}

$businessId = $_GET['business_id'] ?? '';
if (empty($businessId)) {
    header('Location: index.php');
    exit;
}

$business = getBusinessById($businessId);

if (!$business) {
    header('Location: index.php');
    exit;
}

$services = getBusinessServices($businessId);
$staff = getBusinessStaff($businessId);
$user = getCurrentUser();

// Handle booking submission with multiple services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $serviceIds = $_POST['service_ids'] ?? [];
    
    if (empty($serviceIds)) {
        $_SESSION['error'] = 'Please select at least one service.';
    } else {
        $appointDate = sanitize($_POST['date']) . ' ' . sanitize($_POST['time']) . ':00';
        $successCount = 0;
        
        foreach ($serviceIds as $serviceId) {
            $appointmentData = [
                'customer_id' => $user['customer_id'] ?? $user['id'],
                'business_id' => $businessId,
                'service_id' => sanitize($serviceId),
                'staff_id' => sanitize($_POST['staff_id']) ?: null,
                'appoint_date' => $appointDate,
                'appoint_status' => 'pending',
                'appoint_desc' => sanitize($_POST['notes'])
            ];
            
            if (createAppointment($appointmentData)) {
                $successCount++;
            }
        }
        
        if ($successCount > 0) {
            $_SESSION['success'] = 'Appointment' . ($successCount > 1 ? 's' : '') . ' submitted successfully! Waiting for confirmation.';
            header('Location: my-bookings.php');
        } else {
            $_SESSION['error'] = 'Booking failed. Please try again.';
        }
        exit;
    }
}

$pageTitle = 'Book Appointment - ' . $business['business_name'];
include 'includes/header.php';
?>

<style>
/* Pretty calendar styling */
input[type="date"] {
    position: relative;
    padding: 12px 16px;
    font-size: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s ease;
    background-color: white;
    cursor: pointer;
}

input[type="date"]:hover {
    border-color: var(--color-rose);
}

input[type="date"]:focus {
    outline: none;
    border-color: var(--color-burgundy);
    box-shadow: 0 0 0 3px rgba(133, 14, 53, 0.1);
}

/* Calendar icon color */
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(23%) sepia(60%) saturate(2066%) hue-rotate(314deg) brightness(89%) contrast(92%);
}

/* Service checkbox styling */
.service-checkbox-card {
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    background-color: white;
}

.service-checkbox-card:hover {
    border-color: var(--color-rose);
    background-color: var(--color-cream);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(133, 14, 53, 0.1);
}

.service-checkbox-card.selected {
    border-color: var(--color-burgundy);
    background-color: var(--color-cream);
    box-shadow: 0 4px 12px rgba(133, 14, 53, 0.15);
}

.service-checkbox-card input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--color-burgundy);
}

/* Back button styling */
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

.back-button i {
    font-size: 1.2rem;
}

/* Booking summary styling */
.booking-summary-card {
    background: linear-gradient(135deg, #FFF5E4 0%, #FFC4C4 50%, #EE6983 100%);
    border: none;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(133, 14, 53, 0.15);
    margin-top: 30px;
}

.booking-summary-card .card-body {
    padding: 25px;
}

.summary-total {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-burgundy);
}

/* Time select styling */
.form-select {
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.form-select:hover {
    border-color: var(--color-rose);
}

.form-select:focus {
    border-color: var(--color-burgundy);
    box-shadow: 0 0 0 3px rgba(133, 14, 53, 0.1);
}

/* Enhanced form controls */
.form-control:focus, .form-select:focus {
    border-color: var(--color-burgundy);
    box-shadow: 0 0 0 3px rgba(133, 14, 53, 0.1);
}

textarea.form-control {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.3s ease;
}

textarea.form-control:hover {
    border-color: var(--color-rose);
}

/* Selected services badge */
.selected-count-badge {
    display: inline-block;
    background-color: var(--color-burgundy);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-left: 8px;
}
</style>

<main>
    <div class="container my-4">
        <!-- Back Button -->
        <a href="business-detail.php?id=<?php echo $businessId; ?>" class="back-button">
            <i class="bi bi-arrow-left-circle"></i>
            <span>Back to Business Details</span>
        </a>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-4">Book Appointment at <?php echo htmlspecialchars($business['business_name']); ?></h2>
                        
                        <form action="" method="POST" id="bookingForm">
                            <!-- Select Services (Multiple) -->
                            <div class="mb-4">
                                <label class="form-label">
                                    Select Services *
                                    <span id="selectedCountBadge" class="selected-count-badge" style="display: none;">0 selected</span>
                                </label>
                                <?php if (empty($services)): ?>
                                    <div class="alert alert-warning">
                                        No services available. Please contact the business directly.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($services as $service): ?>
                                        <div class="service-checkbox-card" onclick="toggleServiceCard(this)">
                                            <div class="d-flex align-items-start gap-3">
                                                <input 
                                                    class="form-check-input mt-1" 
                                                    type="checkbox" 
                                                    name="service_ids[]" 
                                                    id="service_<?php echo $service['service_id']; ?>" 
                                                    value="<?php echo $service['service_id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($service['service_name']); ?>"
                                                    data-price="<?php echo $service['cost']; ?>"
                                                    data-duration="<?php echo htmlspecialchars($service['duration']); ?>"
                                                    onchange="updateServiceSelection()"
                                                    onclick="event.stopPropagation()">
                                                <label class="flex-grow-1" for="service_<?php echo $service['service_id']; ?>" style="cursor: pointer;">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                                            <?php if (!empty($service['service_desc'])): ?>
                                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($service['service_desc']); ?></p>
                                                            <?php endif; ?>
                                                            <small class="text-muted">
                                                                <i class="bi bi-clock"></i> <?php echo htmlspecialchars($service['duration']); ?>
                                                            </small>
                                                        </div>
                                                        <strong style="color: var(--color-burgundy); font-size: 1.2rem;">₱<?php echo number_format($service['cost'], 2); ?></strong>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Select Staff (Optional) -->
                            <?php if (!empty($staff)): ?>
                                <div class="mb-4">
                                    <label class="form-label">Select Staff (Optional)</label>
                                    <select class="form-select" name="staff_id" id="staff_id" onchange="selectStaff()">
                                        <option value="">Any Available Staff</option>
                                        <?php foreach ($staff as $member): ?>
                                            <?php 
                                                // Support both old and new schema
                                                $employeeName = $member['employee_name'] ?? 
                                                              trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?? 
                                                              'Staff Member';
                                                $employeeId = $member['employee_id'] ?? $member['employ_id'] ?? '';
                                                $specialty = $member['specialization'] ?? $member['skills'] ?? 'General Services';
                                            ?>
                                            <option value="<?php echo htmlspecialchars($employeeId); ?>" data-name="<?php echo htmlspecialchars($employeeName); ?>">
                                                <?php echo htmlspecialchars($employeeName); ?> - <?php echo htmlspecialchars($specialty); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="staff_name" id="staff_name" value="Any Available">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Select Date & Time -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="date" class="form-label">
                                        <i class="bi bi-calendar3"></i> Select Date *
                                    </label>
                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        id="date" 
                                        name="date" 
                                        min="<?php echo date('Y-m-d'); ?>" 
                                        required 
                                        onchange="updateSummary()">
                                </div>
                                <div class="col-md-6">
                                    <label for="time" class="form-label">
                                        <i class="bi bi-clock"></i> Select Time *
                                    </label>
                                    <select class="form-select" id="time" name="time" required onchange="updateSummary()">
                                        <option value="">Choose time...</option>
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="12:00">12:00 PM</option>
                                        <option value="13:00">1:00 PM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                        <option value="17:00">5:00 PM</option>
                                        <option value="18:00">6:00 PM</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Additional Notes -->
                            <div class="mb-4">
                                <label for="notes" class="form-label">
                                    <i class="bi bi-chat-left-text"></i> Additional Notes
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special requests or requirements..."></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="submit_booking" class="btn btn-primary btn-lg">
                                    <i class="bi bi-calendar-check"></i> Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Booking Summary (Below Form) -->
                <div class="booking-summary-card card">
                    <div class="card-body">
                        <h5 class="mb-3" style="color: var(--color-burgundy);">
                            <i class="bi bi-receipt"></i> Booking Summary
                        </h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <strong style="color: var(--color-burgundy);">Business:</strong><br>
                                    <span><?php echo htmlspecialchars($business['business_name']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <strong style="color: var(--color-burgundy);">Location:</strong><br>
                                    <span><?php echo htmlspecialchars($business['business_address'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <strong style="color: var(--color-burgundy);">Your Name:</strong><br>
                                    <span><?php echo htmlspecialchars($user['name']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <strong style="color: var(--color-burgundy);">Contact:</strong><br>
                                    <span><?php echo htmlspecialchars($user['celler_num'] ?? $user['phone'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <hr style="border-color: var(--color-burgundy); opacity: 0.3;">
                        
                        <div id="summary" class="text-muted">
                            <small><i class="bi bi-info-circle"></i> Select services to see booking details</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Customer Info Card -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3" style="color: var(--color-burgundy);">
                            <i class="bi bi-person-circle"></i> Your Information
                        </h6>
                        <div class="mb-2">
                            <i class="bi bi-person"></i> <strong>Name:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-telephone"></i> <strong>Phone:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($user['celler_num'] ?? $user['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div>
                            <i class="bi bi-envelope"></i> <strong>Email:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Business Info Card -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3" style="color: var(--color-burgundy);">
                            <i class="bi bi-shop"></i> Business Information
                        </h6>
                        <div class="mb-2">
                            <strong>Type:</strong> <?php echo htmlspecialchars($business['business_type'] ?? 'N/A'); ?>
                        </div>
                        <?php if (!empty($business['business_contact_num'])): ?>
                        <div class="mb-2">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($business['business_contact_num']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($business['business_email'])): ?>
                        <div>
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($business['business_email']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Toggle service card selection visually
function toggleServiceCard(card) {
    const checkbox = card.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    updateServiceSelection();
}

// Update selected count and visual state
function updateServiceSelection() {
    const checkboxes = document.querySelectorAll('input[name="service_ids[]"]');
    let selectedCount = 0;
    
    checkboxes.forEach(checkbox => {
        const card = checkbox.closest('.service-checkbox-card');
        if (checkbox.checked) {
            card.classList.add('selected');
            selectedCount++;
        } else {
            card.classList.remove('selected');
        }
    });
    
    // Update badge
    const badge = document.getElementById('selectedCountBadge');
    if (selectedCount > 0) {
        badge.textContent = selectedCount + ' selected';
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
    
    updateSummary();
}

function selectStaff() {
    const select = document.getElementById('staff_id');
    const selectedOption = select.options[select.selectedIndex];
    const staffName = selectedOption.getAttribute('data-name') || 'Any Available';
    document.getElementById('staff_name').value = staffName;
    updateSummary();
}

function updateSummary() {
    const checkboxes = document.querySelectorAll('input[name="service_ids[]"]:checked');
    const staffName = document.getElementById('staff_name').value;
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    
    let summary = '';
    let totalPrice = 0;
    
    if (checkboxes.length > 0) {
        summary += '<div class="mb-3">';
        summary += '<strong style="color: var(--color-burgundy); font-size: 1.1rem;">Selected Services:</strong>';
        summary += '<ul class="mt-2 mb-0" style="list-style: none; padding-left: 0;">';
        
        checkboxes.forEach(checkbox => {
            const serviceName = checkbox.getAttribute('data-name');
            const price = parseFloat(checkbox.getAttribute('data-price'));
            const duration = checkbox.getAttribute('data-duration');
            totalPrice += price;
            
            summary += '<li class="mb-2" style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid var(--color-burgundy);">';
            summary += '<strong>' + serviceName + '</strong><br>';
            summary += '<small class="text-muted"><i class="bi bi-clock"></i> ' + duration + '</small><br>';
            summary += '<span style="color: var(--color-burgundy); font-weight: 600;">₱' + price.toFixed(2) + '</span>';
            summary += '</li>';
        });
        
        summary += '</ul></div>';
        
        summary += '<div class="mb-2"><strong style="color: var(--color-burgundy);">Staff:</strong> ' + staffName + '</div>';
        
        if (date) {
            const dateObj = new Date(date);
            const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            summary += '<div class="mb-2"><strong style="color: var(--color-burgundy);">Date:</strong> ' + dateStr + '</div>';
        }
        
        if (time) {
            const timeObj = new Date('2000-01-01 ' + time);
            const timeStr = timeObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            summary += '<div class="mb-2"><strong style="color: var(--color-burgundy);">Time:</strong> ' + timeStr + '</div>';
        }
        
        summary += '<hr style="border-color: var(--color-burgundy); opacity: 0.3;">';
        summary += '<div class="text-center">';
        summary += '<div><strong style="color: var(--color-burgundy);">Total Amount:</strong></div>';
        summary += '<div class="summary-total">₱' + totalPrice.toFixed(2) + '</div>';
        summary += '</div>';
    } else {
        summary = '<small class="text-muted"><i class="bi bi-info-circle"></i> Select services to see booking details</small>';
    }
    
    document.getElementById('summary').innerHTML = summary;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateServiceSelection();
});
</script>

<?php include 'includes/footer.php'; ?>
