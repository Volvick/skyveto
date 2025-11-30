<?php
// File: payment_details_ajax.php (NEW)
// Location: /
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

    if ($action === 'save_payment_details') {
        $type = $_POST['type'] ?? '';

        if ($type === 'bank') {
            $name = trim($_POST['account_holder_name'] ?? '');
            $number = trim($_POST['account_number'] ?? '');
            $ifsc = trim($_POST['ifsc_code'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');

            if (empty($name) || empty($number) || empty($ifsc) || empty($bank_name)) {
                throw new Exception("All bank fields are required.");
            }
            
            // Insert or Update logic
            $stmt = $conn->prepare("
                INSERT INTO user_payment_details (user_id, type, account_holder_name, account_number, ifsc_code, bank_name) 
                VALUES (?, 'bank', ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                account_holder_name = VALUES(account_holder_name), 
                account_number = VALUES(account_number), 
                ifsc_code = VALUES(ifsc_code), 
                bank_name = VALUES(bank_name)
            ");
            $stmt->bind_param("issss", $user_id, $name, $number, $ifsc, $bank_name);

        } elseif ($type === 'upi') {
            $upi_id = trim($_POST['upi_id'] ?? '');
            if (empty($upi_id)) {
                throw new Exception("UPI ID is required.");
            }
            
            // Insert or Update logic
            $stmt = $conn->prepare("
                INSERT INTO user_payment_details (user_id, type, upi_id) 
                VALUES (?, 'upi', ?) 
                ON DUPLICATE KEY UPDATE upi_id = VALUES(upi_id)
            ");
            $stmt->bind_param("is", $user_id, $upi_id);
            
        } else {
            throw new Exception("Invalid payment type specified.");
        }

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => __t('payment_ajax_success')];
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

    }
} catch (Exception $e) {
    $response['message'] = sprintf(__t('payment_ajax_error'), $e->getMessage());
}

echo json_encode($response);
exit();
?>