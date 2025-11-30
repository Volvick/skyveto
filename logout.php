<?php
// File: logout.php
// Location: /
// REVISED: Clears the "Remember Me" cookie on logout.
include_once 'common/config.php';
$user_id = get_user_id();

// --- NEW: Clear Remember Me token from database ---
if ($user_id > 0) {
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, remember_token_expires_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
// --- END NEW ---

// --- NEW: Expire the browser cookie ---
setcookie('remember_me_token', '', time() - 3600, "/");

// Unset all session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['lang']);
unset($_SESSION['language_selected']);
unset($_SESSION['applied_coupon']);

// Redirect to login page
redirect('login.php');
?>