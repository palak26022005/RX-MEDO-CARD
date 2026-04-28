<?php
require('fpdf186/fpdf.php'); // FPDF include

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection (Hostinger live credentials)
$conn = new mysqli("localhost", "u107895813_donations", "2025#Human", "u107895813_donations");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get order_id from URL
$orderId = $_GET['order_id'] ?? '';

// Fetch donation record
$result = $conn->query("SELECT * FROM donate_users WHERE razorpay_order_id='$orderId'");
$row = $result->fetch_assoc();
$conn->close();

// If no record found
if(!$row){
    die("No donation record found for this order.");
}

$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();

// Colors
$primaryColor = [211, 47, 47];    // Red
$textColor    = [51, 51, 51];     // Dark gray
$successColor = [76, 175, 80];    // Green

// Header background
$pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Rect(0, 0, 210, 50, 'F');

// Logo (bigger size)
if(file_exists("cifrc.png")){
    $pdf->Image('cifrc.png', 15, 10, 45);
}

// Header text
$pdf->SetTextColor(255,255,255);
$pdf->SetFont('Arial','B',20);
$pdf->SetXY(65,12);
$pdf->Cell(0,10,'International Federation',0,1,'L');
$pdf->SetFont('Arial','',14);
$pdf->SetXY(65,22);
$pdf->Cell(0,8,'Red Cross and Red Crescent',0,1,'L');

// Receipt badge
$pdf->SetFont('Arial','B',14);
$pdf->SetXY(140,20);
$pdf->Cell(60,10,'OFFICIAL RECEIPT',0,0,'C');

// Title
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->SetFont('Arial','B',24);
$pdf->Ln(30);
$pdf->Cell(0,15,'Donation Receipt',0,1,'C');

// Receipt number and date
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont('Arial','',11);
$pdf->Cell(95,8,'Receipt #: '.substr($row['razorpay_order_id'], -10),0,0,'L');
$pdf->Cell(95,8,'Date: '.date('d M Y', strtotime($row['created_at'])),0,1,'R');

// Donor Info
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(0,8,'Donor Information',0,1,'C',true);

$pdf->SetFont('Arial','',11);
$pdf->Cell(50,8,'Full Name:',0,0);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$row['name'],0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(50,8,'Email:',0,0);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$row['email'],0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(50,8,'Contact:',0,0);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$row['contact'],0,1);

$pdf->SetFont('Arial','',11);
$pdf->Cell(50,8,'Nationality:',0,0);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,$row['nationality'],0,1);

// Payment Info
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(230,230,230);
$pdf->Cell(0,8,'Payment Details',0,1,'C',true);

$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor($successColor[0], $successColor[1], $successColor[2]);
$pdf->Cell(0,10,'INR '.number_format($row['amount'],2),0,1,'C');

$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,8,'Payment ID: '.$row['razorpay_payment_id'],0,1);
$pdf->Cell(0,8,'Order ID: '.$row['razorpay_order_id'],0,1);
$pdf->Cell(0,8,'Status: '.ucfirst($row['status']),0,1);
$pdf->Cell(0,8,'Payment Date: '.date('d M Y H:i', strtotime($row['created_at'])),0,1);

// Thank You + Disclaimer inline
$pdf->Ln(10);
$pdf->SetFont('Arial','B',14);
$pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Cell(0,10,'Thank You for Your Generosity!',0,1,'C');

$pdf->SetFont('Arial','',11);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
$pdf->MultiCell(0,8,"Your contribution makes a difference in saving lives and supporting communities in need around the world.");

$pdf->SetFont('Arial','I',9);
$pdf->SetTextColor(120,120,120);
$pdf->MultiCell(0,8,"This is a computer-generated receipt and does not require a signature.\nFor inquiries, contact: supportiran@great-minds-inc.net",0,'C');

// Output
ob_clean();
$pdf->Output('D','IFRC_Donation_Receipt_'.$orderId.'.pdf');
?>
