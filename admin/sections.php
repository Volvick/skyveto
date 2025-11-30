<?php
// File: sections.php (FINAL CORRECTED VERSION)
// Location: /admin/
include_once 'common/header.php'; 

// Fetch main categories for the dropdown
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories WHERE parent_id IS NULL OR parent_id = 0 ORDER BY name ASC");
if ($cat_result) { while ($row = $cat_result->fetch_assoc()) { $categories[] = $row; } }
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Manage Category Sections</h1>
    <button id="add-section-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <i class="fas fa-plus"></i> Add Section Banner
    </button>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <table class="w-full">
        <thead><tr class="border-b"><th class="text-left p-3">Banner Image</th><th class="text-left p-3">Parent Category</th><th class="text-left p-3">Display Position</th><th class="text-right p-3">Actions</th></tr></thead>
        <tbody id="section-table-body"></tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="section-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Add New Section Banner</h2>
        <form id="section-form" enctype="multipart/form-data">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="section_id" id="section_id">
            <input type="hidden" name="current_image" id="current_image">
            <div class="mb-4">
                <label for="section_image" class="block mb-1">Banner Image (PNG recommended)</label>
                <input type="file" id="section_image" name="section_image" class="w-full p-2 border rounded-md">
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image when editing.</p>
            </div>
            <div class="mb-4">
                <label for="category_id" class="block mb-1">Assign to Parent Category</label>
                <select name="category_id" id="category_id" required class="w-full p-2 border rounded-md">
                    <option value="">Select a Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="display_order" class="block mb-1">Display After Position</label>
                <input type="number" id="display_order" name="display_order" required class="w-full p-2 border rounded-md" value="0">
                <p class="text-xs text-gray-500 mt-1">Show this banner after this many sub-categories.</p>
            </div>
            <div class="flex justify-end space-x-4 mt-4">
                <button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Save Banner</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('section-table-body');
    const modal = document.getElementById('section-modal');
    const addBtn = document.getElementById('add-section-btn');
    const closeBtn = document.getElementById('modal-close-btn');
    const form = document.getElementById('section-form');
    const imageInput = document.getElementById('section_image');

    const openModalForAdd = () => {
        form.reset();
        document.getElementById('modal-title').innerText = 'Add New Section Banner';
        document.getElementById('form-action').value = 'add';
        document.getElementById('section_id').value = '';
        document.getElementById('current_image').value = '';
        imageInput.required = true;
        modal.classList.remove('hidden');
    };

    const openModalForEdit = (data) => {
        form.reset();
        document.getElementById('modal-title').innerText = 'Edit Section Banner';
        document.getElementById('form-action').value = 'update';
        document.getElementById('section_id').value = data.id;
        document.getElementById('category_id').value = data.category_id;
        document.getElementById('display_order').value = data.display_order;
        document.getElementById('current_image').value = data.image;
        imageInput.required = false;
        modal.classList.remove('hidden');
    };
    
    const closeModal = () => modal.classList.add('hidden');

    const fetchSections = async () => {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></td></tr>';
        try {
            const formData = new FormData(); formData.append('action', 'fetch');
            const response = await fetch('sections_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(sec => {
                    // FIX: Use the full image_url from the AJAX response
                    tbody.innerHTML += `<tr class="border-t hover:bg-gray-50">
                        <td class="p-3"><img src="${sec.image_url}" class="h-12 w-auto rounded-md bg-gray-100"></td>
                        <td class="p-3 text-gray-600">${sec.category_name}</td>
                        <td class="p-3">${sec.display_order}</td>
                        <td class="p-3 text-right">
                            <button class="edit-btn text-blue-500 mr-4" data-id="${sec.id}"><i class="fas fa-edit"></i></button>
                            <button class="delete-btn text-red-500" data-id="${sec.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4">No sections found.</td></tr>';
            }
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Failed to load sections.</td></tr>';
        }
    };
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('sections_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') {
                closeModal();
                fetchSections();
            }
        } catch (error) {
            alert('An unexpected error occurred.');
        }
    });

    tbody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single_section');
            formData.append('id', id);
            try {
                const response = await fetch('sections_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    openModalForEdit(result.data);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An unexpected error occurred while fetching section data.');
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (!confirm('Are you sure you want to delete this section banner?')) return;
            const id = deleteBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            try {
                const response = await fetch('sections_ajax.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    fetchSections();
                }
            } catch (error) {
                alert('Failed to delete section.');
            }
        }
    });

    if (addBtn) {
        addBtn.addEventListener('click', openModalForAdd);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => { 
            if (e.target === modal) closeModal(); 
        });
    }
    
    fetchSections();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>