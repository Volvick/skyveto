<?php
// File: banner_ajax.php
// Location: /admin/
// REVISED: Handles saving different link types for banners.
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn || $conn->connect_error) { 
        throw new Exception("Database connection failed for API: " . ($conn->connect_error ?? 'Check config.php')); 
    }

    if ($action == 'fetch_banners') {
        $banners = [];
        $result = $conn->query("SELECT id, image FROM banners WHERE is_active = 1 ORDER BY id DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['image_url'] = BASE_URL . '/uploads/' . $row['image'];
                $banners[] = $row;
            }
        } else {
            throw new Exception("Database query failed: " . $conn->error);
        }
        $response = ['status' => 'success', 'data' => $banners];
    }
    else if ($action == 'add') {
        // --- NEW: Logic to handle different link types ---
        $link_type = $_POST['link_type'] ?? 'url';
        $link_target = '';

        if ($link_type === 'url') {
            $link_target = trim($_POST['link_target_url'] ?? '');
        } elseif ($link_type === 'category') {
            $link_target = (int)($_POST['link_target_category'] ?? 0);
        } elseif ($link_type === 'discount_range') {
            $min = (int)($_POST['link_target_discount_min'] ?? 0);
            $max = (int)($_POST['link_target_discount_max'] ?? 0);
            if ($min >= 0 && $max > $min) {
                $link_target = "$min-$max";
            }
        }

        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = dirname(__DIR__) . "/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
            $image_name = 'banner_' . time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
               throw new Exception('Failed to move uploaded file.');
            }
        } else {
            throw new Exception('Image is required.');
        }
        $stmt = $conn->prepare("INSERT INTO banners (image, link_type, link_target) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $image_name, $link_type, $link_target);
        if (!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $response = ['status' => 'success', 'message' => 'Banner added successfully.'];
    }
    else if ($action == 'delete_banner') {
        $banner_id = (int)($_POST['banner_id'] ?? 0);
        if ($banner_id > 0) {
            $stmt_get = $conn->prepare("SELECT image FROM banners WHERE id = ?");
            $stmt_get->bind_param("i", $banner_id);
            $stmt_get->execute();
            $image_to_delete = $stmt_get->get_result()->fetch_assoc()['image'] ?? null;
            if ($image_to_delete) {
                $file_path = dirname(__DIR__) . "/uploads/" . $image_to_delete;
                if (file_exists($file_path)) { @unlink($file_path); }
            }
            $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->bind_param("i", $banner_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Banner deleted successfully.'];
            } else {
                throw new Exception('Failed to delete banner from database.');
            }
        }
    }
    
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
if ($conn) $conn->close();
exit();
?>