<?php
// File: login_ajax.php
// Location: /
// FINAL VERSION with "Remember Me" functionality.

include_once 'common/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];
$action = $_POST['action'] ?? '';

try {
    // --- LOGIN ACTION ---
    if ($action === 'login') {
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($phone) || empty($password)) {
            throw new Exception('Please fill in all fields.');
        }
        
        $stmt = $conn->prepare("SELECT id, name, password, language FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['lang'] = $user['language'] ?? 'en';
                $_SESSION['language_selected'] = true; 
                
                // --- NEW: Set Remember Me Cookie ---
                $token = bin2hex(random_bytes(32));
                $expiry_date = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
                
                $update_stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expires_at = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $token, $expiry_date, $user['id']);
                $update_stmt->execute();
                
                setcookie('remember_me_token', $token, time() + (30 * 24 * 60 * 60), "/"); // Set cookie for 30 days
                // --- END NEW ---
                
                $redirect_url = 'index.php';
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_url = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                }

                $response = ['status' => 'success', 'message' => 'Login successful!', 'redirect' => $redirect_url];
            } else {
                throw new Exception('Invalid phone number or password.');
            }
        } else {
            throw new Exception('Invalid phone number or password.');
        }
        $stmt->close();
    }
    // --- SIGN UP ACTION ---
    elseif ($action === 'signup') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($phone) || empty($password)) {
            throw new Exception('Please fill all required fields.');
        }
        if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            throw new Exception('Please enter a valid 10-digit phone number.');
        }
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }

        $stmt_check = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt_check->bind_param("s", $phone);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception('This phone number is already registered.');
        }
        $stmt_check->close();

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_insert = $conn->prepare("INSERT INTO users (name, phone, password, plain_password) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $name, $phone, $hashed_password, $password);
        
        if ($stmt_insert->execute()) {
             $new_user_id = $conn->insert_id;
            
            $notif_message = "A new user has registered: " . htmlspecialchars($name);
            $notif_type = "new_user";
            $notif_stmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (?, ?, ?)");
            $link = 'user.php?search=' . urlencode($phone);
            $notif_stmt->bind_param("sss", $notif_message, $notif_type, $link);
            $notif_stmt->execute();
            $notif_stmt->close();

            $response = ['status' => 'success', 'message' => 'Registration successful! You can now login.'];
        } else {
            throw new Exception('Registration failed. Please try again.');
        }
        $stmt_insert->close();
    } else {
        throw new Exception("Invalid action specified.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
?>