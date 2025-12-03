    <?php
include("../connection/origin.php");
include("../connection/database.php");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = $data['product_id'];
    $quantity = max(1, $data['quantity']);
    $product_name = $data['product_name'];
    $product_price = $data['product_price'];
    $mode = $data['mode'] ?? 'add'; // Default to 'add' if not set

    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_variant_id=?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if ($mode === 'add') {
            $new_quantity = $row['quantity'] + $quantity;
        } else {
            $new_quantity = $quantity;
        }

        $update = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
        $update->bind_param("ii", $new_quantity, $row['id']);
        $update->execute();
    } else {
        $total = $quantity * $product_price;
        $insert = $conn->prepare("INSERT INTO cart (user_id, product_variant_id, quantity) VALUES (?, ?, ?)");
        $insert->bind_param("iii", $user_id, $product_id, $quantity);
        $insert->execute();
    }

    echo json_encode(['success' => true]);
}

elseif ($method === 'GET') {
    $sql = "SELECT * FROM cart c 
            INNER JOIN product_variants p ON p.id=c.product_variant_id
            Where user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartItems = $result->fetch_all(MYSQLI_ASSOC);
    foreach($cartItems as $key=>$value){
         $variant_id = $value['id'];
        $images = [];
                $img_query = $conn->query("SELECT url, is_primary FROM variant_images WHERE variant_id = $variant_id ORDER BY is_primary DESC Limit 1");

        while ($img = $img_query->fetch_assoc()) {
            $images[] = [
                'url' => $img['url'],
                'is_primary' => $img['is_primary']
            ];
        }
        $value['images'] = $images;
        $cartItems[$key] = $value;
    }
    // print_r($cartItems);
    echo json_encode($cartItems);
}
?>
