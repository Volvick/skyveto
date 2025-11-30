<?php
// File: order_ajax.php (REVISED AND COMPLETE)
// Location: /admin/
// NEW: Added user notification trigger
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn || $conn->connect_error) { 
        throw new Exception("Database connection failed for API: " . ($conn->connect_error ?? 'Check config.php')); 
    }

    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $details = trim($_POST['details'] ?? 'Status updated by admin.');
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

        if (empty($new_status) || $order_id === 0) {
            throw new Exception("Missing required data (status or order_id).");
        }

        $conn->begin_transaction();

        try {
            // Step 1: Update the main order status
            $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($stmt_update === false) throw new Exception("Prepare failed (update): " . $conn->error);
            $stmt_update->bind_param("si", $new_status, $order_id);
            $stmt_update->execute();
            
            // Step 2: Add a new entry to the tracking history
            $stmt_log = $conn->prepare("INSERT INTO order_tracking_history (order_id, status_update, details) VALUES (?, ?, ?)");
            if ($stmt_log === false) throw new Exception("Prepare failed (log): " . $conn->error);
            $stmt_log->bind_param("iss", $order_id, $new_status, $details);
            $stmt_log->execute();
            
            // Step 3: If we are marking the order as 'Delivered', update the delivered_at timestamp
            if ($new_status === 'Delivered') {
                $stmt_delivered = $conn->prepare("UPDATE orders SET delivered_at = NOW() WHERE id = ?");
                if ($stmt_delivered === false) throw new Exception("Prepare failed (delivered_at): " . $conn->error);
                $stmt_delivered->bind_param("i", $order_id);
                $stmt_delivered->execute();
            }

            // --- NEW: Step 4: Send a notification to the user ---
            $user_id_stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
            $user_id_stmt->bind_param("i", $order_id);
            $user_id_stmt->execute();
            $user_id = $user_id_stmt->get_result()->fetch_assoc()['user_id'];
            if ($user_id) {
                $user_notif_message = "Your order status for Order #$order_id has been updated to: $new_status.";
                $user_notif_link = 'order_details.php?id=' . $order_id;
                $user_notif_stmt = $conn->prepare("INSERT INTO user_notifications (user_id, message, link) VALUES (?, ?, ?)");
                $user_notif_stmt->bind_param("iss", $user_id, $user_notif_message, $user_notif_link);
                $user_notif_stmt->execute();
            }
            // --- END NEW ---

            $conn->commit();
            $response = ['status' => 'success', 'message' => 'Order status and history updated successfully!'];

        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Database transaction failed: " . $e->getMessage());
        }

    } else {
        throw new Exception("Invalid action specified.");
    }
    
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
if ($conn) $conn->close();
exit();
?>