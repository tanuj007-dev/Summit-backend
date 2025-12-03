<?php
// Allow access from any origin
header("Access-Control-Allow-Origin: *");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Allow specific methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// If the request method is OPTIONS, return early
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
    
    
    

include("../connection/origin.php");
include("../connection/database.php");

$menu = [];

$mains = $conn->query("SELECT id, name FROM main_categories ORDER BY order_no");
while ($main = $mains->fetch_assoc()) {
    $main_id = $main['id'];
    $sub_list = [];

    $subs = $conn->query("SELECT id, name FROM sub_categories WHERE main_category_id = $main_id ORDER BY order_no");
    while ($sub = $subs->fetch_assoc()) {
        $sub_id = $sub['id'];
        $series_list = [];

        $series_q = $conn->query("SELECT id, name, sub_category_id FROM series WHERE sub_category_id = $sub_id ORDER BY order_no");
        while ($ser = $series_q->fetch_assoc()) {
            $series_id = $ser['id'];
            $options = [];
            $opts = $conn->query("SELECT id, name , series_id FROM series_options WHERE series_id = $series_id ORDER BY order_no");
            while ($opt = $opts->fetch_assoc()) {
                $opt_id = $opt['id'];
                $sizes = [];

                $size_q = $conn->query("SELECT DISTINCT size FROM product_variants WHERE series_option_id = $opt_id");
                while ($sz = $size_q->fetch_assoc()) {
                    $sizes[] = $sz['size'];
                }

                $opt['sizes'] = $sizes;
                $options[] = $opt;
            }

            $series_list[] = [
                'id' => $series_id,
                'name' => $ser['name'],
                'options' => $options
            ];
        }

        $sub_list[] = [
            'id' => $sub_id,
            'name' => $sub['name'],
            'series' => $series_list
        ];
    }

    $menu[] = [
        'id' => $main_id,
        'name' => $main['name'],
        'sub_categories' => $sub_list
    ];
}

echo json_encode($menu);
?>
