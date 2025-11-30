<?php
// File: checkout.php
// Location: /
// FINAL, COMPLETE VERSION WITH NOTIFICATION TRIGGERS

require_once __DIR__ . '/PaytmSDK/vendor/autoload.php';
use paytmpg\pg\constants\EChannelId;
use paytmpg\pg\constants\EnumCurrency;
use paytmpg\pg\constants\LibraryConstants;
use paytmpg\pg\models\Money;
use paytmpg\pg\models\UserInfo;
use paytmpg\merchant\models\PaymentDetailBuilder;
use paytmpg\pg\process\Payment;
use paytmpg\pg\constants\MerchantProperties;

include_once 'common/config.php';
check_login();
$user_id = get_user_id();
$error = '';

// Check for "Buy Now" flow
$is_buy_now = isset($_GET['buy_now_cart_id']);
$buy_now_cart_id = $is_buy_now ? (int)$_GET['buy_now_cart_id'] : 0;

// AJAX handler for adding a new address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_address') {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => __t('checkout_error_save_address')];
    try {
        $count_stmt = $conn->prepare("SELECT COUNT(id) as address_count FROM user_addresses WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $address_count = (int) $count_stmt->get_result()->fetch_assoc()['address_count'];
        $count_stmt->close();

        if ($address_count >= 50) {
            throw new Exception("You have reached the maximum limit of 50 saved addresses.");
        }

        $full_name = trim($_POST['full_name']); $phone_number = trim($_POST['phone_number']);
        $address_line = trim($_POST['address']); $pincode = trim($_POST['pincode']);
        $district = trim($_POST['district']); $state = trim($_POST['state']); $landmark = trim($_POST['landmark']);

        if (empty($full_name) || empty($phone_number) || empty($address_line) || empty($pincode) || empty($district) || empty($state)) {
            throw new Exception(__t('checkout_error_fill_fields'));
        }
        $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, full_name, phone_number, address, pincode, district, state, landmark) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $full_name, $phone_number, $address_line, $pincode, $district, $state, $landmark);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => __t('checkout_success_save_address')];
        } else {
            throw new Exception(__t('checkout_error_db_save'));
        }
    } catch (Exception $e) { $response['message'] = $e->getMessage(); }
    echo json_encode($response);
    exit();
}

// Fetch payment gateway settings
$pg_settings = $conn->query("SELECT * FROM payment_settings WHERE id = 1")->fetch_assoc();
$is_paytm_enabled = $pg_settings && $pg_settings['online_payment_enabled'] && !empty($pg_settings['paytm_merchant_id']) && !empty($pg_settings['paytm_merchant_key']);
$is_upi_enabled = $pg_settings && !empty($pg_settings['upi_id']);

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $selected_address_id = $_POST['selected_address'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'COD';
    
    $is_buy_now_post = isset($_POST['buy_now_cart_id']);
    $buy_now_cart_id_post = $is_buy_now_post ? (int)$_POST['buy_now_cart_id'] : 0;

    $subtotal_post = 0;
    $cart_items_to_order = [];
    if ($is_buy_now_post) {
        $cart_stmt = $conn->prepare("SELECT c.quantity, c.size, p.id as product_id, p.price, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
        $cart_stmt->bind_param("ii", $buy_now_cart_id_post, $user_id);
    } else {
        $cart_stmt = $conn->prepare("SELECT c.quantity, c.size, p.id as product_id, p.price, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $cart_stmt->bind_param("i", $user_id);
    }
    
    $cart_stmt->execute();
    $result_items = $cart_stmt->get_result();
    while($row = $result_items->fetch_assoc()) {
        $cart_items_to_order[] = $row;
        $subtotal_post += $row['price'] * $row['quantity'];
    }
    
    $user_info_stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $user_info_stmt->bind_param("i", $user_id); $user_info_stmt->execute();
    $user_data = $user_info_stmt->get_result()->fetch_assoc();

    $discount_amount_post = 0.00;
    $coupon_code_post = NULL;
    if (isset($_SESSION['applied_coupon'])) {
        $discount_amount_post = (float)$_SESSION['applied_coupon']['discount_amount'];
        $coupon_code_post = $_SESSION['applied_coupon']['code'];
    }
    $total_amount_post = $subtotal_post - $discount_amount_post;
    
    $shipping_address_text = '';
    if ($selected_address_id > 0) {
        $addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $addr_stmt->bind_param("ii", $selected_address_id, $user_id); $addr_stmt->execute();
        $address_data = $addr_stmt->get_result()->fetch_assoc();
        if ($address_data) {
             $shipping_address_text = $address_data['full_name'] . "\n" . $address_data['phone_number'] . "\n" . $address_data['address'] . ", " . $address_data['district'] . ", " . $address_data['state'] . " - " . $address_data['pincode'];
        }
    }

    if (empty($shipping_address_text)) {
        $error = __t('checkout_error_select_address');
    } elseif (empty($cart_items_to_order)) {
        $error = __t('checkout_error_cart_empty');
    } else {
        if ($payment_method === 'COD') {
            $conn->begin_transaction();
            try {
                $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, payment_status, coupon_code, discount_amount) VALUES (?, ?, ?, 'COD', 'Placed', 'Unpaid', ?, ?)");
                if ($order_stmt === false) throw new Exception("DB Prepare Failed: " . $conn->error);
                $order_stmt->bind_param("idssd", $user_id, $total_amount_post, $shipping_address_text, $coupon_code_post, $discount_amount_post);
                
                $order_stmt->execute();
                $db_order_id = $conn->insert_id;

                // --- TRIGGER NOTIFICATION ---
                $notif_message = "New order (#" . $db_order_id . ") has been placed by " . $user_data['name'] . ".";
                $notif_type = "new_order";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (?, ?, ?)");
                $link = 'order_detail.php?id=' . $db_order_id;
                $notif_stmt->bind_param("sss", $notif_message, $notif_type, $link);
                $notif_stmt->execute();
                $notif_stmt->close();
                // --- END NOTIFICATION ---

                $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (?, ?, ?, ?, ?)");
                $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                foreach ($cart_items_to_order as $item) {
                    $order_item_stmt->bind_param("iiids", $db_order_id, $item['product_id'], $item['quantity'], $item['price'], $item['size']);
                    $order_item_stmt->execute();
                    $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $update_stock_stmt->execute();
                }

                if ($is_buy_now_post) {
                    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $clear_cart_stmt->bind_param("ii", $buy_now_cart_id_post, $user_id);
                } else {
                    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clear_cart_stmt->bind_param("i", $user_id);
                }
                $clear_cart_stmt->execute();
                
                unset($_SESSION['applied_coupon']);
                $conn->commit();
                
                redirect('order.php?success=true&order_id=' . $db_order_id);
            } catch (Exception $e) {
                $conn->rollback();
                $error = sprintf(__t('checkout_error_place_order'), $e->getMessage());
            }
        } else {
            $conn->begin_transaction();
            try {
                $order_status = 'Pending Payment';
                $payment_status = 'Unpaid';
                $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, payment_status, coupon_code, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($order_stmt === false) throw new Exception("DB Prepare Failed: " . $conn->error);
                $order_stmt->bind_param("idsssssd", $user_id, $total_amount_post, $shipping_address_text, $payment_method, $order_status, $payment_status, $coupon_code_post, $discount_amount_post);
                $order_stmt->execute();
                $db_order_id = $conn->insert_id;

                // --- TRIGGER NOTIFICATION ---
                $notif_message = "New pending payment order (#" . $db_order_id . ") created by " . $user_data['name'] . ".";
                $notif_type = "new_order";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (?, ?, ?)");
                $link = 'order_detail.php?id=' . $db_order_id;
                $notif_stmt->bind_param("sss", $notif_message, $notif_type, $link);
                $notif_stmt->execute();
                $notif_stmt->close();
                // --- END NOTIFICATION ---

                $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, size) VALUES (?, ?, ?, ?, ?)");
                $update_stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                foreach ($cart_items_to_order as $item) {
                    $order_item_stmt->bind_param("iiids", $db_order_id, $item['product_id'], $item['quantity'], $item['price'], $item['size']);
                    $order_item_stmt->execute();
                    $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $update_stock_stmt->execute();
                }
                
                if ($is_buy_now_post) {
                    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $clear_cart_stmt->bind_param("ii", $buy_now_cart_id_post, $user_id);
                } else {
                    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clear_cart_stmt->bind_param("i", $user_id);
                }
                $clear_cart_stmt->execute();

                unset($_SESSION['applied_coupon']);
                $conn->commit();
                
                if ($payment_method === 'UPI') {
                    redirect('payment_upi.php?order_id=' . $db_order_id);
                } elseif ($payment_method === 'Online' && $is_paytm_enabled) {
                    $unique_order_id_for_paytm = "SKYVETO_" . $db_order_id . "_" . time();
                    $environment = ($pg_settings['paytm_environment'] === 'production') ? LibraryConstants::PRODUCTION_ENVIRONMENT : LibraryConstants::STAGING_ENVIRONMENT;
                    MerchantProperties::setMid($pg_settings['paytm_merchant_id']);
                    MerchantProperties::setMerchantKey($pg_settings['paytm_merchant_key']);
                    MerchantProperties::setWebsite("WEBSTAGING");
                    MerchantProperties::setEnvironment($environment);
                    MerchantProperties::setCallbackUrl(BASE_URL . "/payment_callback.php");

                    $txnAmount = new Money(EnumCurrency::INR, strval($total_amount_post));
                    $userInfo = new UserInfo("CUST_" . $user_id);
                    if ($user_data && isset($user_data['email'])) $userInfo->setEmail($user_data['email']);
                    if ($user_data && isset($user_data['phone'])) $userInfo->setMobile($user_data['phone']);

                    $paymentDetail = (new PaymentDetailBuilder(EChannelId::WEB, $unique_order_id_for_paytm, $txnAmount, $userInfo))->build();
                    $response = Payment::createTxnToken($paymentDetail);
                    
                    if ($response->isSuccess()) {
                        $txnToken = $response->getResponseObject()->getBody()->getTxnToken();
                        $paytm_url = MerchantProperties::getInitiateTxnUrl();
                        
                        echo '<h2 style="font-family: sans-serif; text-align: center;">Redirecting to payment page... Please do not refresh.</h2>';
                        echo '<form method="post" action="'.$paytm_url.'?mid='.$pg_settings['paytm_merchant_id'].'&orderId='.$unique_order_id_for_paytm.'" name="paytmForm">';
                        echo '<input type="hidden" name="txnToken" value="'.$txnToken.'">';
                        echo '<script type="text/javascript">document.paytmForm.submit();</script>';
                        echo '</form>';
                        exit();
                    } else {
                        throw new Exception("Paytm Error: " . $response->getResponseObject()->getBody()->getResultInfo()->getResultMsg());
                    }
                } else {
                    $error = "Selected payment method is currently unavailable.";
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = sprintf(__t('checkout_error_place_order'), $e->getMessage());
            }
        }
    }
}

// Data Fetching for Display
$addresses = []; $addr_display_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC"); $addr_display_stmt->bind_param("i", $user_id); $addr_display_stmt->execute(); $result_addr_display = $addr_display_stmt->get_result(); while ($row = $result_addr_display->fetch_assoc()) { $addresses[] = $row; }
$subtotal_display = 0; 
if ($is_buy_now) {
    $cart_total_display_stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
    $cart_total_display_stmt->bind_param("ii", $buy_now_cart_id, $user_id);
} else {
    $cart_total_display_stmt = $conn->prepare("SELECT SUM(c.quantity * p.price) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $cart_total_display_stmt->bind_param("i", $user_id);
}
$cart_total_display_stmt->execute(); $subtotal_display = (float)($cart_total_display_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$discount_display = 0; if (isset($_SESSION['applied_coupon'])) { $discount_display = (float)$_SESSION['applied_coupon']['discount_amount']; }
$total_amount_display = $subtotal_display - $discount_display;

include_once 'common/header.php';
?>
<main class="p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('checkout_title'); ?></h1>
    
    <?php if (empty($addresses)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('address-modal').classList.remove('hidden');
            document.getElementById('modal-close-btn').style.display = 'none';
        });
    </script>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php if ($is_buy_now): ?>
            <input type="hidden" name="buy_now_cart_id" value="<?php echo $buy_now_cart_id; ?>">
        <?php endif; ?>

        <div class="bg-white p-4 rounded-lg shadow-sm">
             <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-lg font-semibold"><?php echo __t('checkout_shipping_address'); ?></h2>
                <button type="button" id="add-address-btn" class="text-sm font-semibold text-blue-600 hover:underline"><?php echo __t('checkout_add_new_address'); ?></button>
            </div>
            <div id="address-list" class="space-y-3">
                <?php if (empty($addresses)): ?><p id="no-address-msg" class="text-gray-500 text-center p-4"><?php echo __t('checkout_no_address'); ?></p><?php else: ?><?php foreach ($addresses as $index => $addr): ?><label class="block border rounded-lg p-3 cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500"><input type="radio" name="selected_address" value="<?php echo $addr['id']; ?>" class="hidden" <?php echo $index === 0 ? 'checked' : ''; ?>><p class="font-bold"><?php echo htmlspecialchars($addr['full_name']); ?> <span class="text-sm font-normal text-gray-600"><?php echo htmlspecialchars($addr['phone_number']); ?></span></p><p class="text-sm text-gray-700 mt-1"><?php echo htmlspecialchars($addr['address']); ?>, <?php echo htmlspecialchars($addr['district']); ?>, <?php echo htmlspecialchars($addr['state']); ?> - <?php echo htmlspecialchars($addr['pincode']); ?></p></label><?php endforeach; ?><?php endif; ?>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-sm mt-6">
            <h2 class="text-lg font-semibold mb-3 border-b pb-2"><?php echo __t('checkout_payment_method'); ?></h2>
            <div class="space-y-3">
                <label class="flex items-center p-3 border rounded-md has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500"><input type="radio" name="payment_method" value="COD" checked class="h-4 w-4 text-blue-600"><span class="ml-3 block text-sm font-medium"><?php echo __t('checkout_cod'); ?></span></label>
                <?php if ($is_upi_enabled): ?><label class="flex items-center p-3 border rounded-md has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500"><input type="radio" name="payment_method" value="UPI" class="h-4 w-4 text-blue-600"><span class="ml-3 block text-sm font-medium"><?php echo __t('checkout_upi'); ?></span></label><?php endif; ?>
                <?php if ($is_paytm_enabled): ?><label class="flex items-center p-3 border rounded-md has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500"><input type="radio" name="payment_method" value="Online" class="h-4 w-4 text-blue-600"><span class="ml-3 block text-sm font-medium"><?php echo __t('checkout_paytm'); ?></span></label><?php endif; ?>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-sm mt-6">
             <h2 class="text-lg font-semibold mb-2"><?php echo __t('cart_order_summary'); ?></h2>
             <div class="space-y-2"><div class="flex justify-between text-gray-600"><p><?php echo __t('cart_subtotal'); ?></p><p>₹<?php echo number_format($subtotal_display, 2); ?></p></div><?php if ($discount_display > 0): ?><div class="flex justify-between text-green-600"><p><?php echo __t('cart_discount'); ?></p><p>- ₹<?php echo number_format($discount_display, 2); ?></p></div><?php endif; ?><div class="border-t mt-2 pt-2 flex justify-between font-bold text-xl"><span><?php echo __t('cart_total_amount'); ?></span><span>₹<?php echo number_format($total_amount_display, 2); ?></span></div></div>
        </div>
        <button type="submit" name="place_order" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg mt-6 shadow-lg hover:bg-blue-700"><?php echo __t('checkout_place_order'); ?></button>
    </form>
</main>
<div id="address-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4"><div class="bg-white rounded-lg p-6 w-full max-w-md"><h2 class="text-xl font-bold mb-4"><?php echo __t('checkout_modal_title'); ?></h2><form id="address-form" class="space-y-4"><input type="hidden" name="action" value="add_address"><div><label class="text-sm"><?php echo __t('checkout_modal_full_name'); ?></label><input type="text" name="full_name" required class="mt-1 w-full p-2 border rounded-md"></div><div><label class="text-sm"><?php echo __t('checkout_modal_phone'); ?></label><input type="tel" name="phone_number" required class="mt-1 w-full p-2 border rounded-md"></div><div><label class="text-sm"><?php echo __t('checkout_modal_address'); ?></label><textarea name="address" rows="2" required class="mt-1 w-full p-2 border rounded-md"></textarea></div><div class="grid grid-cols-2 gap-4"><div><label class="text-sm"><?php echo __t('checkout_modal_pincode'); ?></label><input type="text" name="pincode" required class="mt-1 w-full p-2 border rounded-md"></div><div><label class="text-sm"><?php echo __t('checkout_modal_district'); ?></label><input type="text" name="district" required class="mt-1 w-full p-2 border rounded-md"></div></div><div class="grid grid-cols-2 gap-4"><div><label class="text-sm"><?php echo __t('checkout_modal_state'); ?></label><input type="text" name="state" required class="mt-1 w-full p-2 border rounded-md"></div><div><label class="text-sm"><?php echo __t('checkout_modal_landmark'); ?></label><input type="text" name="landmark" class="mt-1 w-full p-2 border rounded-md"></div></div><div id="address-form-msg" class="text-sm"></div><div class="flex justify-end space-x-4 mt-4"><button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md"><?php echo __t('checkout_modal_cancel'); ?></button><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md"><?php echo __t('checkout_modal_save'); ?></button></div></form></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addressModal = document.getElementById('address-modal');
    const addressForm = document.getElementById('address-form');

    const openAddressModal = () => {
        addressForm.reset();
        document.getElementById('modal-close-btn').style.display = 'inline-flex';
        addressModal.classList.remove('hidden');
    };

    const closeAddressModal = () => {
        addressModal.classList.add('hidden');
    };

    document.body.addEventListener('click', (e) => {
        if (e.target.id === 'add-address-btn') {
            openAddressModal();
        }
        if (e.target.id === 'modal-close-btn') {
            closeAddressModal();
        }
        if (e.target.id === 'address-modal') {
            if (document.getElementById('modal-close-btn').style.display !== 'none') {
                closeAddressModal();
            }
        }
    });

    if (addressForm) {
        addressForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('address-form-msg');
            msgEl.textContent = 'Saving...';
            msgEl.style.color = 'black';

            try {
                const response = await fetch('checkout.php', { method: 'POST', body: new FormData(addressForm) });
                const result = await response.json();
                if (result.status === 'success') {
                    msgEl.style.color = 'green';
                    msgEl.textContent = result.message;
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    msgEl.style.color = 'red';
                    msgEl.textContent = 'Error: ' + result.message;
                }
            } catch (error) {
                msgEl.style.color = 'red';
                msgEl.textContent = 'An unexpected error occurred.';
            }
        });
    }
});
</script>

<?php
include_once 'common/bottom.php';
?>