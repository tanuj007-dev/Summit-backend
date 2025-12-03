    <?php
include("../connection/origin.php");
include("../connection/database.php");
// Check login
if (!isset($_SESSION['user_id'])) {

    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to use a coupon.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$coupon_code = $_POST['couponCode'] ?? '';
$subtotal = floatval($_POST['subtotal']);
$status = 'inactive';


if (empty($coupon_code)) {
    echo json_encode(['status' => 'error', 'message' => 'Coupon code is required.']);
    exit;
}

// Get coupon
$sql = "SELECT * FROM coupon WHERE coupon_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $coupon_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid coupon code.']);
    exit;
}

$coupon = $result->fetch_assoc();
$today = date("Y-m-d");

// Check coupon date validity
if ($coupon['enddate'] !== '0000-00-00' && $coupon['enddate'] < $today) {
    echo json_encode(['status' => 'error', 'message' => 'Coupon has expired.']);
    exit;
}

// Check if user already used this coupon
$checkUsed = "SELECT * FROM used_coupon WHERE user_id = ? AND coupon_id = ?";
$stmtUsed = $conn->prepare($checkUsed);
$stmtUsed->bind_param("ii", $user_id, $coupon['coupon_id']);
$stmtUsed->execute();
$resultUsed = $stmtUsed->get_result();

if ($resultUsed->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already used this coupon.']);
    exit;
}

// Calculate discount
$discountAmt = $coupon['dicsountAmt'];
$new_total = $subtotal;
$discountType = $coupon['disountType'];

if ($discountType === 'flat') {
    $new_total = max($subtotal - $discountAmt, 0);
} elseif ($discountType === '%') {
    $new_total = $subtotal - ($subtotal * $discountAmt / 100);
}


// Save coupon usage
$insertLog = "INSERT INTO used_coupon (user_id, coupon_id, couponAmt , discounType , usedDate, status) VALUES (?, ?, ?, ?, NOW(),?)";
$stmtInsert = $conn->prepare($insertLog);
$stmtInsert->bind_param("iiiss", $user_id, $coupon['coupon_id'], $discountAmt, $coupon['disountType'],$status);
$stmtInsert->execute();

// Respond with new total
echo json_encode([
    'status' => 'success',
    'message' => 'Coupon applied successfully!',
    'new_total' => number_format($new_total, 2),
    'discount' => number_format($discountAmt, 2), // REMOVE SPACE
]);

?>
