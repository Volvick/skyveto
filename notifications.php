<?php
// File: notifications.php (NEW)
// Location: /
include_once 'common/config.php';
check_login(); // Ensure user is logged in
$user_id = get_user_id();

// Mark all unread notifications for this user as read
$conn->query("UPDATE user_notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");

// Fetch all notifications for the user
$notifications = [];
$stmt = $conn->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

include_once 'common/header.php';
?>

<main class="p-4 bg-gray-100 min-h-screen">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Notifications</h1>

    <div class="bg-white rounded-lg shadow-sm">
        <div id="notification-list" class="space-y-1">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-bell-slash text-5xl text-gray-300"></i>
                    <p class="text-center text-gray-500 mt-4">You have no notifications yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="block p-4 border-b hover:bg-gray-50">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
include_once 'common/bottom.php'; 
?>