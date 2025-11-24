<?php 

require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_customers.php';
require_once 'backend/function_businesses.php';
require_once 'backend/function_services.php';
require_once 'backend/function_employees.php';
require_once 'backend/function_appointments.php';
require_once 'backend/function_notifications.php';
require_once 'backend/function_favorites.php';

$businessId = $_GET['business_id'] ?? '';

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
$staff = getBusinessEmployees($businessId);
$user = getCurrentCustomer();

// Handle booking submission with multiple services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $serviceIds = $_POST['service_ids'] ?? [];
    
    if (empty($serviceIds)) {
        $_SESSION['error'] = 'Please select at least one service.';
    } else {
        $appointDate = sanitize($_POST['date']) . ' ' . sanitize($_POST['time']) . ':00';
        $successCount = 0;
        $failureCount = 0;
        
        $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
        $employId = isset($_POST['employ_id']) && !empty($_POST['employ_id']) ? sanitize($_POST['employ_id']) : null;
        
        // Create separate appointments for each service
        foreach ($serviceIds as $serviceId) {
            $service = getServiceById($serviceId);
            if (!$service) {
                error_log("Invalid service_id: " . $serviceId);
                continue;
            }
            
            $appointmentData = [
                'customer_id' => $user['customer_id'],
                'service_id' => intval($serviceId), 
                'employ_id' => $employId,
                'appoint_date' => $appointDate,
                'appoint_status' => 'pending',
                'appoint_desc' => $notes
            ];
            
            $result = createAppointment($appointmentData);
            
            if (is_array($result) && isset($result['error'])) {
                // Time slot unavailable
                $failureCount++;
                $_SESSION['error'] = 'The selected time slot is no longer available. Please choose another time.';
            } elseif ($result) {
                $successCount++;
            } else {
                $failureCount++;
                error_log("Failed to create appointment for service_id: " . $serviceId);
            }
        }
        
        if ($successCount > 0 && $failureCount === 0) {
            $_SESSION['success'] = 'Appointment' . ($successCount > 1 ? 's' : '') . ' submitted successfully! Waiting for confirmation.';
            header('Location: my-bookings.php');
            exit;
        } elseif ($successCount > 0 && $failureCount > 0) {
            $_SESSION['warning'] = "Some appointments were created, but {$failureCount} failed. The time slot may have been booked by someone else.";
            header('Location: my-bookings.php');
            exit;
        } else {
            if (!isset($_SESSION['error'])) {
                $_SESSION['error'] = 'Booking failed. The time slot may be unavailable or already booked.';
            }
        }
    }
}

$pageTitle = 'Book Appointment - ' . $business['business_name'];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/booking.css">
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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
                                    <div class="services-container">
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
                                                                    <i class="bi bi-clock"></i> <?php echo htmlspecialchars($service['duration']); ?> min
                                                                </small>
                                                            </div>
                                                            <strong style="color: var(--color-burgundy); font-size: 1.2rem;">₱<?php echo number_format($service['cost'], 2); ?></strong>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Select Staff (Optional) -->
                            <?php if (!empty($staff)): ?>
                                <div class="mb-4">
                                    <label class="form-label">Select Staff (Optional)</label>
                                    <select class="form-select" name="employ_id" id="employ_id" onchange="selectStaff()">
                                        <option value="">Any Available Staff</option>
                                        <?php foreach ($staff as $member): ?>
                                            <?php 
                                                $employeeName = $member['employee_name'] ?? 
                                                              trim(($member['employ_fname'] ?? '') . ' ' . ($member['employ_lname'] ?? '')) ?? 
                                                              'Staff Member';
                                                $employeeId = $member['employ_id'] ?? '';
                                                $specialty = $member['specialization'] ?? 'General Services';
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
                                        type="text" 
                                        class="form-control" 
                                        id="date" 
                                        name="date" 
                                        placeholder="Click to select date"
                                        required 
                                        readonly>
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle"></i> Dates shown in red are fully booked
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <label for="time" class="form-label">
                                        <i class="bi bi-clock"></i> Select Time *
                                    </label>
                                    <select class="form-select" id="time" name="time" required disabled>
                                        <option value="">Please select a date first</option>
                                    </select>
                                    <small class="text-muted" id="timeHelp">
                                        <i class="bi bi-info-circle"></i> Select date and services to see available times
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Additional Notes -->
                            <div class="mb-4">
                                <label for="notes" class="form-label">
                                    <i class="bi bi-chat-left-text"></i> Additional Notes
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special requests or requirements..."></textarea>
                            </div>
                            
                            <!-- Booking Summary -->
                            <div class="card booking-summary-card">
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
                                                <span><?php echo htmlspecialchars($user['fname']); ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <strong style="color: var(--color-burgundy);">Contact:</strong><br>
                                                <span><?php echo htmlspecialchars($user['cstmr_num'] ?? 'N/A'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr style="border-color: var(--color-burgundy); opacity: 0.3;">
                                    
                                    <div id="summary" class="text-muted">
                                        <small><i class="bi bi-info-circle"></i> Select services to see booking details</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mt-3">
                                <button type="submit" name="submit_booking" class="btn btn-primary btn-lg">
                                    <i class="bi bi-calendar-check"></i> Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <!-- Customer Info Card -->
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3" style="color: var(--color-burgundy);">
                                <i class="bi bi-person-circle"></i> Your Information
                            </h6>
                            <div class="mb-2">
                                <i class="bi bi-person"></i> <strong>Name:</strong><br>
                                <span class="ms-4"><?php echo htmlspecialchars($user['fname']); ?></span>
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-telephone"></i> <strong>Phone:</strong><br>
                                <span class="ms-4"><?php echo htmlspecialchars($user['cstmr_num'] ?? 'N/A'); ?></span>
                            </div>
                            <div>
                                <i class="bi bi-envelope"></i> <strong>Email:</strong><br>
                                <span class="ms-4"><?php echo htmlspecialchars($user['cstmr_email']); ?></span>
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
    </div>
</main>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Global state
let availableSlots = [];
let flatpickrInstance = null;
let fullyBookedDates = [];

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
    
    // Refresh calendar and time slots when services change
    loadFullyBookedDates();
    checkAvailability();
    updateSummary();
}

function selectStaff() {
    const select = document.getElementById('employ_id');
    const selectedOption = select.options[select.selectedIndex];
    const staffName = selectedOption.getAttribute('data-name') || 'Any Available';
    document.getElementById('staff_name').value = staffName;
    
    // Refresh calendar and time slots when staff changes
    loadFullyBookedDates();
    checkAvailability();
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
            summary += '<small class="text-muted"><i class="bi bi-clock"></i> ' + duration + ' min</small><br>';
            summary += '<span style="color: var(--color-burgundy); font-weight: 600;">₱' + price.toFixed(2) + '</span>';
            summary += '</li>';
        });
        
        summary += '</ul></div>';
        
        summary += '<div class="mb-2"><strong style="color: var(--color-burgundy);">Staff:</strong> ' + staffName + '</div>';
        
        if (date) {
            const dateObj = new Date(date + 'T00:00:00');
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

// Load fully booked dates from server
async function loadFullyBookedDates() {
    const businessId = <?php echo $businessId; ?>;
    const employId = document.getElementById('employ_id')?.value || '';
    
    try {
        const url = `ajax/get_available_slots.php?action=get_booked_dates&business_id=${businessId}&employ_id=${employId}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            fullyBookedDates = data.fully_booked_dates || [];
            
            // Update Flatpickr to show fully booked dates
            if (flatpickrInstance) {
                flatpickrInstance.set('disable', [
                    function(date) {
                        const dateStr = date.toISOString().split('T')[0];
                        return fullyBookedDates.includes(dateStr);
                    }
                ]);
                flatpickrInstance.redraw();
            }
        }
    } catch (error) {
        console.error('Error loading booked dates:', error);
    }
}

// Check availability when date, services, or staff changes
async function checkAvailability() {
    const date = document.getElementById('date').value;
    const employId = document.getElementById('employ_id')?.value || '';
    const businessId = <?php echo $businessId; ?>;
    
    if (!date) {
        const timeSelect = document.getElementById('time');
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Please select a date first</option>';
        return;
    }
    
    // Get selected service IDs
    const checkboxes = document.querySelectorAll('input[name="service_ids[]"]:checked');
    const serviceIds = Array.from(checkboxes).map(cb => cb.value).join(',');
    
    // Show loading state
    const timeSelect = document.getElementById('time');
    timeSelect.disabled = true;
    timeSelect.innerHTML = '<option value="">Loading available times...</option>';
    
    try {
        const url = `ajax/get_available_slots.php?business_id=${businessId}&date=${date}&employ_id=${employId}&service_ids=${serviceIds}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            availableSlots = data.slots;
            updateTimeSlots();
        } else {
            console.error('Error fetching slots:', data.error);
            timeSelect.innerHTML = '<option value="">Error loading times. Please try again.</option>';
        }
    } catch (error) {
        console.error('Error:', error);
        timeSelect.innerHTML = '<option value="">Error loading times. Please try again.</option>';
    }
    
    timeSelect.disabled = false;
}

// Update time slots dropdown based on availability
function updateTimeSlots() {
    const timeSelect = document.getElementById('time');
    const currentValue = timeSelect.value;
    
    timeSelect.innerHTML = '<option value="">Choose time...</option>';
    
    let hasAvailableSlots = false;
    
    availableSlots.forEach(slot => {
        const option = document.createElement('option');
        option.value = slot.time;
        
        if (!slot.available) {
            option.disabled = true;
            option.textContent = slot.display + ' (Fully Booked)';
            option.style.color = '#999';
            option.style.backgroundColor = '#f8d7da';
            option.style.textDecoration = 'line-through';
        } else {
            option.textContent = slot.display;
            hasAvailableSlots = true;
        }
        
        timeSelect.appendChild(option);
    });
    
    // Update help text
    const helpText = document.getElementById('timeHelp');
    if (!hasAvailableSlots) {
        helpText.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> All time slots are booked for this date. Please select another date.';
        helpText.className = 'text-danger';
    } else {
        helpText.innerHTML = '<i class="bi bi-info-circle"></i> Grey slots are already booked';
        helpText.className = 'text-muted';
    }
    
    // Restore previously selected value if still available
    if (currentValue) {
        const previousOption = timeSelect.querySelector(`option[value="${currentValue}"]`);
        if (previousOption && !previousOption.disabled) {
            timeSelect.value = currentValue;
        }
    }
}

// Initialize Flatpickr calendar
function initializeDatePicker() {
    const dateInput = document.getElementById('date');
    
    flatpickrInstance = flatpickr(dateInput, {
        minDate: 'today',
        maxDate: new Date().fp_incr(90), // 90 days ahead
        dateFormat: 'Y-m-d',
        disableMobile: true,
        onChange: function(selectedDates, dateStr, instance) {
            checkAvailability();
            updateSummary();
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const dateStr = dayElem.dateObj.toISOString().split('T')[0];
            
            // Mark fully booked dates in red
            if (fullyBookedDates.includes(dateStr)) {
                dayElem.style.backgroundColor = '#f8d7da';
                dayElem.style.color = '#721c24';
                dayElem.style.fontWeight = 'bold';
                dayElem.title = 'Fully Booked';
            }
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateServiceSelection();
    initializeDatePicker();
    loadFullyBookedDates();
    
    // Add event listener to time picker
    document.getElementById('time').addEventListener('change', updateSummary);
});

// Validate form before submission
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const timeSelect = document.getElementById('time');
    const selectedOption = timeSelect.options[timeSelect.selectedIndex];
    
    if (!selectedOption || selectedOption.disabled || !selectedOption.value) {
        e.preventDefault();
        alert('Please select an available time slot. The selected slot may be fully booked.');
        return false;
    }
    
    const date = document.getElementById('date').value;
    if (fullyBookedDates.includes(date)) {
        e.preventDefault();
        alert('The selected date is fully booked. Please choose another date.');
        return false;
    }
});
</script>

<style>
/* Flatpickr custom styles */
.flatpickr-day.disabled {
    color: #999 !important;
    background-color: #f8d7da !important;
    cursor: not-allowed !important;
}

/* Style for unavailable time slots */
#time option:disabled {
    color: #999 !important;
    background-color: #f8d7da !important;
    text-decoration: line-through;
    font-style: italic;
}

/* Loading state for time select */
#time:disabled {
    background-color: #f8f9fa;
    cursor: wait;
}

/* Highlight fully booked dates in calendar */
.flatpickr-calendar .flatpickr-day.fully-booked {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    font-weight: bold;
}
</style>

<?php include 'includes/footer.php'; ?>