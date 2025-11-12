<?php
require_once 'config.php';
require_once 'functions.php';

if (!isCustomerLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentCustomer();
$customerId = $user['customer_id'];

// REMOVED: Mark notification as read functionality since is_read column doesn't exist

// REMOVED: Mark all as read functionality

$notifications = getCustomerNotifications($customerId);

$pageTitle = 'Notifications - BeautyGo';
include 'includes/header.php';
?>

<style>
    .notification-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    /* REMOVED: unread class styling since we don't track read status */
    
    .notification-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
</style>

<main class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
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
                    <!-- REMOVED: unread class since we don't track read status -->
                    <div class="card notification-card mb-3">
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
                                        <!-- REMOVED: New badge since we don't track read status -->
                                    </h5>
                                    <p class="mb-2"><?php echo htmlspecialchars($notif['notif_text']); ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?php echo timeAgo($notif['notif_creation']); ?>
                                    </small>
                                </div>
                                <!-- REMOVED: Mark as read button -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>