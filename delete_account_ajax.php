<?php
// File: delete_account_ajax.php (NEW)
// Location: /
include_once 'common/config.php';
header('Content-Type: application/json');
check_login();

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if ($action === 'delete_account') {
        $reason = trim($_POST['reason'] ?? 'No reason provided');

        // Start a transaction to ensure all or nothing is deleted
        $conn->begin_transaction();

        try {
            // 1. Delete from wishlist
            $stmt1 = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $stmt1->bind_param("i", $user_id);
            $stmt1->execute();

            // 2. Delete from cart
            $stmt2 = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();

            // 3. Delete saved addresses
            $stmt3 = $conn->prepare("DELETE FROM user_addresses WHERE user_id = ?");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();
            
            // 4. Delete saved payment details
            $stmt4 = $conn->prepare("DELETE FROM user_payment_details WHERE user_id = ?");
            $stmt4->bind_param("i", $user_id);
            $stmt4->execute();
            
            // 5. Delete reviews
            $stmt5 = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
            $stmt5->bind_param("i", $user_id);
            $stmt5->execute();

            // 6. Anonymize orders (Preserve order history for business analytics)
            // Note: Your `user_id` column in the `orders` table must allow NULL values for this to work.
            $stmt6 = $conn->prepare("UPDATE orders SET user_id = NULL, shipping_address = 'Data Deleted' WHERE user_id = ?");
            $stmt6->bind_param("i", $user_id);
            $stmt6->execute();
            
            // 7. Finally, delete the user from the users table
            $stmt7 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt7->bind_param("i", $user_id);
            $stmt7->execute();

            // If all queries were successful, commit the transaction
            $conn->commit();
            
            // Log the user out completely
            session_destroy();
            setcookie('remember_me_token', '', time() - 3600, "/");

            $response = ['status' => 'success', 'message' => 'Your account has been successfully deleted. You will be redirected shortly.'];

        } catch (Exception $e) {
            // If any query fails, roll back the transaction
            $conn->rollback();
            throw new Exception("Failed to delete account. Please try again. Error: " . $e->getMessage());
        }
    } else {
        throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>