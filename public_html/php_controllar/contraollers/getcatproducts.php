    <?php
include("../connection/origin.php");
include("../connection/database.php");

// Decode category from URL
$category = isset($_GET['cat']) ? urldecode(trim($_GET['cat'])) : '';



if($category == ''){
     echo json_encode([
                'success' => false,
                'status' =>400,
                'message' => 'category required'
            ]);
return;
}

$category = strtolower($category); // convert input to lowercase
$likeCategory = "%$category%";
$categoryList = "('Cookware','Gas Stove','Gas Tandoor','Mixer Grinder','Pressure Cooker','Steam Cookware')";

if($category == 'others'){
$subCategorySql = "SELECT * FROM sub_category WHERE LOWER(TRIM(category)) NOT IN $categoryList";
$subCategorySqlStmt = $conn->prepare($subCategorySql);
}else{
$subCategorySql = "SELECT * FROM sub_category WHERE LOWER(TRIM(category)) LIKE ?";
$subCategorySqlStmt = $conn->prepare($subCategorySql);
$subCategorySqlStmt->bind_param('s', $likeCategory);
}

$subCategorySqlStmt->execute();
$subCategorySqlStmtResult = $subCategorySqlStmt->get_result();
$subCategoryList = [];
    while($row = $subCategorySqlStmtResult->fetch_assoc()) {
        $subCategoryList[] = $row;
    }


if ($category == 'others') {



    // Query for 'Others' category – i.e., products not in the above categories
    $sql = "SELECT * FROM productdetails 
            WHERE TRIM(LOWER(product_category)) NOT IN $categoryList";
    
    // No params to bind
    $stmt = $conn->prepare($sql);

} else {

    // Normal category filter
    $sql = "SELECT * FROM productdetails 
            WHERE TRIM(LOWER(product_category)) = TRIM(LOWER(?))";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $category);
}
// Execute and get results
$stmt->execute();
$result = $stmt->get_result();

// Format product list
$products = [];
while ($row = $result->fetch_assoc()) {
    $images = explode(',', $row['product_images']);
    $firstImage = $images[0] ?? '';
    $fullImagePath = '../admin/' . ltrim($firstImage, '/');

    $products[] = [
        'product_id' => $row['sno'],
        'product_name' => $row['product_name'],
        'product_price' => number_format($row['product_price'], 2),
        'description' => $row['product_description'],
        'image' => $fullImagePath,
        // 'all_images' => array_map(fn($img) => '../admin/' . ltrim($img, '/'), $images),
        'category' => $row['product_category'],
        'subCategory' => $row['product_sub_category'],
        
        'brand' => $row['product_brand'] ?? '',
        'size' => $row['product_size'] ?? '',
        'material' => $row['product_material'] ?? '',
        'created_at' => $row['created_at'] ?? ''
    ];
}
function utf8_clean($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = utf8_clean($value);
        }
    } elseif (is_string($data)) {
        // Fix encoding & remove broken characters
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        $data = preg_replace('/[^\x20-\x7E\xA0-\xFF]/', '', $data); // remove bad symbols
    }
    return $data;
}

// print_r($products);exit;

// Clean your product array
$products = utf8_clean($products);
echo json_encode([
    'success' => true,
    'category' => $category,
    'products' => $products,
    'subCategory'=> $subCategoryList,
]);
// foreach ($products as $i => $product) {
//     foreach ($product as $key => $value) {
//         if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
//             echo "Bad encoding at product index $i, key '$key'\n";
//         }
//     }
// }

// $json = json_encode($products);

// if ($json === false) {
//     echo "JSON Encode Error: " . json_last_error_msg();
// } else {
//     echo $json;
// }


// JSON response

exit;