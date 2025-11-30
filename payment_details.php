<?php
// File: payment_details.php (FULLY TRANSLATED)
// Location: /
include_once 'common/config.php';
check_login();
$user_id = get_user_id();

// Fetch existing details to pre-fill the form
$bank_details = $conn->query("SELECT * FROM user_payment_details WHERE user_id = $user_id AND type = 'bank'")->fetch_assoc();
$upi_details = $conn->query("SELECT * FROM user_payment_details WHERE user_id = $user_id AND type = 'upi'")->fetch_assoc();

include_once 'common/header.php';
?>
<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i><?php echo __t('payment_back_to_account'); ?></a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo __t('payment_title'); ?></h1>
    <div id="form-message" class="mb-4"></div>

    <!-- Bank Details Form -->
    <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
        <h2 class="text-lg font-semibold mb-3"><?php echo __t('payment_bank_details_title'); ?></h2>
        <form id="bank-form" class="space-y-4">
            <input type="hidden" name="action" value="save_payment_details">
            <input type="hidden" name="type" value="bank">
            <div>
                <label class="text-sm"><?php echo __t('payment_account_holder'); ?></label>
                <input type="text" name="account_holder_name" value="<?php echo htmlspecialchars($bank_details['account_holder_name'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="text-sm"><?php echo __t('payment_account_number'); ?></label>
                <input type="text" name="account_number" value="<?php echo htmlspecialchars($bank_details['account_number'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="text-sm"><?php echo __t('payment_ifsc_code'); ?></label>
                <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($bank_details['ifsc_code'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md">
            </div>
            <div>
                <label class="text-sm"><?php echo __t('payment_bank_name'); ?></label>
                <input type="text" name="bank_name" value="<?php echo htmlspecialchars($bank_details['bank_name'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md" placeholder="<?php echo __t('payment_bank_name_placeholder'); ?>">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600"><?php echo __t('payment_save_bank'); ?></button>
        </form>
    </div>

    <!-- UPI Details Form -->
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <h2 class="text-lg font-semibold mb-3"><?php echo __t('payment_upi_details_title'); ?></h2>
        <form id="upi-form" class="space-y-4">
            <input type="hidden" name="action" value="save_payment_details">
            <input type="hidden" name="type" value="upi">
            <div>
                <label class="text-sm"><?php echo __t('payment_upi_id'); ?></label>
                <input type="text" name="upi_id" value="<?php echo htmlspecialchars($upi_details['upi_id'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md" placeholder="<?php echo __t('payment_upi_id_placeholder'); ?>">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600"><?php echo __t('payment_save_upi'); ?></button>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const handleFormSubmit = async (form) => {
        const msgEl = document.getElementById('form-message');
        msgEl.innerHTML = `<div class="p-3 rounded-md text-center bg-yellow-100 text-yellow-800"><?php echo __t('payment_ajax_saving'); ?></div>`;
        window.scrollTo(0, 0);

        try {
            const response = await fetch('payment_details_ajax.php', { method: 'POST', body: new FormData(form) });
            const result = await response.json();
            if (result.status === 'success') {
                msgEl.innerHTML = `<div class="p-3 rounded-md text-center bg-green-100 text-green-800">${result.message}</div>`;
            } else {
                msgEl.innerHTML = `<div class="p-3 rounded-md text-center bg-red-100 text-red-800">${result.message}</div>`;
            }
        } catch (error) {
            msgEl.innerHTML = `<div class="p-3 rounded-md text-center bg-red-100 text-red-800"><?php echo __t('payment_ajax_unexpected_error'); ?></div>`;
        }
    };

    document.getElementById('bank-form').addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(e.target); });
    document.getElementById('upi-form').addEventListener('submit', (e) => { e.preventDefault(); handleFormSubmit(e.target); });
});
</script>
<?php
include_once 'common/bottom.php';
?>