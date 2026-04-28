<?php
// ✅ If referral link is opened (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ref = isset($_GET['ref']) ? $_GET['ref'] : '';
    header("Location: signup.html?ref=" . urlencode($ref));
    exit;
}

// ✅ Database connection
$con = mysqli_connect('localhost', 'root', '', 'rxmedo');
if (!$con) {
  die("❌ Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ✅ Collect user info
  $name     = $_POST['name'] ?? '';
  $email    = $_POST['email'] ?? '';
  $phone    = $_POST['phone'] ?? '';
  $password = $_POST['password'] ?? '';
  $aadhaar  = $_POST['aadhaar_card_no'] ?? '';
  $pan      = $_POST['pan_card_no'] ?? '';

  $userType     = $_POST['type'] ?? '';
  $ageGroup     = $_POST['age'] ?? '';
  $insurance    = $_POST['benefits'] ?? '';
  $selectedCard = $_POST['card_type'] ?? '';

  // ✅ Referral
  $reference = $_POST['reference'] ?? '';
  $ref_code = strtolower(str_replace(' ', '', $name)) . rand(1000,9999);

  // ✅ Coupon Code
  $coupon_code = $_POST['coupon_code'] ?? '';

  // ✅ Spouse info
  $spouse_name     = $_POST['spouse_name'] ?? null;
  $spouse_dob      = $_POST['spouse_dob'] ?? null;
  $spouse_relation = $_POST['spouse_relation'] ?? 'Spouse';

  // ✅ Children info
  $child1_name     = $_POST['child1_name'] ?? null;
  $child1_dob      = $_POST['child1_dob'] ?? null;
  $child1_relation = $_POST['child1_relation'] ?? 'Child';

  $child2_name     = $_POST['child2_name'] ?? null;
  $child2_dob      = $_POST['child2_dob'] ?? null;
  $child2_relation = $_POST['child2_relation'] ?? 'Child';

  $child3_name     = $_POST['child3_name'] ?? null;
  $child3_dob      = $_POST['child3_dob'] ?? null;
  $child3_relation = $_POST['child3_relation'] ?? 'Child';

  $child4_name     = $_POST['child4_name'] ?? null;
  $child4_dob      = $_POST['child4_dob'] ?? null;
  $child4_relation = $_POST['child4_relation'] ?? 'Child';

  // ✅ Duplicate email check
  $check = $con->prepare("SELECT id FROM mydata WHERE email=?");
  $check->bind_param("s", $email);
  $check->execute();
  $check->store_result();
  if ($check->num_rows > 0) {
    echo "<script>alert('❌ Email already exists. Please use another email.'); window.history.back();</script>";
    exit;
  }
  $check->close();

  // ✅ File uploads helper
  function handleUpload($fieldName) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['png','jpg','jpeg','pdf'])) return null;
      $uploadDir = 'uploads/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
      $fileName = time() . '_' . basename($_FILES[$fieldName]['name']);
      $targetPath = $uploadDir . $fileName;
      return move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath) ? $targetPath : null;
    }
    return null;
  }

  $aadhaarFront = handleUpload('aadhaar_front');
  $aadhaarBack  = handleUpload('aadhaar_back');
  $panPhoto     = handleUpload('pan_card_photo');

  // ✅ Insert user info
  $stmt = $con->prepare("INSERT INTO mydata (
    name, email, phone, password, aadhaar_card_no, pan_card_no,
    card_type, age_group, user_type, insurance_status,
    aadhaar_front, aadhaar_back, pan_card_photo,
    spouse_name, spouse_dob, spouse_relation,
    child1_name, child1_dob, child1_relation,
    child2_name, child2_dob, child2_relation,
    child3_name, child3_dob, child3_relation,
    child4_name, child4_dob, child4_relation,
    status, ref_code, reference
  ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

  $status = "pending_payment";

  $stmt->bind_param(
    "sssssssssssssssssssssssssssssss",
    $name, $email, $phone, $password, $aadhaar, $pan,
    $selectedCard, $ageGroup, $userType, $insurance,
    $aadhaarFront, $aadhaarBack, $panPhoto,
    $spouse_name, $spouse_dob, $spouse_relation,
    $child1_name, $child1_dob, $child1_relation,
    $child2_name, $child2_dob, $child2_relation,
    $child3_name, $child3_dob, $child3_relation,
    $child4_name, $child4_dob, $child4_relation,
    $status, $ref_code, $reference
  );

  if ($stmt->execute()) {
    $userId = $stmt->insert_id;

    $_SESSION['user_id'] = $userId;

    // ✅ Base price set according to card_type
    $original_price = 0;
    switch ($selectedCard) {
        case "RX Medo Card": $original_price = 3000; break;
        case "Rx Medo Top Up Card": $original_price = 3500; break;
        case "RX Medo YoungShield Card": $original_price = 7000; break;
        case "RX Medo CitizenCare Card": $original_price = 10000; break;
        case "RX Medo GuardianCare Card": $original_price = 15000; break;
        case "RX Medo SeniorShield Card": $original_price = 25000; break;
        case "RX Medo Family Card": $original_price = 8000; break;
        case "RX Medo Family Top-Up Card": $original_price = 4000; break;
        case "RX Medo Family Secure Card": $original_price = 15000; break;
        default: $original_price = 1; break;
    }

    $final_price = $original_price;

// ✅ Coupon Validation Logic
if (!empty($coupon_code)) {
    $sql = "SELECT discount_percent, cashback_amount 
            FROM coupons 
            WHERE code=? AND validity_date >= CURDATE()";
    $stmtCoupon = $con->prepare($sql);
    $stmtCoupon->bind_param("s", $coupon_code);
    $stmtCoupon->execute();
    $resultCoupon = $stmtCoupon->get_result();

    if ($resultCoupon->num_rows > 0) {
        $row = $resultCoupon->fetch_assoc();
        $discount = intval($row['discount_percent']);
        $cashback = intval($row['cashback_amount']);

        if ($discount > 0 && $cashback == 0) {
            // ✅ Discount case
            $discount_amount = ($original_price * $discount) / 100;
            $final_price = $original_price - $discount_amount;
            $msg = "✅ Coupon applied! Discount: $discount% | Final Price: ₹$final_price";
        } elseif ($cashback > 0 && $discount == 0) {
            // ✅ Cashback case (minus directly from price)
            $final_price = $original_price - $cashback;
            if ($final_price < 0) $final_price = 0; // safety check
            $msg = "✅ Coupon applied! Cashback: ₹$cashback | Final Price: ₹$final_price";
        } elseif ($discount == 0 && $cashback == 0) {
            // ✅ No benefit case
            $final_price = $original_price;
            $msg = "❌ Coupon has no discount/cashback. Price: ₹$original_price";
        } else {
            // ✅ If both entered, give priority to discount
            $discount_amount = ($original_price * $discount) / 100;
            $final_price = $original_price - $discount_amount;
            $msg = "✅ Coupon applied! Discount: $discount% (priority) | Final Price: ₹$final_price";
        }
    } else {
        // ✅ Invalid coupon
        $msg = "❌ Invalid coupon code. Proceeding with Original Price: ₹$original_price";
        $final_price = $original_price;
    }

    echo "<script>
        alert('$msg');
        window.location.href='payscript.php?user_id=$userId&price=$final_price&coupon=$coupon_code';
    </script>";
    exit;

} else {
    // ✅ No coupon entered
    echo "<script>
        window.location.href='payscript.php?user_id=$userId&price=$final_price';
    </script>";
    exit;
}


} else {
    echo "<script>alert('❌ Signup failed. Please try again.'); window.history.back();</script>";
}

$stmt->close();
$con->close();
}
?>
