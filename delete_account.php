<?php
// File: delete_account.php (NEW)
// Location: /
include_once 'common/config.php';
check_login();
include_once 'common/header.php';

$reasons = [
    "I have another account",
    "I'm not happy with the service",
    "I'm concerned about my privacy",
    "I don't find the app useful",
    "Other"
];
?>
<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Back to Profile</a>
    <h1 class="text-2xl font-bold text-red-600 mb-2">Delete Account</h1>
    <p class="text-gray-600 mb-6">We're sorry to see you go. Please note that this action is permanent and cannot be undone.</p>
    
    <div id="form-message" class="hidden mb-4 p-3 rounded-md text-center"></div>

    <form id="delete-account-form" class="bg-white p-4 rounded-lg shadow-sm space-y-6">
        <input type="hidden" name="action" value="delete_account">
        
        <div>
            <label for="reason" class="text-sm font-medium">Why are you leaving?</label>
            <select name="reason" id="reason" required class="mt-1 w-full p-2 border rounded-md">
                <option value="">-- Select a reason --</option>
                <?php foreach ($reasons as $reason): ?>
                <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="flex items-start">
                <input type="checkbox" id="confirm-checkbox" name="confirm" class="h-5 w-5 text-red-600 mt-1">
                <span class="ml-3 text-sm text-gray-700">I understand that my account and all associated data will be permanently deleted. This action cannot be undone.</span>
            </label>
        </div>
        
        <button type="submit" id="delete-submit-btn" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-red-700 disabled:bg-gray-400" disabled>
            Permanently Delete My Account
        </button>
    </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const confirmCheckbox = document.getElementById('confirm-checkbox');
    const deleteBtn = document.getElementById('delete-submit-btn');
    const deleteForm = document.getElementById('delete-account-form');
    const msgEl = document.getElementById('form-message');

    confirmCheckbox.addEventListener('change', () => {
        deleteBtn.disabled = !confirmCheckbox.checked;
    });

    deleteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!confirmCheckbox.checked) {
            alert('Please confirm that you want to delete your account.');
            return;
        }

        if (!confirm('This is your last chance. Are you absolutely sure you want to delete your account? All your data will be lost.')) {
            return;
        }

        msgEl.classList.remove('hidden');
        msgEl.className = 'mb-4 p-3 rounded-md text-center bg-yellow-100 text-yellow-800';
        msgEl.textContent = 'Processing your request...';
        deleteBtn.disabled = true;

        try {
            const formData = new FormData(deleteForm);
            const response = await fetch('delete_account_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-green-100 text-green-800';
                msgEl.textContent = result.message;
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                msgEl.textContent = 'Error: ' + result.message;
                deleteBtn.disabled = false;
            }
        } catch (error) {
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
            msgEl.textContent = 'An unexpected error occurred.';
            deleteBtn.disabled = false;
        }
    });
});
</script>
<?php
include_once 'common/bottom.php';
?>