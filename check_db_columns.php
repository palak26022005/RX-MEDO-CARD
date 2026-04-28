<?php
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Database Column Check for Family Details</h2>";

// Check all family-related columns
$columns_to_check = [
    'spouse_name', 'spouse_dob', 'spouse_relation',
    'wife_name', 'wife_dob', 'wife_relation',
    'child1_name', 'child1_dob', 'child1_relation',
    'child2_name', 'child2_dob', 'child2_relation',
    'child3_name', 'child3_dob', 'child3_relation',
    'child4_name', 'child4_dob', 'child4_relation'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Column Name</th><th>Exists</th><th>Type</th><th>Action Needed</th></tr>";

foreach ($columns_to_check as $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$column'");
    $exists = mysqli_num_rows($result) > 0;
    
    echo "<tr>";
    echo "<td>$column</td>";
    
    if ($exists) {
        $row = mysqli_fetch_assoc($result);
        echo "<td style='color:green;'>YES</td>";
        echo "<td>{$row['Type']}</td>";
        
        // Determine if this column should be used
        if (strpos($column, 'wife_') === 0) {
            echo "<td style='color:orange;'>Old column - should be renamed to spouse_*</td>";
        } else {
            echo "<td style='color:green;'>Correct column</td>";
        }
    } else {
        echo "<td style='color:red;'>NO</td>";
        echo "<td>-</td>";
        
        if (strpos($column, 'spouse_') === 0) {
            echo "<td style='color:red;'>MISSING - needs to be created</td>";
        } else {
            echo "<td style='color:blue;'>Optional - not required</td>";
        }
    }
    
    echo "</tr>";
    mysqli_free_result($result);
}

echo "</table>";

// Check if we need to create or rename columns
echo "<h3>Recommended Actions:</h3>";

$spouse_missing = [];
$wife_exists = [];

foreach (['spouse_name', 'spouse_dob', 'spouse_relation'] as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($result) == 0) {
        $spouse_missing[] = $col;
    }
}

foreach (['wife_name', 'wife_dob', 'wife_relation'] as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($result) > 0) {
        $wife_exists[] = $col;
    }
}

if (!empty($spouse_missing) && !empty($wife_exists)) {
    echo "<p style='color:red; font-weight:bold;'>ISSUE FOUND: Spouse columns are missing but wife columns exist.</p>";
    echo "<p>Solution: Either rename wife_* columns to spouse_* OR update PHP to use wife_* column names.</p>";
    
    echo "<h4>Option 1: Rename wife columns to spouse columns:</h4>";
    foreach ($wife_exists as $wife_col) {
        $spouse_col = str_replace('wife_', 'spouse_', $wife_col);
        echo "ALTER TABLE mydata RENAME COLUMN $wife_col TO $spouse_col;<br>";
    }
    
    echo "<h4>Option 2: Update PHP to use wife column names:</h4>";
    echo "Change PHP variables from \$spouse_* to \$wife_* in signup.php<br>";
} elseif (!empty($spouse_missing)) {
    echo "<p style='color:red; font-weight:bold;'>ISSUE: Spouse columns are missing and need to be created.</p>";
    foreach ($spouse_missing as $col) {
        $type = strpos($col, 'dob') !== false ? 'DATE' : 'VARCHAR(255)';
        echo "ALTER TABLE mydata ADD COLUMN $col $type NULL;<br>";
    }
} else {
    echo "<p style='color:green;'>All spouse columns exist - database structure is correct.</p>";
}

$con->close();
?>
