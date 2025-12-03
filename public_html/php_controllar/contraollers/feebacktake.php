    <?php
include("../connection/origin.php");
include("../connection/database.php");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON input."]);
    exit;
}

// Sanitize and validate
$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$nickname = isset($data['nickname']) ? trim($data['nickname']) : '';
$reason = isset($data['reason']) ? trim($data['reason']) : '';
$comment = isset($data['comment']) ? trim($data['comment']) : '';

// if ($product_id === 0 || $rating === 0 || empty($nickname) || empty($reason) || empty($comment)) {
//     echo json_encode([
//         "status" => "error",
//         "message" => "Missing fields",
//         "debug" => compact("product_id", "rating", "nickname", "reason", "comment")
//     ]);
//     exit;
// }

// Prepare and execute SQL
$stmt = $conn->prepare("INSERT INTO reviews (product_id, rating, nickname, reason, comment) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("iisss", $product_id, $rating, $nickname, $reason, $comment);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Review submitted"]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
