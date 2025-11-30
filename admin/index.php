<?php
// File: index.php
// Location: /admin/
// REVISED: Added a "Recent Orders" section for better usability.

include_once 'common/header.php'; 

// Fetch stats for the dashboard
$total_users = $conn->query("SELECT COUNT(id) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(id) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'Delivered'")->fetch_assoc()['total'];
$active_products = $conn->query("SELECT COUNT(id) as count FROM products WHERE stock > 0")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'Placed'")->fetch_assoc()['count'];
$cancelled_orders = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'Cancelled'")->fetch_assoc()['count'];

$stats = [
    ['label' => 'Total Users', 'value' => $total_users, 'icon' => 'fa-users', 'color' => 'bg-blue-500'],
    ['label' => 'Total Orders', 'value' => $total_orders, 'icon' => 'fa-receipt', 'color' => 'bg-green-500'],
    ['label' => 'Total Revenue', 'value' => '₹'.number_format($total_revenue ?? 0), 'icon' => 'fa-dollar-sign', 'color' => 'bg-yellow-500'],
    ['label' => 'Active Products', 'value' => $active_products, 'icon' => 'fa-box', 'color' => 'bg-indigo-500'],
    ['label' => 'Pending Orders', 'value' => $pending_orders, 'icon' => 'fa-hourglass-half', 'color' => 'bg-orange-500'],
    ['label' => 'Cancellations', 'value' => $cancelled_orders, 'icon' => 'fa-times-circle', 'color' => 'bg-red-500'],
];

// --- NEW: Fetch the 5 most recent orders ---
$recent_orders = [];
$result_orders = $conn->query("
    SELECT o.id, o.total_amount, o.status, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($stats as $stat): ?>
    <div class="<?php echo $stat['color']; ?> text-white p-6 rounded-xl shadow-lg flex items-center justify-between">
        <div>
            <div class="text-4xl font-bold"><?php echo $stat['value']; ?></div>
            <div class="text-lg"><?php echo $stat['label']; ?></div>
        </div>
        <i class="fas <?php echo $stat['icon']; ?> text-5xl opacity-50"></i>
    </div>
    <?php endforeach; ?>
</div>

<!-- NEW: Recent Orders Table -->
<div class="mt-8 bg-white p-6 rounded-xl shadow-lg">
    <h2 class="text-xl font-semibold mb-4">Recent Orders</h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Order ID</th>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_orders)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">No recent orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 font-semibold">#<?php echo $order['id']; ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td class="p-3">₹<?php echo number_format($order['total_amount']); ?></td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="text-blue-500 hover:underline">
                                View Details <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-8 bg-white p-6 rounded-xl shadow-lg">
    <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
    <div class="flex space-x-4">
        <a href="product.php" class="bg-blue-500 text-white px-6 py-3 rounded-md hover:bg-blue-600"><i class="fas fa-plus mr-2"></i>Add Product</a>
        <a href="order.php" class="bg-green-500 text-white px-6 py-3 rounded-md hover:bg-green-600"><i class="fas fa-receipt mr-2"></i>Manage Orders</a>
        <a href="user.php" class="bg-indigo-500 text-white px-6 py-3 rounded-md hover:bg-indigo-600"><i class="fas fa-users mr-2"></i>Manage Users</a>
    </div>
</div>

<?php 
include_once 'common/bottom.php'; 
?>