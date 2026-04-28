<?php
session_start();

require_once __DIR__ . '/razorpay_php/Razorpay.php';
use Razorpay\Api\Api;

$LIVE_KEY_ID = "rzp_live_SbmpBK5uGEF7u7";
$LIVE_KEY_SECRET = "A1SzVmHCYNvBmAga9NO08E77";

// ✅ Get user ID from session (fallback to URL if needed)
$userId = intval($_SESSION['user_id'] ?? ($_GET['user_id'] ?? 0));
if (!$userId) {
    die("❌ Invalid user ID");
}

// ✅ Connect to DB$con = mysqli_connect('localhost', 'root', '', 'medocard');
$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) {
    die("❌ DB Connection failed: " . mysqli_connect_error());
}

// ✅ Fetch user details
$stmt = $con->prepare("SELECT name, email, phone, card_type, rxmedo_family_opt, card_subcategory, age_group 
                       FROM mydata WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("❌ User not found");
}
$user = $result->fetch_assoc();

$name         = $user['name'];
$email        = $user['email'];
$phone        = $user['phone'];
$cardType     = trim($user['card_type']);
$cardCategory = $user['rxmedo_family_opt'];
$cardSubcat   = $user['card_subcategory'];
$ageGroup     = $user['age_group'];

// ✅ Base card pricing logic
switch ($cardType) {
    case "RX Medo Card":              $amount = 100; break; // ₹3000
    case "Rx Medo Top Up Card":       $amount = 350000; break; // ₹3500
    case "RX Medo YoungShield Card":  $amount = 700000; break; // ₹7000
    case "RX Medo CitizenCare Card":  $amount = 1000000; break; // ₹10000
    case "RX Medo GuardianCare Card": $amount = 1500000; break; // ₹15000
    case "RX Medo SeniorShield Card": $amount = 2500000; break; // ₹25000
    case "RX Medo Family Card":       $amount = 800000; break; // ₹8000
    case "RX Medo Family Top-Up card":$amount = 400000; break; // ₹4000
    case "RX Medo Family Secure card":$amount = 500000; break; // ₹15000
    default:                          $amount = 100; break;    // ₹1
}

// ✅ Coupon info from URL
$finalPrice = isset($_GET['price']) ? intval($_GET['price']) : ($amount/100);
$couponCode = $_GET['coupon'] ?? null;

// ✅ Default values to avoid warnings
$discount = 0;
$cashback = 0;

// ✅ Override Razorpay amount if discounted price available
if ($finalPrice && $finalPrice > 0) {
    $amount = $finalPrice * 100; // Razorpay needs paise
}

// ✅ Create Razorpay order
$api = new Api($LIVE_KEY_ID, $LIVE_KEY_SECRET);
$orderData = [
    'receipt'         => 'signup_' . $userId . '_' . uniqid(),
    'amount'          => $amount, // in paise (₹1 = 100)
    'currency'        => 'INR',
    'payment_capture' => 1,
    'notes'           => ['user_id' => (string)$userId, 'coupon_code' => $couponCode]
];
$order   = $api->order->create($orderData);
$orderId = $order['id'];

// ✅ Save order mapping
$map = $con->prepare("INSERT INTO razorpay_orders 
    (order_id, user_id, card_type, card_category, card_subcategory, age_group, amount, coupon_code) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$map->bind_param("sissssis", $orderId, $userId, $cardType, $cardCategory, $cardSubcat, $ageGroup, $amount, $couponCode);
$map->execute();
$map->close();
$con->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Proceed with Payment</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="cdn.jsdelivr.net" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .payment-card {
      width: 100%;
      max-width: 650px;
      background: linear-gradient(135deg, #4671f4, #4558ff);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
      animation: fadeIn 0.7s ease;
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    h2 {
      text-align: center;
      font-size: 26px;
      margin-bottom: 18px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .info-item {
      background: rgba(255, 255, 255, 0.9);
      color: #000;
      padding: 12px 16px;
      border-radius: 12px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      font-size: 15px;
      font-weight: 400;
      border-left: 4px solid #00ffcc;
    }

    .info-label {
      font-weight: 600;
      opacity: 0.85;
    }

    .info-value {
      font-weight: 600;
      color: #3742fa;
    }

    #rzp-button1 {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #00c853, #06a247);
      border: none;
      border-radius: 14px;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      margin-top: 20px;
      font-weight: 600;
      box-shadow: 0 6px 18px rgba(0, 255, 127, 0.4);
      transition: 0.25s ease;
    }

    #rzp-button1:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(0, 255, 127, 0.55);
    }

    #rzp-button1:active {
      transform: scale(0.97);
    }

    /* Mobile Optimized */
    @media (max-width: 480px) {
      h2 {
        font-size: 22px;
      }

      .payment-card {
        padding: 22px;
        border-radius: 16px;
      }

      .info-item {
        font-size: 14px;
        padding: 10px;
      }

      #rzp-button1 {
        font-size: 16px;
        padding: 13px;
      }
    }
  </style>
</head>

<body>

 

    <div class="payment-card">

    <div class="payment-header">
      <img src="logo.jpeg" class="payment-logo" alt="Logo">
      <h2 class="payment-title" style="text-align: center;margin-bottom: 30px;">Proceed with Payment</h2>
    </div>
    
    <style>
      .payment-header {
        
        padding: 15px 20px;
        display: flex;
        align-items: center;
        /* Fix vertical alignment */
        
        /* Center all content */
        gap: 20px;
        /* Space between logo + text */
      }
    
      .payment-logo {
        height: 120x;
        width: 120px;
        object-fit: contain;
       justify-content: space-around;
       margin-bottom: 30px;
      }
    
      .payment-title {
        margin: 0;
        color: white;
        font-size: 24px;
        font-weight: 700;
        margin-left: 30px;
        
      }
    
      /* Mobile Fix */
      @media (max-width: 576px) {
        .payment-header {
          flex-direction: row;
          justify-content: center;
          gap: 12px;
        }
    
        .payment-title {
          font-size: 18px;
          text-align: center;
        }
    
        .payment-logo {
          height: 95px;
          width: 95px;
        }
      }
    </style>
    <!-- <div class="payment-card">
  <h2>Proceed with Payment</h2> -->

  <div class="info-item">
    <span class="info-label">User:</span>
    <span class="info-value"><?php echo htmlspecialchars($name); ?></span>
  </div>

  <div class="info-item">
    <span class="info-label">Email:</span>
    <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
  </div>

  <div class="info-item">
    <span class="info-label">Card Type:</span>
    <span class="info-value"><?php echo htmlspecialchars($cardType); ?></span>
  </div>

  <div class="info-item">
    <span class="info-label">Category:</span>
    <span class="info-value"><?php echo htmlspecialchars($cardCategory ?: 'Membership Card'); ?></span>
  </div>

  <?php if ($cardSubcat): ?>
  <div class="info-item">
    <span class="info-label">Subcategory:</span>
    <span class="info-value"><?php echo htmlspecialchars($cardSubcat); ?></span>
  </div>
  <?php endif; ?>

  <?php if ($ageGroup): ?>
  <div class="info-item">
    <span class="info-label">Age Group:</span>
    <span class="info-value"><?php echo htmlspecialchars($ageGroup); ?></span>
  </div>
  <?php endif; ?>

  <?php if ($couponCode): ?>
  <div class="info-item">
    <span class="info-label">Coupon Applied:</span>
    <span class="info-value"><?php echo htmlspecialchars($couponCode); ?></span>
  </div>
  <?php endif; ?>

  <div class="info-item">
    <span class="info-label">Final Amount:</span>
    <span class="info-value">₹<?php echo number_format($finalPrice, 2); ?></span>
  </div>

  <button id="rzp-button1">Pay Securely</button>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
  var options = {
    "key": "<?php echo $LIVE_KEY_ID; ?>",
    "amount": "<?php echo $amount; ?>", // Razorpay amount in paise
    "currency": "INR",
    "name": "Rxmedo",
    "description": "Signup Payment",
    "order_id": "<?php echo $orderId; ?>",
    "callback_url": "payment_success.php",
    "prefill": {
      "name": "<?php echo htmlspecialchars($name); ?>",
      "email": "<?php echo htmlspecialchars($email); ?>",
      "contact": "<?php echo htmlspecialchars($phone); ?>"
    },
    "theme": { "color": "#00c853" }
  };

  var rzp1 = new Razorpay(options);
  document.getElementById('rzp-button1').onclick = function(e) {
    rzp1.open();
    e.preventDefault();
  }
</script>


</body>

</html>