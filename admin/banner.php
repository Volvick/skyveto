<?php
// File: banner.php
// Location: /admin/
// REVISED: Added advanced banner linking options.
include_once 'common/header.php';

// Fetch categories for the dropdown
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) { while ($row = $cat_result->fetch_assoc()) { $categories[] = $row; } }
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Manage Banners</h1>
    <button id="add-banner-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <i class="fas fa-plus"></i> Add New Banner
    </button>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div id="banner-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Banners will be loaded here by JavaScript -->
    </div>
</div>

<!-- Add/Edit Banner Modal -->
<div id="banner-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Add New Banner</h2>
        <form id="banner-form" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="banner_id" id="banner_id">
            <input type="hidden" name="current_image" id="current_image">

            <div>
                <label for="image" class="block mb-1">Banner Image (Recommended: 1080x480)</label>
                <input type="file" id="image" name="image" class="w-full p-2 border rounded-md">
            </div>

            <div>
                <label for="link_type" class="block mb-1">Link Type</label>
                <select name="link_type" id="link_type" class="w-full p-2 border rounded-md">
                    <option value="url">Custom URL (e.g., to a single product)</option>
                    <option value="category">Link to a Category</option>
                    <option value="discount_range">Link to a Discount Offer</option>
                </select>
            </div>

            <!-- Custom URL Input -->
            <div id="link-type-url" class="link-type-option">
                <label for="link_target_url" class="block mb-1">Link URL</label>
                <input type="text" id="link_target_url" name="link_target_url" placeholder="e.g., product_detail.php?id=5" class="w-full p-2 border rounded-md">
            </div>

            <!-- Category Select Input -->
            <div id="link-type-category" class="link-type-option hidden">
                <label for="link_target_category" class="block mb-1">Select Category</label>
                <select name="link_target_category" id="link_target_category" class="w-full p-2 border rounded-md">
                    <option value="">-- Select a Category --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Discount Range Inputs -->
            <div id="link-type-discount_range" class="link-type-option hidden">
                <label class="block mb-1">Discount Range (%)</label>
                <div class="flex items-center space-x-2">
                    <input type="number" name="link_target_discount_min" id="link_target_discount_min" class="w-full p-2 border rounded-md" placeholder="Min %">
                    <span class="font-semibold">to</span>
                    <input type="number" name="link_target_discount_max" id="link_target_discount_max" class="w-full p-2 border rounded-md" placeholder="Max %">
                </div>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Save Banner</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('add-banner-btn');
    const modal = document.getElementById('banner-modal');
    const closeBtn = document.getElementById('modal-close-btn');
    const form = document.getElementById('banner-form');
    const bannerList = document.getElementById('banner-list');
    const linkTypeSelect = document.getElementById('link_type');

    const openModal = () => { 
        form.reset(); 
        document.getElementById('image').required = true;
        document.getElementById('form-action').value = 'add';
        document.getElementById('banner_id').value = '';
        document.getElementById('current_image').value = '';
        document.getElementById('modal-title').innerText = 'Add New Banner';
        updateLinkInputs();
        modal.classList.remove('hidden'); 
    };
    const closeModal = () => modal.classList.add('hidden');

    const updateLinkInputs = () => {
        document.querySelectorAll('.link-type-option').forEach(div => div.classList.add('hidden'));
        const selectedType = document.getElementById('link_type').value;
        document.getElementById(`link-type-${selectedType}`).classList.remove('hidden');
    };
    
    linkTypeSelect.addEventListener('change', updateLinkInputs);

    const fetchBanners = async () => {
        bannerList.innerHTML = '<div class="col-span-full text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></div>';
        const formData = new FormData();
        formData.append('action', 'fetch_banners');
        try {
            const response = await fetch('banner_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            bannerList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(banner => {
                    bannerList.innerHTML += `
                        <div class="relative group border rounded-lg overflow-hidden">
                            <img src="${banner.image_url}" class="w-full h-auto object-contain">
                            <div class="absolute inset-0 bg-black bg-opacity-50 p-2 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="delete-banner-btn text-white text-lg" data-id="${banner.id}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>`;
                });
            } else {
                bannerList.innerHTML = '<p class="col-span-full text-center text-gray-500 p-4">No banners found. Click "Add New Banner" to start.</p>';
            }
        } catch (error) {
            bannerList.innerHTML = '<p class="col-span-full text-center text-red-500 p-4">Failed to load banners. Please check server logs.</p>';
        }
    };
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('banner_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') {
                closeModal();
                fetchBanners();
            }
        } catch (error) {
            alert('An unexpected error occurred.');
        }
    });

    bannerList.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-banner-btn');
        if (deleteBtn) {
            if (!confirm('Are you sure you want to delete this banner?')) return;
            const id = deleteBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete_banner');
            formData.append('banner_id', id);
            try {
                const response = await fetch('banner_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    fetchBanners();
                }
            } catch (error) {
                alert('Failed to delete banner.');
            }
        }
    });

    addBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    fetchBanners();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>