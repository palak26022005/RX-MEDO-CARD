<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('razorpay-php/Razorpay.php');
use Razorpay\Api\Api;

// DB connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Get form data
$name        = $_POST['name'] ?? '';
$email       = $_POST['email'] ?? '';
$contact     = $_POST['contact'] ?? '';
$nationality = $_POST['nationality'] ?? '';
$amount      = $_POST['amount'] ?? 0;

// Set timezone
date_default_timezone_set('Asia/Kolkata');
$created_at = date('Y-m-d H:i:s');

// Insert record
$sql = "INSERT INTO donate_users (name, email, contact, nationality, amount, status, created_at) 
        VALUES ('$name', '$email', '$contact', '$nationality', '$amount', 'pending', '$created_at')";
$con->query($sql);

// Razorpay live keys
$keyId = "rzp_live_SbmpBK5uGEF7u7";
$keySecret = "A1SzVmHCYNvBmAga9NO08E77";
$api = new Api($keyId, $keySecret);

// Create order
$orderData = [
    'receipt'         => 'rcptid_'.time(),
    'amount'          => $amount * 100, // paise
    'currency'        => 'INR',
    'payment_capture' => 1
];
$razorpayOrder = $api->order->create($orderData);
$orderId = $razorpayOrder['id'];

// Update DB with order_id
$con->query("UPDATE donate_users SET razorpay_order_id='$orderId' WHERE email='$email' AND created_at='$created_at'");
$con->close();
?>

<!-- Razorpay Checkout -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?php echo $keyId; ?>",
    "amount": "<?php echo $amount*100; ?>",
    "currency": "INR",
    "name": "IFRC Donation",
    "description": "Support Our Cause",
    "order_id": "<?php echo $orderId; ?>",
    "handler": function (response){
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'success.php';

        var paymentId = document.createElement('input');
        paymentId.type = 'hidden';
        paymentId.name = 'razorpay_payment_id';
        paymentId.value = response.razorpay_payment_id;
        form.appendChild(paymentId);

        var orderId = document.createElement('input');
        orderId.type = 'hidden';
        orderId.name = 'razorpay_order_id';
        orderId.value = response.razorpay_order_id;
        form.appendChild(orderId);

        var signature = document.createElement('input');
        signature.type = 'hidden';
        signature.name = 'razorpay_signature';
        signature.value = response.razorpay_signature;
        form.appendChild(signature);

        document.body.appendChild(form);
        form.submit();
    },
    "prefill": {
        "name": "<?php echo $name; ?>",
        "email": "<?php echo $email; ?>",
        "contact": "<?php echo $contact; ?>"
    },
    "theme": {
        "color": "#d32f2f"
    }
};
var rzp1 = new Razorpay(options);
rzp1.open();
</script>