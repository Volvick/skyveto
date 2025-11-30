<?php
// File: otp_verify.php (NEW)
// Location: /
include_once 'common/config.php';

// This identifier can be either an email or a phone number.
$identifier = $_SESSION['login_identifier'] ?? '';
if (empty($identifier)) {
    // If no identifier is in the session, redirect to the login page.
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Verify OTP - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm bg-white p-6 rounded-xl shadow-lg">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Verify OTP</h1>
            <p class="text-gray-500 mt-2">An OTP has been sent to <br><strong class="text-gray-700"><?php echo htmlspecialchars($identifier); ?></strong></p>
        </div>

        <form id="otp-form" class="space-y-4">
            <input type="hidden" name="action" value="verify_otp">
            <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($identifier); ?>">
            <div>
                <label for="otp" class="text-sm font-medium text-gray-700">Enter 6-Digit OTP</label>
                <input type="tel" id="otp" name="otp" required maxlength="6" class="mt-1 block w-full text-center tracking-[1em] text-2xl font-bold p-3 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div id="otp-message" class="text-sm text-center"></div>
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-spinner fa-spin mr-2 hidden"></i>Verify & Proceed
            </button>
        </form>
        <div class="text-center mt-4">
            <a href="login.php" class="text-sm text-blue-600 hover:underline">Change Number or Email</a>
        </div>
    </div>

<script>
    const otpForm = document.getElementById('otp-form');
    if (otpForm) {
        otpForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageEl = document.getElementById('otp-message');
            const spinner = otpForm.querySelector('.fa-spinner');
            const button = otpForm.querySelector('button[type="submit"]');

            messageEl.textContent = '';
            spinner.classList.remove('hidden');
            button.disabled = true;

            try {
                const formData = new FormData(otpForm);
                const response = await fetch('login_ajax.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    messageEl.textContent = result.message;
                    messageEl.className = 'text-sm text-green-600';
                    if (result.redirect) {
                        setTimeout(() => window.location.href = result.redirect, 1000);
                    }
                } else {
                    messageEl.textContent = result.message;
                    messageEl.className = 'text-sm text-red-600';
                }
            } catch (error) {
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.className = 'text-sm text-red-600';
            } finally {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        });
    }
</script>
</body>
</html>