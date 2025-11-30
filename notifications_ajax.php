<?php
// File: notifications_ajax.php (NEW)
// Location: /
include_once __DIR__ . '/common/config.php'; 
header('Content-Type: application/json');

// This check handles non-logged-in users for AJAX requests
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    if ($action == 'get_unread_count') {
        $stmt = $conn->prepare("SELECT COUNT(id) as unread_count FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['unread_count'];
        $stmt->close();
        
        $response = ['status' => 'success', 'unread_count' => $count];
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>