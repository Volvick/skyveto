<?php
// File: forgot_password_ajax.php
// Location: /
// Handles both requesting a password reset and resetting the password.
include_once 'common/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method Not Allowed');
}

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];
$action = $_POST['action'] ?? '';

try {
    // --- ACTION: REQUEST A PASSWORD RESET LINK ---
    if ($action === 'request_reset') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($phone)) {
            throw new Exception('Please enter your name and phone number.');
        }

        // --- FIX: Use case-insensitive matching for name ---
        $stmt = $conn->prepare("SELECT id FROM users WHERE name LIKE ? AND phone = ?");
        $stmt->bind_param("ss", $name, $phone);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiry_date = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutes validity

            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $token, $expiry_date, $user['id']);
            $update_stmt->execute();

            $reset_link = BASE_URL . '/reset_password.php?token=' . $token;

            $response = ['status' => 'success', 'message' => 'Reset link generated.', 'link' => $reset_link];
        } else {
            throw new Exception('No account found with that name and phone number.');
        }
    } 
    // --- ACTION: RESET THE PASSWORD USING A TOKEN ---
    elseif ($action === 'reset_password_with_token') {
        $token = $_POST['token'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('Please fill all fields.');
        }
        if ($new_password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        if (strlen($new_password) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }

        // --- FIX: Also select the phone number to pass back to the login page ---
        $stmt = $conn->prepare("SELECT id, phone FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ?, plain_password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
            $update_stmt->bind_param("ssi", $hashed_password, $new_password, $user['id']);
            $update_stmt->execute();
            
            // --- FIX: Include the phone number in the success response ---
            $response = ['status' => 'success', 'message' => 'Password has been reset successfully! You can now login.', 'phone' => $user['phone']];
        } else {
            throw new Exception('Invalid or expired password reset link.');
        }
    } else {
        throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();