    <?php
include("../connection/origin.php");
include("../connection/database.php");

$data = json_decode(file_get_contents("php://input"));

if (isset($data->userId) && isset($data->cart)) {
    $userId = intval($data->userId);
    $cart = $data->cart;

    // Step 1: Delete existing cart items for user
    $deleteQuery = "DELETE FROM cart WHERE user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $userId);
    $deleteStmt->execute();

    // Step 2: Insert new cart items
    $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, product_name, price) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);

    foreach ($cart as $item) {
        $productId = intval($item->id);
        $quantity = intval($item->quantity);
        $productName = $item->product_name;
        $price = floatval($item->price);

        $insertStmt->bind_param("iiisd", $userId, $productId, $quantity, $productName, $price);
        $insertStmt->execute();
    }

    echo json_encode(["status" => "Cart saved successfully"]);
} else {
    echo json_encode(["error" => "Invalid data"]);
}
?>
