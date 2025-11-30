<?php
// File: payment_settings.php (CORRECTED VERSION)
// Location: /admin/
include_once 'common/header.php'; // This correctly includes the database connection

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $online_payment_enabled = isset($_POST['online_payment_enabled']) ? 1 : 0;
    $paytm_environment = $_POST['paytm_environment'];
    $paytm_merchant_id = trim($_POST['paytm_merchant_id']);
    $paytm_merchant_key = trim($_POST['paytm_merchant_key']);
    
    $stmt = $conn->prepare("UPDATE payment_settings SET 
        online_payment_enabled = ?, 
        paytm_environment = ?, 
        paytm_merchant_id = ?, 
        paytm_merchant_key = ? 
        WHERE id = 1");
    $stmt->bind_param("isss", $online_payment_enabled, $paytm_environment, $paytm_merchant_id, $paytm_merchant_key);
    
    if ($stmt->execute()) {
        $message = 'Payment settings updated successfully!'; 
        $message_type = 'success';
    } else {
        $message = 'Error updating settings.'; 
        $message_type = 'error';
    }
}

$settings = $conn->query("SELECT * FROM payment_settings WHERE id = 1")->fetch_assoc();
?>

<h1 class="text-2xl font-bold mb-6">Online Payment Gateway Settings</h1>

<div class="bg-white p-6 rounded-xl shadow-lg max-w-2xl mx-auto">
    <?php if ($message): ?>
    <div class="p-3 rounded-md mb-4 text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <p class="text-sm text-gray-600 mb-4">Configure your online payment provider. Leave credentials blank to disable the online payment option on the checkout page.</p>

    <form method="POST" class="space-y-6">
        
        <div>
            <label for="online_payment_enabled" class="flex items-center cursor-pointer">
                <div class="relative">
                    <input type="checkbox" id="online_payment_enabled" name="online_payment_enabled" class="sr-only" value="1" <?php echo ($settings['online_payment_enabled'] ?? 0) == 1 ? 'checked' : ''; ?>>
                    <div class="block bg-gray-600 w-14 h-8 rounded-full"></div>
                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                </div>
                <div class="ml-3 text-gray-700 font-medium">
                    Enable Online Payments
                </div>
            </label>
        </div>

        <hr>

        <h3 class="text-lg font-semibold text-gray-700">Paytm Business Credentials</h3>
        <div>
            <label for="paytm_environment" class="block font-medium">Environment</label>
            <select name="paytm_environment" id="paytm_environment" class="w-full p-2 border rounded-md mt-1">
                <option value="sandbox" <?php echo (($settings['paytm_environment'] ?? 'sandbox') == 'sandbox') ? 'selected' : ''; ?>>Sandbox (For Testing)</option>
                <option value="production" <?php echo (($settings['paytm_environment'] ?? '') == 'production') ? 'selected' : ''; ?>>Production (Live)</option>
            </select>
        </div>
        <div>
            <label for="paytm_merchant_id" class="block font-medium">Paytm Merchant ID</label>
            <input type="text" id="paytm_merchant_id" name="paytm_merchant_id" value="<?php echo htmlspecialchars($settings['paytm_merchant_id'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1" placeholder="Enter your MID">
        </div>
        <div>
            <label for="paytm_merchant_key" class="block font-medium">Paytm Merchant Key</label>
            <input type="password" id="paytm_merchant_key" name="paytm_merchant_key" value="<?php echo htmlspecialchars($settings['paytm_merchant_key'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1" placeholder="Enter your Merchant Key">
        </div>
        
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 mt-4">Save Settings</button>
    </form>
</div>
<style>
/* Simple toggle switch CSS */
input:checked ~ .dot { transform: translateX(100%); }
input:checked ~ .block { background-color: #2563eb; }
</style>
<?php include_once 'common/bottom.php'; ?>