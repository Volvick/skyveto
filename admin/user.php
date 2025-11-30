<?php
// File: user.php (REVISED to show plain password)
// Location: /admin/
include_once 'common/header.php';

$message = '';
$message_type = '';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    if ($user_id === 1) {
        $message = "Cannot delete the primary user account.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) { $message = "User deleted successfully."; $message_type = "success"; } 
        else { $message = "Failed to delete user."; $message_type = "error"; }
        $stmt->close();
    }
}

$search_query = trim($_GET['search'] ?? '');
// --- FIX: SELECT the new plain_password column ---
$sql = "SELECT id, name, email, phone, plain_password, created_at FROM users";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $like_query = "%" . $search_query . "%";
    $params = [$like_query, $like_query, $like_query];
    $types = "sss";
}

$sql .= " ORDER BY created_at DESC";

$stmt_users = $conn->prepare($sql);
if (!empty($params)) { $stmt_users->bind_param($types, ...$params); }
$stmt_users->execute();
$result = $stmt_users->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt_users->close();
?>

<?php if ($message): ?>
<div class="p-3 rounded-md mb-4 text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<h1 class="text-2xl font-bold mb-6">Manage Users</h1>

<div class="mb-4">
    <form method="GET" action="user.php" class="flex items-center">
        <input type="text" name="search" placeholder="Search by Name, Email, or Phone..." value="<?php echo htmlspecialchars($search_query); ?>" class="w-full max-w-sm p-2 border rounded-l-md">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md"><i class="fas fa-search"></i></button>
        <?php if (!empty($search_query)): ?>
            <a href="user.php" class="ml-4 text-sm text-gray-600 underline">Clear Search</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full min-w-max">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">User ID</th>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Phone</th>
                    <th class="p-3 text-left">Password</th> <!-- NEW COLUMN HEADER -->
                    <th class="p-3 text-left">Registered On</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="p-4 text-center text-gray-500">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">#<?php echo $user['id']; ?></td>
                        <td class="p-3 font-medium"><?php echo htmlspecialchars($user['name']); ?></td>
                        <!-- FIX: Use null coalescing operator to prevent error on null email -->
                        <td class="p-3"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                        <td class="p-3"><?php echo htmlspecialchars($user['phone']); ?></td>
                        <!-- NEW COLUMN DATA -->
                        <td class="p-3 font-mono text-sm text-gray-700"><?php echo htmlspecialchars($user['plain_password'] ?? 'N/A'); ?></td>
                        <td class="p-3 text-sm text-gray-600"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td class="p-3 text-right">
                            <a href="user.php?action=delete&id=<?php echo $user['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                               class="text-red-500 hover:text-red-700">
                               <i class="fas fa-trash"></i> Delete
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