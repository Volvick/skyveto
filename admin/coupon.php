<?php
// File: coupon.php (REVISED with Usage Limit Per User and better JS error handling)
// Location: /admin/
include_once 'common/header.php'; 
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Manage Coupons</h1>
    <button id="add-coupon-btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <i class="fas fa-plus"></i> Add New Coupon
    </button>
</div>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="p-3 text-left">Code</th>
                    <th class="p-3 text-left">Discount</th>
                    <th class="p-3 text-left">Min Purchase</th>
                    <th class="p-3 text-left">Expiry Date</th>
                    <th class="p-3 text-left">For New Users?</th>
                    <th class="p-3 text-left">Limit Per User</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="coupon-table-body">
                <!-- Data will be loaded by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="coupon-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 id="modal-title" class="text-xl font-bold mb-4">Add New Coupon</h2>
        <form id="coupon-form" class="space-y-4">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="coupon_id" id="coupon_id">
            <div>
                <label for="coupon_code" class="block mb-1">Coupon Code</label>
                <input type="text" name="coupon_code" id="coupon_code" required class="w-full p-2 border rounded-md" placeholder="e.g., SALE50">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="discount_type" class="block mb-1">Discount Type</label>
                    <select name="discount_type" id="discount_type" required class="w-full p-2 border rounded-md">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div>
                    <label for="discount_value" class="block mb-1">Value (₹ or %)</label>
                    <input type="number" step="0.01" name="discount_value" id="discount_value" required class="w-full p-2 border rounded-md" placeholder="e.g., 50">
                </div>
            </div>
            <div>
                <label for="min_purchase" class="block mb-1">Minimum Purchase (₹)</label>
                <input type="number" step="0.01" name="min_purchase" id="min_purchase" required class="w-full p-2 border rounded-md" placeholder="e.g., 500">
            </div>
            
            <div>
                <label for="expiry_date" class="block mb-1">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" id="expiry_date" class="w-full p-2 border rounded-md">
            </div>

            <div>
                <label for="usage_limit_per_user" class="block mb-1">Usage Limit Per User</label>
                <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" required class="w-full p-2 border rounded-md" value="1" min="1">
                <p class="text-xs text-gray-500 mt-1">How many times a single user can use this coupon.</p>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_for_new_user" id="is_for_new_user" value="1" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="is_for_new_user" class="ml-2 block text-sm text-gray-900">This coupon is for first-time users only.</label>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="button" id="modal-close-btn" class="bg-gray-300 px-4 py-2 rounded-md">Cancel</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Save Coupon</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('coupon-table-body');
    const modal = document.getElementById('coupon-modal');
    const addBtn = document.getElementById('add-coupon-btn');
    const closeBtn = document.getElementById('modal-close-btn');
    const form = document.getElementById('coupon-form');

    const openModal = () => { form.reset(); document.getElementById('usage_limit_per_user').value = '1'; modal.classList.remove('hidden'); };
    const closeModal = () => modal.classList.add('hidden');

    const fetchCoupons = async () => {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center p-8"><div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-500 mx-auto"></div></td></tr>';
        try {
            const formData = new FormData();
            formData.append('action', 'fetch');
            const response = await fetch('coupon_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            tbody.innerHTML = '';
            if (result.status === 'success') {
                if (result.data.length > 0) {
                    result.data.forEach(c => {
                        const expiryDate = c.expiry_date ? new Date(c.expiry_date).toLocaleDateString('en-CA') : 'N/A';
                        const newUser = c.is_for_new_user == 1 ? '<span class="text-green-600 font-semibold">Yes</span>' : 'No';

                        tbody.innerHTML += `<tr class="border-t">
                            <td class="p-3 font-mono text-blue-600">${c.coupon_code}</td>
                            <td class="p-3">${c.discount_type === 'percentage' ? c.discount_value + '%' : '₹' + c.discount_value}</td>
                            <td class="p-3">₹${c.min_purchase}</td>
                            <td class="p-3">${expiryDate}</td>
                            <td class="p-3">${newUser}</td>
                            <td class="p-3 font-semibold text-center">${c.usage_limit_per_user}</td>
                            <td class="p-3 text-right">
                                <button class="delete-btn text-red-500" data-id="${c.id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4">No coupons found.</td></tr>';
                }
            } else {
                // --- ROBUSTNESS FIX: Display the error from the backend ---
                tbody.innerHTML = `<tr><td colspan="7" class="text-center p-4 text-red-500 font-semibold">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            // --- ROBUSTNESS FIX: Catch network or JSON parsing errors ---
            tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-red-500">Failed to load data. Please check server logs or database connection.</td></tr>';
        }
    };
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const response = await fetch('coupon_ajax.php', { method: 'POST', body: new FormData(form) });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            closeModal();
            fetchCoupons();
        }
    });

    tbody.addEventListener('click', async (e) => {
        if (e.target.closest('.delete-btn')) {
            if (!confirm('Are you sure you want to delete this coupon?')) return;
            const id = e.target.closest('.delete-btn').dataset.id;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('coupon_id', id);
            const response = await fetch('coupon_ajax.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') fetchCoupons();
        }
    });

    addBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    fetchCoupons();
});
</script>

<?php 
include_once 'common/bottom.php'; 
?>