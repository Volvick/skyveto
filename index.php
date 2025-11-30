<?php
// File: index.php
// Location: /
// FINAL VERSION: Added continuous auto-scroll animation to the category section.

include_once 'common/config.php';
include_once 'common/header.php';

// --- NEW: LOGIC TO DETERMINE IF LANGUAGE MODAL SHOULD BE SHOWN ---
$show_language_modal = !isset($_SESSION['language_selected']) && !isset($_SESSION['user_id']);

// --- NEW: Language data for the modal ---
$languages = [
    'en' => ['name' => 'English', 'native' => 'English'],
    'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी'],
    'mr' => ['name' => 'Marathi', 'native' => 'मराठी'],
    'bn' => ['name' => 'Bengali', 'native' => 'বাংলা'],
    'te' => ['name' => 'Telugu', 'native' => 'తెలుగు'],
    'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்'],
    'gu' => ['name' => 'Gujarati', 'native' => 'ગુજરાતી'],
    'kn' => ['name' => 'Kannada', 'native' => 'ಕನ್ನಡ'],
    'ml' => ['name' => 'Malayalam', 'native' => 'മലയാളം'],
    'or' => ['name' => 'Odia', 'native' => 'ଓଡ଼ିଆ'],
    'pa' => ['name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ'],
];


// --- ALL DATA FETCHING LOGIC ---

// 1. Fetch Banners for the Carousel
$banners = [];
$banner_result = $conn->query("SELECT image, link_type, link_target FROM banners WHERE is_active = 1 ORDER BY id DESC");
if ($banner_result) {
    while ($row = $banner_result->fetch_assoc()) {
        $banners[] = $row;
    }
}

// 2. Fetch main categories for the horizontal scroll
$categories = [];
$sql = "SELECT id, name, image FROM categories 
        WHERE parent_id IS NULL OR parent_id = 0 
        ORDER BY id ASC"; // Changed to ID ASC for consistent scroll order
        
$cat_result = $conn->query($sql);
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}


// --- FILTERING LOGIC ---
$sort_order = $_GET['sort'] ?? 'newest';
$gender_filter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$search_query = trim($_GET['search_query'] ?? '');
$discount_min = isset($_GET['discount_min']) ? (int)$_GET['discount_min'] : null;
$discount_max = isset($_GET['discount_max']) ? (int)$_GET['discount_max'] : null;

$is_filtered = !empty($_GET['sort']) || !empty($_GET['gender']) || !empty($_GET['search_query']) || ($discount_min !== null);

$products = [];

$sql = "SELECT p.id, p.name, p.price, p.mrp, p.image, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count,
        (CASE WHEN p.mrp > p.price THEN ((p.mrp - p.price) / p.mrp) * 100 ELSE 0 END) AS discount_percentage
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.variant_of IS NULL";

$params = [];
$types = '';

if (!empty($search_query)) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= 's';
}
if (!empty($gender_filter)) {
    $sql .= " AND p.gender = ?";
    $params[] = $gender_filter;
    $types .= 's';
}

$sql .= " GROUP BY p.id";

if ($discount_min !== null && $discount_max !== null) {
    $sql .= " HAVING discount_percentage BETWEEN ? AND ?";
    $params[] = $discount_min;
    $params[] = $discount_max;
    $types .= 'ii';
}

switch ($sort_order) {
    case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    default: $sql .= " ORDER BY p.created_at DESC"; break;
}

// THIS IS THE FIX: The "LIMIT 8" clause has been removed.
// if (!$is_filtered) {
//     $sql .= " LIMIT 8";
// }

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

$current_url_params = http_build_query($_GET);
?>
<style>
    .filter-bar { background-color: white; border-bottom: 1px solid #e5e7eb; padding: 8px 16px; }
    .filter-btn { border-right: 1px solid #e5e7eb; }
    .filter-btn:last-child { border-right: none; }
    .filter-btn.active { color: #2563eb; font-weight: 600; }
    #product-listing-container { transition: opacity 0.3s ease-in-out; }

    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    @keyframes flicker {
        0%, 100% { opacity: 1; transform: scaleY(1); }
        50% { opacity: 0.7; transform: scaleY(0.98); }
        25%, 75% { opacity: 0.9; }
    }
    @keyframes fall {
        0% { transform: translateY(-10vh) rotateZ(-20deg); opacity: 1; }
        100% { transform: translateY(110vh) rotateZ(20deg); opacity: 0; }
    }

    .diwali-categories-section {
        background: linear-gradient(160deg, #f8c26c, #e59837);
        padding: 15px 0 20px 0;
        margin: -1rem -1rem 0 -1rem;
        position: relative;
        overflow: hidden;
    }
    .diwali-categories-section::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background-image: radial-gradient(circle at 15% 30%, rgba(255,255,255,0.07) 0%, transparent 40%), radial-gradient(circle at 85% 70%, rgba(255,255,255,0.07) 0%, transparent 40%);
        background-size: 200px 200px; opacity: 0.5; z-index: 1;
    }
    .diwali-categories-section > * { position: relative; z-index: 3; }
    .diwali-categories-section h2 {
        color: #4E342E; font-weight: bold; font-size: 1.5rem; display: flex; align-items: center;
        margin-bottom: 0.5rem; padding-left: 1rem;
        /* --- FIX: Added top padding for spacing --- */
        padding-top: 10px;
    }
    .diya-icon {
        width: 28px; height: 18px; background-color: #e67e22; border-radius: 50% 50% 20% 20%;
        position: relative; margin-right: 12px; margin-top: 4px; box-shadow: inset 0 -2px 3px rgba(0,0,0,0.2);
    }
    .diya-icon::before {
        content: ''; position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
        width: 8px; height: 14px; background-color: #f1c40f; border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
        animation: flicker 1s infinite ease-in-out; box-shadow: 0 0 5px #f1c40f, 0 0 10px #f1c40f;
    }
    .diwali-category-item {
        display: flex; flex-direction: column; align-items: center;
        text-align: center; flex-shrink: 0; width: 80px; margin-right: 20px;
    }
    .decorative-border {
        width: 80px; height: 80px; border-radius: 50%; position: relative;
        display: flex; align-items: center; justify-content: center;
        padding: 4px; background: linear-gradient(45deg, #d35400, #e67e22);
    }
    .decorative-border::before {
        content: ''; position: absolute; inset: 0;
        border: 2px dotted #fff; border-radius: 50%; animation: spin 30s linear infinite;
    }
    .category-image-container {
        width: 100%; height: 100%; background-color: #fff; border-radius: 50%;
        overflow: hidden; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 1;
    }
    .category-image-container img { width: 100%; height: 100%; object-fit: cover; }
    .category-name-text {
        margin-top: 6px; color: #422006; font-weight: 600; font-size: 0.8rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 75px;
    }
    .flower-fall-container {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        overflow: hidden; z-index: 2; pointer-events: none;
    }
    .flower {
        position: absolute; top: -10%; font-size: 22px;
        animation: fall linear infinite; opacity: 0; text-shadow: 0 0 5px rgba(0,0,0,0.2);
    }
</style>

<div class="p-4 pt-0">
    <section class="diwali-categories-section">
        <div class="flower-fall-container">
            <?php for ($i = 0; $i < 20; $i++): ?>
                <div class="flower" style="left: <?php echo rand(0, 100); ?>%; animation-duration: <?php echo rand(8, 15); ?>s; animation-delay: <?php echo rand(0, 10); ?>s;">
                    <?php echo ['🌸', '🌼', '🌺'][rand(0,2)]; ?>
                </div>
            <?php endfor; ?>
        </div>

        <h2><i class="diya-icon"></i><?php echo __t('index_categories'); ?></h2>
        
        <div id="category-scroller" class="flex space-x-0 overflow-x-auto pb-4 px-4 no-scrollbar">
            <?php if (empty($categories)): ?>
                <p class="text-white">No categories found.</p>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <a href="product.php?cat_id=<?php echo $category['id']; ?>" class="flex-shrink-0">
                    <div class="diwali-category-item">
                        <div class="decorative-border">
                            <div class="category-image-container">
                                <img src="<?php echo get_image_url($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            </div>
                        </div>
                        <span class="category-name-text"><?php echo htmlspecialchars($category['name']); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if (!empty($banners)): ?>
<section id="banner-carousel" class="relative w-full overflow-hidden bg-gray-200 mb-4" style="height: 160px;">
    <div id="banner-inner" class="flex transition-transform duration-700 ease-in-out h-full">
        <?php foreach ($banners as $banner): ?>
            <?php
            $href = '#';
            if ($banner['link_type'] === 'url' && !empty($banner['link_target'])) {
                $href = htmlspecialchars($banner['link_target']);
            } elseif ($banner['link_type'] === 'category' && !empty($banner['link_target'])) {
                $href = 'product.php?cat_id=' . (int)$banner['link_target'];
            } elseif ($banner['link_type'] === 'discount_range' && !empty($banner['link_target'])) {
                list($min, $max) = explode('-', $banner['link_target']);
                $href = 'product.php?discount_min=' . (int)$min . '&discount_max=' . (int)$max;
            }
            ?>
            <div class="w-full flex-shrink-0 h-full">
                <a href="<?php echo $href; ?>"><img src="<?php echo get_image_url($banner['image']); ?>" class="w-full h-full object-cover"></a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="p-4 pt-0">
    <section id="product-listing-container">
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

        <!-- NEW: SKELETON LOADER -->
        <div id="skeleton-grid" class="grid grid-cols-2 gap-4">
            <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden block">
                <div class="skeleton skeleton-image"></div>
                <div class="p-3">
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text-sm"></div>
                    <div class="skeleton skeleton-text-lg"></div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        
        <!-- REAL CONTENT (Initially Hidden) -->
        <div id="product-grid" class="grid grid-cols-2 gap-4" style="display: none;">
            <?php if (empty($products)): ?>
                <p class="text-gray-500 col-span-2 text-center py-8">No products found for the selected filters.</p>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="bg-white rounded-lg shadow-md overflow-hidden block">
                    <div class="aspect-square w-full overflow-hidden">
                        <img src="<?php echo get_image_url($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-semibold text-gray-700 truncate"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <?php if (isset($product['review_count']) && $product['review_count'] > 0): ?>
                        <div class="flex items-center mt-1 text-xs">
                            <span class="font-bold text-gray-700"><?php echo round($product['avg_rating'], 1); ?></span>
                            <i class="fas fa-star text-yellow-400 ml-1"></i>
                            <span class="text-gray-500 ml-2">(<?php echo $product['review_count']; ?>)</span>
                        </div>
                        <?php endif; ?>
                        <div class="mt-2 flex items-baseline flex-wrap">
                            <span class="text-lg font-bold text-gray-900 mr-2">₹<?php echo number_format($product['price']); ?></span>
                            <?php if (isset($product['mrp']) && $product['mrp'] > $product['price']): ?>
                                <span class="text-xs text-gray-500 line-through mr-2">₹<?php echo number_format($product['mrp']); ?></span>
                                <span class="text-xs font-semibold text-green-600"><?php echo round($product['discount_percentage']); ?>% off</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- SORT OPTIONS MODAL -->
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

<!-- Language Selection Modal for First Time Visit -->
<div id="language-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Choose your language</h2>
            <button id="lang-modal-close-btn" class="text-red-600 text-3xl font-bold leading-none">&times;</button>
        </div>
        <div id="language-list" class="space-y-2 max-h-80 overflow-y-auto">
            <?php foreach ($languages as $code => $lang): ?>
            <div class="language-option border rounded-lg p-3 flex justify-between items-center cursor-pointer hover:bg-blue-50" data-lang-code="<?php echo $code; ?>">
                <div>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($lang['name']); ?></span>
                    <span class="text-gray-500 ml-3"><?php echo htmlspecialchars($lang['native']); ?></span>
                </div>
                <i class="fas fa-chevron-right text-gray-400"></i>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- BANNER CAROUSEL LOGIC ---
    const setupCarousel = (containerSelector, innerSelector, autoScrollSpeed = 3000) => {
        const container = document.querySelector(containerSelector);
        if (!container) return;

        const inner = container.querySelector(innerSelector);
        if (!inner || inner.children.length <= 1) return;

        let currentIndex = 0;
        const slides = inner.children;
        const totalSlides = slides.length;
        let autoScrollInterval;
        let userInteractionTimeout;

        const startAutoScroll = () => {
            if (autoScrollInterval) clearInterval(autoScrollInterval);
            autoScrollInterval = setInterval(() => {
                currentIndex = (currentIndex + 1) % totalSlides;
                inner.style.transition = 'transform 0.7s ease-in-out';
                inner.style.transform = `translateX(-${currentIndex * 100}%)`;
            }, autoScrollSpeed);
        };
        
        const stopAutoScroll = () => clearInterval(autoScrollInterval);
        
        container.addEventListener('touchstart', () => stopAutoScroll(), { passive: true });
        container.addEventListener('touchend', () => {
            clearTimeout(userInteractionTimeout);
            userInteractionTimeout = setTimeout(startAutoScroll, 5000);
        }, { passive: true });

        let touchstartX = 0;
        inner.addEventListener('touchstart', e => { touchstartX = e.changedTouches[0].screenX; }, { passive: true });
        inner.addEventListener('touchend', e => {
            const touchendX = e.changedTouches[0].screenX;
            if (touchendX < touchstartX - 50) {
                currentIndex = (currentIndex + 1) % totalSlides;
            } else if (touchendX > touchstartX + 50) {
                currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
            } else {
                return;
            }
            inner.style.transition = 'transform 0.3s ease-in-out';
            inner.style.transform = `translateX(-${currentIndex * 100}%)`;
        });
        
        startAutoScroll();
    };

    setupCarousel('#banner-carousel', '#banner-inner', 3000);

    // --- FIX: Execute category scroller logic after the page and all images have loaded ---
    window.addEventListener('load', () => {
        const scroller = document.querySelector('#category-scroller');
        if (!scroller || scroller.children.length <= 1) return;

        // Only activate the animation if the content overflows its container
        if (scroller.scrollWidth <= scroller.clientWidth) {
            return;
        }

        // Duplicate items for a seamless loop
        const items = Array.from(scroller.children);
        items.forEach(item => {
            const clone = item.cloneNode(true);
            scroller.appendChild(clone);
        });
        
        let scrollInterval;
        let userInteractionTimeout;
        const scrollWidthOriginal = scroller.scrollWidth / 2;

        const startScrolling = () => {
            if (scrollInterval) clearInterval(scrollInterval);
            scrollInterval = setInterval(() => {
                if (scroller.scrollLeft >= scrollWidthOriginal) {
                    scroller.scrollTo({ left: 0, behavior: 'auto' });
                } else {
                    scroller.scrollBy({ left: 1, behavior: 'auto' });
                }
            }, 50); // Speed control
        };

        const stopScrolling = () => clearInterval(scrollInterval);

        scroller.addEventListener('mouseenter', stopScrolling);
        scroller.addEventListener('mouseleave', () => {
            clearTimeout(userInteractionTimeout);
            userInteractionTimeout = setTimeout(startScrolling, 2000);
        });
        scroller.addEventListener('touchstart', stopScrolling, { passive: true });
        scroller.addEventListener('touchend', () => {
            clearTimeout(userInteractionTimeout);
            userInteractionTimeout = setTimeout(startScrolling, 5000);
        }, { passive: true });
        
        startScrolling();
    });

    // --- The rest of your JavaScript remains unchanged ---
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
        if (document.getElementById('language-modal') && <?php echo json_encode($show_language_modal); ?>) {
             window.location.reload();
        } else {
            updateContent(location.href);
        }
    });

    const languageModal = document.getElementById('language-modal');
    const showLanguageModal = <?php echo json_encode($show_language_modal); ?>;

    if (languageModal && showLanguageModal) {
        const langModalCloseBtn = document.getElementById('lang-modal-close-btn');
        const languageOptions = document.querySelectorAll('.language-option');
        let languageInteractionDone = false;

        const setLanguageAndReload = async (langCode) => {
            if (languageInteractionDone) return;
            languageInteractionDone = true;
            document.getElementById('language-list').innerHTML = '<div class="text-center p-4"><div class="animate-spin rounded-full h-8 w-8 border-t-2 border-blue-500 mx-auto"></div><p class="mt-2">Saving preference...</p></div>';
            const formData = new FormData();
            formData.append('action', 'set_language');
            formData.append('lang_code', langCode);
            try {
                const response = await fetch('welcome_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Could not set language. Defaulting to English.');
                    window.location.reload();
                }
            } catch (error) {
                alert('An error occurred. Defaulting to English.');
                window.location.reload();
            }
        };
        const closeModalAndSetDefault = () => {
            if (!languageInteractionDone) {
                 setLanguageAndReload('en');
            }
        };
        languageModal.classList.remove('hidden');
        langModalCloseBtn.addEventListener('click', closeModalAndSetDefault);
        languageModal.addEventListener('click', (e) => {
            if (e.target === languageModal) {
                closeModalAndSetDefault();
            }
        });
        languageOptions.forEach(option => {
            option.addEventListener('click', () => {
                setLanguageAndReload(option.dataset.langCode);
            });
        });
        history.pushState({modal: true}, '');
        window.addEventListener('popstate', function(event) {
            if (!languageModal.classList.contains('hidden')) {
                closeModalAndSetDefault();
            }
        });
    }
});
</script>

<?php
include_once 'common/bottom.php';
?>