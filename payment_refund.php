<?php
// File: payment_refund.php (NEW)
// Location: /
include_once 'common/config.php';
check_login();
$user_id = get_user_id();

// Fetch all of the user's refund requests and the associated order amount
$refund_requests = $conn->query("
    SELECT 
        r.order_id,
        r.status as refund_status,
        r.created_at as request_date,
        o.total_amount
    FROM returns r
    JOIN orders o ON r.order_id = o.id
    WHERE r.user_id = $user_id AND r.return_type = 'Refund'
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

include_once 'common/header.php';
?>

<main class="p-4 bg-gray-100 min-h-screen">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Back to Account</a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Payment & Refund History</h1>

    <div class="bg-white p-4 rounded-lg shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-max">
                <thead>
                    <tr class="border-b">
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Refund Amount</th>
                        <th class="p-3 text-left">Request Date</th>
                        <th class="p-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($refund_requests)): ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">You have not requested any refunds.</td></tr>
                    <?php else: ?>
                        <?php foreach ($refund_requests as $request): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-semibold">
                                <a href="order_details.php?id=<?php echo $request['order_id']; ?>" class="text-blue-600 hover:underline">
                                    #<?php echo $request['order_id']; ?>
                                </a>
                            </td>
                            <td class="p-3">₹<?php echo number_format($request['total_amount'], 2); ?></td>
                            <td class="p-3 text-sm text-gray-600"><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php
                                        switch ($request['refund_status']) {
                                            case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'Approved': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                            case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                        }
                                    ?>">
                                    <?php echo htmlspecialchars($request['refund_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
include_once 'common/bottom.php';
?>