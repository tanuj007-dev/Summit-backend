    <?php
include("../connection/origin.php");
include("../connection/database.php");


session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
setcookie("PHPSESSID", "", time() - 3600, "/"); // Optional: clear PHPSESSID cookie

echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully"
]);
?>
