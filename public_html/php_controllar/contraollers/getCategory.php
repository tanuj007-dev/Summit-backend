    <?php
include("../connection/origin.php");
include("../connection/database.php");

$main_id = isset($_GET['main']) ? (int) $_GET['main'] : 1; // default to 1

$merged = [];

$subs = $conn->query("
    SELECT id, name 
    FROM sub_categories 
    WHERE main_category_id = $main_id 
    ORDER BY order_no
");

while ($sub = $subs->fetch_assoc()) {
    $sub_id = $sub['id'];
    $sub_name = $sub['name'];

    // Sub-category entry
    $merged[] = [
        "type" => "sub_category",
        "id" => $sub_id,
        "name" => $sub_name
    ];

    // Fetch series under this sub-category
    $series_q = $conn->query("
        SELECT id, name 
        FROM series 
        WHERE sub_category_id = $sub_id 
        ORDER BY order_no
    ");

    // while ($ser = $series_q->fetch_assoc()) {
    //     $series_id = $ser['id'];
    //     $series_name = $ser['name'];
    //     $options = [];

    //     $opts = $conn->query("
    //         SELECT id, name 
    //         FROM series_options 
    //         WHERE series_id = $series_id 
    //         ORDER BY order_no
    //     ");

    //     while ($opt = $opts->fetch_assoc()) {
    //         $opt_id = $opt['id'];
    //         $opt_name = $opt['name'];
    //         $sizes = [];

    //         $size_q = $conn->query("
    //             SELECT DISTINCT size 
    //             FROM product_variants 
    //             WHERE series_option_id = $opt_id AND size IS NOT NULL AND size != ''
    //         ");

    //         while ($sz = $size_q->fetch_assoc()) {
    //             $sizes[] = $sz['size'];
    //         }

    //         $options[] = [
    //             "id" => $opt_id,
    //             "name" => $opt_name,
    //             "sizes" => $sizes
    //         ];
    //     }

    //     $merged[] = [
    //         "type" => "series",
    //         "id" => $series_id,
    //         "name" => $series_name,
    //         "sub_category" => $sub_name,
    //         "options" => $options
    //     ];
    // }
}

echo json_encode($merged);
?>
