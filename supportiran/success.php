<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

// Razorpay live keys
$keyId = "rzp_live_SbmpBK5uGEF7u7";
$keySecret = "A1SzVmHCYNvBmAga9NO08E77";
$api = new Api($keyId, $keySecret);

// Razorpay response (from checkout.js POST)
$paymentId  = $_POST['razorpay_payment_id'] ?? '';
$orderId    = $_POST['razorpay_order_id'] ?? '';
$signature  = $_POST['razorpay_signature'] ?? '';

try {
    // Verify signature
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature'  => $signature
    ]);

    // DB connection
  $con = mysqli_connect('localhost', 'root', '', 'medocard');


    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    // Update DB safely
    $stmt = $con->prepare("UPDATE donate_users SET status=?, razorpay_payment_id=? WHERE razorpay_order_id=?");
    $status = 'success';
    $stmt->bind_param("sss", $status, $paymentId, $orderId);
    $stmt->execute();

    // Check if record updated
    if ($stmt->affected_rows > 0) {
        // Success message
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Donation Success</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='container mt-5'>
            <div class='alert alert-success'>
                <h2>Payment Successful!</h2>
                <p>Thank you for your donation. Your payment has been verified.</p>
            </div>
            <a href='receipt.php?order_id=$orderId' class='btn btn-primary'>Download Receipt</a>
        </body>
        </html>";
    } else {
        echo "<div class='alert alert-danger'>No donation record found for this order in database.</div>";
    }

    $stmt->close();
    $conn->close();

} catch(Exception $e) {
    echo "<div class='alert alert-danger'>Payment verification failed: " . $e->getMessage() . "</div>";
}
?>