    <?php
include("../connection/origin.php");
include("../connection/database.php");

$sort = $_POST['sort'] ?? 'Short by Popularity';
$priceMin = $_POST['priceMin'] ?? 150;
$priceMax = $_POST['priceMax'] ?? 3000;
$page = $_POST['page'] ?? 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Determine sort column
$orderBy = "sno DESC"; // default
if ($sort == 'Short by Newest') {
    $orderBy = "created_at DESC"; // Adjust based on your DB schema
}

// Count total for pagination
$countSql = "SELECT COUNT(*) as total FROM productdetails 
             WHERE product_category = 'plant' AND product_sub_category = 'indoor plants' 
             AND product_price BETWEEN $priceMin AND $priceMax";
$totalResult = $conn->query($countSql)->fetch_assoc();
$totalPages = ceil($totalResult['total'] / $limit);

// Main query
$sql = "SELECT * FROM productdetails 
        WHERE product_category = 'plant' AND product_sub_category= 'indoor plants' 
        AND product_price BETWEEN $priceMin AND $priceMax 
        ORDER BY $orderBy 
        LIMIT $offset, $limit";
$result = $conn->query($sql);

// Output products
$output = '';
while ($row = $result->fetch_assoc()) {
    $firstImage = explode(',', $row['product_images'])[0];
    $output .= '
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="single-product-area mb-50">
            <div class="product-img">
                <a href="shop-details.php?id=' . $row['sno'] . '">
                    <img src="../admin/'. $firstImage.'" alt="">
                </a>
            </div>
            <div class="product-info mt-15 text-center">
                <a href="shop-details.php?id=' . $row['sno'] . '">
                    <p>' . htmlspecialchars($row['product_name']) . '</p>
                </a>
                <h6>₹' . number_format($row['product_price'], 2) . '</h6>
            </div>
        </div>
    </div>';
}

// Pagination
$output .= '<div class="col-12"><ul class="pagination">';
for ($i = 1; $i <= $totalPages; $i++) {
    $output .= '<li class="page-item"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
}
$output .= '</ul></div>';

echo $output;
?>
