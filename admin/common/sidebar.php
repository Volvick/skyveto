<?php
// File: sidebar.php (REVISED WITH NOTIFICATIONS)
// Location: /admin/common/
$current_page = basename($_SERVER['PHP_SELF']);
$links = [
    'index.php' => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
    'notifications.php' => ['icon' => 'fa-bell', 'label' => 'Notifications', 'notification_type' => 'total_unread'], // New Link
    'banner.php' => ['icon' => 'fa-images', 'label' => 'Banners'],
    'coupon.php' => ['icon' => 'fa-gift', 'label' => 'Coupons'],
    'parent_category.php' => ['icon' => 'fa-sitemap', 'label' => 'Parent Categories'],
    'sub_category.php' => ['icon' => 'fa-tags', 'label' => 'Sub Categories'],
    'sections.php' => ['icon' => 'fa-th-large', 'label' => 'Category Sections'],
    'product.php' => ['icon' => 'fa-box', 'label' => 'Products'],
    'order.php' => ['icon' => 'fa-receipt', 'label' => 'All Orders', 'notification_type' => 'new_order'], // Notification type added
    'pending_payments.php' => ['icon' => 'fa-clock', 'label' => 'Pending Payments'],
    'confirmed_orders.php' => ['icon' => 'fa-check-circle', 'label' => 'Confirmed Orders'],
    'delivered_orders.php' => ['icon' => 'fa-truck', 'label' => 'Delivered Orders'],
    'returns.php' => ['icon' => 'fa-undo-alt', 'label' => 'Returns', 'notification_type' => 'return_request'], // Notification type added
    'user.php' => ['icon' => 'fa-users', 'label' => 'Users', 'notification_type' => 'new_user'], // Notification type added
    'user_payments.php' => ['icon' => 'fa-wallet', 'label' => 'Saved Payment Details'],
    'payment_settings.php' => ['icon' => 'fa-money-check-alt', 'label' => 'Payment Settings'],
    'contact_settings.php' => ['icon' => 'fa-address-book', 'label' => 'Contact Settings'],
    'setting.php' => ['icon' => 'fa-cog', 'label' => 'Settings'],
];

// Fetch the logo from the database
$logo_stmt = $conn->query("SELECT logo_image FROM settings WHERE id = 1");
$logo_filename = $logo_stmt ? ($logo_stmt->fetch_assoc()['logo_image'] ?? 'logo.png') : 'logo.png';
$logo_path = ($logo_filename === 'logo.png') ? BASE_URL . '/assets/logo.png' : BASE_URL . '/uploads/' . $logo_filename;
?>
<aside class="w-64 bg-gray-800 text-white min-h-screen p-4 flex flex-col">
    <div class="mb-6 text-center">
        <a href="<?php echo BASE_URL; ?>/admin/index.php">
             <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Skyveto Logo" class="h-10 w-auto mx-auto p-1 bg-white rounded-md">
        </a>
    </div>
    <nav class="flex flex-col space-y-2">
        <?php foreach ($links as $url => $link): ?>
        <a href="<?php echo $url; ?>" 
           class="flex items-center justify-between px-4 py-3 rounded-md text-sm hover:bg-gray-700 transition-colors 
           <?php echo ($current_page == $url) ? 'bg-blue-600' : ''; ?>"
           <?php echo isset($link['notification_type']) ? 'data-notification-type="'.$link['notification_type'].'"' : ''; ?>>
            <div class="flex items-center">
                <i class="fas <?php echo $link['icon']; ?> w-6"></i>
                <span class="ml-3"><?php echo $link['label']; ?></span>
            </div>
            <!-- Notification Badge Placeholder -->
            <span class="notification-badge hidden h-2 w-2 bg-blue-400 rounded-full"></span>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>