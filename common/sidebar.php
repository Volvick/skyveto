<?php
// File: sidebar.php
// Location: /common/
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'Guest';
?>
<!-- SIDEBAR -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>
<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white shadow-xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
    <div class="p-4 bg-blue-600 text-white">
        <h2 class="text-xl font-bold"><?php echo __t('sidebar_hello'); ?>, <?php echo htmlspecialchars($user_name); ?></h2>
    </div>
    <nav class="mt-4">
        <a href="<?php echo BASE_URL; ?>/index.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-home w-6 mr-2"></i><?php echo __t('nav_home'); ?></a>
        <a href="<?php echo BASE_URL; ?>/product.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-box-open w-6 mr-2"></i><?php echo __t('sidebar_all_products'); ?></a>
        <a href="<?php echo BASE_URL; ?>/order.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-receipt w-6 mr-2"></i><?php echo __t('nav_my_orders'); ?></a>
        <a href="<?php echo BASE_URL; ?>/profile.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-user-circle w-6 mr-2"></i><?php echo __t('nav_profile'); ?></a>
        <a href="<?php echo BASE_URL; ?>/change_language.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-language w-6 mr-2"></i><?php echo __t('profile_change_language'); ?></a>
        <a href="<?php echo BASE_URL; ?>/help_center.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-headset w-6 mr-2"></i><?php echo __t('sidebar_contact_customer_care'); ?></a>

        <hr class="my-2">
        <?php if ($is_logged_in): ?>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-3 text-red-500 hover:bg-gray-100"><i class="fas fa-sign-out-alt w-6 mr-2"></i><?php echo __t('sidebar_logout'); ?></a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/login.php" class="block px-4 py-3 text-green-500 hover:bg-gray-100"><i class="fas fa-sign-in-alt w-6 mr-2"></i><?php echo __t('sidebar_login_signup'); ?></a>
        <?php endif; ?>
    </nav>
</aside>