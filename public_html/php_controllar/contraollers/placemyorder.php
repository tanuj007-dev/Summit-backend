    <?php
include("../connection/origin.php");
include("../connection/database.php");


// Check DB connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Check user session
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Retrieve and sanitize inputs
$email       = $_SESSION['user_email'];
$item_name   = trim($_POST['item_name'] ?? '');
$quantity    = intval($_POST['quantity'] ?? 0);
$price       = floatval($_POST['price'] ?? 0.0);
$image_path  = trim($_POST['image_path'] ?? '');
$order_date  = date("Y-m-d H:i:s");

// Validate inputs
if (!$item_name || $quantity <= 0 || $price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order data']);
    exit;
}

$total_price = $price * $quantity;

// Prepare SQL query
$query = "INSERT INTO my_order (user_email, item_name, quantity, total_price, order_date, status, image_path)
          VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
$stmt = mysqli_prepare($conn, $query);

// Check prepare success
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . mysqli_error($conn)]);
    exit;
}

// Bind parameters
mysqli_stmt_bind_param($stmt, "sssdss", $email, $item_name, $quantity, $total_price, $order_date, $image_path);

// Execute
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Order placed successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Order failed: ' . mysqli_stmt_error($stmt)]);
}

// Clean up
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
