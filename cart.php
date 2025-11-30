<?php
// File: cart.php
// Location: /
// REVISED: Handles the "Buy Now" flow by displaying only the selected item.
include_once 'common/config.php';
check_login();
$user_id = get_user_id();

// --- FIX: Check for the "Buy Now" flow ---
$is_buy_now = isset($_GET['buy_now_cart_id']);
$buy_now_cart_id = $is_buy_now ? (int)$_GET['buy_now_cart_id'] : 0;
$checkout_url = 'checkout.php';

// Handle quantity update or item removal (remains the same)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    if (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $cart_check_stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? LIMIT 1");
        if ($cart_check_stmt) {
            $cart_check_stmt->bind_param("i", $user_id);
            $cart_check_stmt->execute();
            if ($cart_check_stmt->get_result()->num_rows === 0) {
                unset($_SESSION['applied_coupon']);
            }
            $cart_check_stmt->close();
        }
    }
    if (!isset($_POST['action'])) {
        $redirect_url = $is_buy_now ? "cart.php?buy_now_cart_id=$buy_now_cart_id" : "cart.php";
        header("Location: $redirect_url");
        exit();
    }
}

// Fetch cart items and calculate subtotal
$cart_items = [];
$subtotal = 0;

if ($is_buy_now) {
    // --- FIX: Fetch ONLY the "Buy Now" item ---
    $sql = "SELECT c.id as cart_id, c.quantity, c.size, p.id as product_id, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $buy_now_cart_id, $user_id);
    $checkout_url .= '?buy_now_cart_id=' . $buy_now_cart_id;
} else {
    // --- Original logic: Fetch all items ---
    $sql = "SELECT c.id as cart_id, c.quantity, c.size, p.id as product_id, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

if ($stmt === false) {
    die("Error preparing database query. Please ensure the installation script has run successfully. Error: " . htmlspecialchars($conn->error));
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
$stmt->close();

// Check for applied coupon
$discount_amount = 0;
$total_amount = $subtotal;
if (isset($_SESSION['applied_coupon'])) {
    $discount_amount = $_SESSION['applied_coupon']['discount_amount'];
    $total_amount -= $discount_amount;
}

include_once 'common/header.php';
?>

<main class="p-4 bg-gray-50 min-h-screen">
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('cart_my_cart'); ?></h1>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-16">
            <i class="fas fa-shopping-cart text-5xl text-gray-300"></i>
            <p class="text-center text-gray-500 mt-4"><?php echo __t('cart_is_empty'); ?></p>
            <a href="index.php" class="mt-6 inline-block bg-blue-600 text-white font-semibold px-6 py-2 rounded-lg"><?php echo __t('cart_shop_now'); ?></a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <!-- Cart items loop -->
            <?php foreach($cart_items as $item): ?>
            <div class="bg-white p-3 rounded-lg shadow-sm flex items-start space-x-4">
                <!-- FIX: Use get_image_url() helper function -->
                <img src="<?php echo get_image_url($item['image']); ?>" class="w-20 h-20 rounded-md object-cover">
                <div class="flex-1">
                    <p class="font-semibold text-gray-800 leading-tight"><?php echo htmlspecialchars($item['name']); ?></p>
                    <?php if (!empty($item['size'])): ?>
                    <p class="text-sm text-gray-500 mt-1"><?php echo __t('cart_size'); ?> <span class="font-medium"><?php echo htmlspecialchars($item['size']); ?></span></p>
                    <?php endif; ?>
                    <p class="font-bold text-lg mt-1">₹<?php echo number_format($item['price']); ?></p>
                    <div class="flex items-center justify-between mt-2">
                        <form method="POST" class="flex items-center">
                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 text-center border rounded-md py-1">
                            <button type="submit" name="update_quantity" class="ml-2 text-xs text-blue-500 font-semibold"><?php echo __t('cart_update'); ?></button>
                        </form>
                        <form method="POST">
                             <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                             <button type="submit" name="remove_item" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Coupon Section -->
        <div class="bg-white p-4 rounded-lg shadow-sm mt-6">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-bold"><?php echo __t('cart_apply_coupon'); ?></h2>
                <button id="view-offers-btn" class="text-sm font-semibold text-blue-600"><?php echo __t('cart_view_offers'); ?></button>
            </div>
            <form id="coupon-form" class="flex space-x-2">
                <input type="text" id="coupon-code-input" name="coupon_code" placeholder="<?php echo __t('cart_enter_coupon_code'); ?>" class="w-full p-2 border rounded-md" value="<?php echo $_SESSION['applied_coupon']['code'] ?? ''; ?>">
                <button type="submit" class="bg-blue-600 text-white font-semibold px-4 rounded-md"><?php echo __t('cart_apply'); ?></button>
            </form>
            <div id="coupon-message" class="text-sm mt-2"></div>
        </div>

        <!-- Order Summary Section -->
        <div class="bg-white p-4 rounded-lg shadow-sm mt-6">
            <h2 class="text-lg font-bold border-b pb-3 mb-4"><?php echo __t('cart_order_summary'); ?></h2>
            <div class="space-y-2">
                <div class="flex justify-between text-gray-600"><p><?php echo __t('cart_subtotal'); ?></p><p>₹<?php echo number_format($subtotal, 2); ?></p></div>
                <div class="flex justify-between text-gray-600"><p><?php echo __t('cart_delivery_charges'); ?></p><p class="text-green-600 font-semibold"><?php echo __t('cart_free'); ?></p></div>
                <div id="discount-row" class="flex justify-between text-green-600 <?php echo $discount_amount > 0 ? '' : 'hidden'; ?>">
                    <p><?php echo __t('cart_discount'); ?></p>
                    <p>- ₹<span id="discount-amount"><?php echo number_format($discount_amount, 2); ?></span></p>
                </div>
                <div class="border-t mt-2 pt-2 flex justify-between font-bold text-lg">
                    <p><?php echo __t('cart_total_amount'); ?></p>
                    <p>₹<span id="total-amount"><?php echo number_format($total_amount, 2); ?></span></p>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <!-- FIX: Use the dynamic checkout URL -->
            <a href="<?php echo $checkout_url; ?>" class="block w-full text-center bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700">
                <?php echo __t('cart_proceed_to_checkout'); ?>
            </a>
        </div>
    <?php endif; ?>
</main>

<!-- Available Coupons Modal -->
<div id="coupons-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4" 
    data-loading-text="<?php echo __t('cart_loading_offers'); ?>"
    data-no-coupons-text="<?php echo __t('cart_no_coupons_available'); ?>"
    data-load-error-text="<?php echo __t('cart_could_not_load_offers'); ?>">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h2 class="text-xl font-bold"><?php echo __t('cart_available_coupons'); ?></h2>
            <button id="modal-close-btn" class="text-gray-500 text-2xl">&times;</button>
        </div>
        <div id="coupon-list" class="space-y-3 max-h-80 overflow-y-auto">
            <!-- Coupons will be loaded here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const couponForm = document.getElementById('coupon-form');
    const couponMsg = document.getElementById('coupon-message');
    const couponInput = document.getElementById('coupon-code-input');
    const viewOffersBtn = document.getElementById('view-offers-btn');
    const couponsModal = document.getElementById('coupons-modal');
    const closeModalBtn = document.getElementById('modal-close-btn');
    const couponListDiv = document.getElementById('coupon-list');

    const applyCoupon = async (couponCode) => {
        couponMsg.textContent = '';
        const formData = new FormData();
        formData.append('action', 'apply_coupon');
        formData.append('coupon_code', couponCode);

        try {
            const response = await fetch('cart_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.status === 'success') {
                couponMsg.textContent = result.message;
                couponMsg.className = 'text-sm mt-2 text-green-600';
                document.getElementById('discount-row').classList.remove('hidden');
                document.getElementById('discount-amount').textContent = result.discount_amount;
                document.getElementById('total-amount').textContent = result.new_total;
            } else {
                couponMsg.textContent = result.message;
                couponMsg.className = 'text-sm mt-2 text-red-600';
                setTimeout(() => window.location.reload(), 2000);
            }
        } catch (error) {
            couponMsg.textContent = "<?php echo __t('coupon_error_generic'); ?>";
            couponMsg.className = 'text-sm mt-2 text-red-600';
        }
    };

    couponForm.addEventListener('submit', (e) => {
        e.preventDefault();
        applyCoupon(couponInput.value);
    });

    const openModal = async () => {
        couponListDiv.innerHTML = `<p class="text-center">${couponsModal.dataset.loadingText}</p>`;
        couponsModal.classList.remove('hidden');

        const formData = new FormData();
        formData.append('action', 'fetch_available_coupons');
        try {
            const response = await fetch('cart_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            couponListDiv.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(coupon => {
                    let discountText = coupon.discount_type === 'percentage'
                        ? `${coupon.discount_value}` + "<?php echo __t('cart_percent_off'); ?>"
                        : "<?php echo sprintf(__t('cart_flat_off'), ''); ?>" + coupon.discount_value;
                    
                    couponListDiv.innerHTML += `
                        <div class="border rounded-lg p-3 flex justify-between items-center">
                            <div>
                                <p class="font-bold text-lg text-green-600">${discountText}</p>
                                <p class="text-sm font-mono text-gray-700">${coupon.coupon_code}</p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo __t('cart_min_purchase'); ?>`.replace('%s', coupon.min_purchase) + `</p>
                            </div>
                            <button class="apply-from-modal-btn text-blue-600 font-semibold text-sm" data-code="${coupon.coupon_code}"><?php echo __t('cart_apply_from_modal'); ?></button>
                        </div>
                    `;
                });
            } else {
                couponListDiv.innerHTML = `<p class="text-center text-gray-500">${couponsModal.dataset.noCouponsText}</p>`;
            }
        } catch(e) {
            couponListDiv.innerHTML = `<p class="text-center text-red-500">${couponsModal.dataset.loadErrorText}</p>`;
        }
    };
    
    const closeModal = () => couponsModal.classList.add('hidden');

    viewOffersBtn.addEventListener('click', openModal);
    closeModalBtn.addEventListener('click', closeModal);
    couponsModal.addEventListener('click', (e) => { if (e.target === couponsModal) closeModal(); });

    couponListDiv.addEventListener('click', (e) => {
        if (e.target.classList.contains('apply-from-modal-btn')) {
            const code = e.target.dataset.code;
            couponInput.value = code;
            closeModal();
            applyCoupon(code);
        }
    });

});
</script>

<?php include_once 'common/bottom.php'; ?>