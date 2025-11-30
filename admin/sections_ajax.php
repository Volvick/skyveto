<?php
// File: sections_ajax.php (FINAL IMAGE BANNER VERSION)
// Location: /admin/
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    if ($action == 'fetch') {
        $sections = [];
        $result = $conn->query("
            SELECT cs.id, cs.image, cs.display_order, c.name as category_name 
            FROM category_sections cs
            JOIN categories c ON cs.category_id = c.id
            ORDER BY cs.category_id, cs.display_order ASC
        ");
        if ($result === false) { throw new Exception("Database query failed: " . $conn->error); }
        while ($row = $result->fetch_assoc()) {
            // FIX: Add the full image URL
            $row['image_url'] = get_image_url($row['image']);
            $sections[] = $row; 
        }
        $response = ['status' => 'success', 'data' => $sections];
    } 
    // ... (rest of the file remains unchanged) ...
    else if ($action == 'add' || $action == 'update') {
        $category_id = (int)$_POST['category_id'];
        $display_order = (int)$_POST['display_order'];
        $section_id = (int)($_POST['section_id'] ?? 0);
        $current_image = $_POST['current_image'] ?? '';
        $image_name = $current_image;

        if ($category_id <= 0) {
            throw new Exception("Please select a parent category.");
        }

        if (isset($_FILES['section_image']) && $_FILES['section_image']['error'] == 0) {
            $target_dir = dirname(__DIR__) . "/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
            
            $image_name = 'section_' . time() . '_' . basename($_FILES["section_image"]["name"]);
            $target_file = $target_dir . $image_name;
            if (!move_uploaded_file($_FILES["section_image"]["tmp_name"], $target_file)) {
               throw new Exception('Failed to move uploaded file.');
            }
        }

        if ($action == 'add') {
             if (empty($image_name)) throw new Exception('Banner image is required.');
            $stmt = $conn->prepare("INSERT INTO category_sections (category_id, display_order, image) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $category_id, $display_order, $image_name);
        } else { // Update action
            if ($section_id <= 0) throw new Exception('Invalid Section ID for update.');
            $stmt = $conn->prepare("UPDATE category_sections SET category_id = ?, display_order = ?, image = ? WHERE id = ?");
            $stmt->bind_param("iisi", $category_id, $display_order, $image_name, $section_id);
        }

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Section banner ' . ($action == 'add' ? 'added' : 'updated') . ' successfully.'];
        } else {
            throw new Exception('Database error: ' . $stmt->error);
        }
    }
    else if ($action == 'get_single_section') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid Section ID.');
        $stmt = $conn->prepare("SELECT id, category_id, display_order, image FROM category_sections WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $response = ['status' => 'success', 'data' => $result];
        } else {
            throw new Exception('Section not found.');
        }
    }
    else if ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID.');

        $stmt_get = $conn->prepare("SELECT image FROM category_sections WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $image_to_delete = $stmt_get->get_result()->fetch_assoc()['image'] ?? null;
        if ($image_to_delete) {
            $file_path = dirname(__DIR__) . "/uploads/" . $image_to_delete;
            if (file_exists($file_path)) { @unlink($file_path); }
        }

        $stmt = $conn->prepare("DELETE FROM category_sections WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Section banner deleted.'];
        } else {
            throw new Exception("Failed to delete section banner.");
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>