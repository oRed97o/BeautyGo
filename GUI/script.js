// BeautyGo JavaScript

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}

// Image preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Format currency
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2);
}

// Validate form
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
}

// Filter businesses
function filterBusinesses() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const items = document.querySelectorAll('.business-listing-item');
    
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const selectedType = typeFilter ? typeFilter.value : '';
    
    items.forEach(item => {
        const businessName = item.querySelector('.airbnb-card-title') ? item.querySelector('.airbnb-card-title').textContent.toLowerCase() : '';
        const businessType = item.dataset.type || '';
        const businessSubtitle = item.querySelector('.airbnb-card-subtitle') ? item.querySelector('.airbnb-card-subtitle').textContent.toLowerCase() : '';
        
        const matchesSearch = businessName.includes(searchTerm) || businessSubtitle.includes(searchTerm);
        const matchesType = !selectedType || businessType === selectedType;
        
        if (matchesSearch && matchesType) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Toggle favorite
function toggleFavorite(businessId) {
    // Get favorites from localStorage
    let favorites = JSON.parse(localStorage.getItem('beautygo_favorites') || '[]');
    
    const index = favorites.indexOf(businessId);
    const btn = event.currentTarget;
    
    if (index > -1) {
        // Remove from favorites
        favorites.splice(index, 1);
        btn.classList.remove('active');
        btn.innerHTML = '<i class="bi bi-heart"></i>';
    } else {
        // Add to favorites
        favorites.push(businessId);
        btn.classList.add('active');
        btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
    }
    
    localStorage.setItem('beautygo_favorites', JSON.stringify(favorites));
}

// Load favorites on page load
document.addEventListener('DOMContentLoaded', function() {
    const favorites = JSON.parse(localStorage.getItem('beautygo_favorites') || '[]');
    
    document.querySelectorAll('.airbnb-favorite-btn').forEach(btn => {
        const card = btn.closest('[data-business-id]');
        if (card) {
            const businessId = parseInt(card.dataset.businessId);
            if (favorites.includes(businessId)) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
            }
        }
    });
});

// Rating stars
function renderStars(rating, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="bi bi-star-fill text-warning"></i>';
        } else if (i - 0.5 <= rating) {
            stars += '<i class="bi bi-star-half text-warning"></i>';
        } else {
            stars += '<i class="bi bi-star text-warning"></i>';
        }
    }
    container.innerHTML = stars;
}

// Set rating (for forms)
function setRating(rating, inputId) {
    document.getElementById(inputId).value = rating;
    const stars = document.querySelectorAll(`[data-rating-input="${inputId}"] i`);
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('bi-star');
            star.classList.add('bi-star-fill');
        } else {
            star.classList.remove('bi-star-fill');
            star.classList.add('bi-star');
        }
    });
}

// Date picker validation
function validateDate(dateInput) {
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        alert('Please select a future date');
        dateInput.value = '';
        return false;
    }
    return true;
}

// Time slot availability (mock)
function checkTimeSlotAvailability(date, time, businessId) {
    // In a real application, this would make an AJAX call to check availability
    return true;
}

// Initialize date inputs to today's date minimum
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (!input.hasAttribute('min')) {
            input.setAttribute('min', today);
        }
    });
});

// Booking confirmation
function confirmBooking(businessName, serviceName, date, time) {
    return confirm(`Confirm booking at ${businessName} for ${serviceName} on ${date} at ${time}?`);
}

// Toggle password visibility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Calculate service total
function calculateTotal() {
    const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
    let total = 0;
    
    checkboxes.forEach(checkbox => {
        total += parseFloat(checkbox.dataset.price || 0);
    });
    
    const totalElement = document.getElementById('totalAmount');
    if (totalElement) {
        totalElement.textContent = formatCurrency(total);
    }
}

// Update appointment status (updated for new schema)
function updateAppointmentStatus(appointmentId, status) {
    if (confirm(`Are you sure you want to ${status} this appointment?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update-booking.php';
        
        const appointmentInput = document.createElement('input');
        appointmentInput.type = 'hidden';
        appointmentInput.name = 'appointment_id';
        appointmentInput.value = appointmentId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        form.appendChild(appointmentInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Legacy function name for backwards compatibility
function updateBookingStatus(id, status) {
    updateAppointmentStatus(id, status);
}

// Print function
function printPage() {
    window.print();
}

// Export data (simple CSV export)
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => rowData.push(col.textContent));
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', filename);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
