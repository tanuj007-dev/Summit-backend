    <?php
include("../connection/origin.php");
include("../connection/database.php");

$main_categories_sql = "SELECT * FROM main_categories  Where name='Pressure Cooker' ORDER BY name";
$main_result = mysqli_query($conn, $main_categories_sql);

$menu = [];

while ($main = mysqli_fetch_assoc($main_result)) {
    $main_id = $main['id'];
    $main_item = [
        'id' => $main_id,
        'name' => $main['name'],
        'sub_categories' => []
    ];

    $sub_sql = "SELECT * FROM sub_categories WHERE main_category_id = $main_id ORDER BY name";
    $sub_result = mysqli_query($conn, $sub_sql);

    while ($sub = mysqli_fetch_assoc($sub_result)) {
        $sub_id = $sub['id'];
        $sub_item = [
            'id' => $sub_id,
            'name' => $sub['name'],
            'series' => []
        ];

        $series_sql = "SELECT * FROM series WHERE sub_category_id = $sub_id ORDER BY name";
        $series_result = mysqli_query($conn, $series_sql);

        while ($series = mysqli_fetch_assoc($series_result)) {
            $series_id = $series['id'];
            $series_item = [
                'id' => $series_id,
                'name' => $series['name'],
                'options' => []
            ];

            $options_sql = "SELECT * FROM series_options WHERE series_id = $series_id ORDER BY name";
            $options_result = mysqli_query($conn, $options_sql);

            while ($opt = mysqli_fetch_assoc($options_result)) {
                $series_item['options'][] = [
                    'id' => $opt['id'],
                    'name' => $opt['name']
                ];
            }

            $sub_item['series'][] = $series_item;
        }

        $main_item['sub_categories'][] = $sub_item;
    }

    $menu[] = $main_item;
}

echo json_encode($menu);
?>
