<?php
// File: login.php
// Location: /
// FINAL VERSION for Phone/Password Login
include_once 'common/config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

// --- FIX: Check for parameters from password reset ---
$reset_success = isset($_GET['reset_success']) && $_GET['reset_success'] === 'true';
$prefill_phone = $_GET['phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Login / Sign Up - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .tab-active { border-bottom-width: 3px; border-color: #3B82F6; color: #3B82F6; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm bg-white p-6 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-blue-600">Skyveto</h1>
            <p class="text-gray-500">Your Shopping Destination</p>
        </div>

        <!-- FIX: Success message for password reset -->
        <?php if ($reset_success): ?>
        <div id="reset-success-message" class="mb-4 p-4 rounded-md text-center bg-gradient-to-r from-pink-500 to-blue-500 text-white shadow-lg">
            <h3 class="font-bold text-lg">Password Reset Successful!</h3>
            <p class="text-sm">You can now log in with your new password.</p>
        </div>
        <?php endif; ?>

        <div class="flex border-b mb-6">
            <button id="login-tab-btn" class="flex-1 py-3 text-center font-semibold tab-active" onclick="showTab('login')">Login</button>
            <button id="signup-tab-btn" class="flex-1 py-3 text-center font-semibold text-gray-500" onclick="showTab('signup')">Sign Up</button>
        </div>

        <form id="login-form" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label for="login-phone" class="text-sm font-medium text-gray-700">Phone Number</label>
                <!-- FIX: Pre-fill phone number if available -->
                <input type="tel" id="login-phone" name="phone" value="<?php echo htmlspecialchars($prefill_phone); ?>" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            <div>
                <label for="login-password" class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="login-password" name="password" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            <div class="text-right text-sm">
                <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500">Forgot Password?</a>
            </div>
            <div id="login-message" class="text-sm text-center"></div>
            <button type="submit" class="w-full flex justify-center py-2.5 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-spinner fa-spin mr-2 hidden"></i>Login
            </button>
        </form>

        <form id="signup-form" class="hidden space-y-4">
            <input type="hidden" name="action" value="signup">
            <div>
                <label for="signup-name" class="text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="signup-name" name="name" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            <div>
                <label for="signup-phone" class="text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" id="signup-phone" name="phone" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            <div>
                <label for="signup-password" class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="signup-password" name="password" required class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            <div id="signup-message" class="text-sm text-center"></div>
            <button type="submit" class="w-full flex justify-center py-2.5 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-spinner fa-spin mr-2 hidden"></i>Sign Up
            </button>
        </form>
    </div>

<script>
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');
    const loginTabBtn = document.getElementById('login-tab-btn');
    const signupTabBtn = document.getElementById('signup-tab-btn');
    
    function showTab(tabName) {
        if (tabName === 'login') {
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
            loginTabBtn.classList.add('tab-active', 'text-blue-600');
            loginTabBtn.classList.remove('text-gray-500');
            signupTabBtn.classList.remove('tab-active');
            signupTabBtn.classList.add('text-gray-500');
        } else {
            loginForm.classList.add('hidden');
            signupForm.classList.remove('hidden');
            signupTabBtn.classList.add('tab-active', 'text-blue-600');
            signupTabBtn.classList.remove('text-gray-500');
            loginTabBtn.classList.remove('tab-active');
            loginTabBtn.classList.add('text-gray-500');
        }
    }

    const handleFormSubmit = async (form, messageEl) => {
        const spinner = form.querySelector('.fa-spinner');
        const button = form.querySelector('button[type="submit"]');
        messageEl.textContent = '';
        spinner.classList.remove('hidden');
        button.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch('login_ajax.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                messageEl.textContent = result.message;
                messageEl.className = 'text-sm text-center text-green-600';
                if(result.redirect) {
                    setTimeout(() => window.location.href = result.redirect, 1000);
                } else {
                    showTab('login');
                    document.getElementById('login-phone').value = formData.get('phone');
                    document.getElementById('login-message').textContent = 'Registration successful! Please login.';
                    document.getElementById('login-message').className = 'text-sm text-center text-green-600';
                }
            } else {
                messageEl.textContent = result.message;
                messageEl.className = 'text-sm text-center text-red-600';
            }
        } catch (error) {
            messageEl.textContent = 'An error occurred. Please try again.';
            messageEl.className = 'text-sm text-center text-red-600';
        } finally {
            spinner.classList.add('hidden');
            button.disabled = false;
        }
    };

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(loginForm, document.getElementById('login-message'));
    });

    signupForm.addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit(signupForm, document.getElementById('signup-message'));
    });

    // --- FIX: Auto-focus password field if phone is pre-filled ---
    document.addEventListener('DOMContentLoaded', () => {
        const loginPhoneInput = document.getElementById('login-phone');
        if (loginPhoneInput && loginPhoneInput.value) {
            document.getElementById('login-password').focus();
        }
        const successMessage = document.getElementById('reset-success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s';
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 500);
            }, 5000); // Hide after 5 seconds
        }
    });

</script>
</body>
</html>