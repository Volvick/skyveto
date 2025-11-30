<?php
// File: notifications.php (NEW)
// Location: /admin/
include_once 'common/header.php';
?>

<h1 class="text-2xl font-bold mb-6">Notifications</h1>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div id="notification-list" class="space-y-4">
        <!-- Notifications will be loaded here by JavaScript -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const notificationList = document.getElementById('notification-list');

    const fetchNotifications = async () => {
        notificationList.innerHTML = '<div class="text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></div>';
        try {
            const formData = new FormData();
            formData.append('action', 'fetch_all');
            const response = await fetch('notifications_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            notificationList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(notification => {
                    const isReadClass = notification.is_read == 1 ? 'bg-gray-50' : 'bg-blue-50 font-semibold';
                    const iconMap = {
                        'new_order': 'fa-receipt text-green-500',
                        'new_user': 'fa-user-plus text-blue-500',
                        'return_request': 'fa-undo-alt text-orange-500'
                    };
                    const iconClass = iconMap[notification.type] || 'fa-bell text-gray-500';

                    notificationList.innerHTML += `
                        <div class="p-4 rounded-lg flex items-start space-x-4 ${isReadClass}" data-id="${notification.id}">
                            <i class="fas ${iconClass} text-2xl mt-1"></i>
                            <div class="flex-1">
                                <p class="text-gray-800">${notification.message}</p>
                                <p class="text-xs text-gray-500 mt-1">${new Date(notification.created_at).toLocaleString()}</p>
                            </div>
                            <button class="mark-as-read-btn text-gray-400 hover:text-gray-600" data-id="${notification.id}" title="Mark as read">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>`;
                });
            } else {
                notificationList.innerHTML = '<p class="text-center text-gray-500 p-8">No notifications yet.</p>';
            }
        } catch (error) {
            notificationList.innerHTML = '<p class="text-center text-red-500 p-8">Failed to load notifications.</p>';
        }
    };

    notificationList.addEventListener('click', async (e) => {
        const markReadBtn = e.target.closest('.mark-as-read-btn');
        if (markReadBtn) {
            const id = markReadBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('notification_id', id);
            try {
                const response = await fetch('notifications_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    const notificationDiv = document.querySelector(`div[data-id='${id}']`);
                    if (notificationDiv) {
                        notificationDiv.classList.remove('bg-blue-50', 'font-semibold');
                        notificationDiv.classList.add('bg-gray-50');
                    }
                    // We can optionally refetch all notifications or just update the UI
                    // For now, just updating UI is fine. We can also update the main count.
                }
            } catch (error) {
                alert('Failed to mark as read.');
            }
        }
    });

    fetchNotifications();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>