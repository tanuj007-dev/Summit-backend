    <?php
include("../connection/origin.php");
include("../connection/database.php");

// Query data
$searchedData = (isset($_GET['search']))?$_GET['search']:'';

     // Prepare wildcard for partial match
    $search = '%' . $searchedData . '%';

    $sql = "SELECT * FROM productdetails WHERE 
                product_name LIKE ? OR 
                product_category LIKE ? OR 
                product_sub_category LIKE ? OR 
                product_tags LIKE ? OR 
                product_description LIKE ?";
    
    $sqlStmt = $conn->prepare($sql);
    $sqlStmt->bind_param('sssss', $search, $search, $search, $search, $search);
    $sqlStmt->execute();
    $result = $sqlStmt->get_result();


if ($searchedData != '' && $result->num_rows > 0) {
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Clean UTF-8 (if needed)
    $data = utf8_clean($data); // define this function below

    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    echo json_encode([]);
}
function utf8_clean($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = utf8_clean($value);
        }
    } elseif (is_string($data)) {
        $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    }
    return $data;
}

$conn->close();

?>