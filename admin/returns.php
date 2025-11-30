<?php
// File: returns.php (REVISED with Search and User Notification)
// Location: /admin/
include_once 'common/header.php';

$message = '';
$message_type = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_id']) && isset($_POST['status'])) {
    $return_id = (int)$_POST['return_id'];
    $new_status = $_POST['status'];
    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE returns SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $return_id);
        $stmt_update->execute();
        $details_message = "Your return request status has been updated to: " . $new_status;
        $stmt_log = $conn->prepare("INSERT INTO return_tracking_history (return_id, status_update, details) VALUES (?, ?, ?)");
        $stmt_log->bind_param("iss", $return_id, $new_status, $details_message);
        $stmt_log->execute();
        
        // --- NEW: Send notification to user ---
        $user_id_stmt = $conn->prepare("SELECT user_id, order_id FROM returns WHERE id = ?");
        $user_id_stmt->bind_param("i", $return_id);
        $user_id_stmt->execute();
        $return_data = $user_id_stmt->get_result()->fetch_assoc();
        if ($return_data) {
            $user_notif_message = "Your return request for Order #{$return_data['order_id']} has been updated to: $new_status.";
            $user_notif_link = 'order_details.php?id=' . $return_data['order_id'];
            $user_notif_stmt = $conn->prepare("INSERT INTO user_notifications (user_id, message, link) VALUES (?, ?, ?)");
            $user_notif_stmt->bind_param("iss", $return_data['user_id'], $user_notif_message, $user_notif_link);
            $user_notif_stmt->execute();
        }
        // --- END NEW ---

        $conn->commit();
        $message = "Status updated successfully!";
        $message_type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Failed to update status: " . $e->getMessage();
        $message_type = 'error';
    }
}

// --- Search Logic ---
$search_query = trim($_GET['search_query'] ?? '');

$sql = "
    SELECT r.*, u.name as user_name, u.phone as user_phone
    FROM returns r JOIN users u ON r.user_id = u.id
";
$params = [];
$types = '';

if (!empty($search_query)) {
    $search_id = (int) ltrim(trim($search_query), '#');
    $sql .= " WHERE r.id = ? OR r.order_id = ?";
    $params = [$search_id, $search_id];
    $types = "ii";
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$returns = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statuses = ['Pending', 'Approved', 'Rejected', 'Completed'];
?>

<h1 class="text-2xl font-bold mb-6">Manage Returns & Refunds</h1>

<?php if ($message): ?>
<div class="p-3 rounded-md mb-4 text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<!-- Search Form -->
<div class="mb-4">
    <form method="GET" action="returns.php" class="flex items-center">
        <input type="text" name="search_query" placeholder="Search by Request ID or Order ID" value="<?php echo htmlspecialchars($search_query); ?>" class="w-full max-w-sm p-2 border rounded-l-md focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_query)): ?>
            <a href="returns.php" class="ml-4 text-sm text-gray-600 hover:text-gray-800 underline">Clear Search</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-max">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Request Info</th>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Reason & Image</th>
                    <th class="p-3 text-left">Refund Details</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">No return requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $return): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 text-sm">
                            <p><strong>Req ID:</strong> #<?php echo $return['id']; ?></p>
                            <p><strong>Order ID:</strong> <a href="order_detail.php?id=<?php echo $return['order_id']; ?>" class="text-blue-500 hover:underline">#<?php echo $return['order_id']; ?></a></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($return['return_type']); ?></p>
                        </td>
                        <td class="p-3 text-sm">
                            <p class="font-medium"><?php echo htmlspecialchars($return['user_name']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($return['user_phone']); ?></p>
                        </td>
                        <td class="p-3 text-sm">
                            <p class="font-semibold"><?php echo htmlspecialchars($return['reason']); ?></p>
                            <?php if (!empty($return['return_image'])): ?>
                                <a href="../uploads/returns/<?php echo htmlspecialchars($return['return_image']); ?>" target="_blank" class="text-blue-500 hover:underline mt-1 inline-block">View Image</a>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-xs">
                            <?php if ($return['return_type'] == 'Refund' && !empty($return['payment_type'])): ?>
                                <p class="font-semibold uppercase"><?php echo $return['payment_type']; ?></p>
                                <?php if ($return['payment_type'] == 'bank'): ?>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($return['account_holder_name']); ?></p>
                                    <p><strong>Acc:</strong> <?php echo htmlspecialchars($return['account_number']); ?></p>
                                    <p><strong>IFSC:</strong> <?php echo htmlspecialchars($return['ifsc_code']); ?></p>
                                    <p><strong>Bank:</strong> <?php echo htmlspecialchars($return['bank_name']); ?></p>
                                <?php else: ?>
                                    <p><strong>ID:</strong> <?php echo htmlspecialchars($return['upi_id']); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <form method="POST">
                                <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
                                <select name="status" class="p-1 border rounded-md" onchange="this.form.submit()">
                                    <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo ($return['status'] == $status) ? 'selected' : ''; ?>>
                                        <?php echo $status; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>