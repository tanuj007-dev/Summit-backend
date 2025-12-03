    <?php
include("../connection/origin.php");
include("../connection/database.php");

if (!isset($_SESSION['user_email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$email = $_SESSION['user_email'];

$query = "SELECT name, email, contact, houseno, area, city FROM user WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $address = $row['houseno'] . ', ' . $row['area'] . ', ' . $row['city'];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'name' => $row['name'],
            'email' => $row['email'],
            'contact' => $row['contact'],
            'address' => $address
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}
