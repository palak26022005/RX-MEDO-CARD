<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) {
    die("DB Connection failed: " . mysqli_connect_error());
}

$seller_id = strtolower(trim($_POST['seller_id']));
$password = $_POST['password'];

// ✅ Updated table name: sellerlogin
$stmt = $con->prepare("SELECT * FROM sellerlogin WHERE LOWER(username)=? AND password=? LIMIT 1");
$stmt->bind_param("ss", $seller_id, $password);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user = $res->fetch_assoc();
    $_SESSION['username'] = $user['username'];
    $_SESSION['ref_code'] = $user['ref_code'];

    echo "<script>
            alert(' ✅  Login Successful');
            window.location.href='salertable.php';
          </script>";
    exit;
} else {
    echo "<script>
            alert('Invalid Login');
            window.location.href='salerlogin.html';
          </script>";
}
?>