<?php
// File: bottom.php
// Location: /common/
$current_page = basename($_SERVER['PHP_SELF']);
$home_active = ($current_page == 'index.php') ? 'text-blue-600' : 'text-gray-500';
$categories_active = ($current_page == 'product.php') ? 'text-blue-600' : 'text-gray-500';
$cart_active = ($current_page == 'cart.php') ? 'text-blue-600' : 'text-gray-500';
$orders_active = ($current_page == 'order.php') ? 'text-blue-600' : 'text-gray-500';
$profile_active = ($current_page == 'profile.php') ? 'text-blue-600' : 'text-gray-500';

$user_id = get_user_id();
$cart_count = 0;
if (isset($conn) && $conn && $user_id > 0) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_count = $stmt->get_result()->fetch_assoc()['total_items'] ?? 0;
        $stmt->close();
    }
}
?>
    </main> <!-- Closing the main tag from header.php -->

    <nav class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t z-20 flex justify-around">
        <a href="<?php echo BASE_URL; ?>/index.php" class="flex flex-col items-center justify-center p-2 w-full <?php echo $home_active; ?> hover:text-blue-600">
            <i class="fas fa-home text-lg"></i>
            <span class="text-xs mt-1"><?php echo __t('nav_home'); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>/product.php" class="flex flex-col items-center justify-center p-2 w-full <?php echo $categories_active; ?> hover:text-blue-600">
            <i class="fas fa-th-large text-lg"></i>
            <span class="text-xs mt-1"><?php echo __t('nav_categories'); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>/cart.php" class="flex flex-col items-center justify-center p-2 w-full <?php echo $cart_active; ?> hover:text-blue-600 relative">
            <i class="fas fa-shopping-cart text-lg"></i>
            <span class="text-xs mt-1"><?php echo __t('nav_cart'); ?></span>
            <?php if(isset($cart_count) && $cart_count > 0): ?>
            <span id="cart-count-badge" class="absolute top-1 right-2 bg-red-500 text-white text-xs font-bold rounded-full h-4 w-4 flex items-center justify-center text-[10px]"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_URL; ?>/order.php" class="flex flex-col items-center justify-center p-2 w-full <?php echo $orders_active; ?> hover:text-blue-600">
            <i class="fas fa-box text-lg"></i>
            <span class="text-xs mt-1"><?php echo __t('nav_my_orders'); ?></span>
        </a>
        <a href="<?php echo BASE_URL; ?>/profile.php" class="flex flex-col items-center justify-center p-2 w-full <?php echo $profile_active; ?> hover:text-blue-600">
            <i class="fas fa-user text-lg"></i>
            <span class="text-xs mt-1"><?php echo __t('nav_profile'); ?></span>
        </a>
    </nav>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.body.style.webkitTouchCallout = 'none';
        
        // --- SIDEBAR LOGIC (No changes here) ---
        const menuBtn = document.getElementById('menu-btn'); 
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        if (menuBtn && sidebar && sidebarOverlay) {
            const toggleSidebar = () => { sidebar.classList.toggle('-translate-x-full'); sidebarOverlay.classList.toggle('hidden'); };
            menuBtn.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        // --- REMOVED SEARCH BAR LOGIC ---
    });
    
    // --- NEW: SKELETON LOADER LOGIC ---
    window.addEventListener('load', () => {
        const skeletonGrid = document.getElementById('skeleton-grid');
        const productGrid = document.getElementById('product-grid');

        if (skeletonGrid && productGrid) {
            skeletonGrid.style.display = 'none';
            // Use 'grid' as it's the correct display property for these elements
            productGrid.style.display = 'grid';
        }
    });
    
    document.addEventListener('keydown', (e) => { if (e.ctrlKey && ['=','-','0'].includes(e.key)) { e.preventDefault(); }});
    </script>
</body>
</html>
<?php 
if (isset($conn) && is_object($conn) && method_exists($conn, 'close')) { $conn->close(); }
?>