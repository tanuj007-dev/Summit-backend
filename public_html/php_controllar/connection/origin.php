<?php
error_reporting(E_ALL);           // Report all errors
ini_set('display_errors', 1);     // Display errors in the browser

// Configure session settings for cross-subdomain
ini_set('session.cookie_domain', 'summithomeappliance.com'); // Leading dot includes subdomains
ini_set('session.cookie_secure', true); // Only if HTTPS is enforced
ini_set('session.cookie_samesite', 'None');

session_start();
header('Content-Type: application/json');

// Define the list of allowed origins (NO trailing slashes)
$allowed_origins = [
    "https://summithomeappliance.com",
    "https://summithomeappliances.performdigimonetize.com",
    "http://localhost:5173",
];

// Handle CORS
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
}

// Common CORS headers
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With");

// Handle preflight OPTIONS request and exit
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }
?>
