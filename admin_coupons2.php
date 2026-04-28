<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Unauthorized access! Please login first.'); window.location.href='admin_login.html';</script>";
    exit;
}

// ✅ Database connection
$con = mysqli_connect('localhost', 'u107895813_utkarsh21', 'Kaushik001@123', 'u107895813_rxmedo');
if (!$con) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

// ✅ Insert Coupon
if(isset($_POST['add_coupon'])){
    $code = $_POST['code'];
    $validity = $_POST['validity_date'];
    $discount = $_POST['discount_percent'];

    $sql = "INSERT INTO coupons (code, validity_date, discount_percent) 
            VALUES ('$code', '$validity', '$discount')";
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
        <div class="mb-3">
            <label class="form-label">Coupon Code</label>
            <input type="text" name="code" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Validity Date</label>
            <input type="date" name="validity_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Discount Percent</label>
            <select name="discount_percent" class="form-select" required>
                <?php for($i=1; $i<=100; $i++){ echo "<option value='$i'>$i%</option>"; } ?>
            </select>
        </div>

        <button type="submit" name="add_coupon" class="btn btn-success">Add Coupon</button>
    </form>
</body>
</html>
