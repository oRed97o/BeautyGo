<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'BeautyGo - Beauty Services in Nasugbu, Batangas'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand brand-logo-new" href="index.php">
                <i class="bi bi-stars"></i> <span>BeautyGo</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <?php if (!isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link nav-link-new" href="register-business.php">
                                <i class="bi bi-shop"></i> List Your Business
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isCustomerLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link nav-link-new" href="my-bookings.php">
                                <i class="bi bi-calendar-check"></i> My Bookings
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php 
                        // Get appropriate user data based on login type
                        if (isBusinessLoggedIn()) {
                            $currentUser = getCurrentBusiness();
                            $displayName = $currentUser['business_name'] ?? 'Business';
                        } else {
                            $currentUser = getCurrentCustomer();
                            $displayName = $currentUser['fname'] ?? 'User';
                        }
                        ?>
                        <?php if (isBusinessLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="businessDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-buildings"></i> <?php echo htmlspecialchars($displayName); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="business-dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="business-profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="auth.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="logout">
                                            <button type="submit" class="dropdown-item">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($displayName); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="user-profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="my-bookings.php">My Bookings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="auth.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="logout">
                                            <button type="submit" class="dropdown-item">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-burgundy me-2" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-burgundy" href="register-user.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>