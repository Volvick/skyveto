<?php
// File: wishlist_ajax.php (NEW)
// Location: /
include_once 'common/config.php';
header('Content-Type: application/json');
check_login(); // This function will handle non-logged in users

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    if ($product_id <= 0) {
        throw new Exception("Invalid Product ID.");
    }
    
    // Check if the item is currently in the user's wishlist
    $stmt_check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $in_wishlist = $stmt_check->get_result()->num_rows > 0;
    $stmt_check->close();

    if ($action === 'check_wishlist') {
        $response = ['status' => 'success', 'in_wishlist' => $in_wishlist];
    } 
    elseif ($action === 'toggle_wishlist') {
        if ($in_wishlist) {
            // Remove from wishlist
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $response = ['status' => 'success', 'action' => 'removed'];
        } else {
            // Add to wishlist
            $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $response = ['status' => 'success', 'action' => 'added'];
        }
    }
    elseif ($action === 'remove_from_wishlist') {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $response = ['status' => 'success', 'message' => 'Item removed from wishlist.'];
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>