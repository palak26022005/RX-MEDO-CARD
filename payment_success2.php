<?php
require_once __DIR__ . '/razorpay_php/Razorpay.php';
use Razorpay\Api\Api;

// ✅ Razorpay live keys
$LIVE_KEY_ID = "rzp_live_SbmpBK5uGEF7u7";
$LIVE_KEY_SECRET = "A1SzVmHCYNvBmAga9NO08E77";

// ✅ DB connection
$con = mysqli_connect('localhost', 'u107895813_utkarsh21', 'Kaushik001@123', 'u107895813_rxmedo');

if ($con->connect_error) {
    die("❌ DB Connection failed: " . $con->connect_error);
}

// ✅ Get Razorpay POST data
$orderId   = $_POST['razorpay_order_id'] ?? '';
$paymentId = $_POST['razorpay_payment_id'] ?? '';
$signature = $_POST['razorpay_signature'] ?? '';

if (!$orderId || !$paymentId || !$signature) {
    die("❌ Missing payment details");
}

// ✅ Verify signature (security check)
$api = new Api($LIVE_KEY_ID, $LIVE_KEY_SECRET);
try {
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature'  => $signature
    ]);
} catch (Exception $e) {
    die("❌ Signature verification failed: " . $e->getMessage());
}

// ✅ Fetch order mapping
$stmt = $con->prepare("SELECT user_id, amount FROM razorpay_orders WHERE order_id = ? LIMIT 1");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("❌ Order not found");
}
$order = $res->fetch_assoc();
$userId = (int)$order['user_id'];
$amount = (int)$order['amount'];

// ✅ Insert payment record
$status = "success";
$payStmt = $con->prepare("INSERT INTO payments (user_id, order_id, payment_id, amount, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
$payStmt->bind_param("issss", $userId, $orderId, $paymentId, $amount, $status);
$payStmt->execute();

// ✅ Update mydata table (status active + purchase_date)
$updStmt = $con->prepare("UPDATE mydata SET status='active', purchase_date=CURDATE() WHERE id=?");
$updStmt->bind_param("i", $userId);
$updStmt->execute();

// ✅ Success message / redirect
echo "<script>alert('✅ Payment successful! please login first to access your dashboard.'); window.location.href='login.html';</script>";
exit;
?>
