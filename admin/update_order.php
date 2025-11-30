<?php
// At the top of the file, include the helper function
require_once('../fcm_helper.php');
// Include your database connection
require_once('../common/config.php');

// --- Assume this part of the code already exists ---
// Get the order ID and the new status from the admin form
$order_id = $_POST['order_id'];
$new_status = 'Shipped'; // Example status

// Update the order status in your 'orders' table
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $order_id);
$stmt->execute();
// --- End of existing code ---


// ==========================================================
// --- START: NEW CODE TO SEND THE PUSH NOTIFICATION ---
// ==========================================================

// 1. Get the user_id and fcm_token for the user who owns this order
$stmt = $conn->prepare("
    SELECT u.fcm_token 
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && !empty($user['fcm_token'])) {
    $fcm_token = $user['fcm_token'];

    // 2. Create the message title and body
    $title = "Your Order is Shipped!";
    $body = "Great news! Your order #{$order_id} has been shipped.";

    // 3. Call the function from fcm_helper.php to send the notification!
    sendPushNotification($fcm_token, $title, $body);
}

// ==========================================================
// --- END: NEW PUSH NOTIFICATION CODE ---
// ==========================================================


// Redirect the admin back to the dashboard
header("Location: dashboard.php?success=1");
exit();

?>