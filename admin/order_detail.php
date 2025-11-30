<?php
// File: order_detail.php (REVISED AND COMPLETE)
// Location: /admin/
include_once 'common/header.php'; 

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header("Location: order.php");
    exit();
}

// --- DATA FETCHING ---
// 1. Fetch main order details including user info
$stmt_order = $conn->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();
$stmt_order->close();

if (!$order) {
    echo '<main class="p-4 text-center"><h1>Order Not Found</h1></main>';
    include_once 'common/bottom.php';
    exit;
}

// 2. Fetch order items
$order_items = [];
$stmt_items = $conn->prepare("
    SELECT oi.quantity, oi.price, oi.size, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
while ($row = $result_items->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt_items->close();

// 3. Fetch tracking history
$tracking_history = [];
$stmt_tracking = $conn->prepare("SELECT * FROM order_tracking_history WHERE order_id = ? ORDER BY created_at DESC");
$stmt_tracking->bind_param("i", $order_id);
$stmt_tracking->execute();
$result_tracking = $stmt_tracking->get_result();
while ($row = $result_tracking->fetch_assoc()) {
    $tracking_history[] = $row;
}
$stmt_tracking->close();

$order_statuses = ['Placed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Order Details #<?php echo $order['id']; ?></h1>
    <a href="order.php" class="text-blue-600 hover:underline">&larr; Back to Orders</a>
</div>

<div id="action-message" class="hidden mb-4 p-3 rounded-md text-center"></div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: Order & User Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Items in this Order -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-lg font-semibold border-b pb-3 mb-4">Items</h2>
            <div class="space-y-4">
                <?php foreach($order_items as $item): ?>
                <div class="flex items-center space-x-4">
                    <!-- FIX: Use get_image_url() helper function here -->
                    <img src="<?php echo get_image_url($item['image']); ?>" class="w-16 h-16 rounded-md object-cover">
                    <div class="flex-1">
                        <p class="font-semibold"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p class="text-sm text-gray-500">
                            Qty: <?php echo $item['quantity']; ?>
                            <?php if(!empty($item['size'])): ?> | Size: <?php echo htmlspecialchars($item['size']); ?> <?php endif; ?>
                        </p>
                    </div>
                    <p class="font-semibold">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tracking History -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
             <h2 class="text-lg font-semibold border-b pb-3 mb-4">Tracking History</h2>
             <?php if(empty($tracking_history)): ?>
                <p class="text-gray-500">No tracking history yet. Add the first update above.</p>
             <?php else: ?>
                <ul class="space-y-4">
                <?php foreach($tracking_history as $track): ?>
                    <li class="text-sm border-l-4 pl-4 <?php echo $track['status_update'] == 'Cancelled' ? 'border-red-500' : 'border-blue-500'; ?>">
                        <p class="font-bold"><?php echo htmlspecialchars($track['status_update']); ?></p>
                        <p class="text-gray-700"><?php echo htmlspecialchars($track['details']); ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo date('d M Y, h:i A', strtotime($track['created_at'])); ?></p>
                    </li>
                <?php endforeach; ?>
                </ul>
             <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Actions & Summary -->
    <div class="space-y-6">
        <!-- Add Tracking Update Form -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-lg font-semibold mb-3">Add Tracking Update</h2>
            <form id="update-status-form" class="space-y-4">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <div>
                    <label for="status" class="block mb-1 text-sm font-medium">New Status</label>
                    <select id="status" name="status" class="w-full p-2 border rounded-md">
                        <?php foreach($order_statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo ($order['status'] == $status) ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="details" class="block mb-1 text-sm font-medium">Details (Optional)</label>
                    <textarea id="details" name="details" rows="3" class="w-full p-2 border rounded-md" placeholder="e.g., Shipped via Delhivery, Tracking ID: 12345XYZ"></textarea>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Add Update</button>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
             <h2 class="text-lg font-semibold border-b pb-3 mb-4">Summary</h2>
             <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>User:</span> <span class="font-medium"><?php echo htmlspecialchars($order['user_name']); ?></span></div>
                <div class="flex justify-between"><span>Contact:</span> <span class="font-medium"><?php echo htmlspecialchars($order['user_phone']); ?></span></div>
                <div class="flex justify-between"><span>Payment:</span> <span class="font-medium"><?php echo htmlspecialchars($order['payment_method']); ?> (<?php echo htmlspecialchars($order['payment_status']); ?>)</span></div>
                <div class="flex justify-between"><span>Date:</span> <span class="font-medium"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span></div>
                <hr class="my-2">
                <div class="flex justify-between font-bold text-lg"><span>Total:</span> <span>₹<?php echo number_format($order['total_amount'], 2); ?></span></div>
             </div>
             <h3 class="font-semibold mt-4">Shipping Address:</h3>
             <p class="text-sm text-gray-600 mt-1 whitespace-pre-line"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusForm = document.getElementById('update-status-form');
    const msgEl = document.getElementById('action-message');

    statusForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const response = await fetch('order_ajax.php', { method: 'POST', body: new FormData(statusForm) });
            const result = await response.json();

            msgEl.classList.remove('hidden');
            if (result.status === 'success') {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-green-100 text-green-800';
                msgEl.textContent = result.message;
                setTimeout(() => window.location.reload(), 1500);
            } else {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                msgEl.textContent = 'Error: ' + result.message;
            }
        } catch (error) {
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
            msgEl.textContent = 'An unexpected error occurred.';
        }
        window.scrollTo(0,0);
    });
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>