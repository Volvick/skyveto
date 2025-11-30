<?php
// File: order_details.php (FULLY CORRECTED AND TRANSLATED)
// Location: /
include_once 'common/config.php';
check_login();

$user_id = get_user_id();
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) { redirect('order.php'); }

// --- DATA FETCHING ---
$stmt_order = $conn->prepare("SELECT *, (TIMESTAMPDIFF(HOUR, delivered_at, NOW()) >= 24) as is_return_expired FROM orders WHERE id = ? AND user_id = ?");
$stmt_order->bind_param("ii", $order_id, $user_id); $stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();
$stmt_order->close();
if (!$order) { include_once 'common/header.php'; echo '<main class="p-4 text-center"><h1>Order Not Found</h1></main>'; include_once 'common/bottom.php'; exit; }

$order_items = [];
$stmt_items = $conn->prepare("SELECT oi.quantity, oi.price, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt_items->bind_param("i", $order_id); $stmt_items->execute();
$result_items = $stmt_items->get_result();
while ($row = $result_items->fetch_assoc()) { $order_items[] = $row; }
$stmt_items->close();

$return_request = null;
$stmt_return = $conn->prepare("SELECT * FROM returns WHERE order_id = ? AND user_id = ?");
$stmt_return->bind_param("ii", $order_id, $user_id); $stmt_return->execute();
$return_request = $stmt_return->get_result()->fetch_assoc();
$stmt_return->close();

$tracking_history = [];
$delivery_statuses = ['Placed', 'Shipped', 'Out for Delivery', 'Delivered'];
$return_statuses = ['Pending', 'Approved', 'Rejected', 'Completed'];

if ($return_request) {
    $stmt_history = $conn->prepare("SELECT status_update, details, created_at FROM return_tracking_history WHERE return_id = ? ORDER BY created_at ASC");
    $stmt_history->bind_param("i", $return_request['id']);
} else {
    $stmt_history = $conn->prepare("SELECT status_update, details, created_at FROM order_tracking_history WHERE order_id = ? ORDER BY created_at ASC");
    $stmt_history->bind_param("i", $order_id);
}
$stmt_history->execute();
$result_history = $stmt_history->get_result();
while ($row = $result_history->fetch_assoc()) { $tracking_history[] = $row; }
$stmt_history->close();

include_once 'common/header.php';
?>
<style>
    .timeline-item { position: relative; padding-left: 2rem; border-left: 2px solid #e5e7eb; }
    .timeline-item:last-child { border-left: 2px solid transparent; }
    .timeline-icon { position: absolute; left: -0.7rem; top: 0.1rem; width: 1.2rem; height: 1.2rem; border-radius: 9999px; display: flex; align-items-center; justify-content: center; }
    .timeline-icon.completed { background-color: #22c55e; color: white; border: 2px solid white; }
    .timeline-icon.pending { background-color: #e5e7eb; border: 2px solid white; }
</style>

<main class="p-4">
    <a href="order.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i><?php echo __t('order_details_back_link'); ?></a>
    <h1 class="text-2xl font-bold text-gray-800"><?php echo __t('order_details_title'); ?></h1>
     <div id="action-message" class="hidden my-4 p-3 rounded-md text-center"></div>

    <div class="bg-white p-4 rounded-lg shadow-sm mt-4">
        <div class="flex justify-between items-center border-b pb-3">
            <div><p class="text-sm text-gray-500"><?php echo __t('order_id'); ?></p><p class="font-semibold">#<?php echo $order['id']; ?></p></div>
            <div><p class="text-sm text-gray-500"><?php echo __t('order_details_total_amount'); ?></p><p class="font-bold text-xl">₹<?php echo number_format($order['total_amount'], 2); ?></p></div>
        </div>
        <div class="pt-3">
            <h3 class="font-semibold">Shipping Address:</h3>
            <p class="text-sm text-gray-600 mt-1 whitespace-pre-line"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
        </div>
    </div>
    
    <div class="bg-white p-4 rounded-lg shadow-sm mt-4">
        <?php if ($return_request): ?>
            <h2 class="font-bold text-lg mb-4"><?php echo __t('order_details_return_tracking'); ?></h2>
            <div class="space-y-6">
                <?php
                $return_status_map = [
                    'Pending' => __t('order_details_return_pending'),
                    'Approved' => __t('order_details_return_approved'),
                    'Rejected' => __t('order_details_return_rejected'),
                    'Completed' => __t('order_details_return_completed'),
                ];
                $current_return_status_index = array_search($return_request['status'], array_keys($return_status_map));
                foreach ($return_status_map as $status_key => $status_translation):
                    $index = array_search($status_key, array_keys($return_status_map));
                    $is_completed = ($current_return_status_index !== false && $current_return_status_index >= $index);
                ?>
                <div class="timeline-item">
                    <div class="timeline-icon <?php echo $is_completed ? 'completed' : 'pending'; ?>"><i class="fas fa-check text-xs"></i></div>
                    <div>
                        <p class="font-bold <?php echo $is_completed ? 'text-gray-800' : 'text-gray-400'; ?>"><?php echo $status_translation; ?></p>
                        <?php foreach($tracking_history as $track): if($track['status_update'] == $status_key): ?>
                        <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded-md border mt-1">
                            <p><?php echo nl2br(htmlspecialchars($track['details'])); ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo date('D, M j, g:i a', strtotime($track['created_at'])); ?></p>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <h2 class="font-bold text-lg mb-4"><?php echo __t('order_details_delivery_tracking'); ?></h2>
            <div class="space-y-6">
                 <?php
                    $delivery_status_map = [
                        'Placed' => __t('order_status_placed'),
                        'Shipped' => __t('order_status_shipped'),
                        'Out for Delivery' => __t('order_details_out_for_delivery'),
                        'Delivered' => __t('order_status_delivered'),
                    ];
                    $current_status_index = array_search($order['status'], array_keys($delivery_status_map));
                    foreach ($delivery_status_map as $status_key => $status_translation):
                        $index = array_search($status_key, array_keys($delivery_status_map));
                        $is_completed = ($current_status_index !== false && $current_status_index >= $index);
                        $text_color = $is_completed ? 'text-gray-800' : 'text-gray-400';
                 ?>
                <div class="timeline-item">
                    <div class="timeline-icon <?php echo $is_completed ? 'completed' : 'pending'; ?>"><?php if ($is_completed): ?><i class="fas fa-check text-xs"></i><?php endif; ?></div>
                    <div class="<?php echo $text_color; ?>">
                        <p class="font-bold"><?php echo $status_translation; ?></p>
                        
                        <?php if ($status_key === 'Placed' && $is_completed): ?>
                            <p class="text-sm"><?php echo __t('order_details_placed_desc'); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('D, jS M Y - g:ia', strtotime($order['created_at'])); ?></p>
                        <?php elseif ($status_key === 'Delivered' && $is_completed && !empty($order['delivered_at'])): ?>
                            <p class="text-sm"><?php echo __t('order_details_delivered_desc'); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('D, jS M Y - g:ia', strtotime($order['delivered_at'])); ?></p>
                        <?php elseif (!$is_completed): ?>
                             <p class="text-sm">
                                <?php
                                switch($status_key) {
                                    case 'Shipped': echo __t('order_details_shipped_desc'); break;
                                    case 'Out for Delivery': echo __t('order_details_out_for_delivery_desc'); break;
                                    case 'Delivered': echo __t('order_details_delivered_desc'); break;
                                }
                                ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($is_completed): 
                            foreach($tracking_history as $track): if($track['status_update'] == $status_key): ?>
                                <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md border mt-2">
                                    <p><?php echo nl2br(htmlspecialchars($track['details'])); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('D, M j, g:i a', strtotime($track['created_at'])); ?></p>
                                </div>
                        <?php endif; endforeach; endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm mt-4">
        <h2 class="font-bold text-lg mb-4"><?php echo __t('order_details_items_in_order'); ?></h2>
        <div class="space-y-4">
            <?php foreach ($order_items as $item): ?>
            <div class="flex items-center space-x-4">
                <img src="<?php echo get_image_url($item['image']); ?>" class="w-16 h-16 rounded-md object-cover">
                <div class="flex-1"><p class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></p><p class="text-sm text-gray-500"><?php echo __t('order_details_quantity'); ?> <?php echo $item['quantity']; ?></p></div>
                <p class="font-semibold">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="border-t mt-4 pt-4 space-y-3">
            <?php if ($return_request): ?>
                <a href="help_center.php" class="block w-full text-center bg-gray-500 text-white font-semibold py-2 rounded-md"><?php echo __t('order_details_contact_help'); ?></a>
            <?php elseif ($order['status'] == 'Placed'): ?>
                <button id="cancel-order-btn" class="w-full bg-red-500 text-white font-semibold py-2 rounded-md hover:bg-red-600"><?php echo __t('order_details_cancel_order'); ?></button>
            <?php elseif ($order['status'] == 'Delivered'): ?>
                <?php if ($order['is_return_expired'] == 0): ?>
                    <a href="request_return.php?order_id=<?php echo $order_id; ?>" class="block w-full text-center bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600"><?php echo __t('order_details_refund_replacement'); ?></a>
                <?php else: ?>
                    <a href="help_center.php" class="block w-full text-center bg-gray-500 text-white font-semibold py-2 rounded-md"><?php echo __t('order_details_contact_care'); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<!-- NEW: Cancellation Reason Modal -->
<div id="cancel-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm">
        <h2 class="text-xl font-bold mb-4">Reason for Cancellation</h2>
        <form id="cancel-reason-form">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <div class="space-y-3 mb-6">
                <label class="block border rounded-md p-3 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                    <input type="radio" name="reason" value="Ordered by mistake" class="mr-2">
                    <span>Ordered by mistake</span>
                </label>
                <label class="block border rounded-md p-3 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                    <input type="radio" name="reason" value="Want to change shipping address" class="mr-2">
                    <span>Want to change shipping address</span>
                </label>
                <label class="block border rounded-md p-3 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                    <input type="radio" name="reason" value="Found a better price elsewhere" class="mr-2">
                    <span>Found a better price elsewhere</span>
                </label>
                 <label class="block border rounded-md p-3 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                    <input type="radio" name="reason" value="Other" class="mr-2">
                    <span>Other</span>
                </label>
            </div>
            <div id="cancel-form-msg" class="text-sm text-red-600 mb-4 text-center"></div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="cancel-modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Go Back</button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md">Confirm Cancellation</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cancelBtn = document.getElementById('cancel-order-btn');
    const cancelModal = document.getElementById('cancel-modal');
    const cancelModalCloseBtn = document.getElementById('cancel-modal-close-btn');
    const cancelReasonForm = document.getElementById('cancel-reason-form');
    const msgEl = document.getElementById('action-message');

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            cancelModal.classList.remove('hidden');
        });
    }
    
    if (cancelModalCloseBtn) {
        cancelModalCloseBtn.addEventListener('click', () => {
            cancelModal.classList.add('hidden');
        });
    }

    if(cancelModal) {
        cancelModal.addEventListener('click', (e) => {
            if (e.target === cancelModal) {
                cancelModal.classList.add('hidden');
            }
        });
    }

    if (cancelReasonForm) {
        cancelReasonForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(cancelReasonForm);
            const reason = formData.get('reason');
            if (!reason) {
                document.getElementById('cancel-form-msg').textContent = 'Please select a reason.';
                return;
            }

            cancelModal.classList.add('hidden');
            
            try {
                const response = await fetch('order_details_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                msgEl.classList.remove('hidden');
                if (result.status === 'success') {
                    msgEl.className = 'my-4 p-3 rounded-md text-center bg-green-100 text-green-800';
                    msgEl.textContent = result.message;
                    if (cancelBtn) cancelBtn.style.display = 'none';
                    setTimeout(() => window.location.href = 'order.php', 2000);
                } else {
                    msgEl.className = 'my-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                    msgEl.textContent = 'Error: ' + result.message;
                }
            } catch (err) {
                msgEl.className = 'my-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                msgEl.textContent = 'An unexpected error occurred.';
            }
        });
    }
});
</script>
<?php include_once 'common/bottom.php'; ?>