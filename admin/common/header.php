<?php
// File: header.php
// Location: /admin/common/
// REVISED WITH NOTIFICATIONS

include_once dirname(__DIR__, 2) . '/common/config.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$logo_stmt_header = $conn->query("SELECT logo_image FROM settings WHERE id = 1");
$logo_filename_header = $logo_stmt_header ? ($logo_stmt_header->fetch_assoc()['logo_image'] ?? 'logo.png') : 'logo.png';
$logo_path_header = ($logo_filename_header === 'logo.png') ? BASE_URL . '/assets/logo.png' : BASE_URL . '/uploads/' . $logo_filename_header;

// --- NEW: Mark notifications as read when visiting a page ---
$current_page_basename = basename($_SERVER['PHP_SELF']);
$notification_type_to_clear = '';
if ($current_page_basename === 'order.php') {
    $notification_type_to_clear = 'new_order';
} elseif ($current_page_basename === 'returns.php') {
    $notification_type_to_clear = 'return_request';
} elseif ($current_page_basename === 'user.php') {
    $notification_type_to_clear = 'new_user';
}

if ($notification_type_to_clear) {
    $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE type = ?");
    $stmt_mark_read->bind_param("s", $notification_type_to_clear);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom_fonts.css">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex">
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    <div class="flex-1 flex flex-col">
        <header class="bg-white shadow-sm p-4 flex justify-between items-center sticky top-0 z-10">
            <div class="flex items-center">
                <img src="<?php echo htmlspecialchars($logo_path_header); ?>" alt="Logo" class="h-8 w-auto">
            </div>
            <div class="flex items-center space-x-6">
                <!-- NEW: Notification Bell in Header -->
                <a href="notifications.php" class="relative text-gray-600 hover:text-blue-600" id="header-notification-link">
                    <i class="fas fa-bell text-xl"></i>
                    <span id="header-notification-badge" class="absolute -top-1 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-4 w-4 flex items-center justify-center text-[10px] hidden"></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="text-red-500 hover:text-red-600"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
        <main class="flex-1 p-6">
        
<!-- NEW: Notification Javascript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fetchNotificationCounts = async () => {
            try {
                const formData = new FormData();
                formData.append('action', 'get_unread_counts');
                const response = await fetch('notifications_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    // Update header badge
                    const headerBadge = document.getElementById('header-notification-badge');
                    if (result.total_unread > 0) {
                        headerBadge.textContent = result.total_unread;
                        headerBadge.classList.remove('hidden');
                    } else {
                        headerBadge.classList.add('hidden');
                    }

                    // Update sidebar badges
                    document.querySelectorAll('[data-notification-type]').forEach(link => {
                        const type = link.dataset.notificationType;
                        const badge = link.querySelector('.notification-badge');
                        
                        let count = 0;
                        if (type === 'total_unread') {
                            count = result.total_unread;
                        } else if (result.counts && result.counts[type]) {
                            count = result.counts[type];
                        }
                        
                        if (count > 0) {
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    });
                }
            } catch (error) {
                console.error("Could not fetch notification counts:", error);
            }
        };

        // Fetch counts on page load and then every 30 seconds
        fetchNotificationCounts();
        setInterval(fetchNotificationCounts, 30000); 
    });
</script>