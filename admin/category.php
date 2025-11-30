<?php
// File: category.php
// Location: /admin/
include_once 'common/header.php'; 

// Fetch main categories for the parent dropdown
$main_categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC");
if ($cat_result) { while ($row = $cat_result->fetch_assoc()) { $main_categories[] = $row; } }
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Manage Categories</h1>
    <button id="add-category-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <i class="fas fa-plus"></i> Add Category
    </button>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <table class="w-full">
        <thead><tr class="border-b"><th class="text-left p-3">Image</th><th class="text-left p-3">Name</th><th class="text-left p-3">Parent Category</th><th class="text-right p-3">Actions</th></tr></thead>
        <tbody id="category-table-body"></tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="category-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Add New Category</h2>
        <form id="category-form">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="category_id" id="category_id">
            <input type="hidden" name="current_image" id="current_image">
            <div class="mb-4">
                <label for="name" class="block mb-1">Category Name</label>
                <input type="text" id="name" name="name" required class="w-full p-2 border rounded-md">
            </div>
            <div class="mb-4">
                <label for="image" class="block mb-1">Category Image</label>
                <input type="file" id="image" name="image" accept="image/*" class="w-full p-2 border rounded-md">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep the current image when editing.</p>
            </div>
            <div class="mb-4">
                <label for="parent_id" class="block mb-1">Parent Category</label>
                <select name="parent_id" id="parent_id" class="w-full p-2 border rounded-md">
                    <option value="">None (This is a main category)</option>
                    <?php foreach ($main_categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('category-table-body');
    const modal = document.getElementById('category-modal');
    const addBtn = document.getElementById('add-category-btn');
    const closeBtn = document.getElementById('modal-close-btn');
    const form = document.getElementById('category-form');

    const openModalForAdd = () => {
        form.reset();
        document.getElementById('modal-title').innerText = 'Add New Category';
        document.getElementById('form-action').value = 'add';
        document.getElementById('category_id').value = '';
        document.getElementById('current_image').value = '';
        document.getElementById('parent_id').value = '';
        document.getElementById('image').required = true;
        modal.classList.remove('hidden');
    };

    const openModalForEdit = (data) => {
        form.reset();
        document.getElementById('modal-title').innerText = 'Edit Category';
        document.getElementById('form-action').value = 'update';
        document.getElementById('category_id').value = data.id;
        document.getElementById('name').value = data.name;
        document.getElementById('current_image').value = data.image;
        document.getElementById('parent_id').value = data.parent_id || '';
        document.getElementById('image').required = false;
        modal.classList.remove('hidden');
    };
    
    const closeModal = () => modal.classList.add('hidden');

    const fetchCategories = async () => {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></td></tr>';
        const formData = new FormData(); formData.append('action', 'fetch');
        try {
            const response = await fetch('category_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(cat => {
                    // FIX: Use the full image_url from the AJAX response
                    tbody.innerHTML += `<tr class="border-t hover:bg-gray-50">
                        <td class="p-3"><img src="${cat.image_url}" class="h-12 w-12 object-cover rounded-md"></td>
                        <td class="p-3 font-medium">${cat.name}</td>
                        <td class="p-3 text-sm text-gray-600">${cat.parent_name || '—'}</td>
                        <td class="p-3 text-right space-x-2">
                            <button class="edit-btn text-blue-500" data-id="${cat.id}"><i class="fas fa-edit"></i></button>
                            <button class="delete-btn text-red-500" data-id="${cat.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">No categories found.</td></tr>';
            }
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Error loading categories.</td></tr>';
        }
    };
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        try {
            const response = await fetch('category_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') { 
                window.location.reload(); 
            } else {
                submitButton.disabled = false;
                submitButton.textContent = 'Save';
            }
        } catch (error) {
            alert('An unexpected error occurred.');
            submitButton.disabled = false;
            submitButton.textContent = 'Save';
        }
    });

    tbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData(); formData.append('action', 'get_single'); formData.append('id', id);
            try {
                const response = await fetch('category_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { openModalForEdit(result.data); } 
                else { alert('Error: ' + result.message); }
            } catch (error) {
                alert('An unexpected error occurred.');
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (!confirm('Are you sure you want to delete this category?')) return;
            const id = deleteBtn.dataset.id;
            const formData = new FormData(); formData.append('action', 'delete'); formData.append('id', id);
            try {
                const response = await fetch('category_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') { fetchCategories(); }
            } catch (error) {
                alert('An unexpected error occurred.');
            }
        }
    });

    addBtn.addEventListener('click', openModalForAdd);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    fetchCategories();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>