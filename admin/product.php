<?php
// File: product.php
// Location: /admin/
// FINAL, ROBUST VERSION: Added Product ID column and truncated long product names.
include_once 'common/header.php'; 

// --- NEW: Fetch and structure categories hierarchically ---
$categories_structured = [];
$cat_result = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name ASC");
if ($cat_result) {
    $sub_categories = [];
    while ($row = $cat_result->fetch_assoc()) {
        if ($row['parent_id'] === null || $row['parent_id'] == 0) {
            $categories_structured[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sub_categories' => []
            ];
        } else {
            $sub_categories[] = $row;
        }
    }
    foreach ($sub_categories as $sub_cat) {
        if (isset($categories_structured[$sub_cat['parent_id']])) {
            $categories_structured[$sub_cat['parent_id']]['sub_categories'][] = $sub_cat;
        }
    }
}


// Fetch all products to be passed to JavaScript for the "Variant Of" dropdown
$all_products = [];
$prod_result = $conn->query("SELECT id, name FROM products ORDER BY name ASC");
if ($prod_result) { while ($row = $prod_result->fetch_assoc()) { $all_products[] = $row; } }
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Manage Products</h1>
    <button id="add-product-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"><i class="fas fa-plus"></i> Add Product</button>
</div>
<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <!-- FIX: Added ID column header -->
            <thead><tr class="border-b"><th class="p-3 text-left">ID</th><th class="p-3 text-left">Product</th><th class="p-3 text-left">Category</th><th class="p-3 text-left">Price</th><th class="p-3 text-left">Stock</th><th class="p-3 text-right">Actions</th></tr></thead>
            <tbody id="product-table-body"></tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div id="product-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Add New Product</h2>
        <form id="product-form" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="product_id" id="product_id">
            <input type="hidden" name="current_image" id="current_image">
            <input type="hidden" name="cat_id" id="cat_id" required> <!-- The final selected category ID goes here -->
            <div><label>Name</label><input type="text" name="name" id="name" required class="w-full p-2 border rounded-md"></div>
            
            <!-- NEW: Hierarchical Category Selection -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label>Main Category</label>
                    <select id="main_cat_id" required class="w-full p-2 border rounded-md">
                        <option value="">Select Main Category</option>
                         <?php foreach ($categories_structured as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="sub-cat-container" class="hidden">
                    <label>Sub-Category</label>
                    <select id="sub_cat_id" class="w-full p-2 border rounded-md">
                        <option value="">Select Sub-Category</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                 <div>
                    <label>Gender</label>
                    <select name="gender" id="gender" class="w-full p-2 border rounded-md">
                        <option value="">All / Unisex</option>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Kids">Kids</option>
                    </select>
                </div>
                <div>
                    <label>Variant Of (Optional)</label>
                    <select name="variant_of" id="variant_of" class="w-full p-2 border rounded-md">
                        <option value="">None (This is a main product)</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label>Original Price (MRP)</label><input type="number" step="0.01" name="mrp" id="mrp" placeholder="e.g. 999" class="w-full p-2 border rounded-md"></div>
                <div><label>Selling Price</label><input type="number" step="0.01" name="price" id="price" required placeholder="e.g. 499" class="w-full p-2 border rounded-md"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                 <div><label>Stock</label><input type="number" name="stock" id="stock" required class="w-full p-2 border rounded-md"></div>
                 <div>
                    <label>Sizes (comma-separated)</label>
                    <input type="text" name="sizes" id="sizes" placeholder="e.g., S, M, L, XL" class="w-full p-2 border rounded-md">
                </div>
            </div>
            <div>
                <label>Delivery Charge (₹)</label>
                <input type="number" step="0.01" name="delivery_charge" id="delivery_charge" class="w-full p-2 border rounded-md" placeholder="Enter 0 for free delivery">
            </div>
            <div><label>Description</label><textarea name="description" id="description" rows="3" class="w-full p-2 border rounded-md"></textarea></div>
            <div>
                <label>YouTube Video Link (optional)</label>
                <input type="url" name="video_url" id="video_url" placeholder="e.g., https://www.youtube.com/watch?v=..." class="w-full p-2 border rounded-md">
            </div>
            <div>
                <label>Main Image</label>
                <input type="file" name="image" id="image" class="w-full p-2 border rounded-md">
                <input type="url" name="main_image_url" id="main_image_url" placeholder="Or paste image URL here" class="mt-2 w-full p-2 border rounded-md">
            </div>
            <div>
                <label>Additional Images (optional)</label>
                <input type="file" name="additional_images[]" id="additional_images" class="w-full p-2 border rounded-md" multiple>
                <textarea name="additional_image_urls" id="additional_image_urls" rows="3" class="mt-2 w-full p-2 border rounded-md" placeholder="Or paste multiple image URLs, one per line"></textarea>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
const allProducts = <?php echo json_encode($all_products); ?>;
const categoriesStructured = <?php echo json_encode(array_values($categories_structured)); ?>;

document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('product-table-body');
    const modal = document.getElementById('product-modal');
    const addBtn = document.getElementById('add-product-btn');
    const closeBtn = document.getElementById('modal-close-btn');
    const form = document.getElementById('product-form');

    // --- NEW: Category dropdown elements ---
    const mainCatSelect = document.getElementById('main_cat_id');
    const subCatContainer = document.getElementById('sub-cat-container');
    const subCatSelect = document.getElementById('sub_cat_id');
    const finalCatIdInput = document.getElementById('cat_id');

    const openModal = (title, action, data = {}) => {
        form.reset();
        const variantOfSelect = document.getElementById('variant_of');
        variantOfSelect.innerHTML = '<option value="">None (This is a main product)</option>';
        allProducts.forEach(p => {
            if (data.id && p.id == data.id) return;
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = `${p.name} (ID: ${p.id})`;
            variantOfSelect.appendChild(option);
        });

        document.getElementById('modal-title').innerText = title;
        document.getElementById('form-action').value = action;
        document.getElementById('product_id').value = data.id || '';
        document.getElementById('name').value = data.name || '';
        document.getElementById('gender').value = data.gender || '';
        variantOfSelect.value = data.variant_of || '';
        document.getElementById('mrp').value = data.mrp || '';
        document.getElementById('price').value = data.price || '';
        document.getElementById('delivery_charge').value = data.delivery_charge || '0';
        document.getElementById('stock').value = data.stock || '';
        document.getElementById('sizes').value = data.sizes || '';
        document.getElementById('description').value = data.description || '';
        document.getElementById('video_url').value = data.video_url || '';
        document.getElementById('current_image').value = data.image || '';
        document.getElementById('image').required = false;

        // --- NEW: Reset and populate category dropdowns ---
        mainCatSelect.value = '';
        subCatContainer.classList.add('hidden');
        subCatSelect.innerHTML = '<option value="">Select Sub-Category</option>';
        finalCatIdInput.value = '';

        if (action === 'update' && data.cat_id) {
            let parentId = data.category_parent_id || data.cat_id;
            let subId = data.category_parent_id ? data.cat_id : '';
            mainCatSelect.value = parentId;
            mainCatSelect.dispatchEvent(new Event('change')); // Trigger change to populate sub-cats
            setTimeout(() => { subCatSelect.value = subId; }, 100); // Allow time for population
            finalCatIdInput.value = data.cat_id;
        }

        document.getElementById('main_image_url').value = '';
        document.getElementById('additional_image_urls').value = '';
        modal.classList.remove('hidden');
    };
    
    const closeModal = () => modal.classList.add('hidden');

    const fetchProducts = async () => {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></td></tr>';
        const formData = new FormData(); formData.append('action', 'fetch');
        try {
            const response = await fetch('product_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.status === 'success') {
                if (result.data.length > 0) {
                    result.data.forEach(p => { 
                        tbody.innerHTML += `<tr class="border-t hover:bg-gray-50">
                            <td class="p-3 font-mono text-xs text-gray-500">#${p.id}</td>
                            <td class="p-3 flex items-center">
                                <img src="${p.image_url}" class="h-10 w-10 mr-3 rounded-md object-cover flex-shrink-0"/> 
                                <span class="max-w-xs truncate" title="${p.name}">${p.name}</span>
                            </td>
                            <td class="p-3">${p.cat_name || 'N/A'}</td>
                            <td class="p-3">₹${p.price}</td>
                            <td class="p-3">${p.stock}</td>
                            <td class="p-3 text-right">
                                <button class="edit-btn text-blue-500 mr-2" data-id="${p.id}"><i class="fas fa-edit"></i></button>
                                <button class="delete-btn text-red-500" data-id="${p.id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`; 
                    });
                } else { 
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4">No products found.</td></tr>'; 
                }
            } else { 
                tbody.innerHTML = `<tr class="border-t"><td colspan="6" class="text-center p-4 text-red-500">${result.message}</td></tr>`; 
            }
        } catch (error) { 
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-4 text-red-500">A critical error occurred while fetching products.</td></tr>'; 
        }
    };
    
    // --- NEW: Event Listener for Main Category Selection ---
    mainCatSelect.addEventListener('change', () => {
        const selectedMainCatId = mainCatSelect.value;
        const mainCategory = categoriesStructured.find(cat => cat.id == selectedMainCatId);

        subCatSelect.innerHTML = '<option value="">Select Sub-Category</option>';
        if (mainCategory && mainCategory.sub_categories.length > 0) {
            mainCategory.sub_categories.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.name;
                subCatSelect.appendChild(option);
            });
            subCatContainer.classList.remove('hidden');
            finalCatIdInput.value = ''; // Require sub-category selection
        } else {
            subCatContainer.classList.add('hidden');
            finalCatIdInput.value = selectedMainCatId; // Use main category ID
        }
    });

    // --- NEW: Event Listener for Sub Category Selection ---
    subCatSelect.addEventListener('change', () => {
        if (subCatSelect.value) {
            finalCatIdInput.value = subCatSelect.value;
        } else {
            finalCatIdInput.value = mainCatSelect.value;
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!finalCatIdInput.value) {
            alert('Please select a category.');
            return;
        }

        const action = document.getElementById('form-action').value;
        const fileInput = document.getElementById('image');
        const urlInput = document.getElementById('main_image_url');
        const currentImage = document.getElementById('current_image').value;

        if (action === 'add' && !fileInput.files.length && urlInput.value.trim() === '') {
            alert('Please provide a main image by either uploading a file or pasting a URL.');
            return;
        }
        if (action === 'update' && !fileInput.files.length && urlInput.value.trim() === '' && currentImage.trim() === '') {
            alert('Please provide a main image by either uploading a file or pasting a URL.');
            return;
        }

        try {
            const response = await fetch('product_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') { 
                window.location.reload(); 
            }
        } catch (error) { alert('An unexpected error occurred while saving the product.'); }
    });

    tbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData(); formData.append('action', 'get_single'); formData.append('id', id);
            try {
                const response = await fetch('product_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { openModal('Edit Product', 'update', result.data); } 
                else { alert('Error: ' + result.message); }
            } catch (error) { alert('An unexpected error occurred while fetching product data.'); }
        }
        if (deleteBtn) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            const id = deleteBtn.dataset.id;
            const formData = new FormData(); formData.append('action', 'delete'); formData.append('id', id);
            try {
                const response = await fetch('product_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') { 
                    window.location.reload(); 
                }
            } catch (error) { alert('An unexpected error occurred while deleting the product.'); }
        }
    });

    addBtn.addEventListener('click', () => openModal('Add New Product', 'add'));
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    fetchProducts();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>