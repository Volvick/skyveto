<?php
// File: checkout_ajax.php (FINAL - PERMANENT FIX)
// Location: /
include_once __DIR__ . '/common/config.php';
header('Content-Type: application/json');

// This will now use the new, smarter login check from config.php
// If the user is logged out, it will correctly send a JSON error instead of crashing.
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$action = $_POST['action'];
$response = ['status' => 'error', 'message' => 'Invalid action.'];
$user_id = get_user_id();

// --- Get Addresses Action ---
if ($action == 'get_addresses') {
    $addresses = [];
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt->close();
    $response = ['status' => 'success', 'data' => $addresses];
}

// --- Add Address Action ---
if ($action == 'add_address') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $full_address = trim($_POST['full_address']);
    
    if (empty($name) || empty($phone) || empty($full_address)) {
        $response['message'] = 'All fields are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO addresses (user_id, name, phone, full_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $name, $phone, $full_address);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Address added successfully.'];
        } else {
            $response['message'] = 'Failed to add address.';
        }
        $stmt->close();
    }
}

// --- Place Order Action ---
if ($action == 'place_order') {
    $address_id = $_POST['address_id'] ?? 0;
    if (empty($address_id)) {
        $response['message'] = 'Please select a shipping address.';
        echo json_encode($response);
        exit();
    }

    $stmt_addr = $conn->prepare("SELECT full_address FROM addresses WHERE id = ? AND user_id = ?");
    $stmt_addr->bind_param("ii", $address_id, $user_id);
    $stmt_addr->execute();
    $address_result = $stmt_addr->get_result()->fetch_assoc();
    if (!$address_result) {
        $response['message'] = 'Invalid address selected.';
        echo json_encode($response);
        exit();
    }
    $shipping_address_text = $address_result['full_address'];

    $cart_items = [];
    $total_amount = 0;
    $stmt_cart = $conn->prepare("SELECT c.product_id, c.quantity, c.size, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    if ($result_cart->num_rows === 0) {
        $response['message'] = 'Your cart is empty.';
        echo json_encode($response);
        exit();
    }
    while ($row = $result_cart->fetch_assoc()) {
        $cart_items[] = $row;
        $total_amount += $row['price'] * $row['quantity'];
    }

    $conn->begin_transaction();
    try {
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, address_id, total_amount, shipping_address, status) VALUES (?, ?, ?, ?, 'Placed')");
        $stmt_order->bind_param("iids", $user_id, $address_id, $total_amount, $shipping_address_text);
        $stmt_order->execute();
        $order_id = $conn->insert_id;

        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, size, price) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt_items->bind_param("iiisd", $order_id, $item['product_id'], $item['quantity'], $item['size'], $item['price']);
            $stmt_items->execute();
        }

        $stmt_clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();

        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Order placed successfully!', 'order_id' => $order_id];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Failed to place order. Please try again.';
    }
}

echo json_encode($response);
exit();