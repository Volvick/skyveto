<?php
// File: magic_login.php (NEW)
// Location: /
include_once 'common/config.php';

$token = $_GET['token'] ?? '';
$error = '';

if (!empty($token)) {
    // Find the user with this token
    $stmt = $conn->prepare("SELECT id, name, language FROM users WHERE login_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        // Log the user in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['lang'] = $user['language'] ?? 'en';
        $_SESSION['language_selected'] = true;

        // Clear the token so it can't be used again
        $clear_stmt = $conn->prepare("UPDATE users SET login_token = NULL WHERE id = ?");
        $clear_stmt->bind_param("i", $user['id']);
        $clear_stmt->execute();
        
        // Redirect to the homepage
        redirect('index.php');
    } else {
        $error = "Invalid or expired login link. Please try again.";
    }
} else {
    $error = "Login link is missing.";
}

// If there was an error, show it to the user
include_once 'common/header.php';
?>
<main class="p-4 text-center">
    <div class="max-w-md mx-auto mt-10">
        <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
        <h1 class="text-2xl font-bold">Login Failed</h1>
        <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($error); ?></p>
        <a href="login.php" class="mt-6 inline-block bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg">Back to Login</a>
    </div>
</main>
<?php
include_once 'common/bottom.php';
?>