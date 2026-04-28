<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Unauthorized access! Please login first.'); window.location.href='admin_login.html';</script>";
    exit;
}

// ✅ Database connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');

if (!$con) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

// ✅ Insert Coupon
if(isset($_POST['add_coupon'])){
    $code = $_POST['code'];
    $validity = $_POST['validity_date'];
    $discount = isset($_POST['discount_percent']) && $_POST['discount_percent'] !== '' 
                ? intval($_POST['discount_percent']) 
                : 0;
    $cashback = isset($_POST['cashback_amount']) && $_POST['cashback_amount'] !== '' 
                ? intval($_POST['cashback_amount']) 
                : 0;

    $sql = "INSERT INTO coupons (code, validity_date, discount_percent, cashback_amount) 
            VALUES ('$code', '$validity', '$discount', '$cashback')";
    if(mysqli_query($con, $sql)){
        echo "<script>alert('✅ Coupon added successfully!');</script>";
    } else {
        echo "<script>alert('❌ Error: " . mysqli_error($con) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Coupon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h2>Add Coupon</h2>
    <form method="POST" action="">
        <!-- Coupon Code -->
        <div class="mb-3">
            <label class="form-label">Coupon Code</label>
            <input type="text" name="code" class="form-control" required>
        </div>

        <!-- Validity Date -->
        <div class="mb-3">
            <label class="form-label">Validity Date</label>
            <input type="date" name="validity_date" class="form-control" required>
        </div>

        <!-- Discount Percent Field -->
        <div class="mb-3">
            <label class="form-label">Discount Percent (%)</label>
            <input type="number" name="discount_percent" class="form-control" value="0" min="0" max="100">
        </div>

        <!-- Cashback Amount Field -->
        <div class="mb-3">
            <label class="form-label">Cashback Amount (₹)</label>
            <input type="number" name="cashback_amount" class="form-control" value="0" min="0">
        </div>

        <button type="submit" name="add_coupon" class="btn btn-success">Add Coupon</button>
    </form>
</body>
</html>
