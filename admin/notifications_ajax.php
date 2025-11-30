<?php
// File: notifications_ajax.php (NEW)
// Location: /admin/
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    // Action to fetch all notifications for the main page
    if ($action == 'fetch_all') {
        $notifications = [];
        $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100");
        if ($result === false) { throw new Exception("Database query failed: " . $conn->error); }
        while ($row = $result->fetch_assoc()) { $notifications[] = $row; }
        $response = ['status' => 'success', 'data' => $notifications];
    }
    
    // Action to get unread notification counts for the sidebar/header
    else if ($action == 'get_unread_counts') {
        $counts = [];
        $result = $conn->query("SELECT type, COUNT(id) as count FROM notifications WHERE is_read = 0 GROUP BY type");
        if ($result === false) { throw new Exception("Database query failed: " . $conn->error); }
        $total_unread = 0;
        while ($row = $result->fetch_assoc()) { 
            $counts[$row['type']] = (int)$row['count'];
            $total_unread += (int)$row['count'];
        }
        $response = ['status' => 'success', 'counts' => $counts, 'total_unread' => $total_unread];
    }

    // Action to mark a single notification as read
    else if ($action == 'mark_as_read') {
        $id = (int)($_POST['notification_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['status' => 'success'];
            } else {
                throw new Exception('Failed to update notification status.');
            }
        }
    }
    
    // Action to mark all notifications of a certain type as read
    else if ($action == 'mark_type_as_read') {
        $type = trim($_POST['type'] ?? '');
        $allowed_types = ['new_order', 'return_request', 'new_user'];
        if (in_array($type, $allowed_types)) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE type = ?");
            $stmt->bind_param("s", $type);
            if ($stmt->execute()) {
                $response = ['status' => 'success'];
            } else {
                throw new Exception('Failed to update notifications.');
            }
        } else {
            throw new Exception('Invalid notification type.');
        }
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>