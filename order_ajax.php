<?php
// File: order_ajax.php
// Location: /
// This file handles background requests from the order page.
include_once 'common/config.php';
check_login(); // Ensure user is logged in

header('Content-Type: application/json');
$user_id = get_user_id();
$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed.");
    }

    if ($action === 'submit_review') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $review_text = trim($_POST['review_text'] ?? '');

        if ($product_id === 0 || $rating < 1 || $rating > 5) {
            throw new Exception(__t('review_invalid_product'));
        }

        // Check if user has already reviewed this product
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
if ($conn) $conn->close();
exit();
?>