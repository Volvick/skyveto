<?php
// File: request_return.php (REVISED for Refund Flow)
// Location: /
include_once 'common/config.php';
check_login();
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id === 0) { redirect('order.php'); }

$return_reasons = [
    "Received a broken or damaged item", "Received the wrong item", "Item does not match the description",
    "Quality is not as expected", "Size or fit issues", "Other"
];

include_once 'common/header.php';
?>
<main class="p-4">
    <a href="order_details.php?id=<?php echo $order_id; ?>" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Back to Order Details</a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Request Refund / Replacement</h1>
    <div id="form-message" class="hidden mb-4 p-3 rounded-md text-center"></div>

    <form id="return-form" class="bg-white p-4 rounded-lg shadow-sm space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="action" value="request_return">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
        
        <div>
            <label class="text-sm font-medium">Request Type</label>
            <select name="return_type" required class="mt-1 w-full p-2 border rounded-md">
                <option value="">-- Select an option --</option>
                <option value="Replacement">Replacement (Get a new item)</option>
                <option value="Refund">Refund (Return item and get money back)</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Reason for Return</label>
            <select name="reason" required class="mt-1 w-full p-2 border rounded-md">
                <option value="">-- Select a reason --</option>
                <?php foreach($return_reasons as $reason): ?>
                <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Upload a Photo of the Item</label>
            <input type="file" name="return_image" required class="mt-1 w-full p-2 border rounded-md text-sm">
            <p class="text-xs text-gray-500 mt-1">Please provide a clear photo showing the issue.</p>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600">Submit Request</button>
    </form>
</main>

<script>
document.getElementById('return-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msgEl = document.getElementById('form-message');
    const formData = new FormData(form);

    msgEl.classList.remove('hidden');
    msgEl.className = 'mb-4 p-3 rounded-md text-center bg-yellow-100 text-yellow-800';
    msgEl.textContent = 'Submitting your request...';

    try {
        const response = await fetch('order_details_ajax.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        msgEl.textContent = result.message;
        if (result.status === 'success') {
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-green-100 text-green-800';
            setTimeout(() => window.location.href = `order.php`, 2000);
        } else if (result.status === 'success_redirect') {
            // NEW: Handle the redirect for refund requests
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-blue-100 text-blue-800';
            setTimeout(() => window.location.href = result.redirect_url, 1500);
        } else {
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
        }
    } catch (err) {
        msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
        msgEl.textContent = 'An unexpected error occurred.';
    }
});
</script>
<?php
include_once 'common/bottom.php';
?>