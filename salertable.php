<?php
session_start();
if (!isset($_SESSION['ref_code'])) {
    echo "<script>
            alert('Please login first');
            window.location.href='salerlogin.html';
          </script>";
    exit;
}

$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) {
    die("DB Connection failed: " . mysqli_connect_error());
}

$ref_code = $_SESSION['ref_code'];

// ✅ Fetch created_at from razorpay_orders and card_download_date from mydata
$stmt = $con->prepare("SELECT m.id, m.name, m.email, m.phone, m.card_type, m.status, r.created_at, m.card_download_date 
                       FROM mydata m
                       LEFT JOIN razorpay_orders r ON m.id = r.user_id
                       WHERE m.reference=?");
$stmt->bind_param("s", $ref_code);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RX Medocard | Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Referrals for <?= htmlspecialchars($_SESSION['username']) ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle text-center">
                    <thead class="table-success">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Card Type</th>
                            <th>Status</th>
                            <th>Signup Date</th>
                            <th>Payment Recieved date</th> <!-- ✅ New Column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['card_type']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><?= htmlspecialchars($row['card_download_date']) ?></td> <!-- ✅ Show card_download_date -->
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>