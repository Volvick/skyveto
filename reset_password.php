<?php
// File: reset_password.php
// Location: /
// REVISED: Secure page for setting a new password via token.
include_once 'common/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$is_token_valid = false;

if (empty($token)) {
    $error = "Invalid password reset link.";
} else {
    // Check if the token is valid and not expired before showing the form
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $is_token_valid = true;
    } else {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm bg-white p-6 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Set a New Password</h1>
        </div>
        
        <?php if (!$is_token_valid): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded text-center">
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="forgot_password.php" class="mt-4 inline-block text-sm text-blue-600 hover:underline">Request a New Link</a>
            </div>
        <?php else: ?>
            <form id="reset-password-form" class="space-y-4">
                <input type="hidden" name="action" value="reset_password_with_token">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="new_password" class="text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" id="new_password" name="new_password" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
                </div>
                 <div>
                    <label for="confirm_password" class="text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
                </div>
                <div id="message" class="text-sm text-center"></div>
                <button type="submit" class="w-full flex justify-center py-2.5 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-spinner fa-spin mr-2 hidden"></i>Save New Password
                </button>
            </form>
        <?php endif; ?>
    </div>
<script>
    const form = document.getElementById('reset-password-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageEl = document.getElementById('message');
            const spinner = form.querySelector('.fa-spinner');
            const button = form.querySelector('button[type="submit"]');

            messageEl.textContent = '';
            spinner.classList.remove('hidden');
            button.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch('forgot_password_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    // --- FIX: Redirect with parameters for a better UX on the login page ---
                    const phoneParam = result.phone ? `&phone=${encodeURIComponent(result.phone)}` : '';
                    window.location.href = `login.php?reset_success=true${phoneParam}`;
                } else {
                    messageEl.className = 'text-sm text-center text-red-600 p-2';
                    messageEl.textContent = result.message;
                }
            } catch (error) {
                messageEl.className = 'text-sm text-center text-red-600 p-2';
                messageEl.textContent = 'An error occurred. Please try again.';
            } finally {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        });
    }
</script>
</body>
</html>