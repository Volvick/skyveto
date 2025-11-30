<?php
// File: user_payments.php (REVISED with Search and Phone Number)
// Location: /admin/
include_once 'common/header.php';

// --- NEW: Search Logic ---
$search_query = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.name as user_name, u.email, u.phone as user_phone, upd.*
    FROM user_payment_details upd
    JOIN users u ON upd.user_id = u.id
";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $like_query = "%" . $search_query . "%";
    $params = [$like_query, $like_query, $like_query];
    $types = "sss";
}

$sql .= " ORDER BY upd.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$payment_details = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h1 class="text-2xl font-bold mb-6">User Payment Details</h1>

<!-- NEW: Search Form -->
<div class="mb-4">
    <form method="GET" action="user_payments.php" class="flex items-center">
        <input type="text" name="search" placeholder="Search by User Name, Email, or Phone..." value="<?php echo htmlspecialchars($search_query); ?>" class="w-full max-w-sm p-2 border rounded-l-md focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_query)): ?>
            <a href="user_payments.php" class="ml-4 text-sm text-gray-600 hover:text-gray-800 underline">Clear Search</a>
        <?php endif; ?>
    </form>
</div>


<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-max">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payment_details)): ?>
                    <tr><td colspan="3" class="p-4 text-center text-gray-500">No payment details found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payment_details as $detail): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">
                            <p class="font-medium"><?php echo htmlspecialchars($detail['user_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['email']); ?></p>
                            <!-- NEW: Display Phone Number -->
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($detail['user_phone']); ?></p>
                        </td>
                        <td class="p-3 font-semibold">
                            <?php echo strtoupper($detail['type']); ?>
                        </td>
                        <td class="p-3 text-sm">
                            <?php if ($detail['type'] == 'bank'): ?>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($detail['account_holder_name']); ?></p>
                                <p><strong>Acc No:</strong> <?php echo htmlspecialchars($detail['account_number']); ?></p>
                                <p><strong>IFSC:</strong> <?php echo htmlspecialchars($detail['ifsc_code']); ?></p>
                                <p><strong>Bank:</strong> <?php echo htmlspecialchars($detail['bank_name']); ?></p>
                            <?php else: ?>
                                <p><strong>UPI ID:</strong> <?php echo htmlspecialchars($detail['upi_id']); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'common/bottom.php'; ?>