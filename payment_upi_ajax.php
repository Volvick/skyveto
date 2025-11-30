<?php
// File: payment_upi_ajax.php
// Location: /
// REVISED: Now updates the existing "Pending Payment" order.
include_once 'common/config.php';
header('Content-Type: application/json');
check_login();

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if ($action === 'confirm_payment') {
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $utr = trim($_POST['utr'] ?? '');

        if ($order_id === 0 || empty($utr)) {
            throw new Exception("Please enter the UTR / Transaction ID.");
        }

        // --- FIX APPLIED HERE: Update the order instead of creating a new one ---
        $stmt = $conn->prepare("UPDATE orders SET status = 'Placed', payment_status = 'Paid', transaction_ref = ? WHERE id = ? AND user_id = ? AND status = 'Pending Payment'");
        $stmt->bind_param("sii", $utr, $order_id, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response = ['status' => 'success', 'message' => 'Payment confirmed! Your order has been placed successfully.'];
            } else {
                // This can happen if the order was already confirmed or doesn't belong to the user
                throw new Exception("Could not confirm payment. The order might already be processed or invalid.");
            }
        } else {
            throw new Exception("Database error. Please try again.");
        }
        $stmt->close();
        // --- END OF FIX ---
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>