<?php
// File: coupon_ajax.php (FINAL, ROBUST VERSION)
// Location: /admin/
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    if ($action == 'fetch') {
        $coupons = [];
        // This query now explicitly lists all columns to prevent errors.
        $result = $conn->query("SELECT id, coupon_code, discount_type, discount_value, min_purchase, expiry_date, is_for_new_user, usage_limit_per_user FROM coupons ORDER BY id DESC");
        
        // --- ROBUSTNESS FIX: Check if the query itself failed ---
        if ($result === false) {
            throw new Exception("Database query failed. Please ensure the `coupons` table structure is correct. Error: " . $conn->error);
        }
        
        while ($row = $result->fetch_assoc()) { $coupons[] = $row; }
        $response = ['status' => 'success', 'data' => $coupons];
    } 
    else if ($action == 'add') {
        $code = strtoupper(trim($_POST['coupon_code']));
        $type = $_POST['discount_type'];
        $value = (float)$_POST['discount_value'];
        $min_purchase = (float)$_POST['min_purchase'];
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $is_for_new_user = isset($_POST['is_for_new_user']) ? 1 : 0;
        $usage_limit = (int)($_POST['usage_limit_per_user'] ?? 1);
        if ($usage_limit < 1) {
            $usage_limit = 1;
        }

        if (empty($code) || empty($type) || $value <= 0) {
            throw new Exception("Please fill all fields correctly.");
        }

        $stmt = $conn->prepare("INSERT INTO coupons (coupon_code, discount_type, discount_value, min_purchase, expiry_date, is_for_new_user, usage_limit_per_user) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsii", $code, $type, $value, $min_purchase, $expiry_date, $is_for_new_user, $usage_limit);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Coupon added successfully.'];
        } else {
            throw new Exception('Coupon code might already exist or there was a database error.');
        }
    }
    else if ($action == 'delete') {
        $id = (int)($_POST['coupon_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Coupon deleted.'];
        } else {
            throw new Exception("Failed to delete coupon.");
        }
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>