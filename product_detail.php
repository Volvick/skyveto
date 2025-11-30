<?php
// File: product_detail.php
// Location: /
// FINAL REVISION: Added Wishlist/Share buttons and fixed sticky footer logic permanently.

include_once 'common/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) { redirect('index.php'); }

// --- Handle Add to Cart / Buy Now Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    check_login(); 
    $user_id = get_user_id(); 
    $p_id = (int)$_POST['product_id']; 
    $quantity = (int)($_POST['quantity'] ?? 1);
    $size = !empty($_POST['size']) ? trim($_POST['size']) : '';

    $p_stmt = $conn->prepare("SELECT sizes, stock FROM products WHERE id = ?");
    $p_stmt->bind_param("i", $p_id); $p_stmt->execute();
    $p_res = $p_stmt->get_result()->fetch_assoc();
    $p_stmt->close();
    
    if (($p_res && !empty(trim($p_res['sizes'])) && empty($size)) || ($p_res && $quantity > $p_res['stock'])) {
        // Validation failed, do nothing as frontend should handle it.
    } else {
        $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $params = [$user_id, $p_id]; $types = "ii";
        if (!empty($size)) { $check_sql .= " AND size = ?"; $params[] = $size; $types .= "s"; } 
        else { $check_sql .= " AND (size IS NULL OR size = '')"; }
        
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param($types, ...$params); 
        $stmt_check->execute(); 
        $result_check = $stmt_check->get_result();
        
        $cart_id = 0;
        if ($result_check->num_rows > 0) {
            $cart_item = $result_check->fetch_assoc(); 
            $cart_id = $cart_item['id'];
            $new_quantity = $cart_item['quantity'] + $quantity;
            $stmt_update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $new_quantity, $cart_item['id']); 
            $stmt_update->execute();
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("iiis", $user_id, $p_id, $quantity, $size); 
            $stmt_insert->execute();
            $cart_id = $conn->insert_id;
        }

        if (isset($_POST['buy_now'])) {
            redirect('cart.php?buy_now_cart_id=' . $cart_id);
        } else {
            header('Location: ' . $_SERVER['REQUEST_URI'] . '&added=1');
            exit();
        }
    }
}

// --- Data Fetching Logic ---
$product = null;
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.cat_id = c.id WHERE p.id = ?");
if($stmt) { $stmt->bind_param("i", $product_id); $stmt->execute(); $product = $stmt->get_result()->fetch_assoc(); $stmt->close(); }
if (!$product) { include_once 'common/header.php'; echo '<main class="p-4 text-center"><h1>404</h1><p>Product not found.</p></main>'; include_once 'common/bottom.php'; exit; }

$product_images = []; if ($product['image']) { $product_images[] = $product['image']; }
$img_stmt = $conn->prepare("SELECT image_name FROM product_images WHERE product_id = ?");
if($img_stmt) { $img_stmt->bind_param("i", $product_id); $img_stmt->execute(); $img_result = $img_stmt->get_result(); while ($row = $img_result->fetch_assoc()) { $product_images[] = $row['image_name']; } $img_stmt->close(); }

$video_id = '';
if (!empty($product['video_url'])) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if (preg_match($pattern, $product['video_url'], $match)) { $video_id = $match[1]; }
}
$gallery_items = [];
foreach ($product_images as $image) { $gallery_items[] = ['type' => 'image', 'src' => $image]; }
if ($video_id) { $gallery_items[] = ['type' => 'video', 'id' => $video_id]; }

$similar_products = [];
$main_product_id = $product['variant_of'] ? $product['variant_of'] : $product['id'];
$stmt_similar = $conn->prepare("(SELECT id, name, image, price, mrp FROM products WHERE id = ?) UNION (SELECT id, name, image, price, mrp FROM products WHERE variant_of = ?) ORDER BY id");
if ($stmt_similar) { $stmt_similar->bind_param("ii", $main_product_id, $main_product_id); $stmt_similar->execute(); $result_similar = $stmt_similar->get_result(); while ($row = $result_similar->fetch_assoc()) { $similar_products[] = $row; } $stmt_similar->close(); }

$rating_summary = ['avg' => 0, 'count' => 0];
$rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE product_id = ?");
if ($rating_stmt) { $rating_stmt->bind_param("i", $product_id); $rating_stmt->execute(); $rating_result = $rating_stmt->get_result()->fetch_assoc(); if ($rating_result && $rating_result['review_count'] > 0) { $rating_summary['avg'] = round($rating_result['avg_rating'], 1); $rating_summary['count'] = $rating_result['review_count']; } $rating_stmt->close(); }

$reviews = [];
$review_stmt = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
if ($review_stmt) { $review_stmt->bind_param("i", $product_id); $review_stmt->execute(); $review_result = $review_stmt->get_result(); while ($row = $review_result->fetch_assoc()) { $reviews[] = $row; } $review_stmt->close(); }

$sizes = [];
if (!empty($product['sizes'])) { $sizes = array_map('trim', explode(',', $product['sizes'])); }

$related_products = [];
if ($product['cat_id']) {
    $stmt_related = $conn->prepare("SELECT id, name, price, mrp, image FROM products WHERE cat_id = ? AND id != ? AND variant_of IS NULL ORDER BY RAND() LIMIT 4");
    if ($stmt_related) {
        $stmt_related->bind_param("ii", $product['cat_id'], $product_id);
        $stmt_related->execute();
        $result_related = $stmt_related->get_result();
        while($row = $result_related->fetch_assoc()) { $related_products[] = $row; }
        $stmt_related->close();
    }
}

include_once 'common/header.php';
?>

<style>
    html.no-scroll, body.no-scroll { overflow: hidden; height: 100%; }
    .gallery-container { position: relative; } 
    .main-image-wrapper { overflow: hidden; border-radius: 8px; position: relative; } 
    .main-image-slider { display: flex; transition: transform 0.3s ease-in-out; } 
    .main-image-slide { min-width: 100%; aspect-ratio: 1 / 1.2; position: relative; } 
    .main-image-slide img { width: 100%; height: 100%; object-fit: contain; cursor: zoom-in; } 
    .slider-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); z-index: 10; }
    .slider-dot { display: inline-block; height: 8px; width: 8px; border-radius: 50%; background-color: rgba(0,0,0,0.3); margin: 0 4px; transition: background-color 0.3s ease; border: 1px solid rgba(255,255,255,0.5); } 
    .slider-dot.active { background-color: #3B82F6; }
    .video-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; cursor: pointer; background-color: rgba(0,0,0,0.3); z-index: 5; }
    .size-btn.selected { background-color: #3B82F6; color: white; border-color: #3B82F6; }
    .description-text { overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 5; } 
    .description-text.expanded { -webkit-line-clamp: unset; }
    .lightbox { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; flex-direction: column; justify-content: center; align-items: center; } .lightbox.active { display: flex; } .lightbox-content { position: relative; width: 100%; flex-grow: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; } .lightbox-slider { display: flex; height: 100%; transition: transform 0.3s ease-in-out; touch-action: none; } .lightbox-slide { min-width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden; } .lightbox-slide img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.2s ease; cursor: grab; transform-origin: center center; }
    .lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: white; border: none; font-size: 2rem; cursor: pointer; padding: 10px 15px; border-radius: 50%; z-index: 1001; } .lightbox-prev { left: 10px; } .lightbox-next { right: 10px; }
    .lightbox-close { position: absolute; top: 15px; right: 20px; font-size: 2.5rem; color: white; cursor: pointer; z-index: 1001; }
    .lightbox-thumbnails { flex-shrink: 0; padding: 10px; text-align: center; overflow-x: auto; white-space: nowrap; max-width: 100%; } .lightbox-thumbnail-item { display: inline-block; border: 2px solid #555; border-radius: 6px; cursor: pointer; margin: 0 4px; } .lightbox-thumbnail-item.active { border-color: #fff; } .lightbox-thumbnail-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; display: block; }
    @keyframes slide-up { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
</style>

<main class="pb-40">
    <!-- Image Gallery Section -->
    <div class="p-4">
        <div class="gallery-container">
            <div class="main-image-wrapper border rounded-lg">
                <div class="main-image-slider">
                    <?php foreach ($gallery_items as $index => $item): ?>
                        <div class="main-image-slide">
                            <?php if ($item['type'] === 'image'): ?>
                                <img src="<?php echo get_image_url($item['src']); ?>" class="main-img-item" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php elseif ($item['type'] === 'video'): ?>
                                <div id="ytplayer_<?php echo $index; ?>" class="w-full h-full"></div>
                                <div class="video-overlay" data-player-id="ytplayer_<?php echo $index; ?>">
                                    <i class="fas fa-play text-white text-6xl opacity-80"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($gallery_items) > 1): ?>
                    <div class="slider-dots"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Similar Products (Variants) Section -->
    <?php if (count($similar_products) > 1): ?>
    <section class="p-4 pt-0">
        <h2 class="text-lg font-semibold mb-3"><?php echo __t('prod_detail_similar_products'); ?></h2>
        <div class="flex space-x-3 overflow-x-auto pb-2">
            <?php foreach ($similar_products as $variant): ?>
            <a href="product_detail.php?id=<?php echo $variant['id']; ?>" class="block flex-shrink-0">
                <img src="<?php echo get_image_url($variant['image']); ?>" 
                     alt="<?php echo htmlspecialchars($variant['name']); ?>" 
                     class="w-20 h-20 object-cover rounded-md border-2 <?php echo ($variant['id'] == $product_id) ? 'border-blue-500' : 'border-gray-300'; ?>">
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Product Info, Features, Size/Quantity sections... -->
    <div class="p-4">
        <span class="text-xs text-blue-500 font-semibold"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>

        <!-- --- NEW: Title with Wishlist and Share Buttons --- -->
        <div class="flex justify-between items-start mt-1">
            <h1 class="text-2xl font-bold text-gray-800 pr-4"><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="flex items-center space-x-4 flex-shrink-0">
                <button id="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" class="text-gray-500 text-xl">
                    <i class="far fa-heart"></i>
                </button>
                <button id="share-btn" class="text-gray-500 text-xl">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>
        </div>
        
        <?php if ($rating_summary['count'] > 0): ?><div class="flex items-center mt-2 space-x-2"><div class="flex items-center"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star text-xs <?php echo ($i <= $rating_summary['avg']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i><?php endfor; ?></div><span class="text-sm font-semibold text-gray-700"><?php echo $rating_summary['avg']; ?></span><span class="text-sm text-gray-500">(<?php echo $rating_summary['count']; ?> <?php echo __t('prod_detail_ratings'); ?>)</span></div><?php endif; ?>
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <?php if (isset($product['mrp']) && $product['mrp'] > $product['price']): $discount_percentage = round((($product['mrp'] - $product['price']) / $product['mrp']) * 100);?>
                        <div class="flex items-baseline space-x-2">
                            <span class="text-3xl font-bold text-gray-900">₹<?php echo number_format($product['price']); ?></span>
                            <span class="text-md text-gray-500 line-through">₹<?php echo number_format($product['mrp']); ?></span>
                            <span class="text-md font-bold text-green-600"><?php echo $discount_percentage; ?>% <?php echo __t('prod_detail_off'); ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-3xl font-bold text-gray-900">₹<?php echo number_format($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-sm font-semibold <?php echo $product['stock'] > 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $product['stock'] > 0 ? __t('prod_detail_in_stock') : __t('prod_detail_out_of_stock'); ?></div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
                <?php if ((float)$product['delivery_charge'] > 0): ?>
                    + ₹<?php echo number_format($product['delivery_charge']); ?> <?php echo __t('prod_detail_delivery_charge'); ?>
                <?php else: ?>
                    <span class="font-semibold text-green-600"><?php echo __t('prod_detail_free_delivery'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="p-4"><div class="flex justify-around items-center bg-violet-50 border border-violet-200 rounded-lg p-3 text-center"><div class="text-xs font-medium text-gray-700"><i class="fas fa-undo-alt block text-violet-600 mb-1"></i><?php echo __t('prod_detail_7_day_return'); ?></div><div class="text-xs font-medium text-gray-700"><i class="fas fa-money-bill-wave block text-violet-600 mb-1"></i><?php echo __t('prod_detail_cod_available'); ?></div><div class="text-xs font-medium text-gray-700"><i class="fas fa-tag block text-violet-600 mb-1"></i><?php echo __t('prod_detail_lowest_price'); ?></div></div></div>
    <form id="add-to-cart-form" method="POST" action="product_detail.php?id=<?php echo $product_id; ?>"><input type="hidden" name="product_id" value="<?php echo $product_id; ?>"><input type="hidden" name="size" id="selected-size" value=""><input type="hidden" name="quantity" id="selected-quantity" value="1"></form>
    <div class="p-4">
        <?php if (!empty($sizes)): ?>
        <div class="mb-4"><h3 class="text-md font-semibold mb-2"><?php echo __t('prod_detail_select_size'); ?></h3><div id="main-size-selector" class="flex flex-wrap gap-2"><?php foreach ($sizes as $size): ?><button type="button" class="size-btn border border-gray-300 rounded-md px-4 py-2 text-sm focus:outline-none" data-size="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></button><?php endforeach; ?></div></div>
        <?php endif; ?>
        <div class="flex items-center space-x-4 mb-4"><label for="quantity-input" class="font-semibold"><?php echo __t('prod_detail_quantity'); ?></label><div class="flex items-center border rounded-md"><button type="button" onclick="changeQuantity(-1)" class="px-3 py-1 text-lg font-bold">-</button><input type="number" id="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>" class="w-12 text-center border-l border-r outline-none" readonly><button type="button" onclick="changeQuantity(1)" class="px-3 py-1 text-lg font-bold">+</button></div></div>
    </div>
    <div id="main-action-buttons" class="p-4 pt-0"><div class="flex space-x-2"><button type="button" id="main-add-to-cart-btn" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700"><?php echo __t('prod_detail_add_to_cart'); ?></button><button type="button" id="main-buy-now-btn" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700">Buy Now</button></div></div>
    <div class="p-4"><div class="mt-2"><h2 class="text-lg font-semibold border-b pb-2 mb-2"><?php echo __t('prod_detail_description'); ?></h2><p id="description-text" class="text-gray-600 text-sm description-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p><button id="see-more-desc-btn" class="text-blue-600 font-semibold text-sm mt-2" data-see-more="<?php echo __t('prod_detail_see_more'); ?>" data-see-less="<?php echo __t('prod_detail_see_less'); ?>"><?php echo __t('prod_detail_see_more'); ?></button></div></div>
    
    <!-- Ratings & Reviews Section -->
    <section class="p-4 bg-gray-50 mt-4 border-t"><h2 class="text-xl font-semibold text-gray-800 mb-3"><?php echo __t('prod_detail_ratings_reviews'); ?></h2><?php if (empty($reviews)): ?><p class="text-gray-500 text-center py-4"><?php echo __t('prod_detail_no_reviews'); ?></p><?php else: ?><div class="space-y-4"><?php foreach (array_slice($reviews, 0, 5) as $review): ?><div class="border-b pb-4"><div class="flex items-center mb-1"><p class="font-semibold"><?php echo htmlspecialchars($review['user_name']); ?></p><span class="text-xs text-gray-400 ml-auto"><?php echo date('d M Y', strtotime($review['created_at'])); ?></span></div><div class="flex items-center mb-2"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star text-xs <?php echo ($i <= $review['rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i><?php endfor; ?></div><p class="text-gray-700 text-sm mb-2"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p></div><?php endforeach; ?></div><?php if (count($reviews) > 5): ?><div class="text-center mt-4"><a href="reviews.php?product_id=<?php echo $product_id; ?>" class="text-blue-600 font-semibold border border-blue-600 rounded-full px-6 py-2 text-sm hover:bg-blue-50"><?php echo __t('prod_detail_see_all_reviews'); ?></a></div><?php endif; ?><?php endif; ?></section>

    <!-- Related Products Section -->
    <?php if (!empty($related_products)): ?>
    <section id="related-products-section" class="p-4 mt-4 border-t">
        <h2 class="text-xl font-semibold text-gray-800 mb-3"><?php echo __t('prod_detail_related_products'); ?></h2>
        <div class="grid grid-cols-2 gap-4">
            <?php foreach ($related_products as $related_product): ?>
            <a href="product_detail.php?id=<?php echo $related_product['id']; ?>" class="bg-white rounded-lg shadow-md overflow-hidden block">
                <div class="aspect-square w-full overflow-hidden">
                    <img src="<?php echo get_image_url($related_product['image']); ?>" alt="<?php echo htmlspecialchars($related_product['name']); ?>" class="w-full h-full object-cover">
                </div>
                <div class="p-3">
                    <h3 class="text-sm font-semibold text-gray-700 truncate"><?php echo htmlspecialchars($related_product['name']); ?></h3>
                    <div class="mt-2 flex items-baseline flex-wrap">
                        <span class="text-lg font-bold text-gray-900 mr-2">₹<?php echo number_format($related_product['price']); ?></span>
                        <?php if (isset($related_product['mrp']) && $related_product['mrp'] > $related_product['price']): ?>
                            <span class="text-xs text-gray-500 line-through">₹<?php echo number_format($related_product['mrp']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span id="lightbox-close" class="lightbox-close">&times;</span>
    <button id="lightbox-prev" class="lightbox-nav lightbox-prev">&#10094;</button>
    <div class="lightbox-content">
        <div id="lightbox-slider" class="lightbox-slider">
            <?php foreach ($gallery_items as $item): ?>
                <div class="lightbox-slide">
                    <?php if ($item['type'] === 'image'): ?>
                        <img src="<?php echo get_image_url($item['src']); ?>">
                    <?php elseif ($item['type'] === 'video'): ?>
                        <div class="w-full max-w-3xl aspect-video bg-black">
                            <iframe class="w-full h-full" src="https://www.youtube.com/embed/<?php echo htmlspecialchars($item['id']); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <button id="lightbox-next" class="lightbox-nav lightbox-next">&#10095;</button>
    <div id="lightbox-thumbnails" class="lightbox-thumbnails"></div>
</div>

<!-- Size Selection Modal and Sticky Footer -->
<div id="size-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-end justify-center hidden z-50"><div class="bg-white rounded-t-2xl p-4 w-full max-w-lg mx-auto animate-slide-up"><div class="flex items-start space-x-4 border-b pb-4 mb-4"><img id="modal-product-img" src="<?php echo get_image_url($product['image']); ?>" class="w-16 h-20 rounded-md object-cover"><div><p id="modal-product-name" class="font-semibold text-gray-800 leading-tight"><?php echo htmlspecialchars($product['name']); ?></p><p id="modal-product-price" class="font-bold text-lg mt-1">₹<?php echo number_format($product['price']); ?></p></div><button id="size-modal-close-btn" class="text-gray-500 text-2xl ml-auto">&times;</button></div><h3 class="text-md font-semibold mb-2"><?php echo __t('prod_detail_select_size'); ?></h3><div id="modal-size-selector" class="flex flex-wrap gap-3 mb-4"><?php foreach ($sizes as $size): ?><button class="size-btn border border-gray-300 rounded-md px-4 py-2 text-sm focus:outline-none" data-size="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></button><?php endforeach; ?></div><p id="size-error-msg" class="text-red-500 text-sm hidden mb-4">Please select a size.</p><button id="modal-confirm-action-btn" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700"></button></div></div>
<div id="sticky-action-footer" class="fixed bottom-[65px] left-0 right-0 bg-white border-t p-2 flex space-x-2 z-20 hidden"><button type="button" id="sticky-add-to-cart-btn" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700"><?php echo __t('prod_detail_add_to_cart'); ?></button><button type="button" id="sticky-buy-now-btn" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-purple-700">Buy Now</button></div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const getImageUrlJs = (src) => {
        if (!src) return `${BASE_URL}/uploads/placeholder.png`;
        if (src.startsWith('http://') || src.startsWith('https://')) {
            return src;
        }
        return `${BASE_URL}/uploads/${src}`;
    };

    function changeQuantity(amount) { const input = document.getElementById('quantity-input'); let currentVal = parseInt(input.value); const maxStock = parseInt(input.max); let newVal = currentVal + amount; if (newVal < 1) newVal = 1; if (maxStock > 0 && newVal > maxStock) newVal = maxStock; if (maxStock <= 0) newVal = 1; input.value = newVal; }
    let players = {}; const galleryItems = <?php echo json_encode($gallery_items); ?>;
    function onYouTubeIframeAPIReady() { galleryItems.forEach((item, index) => { if (item.type === 'video') { players[index] = new YT.Player('ytplayer_' + index, { height: '100%', width: '100%', videoId: item.id, playerVars: { 'playsinline': 1, 'controls': 1, 'rel': 0 }, events: { 'onStateChange': onPlayerStateChange } }); } }); }
    function onPlayerStateChange(event) { const iframe = event.target.getIframe(); const overlay = iframe.nextElementSibling; if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) { if (overlay) overlay.style.display = 'flex'; } }
    if (galleryItems.some(item => item.type === 'video')) { const tag = document.createElement('script'); tag.src = "https://www.youtube.com/iframe_api"; const firstScriptTag = document.getElementsByTagName('script')[0]; firstScriptTag.parentNode.insertBefore(tag, firstScriptTag); }
    document.addEventListener('DOMContentLoaded', () => {
        const wishlistBtn = document.getElementById('wishlist-btn');
        const shareBtn = document.getElementById('share-btn');
        const productId = wishlistBtn.dataset.productId;

        const checkWishlistStatus = async () => {
            const formData = new FormData();
            formData.append('action', 'check_wishlist');
            formData.append('product_id', productId);
            try {
                const response = await fetch('wishlist_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success' && result.in_wishlist) {
                    wishlistBtn.innerHTML = '<i class="fas fa-heart text-red-500"></i>';
                } else {
                    wishlistBtn.innerHTML = '<i class="far fa-heart"></i>';
                }
            } catch(e) {}
        };
        checkWishlistStatus();

        wishlistBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'toggle_wishlist');
            formData.append('product_id', productId);
            try {
                const response = await fetch('wishlist_ajax.php', { method: 'POST', body: formData });
                if (response.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }
                const result = await response.json();
                if (result.status === 'success') {
                    if (result.action === 'added') {
                        wishlistBtn.innerHTML = '<i class="fas fa-heart text-red-500"></i>';
                    } else {
                        wishlistBtn.innerHTML = '<i class="far fa-heart"></i>';
                    }
                }
            } catch(e) {
                alert('Could not update wishlist. Please try again.');
            }
        });
        
        shareBtn.addEventListener('click', async () => {
            const productTitle = '<?php echo addslashes(htmlspecialchars($product['name'])); ?>';
            const productUrl = '<?php echo BASE_URL . '/product_detail.php?id=' . $product['id']; ?>';

            // Check for Android interface
            if (typeof window.Android !== "undefined" && typeof window.Android.shareProduct === "function") {
                window.Android.shareProduct(productTitle, productUrl);
            } else {
                // Fallback to Web Share API
                const shareData = {
                    title: productTitle,
                    text: 'Check out this product on Skyveto!',
                    url: productUrl
                };
                try {
                    if (navigator.share) {
                        await navigator.share(shareData);
                    } else {
                        await navigator.clipboard.writeText(productUrl);
                        alert('Link copied to clipboard!');
                    }
                } catch(e) {
                    console.error("Share failed:", e);
                }
            }
        });

        const slider = document.querySelector('.main-image-slider'); const dotsContainer = document.querySelector('.slider-dots'); let currentIndex = 0;
        const goToSlide = (index) => { const slides = slider.querySelectorAll('.main-image-slide'); const totalSlides = slides.length; if (players[currentIndex] && typeof players[currentIndex].pauseVideo === 'function') { players[currentIndex].pauseVideo(); } if (index < 0) index = totalSlides - 1; if (index >= totalSlides) index = 0; slider.style.transform = `translateX(-${index * 100}%)`; if (dotsContainer) { const dots = dotsContainer.querySelectorAll('.slider-dot'); dots.forEach(dot => dot.classList.remove('active')); if (dots[index]) dots[index].classList.add('active'); } currentIndex = index; };
        if (slider) { const slides = slider.querySelectorAll('.main-image-slide'); if (slides.length > 1 && dotsContainer) { slides.forEach((_, i) => { const dot = document.createElement('button'); dot.classList.add('slider-dot'); if (i === 0) dot.classList.add('active'); dotsContainer.appendChild(dot); }); dotsContainer.querySelectorAll('.slider-dot').forEach((dot, i) => dot.addEventListener('click', () => goToSlide(i))); } let touchstartX = 0; slider.addEventListener('touchstart', e => { touchstartX = e.changedTouches[0].screenX; }, { passive: true }); slider.addEventListener('touchend', e => { const touchendX = e.changedTouches[0].screenX; if (touchendX < touchstartX - 50) goToSlide(currentIndex + 1); if (touchendX > touchstartX + 50) goToSlide(currentIndex - 1); }); }
        document.body.addEventListener('click', function(e){ const overlay = e.target.closest('.video-overlay'); if(overlay) { const playerId = overlay.dataset.playerId; if (players[playerId.split('_')[1]]) { players[playerId.split('_')[1]].playVideo(); overlay.style.display = 'none'; } } });
        const mainForm = document.getElementById('add-to-cart-form'); const mainAddToCartBtn = document.getElementById('main-add-to-cart-btn'); const mainBuyNowBtn = document.getElementById('main-buy-now-btn'); const sizeModal = document.getElementById('size-modal'); const sizeModalCloseBtn = document.getElementById('size-modal-close-btn'); const modalSizeSelector = document.getElementById('modal-size-selector'); const modalConfirmBtn = document.getElementById('modal-confirm-action-btn'); const sizeErrorMsg = document.getElementById('size-error-msg'); const stickyActionFooter = document.getElementById('sticky-action-footer'); const stickyAddToCartBtn = document.getElementById('sticky-add-to-cart-btn'); const stickyBuyNowBtn = document.getElementById('sticky-buy-now-btn'); const mainActionButtons = document.getElementById('main-action-buttons'); const hasSizes = <?php echo json_encode(!empty($sizes)); ?>; let buyNowAction = false; let selectedModalSize = null;
        const openSizeModal = (isBuyNow) => { buyNowAction = isBuyNow; modalConfirmBtn.textContent = isBuyNow ? 'Buy Now' : '<?php echo __t('prod_detail_add_to_cart'); ?>'; sizeModal.classList.remove('hidden'); }; const closeSizeModal = () => sizeModal.classList.add('hidden');
        const submitMainForm = () => { const actionName = buyNowAction ? 'buy_now' : 'add_to_cart'; let oldAction = mainForm.querySelector('input[name="add_to_cart"], input[name="buy_now"]'); if (oldAction) oldAction.remove(); let actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = actionName; actionInput.value = '1'; mainForm.appendChild(actionInput); mainForm.querySelector('input[name="quantity"]').value = document.getElementById('quantity-input').value; mainForm.submit(); };
        const initiateAction = (isBuyNow) => { buyNowAction = isBuyNow; const mainSelectedSize = document.querySelector('#main-size-selector .size-btn.selected'); if (hasSizes && !mainSelectedSize) { openSizeModal(isBuyNow); } else { if(mainSelectedSize) { document.getElementById('selected-size').value = mainSelectedSize.dataset.size; } else { document.getElementById('selected-size').value = ''; } submitMainForm(); } };
        if (mainAddToCartBtn) mainAddToCartBtn.addEventListener('click', () => initiateAction(false)); if (mainBuyNowBtn) mainBuyNowBtn.addEventListener('click', () => initiateAction(true)); if (stickyAddToCartBtn) stickyAddToCartBtn.addEventListener('click', () => initiateAction(false)); if (stickyBuyNowBtn) stickyBuyNowBtn.addEventListener('click', () => initiateAction(true));
        sizeModalCloseBtn.addEventListener('click', closeSizeModal); modalSizeSelector.addEventListener('click', (e) => { if (e.target.classList.contains('size-btn')) { selectedModalSize = e.target.dataset.size; sizeErrorMsg.classList.add('hidden'); modalSizeSelector.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('selected')); e.target.classList.add('selected'); } });
        modalConfirmBtn.addEventListener('click', () => { if (selectedModalSize) { document.getElementById('selected-size').value = selectedModalSize; submitMainForm(); } else { sizeErrorMsg.classList.remove('hidden'); } });
        const mainSizeSelector = document.getElementById('main-size-selector'); if (mainSizeSelector) { mainSizeSelector.addEventListener('click', (e) => { if(e.target.classList.contains('size-btn')) { const selectedSize = e.target.dataset.size; document.getElementById('selected-size').value = selectedSize; mainSizeSelector.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('selected')); e.target.classList.add('selected'); } }); }
        const descText = document.getElementById('description-text'); const seeMoreBtn = document.getElementById('see-more-desc-btn'); if (descText && seeMoreBtn) { if (descText.scrollHeight <= descText.clientHeight) { seeMoreBtn.style.display = 'none'; } else { seeMoreBtn.addEventListener('click', () => { descText.classList.toggle('expanded'); if (descText.classList.contains('expanded')) { seeMoreBtn.textContent = seeMoreBtn.dataset.seeLess; } else { seeMoreBtn.textContent = seeMoreBtn.dataset.seeMore; } }); } }
        
        // --- START: BUG FIX FOR STICKY FOOTER ---
        const relatedProductsSection = document.getElementById('related-products-section');
        let isMainButtonsVisible = true;
        let isRelatedSectionVisible = false;

        const updateStickyFooterVisibility = () => {
            // Show sticky footer if main buttons are NOT visible AND related products are also NOT visible
            if (!isMainButtonsVisible && !isRelatedSectionVisible) {
                stickyActionFooter.classList.remove('hidden');
            } else {
                stickyActionFooter.classList.add('hidden');
            }
        };

        if (mainActionButtons && stickyActionFooter) {
            // This observer always runs
            const mainButtonsObserver = new IntersectionObserver(([entry]) => {
                isMainButtonsVisible = entry.isIntersecting;
                updateStickyFooterVisibility();
            }, { threshold: 0 });
            mainButtonsObserver.observe(mainActionButtons);
            
            // This observer ONLY runs if the related products section exists
            if (relatedProductsSection) {
                const relatedProductsObserver = new IntersectionObserver(([entry]) => {
                    isRelatedSectionVisible = entry.isIntersecting;
                    updateStickyFooterVisibility();
                }, { threshold: 0 });
                relatedProductsObserver.observe(relatedProductsSection);
            }
        }
        // --- END: BUG FIX FOR STICKY FOOTER ---

        const lightbox = document.getElementById('lightbox'); const lightboxSlider = document.getElementById('lightbox-slider'); const lightboxThumbnails = document.getElementById('lightbox-thumbnails'); let lightboxCurrentIndex = 0; let lightboxPlayers = {};
        const openLightbox = (index) => { lightbox.classList.add('active'); document.documentElement.classList.add('no-scroll'); buildLightboxThumbnails(); goToLightboxSlide(index, 'auto'); }; const closeLightbox = () => { lightbox.classList.remove('active'); document.documentElement.classList.remove('no-scroll'); Object.values(lightboxPlayers).forEach(player => player.pauseVideo()); };
        
        const buildLightboxThumbnails = () => {
            lightboxThumbnails.innerHTML = '';
            galleryItems.forEach((item, index) => {
                if (item.type === 'image') {
                    const thumb = document.createElement('div');
                    thumb.className = 'lightbox-thumbnail-item';
                    thumb.dataset.index = index;
                    thumb.innerHTML = `<img src="${getImageUrlJs(item.src)}" alt="Thumbnail ${index + 1}">`;
                    thumb.addEventListener('click', () => goToLightboxSlide(index, 'auto'));
                    lightboxThumbnails.appendChild(thumb);
                }
            });
        };

        const goToLightboxSlide = (index, behavior = 'smooth') => { const slides = lightboxSlider.querySelectorAll('.lightbox-slide'); const total = slides.length; if (index < 0) index = total - 1; if (index >= total) index = 0; lightboxSlider.style.transform = `translateX(-${index * 100}%)`; lightboxCurrentIndex = index; const thumbs = lightboxThumbnails.querySelectorAll('.lightbox-thumbnail-item'); thumbs.forEach(thumb => thumb.classList.remove('active')); const activeThumb = lightboxThumbnails.querySelector(`[data-index="${index}"]`); if(activeThumb) { activeThumb.classList.add('active'); activeThumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }); } slides.forEach((slide, i) => { const img = slide.querySelector('img'); if (img && i !== index) { img.style.transform = 'scale(1) translate(0, 0)'; } }); };
        document.querySelectorAll('.main-img-item').forEach((img, index) => { img.addEventListener('click', () => openLightbox(index)); }); document.getElementById('lightbox-close').addEventListener('click', closeLightbox); document.getElementById('lightbox-prev').addEventListener('click', () => goToLightboxSlide(lightboxCurrentIndex - 1)); document.getElementById('lightbox-next').addEventListener('click', () => goToLightboxSlide(lightboxCurrentIndex + 1));
        const lightboxSlides = lightboxSlider.querySelectorAll('.lightbox-slide');
        lightboxSlides.forEach(slide => {
            const img = slide.querySelector('img'); if (!img) return; let scale = 1, panning = false, pointX = 0, pointY = 0, start = { x: 0, y: 0 }, zoomStartDistance = 0; const setTransform = () => { img.style.transform = `translate(${pointX}px, ${pointY}px) scale(${scale})`; }; const getDistance = (p1, p2) => Math.sqrt(Math.pow(p2.clientX - p1.clientX, 2) + Math.pow(p2.clientY - p1.clientY, 2));
            img.addEventListener('touchstart', e => { if (e.touches.length === 2) { zoomStartDistance = getDistance(e.touches[0], e.touches[1]); } else if (e.touches.length === 1 && scale > 1) { panning = true; start = { x: e.touches[0].clientX - pointX, y: e.touches[0].clientY - pointY }; } });
            img.addEventListener('touchmove', e => { e.preventDefault(); if (e.touches.length === 2) { const zoomCurrentDistance = getDistance(e.touches[0], e.touches[1]); const newScale = scale * (zoomCurrentDistance / zoomStartDistance); scale = Math.max(1, Math.min(newScale, 5)); zoomStartDistance = zoomCurrentDistance; setTransform(); } else if (panning && e.touches.length === 1) { pointX = e.touches[0].clientX - start.x; pointY = e.touches[0].clientY - start.y; setTransform(); } });
            img.addEventListener('touchend', e => { panning = false; if (e.touches.length < 2) zoomStartDistance = 0; if (scale === 1) { pointX = 0; pointY = 0; setTransform(); } });
        });
    });
</script>

<?php include_once 'common/bottom.php'; ?>