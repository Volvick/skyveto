<?php
// File: edit_profile.php (NEW)
// Location: /
include_once 'common/config.php';
check_login();
$user_id = get_user_id();
$message = '';
$message_type = '';

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $current_pic = $_POST['current_pic'];
    $profile_pic_name = $current_pic;

    // Image Upload Logic
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "uploads/";
        $profile_pic_name = 'user_' . $user_id . '_' . time() . '_' . basename($_FILES["profile_pic"]["name"]);
        $target_file = $target_dir . $profile_pic_name;
        if (!move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
           $message = 'Failed to upload profile picture.'; $message_type = 'error'; $profile_pic_name = $current_pic;
        }
    }

    if (empty($message)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, email = ?, gender = ?, profile_pic = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $phone, $email, $gender, $profile_pic_name, $user_id);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $message = "Profile updated successfully!";
            $message_type = 'success';
        } else {
            $message = "Failed to update profile. Email or phone may already be in use.";
            $message_type = 'error';
        }
    }
}

// Fetch user details for the form
$stmt = $conn->prepare("SELECT name, phone, email, gender, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include_once 'common/header.php';
?>
<main class="p-4">
    <a href="profile.php" class="text-blue-600 font-semibold mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Back to Account</a>
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Edit Profile</h1>
    
    <?php if ($message): ?>
    <div class="mb-4 p-3 rounded-md text-center <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded-lg shadow-sm space-y-4">
        <div class="text-center">
             <img id="pic-preview" src="uploads/<?php echo htmlspecialchars($user['profile_pic'] ?? 'default_avatar.png'); ?>" class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 mx-auto">
             <label for="profile_pic_input" class="cursor-pointer text-sm text-blue-600 font-semibold mt-2 block">Change Picture</label>
             <input type="file" name="profile_pic" id="profile_pic_input" class="hidden" accept="image/*">
             <input type="hidden" name="current_pic" value="<?php echo htmlspecialchars($user['profile_pic'] ?? ''); ?>">
        </div>
        <div>
            <label class="text-sm font-medium">Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md bg-gray-50">
        </div>
        <div>
            <label class="text-sm font-medium">Phone</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md bg-gray-50">
        </div>
        <div>
            <label class="text-sm font-medium">Email</label>
            <!-- FIX: Use null coalescing operator to prevent error on null email -->
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required class="mt-1 w-full p-2 border rounded-md bg-gray-50">
        </div>
        <div>
            <label class="text-sm font-medium">Gender</label>
            <select name="gender" class="mt-1 w-full p-2 border rounded-md bg-gray-50">
                <option value="Male" <?php echo (($user['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo (($user['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo (($user['gender'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-600">Save Changes</button>
    </form>
</main>
<script>
document.getElementById('profile_pic_input').onchange = evt => {
    const [file] = evt.target.files;
    if (file) {
        document.getElementById('pic-preview').src = URL.createObjectURL(file);
    }
}
</script>
<?php
include_once 'common/bottom.php';
?>