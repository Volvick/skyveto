<?php
// File: config.php
// Location: /common/
// FINAL, ROBUST VERSION with "Remember Me" functionality and Image URL Helper.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DEVELOPER MODE SWITCH (Set to false for production) ---
define('DEVELOPER_MODE', true);
if (DEVELOPER_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// --- ABSOLUTE ROOT PATH (For server-side includes) ---
define('ROOT_PATH', dirname(__DIR__));

// --- AUTOMATIC BASE URL DETECTION (For browser links and images) ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path = str_replace(basename($script_name), '', $script_name);
if (strpos($script_name, '/admin/') !== false) {
    $base_path = str_replace('/admin/', '/', $base_path);
}
if (strpos($script_name, '/common/') !== false) {
    $base_path = str_replace('/common/', '/', $base_path);
}
$base_url = rtrim($protocol . $host . $base_path, '/');
define('BASE_URL', $base_url);

// --- DATABASE CONFIGURATION ---
// FIX: Ensure these credentials are correct as per your hosting control panel.
define('DB_HOST', 'localhost');
define('DB_USER', 'skyvetoc_skyveto'); // Corrected username
define('DB_PASS', 'usha&&682047'); // <-- PASTE YOUR NEW PASSWORD HERE
define('DB_NAME', 'skyvetoc_skyveto'); // Corrected database name

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    if (DEVELOPER_MODE) {
        die("Database Connection Failed: " . $conn->connect_error);
    } else {
        die("Could not connect to the service. Please try again later.");
    }
}
$conn->set_charset("utf8mb4");


// --- REMEMBER ME LOGIC ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me_token'])) {
    $token_from_cookie = $_COOKIE['remember_me_token'];
    
    $stmt_token = $conn->prepare("SELECT id, name, language FROM users WHERE remember_token = ? AND remember_token_expires_at > NOW()");
    $stmt_token->bind_param("s", $token_from_cookie);
    $stmt_token->execute();
    $result_token = $stmt_token->get_result();

    if ($result_token->num_rows === 1) {
        $user = $result_token->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['lang'] = $user['language'] ?? 'en';
        $_SESSION['language_selected'] = true;
        
        $new_token = bin2hex(random_bytes(32));
        $expiry_date = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        
        $stmt_update = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expires_at = ? WHERE id = ?");
        $stmt_update->bind_param("ssi", $new_token, $expiry_date, $user['id']);
        $stmt_update->execute();
        
        setcookie('remember_me_token', $new_token, time() + (30 * 24 * 60 * 60), "/");
    } else {
        setcookie('remember_me_token', '', time() - 3600, "/");
    }
    $stmt_token->close();
}


// --- GLOBAL HELPER FUNCTIONS ---
function redirect($url) {
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    header("Location: " . $url);
    exit();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Authentication required.', 'redirect' => BASE_URL . '/login.php']);
            exit();
        }
        redirect('login.php');
    }
}

function get_user_id() {
    return $_SESSION['user_id'] ?? 0;
}

// --- CORRECTED HELPER FUNCTION TO GET IMAGE URL ---
function get_image_url($image_name, $placeholder = 'placeholder.png') {
    if (empty($image_name)) {
        return BASE_URL . '/uploads/' . $placeholder;
    }
    // Check if it's already a full URL
    if (filter_var($image_name, FILTER_VALIDATE_URL)) {
        return htmlspecialchars($image_name);
    }
    // Otherwise, construct the local URL
    return BASE_URL . '/uploads/' . htmlspecialchars($image_name);
}


date_default_timezone_set('Asia/Kolkata');
include_once ROOT_PATH . '/common/i18n.php';
?>