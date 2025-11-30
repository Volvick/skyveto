<?php
// File: sitemap.php
// Location: / (public_html)
// FINAL, ROBUST VERSION - Clears output buffer to ensure pure XML.

// Start output buffering to catch any stray output from included files.
ob_start();

// Include the main configuration file to get correct DB credentials.
include_once __DIR__ . '/common/config.php';

// Check for connection errors BEFORE trying to output XML.
if ($conn->connect_error) {
    ob_end_clean(); // Clear any buffered output.
    header("HTTP/1.1 500 Internal Server Error");
    error_log("Sitemap DB Connection Failed: " . $conn->connect_error);
    exit;
}

// --- IMPORTANT: Clear any potential stray output from included files ---
ob_end_clean();

// --- Set the correct XML content type header ---
header("Content-Type: application/xml; charset=utf-8");

// --- Start the XML output ---
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Function to safely output a URL using the BASE_URL from config.
function output_sitemap_url($loc, $changefreq = 'daily', $priority = '0.8') {
    // BASE_URL is defined in your config.php file.
    $full_loc = BASE_URL . '/' . ltrim($loc, '/');
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($full_loc) . "</loc>\n";
    echo "    <changefreq>" . $changefreq . "</changefreq>\n";
    echo "    <priority>" . $priority . "</priority>\n";
    echo "  </url>\n";
}

// --- Static Pages ---
output_sitemap_url('', 'daily', '1.0'); // Homepage
output_sitemap_url('login.php', 'monthly', '0.8');
output_sitemap_url('product.php', 'daily', '0.9');

// --- Dynamic Product Pages ---
$products_result = $conn->query("SELECT id FROM products WHERE variant_of IS NULL ORDER BY id DESC");
if ($products_result) {
    while($product = $products_result->fetch_assoc()) {
        output_sitemap_url('product_detail.php?id=' . $product['id'], 'weekly', '0.8');
    }
}

// --- Dynamic Category Pages ---
$categories_result = $conn->query("SELECT id FROM categories ORDER BY id DESC");
if ($categories_result) {
    while($category = $categories_result->fetch_assoc()) {
        output_sitemap_url('product.php?cat_id=' . $category['id'], 'weekly', '0.7');
    }
}

echo '</urlset>';
$conn->close();

// Terminate the script to ensure no other output is sent.
exit();
?>