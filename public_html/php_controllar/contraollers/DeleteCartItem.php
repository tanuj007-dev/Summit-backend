    <?php
include("../connection/origin.php");
include("../connection/database.php");


// Handle OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

$product_id = isset($data->product_id) ? intval($data->product_id) : 0;
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($product_id > 0 && $user_id > 0) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_variant_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Item removed."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to delete."]);
    }
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid product_id or user session."]);
}
?>
