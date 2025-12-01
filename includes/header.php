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
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    
    <style>
        /* Enhanced BeautyGo Logo Styling */
        .navbar-brand.brand-logo-new {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar-brand.brand-logo-new i {
            font-size: 1.8rem;
            transition: all 0.3s ease;
        }
        
        .navbar-brand.brand-logo-new span {
            transition: all 0.3s ease;
        }
        
        .navbar-brand.brand-logo-new:hover {
            color: var(--color-rose);
            transform: translateY(-2px);
        }
        
        .navbar-brand.brand-logo-new:hover i {
            transform: rotate(15deg) scale(1.15);
            color: var(--color-rose);
        }
        
        .navbar-brand.brand-logo-new:hover span {
            letter-spacing: 1px;
        }
        
        /* Notification bell styling */
        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .notification-bell:hover {
            color: var(--color-rose);
            transform: scale(1.1);
        }
        
        /* Favorites heart styling */
        .favorites-heart {
            position: relative;
            font-size: 1.3rem;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .favorites-heart:hover {
            color: #dc3545;
            transform: scale(1.1);
        }
        
        /* Bookings calendar styling */
        .bookings-calendar {
            position: relative;
            font-size: 1.3rem;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .bookings-calendar:hover {
            color: var(--color-rose);
            transform: scale(1.1);
        }
        
        .notification-badge, .favorites-badge, .bookings-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Responsive icon adjustments for mobile */
        @media (max-width: 576px) {
            .notification-bell,
            .favorites-heart,
            .bookings-calendar {
                font-size: 1.1rem;
                padding: 4px 8px;
            }
            
            .notification-badge, 
            .favorites-badge, 
            .bookings-badge {
                top: -5px;
                right: -5px;
                padding: 2px 5px;
                font-size: 0.6rem;
                min-width: 16px;
            }
            
            .style-recommendations-btn span {
                display: none;
            }
            
            .style-recommendations-btn {
                padding: 6px 12px;
            }
        }
        
        /* Tablet responsive adjustments */
        @media (max-width: 768px) {
            .notification-bell,
            .favorites-heart,
            .bookings-calendar {
                font-size: 1.2rem;
            }
        }
        
        /* Style Recommendations Button */
        .style-recommendations-btn {
            border: 2px solid var(--color-burgundy);
            color: var(--color-burgundy);
            background: transparent;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .style-recommendations-btn:hover {
            background-color: var(--color-burgundy);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 0, 0, 0.3);
        }
        
        .style-recommendations-btn i {
            font-size: 1.1rem;
        }
        
        /* Shine Animation */
        @keyframes shine {
            0% {
                left: -100%;
            }
            20%, 100% {
                left: 100%;
            }
        }
        
        .style-recommendations-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            animation: shine 3s infinite;
            animation-delay: 2s;
        }
        
        .notification-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            border: 2px solid #ffc0cb;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(255, 192, 203, 0.3);
        }
        
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #fff5f5;
        }
        
        .notification-item.unread {
            background-color: #fff5f5;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .notification-icon.confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .notification-icon.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .notification-icon.completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .notification-icon.reminder {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .notification-icon.new-booking {
            background-color: #d4edda;
            color: #155724;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Fix dropdown menu positioning */
        .dropdown-menu {
            position: absolute !important;
            z-index: 1050;
        }

        /* Custom Sign Up Dropdown Styling */
        .signup-dropdown-menu {
            min-width: 200px;
            padding: 0.5rem 0;
        }

        .signup-dropdown-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .signup-dropdown-item i {
            font-size: 1.5rem;
            width: 30px;
            text-align: center;
        }

        .signup-dropdown-item:hover {
            background-color: #fff5f5;
            padding-left: 2rem;
        }

        .signup-dropdown-item.user-signup i {
            color: var(--color-burgundy);
        }

        .signup-dropdown-item.business-signup i {
            color: var(--color-rose);
        }

        /* Enhanced Profile Dropdown Styling */
        .nav-item.profile-dropdown .nav-link {
            font-size: 1.15rem;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: var(--color-burgundy);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nav-item.profile-dropdown .nav-link i {
            font-size: 1.4rem;
            margin-right: 6px;
            flex-shrink: 0;
        }
        
        .nav-item.profile-dropdown .nav-link:hover {
            background-color: #fff5f5;
            color: var(--color-rose);
            transform: translateY(-2px);
        }
        
        /* Profile Dropdown Menu - Pink Theme */
        .profile-dropdown .dropdown-menu {
            min-width: 250px;
            border: 2px solid #ffc0cb;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(255, 192, 203, 0.3);
            padding: 0.75rem 0;
        }
        
        .profile-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .profile-dropdown .dropdown-item i {
            font-size: 1.3rem;
            width: 25px;
            text-align: center;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .profile-dropdown .dropdown-item:hover {
            background-color: #fff5f5;
            color: var(--color-rose);
            padding-left: 2rem;
            transform: translateX(4px);
        }
        
        .profile-dropdown .dropdown-item:hover i {
            color: var(--color-rose);
            transform: scale(1.15);
        }
        
        .profile-dropdown .dropdown-divider {
            border-top: 2px solid #ffe0e6;
            margin: 0.5rem 0;
        }
        
        .profile-dropdown .dropdown-item.text-danger {
            color: #dc3545 !important;
        }
        
        .profile-dropdown .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
            color: #c82333 !important;
        }
        
        .profile-dropdown .dropdown-item.text-danger i {
            color: #dc3545;
            transition: all 0.3s ease;
        }
        
        .profile-dropdown .dropdown-item.text-danger:hover i {
            color: #c82333;
            transform: translateX(8px);
        }
        
        /* Responsive profile dropdown */
        @media (max-width: 576px) {
            .nav-item.profile-dropdown .nav-link {
                font-size: 0.95rem;
                padding: 6px 12px;
            }
            
            .nav-item.profile-dropdown .nav-link i {
                font-size: 1.2rem;
                margin-right: 4px;
            }
            
            .profile-dropdown .dropdown-menu {
                min-width: 180px;
            }
            
            .profile-dropdown .dropdown-item {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            
            .profile-dropdown .dropdown-item i {
                font-size: 1.1rem;
                width: 20px;
            }
            
            .profile-dropdown .dropdown-item:hover {
                padding-left: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .nav-item.profile-dropdown .nav-link {
                font-size: 1rem;
                padding: 6px 14px;
            }
            
            .nav-item.profile-dropdown .nav-link i {
                font-size: 1.2rem;
            }
        }

        /* Hover to show dropdown */
        .nav-item.dropdown:hover > .dropdown-menu {
            display: block;
            margin-top: 0;
        }

        .nav-item.dropdown > .dropdown-toggle:active {
            pointer-events: none;
        }
        
        /* Responsive navbar items */
        @media (max-width: 576px) {
            .navbar {
                padding: 0.75rem 0;
            }
            
            .navbar-collapse {
                padding-top: 10px;
            }
            
            .nav-item {
                margin-right: 8px !important;
                margin-bottom: 4px;
            }
            
            .nav-link {
                font-size: 0.95rem;
                padding: 6px 8px !important;
            }
            
            .dropdown-menu {
                min-width: 160px;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-nav {
                gap: 4px;
            }
        }
    </style>
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
                <!-- Right Menu Items -->
                <ul class="navbar-nav ms-auto">
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
                        
                        <!-- Style Recommendations Button (Only for Customers) -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <li class="nav-item me-3 d-flex align-items-center">
                                <a class="style-recommendations-btn" href="../FaceShapeAI/index.html" target="_blank" title="Style Recommendations">
                                    <i class="bi bi-stars"></i>
                                    <span>Face Shape Detector</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- My Bookings Icon (Only for Customers) -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <?php 
                            // Get pending/upcoming bookings count (you can create a function for this)
                            $upcomingBookingsCount = function_exists('getUpcomingBookingsCount') ? getUpcomingBookingsCount($currentUser['customer_id']) : 0;
                            ?>
                            <li class="nav-item me-3">
                                <a class="nav-link position-relative" href="my-bookings.php" title="My Bookings">
                                    <i class="bi bi-calendar-check-fill bookings-calendar"></i>
                                    <?php if ($upcomingBookingsCount > 0): ?>
                                        <span class="bookings-badge"><?php echo $upcomingBookingsCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Favorites Button (Only for Customers) -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <?php 
                            $favoriteCount = function_exists('getNewFavoritesCount') ? getNewFavoritesCount($currentUser['customer_id']) : 0;
                            ?>
                            <li class="nav-item me-3">
                                <a class="nav-link position-relative" href="favorites.php" title="My Favorites">
                                    <i class="bi bi-heart-fill favorites-heart"></i>
                                    <?php if ($favoriteCount > 0): ?>
                                        <span class="favorites-badge"><?php echo $favoriteCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Notification Bell for BOTH Customer and Business -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <!-- CUSTOMER NOTIFICATIONS -->
                            <?php 
                            $notifications = function_exists('getCustomerNotifications') ? getCustomerNotifications($currentUser['customer_id']) : [];
                            $unreadCount = function_exists('countUnreadNotifications') ? countUnreadNotifications($currentUser['customer_id']) : 0;
                            ?>
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell-fill notification-bell"></i>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="notification-badge"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                                    <li class="px-3 py-2 border-bottom">
                                        <strong>Notifications</strong>
                                        <?php if ($unreadCount > 0): ?>
                                            <span class="badge bg-danger float-end"><?php echo $unreadCount; ?> new</span>
                                        <?php endif; ?>
                                    </li>
                                    
                                    <?php if (empty($notifications)): ?>
                                        <li class="px-3 py-4 text-center text-muted">
                                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                            <p class="mb-0 mt-2">No notifications yet</p>
                                        </li>
                                    <?php else: ?>
                                        <?php foreach (array_slice($notifications, 0, 5) as $notif): ?>
                                            <li>
                                                <a href="notifications.php" class="notification-item d-flex text-decoration-none text-dark <?php echo $notif['read_status'] == 0 ? 'unread' : ''; ?>">
                                                    <div class="notification-icon <?php 
                                                        if (strpos(strtolower($notif['notif_title']), 'confirmed') !== false) {
                                                            echo 'confirmed';
                                                        } elseif (strpos(strtolower($notif['notif_title']), 'completed') !== false) {
                                                            echo 'completed';
                                                        } elseif (strpos(strtolower($notif['notif_title']), 'reminder') !== false) {
                                                            echo 'reminder';
                                                        } else {
                                                            echo 'cancelled';
                                                        }
                                                    ?> flex-shrink-0">
                                                        <i class="bi <?php 
                                                            if (strpos(strtolower($notif['notif_title']), 'confirmed') !== false) {
                                                                echo 'bi-check-circle-fill';
                                                            } elseif (strpos(strtolower($notif['notif_title']), 'completed') !== false) {
                                                                echo 'bi-star-fill';
                                                            } elseif (strpos(strtolower($notif['notif_title']), 'reminder') !== false) {
                                                                echo 'bi-exclamation-circle-fill';
                                                            } else {
                                                                echo 'bi-x-circle-fill';
                                                            }
                                                        ?>"></i>
                                                    </div>
                                                    <div class="ms-3 flex-grow-1">
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($notif['notif_title']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($notif['notif_text']); ?></div>
                                                        <div class="notification-time mt-1">
                                                            <i class="bi bi-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($notif['notif_creation']) : date('M j, Y', strtotime($notif['notif_creation'])); ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        
                                        <li class="px-3 py-2 border-top text-center">
                                            <a href="notifications.php" class="text-decoration-none">
                                                View all notifications <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php elseif (isBusinessLoggedIn()): ?>
                            <!-- BUSINESS NOTIFICATIONS -->
                            <?php 
                            $businessNotifications = function_exists('getBusinessNotifications') ? getBusinessNotifications($currentUser['business_id'], 10) : [];
                            $businessUnreadCount = function_exists('countRecentBusinessNotifications') ? countRecentBusinessNotifications($currentUser['business_id']) : 0;
                            ?>
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link position-relative" href="#" id="businessNotificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-bell-fill notification-bell"></i>
                                    <?php if ($businessUnreadCount > 0): ?>
                                        <span class="notification-badge"><?php echo $businessUnreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="businessNotificationDropdown">
                                    <li class="px-3 py-2 border-bottom">
                                        <strong>Recent Bookings</strong>
                                        <?php if ($businessUnreadCount > 0): ?>
                                            <span class="badge bg-danger float-end"><?php echo $businessUnreadCount; ?> new</span>
                                        <?php endif; ?>
                                    </li>
                                    
                                    <?php if (empty($businessNotifications)): ?>
                                        <li class="px-3 py-4 text-center text-muted">
                                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                            <p class="mb-0 mt-2">No notifications yet</p>
                                        </li>
                                    <?php else: ?>
                                        <?php foreach ($businessNotifications as $notif): ?>
                                            <li>
                                                <a href="business-dashboard.php" class="notification-item d-flex text-decoration-none text-dark">
                                                    <div class="notification-icon <?php echo strpos($notif['notif_title'], 'New Booking') !== false ? 'new-booking' : 'cancelled'; ?> flex-shrink-0">
                                                        <i class="bi <?php echo strpos($notif['notif_title'], 'New Booking') !== false ? 'bi-calendar-plus-fill' : 'bi-x-circle-fill'; ?>"></i>
                                                    </div>
                                                    <div class="ms-3 flex-grow-1">
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($notif['notif_title']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($notif['notif_text']); ?></div>
                                                        <div class="notification-time mt-1">
                                                            <i class="bi bi-clock"></i> <?php echo function_exists('timeAgo') ? timeAgo($notif['notif_creation']) : date('M j, Y', strtotime($notif['notif_creation'])); ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        
                                        <li class="px-3 py-2 border-top text-center">
                                            <a href="business-dashboard.php" class="text-decoration-none">
                                                View dashboard <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <!-- User/Business Profile Dropdown -->
                        <?php if (isBusinessLoggedIn()): ?>
                        <!-- Business Profile Dropdown -->
                        <li class="nav-item dropdown profile-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="businessDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-building"></i>
                                <span><?php echo htmlspecialchars($currentUser['business_name']); ?></span>
                            </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="businessDropdown">
                                    <li><a class="dropdown-item" href="business-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                                    <li><a class="dropdown-item" href="business-profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="backend/auth.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="logout">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item dropdown profile-dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($displayName); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="user-profile.php"><i class="bi bi-person"></i> Profile</a></li>
                                    <li><a class="dropdown-item" href="my-bookings.php"><i class="bi bi-calendar-check"></i> My Bookings</a></li>
                                    <li><a class="dropdown-item" href="favorites.php"><i class="bi bi-heart-fill"></i> My Favorites</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="backend/auth.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="logout">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Login Button -->
                        <li class="nav-item">
                            <a class="btn btn-outline-burgundy me-2" href="login.php">Login</a>
                        </li>
                        
                        <!-- Sign Up Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="btn btn-burgundy dropdown-toggle" href="#" id="signupDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Sign Up
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end signup-dropdown-menu" aria-labelledby="signupDropdown">
                                <li>
                                    <a class="dropdown-item signup-dropdown-item user-signup" href="register-user.php">
                                        <i class="bi bi-person-fill"></i>
                                        <span>Sign up as User</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item signup-dropdown-item business-signup" href="register-business.php">
                                        <i class="bi bi-shop"></i>
                                        <span>Sign up as Business</span>
                                    </a>
                                </li>
                            </ul>
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