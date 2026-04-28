<?php
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== Checking Database Columns ===\n";

// Check spouse columns
$spouse_cols = ['spouse_name', 'spouse_dob', 'spouse_relation'];
$wife_cols = ['wife_name', 'wife_dob', 'wife_relation'];

echo "\nSpouse Columns:\n";
foreach ($spouse_cols as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    echo "- $col: " . (mysqli_num_rows($result) > 0 ? "EXISTS" : "MISSING") . "\n";
}

echo "\nWife Columns (old):\n";
foreach ($wife_cols as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    echo "- $col: " . (mysqli_num_rows($result) > 0 ? "EXISTS" : "MISSING") . "\n";
}

$con->close();
?>
