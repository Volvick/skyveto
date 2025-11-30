<?php
// File: confirmed_orders.php (NEW)
// Location: /admin/
include_once 'common/header.php';

$search_id = $_GET['search_id'] ?? '';

// Base query for confirmed orders (not pending payment or cancelled)
$sql = "
    SELECT o.id, o.total_amount, o.status, o.created_at, u.name as user_name, o.payment_method, o.payment_status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status NOT IN ('Pending Payment', 'Cancelled')
";

// Handle search
if (!empty($search_id)) {
    $order_id_to_search = (int) ltrim(trim($search_id), '#');
    $sql .= " AND o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id_to_search);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql .= " ORDER BY o.created_at DESC";
    $orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

?>

<h1 class="text-2xl font-bold mb-6">Confirmed Orders</h1>

<div class="mb-4">
    <form method="GET" action="confirmed_orders.php" class="flex items-center">
        <input type="text" name="search_id" placeholder="Search by Order ID (e.g., 51 or #51)" value="<?php echo htmlspecialchars($search_id); ?>" class="w-full max-w-sm p-2 border rounded-l-md focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_id)): ?>
            <a href="confirmed_orders.php" class="ml-4 text-sm text-gray-600 hover:text-gray-800 underline">Clear Search</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Order ID</th>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Payment</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="p-4 text-center text-gray-500">No confirmed orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 font-semibold">#<?php echo $order['id']; ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td class="p-3">₹<?php echo number_format($order['total_amount']); ?></td>
                        <td class="p-3 text-sm">
                            <div class="font-semibold"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                            <?php if ($order['payment_method'] !== 'COD'): ?>
                                <div class="text-xs font-semibold <?php echo $order['payment_status'] === 'Paid' ? 'text-green-600' : 'text-orange-500'; ?>">
                                    <?php echo htmlspecialchars($order['payment_status']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
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
<?php include_once 'common/bottom.php'; ?>