<?php
// File: order.php
// Location: /
// REVISED: Replaced image animation with a professional, pure CSS animation and improved font.
include_once 'common/config.php';
check_login();

$user_id = get_user_id();

// Fetch all orders for the user
$orders = [];
$stmt = $conn->prepare("
    SELECT 
        o.id, o.total_amount, o.status, o.created_at, o.delivered_at,
        oi.product_id, oi.size, 
        p.name as product_name, p.image as product_image,
        r.status as return_status
    FROM orders o
    JOIN (
        SELECT order_id, product_id, size, ROW_NUMBER() OVER(PARTITION BY order_id ORDER BY id) as rn
        FROM order_items
    ) oi ON o.id = oi.order_id AND oi.rn = 1
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN returns r ON o.id = r.order_id AND r.status NOT IN ('Completed', 'Rejected')
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
if ($stmt === false) {
    die("Error preparing database query. Please ensure the installation script has run successfully. Error: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

$active_orders = array_filter($orders, function($o) {
    return !in_array($o['status'], ['Delivered', 'Cancelled', 'Pending Payment']) || !empty($o['return_status']);
});

$past_orders = array_filter($orders, function($o) {
    return in_array($o['status'], ['Delivered', 'Cancelled', 'Pending Payment']) && empty($o['return_status']);
});

include_once 'common/header.php';
?>
<style>
    .star-rating input { display: none; }
    .star-rating label { font-size: 2rem; color: #d1d5db; cursor: pointer; transition: color 0.2s; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color: #f59e0b; }
    .tab-btn.tab-active { border-bottom-width: 3px; border-color: #3B82F6; color: #3B82F6; }

    /* --- NEW: Professional CSS-only Animation Styles --- */
    #thank-you-animation {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); /* Professional purple-blue gradient */
        z-index: 100;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease-in-out;
    }
    #thank-you-animation.visible {
        opacity: 1;
        pointer-events: auto;
    }
    .envelope {
        position: relative;
        width: 280px;
        height: 180px;
        transform: scale(0.5);
        opacity: 0;
        transition: transform 0.6s 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s 0.2s ease;
    }
    .envelope-back {
        position: absolute;
        width: 100%;
        height: 100%;
        background-color: #f1c40f; /* Main yellow */
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    }
    .card {
        position: absolute;
        width: 90%;
        height: 90%;
        background-color: #ecf0f1; /* Off-white card */
        top: 5%;
        left: 5%;
        border-radius: 6px;
        display: flex;
        justify-content: center;
        align-items: center;
        transform: translateY(100%);
        transition: transform 0.6s 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        z-index: 5;
    }
    .envelope-flap-top {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100px;
        background-color: #f39c12; /* Darker orange-yellow flap */
        transform-origin: top center;
        border-radius: 10px 10px 0 0;
        clip-path: polygon(0% 0%, 100% 0%, 100% 55%, 50% 100%, 0% 55%);
        transition: transform 0.5s 0.3s ease-in-out;
        transform: rotateX(0deg);
        z-index: 10;
    }
    .envelope-flap-front {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #f1c40f;
        clip-path: polygon(0 100%, 50% 45%, 100% 100%);
        z-index: 3;
    }
    .checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #2ecc71; /* Green */
        display: flex;
        justify-content: center;
        align-items: center;
        transform: scale(0);
        transition: transform 0.4s 1.2s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    }
    .checkmark i {
        color: white;
        font-size: 40px;
        font-weight: bold;
    }
    .thank-you-text {
        margin-top: 25px;
        font-family: 'SchoolCamping', 'Poppins', sans-serif; /* Using your custom font */
        font-size: 2.8rem;
        font-weight: bold;
        color: #f1c40f; /* Yellow text */
        text-shadow: 2px 2px 0 #2c3e50, 4px 4px 8px rgba(0,0,0,0.3); /* Bold effect with shadow */
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.6s 1.6s ease, transform 0.6s 1.6s ease;
        text-align: center;
        line-height: 1.1;
    }
    /* Animation Trigger Class */
    #thank-you-animation.start-animation .envelope {
        transform: scale(1);
        opacity: 1;
    }
    #thank-you-animation.start-animation .envelope-flap-top {
        transform: rotateX(180deg);
    }
    #thank-you-animation.start-animation .card {
        transform: translateY(5%);
    }
    #thank-you-animation.start-animation .checkmark {
        transform: scale(1);
    }
    #thank-you-animation.start-animation .thank-you-text {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<main class="p-4">
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('order_my_orders'); ?></h1>
    <div id="success-message-container" class="<?php echo isset($_GET['success']) ? '' : 'hidden'; ?>">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p class="font-bold"><?php echo __t('order_success_message'); ?></p>
            <p><?php echo sprintf(__t('order_success_details'), htmlspecialchars($_GET['order_id'] ?? '')); ?></p>
        </div>
    </div>

    <!-- TABS AND ORDER LISTS (Same as before) -->
    <div class="flex border-b mb-4">
        <button class="flex-1 py-3 text-center font-semibold tab-btn" onclick="showOrderTab('active', this)"><?php echo __t('order_active_orders'); ?></button>
        <button class="flex-1 py-3 text-center font-semibold tab-btn text-gray-500" onclick="showOrderTab('history', this)"><?php echo __t('order_history'); ?></button>
    </div>

    <div id="active-orders" class="order-tab-content space-y-4">
        <?php if (empty($active_orders)): ?>
            <p class="text-center text-gray-500 mt-8"><?php echo __t('order_no_active_orders'); ?></p>
        <?php else: ?>
            <?php foreach($active_orders as $order): ?>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="block">
                        <div class="flex items-start space-x-4">
                            <img src="<?php echo get_image_url($order['product_image']); ?>" class="w-16 h-16 rounded-md object-cover">
                            <div class="flex-1">
                                 <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['product_name']); ?> <?php echo __t('order_and_more'); ?></p>
                                 <?php if (!empty($order['size'])): ?>
                                    <p class="text-xs text-gray-600 font-medium"><?php echo __t('cart_size'); ?> <?php echo htmlspecialchars($order['size']); ?></p>
                                 <?php endif; ?>
                                 <p class="text-sm text-gray-500"><?php echo __t('order_id'); ?> #<?php echo $order['id']; ?></p>
                                 <p class="font-bold text-lg mt-1">₹<?php echo number_format($order['total_amount']); ?></p>
                            </div>
                        </div>
                    </a>
                    
                    <div class="mt-4">
                        <?php if (!empty($order['return_status'])): ?>
                            <div class="text-center">
                                <p class="font-semibold text-orange-600"><?php echo sprintf(__t('order_return_status'), htmlspecialchars($order['return_status'])); ?></p>
                                <p class="text-xs text-gray-500"><?php echo __t('order_return_reviewing'); ?></p>
                            </div>
                        <?php else: ?>
                            <?php 
                                $placed_class = 'text-blue-600';
                                $shipped_class = in_array($order['status'], ['Shipped', 'Out for Delivery', 'Delivered']) ? 'text-blue-600' : 'text-gray-400';
                                $delivered_class = ($order['status'] == 'Delivered') ? 'text-blue-600' : 'text-gray-400';
                                $progress_width = '0%';
                                if (in_array($order['status'], ['Shipped', 'Out for Delivery'])) { $progress_width = '50%'; } 
                                elseif ($order['status'] == 'Delivered') { $progress_width = '100%'; }
                                
                                $placedDate = new DateTime($order['created_at']);
                                $estimatedDeliveryDate = (clone $placedDate)->modify('+7 days');
                            ?>
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="<?php echo $placed_class; ?>"><?php echo __t('order_status_placed'); ?></span>
                                <span class="<?php echo $shipped_class; ?>"><?php echo __t('order_status_shipped'); ?></span>
                                <span class="<?php echo $delivered_class; ?>"><?php echo __t('order_status_delivered'); ?></span>
                            </div>
                            <div class="relative mt-2">
                                <div class="absolute w-full h-1 bg-gray-200 rounded-full top-1/2 -translate-y-1/2"></div>
                                <div class="absolute h-1 bg-blue-600 rounded-full top-1/2 -translate-y-1/2 transition-all duration-500" style="width: <?php echo $progress_width; ?>;"></div>
                                <div class="flex justify-between items-center relative">
                                    <div class="w-3 h-3 rounded-full <?php echo $placed_class == 'text-blue-600' ? 'bg-blue-600' : 'bg-gray-400'; ?>"></div>
                                    <div class="w-3 h-3 rounded-full <?php echo $shipped_class == 'text-blue-600' ? 'bg-blue-600' : 'bg-gray-400'; ?>"></div>
                                    <div class="w-3 h-3 rounded-full <?php echo $delivered_class == 'text-blue-600' ? 'bg-blue-600' : 'bg-gray-400'; ?>"></div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 mt-1">
                                <span class="font-semibold"><?php echo $placedDate->format('d M'); ?></span>
                                <span></span>
                                <span class="font-semibold">By <?php echo $estimatedDeliveryDate->format('d M'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-t mt-4 pt-3 text-center">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-sm font-semibold text-blue-600 hover:underline">
                            <?php echo __t('order_see_all_updates'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="history-orders" class="order-tab-content space-y-4 hidden">
        <?php if (empty($past_orders)): ?>
            <p class="text-center text-gray-500 mt-8"><?php echo __t('order_no_past_orders'); ?></p>
        <?php else: ?>
             <?php foreach($past_orders as $order): ?>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="block">
                        <div class="flex items-start space-x-4">
                            <img src="<?php echo get_image_url($order['product_image']); ?>" class="w-16 h-16 rounded-md object-cover">
                            <div class="flex-1">
                                 <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['product_name']); ?> <?php echo __t('order_and_more'); ?></p>
                                 <?php if (!empty($order['size'])): ?>
                                    <p class="text-xs text-gray-600 font-medium"><?php echo __t('cart_size'); ?> <?php echo htmlspecialchars($order['size']); ?></p>
                                 <?php endif; ?>
                                 <p class="text-sm text-gray-500"><?php echo __t('order_id'); ?> #<?php echo $order['id']; ?></p>
                                 <div class="flex justify-between items-center mt-2">
                                    <p class="font-bold text-lg">₹<?php echo number_format($order['total_amount']); ?></p>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $order['status'] == 'Delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo sprintf(__t('order_status_on_date'), __t('order_status_'.strtolower(str_replace(' ','_', $order['status']))), date('d M Y', strtotime($order['created_at']))); ?>
                                    </span>
                                 </div>
                            </div>
                        </div>
                    </a>
                    <div class="border-t mt-3 pt-3 flex justify-between items-center">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-sm font-semibold text-blue-600 hover:underline"><?php echo __t('order_track_order'); ?></a>
                        <?php if ($order['status'] === 'Delivered'): ?>
                            <button onclick="openReviewModal(<?php echo $order['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($order['product_name'])); ?>', '<?php echo get_image_url($order['product_image']); ?>')" class="text-sm font-semibold bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                <?php echo __t('order_write_review'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Review Modal (Same as before) -->
<div id="review-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm">
        <h2 class="text-xl font-bold mb-2"><?php echo __t('review_modal_title'); ?></h2>
        <div id="review-product" class="flex items-center border-b pb-4 mb-4">
            <img id="review-product-img" src="" class="w-12 h-12 object-cover rounded-md">
            <p id="review-product-name" class="ml-3 font-semibold text-gray-700"></p>
        </div>
        <form id="review-form">
            <input type="hidden" name="action" value="submit_review">
            <input type="hidden" name="product_id" id="review-product-id">
            <div class="mb-4">
                <label class="block font-semibold mb-2"><?php echo __t('review_modal_rating'); ?></label>
                <div class="star-rating flex flex-row-reverse justify-end">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars">&#9733;</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">&#9733;</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">&#9733;</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">&#9733;</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 stars">&#9733;</label>
                </div>
            </div>
            <div class="mb-4">
                <label for="review_text" class="block font-semibold mb-2"><?php echo __t('review_modal_review_optional'); ?></label>
                <textarea name="review_text" id="review_text" rows="4" class="w-full p-2 border rounded-md" placeholder="<?php echo __t('review_modal_placeholder'); ?>"></textarea>
            </div>
            <div id="review-form-msg" class="text-sm mb-4"></div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeReviewModal()" class="bg-gray-300 px-4 py-2 rounded-md"><?php echo __t('review_modal_cancel'); ?></button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md"><?php echo __t('review_modal_submit'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- NEW: Enhanced CSS-Only Thank You Animation HTML with Audio -->
<div id="thank-you-animation">
    <audio id="success-sound" src="<?php echo BASE_URL; ?>/assets/sounds/success-chime.mp3" preload="auto"></audio>
    <div class="envelope">
        <div class="envelope-back"></div>
        <div class="card">
            <div class="checkmark">
                <i class="fas fa-check"></i>
            </div>
        </div>
        <div class="envelope-flap-front"></div>
        <div class="envelope-flap-top"></div>
    </div>
    <div class="thank-you-text">THANK YOU FOR<br>YOUR SHOPPING</div>
</div>

<script>
    function showOrderTab(tabName, btn) {
        document.querySelectorAll('.order-tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(tabName + '-orders').classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('tab-active', 'text-blue-600'); b.classList.add('text-gray-500');
        });
        btn.classList.add('tab-active', 'text-blue-600'); btn.classList.remove('text-gray-500');
    }
    document.addEventListener('DOMContentLoaded', () => { 
        document.querySelector('.tab-btn').click(); 

        const urlParams = new URLSearchParams(window.location.search);
        const successMessageContainer = document.getElementById('success-message-container');
        const animationContainer = document.getElementById('thank-you-animation');
        const successSound = document.getElementById('success-sound');

        if (urlParams.has('success') && urlParams.get('success') === 'true') {
            successMessageContainer.style.display = 'none';
            animationContainer.classList.add('visible');
            
            setTimeout(() => {
                animationContainer.classList.add('start-animation');
                if (successSound) {
                    successSound.play().catch(e => console.error("Audio play failed:", e));
                }
            }, 100);

            setTimeout(() => {
                animationContainer.classList.remove('visible');
                successMessageContainer.style.display = 'block';
            }, 4000);
        } else {
             successMessageContainer.style.display = 'none';
             animationContainer.style.display = 'none';
        }
    });

    const reviewModal = document.getElementById('review-modal'); const reviewForm = document.getElementById('review-form');
    function openReviewModal(productId, productName, productImg) {
        reviewForm.reset(); document.getElementById('review-product-id').value = productId; document.getElementById('review-product-name').textContent = productName;
        document.getElementById('review-product-img').src = productImg; document.getElementById('review-form-msg').textContent = ''; reviewModal.classList.remove('hidden');
    }
    function closeReviewModal() { reviewModal.classList.add('hidden'); }
    reviewForm.addEventListener('submit', async (e) => {
        e.preventDefault(); const msgEl = document.getElementById('review-form-msg'); msgEl.textContent = "<?php echo __t('review_submitting'); ?>";
        try {
            const response = await fetch('order_ajax.php', { method: 'POST', body: new FormData(reviewForm) }); const result = await response.json();
            if (result.status === 'success') { msgEl.style.color = 'green'; msgEl.textContent = result.message; setTimeout(() => closeReviewModal(), 2000);
            } else { msgEl.style.color = 'red'; msgEl.textContent = "<?php echo __t('review_error'); ?>".replace('%s', result.message); }
        } catch (error) { msgEl.style.color = 'red'; msgEl.textContent = "<?php echo __t('review_error_unexpected'); ?>"; }
    });
</script>

<?php 
include_once 'common/bottom.php'; 
?>