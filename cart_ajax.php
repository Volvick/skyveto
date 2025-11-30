<?php
// File: cart_ajax.php (REVISED with Usage Limit validation)
// Location: /
include_once 'common/config.php';
header('Content-Type: application/json');
check_login();

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if ($action === 'fetch_available_coupons') {
        $subtotal = 0;
        $cart_stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $subtotal = (float)($cart_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $cart_stmt->close();

        $order_check_stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? LIMIT 1");
        $order_check_stmt->bind_param("i", $user_id);
        $order_check_stmt->execute();
        $is_new_user = $order_check_stmt->get_result()->num_rows === 0;
        $order_check_stmt->close();

        $sql = "SELECT coupon_code, discount_type, discount_value, min_purchase FROM coupons WHERE is_active = 1 AND min_purchase <= ? AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
        if (!$is_new_user) {
            $sql .= " AND is_for_new_user = 0";
        }
        $sql .= " ORDER BY min_purchase ASC";

        $coupons = [];
        $coupon_stmt = $conn->prepare($sql);
        $coupon_stmt->bind_param("d", $subtotal);
        $coupon_stmt->execute();
        $result = $coupon_stmt->get_result();
        while($row = $result->fetch_assoc()){
            $coupons[] = $row;
        }
        $response = ['status' => 'success', 'data' => $coupons];

    } elseif ($action === 'apply_coupon') {
        $coupon_code = strtoupper(trim($_POST['coupon_code']));
        if (empty($coupon_code)) throw new Exception(__t('coupon_enter_code'));

        $stmt = $conn->prepare("SELECT * FROM coupons WHERE coupon_code = ? AND is_active = 1");
        $stmt->bind_param("s", $coupon_code);
        $stmt->execute();
        $coupon = $stmt->get_result()->fetch_assoc();
        if (!$coupon) throw new Exception(__t('coupon_invalid'));
        
        if ($coupon['expiry_date'] && strtotime($coupon['expiry_date']) < strtotime(date('Y-m-d'))) {
            throw new Exception('This coupon has expired.');
        }

        if ($coupon['is_for_new_user'] == 1) {
            $order_check_stmt = $conn->prepare("SELECT id FROM orders WHERE user_id = ? LIMIT 1");
            $order_check_stmt->bind_param("i", $user_id);
            $order_check_stmt->execute();
            if ($order_check_stmt->get_result()->num_rows > 0) {
                throw new Exception('This coupon is valid only on your first order.');
            }
            $order_check_stmt->close();
        }

        // NEW: Check usage limit per user
        $usage_check_stmt = $conn->prepare("SELECT COUNT(id) as usage_count FROM orders WHERE user_id = ? AND coupon_code = ?");
        $usage_check_stmt->bind_param("is", $user_id, $coupon_code);
        $usage_check_stmt->execute();
        $usage_count = (int)$usage_check_stmt->get_result()->fetch_assoc()['usage_count'];
        $usage_check_stmt->close();
        
        if ($usage_count >= $coupon['usage_limit_per_user']) {
            throw new Exception("You have already used this coupon the maximum number of times.");
        }
        // END NEW CHECK

        $subtotal = 0;
        $cart_stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $cart_stmt->bind_param("i", $user_id);
        $cart_stmt->execute();
        $subtotal = (float)($cart_stmt->get_result()->fetch_assoc()['total']);

        if ($subtotal < $coupon['min_purchase']) {
            throw new Exception(sprintf(__t('coupon_min_purchase_required'), $coupon['min_purchase']));
        }

        $discount_amount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discount_amount = ($subtotal * $coupon['discount_value']) / 100;
        } else {
            $discount_amount = $coupon['discount_value'];
        }

        $_SESSION['applied_coupon'] = [
            'code' => $coupon['coupon_code'],
            'discount_amount' => $discount_amount
        ];
        
        $response = [
            'status' => 'success',
            'message' => __t('coupon_applied_success'),
            'discount_amount' => number_format($discount_amount, 2),
            'new_total' => number_format($subtotal - $discount_amount, 2)
        ];

    } elseif ($action === 'remove_coupon') {
        unset($_SESSION['applied_coupon']);
        $response = ['status' => 'success', 'message' => __t('coupon_removed')];
    }

} catch (Exception $e) {
    unset($_SESSION['applied_coupon']);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>