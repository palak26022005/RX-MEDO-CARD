<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/libs/fpdf.php'; // ✅ Corrected __DIR__

// ✅ DB connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) { die("❌ DB connection failed"); }

// ✅ Auth check
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo "<script>alert('Please login first.'); window.location.href='login.html';</script>";
  exit;
}

// ✅ Fetch user + plan info (removed address & family_option)
$userStmt = $con->prepare("
  SELECT name, email, phone,
         card_type, rxmedo_family_opt, purchase_date,
         receipt_download_date, card_download_date
  FROM mydata
  WHERE id = ?
");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) { die("❌ User not found"); }

$name        = $user['name'];
$email       = $user['email'];
$mobile      = $user['phone'];
$address     = "NCR"; // ✅ fixed default value
$cardType    = $user['card_type'];
$category    = $user['rxmedo_family_opt']; // ✅ use this for family logic
$familyOpt   = $user['rxmedo_family_opt']; // ✅ replaced family_option
$purchase    = $user['purchase_date'];
$receiptFix  = $user['receipt_download_date'];
$cardFix     = $user['card_download_date'];

// ✅ Latest payment
$payStmt = $con->prepare("
  SELECT order_id, payment_id, amount, status, created_at
  FROM payments
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 1
");
$payStmt->bind_param("i", $user_id);
$payStmt->execute();
$payment = $payStmt->get_result()->fetch_assoc();
$payStmt->close();

if (!$payment) { die("❌ Payment not found for receipt"); }

$orderId    = $payment['order_id'];
$paymentId  = $payment['payment_id'];
$amountP    = (int)$payment['amount'];
$payStatus  = $payment['status'];
$paidAt     = $payment['created_at'];

// ✅ Fix first-download dates
$todayDateDB = date('Y-m-d');
if (empty($receiptFix) || empty($cardFix)) {
  $upd = $con->prepare("UPDATE mydata SET 
                          receipt_download_date = COALESCE(receipt_download_date, ?),
                          card_download_date    = COALESCE(card_download_date, ?)
                        WHERE id = ?");
  $upd->bind_param("ssi", $todayDateDB, $todayDateDB, $user_id);
  $upd->execute();
  $upd->close();
  $receiptFix = $receiptFix ?: $todayDateDB;
  $cardFix    = $cardFix ?: $todayDateDB;
}

// ✅ Activation date
$activationSrc = $purchase ?: ($paidAt ?: $cardFix);
$activationDateDisp = date('d/m/Y', strtotime($activationSrc));

// ✅ Validity
$validStart = date('d/m/Y', strtotime($activationSrc));
$validEnd   = date('d/m/Y', strtotime('+1 year -1 day', strtotime($activationSrc)));
$validText  = "1 Year ($validStart - $validEnd)";

// ✅ Receipt date
$receiptDateDisp = date('d/m/Y', strtotime($receiptFix));

// ✅ Amount
$amountR = number_format($amountP / 100, 2);

// ✅ Payment status
$statusText = (strtolower($payStatus) === 'success') ? 'Successful' : 'Pending Payment';

// ✅ Members covered
function membersCoveredCount($category, $familyOpt) {
  if (strtolower($category) !== 'family') return 1;
  $opt = strtolower(trim($familyOpt));
  $map = [
    'husband wife + 1 child' => 3,
    'husband wife + 2 child' => 4,
    'husband wife + 3 child' => 5,
    'husband wife + 4 child' => 6,
    'husband + wife'         => 2,
    'only husband'           => 1,
    'only wife'              => 1,
  ];
  if (isset($map[$opt])) return $map[$opt];
  if (preg_match('/\+?\s*(\d+)\s*child/', $opt, $m)) {
    return 2 + (int)$m[1];
  }
  return 2;
}
$membersCovered = membersCoveredCount($category, $familyOpt);

// ✅ Background image path
$bgImage = __DIR__ . "/RX_Medo_Card_Receipt.jpg";
if (!file_exists($bgImage)) {
  die("❌ Receipt background image not found");
}

// ✅ Output file path
$outputDir = 'receipts/generated/';
if (!is_dir($outputDir)) { mkdir($outputDir, 0777, true); }
$pdfFile = $outputDir . preg_replace('/\s+/', '_', strtolower($name)) . '_payment_receipt.pdf';

// ✅ Generate PDF
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->Image($bgImage, 0, 0, 210, 297);

$pdf->SetFont('Arial','',12);
$pdf->SetTextColor(0,0,0);

function putField($pdf, $xLabel, $y, $label, $value, $xValue = null) {
  if ($xValue === null) $xValue = $xLabel + 50;
  $pdf->SetXY($xLabel, $y);
  $pdf->Cell(45, 7, $label, 0, 0);
  $pdf->SetXY($xValue, $y);
  $pdf->Cell(120, 7, $value, 0, 1);
}

// Customer Details
putField($pdf, 23, 110,  "",          $paymentId);
putField($pdf, 103, 110,  "",                 $receiptDateDisp);
putField($pdf, 23, 117,  "",        $name);
putField($pdf, 103, 116,  "",           $mobile);
putField($pdf, 23, 124,  "",             $email);
// putField($pdf, 103, 123, "",              $address);

// Product Details
putField($pdf, 27, 152, "",            $cardType);
putField($pdf, 27, 158, "",      (string)$membersCovered);
putField($pdf, 27, 164, "",             $validText);
putField($pdf, 27, 170, "",      $activationDateDisp);

// Payment Details
putField($pdf, 34, 198, "",          "Rs." . $amountR);
putField($pdf, 34, 204, "",         "Online Transfer");
putField($pdf, 34, 209, "", $orderId);
putField($pdf, 34, 215, "",       $statusText);

// ✅ Save PDF
$pdf->Output('F', $pdfFile);

// ✅ Trigger download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($pdfFile) . '"');
readfile($pdfFile);
exit;
