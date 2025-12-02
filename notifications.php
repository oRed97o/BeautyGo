<?php
require_once 'db_connection/config.php';
require_once 'backend/function_utilities.php';
require_once 'backend/function_customers.php';
require_once 'backend/function_notifications.php';

if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentCustomer();
$customerId = $user['customer_id'];

// Use enhanced function to get notifications with booking details
$notifications = function_exists('getCustomerNotificationsWithBooking') 
    ? getCustomerNotificationsWithBooking($customerId) 
    : getCustomerNotifications($customerId);

// Mark all notifications as read when viewing notifications page
markCustomerNotificationsAsRead($customerId);

$pageTitle = 'Notifications - BeautyGo';
include 'includes/header.php';
?>

<style>
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
    
    .notification-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    /* REMOVED: unread class styling since we don't track read status */
    
    .notification-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    /* Notification header styling */
    .notification-header {
        display: flex;
        justify-content: between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    
    .notification-header h2 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.75rem;
    }
    
    .notification-header h2 i {
        color: var(--color-burgundy);
        font-size: 2rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .back-button {
            padding: 6px 12px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .back-button i {
            font-size: 1rem;
        }
        
        .notification-header h2 {
            font-size: 1.35rem;
        }
        
        .notification-header h2 i {
            font-size: 1.5rem;
        }
        
        .notification-card {
            margin-bottom: 0.75rem !important;
        }

        .card {
            margin-bottom: 0.75rem !important;
        }
    }
    
    @media (max-width: 768px) {
        .notification-header h2 {
            font-size: 1.5rem;
        }
        
        .notification-header h2 i {
            font-size: 1.75rem;
        }
    }
</style>

<main class="container px-2 px-md-3 py-3 py-md-4" style="max-width: 1200px;">
    <div class="row">
        <div class="col-12">
            <!-- Back Button -->
            <a href="index.php" class="back-button">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Back to Home</span>
            </a>
            
            <div class="notification-header">
                <h2><i class="bi bi-bell-fill"></i> Notifications</h2>
                <!-- REMOVED: Mark all as read button -->
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3 text-muted">No notifications yet</h4>
                        <p class="text-muted">You'll see updates about your appointments here</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <!-- Notification card with booking details and redirect link -->
                    <div class="card notification-card mb-3" 
                         style="cursor: pointer; transition: all 0.3s ease;"
                         onclick="<?php if (!empty($notif['appointment_id'])) echo "window.location.href='my-bookings.php?appointment_id={$notif['appointment_id']}';"; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon <?php echo strpos($notif['notif_title'], 'Confirmed') !== false ? 'confirmed' : (strpos($notif['notif_title'], 'Cancelled') !== false ? 'cancelled' : 'completed'); ?> flex-shrink-0 me-3">
                                    <i class="bi <?php 
                                        if (strpos($notif['notif_title'], 'Confirmed') !== false) {
                                            echo 'bi-check-circle-fill';
                                        } elseif (strpos($notif['notif_title'], 'Cancelled') !== false) {
                                            echo 'bi-x-circle-fill';
                                        } else {
                                            echo 'bi-star-fill';
                                        }
                                    ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($notif['notif_title']); ?>
                                    </h5>
                                    <p class="mb-2"><?php echo htmlspecialchars($notif['notif_text']); ?></p>
                                    
                                    <!-- Booking Details (if available) -->
                                    <?php if (!empty($notif['appointment_id']) && !empty($notif['business_name'])): ?>
                                        <div class="alert alert-light mt-2 mb-2" style="border-left: 3px solid var(--color-rose);">
                                            <small>
                                                <strong>📍 <?php echo htmlspecialchars($notif['business_name']); ?></strong><br>
                                                <?php if (!empty($notif['service_name'])): ?>
                                                    <i class="bi bi-scissors"></i> <?php echo htmlspecialchars($notif['service_name']); ?>
                                                    (<?php echo intval($notif['duration']); ?> min) — ₱<?php echo number_format($notif['cost'] ?? 0, 2); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($notif['appoint_date'])): ?>
                                                    <i class="bi bi-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($notif['appoint_date'])); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($notif['appoint_desc'])): ?>
                                                    <i class="bi bi-chat-dots"></i> <em><?php echo htmlspecialchars($notif['appoint_desc']); ?></em><br>
                                                <?php endif; ?>
                                                <span class="badge bg-<?php echo $notif['appoint_status'] === 'confirmed' ? 'success' : ($notif['appoint_status'] === 'cancelled' ? 'danger' : 'info'); ?> mt-1">
                                                    <?php echo ucfirst($notif['appoint_status'] ?? 'pending'); ?>
                                                </span>
                                            </small>
                                        </div>
                                        <a href="my-bookings.php?appointment_id=<?php echo $notif['appointment_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-right"></i> View Booking
                                        </a>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-clock"></i> <?php echo timeAgo($notif['notif_creation']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>