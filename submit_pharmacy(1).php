<?php
session_start();
$con = mysqli_connect("localhost", "u107895813_utkarsh21", "Kaushik001@123", "u107895813_rxmedo");
if (!$con) {
  die("❌ Connection failed: " . mysqli_connect_error());
}

$full_name     = trim($_POST['name'] ?? '');
$hospital_name = trim($_POST['hospital'] ?? '');
$patient_id    = trim($_POST['patient_id'] ?? '');
$contact       = trim($_POST['contact'] ?? '');
$date          = $_POST['date'] ?? '';
$card_number   = trim($_POST['card_number'] ?? '');
$description   = trim($_POST['description'] ?? '');

$errors = [];
if (empty($full_name)) {
  $errors[] = "❌ Full Name is required.";
}
if (empty($hospital_name)) {
  $errors[] = "❌ Hospital Name is required.";
}
if (empty($patient_id) || !filter_var($patient_id, FILTER_VALIDATE_EMAIL)) {
  $errors[] = "❌ Patient ID must be a valid email.";
}
if (empty($contact) || !preg_match("/^\d{10}$/", $contact)) {
  $errors[] = "❌ Contact Number must be exactly 10 digits.";
}
if (empty($date)) {
  $errors[] = "❌ Appointment Date is required.";
}
if (empty($card_number)) {
  $errors[] = "❌ Card Number is required.";
}

if (!empty($errors)) {
  $msg = implode("\\n", $errors);
  echo "<script>alert('$msg'); window.history.back();</script>";
  exit;
}

// ✅ Handle receipt upload
$receipt_path = '';
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = 'uploads/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }
  $fileName = basename($_FILES['receipt']['name']);
  $targetPath = $uploadDir . time() . '_' . $fileName;
  if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetPath)) {
    $receipt_path = $targetPath;
  }
}

// ✅ Insert into DB
$stmt = $con->prepare("INSERT INTO pharmacy_services (
  full_name, hospital_name, patient_id, contact_number, appointment_date,
  card_number, receipt_path, description
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
  die("❌ Prepare failed: " . mysqli_error($con));
}

$stmt->bind_param("ssssssss", $full_name, $hospital_name, $patient_id, $contact, $date, $card_number, $receipt_path, $description);

if ($stmt->execute()) {
  echo "<script>alert('✅ Appointment submitted successfully! Thank you for booking your Pharmacy appointment'); window.location.href='pharmacydash.html';</script>";
} else {
  die("❌ Insert failed: " . mysqli_error($con));
}

$stmt->close();
$con->close();
?>