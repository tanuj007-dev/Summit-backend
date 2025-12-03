<?php
include("../connection/origin.php");

require('../vendor/autoload.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$keyId = "rzp_test_R7BTCTcFLCXdCH";
$keySecret = "LgjUmhBR2Ee72d0ht9TLGYQV";

$api = new Api($keyId, $keySecret);

// Get POST data from frontend
$input = json_decode(file_get_contents("php://input"), true);

$paymentId   = $input['razorpay_payment_id'] ?? null;
$orderId     = $input['razorpay_order_id'] ?? null;
$signature   = $input['razorpay_signature'] ?? null;

try {
    // Verify signature
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // ✅ Payment verified successfully
    $response = [
        'success' => true,
        'message' => 'Payment Verified',
        'payment_id' => $paymentId,
        'order_id' => $orderId
    ];

} catch (SignatureVerificationError $e) {
    // ❌ Payment verification failed
    $response = [
        'success' => false,
        'message' => 'Payment Verification Failed: ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
