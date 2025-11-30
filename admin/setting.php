<?php
// File: setting.php (REVISED WITH LOGO & FAVICON UPLOAD)
// Location: /admin/
include_once __DIR__ . '/common/header.php';

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- LOGO UPLOAD LOGIC ---
    if (isset($_POST['action']) && $_POST['action'] == 'update_logo') {
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] == 0) {
            $target_dir = ROOT_PATH . "/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
            
            $image_name = 'logo_' . time() . '_' . basename($_FILES["logo_image"]["name"]);
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES["logo_image"]["tmp_name"], $target_file)) {
                $stmt_logo = $conn->prepare("UPDATE settings SET logo_image = ? WHERE id = 1");
                $stmt_logo->bind_param("s", $image_name);
                if ($stmt_logo->execute()) {
                    $message = 'Logo updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database error: Could not save new logo.';
                    $message_type = 'error';
                }
            } else {
               $message = 'Failed to upload the new logo file.';
               $message_type = 'error';
            }
        } else {
            $message = 'Please choose a file to upload for the logo.';
            $message_type = 'error';
        }
    }
    // --- NEW: FAVICON UPLOAD LOGIC ---
    elseif (isset($_POST['action']) && $_POST['action'] == 'update_favicon') {
        if (isset($_FILES['favicon_image']) && $_FILES['favicon_image']['error'] == 0) {
            $target_dir = ROOT_PATH . "/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
            
            $image_name = 'favicon_' . time() . '_' . basename($_FILES["favicon_image"]["name"]);
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES["favicon_image"]["tmp_name"], $target_file)) {
                $stmt_favicon = $conn->prepare("UPDATE settings SET favicon_image = ? WHERE id = 1");
                $stmt_favicon->bind_param("s", $image_name);
                if ($stmt_favicon->execute()) {
                    $message = 'Favicon updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database error: Could not save new favicon.';
                    $message_type = 'error';
                }
            } else {
               $message = 'Failed to upload the new favicon file.';
               $message_type = 'error';
            }
        } else {
            $message = 'Please choose a file to upload for the favicon.';
            $message_type = 'error';
        }
    }
    // --- ADMIN DETAILS UPDATE LOGIC ---
    elseif (isset($_POST['action']) && $_POST['action'] == 'update_admin') {
        $admin_id = $_SESSION['admin_id'];
        $username = $_POST['username'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT username, password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $admin['password'])) {
            $update_fields = ['username = ?']; $params = [$username]; $types = 's';
            if (!empty($new_password)) {
                $update_fields[] = 'password = ?';
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                $types .= 's';
            }
            $params[] = $admin_id; $types .= 'i';
            $sql = "UPDATE admin SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $update_stmt = $conn->prepare($sql);
            $update_stmt->bind_param($types, ...$params);

            if ($update_stmt->execute()) {
                $_SESSION['admin_username'] = $username;
                $message = 'Admin settings updated successfully!'; $message_type = 'success';
            } else {
                $message = 'Error updating settings. Username might be taken.'; $message_type = 'error';
            }
        } else {
            $message = 'Incorrect current password.'; $message_type = 'error';
        }
    }
}

// Fetch current settings to display logo and favicon
$settings = $conn->query("SELECT logo_image, favicon_image FROM settings WHERE id = 1")->fetch_assoc();
$logo_filename = $settings['logo_image'] ?? 'logo.png';
$logo_path = ($logo_filename === 'logo.png') ? BASE_URL . '/assets/logo.png' : BASE_URL . '/uploads/' . $logo_filename;
$favicon_filename = $settings['favicon_image'] ?? 'favicon.png';
$favicon_path = BASE_URL . '/uploads/' . $favicon_filename;
?>

<h1 class="text-2xl font-bold mb-6">General Settings</h1>

<?php if ($message): ?>
<div class="p-3 rounded-md mb-4 text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Settings Column 1 -->
    <div>
        <!-- Logo Settings -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold mb-4">Website Logo</h2>
            <div class="text-center mb-4 p-4 border rounded-md bg-gray-50">
                <p class="text-sm mb-2">Current Logo:</p>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" class="h-12 mx-auto">
            </div>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update_logo">
                <div>
                    <label for="logo_image" class="block font-medium">Upload New Logo</label>
                    <input type="file" id="logo_image" name="logo_image" required class="w-full p-2 border rounded-md mt-1">
                    <p class="text-xs text-gray-500 mt-1">Recommended: Transparent PNG, max height 40px.</p>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Upload Logo</button>
            </form>
        </div>

        <!-- NEW: Favicon Settings -->
        <div class="bg-white p-6 rounded-xl shadow-lg mt-6">
            <h2 class="text-xl font-bold mb-4">Website Favicon</h2>
            <div class="text-center mb-4 p-4 border rounded-md bg-gray-50">
                <p class="text-sm mb-2">Current Favicon:</p>
                <img src="<?php echo htmlspecialchars($favicon_path); ?>" class="h-8 w-8 mx-auto">
            </div>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="update_favicon">
                <div>
                    <label for="favicon_image" class="block font-medium">Upload New Favicon (PNG, ICO)</label>
                    <input type="file" id="favicon_image" name="favicon_image" required class="w-full p-2 border rounded-md mt-1" accept="image/png, image/x-icon, image/vnd.microsoft.icon">
                    <p class="text-xs text-gray-500 mt-1">Recommended: 32x32 or 16x16 PNG.</p>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Upload Favicon</button>
            </form>
        </div>
    </div>

    <!-- Admin Settings -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-bold mb-4">Admin Credentials</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_admin">
            <div>
                <label for="username" class="block font-medium">Admin Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['admin_username']); ?>" required class="w-full p-2 border rounded-md mt-1">
            </div>
            <div>
                <label for="new_password" class="block font-medium">New Password (optional)</label>
                <input type="password" id="new_password" name="new_password" class="w-full p-2 border rounded-md mt-1" placeholder="Leave blank to keep current">
            </div>
            <hr/>
            <div>
                <label for="current_password" class="block font-medium">Current Password</label>
                <input type="password" id="current_password" name="current_password" required class="w-full p-2 border rounded-md mt-1" placeholder="Enter current password to save">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Save Admin Changes</button>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/common/bottom.php'; ?>