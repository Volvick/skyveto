<?php
// File: language_ajax.php
// Location: /
include_once 'common/config.php';
header('Content-Type: application/json');
check_login();

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];
$user_id = get_user_id();

try {
    if ($action === 'update_language') {
        $lang_code = $_POST['lang_code'] ?? 'en';
        
        // List of supported languages to prevent invalid data injection
        $supported_langs = ['en', 'hi', 'mr', 'bn', 'te', 'ta', 'gu', 'kn', 'ml', 'or', 'pa'];

        if (!in_array($lang_code, $supported_langs)) {
            throw new Exception("Unsupported language selected.");
        }

        // Update the user's language in the database
        $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
        $stmt->bind_param("si", $lang_code, $user_id);
        
        if ($stmt->execute()) {
            // Update the language in the current session
            $_SESSION['lang'] = $lang_code;
            $response = ['status' => 'success', 'message' => 'Language updated successfully!'];
        } else {
            throw new Exception("Failed to update language preference.");
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();