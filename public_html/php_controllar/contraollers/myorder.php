    <?php
include("../connection/origin.php");
include("../connection/database.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Get the user's ID using email
$user_id = $_SESSION['user_id'];
$user_query = "SELECT id FROM user WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);

if (!$user_data) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$user_id = $user_data['id'];

// Fetch orders and items
$order_query = "
    SELECT 
        o.id AS order_id,
        o.ordered_id,
        o.total_price,
        o.status,
        o.created_at,
        o.payment_type,
        o.payment_status,
        o.engraved_name,
        o.shipping_address,
        oi.quantity,
        oi.price_at_purchase,
        pv.id AS variant_id,
        pv.name AS variant_name,
        pv.material,
        pv.size,
        pv.lid_type,
        pv.shape,
        pv.bottom_type,
        (
            SELECT url 
            FROM variant_images vi 
            WHERE vi.variant_id = pv.id 
              AND vi.is_primary = 1 
            ORDER BY vi.order_no ASC 
            LIMIT 1
        ) AS image_url
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN product_variants pv ON oi.product_variant_id = pv.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";

$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];

while ($row = mysqli_fetch_assoc($result)) {
    $order_id = $row['order_id'];

    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_id' => $order_id,
            'ordered_id' => $row['ordered_id'],
            'total_price' => $row['total_price'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'payment_type' => $row['payment_type'],
            'payment_status' => $row['payment_status'],
            'engraved_name' => $row['engraved_name'],
            'shipping_address' => $row['shipping_address'],
            'items' => []
        ];
    }

    $orders[$order_id]['items'][] = [
        'variant_id' => $row['variant_id'],
        'name' => $row['variant_name'],
        'material' => $row['material'],
        'size' => $row['size'],
        'lid_type' => $row['lid_type'],
        'shape' => $row['shape'],
        'bottom_type' => $row['bottom_type'],
        'quantity' => $row['quantity'],
        'price_at_purchase' => $row['price_at_purchase'],
        'image_url' => $row['image_url']
    ];
}

echo json_encode([
    'status' => 'success',
    'orders' => array_values($orders)
]);
