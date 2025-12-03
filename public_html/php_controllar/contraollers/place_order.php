    <?php
include("../connection/origin.php");
include("../connection/database.php");
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

// Extract data
$cartItems = $data['cart_items'] ?? [];
$shipping_address = trim($data['shipping_address'] ?? '');
$payment_type = trim($data['payment_type'] ?? '');
$engraved_name = trim($data['engraved_name'] ?? '');
$billing = $data['billing_address'] ?? [];
$razorpay_payment_id = $data['razorpay_payment_id'];
$payment_status = 0; // 0 = unpaid
if($razorpay_payment_id){
    $payment_status = 1;
}
// Validation
if (empty($cartItems) || !$shipping_address || !$payment_type || empty($billing)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$valid_payment_types = ['UPI', 'Credit Card', 'Debit Card', 'COD','Online'];
if (!in_array($payment_type, $valid_payment_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment type']);
    exit;
}

// Generate unique ordered_id
function generateOrderedId($conn) {
    $prefix = "ORD-" . date("Ymd") . "-";
    do {
        $random = strtoupper(bin2hex(random_bytes(4))); // 8-char
        $ordered_id = $prefix . substr($random, 0, 8);

        // Check if exists
        $check = mysqli_prepare($conn, "SELECT id FROM orders WHERE ordered_id = ?");
        mysqli_stmt_bind_param($check, "s", $ordered_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $exists = mysqli_stmt_num_rows($check) > 0;
        mysqli_stmt_close($check);
    } while ($exists);

    return $ordered_id;
}

$ordered_id = generateOrderedId($conn);

// Insert into orders
$total_price = 0;
foreach ($cartItems as $item) {
    $price = floatval($item['price'] ?? 0);
    $qty = intval($item['quantity'] ?? 1);
    $total_price += $price * $qty;
}

$tax = $total_price * 0.08;      // 8% tax
$total_price += $tax;

$orderQuery = "INSERT INTO orders 
    (user_id, total_price, status, engraved_name, shipping_address, payment_type, payment_status, ordered_id,razorpay_payment_id)
    VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?,?)";

$orderStmt = mysqli_prepare($conn, $orderQuery);
mysqli_stmt_bind_param(
    $orderStmt,
    "idsssiss", // ✅ Correct: 7 types for 7 variables
    $user_id,
    $total_price,
    $engraved_name,
    $shipping_address,
    $payment_type,
    $payment_status,
    $ordered_id,
    $razorpay_payment_id
);


if (!mysqli_stmt_execute($orderStmt)) {
    echo json_encode(['status' => 'error', 'message' => 'Order insert failed: ' . mysqli_stmt_error($orderStmt)]);
    exit;
}
$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($orderStmt);

// Insert order_items
foreach ($cartItems as $item) {
    $variant_id = intval($item['product_variant_id'] ?? 0);
    $qty = intval($item['quantity'] ?? 1);
    $price = floatval($item['price'] ?? 0);

    $itemQuery = "INSERT INTO order_items 
        (order_id, product_variant_id, quantity, price_at_purchase)
        VALUES (?, ?, ?, ?)";
    $itemStmt = mysqli_prepare($conn, $itemQuery);
    mysqli_stmt_bind_param($itemStmt, "iiid", $order_id, $variant_id, $qty, $price);
    mysqli_stmt_execute($itemStmt);
    mysqli_stmt_close($itemStmt);
}

// Remove from cart
$deleteCartQuery = "DELETE FROM cart WHERE user_id = ?";
$deleteCartStmt = mysqli_prepare($conn, $deleteCartQuery);
mysqli_stmt_bind_param($deleteCartStmt, "i", $user_id);
mysqli_stmt_execute($deleteCartStmt);
mysqli_stmt_close($deleteCartStmt);

// Done
echo json_encode([
    'status' => 'success',
    'message' => 'Order placed successfully',
    'order_id' => $order_id,
    'ordered_id' => $ordered_id
]);

mysqli_close($conn);
?>
