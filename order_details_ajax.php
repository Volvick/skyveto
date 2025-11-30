<?php
// File: order_details_ajax.php
// Location: /
// REVISED: Accepts a cancellation reason and logs it to the tracking history.

include_once 'common/config.php';
header('Content-Type: application/json');
check_login();

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed.");
    }

    if ($action === 'cancel_order') {
        $order_id = (int)$_POST['order_id'];
        $reason = trim($_POST['reason'] ?? 'Order cancelled by user.'); // Get reason from POST

        // Restore stock when cancelling
        $conn->begin_transaction();
        try {
            $items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();

            $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            while ($item = $items_result->fetch_assoc()) {
                $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $update_stock_stmt->execute();
            }
            
            $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Placed'");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                 // Log cancellation in tracking history with the provided reason
                $details_message = "Order cancelled by user. Reason: " . $reason;
                $stmt_log = $conn->prepare("INSERT INTO order_tracking_history (order_id, status_update, details) VALUES (?, 'Cancelled', ?)");
                $stmt_log->bind_param("is", $order_id, $details_message);
                $stmt_log->execute();

                $conn->commit();
                $response = ['status' => 'success', 'message' => __t('order_details_cancel_success')];
            } else {
                throw new Exception(__t('order_details_cancel_fail'));
            }
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } elseif ($action === 'request_return') {
        $order_id = (int)$_POST['order_id'];
        $return_type = $_POST['return_type'];
        $reason = trim($_POST['reason']);
        $image_name = '';

        if(empty($reason) || empty($return_type)){ throw new Exception("Please select a return type and provide a reason."); }
        
        if (isset($_FILES['return_image']) && $_FILES['return_image']['error'] == 0) {
            $target_dir = "uploads/returns/";
            if (!is_dir($target_dir)) { @mkdir($target_dir, 0755, true); }
            $image_name = 'return_' . $order_id . '_' . time() . '_' . basename($_FILES["return_image"]["name"]);
            $target_file = $target_dir . $image_name;
            if (!move_uploaded_file($_FILES["return_image"]["tmp_name"], $target_file)) {
               throw new Exception('Failed to upload proof image.');
            }
        } else {
            throw new Exception('An image of the product is required.');
        }

        $stmt = $conn->prepare("INSERT INTO returns (order_id, user_id, return_type, reason, return_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $order_id, $user_id, $return_type, $reason, $image_name);

        if ($stmt->execute()) {
            $return_id = $conn->insert_id;
            
            $notif_message = "New return request (#" . $return_id . ") for Order ID #" . $order_id;
            $notif_type = "return_request";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (?, ?, ?)");
            $link = 'returns.php?search_query=' . $return_id;
            $notif_stmt->bind_param("sss", $notif_message, $notif_type, $link);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            if ($return_type === 'Refund') {
                $response = ['status' => 'success_redirect', 'message' => 'Request submitted. Please provide payment details for refund.', 'redirect_url' => 'refund_payment_details.php?return_id=' . $return_id];
            } else {
                $response = ['status' => 'success', 'message' => 'Your replacement request has been submitted successfully.'];
            }
        } else {
            throw new Exception("Failed to submit your request. Please try again.");
        }

    } elseif ($action === 'save_refund_details') {
        $return_id = (int)$_POST['return_id'];
        $payment_type = $_POST['payment_type'];

        if ($payment_type === 'bank') {
            $name = trim($_POST['account_holder_name']);
            $number = trim($_POST['account_number']);
            $ifsc = trim($_POST['ifsc_code']);
            $bank_name = trim($_POST['bank_name']);
            if (empty($name) || empty($number) || empty($ifsc) || empty($bank_name)) throw new Exception("All bank fields are required.");
            
            $stmt = $conn->prepare("UPDATE returns SET payment_type='bank', account_holder_name=?, account_number=?, ifsc_code=?, bank_name=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssssii", $name, $number, $ifsc, $bank_name, $return_id, $user_id);

        } elseif ($payment_type === 'upi') {
            $upi_id = trim($_POST['upi_id']);
            if (empty($upi_id)) throw new Exception("UPI ID is required.");
            $stmt = $conn->prepare("UPDATE returns SET payment_type='upi', upi_id=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sii", $upi_id, $return_id, $user_id);
        } else {
            throw new Exception("Invalid payment type.");
        }

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Refund details saved. Our team will review your request.'];
        } else {
            throw new Exception("Database error. Could not save refund details.");
        }
    } elseif ($action === 'submit_review') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $review_text = trim($_POST['review_text'] ?? '');

        if ($product_id === 0 || $rating < 1 || $rating > 5) {
            throw new Exception(__t('review_invalid_product'));
        }

        $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception(__t('review_already_submitted'));
        }
        $check_stmt->close();

        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $product_id, $rating, $review_text);
        
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => __t('review_success')];
        } else {
            throw new Exception(__t('review_failed_to_save'));
        }
        $stmt->close();
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
if (isset($conn) && $conn) $conn->close();
exit();
?>