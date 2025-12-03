    <?php
include("../connection/origin.php");
include("../connection/database.php");

// Ensure session has user_id
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = $_GET['action'] ?? '';

// Function to escape and sanitize input
function get_post($key) {
    return isset($_POST[$key]) ? intval($_POST[$key]) : null;
}
$data = json_decode(file_get_contents("php://input"), true);
switch ($action) {
    case 'get':
        $sql = "SELECT product_variant_id as product_id FROM wishlists WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $wishlists = [];

        while ($row = $result->fetch_assoc()) {
            $wishlists[] = $row;
        }

        echo json_encode($wishlists);
        break;
        

    case 'add':
        

        $product_id = $data['product_id'];
        if (!$product_id) {
            echo json_encode(['error' => 'Product ID missing']);
            exit;
        }

        // Check if already exists
        $check = $conn->prepare("SELECT * FROM wishlists WHERE user_id = ? AND product_variant_id = ?");
        if (!$check) {
                die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }

        $check->bind_param("ii", $user_id, $product_id);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();

        if ($exists) {
            echo json_encode(['status' => 'already exists']);
            exit;
        }

        $sql = "INSERT INTO wishlists (user_id, product_variant_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();

        echo json_encode(['status' => 'added']);
        break;

    case 'remove':
        $product_id = $data['product_id'];
        if (!$product_id) {
            echo json_encode(['error' => 'Product ID missing']);
            exit;
        }

        $sql = "DELETE FROM wishlists WHERE user_id = ? AND product_variant_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();

        echo json_encode(['status' => 'removed']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
