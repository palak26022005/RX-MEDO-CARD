<?php
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Checking database columns for family details...\n";

// Check for spouse columns
$spouse_columns = ['spouse_name', 'spouse_dob', 'spouse_relation'];
$wife_columns = ['wife_name', 'wife_dob', 'wife_relation'];

echo "\n=== Checking Spouse Columns ===\n";
foreach ($spouse_columns as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($result) > 0) {
        echo "FOUND: $col\n";
    } else {
        echo "MISSING: $col\n";
    }
    mysqli_free_result($result);
}

echo "\n=== Checking Wife Columns (old names) ===\n";
foreach ($wife_columns as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($result) > 0) {
        echo "FOUND: $col\n";
    } else {
        echo "MISSING: $col\n";
    }
    mysqli_free_result($result);
}

$con->close();
?>
