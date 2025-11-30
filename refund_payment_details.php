<?php
// File: refund_payment_details.php (REVISED)
// Location: /
include_once 'common/config.php';
check_login();
$return_id = isset($_GET['return_id']) ? (int)$_GET['return_id'] : 0;
if ($return_id === 0) { redirect('order.php'); }

include_once 'common/header.php';
?>
<style>
    .tab-btn.active { border-bottom: 2px solid #3B82F6; color: #3B82F6; }
</style>
<main class="p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Refund Details</h1>
    <p class="text-gray-600 mb-4">Please provide your payment details to receive your refund.</p>
    <div id="form-message" class="hidden mb-4 p-3 rounded-md text-center"></div>

    <div class="bg-white p-4 rounded-lg shadow-sm">
        <div class="flex border-b mb-4">
            <button class="tab-btn active p-3 font-semibold" onclick="showTab('bank')">Bank Account</button>
            <button class="tab-btn p-3 font-semibold" onclick="showTab('upi')">UPI ID</button>
        </div>
        
        <!-- Bank Details Form -->
        <form id="bank-form" class="space-y-4">
            <input type="hidden" name="action" value="save_refund_details">
            <input type="hidden" name="payment_type" value="bank">
            <input type="hidden" name="return_id" value="<?php echo $return_id; ?>">
            <div>
                <label class="text-sm">Account Holder Name</label>
                <input type="text" name="account_holder_name" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="text-sm">Account Number</label>
                <input type="text" name="account_number" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="text-sm">IFSC Code</label>
                <input type="text" name="ifsc_code" required class="mt-1 w-full p-2 border rounded-md">
            </div>
             <!-- NEW FIELD ADDED HERE -->
            <div>
                <label class="text-sm">Bank Name</label>
                <input type="text" name="bank_name" required class="mt-1 w-full p-2 border rounded-md" placeholder="e.g., State Bank of India">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600">Submit Bank Details</button>
        </form>

        <!-- UPI Details Form -->
        <form id="upi-form" class="space-y-4 hidden">
             <input type="hidden" name="action" value="save_refund_details">
             <input type="hidden" name="payment_type" value="upi">
             <input type="hidden" name="return_id" value="<?php echo $return_id; ?>">
            <div>
                <label class="text-sm">UPI ID</label>
                <input type="text" name="upi_id" required class="mt-1 w-full p-2 border rounded-md" placeholder="yourname@upi">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600">Submit UPI ID</button>
        </form>
    </div>
</main>
<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
    if (tabName === 'bank') {
        document.getElementById('bank-form').classList.remove('hidden');
        document.getElementById('upi-form').classList.add('hidden');
    } else {
        document.getElementById('bank-form').classList.add('hidden');
        document.getElementById('upi-form').classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const handleFormSubmit = async (form) => {
        const msgEl = document.getElementById('form-message');
        try {
            const response = await fetch('order_details_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            
            msgEl.classList.remove('hidden');
            if (result.status === 'success') {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-green-100 text-green-800';
                msgEl.textContent = result.message;
                setTimeout(() => window.location.href = `order.php`, 2000);
            } else {
                msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
                msgEl.textContent = 'Error: ' + result.message;
            }
        } catch (error) {
            msgEl.className = 'mb-4 p-3 rounded-md text-center bg-red-100 text-red-800';
            msgEl.textContent = 'An unexpected error occurred.';
        }
    };

    document.getElementById('bank-form').addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(e.target); });
    document.getElementById('upi-form').addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(e.target); });
});
</script>
<?php
include_once 'common/bottom.php';
?>