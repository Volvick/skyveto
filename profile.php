<?php
// File: profile.php
// Location: /
// FINAL VERSION: Added Delete Account link.

include_once 'common/config.php';
check_login();
$user_id = get_user_id();

// Fetch user details for the page
$stmt = $conn->prepare("SELECT name, email, phone, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Use a placeholder if no profile picture is set
$profile_pic_path = BASE_URL . '/uploads/' . ($user['profile_pic'] && file_exists(ROOT_PATH . '/uploads/' . $user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'default_avatar.png');

include_once 'common/header.php';
?>
<main class="p-4 bg-gray-50 min-h-screen">
    <!-- User Info Header -->
    <div class="bg-white p-4 rounded-lg shadow-sm text-center">
        <img src="<?php echo $profile_pic_path; ?>" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200 mx-auto">
        <h1 class="text-2xl font-bold mt-3"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h1>
        <p class="text-gray-600"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
        <p class="text-gray-600"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></p>
        <a href="edit_profile.php" class="mt-4 inline-block bg-blue-50 text-blue-600 font-semibold px-6 py-2 rounded-lg text-sm hover:bg-blue-100 transition-colors"><?php echo __t('profile_edit_profile'); ?></a>
    </div>

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-3 gap-4 mt-6">
        <a href="wishlist.php" class="bg-white p-4 rounded-lg shadow-sm text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-heart text-2xl text-red-500"></i>
            <p class="mt-2 font-semibold text-sm">My Wishlist</p>
        </a>
        <a href="help_center.php" class="bg-white p-4 rounded-lg shadow-sm text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-headset text-2xl text-blue-500"></i>
            <p class="mt-2 font-semibold text-sm"><?php echo __t('profile_help_centre'); ?></p>
        </a>
        <a href="change_language.php" class="bg-white p-4 rounded-lg shadow-sm text-center hover:bg-gray-50 transition-colors">
            <i class="fas fa-language text-2xl text-blue-500"></i>
            <p class="mt-2 font-semibold text-sm"><?php echo __t('profile_change_language'); ?></p>
        </a>
    </div>

    <!-- My Account Section -->
    <div class="mt-6 bg-white rounded-lg shadow-sm">
        <h2 class="text-lg font-semibold p-4 border-b">My Account</h2>
        <a href="order.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center">
            <span><i class="fas fa-box w-6 mr-2 text-gray-500"></i>My Orders</span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <a href="edit_profile.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center border-t">
            <span><i class="fas fa-user-circle w-6 mr-2 text-gray-500"></i>Profile Details</span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
    </div>

    <!-- My Payments Section -->
    <div class="mt-6 bg-white rounded-lg shadow-sm">
        <h2 class="text-lg font-semibold p-4 border-b"><?php echo __t('profile_my_payments'); ?></h2>
        <a href="payment_details.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center">
            <span><i class="fas fa-wallet w-6 mr-2 text-gray-500"></i><?php echo __t('profile_bank_upi_details'); ?></span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <a href="payment_refund.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center border-t">
            <span><i class="fas fa-undo-alt w-6 mr-2 text-gray-500"></i><?php echo __t('profile_payment_refund'); ?></span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
    </div>
    
    <!-- Others Section -->
    <div class="mt-6 bg-white rounded-lg shadow-sm">
        <h2 class="text-lg font-semibold p-4 border-b"><?php echo __t('profile_others'); ?></h2>
        <a href="privacy_policy.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center">
            <span><i class="fas fa-user-shield w-6 mr-2 text-gray-500"></i>Privacy Policy</span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <a href="legal_and_policies.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center border-t">
            <span><i class="fas fa-shield-alt w-6 mr-2 text-gray-500"></i><?php echo __t('profile_legal_policies'); ?></span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <a href="#" class="block p-4 hover:bg-gray-50 flex justify-between items-center border-t">
            <span><i class="fas fa-star w-6 mr-2 text-gray-500"></i>Rate Skyveto</span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
        <!-- NEW DELETE ACCOUNT LINK -->
        <a href="delete_account.php" class="block p-4 hover:bg-gray-50 flex justify-between items-center border-t">
            <span class="text-red-500"><i class="fas fa-trash-alt w-6 mr-2"></i>Delete Account</span>
            <i class="fas fa-chevron-right text-gray-400"></i>
        </a>
    </div>

    <!-- LOGOUT BUTTON SECTION -->
    <div class="mt-6">
        <a href="logout.php" class="block w-full text-center bg-red-500 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-red-600">
            <i class="fas fa-sign-out-alt mr-2"></i><?php echo __t('sidebar_logout'); ?>
        </a>
    </div>
</main>
<?php
include_once 'common/bottom.php';
?>