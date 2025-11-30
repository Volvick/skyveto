<?php
// File: api_login.php
// Purpose: Handles login requests from the Android app and saves the FCM token.

// Include your database configuration file
include_once __DIR__ . '/common/config.php'; // Make sure this path is correct

// Set the content type to JSON for API responses
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from the POST request (sent by the app)
    // You might use 'phone_number' or 'email' instead of 'username'
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $fcm_token = $_POST['fcm_token'] ?? ''; // Get the FCM token from the app

    if (empty($username) || empty($password)) {
        $response['message'] = 'Username and password are required.';
        echo json_encode($response);
        exit();
    }

    // IMPORTANT: Select from your CUSTOMERS table (e.g., 'users'), not the 'admin' table.
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // --- SUCCESSFUL LOGIN ---

            $user_id = $user['id'];
            
            // --- START: NEW FEATURE - SAVE FCM TOKEN ---
            if (!empty($fcm_token)) {
                // Save the new token to the database for this user
                $update_stmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
                $update_stmt->bind_param("si", $fcm_token, $user_id);
                $update_stmt->execute();
            }
            // --- END: NEW FEATURE ---

            // Start a session for the user
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user['username'];

            $response['success'] = true;
            $response['message'] = 'Login successful!';
            $response['user_id'] = $user_id; // Send back user ID to the app

        } else {
            $response['message'] = 'Invalid username or password.';
        }
    } else {
        $response['message'] = 'Invalid username or password.';
    }
    
    // Send the JSON response back to the app
    echo json_encode($response);

} else {
    // Handle cases where the request is not POST
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}

?>