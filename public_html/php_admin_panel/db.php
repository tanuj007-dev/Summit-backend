
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "u309740424_sahil";
// $user = "root";
$pass = "K;3lhK:!?!Gf0aAf";
// $pass = "";
// $db_name = "u309740424_SummitHome";
$db_name = "u309740424_adminsummit";




$conn = new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
