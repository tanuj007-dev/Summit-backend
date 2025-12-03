<?php
include("../connection/origin.php");
// include("../connection/database.php");
require('../vendor/autoload.php'); // Composer autoload (razorpay/razorpay SDK)

use Razorpay\Api\Api;

// Your API Keys (from Dashboard)
$keyId = "rzp_test_R7BTCTcFLCXdCH";
$keySecret = "LgjUmhBR2Ee72d0ht9TLGYQV";


// Initialize Razorpay API
$api = new Api($keyId, $keySecret);

 $data = json_decode(file_get_contents("php://input"), true);

$orderData = [
    'receipt'         => uniqid(),
    'amount'          => $data['amount'], // amount in paise
    'currency'        => $data['currency'],
    'payment_capture' => 1 // auto capture
];

$order = $api->order->create($orderData);

// ✅ Convert Razorpay object to array before sending to frontend
echo json_encode($order->toArray());

exit;
