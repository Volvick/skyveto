<?php
// File: forgot_password.php
// Location: /
// REVISED: Implements on-page password reset link generation.
include_once 'common/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm bg-white p-6 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Forgot Password</h1>
            <p class="text-gray-500 mt-2">Enter your registered name and phone number to get a password reset link.</p>
        </div>
        <div id="message" class="text-sm text-center p-2"></div>
        <form id="request-reset-form" class="space-y-4">
            <input type="hidden" name="action" value="request_reset">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700">Your Full Name</label>
                <input type="text" id="name" name="name" required placeholder="The name you used for sign up" class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>
            
            <div>
                <label for="phone" class="text-sm font-medium text-gray-700">Registered Phone Number</label>
                <input type="tel" id="phone" name="phone" required placeholder="The phone number you used for sign up" class="mt-1 block w-full px-3 py-2 bg-gray-50 border rounded-md">
            </div>

            <button type="submit" class="w-full flex justify-center py-2.5 rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-spinner fa-spin mr-2 hidden"></i>Get Reset Link
            </button>
        </form>
         <div class="text-center mt-4 text-sm">
            <a href="login.php" class="text-blue-600 hover:underline">Back to Login</a>
            <span class="mx-2 text-gray-300">|</span>
            <!-- FIX: Added Contact Customer Care link -->
            <a href="help_center.php" class="text-gray-600 hover:underline">Contact Customer Care</a>
        </div>
    </div>
<script>
    const form = document.getElementById('request-reset-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageEl = document.getElementById('message');
            const spinner = form.querySelector('.fa-spinner');
            const button = form.querySelector('button[type="submit"]');

            messageEl.innerHTML = '';
            spinner.classList.remove('hidden');
            button.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch('forgot_password_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    messageEl.className = 'text-sm text-center p-3 rounded-md bg-green-100 text-green-800';
                    // --- FIX: Show a button instead of a raw link ---
                    messageEl.innerHTML = `<strong>Success!</strong> A password reset link has been generated. This link is valid for 15 minutes.<br><br>
                                           <a href="${result.link}" class="inline-block bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg">
                                               Continue to Reset Password
                                           </a>`;
                    form.style.display = 'none'; // Hide the form fields after success
                } else {
                    messageEl.className = 'text-sm text-center p-3 rounded-md bg-red-100 text-red-800';
                    messageEl.textContent = result.message;
                }
            } catch (error) {
                messageEl.className = 'text-sm text-center p-3 rounded-md bg-red-100 text-red-800';
                messageEl.textContent = 'An unexpected error occurred. Please try again.';
            } finally {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        });
    }
</script>
</body>
</html>