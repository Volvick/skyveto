<?php
// File: search.php (NEW)
// Location: /
include_once 'common/config.php';

// Fetch popular/trending search terms. For simplicity, we'll get some product names.
$popular_searches = [];
$result = $conn->query("SELECT name FROM products WHERE variant_of IS NULL ORDER BY RAND() LIMIT 8");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $popular_searches[] = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Search - Skyveto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-white">
    <div class="p-4">
        <!-- Search Header -->
        <div class="flex items-center gap-2 mb-6">
            <a href="javascript:history.back()" class="text-gray-600 text-xl p-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <form action="<?php echo BASE_URL; ?>/product.php" method="GET" class="w-full flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:border-blue-500">
                <input class="appearance-none bg-transparent border-none w-full text-gray-700 text-sm py-2 px-3 leading-tight focus:outline-none" type="search" name="search_query" placeholder="Search for products..." required autofocus>
                <button type="button" class="px-3 text-gray-500 hover:text-gray-700" title="Search by voice (coming soon)">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="button" class="px-3 text-gray-500 hover:text-gray-700" title="Search by image (coming soon)">
                    <i class="fas fa-camera"></i>
                </button>
            </form>
        </div>

        <!-- Popular Searches -->
        <div>
            <h2 class="text-md font-semibold text-gray-700 mb-3">Popular Searches</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($popular_searches as $search_term): ?>
                    <a href="product.php?search_query=<?php echo urlencode($search_term); ?>" class="bg-gray-100 text-gray-600 text-sm px-3 py-1 rounded-full hover:bg-gray-200">
                        <?php echo htmlspecialchars($search_term); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>