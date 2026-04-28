<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Please login first.'); window.location.href='login.html';</script>";
  exit;
}

$con = mysqli_connect('localhost', 'root', '', 'medocard');



$user_id = $_SESSION['user_id'];

// ✅ Fetch latest order ID
$orderStmt = $con->prepare("SELECT order_id FROM razorpay_orders WHERE user_id=? ORDER BY id DESC LIMIT 1");
$orderStmt->bind_param("i", $user_id);
$orderStmt->execute();
$orderRes = $orderStmt->get_result();
$orderRow = $orderRes->fetch_assoc();
$orderId = $orderRow['order_id'] ?? '';
$orderStmt->close();


// ✅ Fetch user details (all serials + referral code)
$stmt = $con->prepare("SELECT 
  name, card_type, purchase_date, ref_code,
  serial_rx_medo_card, serial_rx_medo_family_card, serial_rx_medo_youngshield_card,
  serial_rx_medo_citizencare_card, serial_rx_medo_guardiancare_card, serial_rx_medo_seniorshield_card,
  serial_rx_medo_familysecure_card, serial_rx_medo_topup_card, serial_rx_medo_family_topup_card
  FROM mydata WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$con->close();


$name          = $user['name'];
$card_type     = $user['card_type'];
$purchase_date = $user['purchase_date'];
$ref_code      = $user['ref_code'];   // ✅ NEW

// ✅ Generate referral link
$referral_link = "https://rxmedocard.com/signup.php?ref=" . urlencode($ref_code);
  

// ✅ Pick correct serial based on card type
$serialMap = [  
  'RX Medo Card'               => $user['serial_rx_medo_card'],
  'RX Medo Family Card'        => $user['serial_rx_medo_family_card'],
  'RX Medo YoungShield Card'   => $user['serial_rx_medo_youngshield_card'],
  'RX Medo CitizenCare Card'   => $user['serial_rx_medo_citizencare_card'],
  'RX Medo GuardianCare Card'  => $user['serial_rx_medo_guardiancare_card'],
  'RX Medo SeniorShield Card'  => $user['serial_rx_medo_seniorshield_card'],
  'RX Medo FamilySecure Card'  => $user['serial_rx_medo_familysecure_card'],
  'RX Medo Top-Up Card'        => $user['serial_rx_medo_topup_card'],
  'RX Medo Family Top-Up Card' => $user['serial_rx_medo_family_topup_card']
];


$card_no = $serialMap[$card_type] ?? '';

// ✅ Validity calculation
if (!empty($purchase_date)) {
  $startDate = date('d-m-Y', strtotime($purchase_date));
  $endDate   = date('d-m-Y', strtotime("+1 year -1 day", strtotime($purchase_date)));
  $validityText = "1 Year ($startDate to $endDate)";
} else {
  $validityText = "No validity date available.";
}
?>

<!-- ✅ Now show referral link anywhere in your HTML dashboard

<h3>Your Referral Link</h3>
<input type="text" value="<?php echo $referral_link; ?>" readonly style="width:100%; padding:8px;">

<p>Share this link with others. When they sign up, your name will auto‑fill in their reference field.</p>
 -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RX Medocard | Dashboard</title>
  <link rel="stylesheet" href="dashboard.css">

  <link rel="icon" href="images/white logo.png">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Driver.js CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    :root {
      --primary-color: #0d6efd;
      --primary-dark: #0a58ca;
      --secondary-color: #6c757d;
      --success-color: #198754;
      --warning-color: #ffc107;
      --danger-color: #dc3545;
      --light-bg: #f5f7fb;
      --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: var(--light-bg);
      font-family: "Poppins", sans-serif;
      overflow-x: hidden;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    ::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #0d6efd 0%, #0a58ca 50%, #084298 100%);
      color: white;
      position: fixed;
      left: 0;
      top: 0;
      padding: 25px 0;
      overflow-y: auto;
      box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
      z-index: 1500;
      transition: var(--transition-smooth);
    }

    .sidebar .logo-box {
      text-align: center;
      margin-bottom: 30px;
      padding: 0 20px;
    }

    .sidebar .logo-box img {
      height: 110px;
      width: 130px;
      border-radius: 30%;
    }

    .sidebar .logo-box img:hover {
      transform: scale(1.05) rotate(5deg);
      border-color: white;
    }

    .sidebar-nav {
      padding: 0 15px;
    }

    .sidebar a,
    .sidebar button.menu-btn {
      color: white;
      font-size: 14px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      border-radius: 12px;
      margin: 6px 0;
      transition: var(--transition-smooth);
      background: transparent;
      border: none;
      text-align: left;
      width: 100%;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .sidebar a::before,
    .sidebar button.menu-btn::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 0;
      background: rgba(255, 255, 255, 0.2);
      transition: var(--transition-smooth);
      border-radius: 12px;
    }

    .sidebar a:hover::before,
    .sidebar button.menu-btn:hover::before,
    .sidebar a.active::before,
    .sidebar button.menu-btn.active::before {
      width: 100%;
    }

    .sidebar a:hover,
    .sidebar button.menu-btn:hover {
      transform: translateX(5px);
    }

    .sidebar a.active,
    .sidebar button.menu-btn.active {
      background: rgba(255, 255, 255, 0.25);
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .menu-icon {
      font-size: 18px;
      width: 24px;
      text-align: center;
    }

    .sidebar-divider {
      height: 1px;
      background: rgba(255, 255, 255, 0.2);
      margin: 15px 20px;
    }

    /* Tour Button in Sidebar */
    .tour-btn-sidebar {
      background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%) !important;
      color: #000 !important;
      font-weight: 600 !important;
      margin-top: 10px;
    }

    .tour-btn-sidebar:hover {
      box-shadow: 0 4px 20px rgba(255, 193, 7, 0.5) !important;
    }



    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 30px;
      min-height: 100vh;
      transition: var(--transition-smooth);
    }

    /* Welcome Banner */
    .welcome-banner {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      padding: 30px;
      color: white;
      margin-bottom: 25px;
      position: relative;
      overflow: hidden;
    }

    .welcome-banner::before {
      content: '';
      position: absolute;
      right: -50px;
      top: -50px;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }

    .welcome-banner::after {
      content: '';
      position: absolute;
      right: 50px;
      bottom: -30px;
      width: 100px;
      height: 100px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
    }

    .welcome-banner h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .welcome-banner p {
      opacity: 0.9;
      font-size: 15px;
    }

    .content-section {
      display: none;
      animation: fadeSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .content-section.active {
      display: block;
    }

    @keyframes fadeSlideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Fade-in Animations */
    .fade-in {
      animation: fadeIn 0.6s ease forwards;
      opacity: 0;
    }

    .fade-in-delay-1 {
      animation: fadeIn 0.7s ease forwards 0.1s;
      opacity: 0;
    }

    .fade-in-delay-2 {
      animation: fadeIn 0.8s ease forwards 0.2s;
      opacity: 0;
    }

    .fade-in-delay-3 {
      animation: fadeIn 0.9s ease forwards 0.3s;
      opacity: 0;
    }

    .fade-in-delay-4 {
      animation: fadeIn 1s ease forwards 0.4s;
      opacity: 0;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(15px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modern card section styling */
    .modern-card-section {
      background: white;
      padding: 30px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      margin-bottom: 25px;
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: var(--transition-smooth);
    }

    .modern-card-section:hover {
      box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
      transform: translateY(-2px);
    }

    .info-box {
      background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 100%);
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 20px;
      border-left: 4px solid var(--primary-color);
    }

    .info-box p {
      margin-bottom: 8px;
      font-size: 15px;
    }

    .info-box p:last-child {
      margin-bottom: 0;
    }

    .services-box {
      background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
      padding: 25px;
      border-radius: 16px;
      margin-top: 20px;
      border: 1px solid #e8ecf4;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .services-box h5 {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .services-box h5::before {
      content: '✨';
    }

    .services-box ul {
      list-style: none;
      padding-left: 0;
    }

    .services-box ul li {
      padding: 10px 0;
      font-size: 14px;
      color: #444;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: var(--transition-smooth);
    }

    .services-box ul li:hover {
      transform: translateX(5px);
      color: var(--primary-color);
    }

    /* Modern Buttons */
    .modern-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 24px;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-smooth);
      border: none;
      cursor: pointer;
      font-size: 14px;
    }

    .primary-btn {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    }

    .primary-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.4);
      color: white;
    }

    .secondary-btn {
      background: linear-gradient(135deg, #e8ecff 0%, #f0f4ff 100%);
      color: #0d6efd;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .secondary-btn:hover {
      background: linear-gradient(135deg, #d0d9ff 0%, #e0e8ff 100%);
      transform: translateY(-2px);
      color: #0d6efd;
    }

    .button-group {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
    }

    /* Referral Box */
    .referral-box {
      background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
      padding: 20px;
      border-radius: 15px;
      margin-top: 20px;
      border: 1px solid #ffc107;
    }

    .referral-box h4 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 10px;
      color: #856404;
    }

    .referral-box input {
      width: 100%;
      padding: 12px;
      border: 2px solid #ffc107;
      border-radius: 10px;
      font-size: 14px;
      background: white;
    }

    .referral-box p {
      font-size: 13px;
      color: #856404;
      margin-top: 10px;
    }

    /* Services Card Wrapper */
    .services-card-wrapper {
      display: flex;
      justify-content: space-between;
      gap: 40px;
      align-items: center;
      margin-top: 20px;
    }

    /* Image Slider */
    .image-slider {
      width: 355px;
      height: 220px;
      position: relative;
      overflow: hidden;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .slide {
      width: 100%;
      height: 100%;

      position: absolute;
      top: 0;
      left: 0;
      opacity: 0;
      animation: fadeSlide 12s infinite;
      border-radius: 16px;
    }

    .slide:nth-child(1) {
      animation-delay: 0s;
    }

    .slide:nth-child(2) {
      animation-delay: 3s;
    }

    .slide:nth-child(3) {
      animation-delay: 6s;
    }

    .slide:nth-child(4) {
      animation-delay: 9s;
    }

    @keyframes fadeSlide {
      0% {
        opacity: 0;
        transform: scale(1.05);
      }

      10% {
        opacity: 1;
        transform: scale(1);
      }

      25% {
        opacity: 1;
        transform: scale(1);
      }

      35% {
        opacity: 0;
        transform: scale(0.95);
      }

      100% {
        opacity: 0;
      }
    }

    /* Card Grid for Card Details */
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 25px;
      margin-top: 25px;
    }

    .card-item {
      background: white;
      border-radius: 16px;
      padding: 25px;
      border: 2px solid #e8ecf4;
      transition: var(--transition-smooth);
      position: relative;
      overflow: hidden;
    }

    .card-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #0d6efd, #6610f2);
    }

    .card-item:hover {
      border-color: var(--primary-color);
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(13, 110, 253, 0.15);
    }

    .card-item h4 {
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin-bottom: 12px;
    }

    .card-item p {
      font-size: 14px;
      color: #666;
      margin-bottom: 8px;
    }

    .view-btn {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-smooth);
      margin-top: 15px;
    }

    .view-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 5px 20px rgba(13, 110, 253, 0.3);
    }

    /* Family Plans */
    .family-plans-card {
      background: white;
      padding: 30px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
    }

    .section-title {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 25px;
      color: #222;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .plan-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .plan-item {
      display: flex;
      align-items: center;
      gap: 20px;
      background: linear-gradient(135deg, #f5f7ff 0%, #ffffff 100%);
      padding: 20px;
      border-radius: 16px;
      margin-bottom: 15px;
      transition: var(--transition-smooth);
      border: 2px solid transparent;
      cursor: pointer;
    }

    .plan-item:hover {
      border-color: var(--primary-color);
      transform: translateX(10px);
      box-shadow: 0 10px 30px rgba(13, 110, 253, 0.1);
    }

    .plan-icon {
      font-size: 36px;
      background: linear-gradient(135deg, #e8ecff 0%, #f0f4ff 100%);
      padding: 15px;
      border-radius: 50%;
    }

    .plan-item h4 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: #333;
    }

    .plan-item p {
      margin: 5px 0 0;
      font-size: 14px;
      color: #666;
    }

    /* Hospital Cards */
    .hospital-cards-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 25px;
    }

    .hospital-card {
      background: white;
      border-radius: 16px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      transition: var(--transition-smooth);
    }

    .hospital-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    }

    .hospital-card-img-container {
      position: relative;
      width: 100%;
      height: 200px;
      overflow: hidden;
    }

    .hospital-card-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: var(--transition-smooth);
    }

    .hospital-card:hover .hospital-card-img {
      transform: scale(1.1);
    }

    .hospital-card-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 80px;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.5));
    }

    .hospital-card-content {
      padding: 25px;
    }

    .hospital-card-title {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 10px;
      color: #333;
    }

    .hospital-card-location {
      font-size: 14px;
      color: #666;
      margin-bottom: 15px;
      display: flex;
      align-items: flex-start;
      gap: 8px;
    }

    .hospital-card-location i {
      color: var(--primary-color);
      margin-top: 2px;
    }

    .hospital-card-description {
      font-size: 14px;
      color: #555;
      margin-bottom: 15px;
      line-height: 1.6;
    }

    .hospital-card-features {
      list-style: none;
      padding: 0;
      margin-bottom: 20px;
    }

    .hospital-card-features li {
      font-size: 13px;
      color: #555;
      padding: 5px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .hospital-card-features li::before {
      content: "✓";
      color: var(--success-color);
      font-weight: bold;
    }

    .hospital-card-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      transition: var(--transition-smooth);
    }

    .hospital-card-btn:hover {
      transform: translateX(5px);
      box-shadow: 0 5px 20px rgba(13, 110, 253, 0.3);
      color: white;
    }

    /* Service Sections */
    .hospital-info {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      padding: 40px;
      gap: 40px;
      background: white;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      margin-bottom: 30px;
    }

    .hospital-info img {
      max-width: 400px;
      width: 100%;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      transition: var(--transition-smooth);
    }

    .hospital-info img:hover {
      transform: scale(1.02);
    }

    .info-text {
      max-width: 500px;
    }

    .info-text h2 {
      color: var(--primary-color);
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .info-text p {
      color: #555;
      line-height: 1.7;
      margin-bottom: 15px;
    }

    .discount {
      background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
      padding: 15px 20px;
      border-radius: 12px;
      border-left: 4px solid #ffc107;
      margin-bottom: 20px;
    }

    .appointment-btn {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .appointment-btn a {
      color: white;
      text-decoration: none;
    }

    .appointment-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    /* Recommended Section */
    .recommended {
      margin-top: 30px;
    }

    .recommended h2 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 25px;
      color: #333;
    }

    .hospital-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 25px;
    }

    .hospital-box {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      transition: var(--transition-smooth);
    }

    .hospital-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .hospital-box img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .hospital-box h3 {
      padding: 15px 20px 5px;
      font-size: 18px;
      font-weight: 600;
    }

    .hospital-box p {
      padding: 0 20px 10px;
      font-size: 14px;
      color: #666;
    }

    .offer {
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: #155724;
      padding: 12px 20px;
      font-weight: 600;
      font-size: 14px;
    }

    /* Contact Section */
    .contact {
      text-align: center;
      padding: 40px;
      background: white;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      margin: 30px 0;
    }

    .contact h2 {
      font-size: 24px;
      margin-bottom: 20px;
      color: #333;
    }

    .call-btn {
      background: linear-gradient(135deg, #198754 0%, #157347 100%);
      color: white;
      border: none;
      padding: 15px 35px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .call-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 25px rgba(25, 135, 84, 0.3);
    }

    /* Form Section */
    .form-section {
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      max-width: 1000px;
      margin: 30px auto;
    }

    .form-section h3 {
      text-align: center;
      color: var(--primary-color);
      margin-bottom: 25px;
      font-size: 22px;
      font-weight: 700;
    }

    .form-section form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .form-section input,
    .form-section textarea,
    .form-section select {
      padding: 14px 18px;
      border: 2px solid #e8ecf4;
      border-radius: 12px;
      font-size: 15px;
      transition: var(--transition-smooth);
      background: #f8f9fa;
    }

    .form-section input:focus,
    .form-section textarea:focus,
    .form-section select:focus {
      outline: none;
      border-color: var(--primary-color);
      background: white;
      box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .form-section button[type="submit"] {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      padding: 16px;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .form-section button[type="submit"]:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    /* Book Services Form */
    .form-container {
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      max-width: 1000px;
      margin: auto;
    }

    .form-container h2 {
      text-align: center;
      margin-bottom: 30px;
      color: var(--primary-color);
      font-size: 26px;
      font-weight: 700;
    }

    .form-container label {
      font-weight: 600;
      margin-bottom: 8px;
      display: block;
      color: #333;
    }

    .form-container input,
    .form-container select {
      width: 100%;
      padding: 14px;
      border: 2px solid #e8ecf4;
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 15px;
      transition: var(--transition-smooth);
    }

    .form-container input:focus,
    .form-container select:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .form-container button {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .form-container button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    /* Dropdown Custom */
    .dropdown {
      position: relative;
      margin-bottom: 20px;
    }

    .dropdown-btn {
      width: 100%;
      padding: 14px;
      border: 2px solid #e8ecf4;
      background: #f8f9fa;
      cursor: pointer;
      text-align: left;
      border-radius: 12px;
      transition: var(--transition-smooth);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .dropdown-btn::after {
      content: '▼';
      font-size: 12px;
      color: #666;
    }

    .dropdown-btn:hover {
      border-color: var(--primary-color);
    }

    .dropdown-content {
      display: none;
      position: absolute;
      width: 100%;
      border: 2px solid #e8ecf4;
      background: white;
      z-index: 1000;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      overflow: hidden;
      margin-top: 5px;
    }

    .dropdown-header {
      padding: 12px;
      border-bottom: 1px solid #e8ecf4;
      background: #f8f9fa;
    }

    .dropdown-header input {
      width: 100%;
      padding: 10px;
      border: 2px solid #e8ecf4;
      border-radius: 8px;
      margin-bottom: 0 !important;
    }

    .dropdown-list {
      max-height: 200px;
      overflow-y: auto;
    }

    .dropdown-list div {
      padding: 12px 15px;
      cursor: pointer;
      transition: var(--transition-smooth);
      border-bottom: 1px solid #f0f0f0;
    }

    .dropdown-list div:last-child {
      border-bottom: none;
    }

    .dropdown-list div:hover {
      background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 100%);
      color: var(--primary-color);
    }

    /* Upload Section */
    .upload-section {
      background: white;
      padding: 35px;
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      max-width: 700px;
      margin: auto;
    }

    .upload-section h2 {
      color: #333;
      margin-bottom: 10px;
    }

    .upload-section>p {
      color: #666;
      margin-bottom: 25px;
    }

    .file-upload {
      margin-bottom: 25px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 12px;
      border: 2px dashed #e8ecf4;
      transition: var(--transition-smooth);
    }

    .file-upload:hover {
      border-color: var(--primary-color);
      background: #f0f7ff;
    }

    .file-upload label {
      display: block;
      font-weight: 600;
      margin-bottom: 10px;
      color: #333;
    }

    .file-upload input[type="file"] {
      width: 100%;
    }

    .file-upload img {
      margin-top: 10px;
      border-radius: 8px;
      border: 2px solid #e8ecf4;
    }

    .btn-upload {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-smooth);
      width: 100%;
    }

    .btn-upload:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(5px);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      width: 90%;
      max-width: 500px;
      padding: 35px;
      border-radius: 20px;
      animation: modalPop 0.3s ease-out;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
      max-height: 85vh;
      overflow-y: auto;
    }

    @keyframes modalPop {
      from {
        transform: scale(0.8) translateY(50px);
        opacity: 0;
      }

      to {
        transform: scale(1) translateY(0);
        opacity: 1;
      }
    }

    .modal-content h2 {
      color: var(--primary-color);
      margin-bottom: 20px;
      font-size: 22px;
    }

    .modal-content h3 {
      color: #333;
      font-size: 16px;
      margin: 20px 0 10px;
    }

    .close-btn {
      float: right;
      font-size: 28px;
      cursor: pointer;
      color: #666;
      transition: var(--transition-smooth);
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .close-btn:hover {
      background: #f0f0f0;
      color: #333;
    }

    .services-list {
      padding-left: 20px;
    }

    .services-list li {
      margin: 10px 0;
      font-size: 14px;
      color: #555;
      line-height: 1.6;
    }

    /* Radiology Box */
    .radiology-box {
      background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 100%);
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      max-width: 450px;
      text-align: center;
      transition: var(--transition-smooth);
      border: 2px solid transparent;
    }

    .radiology-box:hover {
      transform: translateY(-5px);
      border-color: var(--primary-color);
    }

    .radiology-box h1 {
      color: var(--primary-color);
      font-size: 22px;
      margin-bottom: 15px;
    }

    .radiology-box p {
      color: #555;
      margin-bottom: 20px;
    }

    .radiology-box button {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .radiology-box button:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    }

    /* Checkbox Group */
    .checkbox-group {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 10px;
      margin-top: 10px;
    }

    .checkbox-group label {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 8px;
      cursor: pointer;
      transition: var(--transition-smooth);
      font-weight: normal;
    }

    .checkbox-group label:hover {
      background: #e8f4fd;
    }

    .checkbox-group input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: var(--primary-color);
    }

    /* Mobile Header */
    .mobile-header {
      display: none;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background: white;
      border-bottom: 1px solid #e8ecf4;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 2000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .mobile-logo {
      height: 50px;
      border-radius: 50%;
    }

    .hamburger {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      font-size: 24px;
      border: none;
      padding: 10px 15px;
      border-radius: 10px;
      cursor: pointer;
      transition: var(--transition-smooth);
    }

    .hamburger:hover {
      transform: scale(1.05);
    }

    /* Floating Tour Button */
    .floating-tour-btn {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
      color: #000;
      border: none;
      padding: 18px 25px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 15px;
      cursor: pointer;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 8px 30px rgba(255, 193, 7, 0.4);
      transition: var(--transition-smooth);
      animation: pulse 2s infinite;
    }

    .floating-tour-btn:hover {
      transform: scale(1.08);
      box-shadow: 0 12px 40px rgba(255, 193, 7, 0.5);
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.5);
      }

      70% {
        box-shadow: 0 0 0 15px rgba(255, 193, 7, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
      }
    }

    .floating-tour-btn i {
      font-size: 20px;
    }

    /* Quick Stats */
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 16px;
      box-shadow: var(--card-shadow);
      text-align: center;
      transition: var(--transition-smooth);
      border: 2px solid transparent;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary-color);
    }

    .stat-card i {
      font-size: 40px;
      color: var(--primary-color);
      margin-bottom: 15px;
    }

    .stat-card h3 {
      font-size: 28px;
      font-weight: 700;
      color: #333;
      margin-bottom: 5px;
    }

    .stat-card p {
      font-size: 14px;
      color: #666;
    }

    /* Responsive */
    @media (min-width: 769px) {
      .sidebar {
        left: 0 !important;
      }

      .mobile-header {
        display: none;
      }

      .slide {

        width: 100%;

      }
    }

    @media (max-width: 768px) {
      .mobile-header {
        display: flex;
      }

      .sidebar {
        left: -300px;
        top: 70px;
        height: calc(100vh - 70px);
      }

      .sidebar.open {
        left: 0;
      }

      .main-content {
        margin-left: 0;
        margin-top: 70px;
        padding: 20px;
      }

      .services-card-wrapper {
        flex-direction: column;
      }

      .image-slider {
        width: 100%;
        max-width: 300px;
      }

      .hospital-info {
        flex-direction: column;
        text-align: center;
      }

      .card-grid,
      .hospital-grid,
      .hospital-cards-container {
        grid-template-columns: 1fr;
      }

      .button-group {
        flex-direction: column;
      }

      .modern-btn {
        width: 100%;
        justify-content: center;
      }

      .floating-tour-btn {
        bottom: 20px;
        right: 20px;
        padding: 15px 20px;
        font-size: 14px;
      }

      .quick-stats {
        grid-template-columns: 1fr 1fr;
      }

      .logo-box {
        display: none;
      }
    }

    @media (max-width: 480px) {
      .quick-stats {
        grid-template-columns: 1fr;
      }

      .welcome-banner h2 {
        font-size: 22px;
      }

      .section-title {
        font-size: 20px;
      }
    }

    /* Driver.js Custom Styles */
    .driver-popover {
      border-radius: 16px !important;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2) !important;
    }

    .driver-popover-title {
      font-weight: 700 !important;
      color: var(--primary-color) !important;
    }

    .driver-popover-description {
      color: #555 !important;
      line-height: 1.6 !important;
    }

    .driver-popover-navigation-btns {
      gap: 10px !important;
    }

    .driver-popover-next-btn {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
      border-radius: 8px !important;
      padding: 10px 20px !important;
    }

    .driver-popover-prev-btn {
      background: #f0f0f0 !important;
      color: #333 !important;
      border-radius: 8px !important;
      padding: 10px 20px !important;
    }
  </style>
</head>

<body>

  <!-- MOBILE HEADER -->
  <div class="mobile-header">
    <img src="treelogo.jpg" class="mobile-logo" alt="Logo">
    <button class="hamburger" onclick="toggleSidebar()" id="hamIcon">
      <i class="bi bi-list"></i>
    </button>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar" id="sidebar">
    <div class="logo-box">
      <img src="treelogo.jpg" alt="RX Medocard Logo">
    </div>

    <nav class="sidebar-nav">
      <button class="menu-btn" id="nav-profile" onclick="showSection('dashboard', this)">
        <span class="menu-icon"><i class="bi bi-person-circle"></i></span>
        Profile
      </button>
      <button class="menu-btn" id="nav-cards" onclick="showSection('cards', this)">
        <span class="menu-icon"><i class="bi bi-credit-card-2-front"></i></span>
        Card Details
      </button>
      <button class="menu-btn" id="nav-services" onclick="showSection('Services', this)">
        <span class="menu-icon"><i class="bi bi-calendar-check"></i></span>
        Book Services
      </button>
      <button class="menu-btn" id="nav-family" onclick="showSection('membership', this)">
        <span class="menu-icon"><i class="bi bi-people"></i></span>
        Family Plans
      </button>

      <div class="sidebar-divider"></div>

      <button class="menu-btn" id="nav-hospitals" onclick="showSection('hospitals', this)">
        <span class="menu-icon"><i class="bi bi-hospital"></i></span>
        Hospitals
      </button>
      <button class="menu-btn" id="nav-pharmacy" onclick="showSection('pharmacy', this)">
        <span class="menu-icon"><i class="bi bi-capsule"></i></span>
        Pharmacy
      </button>
      <button class="menu-btn" id="nav-diagnostic" onclick="showSection('diagnostic', this)">
        <span class="menu-icon"><i class="bi bi-clipboard2-pulse"></i></span>
        Diagnostic
      </button>
      <button class="menu-btn" id="nav-pathology" onclick="showSection('pathology', this)">
        <span class="menu-icon"><i class="bi bi-droplet"></i></span>
        Pathology
      </button>
      <button class="menu-btn" id="nav-radiology" onclick="showSection('rediology', this)">
        <span class="menu-icon"><i class="bi bi-radioactive"></i></span>
        Radiology
      </button>
      <button class="menu-btn" id="nav-opd" onclick="showSection('OPD', this)">
        <span class="menu-icon"><i class="bi bi-building"></i></span>
        OPD
      </button>

      <div class="sidebar-divider"></div>

      <button class="menu-btn" id="nav-payment" onclick="showSection('payment', this)">
        <span class="menu-icon"><i class="bi bi-wallet2"></i></span>
        Payment History
      </button>
      <button class="menu-btn" id="nav-documents" onclick="showSection('documents', this)">
        <span class="menu-icon"><i class="bi bi-cloud-upload"></i></span>
        Upload Documents
      </button>

      <div class="sidebar-divider"></div>


      <!-- Tour Button in Sidebar -->
      <button class="menu-btn tour-btn-sidebar" onclick="startTour()">
        <span class="menu-icon"><i class="bi bi-compass"></i></span>
        Take a Tour
      </button>

      <button class="menu-btn btn btn-danger"
        style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); margin-top: 10px;"
        onclick="return confirmLogout()">
        <span class="menu-icon"><i class="bi bi-box-arrow-right"></i></span>
        Logout
      </button>
    </nav>
  </div>

  <!-- FLOATING TOUR BUTTON -->
  <button class="floating-tour-btn" onclick="startTour()" id="floatingTourBtn">
    <i class="bi bi-compass"></i>
    Take a Tour
  </button>

  <!-- MAIN CONTENT -->
  <div class="main-content">

    <!-- DASHBOARD (Profile) -->
    <div id="dashboard" class="content-section active">

      <!-- Welcome Banner -->
      <div class="welcome-banner fade-in" id="welcome-banner">
        <h2>👋 Welcome,
          <?php echo htmlspecialchars($name); ?>!
        </h2>
        <p>Manage your health card, book appointments, and access all healthcare services from one place.</p>
      </div>

      <!-- Quick Stats -->
      <!-- <div class="quick-stats fade-in-delay-1" id="quick-stats">
        <div class="stat-card">
          <i class="bi bi-shield-check"></i>
          <h3>Active</h3>
          <p>Card Status</p>
        </div>
        <div class="stat-card">
          <i class="bi bi-calendar-check"></i>
          <h3>0</h3>
          <p>Appointments</p>
        </div>
        <div class="stat-card">
          <i class="bi bi-hospital"></i>
          <h3>50+</h3>
          <p>Partner Hospitals</p>
        </div>
        <div class="stat-card">
          <i class="bi bi-percent"></i>
          <h3>Up to 50%</h3>
          <p>Discounts</p>
        </div>
      </div> -->

      <div class="modern-card-section" id="profile-card">
        <h3 class="fade-in" style="font-size: 22px; font-weight: 700; margin-bottom: 20px;">
          <i class="bi bi-person-badge" style="color: var(--primary-color);"></i>
          Your Profile
        </h3>

        <div class="info-box fade-in-delay-1" id="card-info">
          <p><strong><i class="bi bi-credit-card"></i> Selected Card:</strong>
            <?php echo htmlspecialchars($card_type); ?>
          </p>
          <p><strong><i class="bi bi-heart-pulse"></i> Type:</strong> Health Insurance</p>
          <p><strong><i class="bi bi-calendar-range"></i> Validity:</strong>
            <?php echo $validityText; ?>
          </p>
        </div>

        <!-- Referral Box -->
        <div class="referral-box fade-in-delay-2" id="referral-box">
          <h4><i class="bi bi-share"></i> Your Referral Link</h4>
          <input type="text" value="<?php echo $referral_link; ?>" readonly
            onclick="this.select(); document.execCommand('copy'); alert('Link copied!');">
          <p><i class="bi bi-info-circle"></i> Share this link with others. When they sign up, your name will auto-fill
            in their reference field.</p>
        </div>

        <div class="button-group fade-in-delay-3" id="download-buttons">
          <a href="generate_card.php" class="modern-btn primary-btn">
            <i class="bi bi-download"></i> Download RX Medo Card
          </a>
          <a href="download_receipt.php?order_id=<?php echo $orderId; ?>" class="modern-btn secondary-btn">
            <i class="bi bi-receipt"></i> Download Receipt
          </a>
        </div>

        <div class="services-box fade-in-delay-4" id="services-preview">
          <h5>Included Services</h5>

          <div class="services-card-wrapper">
            <ul class="services-list">
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Waiver of First Year
                Exclusion</li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Waiver of 30 Days Waiting
                Period</li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Waiver of First Two Year
                Exclusion</li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Cover for Pre-Existing
                Diseases</li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> ₹5 Lakh Insurance + ₹50
                Lakh Accidental Cover</li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Free Online Consultation
              </li>
              <li><i class="bi bi-check-circle-fill" style="color: var(--success-color);"></i> Up to <b>20%</b>
                long-term renewal discount</li>
            </ul>

            <div class="image-slider" id="card-preview">
              <img src="yellowcardfront.jpg" class="slide" alt="Card Front">
              <img src="yellowcardback.jpg" class="slide" alt="Card Back">
              <img src="bluecardfront.jpg" class="slide" alt="Blue Card Front">
              <img src="bluecardback .jpg" class="slide" alt="Blue Card Back">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CARDS SECTION -->
    <div id="cards" class="content-section">
      <div class="modern-card-section" id="cards-section">
        <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 10px;">
          <i class="bi bi-credit-card-2-front" style="color: var(--primary-color);"></i>
          Available RX Medo Cards
        </h3>
        <p style="color: #666; margin-bottom: 20px;">Choose the card that best suits your healthcare needs</p>

        <div class="card-grid">
          <!-- Card Items -->
          <div class="card-item">
            <h4><i class="bi bi-card-heading"></i> RX Medo Card</h4>
            <p><strong>For:</strong> Individual | No age limit</p>
            <p><strong>Features:</strong> Basic RX Medo benefits</p>
            <p><strong>Price:</strong> ₹3,000</p>
            <button onclick="showServices('rxmedo')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-people"></i> RX Medo Family Card</h4>
            <p><strong>For:</strong> Family (up to 4 members)</p>
            <p><strong>Features:</strong> All RX Medo benefits for family</p>
            <p><strong>Price:</strong> ₹8,000</p>
            <button onclick="showServices('family')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-person-check"></i> RX Medo YoungShield</h4>
            <p><strong>For:</strong> Age 18–35</p>
            <p><strong>Features:</strong> ₹5 Lakh insurance + pre-existing cover</p>
            <p><strong>Price:</strong> ₹7,000</p>
            <button onclick="showServices('youngshield')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-person-badge"></i> RX Medo CitizenCare</h4>
            <p><strong>For:</strong> Age 36–45</p>
            <p><strong>Features:</strong> ₹5 Lakh insurance + pre-existing cover</p>
            <p><strong>Price:</strong> ₹10,000</p>
            <button onclick="showServices('citizencare')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-shield-check"></i> RX Medo GuardianCare</h4>
            <p><strong>For:</strong> Age 46–55</p>
            <p><strong>Features:</strong> ₹5 Lakh insurance + pre-existing cover</p>
            <p><strong>Price:</strong> ₹15,000</p>
            <button onclick="showServices('guardiancare')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-heart"></i> RX Medo SeniorShield</h4>
            <p><strong>For:</strong> Age 56–65</p>
            <p><strong>Features:</strong> ₹5 Lakh insurance + pre-existing cover</p>
            <p><strong>Price:</strong> ₹25,000</p>
            <button onclick="showServices('seniorshield')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-house-heart"></i> RX Medo FamilySecure</h4>
            <p><strong>For:</strong> Family (max age 35)</p>
            <p><strong>Features:</strong> ₹5 Lakh insurance + covers spouse & 4 children</p>
            <p><strong>Price:</strong> ₹15,000</p>
            <button onclick="showServices('familysecure')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-plus-circle"></i> RX Medo Top-Up Card</h4>
            <p><strong>For:</strong> Individual | No age limit</p>
            <p><strong>Features:</strong> ₹40 Lakh top-up for existing insurance holders</p>
            <p><strong>Price:</strong> Starts from ₹3,500</p>
            <button onclick="showServices('topup')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>

          <div class="card-item">
            <h4><i class="bi bi-plus-square"></i> RX Medo Family Top-Up</h4>
            <p><strong>For:</strong> Family | No age limit</p>
            <p><strong>Features:</strong> ₹40 Lakh top-up extended to 4 members</p>
            <p><strong>Price:</strong> Starts from ₹4,000</p>
            <button onclick="showServices('familytopup')" class="view-btn">
              <i class="bi bi-eye"></i> View Services
            </button>
          </div>
        </div>

        <div class="button-group" style="margin-top: 30px;">
          <a href="generate_card.php" class="modern-btn primary-btn">
            <i class="bi bi-download"></i> Download RX Medo Card
          </a>
          <a href="download_receipt.php?order_id=<?php echo $orderId; ?>" class="modern-btn secondary-btn">
            <i class="bi bi-receipt"></i> Download Receipt
          </a>
        </div>
      </div>
    </div>

    <!-- MEMBERSHIP / FAMILY PLANS -->
    <div id="membership" class="content-section">
      <div class="family-plans-card" id="family-plans">
        <h2 class="section-title">
          <i class="bi bi-people-fill" style="color: var(--primary-color);"></i>
          Family Plans
        </h2>

        <ul class="plan-list">
          <li class="plan-item">
            <span class="plan-icon">👨‍👩‍👦</span>
            <div>
              <h4>Basic Plan</h4>
              <p>₹5,000/year • 2 members</p>
            </div>
          </li>

          <li class="plan-item">
            <span class="plan-icon">👨‍👩‍👧‍👦</span>
            <div>
              <h4>Standard Plan</h4>
              <p>₹10,000/year • 4 members</p>
            </div>
          </li>

          <li class="plan-item">
            <span class="plan-icon">👨‍👨‍👦‍👦✨</span>
            <div>
              <h4>Premium Plan</h4>
              <p>₹15,000/year • 6 members + Extra Benefits</p>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- HOSPITALS -->
    <div id="hospitals" class="content-section">
      <div class="modern-card-section">
        <h2 class="section-title">
          <i class="bi bi-hospital" style="color: var(--primary-color);"></i>
          Our Hospital Network
        </h2>
        <p style="color: #666; margin-bottom: 30px;">Access quality healthcare at our partner hospitals with exclusive
          discounts</p>

        <div class="hospital-cards-container" id="hospital-network">
          <!-- MD City Hospital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="image.png" alt="MD City Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">MD City Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Parthala Chowk, PKC-12, Sector-122, Noida
              </div>
              <p class="hospital-card-description">
                Multi-specialty services and emergency care with priority access for Rx Medo Card holders.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Advanced Diagnostics</li>
                <li>24/7 Emergency</li>
              </ul>
              <a href="https://www.google.com/search?q=MD+City+Hospital" target="_blank" class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Sarvodaya Hospital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Sarvodaya Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Greater Noida, India
              </div>
              <p class="hospital-card-description">
                Advanced medical facilities and experienced staff offering seamless care.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Emergency Services</li>
                <li>Modern Infrastructure</li>
              </ul>
              <a href="https://www.google.com/search?q=sarvodaya+hospital" target="_blank" class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- sarvodya hospital faridabad -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="sarvodya sector8.jpg" alt="Sarvodaya Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Sarvodaya Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Sarvodaya Hospital, Sector 8,Faridabad
              </div>
              <p class="hospital-card-description">
                Advanced medical facilities and experienced staff offering seamless care.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Emergency Services</li>
                <li>Modern Infrastructure</li>
              </ul>
              <a href="https://www.google.com/search?q=sarvodaya+hospital+Faridabad" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Sarvodaya Hospital, Sector 19, Faridabad -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="sarvodaya hospital sector19.jpg" alt="Sarvodaya Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Sarvodaya Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Sarvodaya Hospital, Sector 19,Faridabad
              </div>
              <p class="hospital-card-description">
                Advanced medical facilities and experienced staff offering seamless care.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Emergency Services</li>
                <li>Modern Infrastructure</li>
              </ul>
              <a href="https://www.google.com/search?q=sarvodaya+hospital+Faridabad" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>


          <!-- Felix Hospital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="./images/felix.jpg" alt="Felix Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Felix Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Sector-137, Noida
              </div>
              <p class="hospital-card-description">
                Comprehensive healthcare services with modern infrastructure and support.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Advanced Diagnostics</li>
                <li>Expert Doctors</li>
              </ul>
              <a href="https://www.google.com/search?q=Felix+Hospital+Noida" target="_blank" class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- felix hospital Greater Noida -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="felix hospital greter noida.jpg" alt="Felix Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Felix Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Felix Hospital, Greater Noida West, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Comprehensive healthcare services with modern infrastructure and support.
              </p>
              <ul class="hospital-card-features">
                <li>Multi-Specialty Care</li>
                <li>Advanced Diagnostics</li>
                <li>Expert Doctors</li>
              </ul>
              <a href="https://www.google.com/search?q=Felix+Hospital+Noida" target="_blank" class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Max Hospital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="./images/max.webp" alt="Max Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Max Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Saket, New Delhi
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Max+Hospital+Saket+Delhi" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>


          <!-- Yashoda Hospital, Sanjay Nagar, Noida -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="./images/yashodhasanjaynagar.jpeg" alt="Yashoda Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Yashoda Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Sanjay Nagar,Ghaziabad, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Yashoda+Hospital+Sanjay+Nagar+Ghaziabad+New+Delhi"
                target="_blank" class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Yashoda Hospital, Nehru Nagar, Noida -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="./images/yashodhanehrunagar.webp" alt="Yashoda Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Yashoda Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Nehru Nagar, Ghaziabad, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Yashoda+Hospital+Nehru+Nagar+Noida" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Yashoda Hospital, Vasundra, Noida -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="./images/yashodhavasundra.webp" alt="Yashoda Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Yashoda Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Vasundra, Ghaziabad, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Yashoda+Hospital+Vasundra+Noida" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Medanta Hosptital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="images/medanta.jpg" alt="Medanta Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Medanta Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Sector 51, Noida, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Medanta+Hospital+Sector+51+Noida" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- SRS Hosptital -->
          <div class="hospital-card">
            <div class="hospital-card-img-container">
              <img src="images/srs.jpeg" alt="SRS Hospital" class="hospital-card-img">
              <div class="hospital-card-overlay"></div>
            </div>
            <div class="hospital-card-content">
              <h3 class="hospital-card-title">Shri Ram Singh Hospital</h3>
              <div class="hospital-card-location">
                <i class="bi bi-geo-alt-fill"></i> Opp. OIDB, Sector 70, Noida, Uttar Pradesh
              </div>
              <p class="hospital-card-description">
                Specialized treatments and priority appointments for Rx Medo Card members.
              </p>
              <ul class="hospital-card-features">
                <li>World-Class Care</li>
                <li>Advanced Diagnostics</li>
                <li>International Patients</li>
              </ul>
              <a href="https://www.google.com/search?q=Medanta+Hospital+Sector+51+Noida" target="_blank"
                class="hospital-card-btn">
                View Location <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>


        </div>
      </div>
    </div>

    <!-- PHARMACY -->
    <div id="pharmacy" class="content-section">
      <section class="hospital-info">
        <img
          src="https://media.istockphoto.com/photos/modern-hospital-building-picture-id1312706413?b=1&k=20&m=1312706413&s=170667a&w=0&h=VRi3w2E1UqvCCcK-nDV6mH7FhDZoTU9MM2QKSom96X4="
          alt="Hospital Image" />
        <div class="info-text">
          <h2><i class="bi bi-capsule"></i> About Our Pharmacy Services</h2>
          <p>We connect you with top pharmacies offering the best medicines with exclusive discounts and home delivery.
          </p>
          <div class="discount">
            🎉 <strong>Special Offer:</strong> Get <b>20% OFF</b> on select pharmacy purchases this week!
          </div>
          <button class="appointment-btn">
            <a href="#pharmacy-form">Book Appointment</a>
          </button>
        </div>
      </section>

      <section class="recommended">
        <h2><i class="bi bi-star-fill" style="color: #ffc107;"></i> Our Best Recommendations</h2>
        <div class="hospital-grid">
          <div class="hospital-box">
            <img src="image.png" alt="MD City Hospital" />
            <h3>MD City Hospital Pharmacy</h3>
            <p><b>Address:</b> Parthala Chowk, Sector-122, Noida</p>
            <p>Quality medicines with expert guidance.</p>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>Sarvodaya Hospital</h3>
            <p><b>Address:</b> Greater Noida</p>
            <p>Quick appointments.</p>
            <!-- <div class="offer">🔥 20% OFF on OPD</div> -->
          </div>

          <div class="hospital-box">
            <img src="./images/felix.jpg" alt="Felix Hospital" />
            <h3>Felix Hospital</h3>
            <p><b>Address:</b> Sector 137, Noida</p>
            <p>Modern OPD facilities.</p>

          </div>

          <div class="hospital-box">
            <img src="https://pbs.twimg.com/profile_images/1229372089821532160/YP19Pql2.jpg" alt="Aman Medical" />
            <h3>Aman Medical</h3>
            <p>Comprehensive healthcare with modern pharmacy facilities.</p>
          </div>

          <div class="hospital-box">
            <img src="careplus.jpeg" alt="Care Plus Pharmacy" />
            <h3>Care Plus Pharmacy</h3>
            <p>Quality medicines at affordable prices.</p>
          </div>

          <div class="hospital-box">
            <img src="Rx healthcare pharmacy.png" alt="Care Plus Pharmacy" />
            <h3>Rx Healthcare Pharmacy</h3>
            <p>Pharmacy & Health Respiratory Care Medical Devices.</p>
          </div>


        </div>
      </section>

      <section class="contact">
        <h2><i class="bi bi-telephone-fill"></i> Contact Us</h2>
        <a href="tel:+919999307517"><button class="call-btn"><i class="bi bi-telephone"></i> Call Us Now</button></a>
      </section>

      <section class="form-section" id="pharmacy-form">
        <h3><i class="bi bi-capsule"></i> Pharmacy Appointment Form</h3>
        <form id="pharmacyForm" action="submit_pharmacy.php" method="POST" enctype="multipart/form-data">
          <input type="text" name="name" placeholder="Full Name" required />
          <input type="text" name="hospital" placeholder="Pharmacy Name" required />
          <input type="text" name="patient_id" placeholder="Patient ID / Email" required />
          <input type="text" name="contact" placeholder="Contact Number" required />
          <input type="date" name="date" required />
          <input type="text" name="card_number" placeholder="Card Number" />
          <input type="file" name="receipt" accept="image/*,application/pdf" />
          <textarea name="description" rows="3" placeholder="Description or upload prescription above"></textarea>
          <button type="submit">Submit Form</button>
        </form>
      </section>
    </div>

    <!-- DIAGNOSTIC -->
    <div id="diagnostic" class="content-section">
      <section class="hospital-info">
        <img
          src="https://media.istockphoto.com/photos/modern-hospital-building-picture-id1312706413?b=1&k=20&m=1312706413&s=170667a&w=0&h=VRi3w2E1UqvCCcK-nDV6mH7FhDZoTU9MM2QKSom96X4="
          alt="Hospital Image" />
        <div class="info-text">
          <h2><i class="bi bi-clipboard2-pulse"></i> About Our Diagnostic Services</h2>
          <p>We connect you with top diagnostic centers offering accurate tests with exclusive discounts.</p>
          <div class="discount">
            🎉 <strong>Special Offer:</strong> Get <b>30% OFF</b> on diagnostic services this week!
          </div>
          <button class="appointment-btn">
            <a href="#diagnostic-form">Book Appointment</a>
          </button>
        </div>
        <div class="radiology-box">
          <h1><i class="bi bi-radioactive"></i> About Radiology</h1>
          <p>Advanced imaging techniques including X-rays, CT scans, and MRIs for accurate diagnosis.</p>
          <button class="menu-btn" onclick="showSection('rediology', this)">Go to Radiology</button>
        </div>
      </section>

      <section class="recommended">
        <h2><i class="bi bi-star-fill" style="color: #ffc107;"></i> Our Best Recommendations</h2>
        <div class="hospital-grid">
          <div class="hospital-box">
            <img src="image.png" alt="MD City Hospital" />
            <h3>MD City Hospital</h3>
            <p><b>Address:</b> Parthala Chowk, Sector-122, Noida</p>
            <p>Advanced diagnostic facilities.</p>
            <div class="offer">💙 30% OFF on Diagnostics</div>
          </div>

          <div class="hospital-box">
            <img src="./images/felix.jpg" alt="Felix Hospital" />
            <h3>Felix Hospital</h3>
            <p><b>Address:</b> Sector 137, Noida</p>
            <p>Modern diagnostic equipment.</p>
            <div class="offer">💰 20% Discount</div>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>Sarvodaya Hospital</h3>
            <p><b>Address:</b> Greater Noida</p>
            <p>Quick appointments.</p>
            <div class="offer">🔥 20% OFF on Diagnostic</div>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>SRS Hospital</h3>
            <p><b>Address:</b> Opp. OIDB, Sector 70, Noida (Uttar Pradesh)</p>
            <p>Comprehensive healthcare services.</p>
            <div class="offer">🔥 Upto 20% OFF on OPD</div>
          </div>

          
          <div class="hospital-box">
            <img src="https://cdn.siasat.com/wp-content/uploads/2021/08/photo_2021-08-28_16-08-26.jpg"
              alt="Apollo Diagnostic" />
            <h3>Apollo Diagnostic</h3>
            <p>Trusted diagnostic services.</p>
            <div class="offer">💰 15% Discount</div>
          </div>

          <div class="hospital-box">
            <img src="https://images.crunchbase.com/image/upload/c_pad,h_256,w_256,f_auto,q_auto:eco,dpr_1/qrrh7az1avfxkqmlhxxn"
              alt="curelo Diagnostic" />
            <h3>curelo Diagnostic</h3>
            <p>Comprehensive healthcare services with modern Diagnostic facilities and experienced doctors.</p>
            <div class="offer">💰 15% Discount on Diagnostic</div>
          </div>

          <div class="hospital-box">
            <img src="medima.png"
              alt="Medima Diagnostic" />
            <h3>Medimaa Imaging Center</h3>
            <p><b>Address:</b> Ground Floor, Ramshree complex, near Shivalik hospital, Sector 51, Noida.</p>
            <div class="offer">💰 15% Discount on Diagnostic</div>
          </div>

          <div class="hospital-box">
            <img src="images/WhatsApp Image 2025-10-04 at 7.27.15 PM.jpeg"
              alt="Star Diagnostic" />
            <h3>Star Radiology</h3>
            <p>Comprehensive healthcare services with modern Diagnostic facilities and experienced doctors</p>
            <div class="offer">💰 15% Discount on Diagnostic</div>
          </div>

          <div class="hospital-box">
            <img src="redclif.png"
              alt="Redclif" />
            <h3>Redcliffe Lab</h3>
            <p>Comprehensive healthcare services with modern Diagnostic facilities and experienced doctors</p>
            <div class="offer">💰 15% Discount on Diagnostic</div>
          </div>

          <div class="hospital-box">
            <img src="fusion.png"
              alt="Fusion Diagnostic" />
            <h3>Fusion Diagnostic</h3>
            <p>Comprehensive healthcare services with modern Diagnostic facilities and experienced doctors</p>
            <div class="offer">💰 15% Discount on Diagnostic</div>
          </div>
        </div>
      </section>

      <section class="contact">
        <h2><i class="bi bi-telephone-fill"></i> Contact Us</h2>
        <a href="tel:+919999307517"><button class="call-btn"><i class="bi bi-telephone"></i> Call Us Now</button></a>
      </section>

      <section class="form-section" id="diagnostic-form">
        <h3><i class="bi bi-clipboard2-pulse"></i> Diagnostic Appointment Form</h3>
        <form id="diagnosticForm">
          <input type="text" placeholder="Full Name" required />
          <input type="text" placeholder="Diagnostic Center Name" required />
          <input type="text" placeholder="Patient ID / Email" required />
          <input type="text" placeholder="Contact Number" required />
          <input type="date" required />
          <input type="text" placeholder="Card Number" />
          <input type="file" accept="image/*,application/pdf" />
          <textarea rows="3" placeholder="Description or upload prescription above"></textarea>
          <button type="submit">Submit Form</button>
        </form>
      </section>
    </div>

    <!-- PATHOLOGY -->
    <div id="pathology" class="content-section">
      <section class="hospital-info">
        <img
          src="https://media.istockphoto.com/photos/modern-hospital-building-picture-id1312706413?b=1&k=20&m=1312706413&s=170667a&w=0&h=VRi3w2E1UqvCCcK-nDV6mH7FhDZoTU9MM2QKSom96X4="
          alt="Hospital Image" />
        <div class="info-text">
          <h2><i class="bi bi-droplet"></i> About Our Pathology Services</h2>
          <p>Accurate blood tests and lab services with home sample collection available.</p>
          <div class="discount">
            🎉 <strong>Special Offer:</strong> Get <b>20% OFF</b> on pathology tests this week!
          </div>
          <button class="appointment-btn">
            <a href="#pathology-form">Book Appointment</a>
          </button>
        </div>
      </section>

      <section class="recommended">
        <h2><i class="bi bi-star-fill" style="color: #ffc107;"></i> Our Best Recommendations</h2>
        <div class="hospital-grid">
          <div class="hospital-box">
            <img src="image.png" alt="MD City Hospital" />
            <h3>MD City Hospital</h3>
            <p><b>Address:</b> Parthala Chowk, Sector-122, Noida</p>
            <p>Expert doctors available.</p>
            <div class="offer">💙 FREE OPD Consultation</div>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>Sarvodaya Hospital</h3>
            <p><b>Address:</b> Greater Noida</p>
            <p>Quick appointments.</p>
            <div class="offer">🔥 20% OFF on OPD</div>
          </div>

          <div class="hospital-box">
            <img src="./images/felix.jpg" alt="Felix Hospital" />
            <h3>Felix Hospital</h3>
            <p><b>Address:</b> Sector 137, Noida</p>
            <p>Modern OPD facilities.</p>
            <div class="offer">💰 20% Discount</div>
          </div>

          <div class="hospital-box">
            <img src="https://haimedicalcentre.com/wp-content/uploads/2024/09/Polyclinic.jpg" alt="Felix Hospital" />
            <h3>Adrija Polyclinic</h3>

            <p>Modern OPD facilities.</p>
            <div class="offer">Expert Doctors</div>
          </div>

          <div class="hospital-box">
            <img src="images/max.webp" alt="Max Hospital" />
            <h3>Max Hospital</h3>
            <p><b>Address:</b> Vaishali, Ghaziabad, UP</p>
            <p>International Patients</p>
            <div class="offer">Multi-Specialty Care</div>
          </div>

          <div class="hospital-box">
            <img src="images/yashodhasanjaynagar.jpeg" alt="Yashoda Hospital" />
            <h3>Yashoda Hospital</h3>
            <p><b>Address:</b> Ghaziabad, Uttar Pradesh</p>
            <p>internal medicines</p>
            <div class="offer">superspecialities of cancer</div>
          </div>

          <div class="hospital-box">
            <img src="images/srs.jpeg" alt="SRS Hospital" />
            <h3>SRS Hospital</h3>
            <p><b>Address:</b> Opp. OIDB, Sector 70, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>

          <div class="hospital-box">
            <img src="images/medanta.jpg" alt="Medanta Hospital" />
            <h3>Medanta Hospital</h3>
            <p><b>Address:</b> Sector 51, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>
        </div>
      </section>

      <section class="contact">
        <h2><i class="bi bi-telephone-fill"></i> Contact Us</h2>
        <a href="tel:+919999307517"><button class="call-btn"><i class="bi bi-telephone"></i> Call Us Now</button></a>
      </section>

      <section class="form-section" id="pathology-form">
        <h3><i class="bi bi-droplet"></i> Pathology Appointment Form</h3>
        <form id="pathologyForm">
          <input type="text" placeholder="Full Name" required />
          <input type="text" placeholder="Lab Name" required />
          <input type="text" placeholder="Patient ID / Email" required />
          <input type="text" placeholder="Contact Number" required />
          <input type="date" required />
          <input type="text" placeholder="Card Number" />
          <input type="file" accept="image/*,application/pdf" />
          <textarea rows="3" placeholder="Description or upload prescription above"></textarea>
          <button type="submit">Submit Form</button>
        </form>
      </section>
    </div>

    <!-- RADIOLOGY -->
    <div id="rediology" class="content-section">
      <section class="hospital-info">
        <img
          src="https://media.istockphoto.com/photos/modern-hospital-building-picture-id1312706413?b=1&k=20&m=1312706413&s=170667a&w=0&h=VRi3w2E1UqvCCcK-nDV6mH7FhDZoTU9MM2QKSom96X4="
          alt="Hospital Image" />
        <div class="info-text">
          <h2><i class="bi bi-radioactive"></i> About Our Radiology Services</h2>
          <p>Advanced imaging services including X-Ray, CT Scan, MRI, and Ultrasound.</p>
          <div class="discount">
            🎉 <strong>Special Offer:</strong> Get <b>20% OFF</b> on radiology services this week!
          </div>
        </div>
      </section>

      <section class="recommended">
        <h2><i class="bi bi-star-fill" style="color: #ffc107;"></i> Our Best Recommendations</h2>
        <div class="hospital-grid">
          <div class="hospital-box">
            <img src="image.png" alt="MD City Hospital" />
            <h3>MD City Hospital</h3>
            <p><b>Address:</b> Parthala Chowk, Sector-122, Noida</p>
            <p>Expert doctors available.</p>
            <div class="offer">💙 FREE OPD Consultation</div>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>Sarvodaya Hospital</h3>
            <p><b>Address:</b> Greater Noida</p>
            <p>Quick appointments.</p>
            <div class="offer">🔥 20% OFF on OPD</div>
          </div>

          <div class="hospital-box">
            <img src="./images/felix.jpg" alt="Felix Hospital" />
            <h3>Felix Hospital</h3>
            <p><b>Address:</b> Sector 137, Noida</p>
            <p>Modern OPD facilities.</p>
            <div class="offer">💰 20% Discount</div>
          </div>

          <div class="hospital-box">
            <img src="https://haimedicalcentre.com/wp-content/uploads/2024/09/Polyclinic.jpg" alt="Felix Hospital" />
            <h3>Adrija Polyclinic</h3>

            <p>Modern OPD facilities.</p>
            <div class="offer">Expert Doctors</div>
          </div>

          <div class="hospital-box">
            <img src="images/max.webp" alt="Max Hospital" />
            <h3>Max Hospital</h3>
            <p><b>Address:</b> Vaishali, Ghaziabad, UP</p>
            <p>International Patients</p>
            <div class="offer">Multi-Specialty Care</div>
          </div>

          <div class="hospital-box">
            <img src="images/yashodhasanjaynagar.jpeg" alt="Yashoda Hospital" />
            <h3>Yashoda Hospital</h3>
            <p><b>Address:</b> Ghaziabad, Uttar Pradesh</p>
            <p>internal medicines</p>
            <div class="offer">superspecialities of cancer</div>
          </div>

          <div class="hospital-box">
            <img src="images/srs.jpeg" alt="SRS Hospital" />
            <h3>SRS Hospital</h3>
            <p><b>Address:</b> Opp. OIDB, Sector 70, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>

          <div class="hospital-box">
            <img src="images/medanta.jpg" alt="Medanta Hospital" />
            <h3>Medanta Hospital</h3>
            <p><b>Address:</b> Sector 51, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>

        </div>
      </section>

      <section class="contact">
        <h2><i class="bi bi-telephone-fill"></i> Contact Us</h2>
        <a href="tel:+919999307517"><button class="call-btn"><i class="bi bi-telephone"></i> Call Us Now</button></a>
      </section>

      <section class="form-section">
        <h3><i class="bi bi-radioactive"></i> Radiology Appointment Form</h3>
        <form id="radiologyForm" action="submit_rediology.php" method="POST" enctype="multipart/form-data">
          <input type="text" name="name" placeholder="Full Name" required />
          <input type="text" name="hospital" placeholder="Hospital/Center Name" required />
          <input type="text" name="patient_id" placeholder="Patient ID / Email" required />
          <input type="text" name="contact" placeholder="Contact Number" required />
          <input type="date" name="date" required />
          <input type="text" name="card_number" placeholder="Card Number" />

          <label style="font-weight: 600; margin-bottom: 10px; display: block;">Select Radiology Services:</label>
          <div class="checkbox-group">
            <label><input type="checkbox" name="services[]" value="CT Scan"> CT Scan</label>
            <label><input type="checkbox" name="services[]" value="3D/4D Ultrasound"> 3D/4D Ultrasound</label>
            <label><input type="checkbox" name="services[]" value="X-Ray"> X-Ray</label>
            <label><input type="checkbox" name="services[]" value="Echo"> Echo</label>
            <label><input type="checkbox" name="services[]" value="ECG"> ECG</label>
            <label><input type="checkbox" name="services[]" value="TMT"> TMT</label>
            <label><input type="checkbox" name="services[]" value="PFT"> PFT</label>
          </div>

          <label style="font-weight: 600; margin: 15px 0 10px; display: block;">Upload Prescription:</label>
          <input type="file" name="receipt" accept="image/*,application/pdf" />
          <textarea name="description" rows="3" placeholder="Additional notes or description"></textarea>
          <button type="submit">Submit Form</button>
        </form>
      </section>
    </div>

    <!-- OPD -->
    <div id="OPD" class="content-section">
      <section class="hospital-info">
        <img
          src="https://media.istockphoto.com/photos/modern-hospital-building-picture-id1312706413?b=1&k=20&m=1312706413&s=170667a&w=0&h=VRi3w2E1UqvCCcK-nDV6mH7FhDZoTU9MM2QKSom96X4="
          alt="Hospital Image" />
        <div class="info-text">
          <h2><i class="bi bi-building"></i> About Our OPD Services</h2>
          <p>Quick consultations with expert doctors at partner hospitals with exclusive discounts.</p>
          <div class="discount">
            🎉 <strong>Special Offer:</strong> Get <b>FREE OPD</b> consultation at select hospitals!
          </div>
          <button class="appointment-btn">
            <a href="#opd-form">Book Appointment</a>
          </button>
        </div>
      </section>

      <section class="recommended">
        <h2><i class="bi bi-star-fill" style="color: #ffc107;"></i> Our Best Recommendations</h2>
        <div class="hospital-grid">
          <div class="hospital-box">
            <img src="image.png" alt="MD City Hospital" />
            <h3>MD City Hospital</h3>
            <p><b>Address:</b> Parthala Chowk, Sector-122, Noida</p>
            <p>Expert doctors available.</p>
            <div class="offer">💙 FREE OPD Consultation</div>
          </div>

          <div class="hospital-box">
            <img src="sarvodaya hospitalgreaternoida.jpg" alt="Sarvodaya Hospital" />
            <h3>Sarvodaya Hospital</h3>
            <p><b>Address:</b> Greater Noida</p>
            <p>Quick appointments.</p>
            <div class="offer">🔥 20% OFF on OPD</div>
          </div>

          <div class="hospital-box">
            <img src="./images/felix.jpg" alt="Felix Hospital" />
            <h3>Felix Hospital</h3>
            <p><b>Address:</b> Sector 137, Noida</p>
            <p>Modern OPD facilities.</p>
            <div class="offer">💰 20% Discount</div>
          </div>

          <div class="hospital-box">
            <img src="https://haimedicalcentre.com/wp-content/uploads/2024/09/Polyclinic.jpg" alt="Felix Hospital" />
            <h3>Adrija Polyclinic</h3>

            <p>Modern OPD facilities.</p>
            <div class="offer">Expert Doctors</div>
          </div>

          <div class="hospital-box">
            <img src="images/max.webp" alt="Max Hospital" />
            <h3>Max Hospital</h3>
            <p><b>Address:</b> Vaishali, Ghaziabad, UP</p>
            <p>International Patients</p>
            <div class="offer">Multi-Specialty Care</div>
          </div>

          <div class="hospital-box">
            <img src="images/yashodhasanjaynagar.jpeg" alt="Yashoda Hospital" />
            <h3>Yashoda Hospital</h3>
            <p><b>Address:</b> Ghaziabad, Uttar Pradesh</p>
            <p>internal medicines</p>
            <div class="offer">superspecialities of cancer</div>
          </div>

          <div class="hospital-box">
            <img src="images/srs.jpeg" alt="SRS Hospital" />
            <h3>SRS Hospital</h3>
            <p><b>Address:</b> Opp. OIDB, Sector 70, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>

          <div class="hospital-box">
            <img src="images/medanta.jpg" alt="Medanta Hospital" />
            <h3>Medanta Hospital</h3>
            <p><b>Address:</b> Sector 51, Noida</p>
            <p>Multi-Specialty Care</p>
            <div class="offer">Advanced Diagnostics</div>
          </div>
        </div>
      </section>

      <section style="padding: 50px 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div style="max-width: 1200px; margin: 0 auto;">
          <h2 style="text-align: center; font-size: 2.2rem; color: #0077b6; margin-bottom: 10px;">
            👨‍⚕️ Meet Our Expert Doctors
          </h2>
          <p style="text-align: center; color: #666; font-size: 1.1rem; margin-bottom: 40px;">
            Experienced healthcare professionals dedicated to your well-being
          </p>

          <div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center;">

            <!-- Dr. G.P. Singh -->
            <div
              style="background: white; border-radius: 20px; padding: 30px; width: 100%; max-width: 500px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); border-top: 5px solid #0077b6; transition: transform 0.3s ease;">
              <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                <div
                  style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #0077b6, #00b4d8); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; flex-shrink: 0;">
                  👨‍⚕️
                </div>
                <div>
                  <h3 style="margin: 0; color: #0077b6; font-size: 1.5rem;">Dr. G.P. Singh</h3>
                  <p style="margin: 5px 0 0; color: #00b4d8; font-weight: 600; font-size: 1rem;">
                    General Physician</p>
                </div>
              </div>

              <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px; color: #333; font-size: 1rem;">
                  <span style="color: #0077b6;">🎓</span> Qualifications & Expertise
                </h4>
                <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                  <li>General Physician</li>
                  <li>Specialist in Internal Medicine</li>
                </ul>
              </div>

              <div
                style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px; color: #0077b6; font-size: 1rem;">
                  <span>💼</span> Current Position
                </h4>
                <p style="margin: 0; color: #333; font-weight: 600;">
                  <i class="fas fa-hospital" style="color: #0077b6; margin-right: 8px;"></i>
                  Medical Officer at <span style="color: #0077b6;">SRS Hospital</span>
                </p>
              </div>

              <div style="background: #fff3e0; padding: 15px; border-radius: 12px;">
                <h4 style="margin: 0 0 10px; color: #e65100; font-size: 1rem;">
                  <span>📋</span> Previous Experience
                </h4>
                <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                  <li><b>Ex. RMO</b> at <span style="color: #0077b6; font-weight: 600;">Vrindavan
                      Hospital</span></li>
                  <li><b>Ex. RMO</b> at <span style="color: #0077b6; font-weight: 600;">Sarvodaya
                      Hospital</span></li>
                </ul>
              </div>

              <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <span
                  style="background: #e3f2fd; color: #0077b6; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">General
                  Medicine</span>
                <span
                  style="background: #e8f5e9; color: #2e7d32; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">Internal
                  Medicine</span>
                <span
                  style="background: #fce4ec; color: #c2185b; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">Primary
                  Care</span>
              </div>


            </div>

            <!-- Dr. Shikha Singh -->
            <div
              style="background: white; border-radius: 20px; padding: 30px; width: 100%; max-width: 500px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); border-top: 5px solid #e91e63; transition: transform 0.3s ease;">
              <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                <div
                  style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #e91e63, #f48fb1); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; flex-shrink: 0;">
                  👩‍⚕️
                </div>
                <div>
                  <h3 style="margin: 0; color: #e91e63; font-size: 1.5rem;">Dr. Shikha Singh</h3>
                  <p style="margin: 5px 0 0; color: #f48fb1; font-weight: 600; font-size: 1rem;">
                    Gynaecologist | BAMS</p>
                </div>
              </div>

              <div style="background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px; color: #333; font-size: 1rem;">
                  <span style="color: #e91e63;">🎓</span> Qualifications & Expertise
                </h4>
                <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                  <li>Gynaecologist</li>
                  <li>BAMS (Bachelor of Ayurvedic Medicine and Surgery)</li>
                  <li>Women's Health Specialist</li>
                </ul>
              </div>

              <div
                style="background: linear-gradient(135deg, #fce4ec, #f8bbd9); padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px; color: #e91e63; font-size: 1rem;">
                  <span>💼</span> Current Position
                </h4>
                <p style="margin: 0; color: #333; font-weight: 600;">
                  <i class="fas fa-hospital" style="color: #e91e63; margin-right: 8px;"></i>
                  Medical Officer at <span style="color: #e91e63;">Astha Hospital</span>
                </p>
              </div>

              <div style="background: #f3e5f5; padding: 15px; border-radius: 12px;">
                <h4 style="margin: 0 0 10px; color: #7b1fa2; font-size: 1rem;">
                  <span>🩺</span> Specializations
                </h4>
                <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                  <li>Obstetrics & Gynaecology</li>
                  <li>Women's Reproductive Health</li>
                  <li>Ayurvedic Treatment & Care</li>
                  <li>Prenatal & Postnatal Care</li>
                </ul>
              </div>

              <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <span
                  style="background: #fce4ec; color: #e91e63; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">Gynaecology</span>
                <span
                  style="background: #e8f5e9; color: #2e7d32; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">BAMS</span>
                <span
                  style="background: #f3e5f5; color: #7b1fa2; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">Women's
                  Health</span>
              </div>


            </div>

          </div>

          <!-- Adrija Polyclinic Info Banner -->
          <div
            style="margin-top: 50px; background: linear-gradient(135deg, #0077b6, #00b4d8); border-radius: 20px; padding: 40px; text-align: center; color: white; box-shadow: 0 10px 40px rgba(0, 119, 182, 0.3);">
            <h3 style="margin: 0 0 15px; font-size: 1.8rem;">🏥 Visit Adrija Polyclinic Today!</h3>
            <p style="margin: 0 0 20px; font-size: 1.1rem; opacity: 0.95;">
              Experience quality healthcare with our team of experienced doctors
            </p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-bottom: 25px;">
              <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 15px;">
                <span style="font-size: 1.5rem;">👨‍⚕️</span>
                <p style="margin: 5px 0 0; font-weight: 600;">Experienced Doctors</p>
              </div>
              <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 15px;">
                <span style="font-size: 1.5rem;">💰</span>
                <p style="margin: 5px 0 0; font-weight: 600;">Affordable Pricing</p>
              </div>
              <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 15px;">
                <span style="font-size: 1.5rem;">🏆</span>
                <p style="margin: 5px 0 0; font-weight: 600;">Quality Care</p>
              </div>
              <div style="background: rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 15px;">
                <span style="font-size: 1.5rem;">⏰</span>
                <p style="margin: 5px 0 0; font-weight: 600;">Convenient Timings</p>
              </div>
            </div>
            <a href="#opd-form"
              style="display: inline-block; background: white; color: #0077b6; padding: 15px 40px; border-radius: 30px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 20px rgba(0,0,0,0.2); transition: all 0.3s ease;">
              📞 Book Your Appointment Now
            </a>
          </div>
        </div>
      </section>


      <section class="contact" id="opd-form">
        <h2><i class="bi bi-telephone-fill"></i> Contact Us</h2>
        <a href="tel:+919999307517"><button class="call-btn"><i class="bi bi-telephone"></i> Call Us Now</button></a>
      </section>

      <section class="form-section">
        <h3><i class="bi bi-building"></i> OPD Appointment Form</h3>
        <form id="opdForm">
          <input type="text" placeholder="Full Name" required />
          <input type="text" placeholder="Hospital Name" required />
          <input type="text" placeholder="Patient ID / Email" required />
          <input type="text" placeholder="Contact Number" required />
          <input type="date" required />
          <input type="text" placeholder="Card Number" />
          <input type="file" accept="image/*,application/pdf" />
          <textarea rows="3" placeholder="Description or upload prescription above"></textarea>
          <button type="submit">Submit Form</button>
        </form>
      </section>
    </div>

    <!-- PAYMENT HISTORY -->
    <div id="payment" class="content-section">
      <div class="modern-card-section" id="payment-section">
        <h2 class="section-title">
          <i class="bi bi-wallet2" style="color: var(--primary-color);"></i>
          Payment History
        </h2>
        <div id="paymentList">
          <p style="color: #666; text-align: center; padding: 40px;">
            <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 15px; color: #ddd;"></i>
            No payment history available yet.
          </p>
        </div>
      </div>
    </div>

    <!-- BOOK SERVICES -->
    <div id="Services" class="content-section">
      <div class="form-container" id="book-services-form">
        <h2><i class="bi bi-calendar-check"></i> Book Services</h2>

        <form method="POST" action="submit_request.php" enctype="multipart/form-data">
          <label for="service_type">Service Type</label>
          <select id="service_type" name="service_type" required>
            <option value="">-- Select Service --</option>
            <option value="Hospital Visit">Hospital Visit</option>
            <option value="OPD">OPD</option>
            <option value="Pharmacy">Pharmacy</option>
            <optgroup label="Diagnostic">
              <option value="Pathology">Pathology</option>
              <option value="Radiology">Radiology</option>
            </optgroup>
          </select>

          <div id="hospital_block">
            <label>Select Service Provider</label>
            <div class="dropdown">
              <div class="dropdown-btn" id="dropdownBtn">-- Select Service Provider --</div>
              <div class="dropdown-content" id="dropdownContent">
                <div class="dropdown-header">
                  <input type="text" id="hospital_search" placeholder="Search service providers...">
                </div>
                <div class="dropdown-list" id="hospitalList"></div>
              </div>
            </div>
            <input type="hidden" name="provider_id" id="selectedHospital">
          </div>

          <div id="appointment_date_block">
            <label for="appointment_date">Appointment Date</label>
            <input type="date" id="appointment_date" name="appointment_date" required>
          </div>

          <div id="appointment_time_block">
            <label for="appointment_time">Appointment Time</label>
            <select id="appointment_time" name="appointment_time" required>
              <option value="">-- Select Time --</option>
              <option value="8:00 AM">8:00 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="12:00 PM">12:00 PM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="4:00 PM">4:00 PM</option>
              <option value="5:00 PM">5:00 PM</option>
              <option value="6:00 PM">6:00 PM</option>
              <option value="7:00 PM">7:00 PM</option>
            </select>
            <p style="font-size: 13px; color: #666; margin-top: 8px;">
              <i class="bi bi-info-circle"></i> The doctor will contact you during your selected time slot.
            </p>
          </div>

          <label for="prescription_image">Prescription (Upload your previous prescription)</label>
          <input type="file" id="prescription_image" name="prescription_image" accept="image/*,application/pdf"
            required>

          <label for="card_no_display">Card Number</label>
          <input type="text" id="card_no_display" value="<?php echo htmlspecialchars($card_no); ?>" readonly
            style="background: #f0f0f0;">
          <input type="hidden" name="card_no" value="<?php echo htmlspecialchars($card_no); ?>">

          <button type="submit">
            <i class="bi bi-send"></i> Submit Request
          </button>
        </form>
      </div>
    </div>

    <!-- UPLOAD DOCUMENTS -->
    <div id="documents" class="content-section">
      <div class="upload-section" id="upload-documents">
        <h2><i class="bi bi-cloud-upload" style="color: var(--primary-color);"></i> Upload Documents</h2>
        <p>Upload and manage your Aadhaar and PAN card documents securely.</p>

        <form id="documentUploadForm">
          <div class="file-upload">
            <label for="aadhaarFront"><i class="bi bi-card-heading"></i> Aadhaar Front</label>
            <input type="file" id="aadhaarFront" accept="image/*,application/pdf" required>
            <img id="aadhaarFrontPreview" src="#" alt="Preview"
              style="display:none; max-width:150px; margin-top:10px; border-radius: 8px;">
          </div>

          <div class="file-upload">
            <label for="aadhaarBack"><i class="bi bi-card-heading"></i> Aadhaar Back</label>
            <input type="file" id="aadhaarBack" accept="image/*,application/pdf" required>
            <img id="aadhaarBackPreview" src="#" alt="Preview"
              style="display:none; max-width:150px; margin-top:10px; border-radius: 8px;">
          </div>

          <div class="file-upload">
            <label for="panCard"><i class="bi bi-credit-card-2-front"></i> PAN Card</label>
            <input type="file" id="panCard" accept="image/*,application/pdf" required>
            <img id="panCardPreview" src="#" alt="Preview"
              style="display:none; max-width:150px; margin-top:10px; border-radius: 8px;">
          </div>

          <button type="submit" class="btn-upload">
            <i class="bi bi-cloud-upload"></i> Upload Documents
          </button>
        </form>
      </div>
    </div>

  </div>

  <!-- SERVICES MODAL -->
  <div id="servicesModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeServices()">&times;</span>
      <h2><i class="bi bi-gift"></i> RX Medo Card Services</h2>
      <ul class="services-list">
        <li>✓ Discount up to 20–50% in Diagnostics and Laboratories</li>
        <li>✓ Discount up to 20–50% in OPD</li>
        <li>✓ Discount up to 20–22% in Pharmacy</li>
        <li>✓ Discount up to 10–20% in Hospital Operations</li>
        <li>✓ Discount up to 10–20% in Short-Term Hospitalization</li>
        <li>✓ Discount up to 10–20% on Home Nurses</li>
        <li>✓ Discount up to 10–20% on Medical Devices</li>
        <li>✓ Discount up to 10–20% on Physio and Therapies</li>
        <li>✓ Free OPD in Multiple Hospitals</li>
        <li>✓ Free Medicine Delivery & Ambulance</li>
        <li>✓ Free Online E-Consultation</li>
      </ul>
    </div>
  </div>

  <!-- INSURE SERVICES MODAL -->
  <div id="insureServicesModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeInsureServices()">&times;</span>
      <h2><i class="bi bi-shield-check"></i> Our Insure Services</h2>

      <h3>1. Young India (18–35 Years)</h3>
      <ul class="services-list">
        <li>✓ Waiver of First Year Exclusion</li>
        <li>✓ Waiver of 30 Days Waiting Period</li>
        <li>✓ Cover for Pre-Existing Diseases</li>
        <li>✓ ₹5 Lakhs Insurance + ₹50 Lakhs Accidental</li>
        <li>✓ Free Online Consultation</li>
        <li>✓ Up to 20% long-term discount</li>
      </ul>

      <h3>2. Matured Individuals (36–50 Years)</h3>
      <ul class="services-list">
        <li>✓ Add Family Members to This Card</li>
      </ul>

      <h3>3. Senior Citizens (50+ Years)</h3>
      <ul class="services-list">
        <li>✓ Free Home Medication Services</li>
      </ul>

      <p style="margin-top: 15px; font-weight: bold;">
        <i class="bi bi-plus-circle"></i> Includes all RX Medo Card benefits
      </p>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Driver.js -->
  <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>

  <script>
    // ==================== DRIVER.JS TOUR ====================
    function startTour() {
      const driver = window.driver.js.driver;

      const driverObj = driver({
        showProgress: true,
        animate: true,
        allowClose: true,
        overlayClickNext: false,
        stagePadding: 10,
        stageRadius: 10,
        popoverClass: 'driverjs-theme',
        progressText: '{{current}} of {{total}}',
        nextBtnText: 'Next →',
        prevBtnText: '← Previous',
        doneBtnText: 'Finish Tour ✓',
        onDestroyStarted: () => {
          if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tour?")) {
            driverObj.destroy();
          }
        },
        steps: [
          {
            element: '#welcome-banner',
            popover: {
              title: '👋 Welcome to RX Medocard!',
              description: 'This is your personalized health dashboard. Let\'s take a quick tour to help you get started!',
              side: 'bottom',
              align: 'center'
            }
          },

          {
            element: '#card-info',
            popover: {
              title: '💳 Your Card Information',
              description: 'View your selected card type, insurance type, and validity period here.',
              side: 'bottom',
              align: 'center'
            }
          },
          {
            element: '#referral-box',
            popover: {
              title: '🎁 Referral Program',
              description: 'Share your unique referral link with friends and family. When they sign up using your link, they get special benefits!',
              side: 'top',
              align: 'center'
            }
          },
          {
            element: '#download-buttons',
            popover: {
              title: '📥 Download Options',
              description: 'Download your RX Medo Card or payment receipt with a single click.',
              side: 'top',
              align: 'center'
            }
          },
          {
            element: '#services-preview',
            popover: {
              title: '✨ Included Services',
              description: 'These are all the amazing benefits included with your RX Medo Card - from insurance coverage to free consultations!',
              side: 'top',
              align: 'center'
            }
          },
          {
            element: '#nav-cards',
            popover: {
              title: '🎴 Card Details',
              description: 'Click here to explore all available RX Medo card types and their features.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-services',
            popover: {
              title: '📅 Book Services',
              description: 'Easily book appointments for hospital visits, OPD, pharmacy, and diagnostic services.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-family',
            popover: {
              title: '👨‍👩‍👧‍👦 Family Plans',
              description: 'Add your family members and enjoy healthcare benefits together with our family plans.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-hospitals',
            popover: {
              title: '🏥 Partner Hospitals',
              description: 'Browse our network of 50+ partner hospitals where you can avail exclusive discounts.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-pharmacy',
            popover: {
              title: '💊 Pharmacy Services',
              description: 'Get medicines delivered to your doorstep with up to 22% discount!',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-diagnostic',
            popover: {
              title: '🔬 Diagnostic Services',
              description: 'Book lab tests and diagnostic services with discounts up to 50%.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-payment',
            popover: {
              title: '💰 Payment History',
              description: 'Track all your payments and transactions in one place.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#nav-documents',
            popover: {
              title: '📄 Upload Documents',
              description: 'Securely upload your Aadhaar and PAN card for verification.',
              side: 'right',
              align: 'center'
            }
          },
          {
            element: '#floatingTourBtn',
            popover: {
              title: '🧭 Tour Button',
              description: 'You can restart this tour anytime by clicking this button!',
              side: 'left',
              align: 'center'
            }
          },
          {
            popover: {
              title: '🎉 You\'re All Set!',
              description: 'Congratulations! You now know your way around the RX Medocard dashboard. Start exploring and enjoy your healthcare benefits!',
            }
          }
        ]
      });

      driverObj.drive();
    }

    // Auto-show tour for first-time visitors
    window.addEventListener('DOMContentLoaded', function () {
      const hasSeenTour = localStorage.getItem('rxmedo_tour_completed');
      if (!hasSeenTour) {
        setTimeout(() => {
          if (confirm('👋 Welcome to RX Medocard! Would you like a quick tour of the dashboard?')) {
            startTour();
          }
          localStorage.setItem('rxmedo_tour_completed', 'true');
        }, 1500);
      }
    });

    // ==================== SECTION SWITCHING ====================
    function showSection(sectionId, btnEl) {
      // Hide all sections
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });

      // Show selected
      const target = document.getElementById(sectionId);
      if (target) {
        target.classList.add('active');
        document.querySelector('.main-content').scrollTop = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }

      // Sidebar active highlight
      document.querySelectorAll('.sidebar .menu-btn').forEach(el => {
        el.classList.remove('active');
      });

      if (btnEl) btnEl.classList.add('active');

      // Close sidebar on mobile
      if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('hamIcon').innerHTML = '<i class="bi bi-list"></i>';
      }
    }

    // ==================== LOGOUT ====================
    function confirmLogout() {
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "index.html";
        return false;
      }
      return false;
    }

    // ==================== SIDEBAR TOGGLE ====================
    function toggleSidebar() {
      let sidebar = document.getElementById("sidebar");
      let hamIcon = document.getElementById("hamIcon");

      sidebar.classList.toggle("open");

      if (sidebar.classList.contains("open")) {
        hamIcon.innerHTML = '<i class="bi bi-x-lg"></i>';
      } else {
        hamIcon.innerHTML = '<i class="bi bi-list"></i>';
      }
    }

    // ==================== MODALS ====================
    function showServices() {
      document.getElementById("servicesModal").style.display = "flex";
    }

    function closeServices() {
      document.getElementById("servicesModal").style.display = "none";
    }

    function showInsureServices() {
      document.getElementById("insureServicesModal").style.display = "flex";
    }

    function closeInsureServices() {
      document.getElementById("insureServicesModal").style.display = "none";
    }

    window.onclick = function (e) {
      if (e.target == document.getElementById("servicesModal")) {
        closeServices();
      }
      if (e.target == document.getElementById("insureServicesModal")) {
        closeInsureServices();
      }
    }

    // ==================== DOCUMENT PREVIEW ====================
    const previewFile = (inputId, previewId) => {
      const input = document.getElementById(inputId);
      const preview = document.getElementById(previewId);
      input.addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    };

    previewFile('aadhaarFront', 'aadhaarFrontPreview');
    previewFile('aadhaarBack', 'aadhaarBackPreview');
    previewFile('panCard', 'panCardPreview');

    // ==================== FORM SUBMISSIONS ====================
    document.querySelectorAll('form').forEach(form => {
      if (!form.getAttribute('action')) {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          alert("✅ Form Submitted Successfully! We will contact you soon.");
          form.reset();
        });
      }
    });

    // ==================== SMOOTH SCROLL ====================
    document.querySelectorAll('.appointment-btn a').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          window.scrollTo({
            top: target.offsetTop - 100,
            behavior: 'smooth'
          });
        }
      });
    });

    // ==================== BOOK SERVICES FORM ====================
    const serviceTypeSelect = document.getElementById('service_type');
    const hospitalBlock = document.getElementById('hospital_block');
    const appointmentDateBlock = document.getElementById('appointment_date_block');
    const appointmentTimeBlock = document.getElementById('appointment_time_block');
    const dropdownBtn = document.getElementById('dropdownBtn');
    const dropdownContent = document.getElementById('dropdownContent');
    const hospitalSearch = document.getElementById('hospital_search');
    const hospitalList = document.getElementById('hospitalList');
    const selectedHospital = document.getElementById('selectedHospital');
    const appointmentDate = document.getElementById('appointment_date');
    const appointmentTime = document.getElementById('appointment_time');

    if (serviceTypeSelect) {
      serviceTypeSelect.addEventListener('change', function () {
        const selectedType = this.value;

        if (selectedType === 'Pharmacy') {
          hospitalBlock.style.display = 'none';
          appointmentDateBlock.style.display = 'none';
          appointmentTimeBlock.style.display = 'none';
          selectedHospital.value = "101";
          dropdownBtn.textContent = "Pharmacy Selected";
          selectedHospital.removeAttribute('required');
          appointmentDate.removeAttribute('required');
          appointmentTime.removeAttribute('required');
        } else if (selectedType === 'Pathology') {
          hospitalBlock.style.display = 'none';
          appointmentDateBlock.style.display = 'none';
          appointmentTimeBlock.style.display = 'none';
          selectedHospital.value = "102";
          dropdownBtn.textContent = "Pathology Selected";
          selectedHospital.removeAttribute('required');
          appointmentDate.removeAttribute('required');
          appointmentTime.removeAttribute('required');
        } else if (selectedType !== '') {
          hospitalBlock.style.display = 'block';
          appointmentDateBlock.style.display = 'block';
          appointmentTimeBlock.style.display = 'block';
          loadHospitals(selectedType);
          selectedHospital.setAttribute('required', 'required');
          appointmentDate.setAttribute('required', 'required');
          appointmentTime.setAttribute('required', 'required');
        } else {
          hospitalBlock.style.display = 'none';
          appointmentDateBlock.style.display = 'none';
          appointmentTimeBlock.style.display = 'none';
          selectedHospital.value = "";
          dropdownBtn.textContent = "-- Select Service Provider --";
          selectedHospital.removeAttribute('required');
          appointmentDate.removeAttribute('required');
          appointmentTime.removeAttribute('required');
        }
      });
    }

    function loadHospitals(serviceType) {
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "load_hospitals.php?type=" + encodeURIComponent(serviceType), true);
      xhr.onload = function () {
        if (xhr.status === 200) {
          hospitalList.innerHTML = xhr.responseText;
          dropdownBtn.textContent = "-- Select Service Provider --";
          selectedHospital.value = "";
        }
      };
      xhr.send();
    }

    if (dropdownBtn) {
      dropdownBtn.addEventListener('click', function () {
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
      });
    }

    if (hospitalList) {
      hospitalList.addEventListener('click', function (e) {
        if (e.target && e.target.dataset.id) {
          selectedHospital.value = e.target.dataset.id;
          dropdownBtn.textContent = e.target.textContent;
          dropdownContent.style.display = 'none';
        }
      });
    }

    if (hospitalSearch) {
      hospitalSearch.addEventListener('keyup', function () {
        const filter = this.value.toUpperCase();
        const items = hospitalList.querySelectorAll('div[data-id]');
        items.forEach(item => {
          const txt = item.textContent.toUpperCase();
          item.style.display = txt.includes(filter) ? '' : 'none';
        });
      });
    }

    document.addEventListener('click', function (e) {
      if (dropdownBtn && dropdownContent) {
        if (!dropdownBtn.contains(e.target) && !dropdownContent.contains(e.target)) {
          dropdownContent.style.display = 'none';
        }
      }
    });

    // ==================== INITIAL STATE ====================
    document.addEventListener('DOMContentLoaded', function () {
      const firstBtn = document.querySelector('.sidebar .menu-btn');
      if (firstBtn) firstBtn.classList.add('active');
      showSection('dashboard', firstBtn);

      // Set minimum date for appointment
      const dateInputs = document.querySelectorAll('input[type="date"]');
      const today = new Date().toISOString().split('T')[0];
      dateInputs.forEach(input => {
        input.setAttribute('min', today);
      });
    });
  </script>
</body>

</html>