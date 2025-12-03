    <?php
include("../connection/origin.php");
include("../connection/database.php");

// Decode category from URL
$category = isset($_GET['cat']) ? urldecode(trim($_GET['cat'])) : 'Cookware';

// $subCatSql = "SELECT * from  "

$sql = "SELECT * FROM productdetails 
        WHERE TRIM(LOWER(product_category)) = TRIM(LOWER(?)) 
        AND product_price BETWEEN ? AND ? 
        ORDER BY $orderBy 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siiii", $category, $priceMin, $priceMax, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

// Format product list
$products = [];
while ($row = $result->fetch_assoc()) {
    $images = explode(',', $row['product_images']);
    $firstImage = $images[0] ?? '';
    $fullImagePath = '../admin/' . ltrim($firstImage, '/');

    $products[] = [
        'id' => $row['sno'],
        'name' => $row['product_name'],
        'price' => number_format($row['product_price'], 2),
        'description' => $row['product_description'],
        'image' => $fullImagePath,
        'all_images' => array_map(fn($img) => '../admin/' . ltrim($img, '/'), $images),
        'category' => $row['product_category'],
        'brand' => $row['product_brand'] ?? '',
        'size' => $row['product_size'] ?? '',
        'material' => $row['product_material'] ?? '',
        'created_at' => $row['created_at'] ?? ''
    ];
}

// JSON response
echo json_encode([
    'success' => true,
    'category' => $category,
    'current_page' => (int) $page,
    'total_pages' => $totalPages,
    'total_products' => $totalProducts,
    'products' => $products
]);
