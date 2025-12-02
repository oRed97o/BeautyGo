    <footer class="footer mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="brand-logo"><i class="bi bi-stars"></i> BeautyGo</h5>
                    <p class="text-muted">Your digital hub for beauty services in Nasugbu, Batangas</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Home</a></li>
                        <?php if (isCustomerLoggedIn()): ?>
                            <li><a href="my-bookings.php" class="text-muted">My Bookings</a></li>
                            <li><a href="favorites.php" class="text-muted">Favorites</a></li>
                        <?php endif; ?>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="register-business.php" class="text-muted">Register Your Business</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6>Contact</h6>
                    <p class="text-muted mb-1"><i class="bi bi-geo-alt"></i> Nasugbu, Batangas</p>
                    <p class="text-muted mb-1"><i class="bi bi-envelope"></i> info@beautygo.com</p>
                    <p class="text-muted"><i class="bi bi-phone"></i> +63 123 456 7890</p>
                </div>
            </div>
            <hr>
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> BeautyGo. All rights reserved.</small>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap Bundle with Popper (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Initialize Bootstrap Dropdowns -->
    <script>
        // Ensure all dropdowns are properly initialized
        document.addEventListener('DOMContentLoaded', function() {
            // Get all dropdown toggle elements
            const dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            
            // Initialize each dropdown
            const dropdownList = Array.from(dropdownElementList).map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl, {
                    autoClose: true,
                    boundary: 'viewport'
                });
            });
            
            // Debug log
            console.log('✅ Bootstrap dropdowns initialized:', dropdownList.length);
            
            // Fix for dropdowns not closing when clicking outside
            document.addEventListener('click', function(event) {
                const dropdowns = document.querySelectorAll('.dropdown-menu.show');
                dropdowns.forEach(dropdown => {
                    if (!dropdown.contains(event.target) && !dropdown.previousElementSibling.contains(event.target)) {
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }
                    }
                });
            });
            
            // Auto-dismiss success and error alerts after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            const errorAlert = document.querySelector('.alert-danger');
            
            const dismissAlert = function(alert) {
                if (alert) {
                    setTimeout(function() {
                        // Create a Bootstrap Alert instance and close it
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 5000); // 5 seconds
                }
            };
            
            // Dismiss both alerts
            dismissAlert(successAlert);
            dismissAlert(errorAlert);
        });
    </script>
    
    <!-- Custom JavaScript -->
    <script src="script.js"></script>
</body>
</html>