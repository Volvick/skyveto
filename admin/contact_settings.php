<?php
// File: contact_settings.php (REVISED to include Facebook, WhatsApp, Email)
// Location: /admin/
include_once 'common/header.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chat_link = $_POST['instagram_chat_link'];
    $chat_name = $_POST['instagram_chat_name'];
    $helper_link = $_POST['instagram_helper_link'];
    $helper_name = $_POST['instagram_helper_name'];
    $phone = $_POST['phone_number'];
    // NEW: Get new fields from form
    $facebook_link = $_POST['facebook_link'];
    $facebook_name = $_POST['facebook_name'];
    $whatsapp_number = $_POST['whatsapp_number'];
    $email_address = $_POST['email_address'];

    $stmt = $conn->prepare("UPDATE contact_details SET 
        instagram_chat_link = ?, instagram_chat_name = ?, 
        instagram_helper_link = ?, instagram_helper_name = ?, 
        phone_number = ?, 
        facebook_link = ?, facebook_name = ?,
        whatsapp_number = ?, email_address = ?
        WHERE id = 1");
    // NEW: Add new variables to bind_param
    $stmt->bind_param("sssssssss", 
        $chat_link, $chat_name, 
        $helper_link, $helper_name, 
        $phone, 
        $facebook_link, $facebook_name,
        $whatsapp_number, $email_address
    );

    if ($stmt->execute()) {
        $message = 'Contact settings updated successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error updating settings.';
        $message_type = 'error';
    }
}

// Fetch current settings
$contact_details = $conn->query("SELECT * FROM contact_details WHERE id = 1")->fetch_assoc();
?>

<h1 class="text-2xl font-bold mb-6">Contact & Help Center Settings</h1>

<div class="bg-white p-6 rounded-xl shadow-lg max-w-2xl mx-auto">
    <?php if ($message): ?>
    <div class="p-3 rounded-md mb-4 text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Instagram Links</h3>
        <div>
            <label class="block font-medium">Instagram Chat Link (Skyveto Chat)</label>
            <input type="url" name="instagram_chat_link" value="<?php echo htmlspecialchars($contact_details['instagram_chat_link'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        <div>
            <label class="block font-medium">Instagram Chat Display Name</label>
            <input type="text" name="instagram_chat_name" value="<?php echo htmlspecialchars($contact_details['instagram_chat_name'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        <hr/>
        <div>
            <label class="block font-medium">Instagram Helper Link (Skyveto Helper)</label>
            <input type="url" name="instagram_helper_link" value="<?php echo htmlspecialchars($contact_details['instagram_helper_link'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        <div>
            <label class="block font-medium">Instagram Helper Display Name</label>
            <input type="text" name="instagram_helper_name" value="<?php echo htmlspecialchars($contact_details['instagram_helper_name'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        
        <!-- NEW: Facebook Section -->
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 pt-4">Facebook Link</h3>
        <div>
            <label class="block font-medium">Facebook Page Link</label>
            <input type="url" name="facebook_link" value="<?php echo htmlspecialchars($contact_details['facebook_link'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        <div>
            <label class="block font-medium">Facebook Display Name</label>
            <input type="text" name="facebook_name" value="<?php echo htmlspecialchars($contact_details['facebook_name'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>

        <!-- NEW: Other Contacts Section -->
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2 pt-4">Other Contacts</h3>
        <div>
            <label class="block font-medium">Phone Number</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($contact_details['phone_number'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        <div>
            <label class="block font-medium">WhatsApp Number (with country code)</label>
            <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($contact_details['whatsapp_number'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1" placeholder="e.g., 918296570985">
        </div>
         <div>
            <label class="block font-medium">Email Address</label>
            <input type="email" name="email_address" value="<?php echo htmlspecialchars($contact_details['email_address'] ?? ''); ?>" class="w-full p-2 border rounded-md mt-1">
        </div>
        
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 mt-6">Save Changes</button>
    </form>
</div>

<?php include_once 'common/bottom.php'; ?>