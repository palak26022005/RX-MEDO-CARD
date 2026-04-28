<?php
session_start();


// ✅ Database connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');



if (!$con) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ✅ Check admin credentials from database
    $stmt = $con->prepare("SELECT * FROM admin_users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    // ✅ If match found → login success
    if ($result->num_rows === 1) {

        $_SESSION['admin_logged_in'] = true;

        echo "<script>
                alert('✅ Login successful!');
                window.location.href='admin_appointments.php';
              </script>";
        exit;

    } else {
        echo "<script>
                alert('❌ Incorrect username or password');
                window.history.back();
              </script>";
        exit;
    }
}
?>