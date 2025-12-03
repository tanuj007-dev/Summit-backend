    <?php
include("../connection/origin.php");
include("../connection/database.php");

$main = (isset($_GET['main']) && $_GET['main'] != '') ? $conn->real_escape_string($_GET['main']) : '';
$sub = isset($_GET['sub']) ? $conn->real_escape_string($_GET['sub']) : '';
$series = isset($_GET['series']) ? $conn->real_escape_string($_GET['series']) : '';
$option = isset($_GET['option']) ? $conn->real_escape_string($_GET['option']) : '';
$size = isset($_GET['size']) ? $conn->real_escape_string($_GET['size']) : '';

$query = "
    SELECT pv.*, 
           si.name AS series_option, 
           si.id AS series_option_id, 
           s.name AS series_name, 
           s.id AS series_id, 
           sc.name AS sub_category, 
           sc.id AS sub_category_id, 
           mc.name AS main_category,
           mc.id AS main_category_id
    FROM product_variants pv
    JOIN series_options si ON pv.series_option_id = si.id
    JOIN series s ON si.series_id = s.id
    JOIN sub_categories sc ON s.sub_category_id = sc.id
    JOIN main_categories mc ON sc.main_category_id = mc.id
    WHERE 1=1
";

if ($main)  $query .= " AND mc.id = '$main'";
if ($sub)   $query .= " AND sc.name = '$sub'";
if ($series) $query .= " AND s.name = '$series'";
if ($option) $query .= " AND si.name = '$option'";
if ($size)   $query .= " AND pv.size = '$size'";

$query .= " ORDER BY pv.order_no";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $variant_id = $row['id'];
    $images = [];

    // Fetch variant images
    $img_query = $conn->query("SELECT url, is_primary FROM variant_images WHERE variant_id = $variant_id ORDER BY is_primary DESC, order_no ASC");

    while ($img = $img_query->fetch_assoc()) {
        $images[] = [
            'url' => $img['url'],
            'is_primary' => $img['is_primary']
        ];
    }

    $row['images'] = $images;
    $data[] = $row;
}

echo json_encode($data);
?>
