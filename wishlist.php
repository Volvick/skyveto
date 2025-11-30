<?php
// File: wishlist.php (NEW)
// Location: /
include_once 'common/config.php';
check_login();
$user_id = get_user_id();

// Fetch wishlisted items for the user
$wishlist_items = [];
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.mrp, p.image 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row;
}
$stmt->close();

include_once 'common/header.php';
?>

<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Back to Account</a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4">My Wishlist</h1>

    <div id="wishlist-grid" class="grid grid-cols-2 gap-4">
        <?php if (empty($wishlist_items)): ?>
            <p class="col-span-2 text-center text-gray-500 mt-8">Your wishlist is empty.</p>
        <?php else: ?>
            <?php foreach ($wishlist_items as $product): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden relative">
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="block">
                    <div class="aspect-square w-full overflow-hidden">
                        <!-- FIX: Use get_image_url() helper function to handle both local and URL images -->
                        <img src="<?php echo get_image_url($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-semibold text-gray-700 truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="mt-2 flex items-baseline flex-wrap">
                            <span class="text-lg font-bold text-gray-900 mr-2">₹<?php echo number_format($product['price']); ?></span>
                            <?php if (isset($product['mrp']) && $product['mrp'] > $product['price']): ?>
                                <span class="text-xs text-gray-500 line-through">₹<?php echo number_format($product['mrp']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <button class="remove-wishlist-btn absolute top-2 right-2 bg-white rounded-full h-8 w-8 flex items-center justify-center shadow" data-product-id="<?php echo $product['id']; ?>">
                    <i class="fas fa-trash text-red-500"></i>
                </button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('wishlist-grid').addEventListener('click', async (e) => {
        const removeBtn = e.target.closest('.remove-wishlist-btn');
        if (removeBtn) {
            if (!confirm('Are you sure you want to remove this item from your wishlist?')) return;
            
            const productId = removeBtn.dataset.productId;
            const formData = new FormData();
            formData.append('action', 'remove_from_wishlist');
            formData.append('product_id', productId);

            try {
                const response = await fetch('wishlist_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    // Remove the item from the page without reloading
                    removeBtn.closest('.bg-white').remove();
                } else {
                    alert(result.message || 'Could not remove item.');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
    });
});
</script>

<?php
include_once 'common/bottom.php';
?>