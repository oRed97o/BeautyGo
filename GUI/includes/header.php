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
    
    <style>
        /* Notification bell styling */
        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            color: var(--color-burgundy);
            transition: all 0.3s ease;
        }
        
        .notification-bell:hover {
            color: var(--color-rose);
            transform: scale(1.1);
        }
        
        .notification-badge {
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
        
        .notification-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: var(--color-cream);
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
        
        .notification-icon.new-booking {
            background-color: #d4edda;
            color: #155724;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
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
                        
                        <!-- Notification Bell for BOTH Customer and Business -->
                        <?php if (isCustomerLoggedIn()): ?>
                            <!-- CUSTOMER NOTIFICATIONS -->
                            <?php 
                            $notifications = getCustomerNotifications($currentUser['customer_id']);
                            $unreadCount = countUnreadNotifications($currentUser['customer_id']);
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
                                                <a href="notifications.php" class="notification-item d-flex text-decoration-none text-dark">
                                                    <div class="notification-icon <?php echo strpos(strtolower($notif['notif_title']), 'confirmed') !== false ? 'confirmed' : (strpos(strtolower($notif['notif_title']), 'completed') !== false ? 'completed' : 'cancelled'); ?> flex-shrink-0">
                                                        <i class="bi <?php 
                                                            if (strpos(strtolower($notif['notif_title']), 'confirmed') !== false) {
                                                                echo 'bi-check-circle-fill';
                                                            } elseif (strpos(strtolower($notif['notif_title']), 'completed') !== false) {
                                                                echo 'bi-star-fill';
                                                            } else {
                                                                echo 'bi-x-circle-fill';
                                                            }
                                                        ?>"></i>
                                                    </div>
                                                    <div class="ms-3 flex-grow-1">
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($notif['notif_title']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($notif['notif_text']); ?></div>
                                                        <div class="notification-time mt-1">
                                                            <i class="bi bi-clock"></i> <?php echo timeAgo($notif['notif_creation']); ?>
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
                            $businessNotifications = getBusinessNotifications($currentUser['business_id'], 10);
                            $businessUnreadCount = countRecentBusinessNotifications($currentUser['business_id']);
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
                                                            <i class="bi bi-clock"></i> <?php echo timeAgo($notif['notif_creation']); ?>
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