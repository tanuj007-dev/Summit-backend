    <?php
include("../connection/origin.php");
include("../connection/database.php");
if (isset($_GET['userId'])) {
    $userId = intval($_GET['userId']);

    $query = "SELECT * FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $cartItems = [];

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }

    echo json_encode($cartItems);
} else {
    echo json_encode(["error" => "Missing userId"]);
}
?>
