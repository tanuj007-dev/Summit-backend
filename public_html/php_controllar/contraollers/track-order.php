<?php
include("../connection/origin.php");
include("../connection/database.php");

// === Get and validate ordered_id ===
$ordered_id = isset($_GET['ordered_id']) ? trim($_GET['ordered_id']) : '';

if (empty($ordered_id)) {
    echo json_encode(["error" => "Invalid or missing ordered_id"]);
    exit;
}

// === SQL to fetch order items and order info by ordered_id ===
$sql = "
    SELECT 
        oi.quantity,
        oi.price_at_purchase,
        pv.name AS product_name,
        pv.price AS current_price,
        (
            SELECT vi.url 
            FROM variant_images vi 
            WHERE vi.variant_id = pv.id AND vi.is_primary = 1 
            LIMIT 1
        ) AS product_image,
        o.shipping_address,
        o.status,
        o.created_at AS order_date,
        o.id AS order_id
    FROM 
        order_items oi
    JOIN 
        product_variants pv ON oi.product_variant_id = pv.id
    JOIN
        orders o ON oi.order_id = o.id
    WHERE 
        o.ordered_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["error" => "Failed to prepare SQL statement."]);
    exit;
}

$stmt->bind_param("s", $ordered_id);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
$total = 0;
$shipping_address = "";
$order_status = "";
$order_date = "";

// Optional image base URL
$baseImageURL = "https://api.summithomeappliance.com/php_admin_panel/";

while ($row = $result->fetch_assoc()) {
    $subtotal = $row['price_at_purchase'] * $row['quantity'];
    $total += $subtotal;

    // For PHP < 8 fallback (no str_starts_with)
    $imageUrl = $row['product_image'];
    if ($imageUrl && strpos($imageUrl, "http") !== 0) {
        $imageUrl = $baseImageURL . ltrim($imageUrl, "/");
    }

    $orderItems[] = [
        "name" => $row['product_name'],
        "image" => $imageUrl,
        "price" => number_format($row['price_at_purchase'], 2),
        "quantity" => (int)$row['quantity'],
        "subtotal" => number_format($subtotal, 2)
    ];

    $shipping_address = $row['shipping_address'];
    $order_status = $row['status'];
    $order_date = $row['order_date'];
}

// === Handle no results ===
if (empty($orderItems)) {
    echo json_encode(["error" => "No items found for this Order ID"]);
    exit;
}

// === Success response ===
echo json_encode([
    "success" => true,
    "ordered_id" => $ordered_id,
    "order_date" => $order_date,
    "status" => $order_status,
    "shipping_address" => $shipping_address,
    "items" => $orderItems,
    "total" => number_format($total, 2)
], JSON_PRETTY_PRINT);

?>
