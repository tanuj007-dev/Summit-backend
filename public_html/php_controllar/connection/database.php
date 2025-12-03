<?php
// $host = "localhost";
// // $user = "u309740424_SummitHome";
// $user = "root";  
// // $pass = "SummitHome@123";
// $pass = "";
// // $db_name = "u309740424_SummitHome";
// $db_name = "u309740424_summithome_latest";


 
// $conn = new mysqli($host, $user, $pass, $db_name);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
?>

<?php
$host = "localhost";
$user = "u309740424_live_summit";
// $user = "u309740424_live_summit";
$pass = "5P#1e+9P~If";
// $pass = "";
$db_name = "u309740424_live_summit";


 
$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>