<?php
// File: welcome_ajax.php
// Location: /
// FINAL CORRECTED VERSION
include_once 'common/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if ($action === 'set_language') {
        $lang_code = $_POST['lang_code'] ?? 'en';
        $supported_langs = ['en', 'hi', 'mr', 'bn', 'te', 'ta', 'gu', 'kn', 'ml', 'or', 'pa'];

        if (in_array($lang_code, $supported_langs)) {
            $_SESSION['lang'] = $lang_code;
            $_SESSION['language_selected'] = true;
            $response = ['status' => 'success'];
        } else {
            throw new Exception('Unsupported language.');
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>