<?php
error_reporting(0);
ini_set('display_errors', 0);

$con = mysqli_connect('localhost', 'u107895813_utkarsh21', 'Kaushik001@123', 'u107895813_rxmedo');
if ($conn->connect_error) {
    die("DB connection failed");
}

$sql = "SELECT * FROM mydata";
$result = $conn->query($sql);

if ($result === false) {
    die("Query failed");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=users_data.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* ===== EXCEL HEADERS ===== */
echo "Name\tEmail\tAadhaar Card\tPAN Card\tCard Type\tPurchase Date\tStatus\tRef Code\tReference\n";

/* ===== DATA ROWS ===== */
while ($row = $result->fetch_assoc()) {

    echo ($row['name'] ?? '') . "\t" .
        ($row['email'] ?? '') . "\t" .
        ("'" . ($row['aadhaar_card_no'] ?? '')) . "\t".
        ($row['pan_card_no'] ?? '') . "\t" .
        ($row['card_type'] ?? '') . "\t" .
        ($row['purchase_date'] ?? '') . "\t" .
        ($row['status'] ?? '') . "\t" .
        ($row['ref_code'] ?? '') . "\t" .
        ($row['reference'] ?? '') . "\n";
}

exit;
