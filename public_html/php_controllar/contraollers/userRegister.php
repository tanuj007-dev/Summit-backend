    <?php
include("../connection/origin.php");
include("../connection/database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and trim inputs
    $name     = trim($_POST['name'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($contact) || empty($address)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    // Explode address into parts (House no, Area, City)
    $parts = array_map('trim', explode(',', $address));

    $houseno = $parts[0] ?? 'N/A';
    $area    = $parts[1] ?? 'N/A';
    $city    = $parts[2] ?? 'Unknown';

    // Check if user already exists
    $checkQuery = "SELECT * FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
        exit;
    }

    // Password format validation
    if (!preg_match('/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}$/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters, include one uppercase letter and one special character.']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user (without pincode)
    $insertQuery = "INSERT INTO user (name, email, password, contact, houseno, area, city) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertQuery);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Insert query error: ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "sssssss", $name, $email, $hashedPassword, $contact, $houseno, $area, $city);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'User registered successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . mysqli_stmt_error($stmt)]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
