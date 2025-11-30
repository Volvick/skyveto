<?php
// File: product.php
// Location: /
// FINAL, ROBUST VERSION: Displays discount percentage on product cards.
include_once 'common/config.php';
include_once 'common/header.php';

// --- FILTERING, SORTING, AND SEARCHING LOGIC ---
$category_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$sort_order = $_GET['sort'] ?? 'newest';
$search_query = trim($_GET['search_query'] ?? '');
$gender_filter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$discount_min = isset($_GET['discount_min']) ? (int)$_GET['discount_min'] : null;
$discount_max = isset($_GET['discount_max']) ? (int)$_GET['discount_max'] : null;

$page_title = __t('product_our_products');
$category_name = '';
$products = [];
$sub_categories = [];
$sections_by_position = [];

$show_all_categories = ($category_id == 0 && empty($search_query) && empty($gender_filter) && $sort_order == 'newest' && $discount_min === null);
$show_product_grid = !$show_all_categories;

$all_main_categories_result = $conn->query("SELECT id, name, image FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC");
$all_main_categories = $all_main_categories_result ? $all_main_categories_result->fetch_all(MYSQLI_ASSOC) : [];

if ($show_product_grid) {
    $params = [];
    $types = '';
    
    $sql = "SELECT p.id, p.name, p.price, p.mrp, p.image, 
            ((p.mrp - p.price) / p.mrp) * 100 AS discount_percentage
            FROM products p 
            LEFT JOIN categories c ON p.cat_id = c.id 
            WHERE 1=1";

    if ($category_id > 0) {
        $main_cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $main_cat_stmt->bind_param("i", $category_id);
        $main_cat_stmt->execute();
        $category_name = $main_cat_stmt->get_result()->fetch_assoc()['name'] ?? '';
        $page_title = htmlspecialchars($category_name);
        $main_cat_stmt->close();

        $sub_cat_stmt = $conn->prepare("SELECT id, name, image FROM categories WHERE parent_id = ? ORDER BY name ASC");
        $sub_cat_stmt->bind_param("i", $category_id);
        $sub_cat_stmt->execute();
        $result_sub_cat = $sub_cat_stmt->get_result();
        while ($row = $result_sub_cat->fetch_assoc()) { $sub_categories[] = $row; }
        $sub_cat_stmt->close();

        $section_stmt = $conn->prepare("SELECT image, display_order FROM category_sections WHERE category_id = ? AND is_active = 1");
        $section_stmt->bind_param("i", $category_id);
        $section_stmt->execute();
        $result_section = $section_stmt->get_result();
        while ($row = $result_section->fetch_assoc()) { $sections_by_position[$row['display_order']] = $row; }
        $section_stmt->close();

        $category_ids_to_fetch = [$category_id];
        foreach ($sub_categories as $sub_cat) { $category_ids_to_fetch[] = $sub_cat['id']; }
        $placeholders = implode(',', array_fill(0, count($category_ids_to_fetch), '?'));
        $sql .= " AND p.cat_id IN ($placeholders)";
        $types .= str_repeat('i', count($category_ids_to_fetch));
        $params = array_merge($params, $category_ids_to_fetch);
    }
    
    if (!empty($search_query)) {
        $sql .= " AND p.name LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= 's';
        if ($category_id == 0) { $page_title = "Search for: " . htmlspecialchars($search_query); }
    }

    if (!empty($gender_filter)) {
        $sql .= " AND p.gender = ?";
        $params[] = $gender_filter;
        $types .= 's';
    }

    if ($discount_min !== null && $discount_max !== null) {
        $sql .= " HAVING discount_percentage BETWEEN ? AND ?";
        $params[] = $discount_min;
        $params[] = $discount_max;
        $types .= 'ii';
        $page_title = "$discount_min% - $discount_max% Off";
    } else {
        // This makes sure products without discounts are also shown when not filtering by discount
        $sql = str_replace("WHERE p.mrp > 0 AND p.mrp > p.price", "WHERE 1=1", $sql);
    }

    switch ($sort_order) {
        case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
        default: $sql .= " ORDER BY p.created_at DESC"; break;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result_products = $stmt->get_result();
        while ($row = $result_products->fetch_assoc()) { $products[] = $row; }
        $stmt->close();
    }
}
?>
<style>
    .filter-bar { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 8px 16px; }
    .filter-btn { border-right: 1px solid #e5e7eb; }
    .filter-btn:last-child { border-right: none; }
    .filter-btn.active { color: #2563eb; font-weight: 600; }
    #product-listing-container { transition: opacity 0.3s ease-in-out; }
</style>

<main class="p-4">
    <div id="product-listing-container">
        <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h1>

        <?php if ($show_all_categories): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <?php foreach ($all_main_categories as $category): ?>
                <a href="product.php?cat_id=<?php echo $category['id']; ?>" class="block bg-white rounded-lg shadow-md overflow-hidden text-center">
                    <div class="aspect-square w-full overflow-hidden">
                         <img src="<?php echo get_image_url($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-2">
                        <h3 class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($category['name']); ?></h3>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if ($category_id > 0 && !empty($sub_categories)): ?>
            <div class="mb-6">
                <!-- FIX: Changed grid-cols-3 to grid-cols-2 -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <?php
                $subcat_counter = 0;
                foreach ($sub_categories as $sub_cat) {
                    echo '<a href="product.php?cat_id='. $sub_cat['id'] .'" class="block bg-white rounded-lg shadow-sm overflow-hidden text-center p-2 hover:shadow-lg transition-shadow">';
                    echo '<div class="aspect-square w-full overflow-hidden rounded-md"><img src="'. get_image_url($sub_cat['image']) .'" alt="'. htmlspecialchars($sub_cat['name']) .'" class="w-full h-full object-cover"></div></a>';
                    $subcat_counter++;

                    if (isset($sections_by_position[$subcat_counter])) {
                        $section = $sections_by_position[$subcat_counter];
                        echo '</div>';
                        if (!empty($section['image'])) {
                            echo '<div class="w-full my-4 px-4">';
                            echo '<img src="' . get_image_url($section['image']) . '" class="w-full h-auto rounded-md" alt="Offer Banner">';
                            echo '</div>';
                        }
                        // FIX: Changed grid-cols-3 to grid-cols-2
                        echo '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">';
                    }
                }
                ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="filter-bar flex items-center justify-around mb-4 shadow-sm rounded-md">
                <button id="sort-btn" class="filter-btn flex-1 py-2 text-center text-gray-700 font-medium"><i class="fas fa-sort-amount-down mr-2"></i>Sort</button>
                <a href="product.php" class="filter-btn flex-1 py-2 text-center text-gray-700 font-medium">Category</a>
                <div class="filter-btn flex-1 py-2 text-center text-gray-700 font-medium relative" id="gender-filter-container">
                    <button id="gender-btn">Gender <i class="fas fa-chevron-down text-xs ml-1"></i></button>
                    <div id="gender-dropdown" class="hidden absolute top-full left-0 mt-2 w-full bg-white shadow-lg rounded-md border z-10">
                        <a href="#" data-gender="" class="block px-4 py-2 text-sm hover:bg-gray-100 <?php echo empty($gender_filter) ? 'font-bold' : ''; ?>">All</a>
                        <a href="#" data-gender="Men" class="block px-4 py-2 text-sm hover:bg-gray-100 <?php echo ($gender_filter == 'Men') ? 'font-bold' : ''; ?>">Men</a>
                        <a href="#" data-gender="Women" class="block px-4 py-2 text-sm hover:bg-gray-100 <?php echo ($gender_filter == 'Women') ? 'font-bold' : ''; ?>">Women</a>
                        <a href="#" data-gender="Kids" class="block px-4 py-2 text-sm hover:bg-gray-100 <?php echo ($gender_filter == 'Kids') ? 'font-bold' : ''; ?>">Kids</a>
                    </div>
                </div>
                <button id="filter-btn" class="flex-1 py-2 text-center text-gray-700 font-medium"><i class="fas fa-filter mr-2"></i>Filters</button>
            </div>

            <div id="product-grid" class="grid grid-cols-2 gap-4">
                <?php if (empty($products)): ?>
                    <p class="col-span-full text-center text-gray-500 mt-8"><?php echo __t('product_no_products_found'); ?></p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="bg-white rounded-lg shadow-md overflow-hidden block">
                        <div class="aspect-square w-full overflow-hidden"><img src="<?php echo get_image_url($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover"></div>
                        <div class="p-3">
                            <h3 class="text-sm font-semibold text-gray-700 truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <!-- FIX: Added discount percentage display -->
                            <div class="mt-2 flex items-baseline flex-wrap">
                                <span class="text-lg font-bold text-gray-900 mr-2">₹<?php echo number_format($product['price']); ?></span>
                                <?php if (isset($product['mrp']) && $product['mrp'] > $product['price']): ?>
                                    <span class="text-xs text-gray-500 line-through mr-2">₹<?php echo number_format($product['mrp']); ?></span>
                                    <span class="text-xs font-semibold text-green-600"><?php echo round((($product['mrp'] - $product['price']) / $product['mrp']) * 100); ?>% off</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- SORT OPTIONS MODAL (No changes here) -->
<div id="sort-modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>
<div id="sort-modal" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl p-4 z-40 transform translate-y-full transition-transform duration-300">
    <h3 class="text-lg font-bold text-center mb-4">Sort By</h3>
    <div id="sort-options" class="space-y-2">
        <label class="flex items-center p-3 rounded-md has-[:checked]:bg-blue-50">
            <input type="radio" name="sort" value="newest" class="h-5 w-5 text-blue-600" <?php echo ($sort_order == 'newest') ? 'checked' : ''; ?>>
            <span class="ml-3 font-medium text-gray-700"><?php echo __t('product_sort_by_newest'); ?></span>
        </label>
        <label class="flex items-center p-3 rounded-md has-[:checked]:bg-blue-50">
            <input type="radio" name="sort" value="price_asc" class="h-5 w-5 text-blue-600" <?php echo ($sort_order == 'price_asc') ? 'checked' : ''; ?>>
            <span class="ml-3 font-medium text-gray-700"><?php echo __t('product_price_low_to_high'); ?></span>
        </label>
        <label class="flex items-center p-3 rounded-md has-[:checked]:bg-blue-50">
            <input type="radio" name="sort" value="price_desc" class="h-5 w-5 text-blue-600" <?php echo ($sort_order == 'price_desc') ? 'checked' : ''; ?>>
            <span class="ml-3 font-medium text-gray-700"><?php echo __t('product_price_high_to_low'); ?></span>
        </label>
    </div>
</div>

<script>
// JavaScript for this page does not need changes for this fix.
document.addEventListener('DOMContentLoaded', () => {
    const productListingContainer = document.getElementById('product-listing-container');

    const updateContent = async (url) => {
        if (!productListingContainer) return;
        productListingContainer.style.opacity = '0.5';
        try {
            const response = await fetch(url);
            const text = await response.text();
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(text, 'text/html');
            const newContent = newDoc.getElementById('product-listing-container');
            if (newContent) {
                productListingContainer.innerHTML = newContent.innerHTML;
                document.title = newDoc.title;
                history.pushState({}, '', url);
            } else { window.location.href = url; }
        } catch (error) {
            console.error('Failed to update content:', error);
            window.location.href = url;
        } finally {
            productListingContainer.style.opacity = '1';
        }
    };

    const updateUrlAndApply = (key, value) => {
        const url = new URL(window.location.href);
        if (value === '' || value === null) {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
        // Remove discount filters if another filter is applied
        if (key !== 'discount_min' && key !== 'discount_max') {
            url.searchParams.delete('discount_min');
            url.searchParams.delete('discount_max');
        }
        updateContent(url.toString());
    };

    document.body.addEventListener('click', (event) => {
        const sortBtn = event.target.closest('#sort-btn');
        const genderBtn = event.target.closest('#gender-btn');
        const genderLink = event.target.closest('#gender-dropdown a');
        const overlay = event.target.closest('#sort-modal-overlay');
        const filterBtn = event.target.closest('#filter-btn');

        if(sortBtn) {
            const sortModalOverlay = document.getElementById('sort-modal-overlay');
            const sortModal = document.getElementById('sort-modal');
            sortModalOverlay.classList.remove('hidden');
            sortModal.classList.remove('translate-y-full');
        }
        if(overlay) {
            const sortModalOverlay = document.getElementById('sort-modal-overlay');
            const sortModal = document.getElementById('sort-modal');
            sortModalOverlay.classList.add('hidden');
            sortModal.classList.add('translate-y-full');
        }
        
        if (genderBtn) {
            event.stopPropagation();
            document.getElementById('gender-dropdown').classList.toggle('hidden');
        } else {
             const dropdown = document.getElementById('gender-dropdown');
             if(dropdown && !dropdown.contains(event.target)){
                dropdown.classList.add('hidden');
             }
        }
        
        if (genderLink) {
            event.preventDefault();
            document.getElementById('gender-dropdown').classList.add('hidden');
            updateUrlAndApply('gender', genderLink.dataset.gender);
        }
        
        if (filterBtn) {
            alert('More filter options will be added in a future update!');
        }
    });

    const sortOptions = document.getElementById('sort-options');
    if (sortOptions) {
        sortOptions.addEventListener('change', (e) => {
            if (e.target.matches('input[name="sort"]')) {
                const sortModalOverlay = document.getElementById('sort-modal-overlay');
                const sortModal = document.getElementById('sort-modal');
                sortModalOverlay.classList.add('hidden');
                sortModal.classList.add('translate-y-full');
                updateUrlAndApply('sort', e.target.value);
            }
        });
    }

    window.addEventListener('popstate', (e) => {
        updateContent(location.href);
    });
});
</script>

<?php
include_once 'common/bottom.php';
?>