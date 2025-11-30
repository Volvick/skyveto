<?php
// File: category_ajax.php
// Location: /admin/
// FIXED: Removed references to non-existent 'section_interval' column.
// REVISED: Added logic to fetch parent or sub-categories.
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn) { throw new Exception("Database connection failed."); }

    if ($action == 'fetch') {
        $fetch_type = $_POST['fetch_type'] ?? 'all';
        $where_clause = '';
        if ($fetch_type === 'parent') {
            $where_clause = 'WHERE c.parent_id IS NULL OR c.parent_id = 0';
        } elseif ($fetch_type === 'sub') {
            $where_clause = 'WHERE c.parent_id IS NOT NULL AND c.parent_id != 0';
        }

        $categories = [];
        $sql = "SELECT c.id, c.name, c.image, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id $where_clause ORDER BY c.id DESC";
        $result = $conn->query($sql);

        if (!$result) { throw new Exception("Database query failed: " . $conn->error); }
        while ($row = $result->fetch_assoc()) { 
            $row['image_url'] = get_image_url($row['image']);
            $categories[] = $row; 
        }
        $response = ['status' => 'success', 'data' => $categories];
    }
    else if ($action == 'add' || $action == 'update') {
        $name = trim($_POST['name']);
        if (empty($name)) throw new Exception('Category name is required.');
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $image_name = $_POST['current_image'] ?? '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = dirname(__DIR__) . "/uploads/";
            if (!is_dir($target_dir) && !mkdir($target_dir, 0755, true)) {
                throw new Exception('Failed to create uploads directory.');
            }
            $image_name = time() . '_' . basename($_FILES["image"]["name"]);
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name)) {
               throw new Exception('Failed to move uploaded file.');
            }
        }

        if ($action == 'add') {
            // FIX: Removed section_interval from query and bindings
            $stmt = $conn->prepare("INSERT INTO categories (name, image, parent_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $image_name, $parent_id);
        } else { 
            if ($cat_id <= 0) throw new Exception('Invalid Category ID for update.');
            // FIX: Removed section_interval from query and bindings
            $stmt = $conn->prepare("UPDATE categories SET name = ?, image = ?, parent_id = ? WHERE id = ?");
            $stmt->bind_param("ssii", $name, $image_name, $parent_id, $cat_id);
        }
        if (!$stmt->execute()) throw new Exception('Database error: ' . $stmt->error);
        $response = ['status' => 'success', 'message' => 'Category ' . ($action == 'add' ? 'added' : 'updated') . ' successfully.'];
    }
    else if ($action == 'get_single') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID.');
        // FIX: Removed section_interval from query
        $stmt = $conn->prepare("SELECT id, name, image, parent_id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) { $response = ['status' => 'success', 'data' => $result]; } 
        else { throw new Exception('Category not found.'); }
    }
    else if ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID.');
        
        $conn->begin_transaction();
        try {
            // Find image to delete
            $stmt_get = $conn->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $image_to_delete = $stmt_get->get_result()->fetch_assoc()['image'] ?? null;
            if ($image_to_delete) {
                $file_path = dirname(__DIR__) . "/uploads/" . $image_to_delete;
                if (file_exists($file_path)) { unlink($file_path); }
            }

            // Also delete sub-categories if this is a parent category
            $stmt_delete_subs = $conn->prepare("DELETE FROM categories WHERE parent_id = ?");
            $stmt_delete_subs->bind_param("i", $id);
            $stmt_delete_subs->execute();

            // Delete the main category
            $stmt_delete = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt_delete->bind_param("i", $id);
            if ($stmt_delete->execute()) {
                $conn->commit();
                $response = ['status' => 'success', 'message' => 'Category deleted successfully.'];
            } else {
                throw new Exception('Failed to delete category.');
            }
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
exit();