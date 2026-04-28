<?php
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Structure Check</h2>";

// Get table structure
$result = mysqli_query($con, "DESCRIBE mydata");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

// Check specifically for family columns
echo "<h3>Family Detail Columns Check:</h3>";
$family_columns = ['wife_name', 'wife_dob', 'wife_relation', 'child1_name', 'child1_dob', 'child1_relation', 
                   'child2_name', 'child2_dob', 'child2_relation', 'child3_name', 'child3_dob', 'child3_relation',
                   'child4_name', 'child4_dob', 'child4_relation'];

foreach ($family_columns as $col) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($check) > 0) {
        echo "<span style='color:green;'>$col - EXISTS</span><br>";
    } else {
        echo "<span style='color:red;'>$col - MISSING</span><br>";
    }
}

$con->close();
?>
