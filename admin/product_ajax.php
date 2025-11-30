<?php
// File: product_ajax.php
// Location: /admin/
// REVISED: Now automatically converts Google Drive links to direct image URLs.
include_once __DIR__ . '/../common/config.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required. Please log in again.']);
    exit();
}

/**
 * --- NEW HELPER FUNCTION ---
 * Converts a Google Drive sharing link to a direct, embeddable image link.
 * @param string $url The original Google Drive URL.
 * @return string The converted direct image URL, or the original URL if it's not a GDrive link.
 */
function convertGoogleDriveLink($url) {
    // Check if it's a valid Google Drive file link
    if (strpos($url, 'drive.google.com/file/d/') !== false) {
        // Use regex to extract the file ID
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $fileID = $matches[1];
            // Return the direct image link format
            return "https://lh3.googleusercontent.com/d/" . $fileID;
        }
    }
    // If it's not a GDrive link or format is unknown, return the original URL
    return $url;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid Request'];

try {
    if (!$conn || $conn->connect_error) { 
        throw new Exception("Database connection failed for API: " . ($conn->connect_error ?? 'Check config.php')); 
    }

    // --- ACTION: FETCH ALL PRODUCTS ---
    if ($action == 'fetch') {
        $products = [];
        $result = $conn->query("SELECT p.id, p.name, p.price, p.stock, p.image, c.name as cat_name FROM products p LEFT JOIN categories c ON p.cat_id = c.id ORDER BY p.id DESC");
        if (!$result) throw new Exception("Database query failed: " . $conn->error);
        
        while ($row = $result->fetch_assoc()) {
            $row['image_url'] = get_image_url($row['image']);
            $products[] = $row; 
        }
        $response = ['status' => 'success', 'data' => $products];
    } 
    
    // --- ACTION: ADD OR UPDATE A PRODUCT ---
    else if ($action == 'add' || $action == 'update') {
        $conn->begin_transaction();
        
        $name = trim($_POST['name'] ?? '');
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        if (empty($name)) throw new Exception('Product name is required.');
        if ($cat_id <= 0) throw new Exception('Please select a category.');

        $product_id = (int)($_POST['product_id'] ?? 0);
        $image_name = $_POST['current_image'] ?? '';

        // Handle Main Image (File upload takes priority over URL)
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = ROOT_PATH . "/uploads/";
            if (!is_dir($target_dir)) @mkdir($target_dir, 0755, true);
            $image_name = 'prod_main_' . time() . '_' . basename($_FILES["image"]["name"]);
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name)) {
                throw new Exception('Failed to upload main image.');
            }
        } else if (!empty(trim($_POST['main_image_url']))) {
            $image_url = trim($_POST['main_image_url']);
            // --- FIX: Convert Google Drive link before validation ---
            $converted_url = convertGoogleDriveLink($image_url);
            if (filter_var($converted_url, FILTER_VALIDATE_URL)) {
                $image_name = $converted_url;
            } else {
                throw new Exception('The provided Main Image URL is not valid.');
            }
        }

        if ($action == 'add' && empty($image_name)) {
            throw new Exception('A main image (either by upload or URL) is required.');
        }

        $variant_of = !empty($_POST['variant_of']) ? (int)$_POST['variant_of'] : null;
        $gender = !empty($_POST['gender']) ? trim($_POST['gender']) : null;
        $mrp = !empty($_POST['mrp']) ? (float)$_POST['mrp'] : null;
        $price = (float)$_POST['price'];
        $delivery_charge = (float)($_POST['delivery_charge'] ?? 0.00);
        $stock = (int)$_POST['stock'];
        $sizes = trim($_POST['sizes']);
        $description = trim($_POST['description']);
        $video_url = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null;
        
        if ($action == 'add') {
            $stmt = $conn->prepare("INSERT INTO products (name, cat_id, gender, variant_of, price, delivery_charge, mrp, stock, sizes, description, image, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisidddissss", $name, $cat_id, $gender, $variant_of, $price, $delivery_charge, $mrp, $stock, $sizes, $description, $image_name, $video_url);
            $stmt->execute();
            $product_id = $conn->insert_id;
        } else {
            if ($product_id <= 0) throw new Exception('Invalid product ID for update.');
            if ($variant_of == $product_id) $variant_of = null;
            $stmt = $conn->prepare("UPDATE products SET name=?, cat_id=?, gender=?, variant_of=?, price=?, delivery_charge=?, mrp=?, stock=?, sizes=?, description=?, image=?, video_url=? WHERE id=?");
            $stmt->bind_param("sisidddissssi", $name, $cat_id, $gender, $variant_of, $price, $delivery_charge, $mrp, $stock, $sizes, $description, $image_name, $video_url, $product_id);
            $stmt->execute();
        }
        $stmt->close();
        
        // Handle Additional Images (Both Files and URLs)
        // 1. Handle File Uploads
        if (isset($_FILES['additional_images'])) {
            $img_stmt_file = $conn->prepare("INSERT INTO product_images (product_id, image_name) VALUES (?, ?)");
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] == 0) {
                    $add_img_name = 'prod_add_' . time() . '_' . basename($_FILES['additional_images']['name'][$key]);
                    if (move_uploaded_file($tmp_name, ROOT_PATH . "/uploads/" . $add_img_name)) {
                        $img_stmt_file->bind_param("is", $product_id, $add_img_name);
                        $img_stmt_file->execute();
                    }
                }
            }
            $img_stmt_file->close();
        }
        // 2. Handle URL Inputs
        if (!empty(trim($_POST['additional_image_urls']))) {
            $urls = preg_split('/\\r\\n|\\r|\\n/', $_POST['additional_image_urls']);
            if (count($urls) > 0) {
                $img_stmt_url = $conn->prepare("INSERT INTO product_images (product_id, image_name) VALUES (?, ?)");
                foreach ($urls as $url) {
                    $url = trim($url);
                    // --- FIX: Convert Google Drive link before validation ---
                    $converted_url = convertGoogleDriveLink($url);
                    if (filter_var($converted_url, FILTER_VALIDATE_URL)) {
                        $img_stmt_url->bind_param("is", $product_id, $converted_url);
                        $img_stmt_url->execute();
                    }
                }
                $img_stmt_url->close();
            }
        }
        
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Product ' . ($action == 'add' ? 'saved' : 'updated') . ' successfully.'];

    } 
    
    else if ($action == 'get_single') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("Invalid Product ID.");
        
        $stmt = $conn->prepare("
            SELECT p.*, c.parent_id as category_parent_id 
            FROM products p
            LEFT JOIN categories c ON p.cat_id = c.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) { $response = ['status' => 'success', 'data' => $product]; } 
        else { throw new Exception("Product not found."); }
    } 
    
    else if ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception("Invalid Product ID.");
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) { $response = ['status' => 'success', 'message' => 'Product deleted.']; } 
        else { throw new Exception("Failed to delete product."); }
    }

} catch (Exception $e) {
    // Check if the property exists before accessing it
    if (property_exists($conn, 'in_transaction') && $conn->in_transaction) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
if ($conn) $conn->close();
exit();
?>