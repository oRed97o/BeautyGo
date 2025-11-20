    <!-- Footer -->
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
                        <li><a href="services.php" class="text-muted">Browse Services</a></li>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="script.js"></script>
</body>
</html>
