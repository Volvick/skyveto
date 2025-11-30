<?php
// File: payment_upi.php
// Location: /
// FINAL REVISION: Corrected a critical error and added the Order ID to the UPI Transaction Reference for better tracking.
include_once 'common/config.php';
check_login();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id === 0) {
    redirect('order.php');
}

// Fetch order details and the admin's UPI ID
$stmt = $conn->prepare("SELECT o.id, o.total_amount, u.name as user_name, ps.upi_id FROM orders o JOIN users u ON o.user_id = u.id, payment_settings ps WHERE o.id = ? AND ps.id = 1");
if ($stmt === false) {
    // This provides a graceful exit if the DB connection is already lost before this query.
    die("Database error: Could not prepare the statement to fetch order details.");
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_details = $stmt->get_result()->fetch_assoc();
$stmt->close(); // It's good practice to close the statement right after use.

if (!$order_details || empty($order_details['upi_id'])) {
    redirect('order.php');
}

// --- FIX APPLIED HERE: Added Transaction Reference (tr) to the UPI string ---
$upi_id = $order_details['upi_id'];
$amount = number_format($order_details['total_amount'], 2, '.', '');
$merchant_name = 'Skyveto';
$note = 'Payment for Skyveto'; // A general note
$transaction_ref = 'SKYVETO-ORDER-' . $order_details['id']; // The unique, non-editable Order ID

$upi_string = "upi://pay?pa=" . urlencode($upi_id)
            . "&pn=" . urlencode($merchant_name)
            . "&am=" . $amount
            . "&cu=INR"
            . "&tn=" . urlencode($note)
            . "&tr=" . urlencode($transaction_ref); // This is the new, important part

$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upi_string);
// --- END OF FIX ---

include_once 'common/header.php';
?>

<main class="p-4 text-center">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Complete Your Payment</h1>
    <p class="text-gray-600 mb-4">Scan the QR code, complete the payment, then enter the UTR below.</p>

    <!-- QR Code and Details -->
    <div class="bg-white p-6 rounded-lg shadow-lg inline-block">
        <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code" class="w-64 h-64 mx-auto">
        
        <div class="mt-4 text-left">
            <p class="text-sm"><strong>Amount:</strong> <span class="font-bold text-xl">₹<?php echo $amount; ?></span></p>
            <p class="text-sm"><strong>Payable to:</strong> <?php echo htmlspecialchars($merchant_name); ?></p>
            <p class="text-sm"><strong>UPI ID:</strong> <?php echo htmlspecialchars($upi_id); ?></p>
            <p class="text-sm"><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
        </div>
    </div>

    <!-- UTR Confirmation Form -->
    <div class="mt-6 max-w-sm mx-auto">
        <form id="confirm-payment-form" class="space-y-4">
            <input type="hidden" name="action" value="confirm_payment">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <div>
                <label for="utr" class="block text-sm font-medium text-gray-700">Enter UTR / Transaction ID</label>
                <input type="text" name="utr" id="utr" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" placeholder="Find this in your UPI app after paying">
            </div>
            <div id="payment-form-msg" class="text-sm"></div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700">
                Confirm Payment
            </button>
        </form>
    </div>

    <!-- After Payment Notes -->
    <div class="mt-8 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 text-left max-w-sm mx-auto">
        <p class="font-bold">Having Trouble?</p>
        <p class="text-sm">If you have paid but are facing issues, please <a href="help_center.php" class="font-semibold underline">Contact Customer Care</a> with your Order ID.</p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const confirmForm = document.getElementById('confirm-payment-form');
    if (confirmForm) {
        confirmForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('payment-form-msg');
            const button = confirmForm.querySelector('button[type="submit"]');
            
            msgEl.textContent = 'Confirming...';
            msgEl.className = 'text-sm text-yellow-600';
            button.disabled = true;

            try {
                const formData = new FormData(confirmForm);
                const response = await fetch('payment_upi_ajax.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    msgEl.className = 'text-sm text-green-600';
                    msgEl.textContent = result.message;
                    setTimeout(() => {
                        window.location.href = 'order_details.php?id=<?php echo $order_id; ?>';
                    }, 2000);
                } else {
                    msgEl.className = 'text-sm text-red-600';
                    msgEl.textContent = 'Error: ' + result.message;
                    button.disabled = false;
                }
            } catch (error) {
                msgEl.className = 'text-sm text-red-600';
                msgEl.textContent = 'An unexpected network error occurred.';
                button.disabled = false;
            }
        });
    }
});
</script>

<?php
include_once 'common/bottom.php';
// There should be absolutely no PHP code after this line.
?>