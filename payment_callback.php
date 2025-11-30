<?php
// File: payment_callback.php
// Location: /
// REVISED: Now updates the existing "Pending Payment" order.

require_once __DIR__ . '/PaytmSDK/vendor/autoload.php';
use paytmpg\pg\constants\LibraryConstants;
use paytmpg\pg\constants\MerchantProperties;
use paytmpg\pg\utils\EncDecUtil;

include_once 'common/config.php';

$paytmChecksum = "";
$paramList = array();
$isValidChecksum = "FALSE";

$paramList = $_POST;
$paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";

$pg_settings = $conn->query("SELECT paytm_merchant_key, paytm_environment FROM payment_settings WHERE id = 1")->fetch_assoc();
if (!$pg_settings) { die("Payment gateway settings not found."); }

$environment = ($pg_settings['paytm_environment'] === 'production') ? LibraryConstants::PRODUCTION_ENVIRONMENT : LibraryConstants::STAGING_ENVIRONMENT;
MerchantProperties::setMerchantKey($pg_settings['paytm_merchant_key']);
MerchantProperties::setEnvironment($environment);

$isValidChecksum = EncDecUtil::verifySignature($paramList, MerchantProperties::getMerchantKey(), $paytmChecksum);

// --- FIX APPLIED HERE: Extract the real order ID ---
$order_id_parts = explode('_', $_POST['ORDERID']);
$order_id = (int)($order_id_parts[1] ?? 0); // Safely get the real order ID
// --- END OF FIX ---

if ($isValidChecksum == "TRUE") {
    if ($_POST["STATUS"] == "TXN_SUCCESS") {
        $transaction_ref = $_POST['TXNID'];
        
        // --- FIX APPLIED HERE: Update the order instead of creating a new one ---
        $stmt = $conn->prepare("UPDATE orders SET status = 'Placed', payment_status = 'Paid', transaction_ref = ? WHERE id = ? AND status = 'Pending Payment'");
        $stmt->bind_param("si", $transaction_ref, $order_id);
        $stmt->execute();
        
        header("Location: order.php?success=true&order_id=" . $order_id);
        exit();
    } else {
        // Payment failed
        $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled', payment_status = 'Failed' WHERE id = ? AND status = 'Pending Payment'");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        header("Location: cart.php?payment_failed=true");
        exit();
    }
} else {
    // Checksum mismatched
    header("Location: index.php");
    exit();
}
?>