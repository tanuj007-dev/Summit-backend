    <?php
include("../connection/origin.php");
include("../connection/database.php");
// Query data
$sql = "SELECT p.*,
so.name as product_series_option,
s.name as product_series,
sc.name as product_sub_category,
mc.name as product_category
FROM product_variants p 
INNER JOIN series_options so ON  so.id=p.series_option_id
INNER JOIN series s ON s.id=so.series_id
INNER JOIN sub_categories sc ON sc.id=s.sub_category_id
INNER JOIN main_categories mc ON mc.id=sc.main_category_id"; // change table name accordingly

$result = $conn->query($sql);

$data = [];
 
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $variant_id = $row['id'];
        $images = [];

        // Fetch variant images
        $img_query = $conn->query("SELECT url, is_primary FROM variant_images WHERE variant_id = $variant_id ORDER BY is_primary DESC Limit 1");

        while ($img = $img_query->fetch_assoc()) {
            $images[] = [
                'url' => $img['url'],
                'is_primary' => $img['is_primary']
            ];
        }

        $row['images'] = $images;
        $data[] = $row;
    }
    function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }
     return $mixed;
    }

    // <- FIXED

    $json = json_encode($data);
    // print_r($data);
    if ($json === false) {
         $data = utf8ize($data);
         $json = json_encode($data);
         echo $json;
        // Log or return the JSON error
        // http_response_code(500); // Internal Server Error
        // echo json_encode([
        //     "success" => false,
        //     "error" => "JSON encoding failed",
        //     "json_error" => json_last_error_msg()
        // ]);
        exit;
    }
    echo $json;

} else {
    echo json_encode([]);
}

$conn->close();
?>