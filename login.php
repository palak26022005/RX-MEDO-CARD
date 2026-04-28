<?php
session_start();

// ✅ Connect to database
$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) {
  die("❌ Connection failed: " . mysqli_connect_error());
}

// ✅ Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['mail']);
  $password = trim($_POST['pass']);

  // ✅ Check if email exists
  $stmt = $con->prepare("SELECT id, name, password, card_type, status,
    serial_rx_medo_card, serial_rx_medo_family_card, serial_rx_medo_youngshield_card,
    serial_rx_medo_citizencare_card, serial_rx_medo_guardiancare_card, serial_rx_medo_seniorshield_card,
    serial_rx_medo_familysecure_card, serial_rx_medo_topup_card, serial_rx_medo_family_topup_card
    FROM mydata WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $name, $dbPassword, $card_type, $status,
      $serial1, $serial2, $serial3, $serial4, $serial5, $serial6, $serial7, $serial8, $serial9);
    $stmt->fetch();

    // ✅ Block login if payment not done
    if ($status !== 'active') {

      // ✅ Store user_id in session so payscript.php can fetch details
      $_SESSION['user_id'] = $id;

      echo "<script>
        alert('❌ Payment not completed. Please complete payment before logging in.');
        window.location.href='payscript.php';
      </script>";
      exit;
    }

    if ($password === $dbPassword) {
      $_SESSION['user_id'] = $id;
      $_SESSION['user_name'] = $name;
      $_SESSION['user_email'] = $email;

      // ✅ Normalize card_type for all variations
      $cardKey = strtolower(trim($card_type));
      $normalizeMap = [
        'rx medo top-up card'         => 'rx medo top up card',
        'rx medo topup card'          => 'rx medo top up card',
        'rx medo top up card'         => 'rx medo top up card',
        'rx medo family top-up card'  => 'rx medo family top up card',
        'rx medo family topup card'   => 'rx medo family top up card',
        'rx medo family top up card'  => 'rx medo family top up card',
        'rx medo familysecure card'   => 'rx medo familysecure card',
        'rx medo family secure card'  => 'rx medo familysecure card'
      ];
      if (isset($normalizeMap[$cardKey])) {
        $cardKey = $normalizeMap[$cardKey];
      }

      // ✅ Serial logic for 9 card types
      $cardMap = [
        'rx medo card'               => ['col' => 'serial_rx_medo_card',              'prefix' => 'RMC025'],
        'rx medo family card'        => ['col' => 'serial_rx_medo_family_card',       'prefix' => 'RMFC025'],
        'rx medo youngshield card'   => ['col' => 'serial_rx_medo_youngshield_card',  'prefix' => 'RMYSC025'],
        'rx medo citizencare card'   => ['col' => 'serial_rx_medo_citizencare_card',  'prefix' => 'RMCCC025'],
        'rx medo guardiancare card'  => ['col' => 'serial_rx_medo_guardiancare_card', 'prefix' => 'RMGCC025'],
        'rx medo seniorshield card'  => ['col' => 'serial_rx_medo_seniorshield_card', 'prefix' => 'RMSSC025'],
        'rx medo familysecure card'  => ['col' => 'serial_rx_medo_familysecure_card', 'prefix' => 'RMFSC025'],
        'rx medo top up card'        => ['col' => 'serial_rx_medo_topup_card',        'prefix' => 'RMTP025'],
        'rx medo family top up card' => ['col' => 'serial_rx_medo_family_topup_card', 'prefix' => 'RMFTP025']
      ];

      if (isset($cardMap[$cardKey])) {
        $col = $cardMap[$cardKey]['col'];
        $prefix = $cardMap[$cardKey]['prefix'];

        // ✅ Check if serial already assigned
        $checkSerial = $con->prepare("SELECT $col FROM mydata WHERE id = ?");
        $checkSerial->bind_param("i", $id);
        $checkSerial->execute();
        $checkRes = $checkSerial->get_result()->fetch_assoc();
        $currentSerial = $checkRes[$col] ?? '';

        if (empty($currentSerial)) {
          $countQuery = mysqli_query($con, "SELECT COUNT(*) AS total FROM mydata WHERE $col IS NOT NULL");
          $countRow = mysqli_fetch_assoc($countQuery);
          $nextNumber = $countRow['total'] + 1;
          $serial = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

          $updateSerial = $con->prepare("UPDATE mydata SET $col = ? WHERE id = ?");
          $updateSerial->bind_param("si", $serial, $id);
          $updateSerial->execute();
          $updateSerial->close();
        }
      }

      echo "<script>alert('✅ Login successful. Welcome $name!'); window.location.href='dashboard.php';</script>";
    } else {
      echo "<script>alert('❌ Incorrect password.'); window.history.back();</script>";
    }
  } else {
    echo "<script>alert('❌ Email not found. Please sign up.'); window.location.href='signup.html';</script>";
  }

  $stmt->close();
  $con->close();
}
?>