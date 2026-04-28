<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Connection Test ===\n";
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Database connected successfully!\n\n";

echo "=== Checking Family Columns ===\n";
$family_columns = ['wife_name', 'wife_dob', 'wife_relation', 'child1_name', 'child1_dob', 'child1_relation'];

foreach ($family_columns as $col) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM mydata LIKE '$col'");
    if (mysqli_num_rows($result) > 0) {
        echo "SUCCESS: $col column exists\n";
    } else {
        echo "ERROR: $col column missing\n";
    }
    mysqli_free_result($result);
}

echo "\n=== Testing Sample Data Insert ===\n";
$test_email = 'test_' . time() . '@example.com';

// Prepare the same statement as signup.php
$stmt = $con->prepare("INSERT INTO mydata (
    name, email, phone, password, aadhaar_card_no, pan_card_no,
    card_type, age_group, user_type, insurance_status,
    aadhaar_front, aadhaar_back, pan_card_photo,
    wife_name, wife_dob, wife_relation,
    child1_name, child1_dob, child1_relation,
    child2_name, child2_dob, child2_relation,
    child3_name, child3_dob, child3_relation,
    child4_name, child4_dob, child4_relation,
    status, ref_code, reference
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

if ($stmt === false) {
    echo "ERROR: " . mysqli_error($con) . "\n";
} else {
    echo "SQL statement prepared successfully\n";
    
    // Bind parameters
    $name = 'Test User';
    $email = $test_email;
    $phone = '9876543210';
    $password = 'test123';
    $aadhaar = '123456789012';
    $pan = 'ABCDE1234F';
    $selectedCard = 'RX Medo Family Card';
    $ageGroup = '36-45';
    $userType = 'family';
    $insurance = 'basic';
    $aadhaarFront = null;
    $aadhaarBack = null;
    $panPhoto = null;
    $wife_name = 'Test Wife';
    $wife_dob = '1990-01-01';
    $wife_relation = 'Wife';
    $child1_name = 'Test Child';
    $child1_dob = '2015-01-01';
    $child1_relation = 'Child';
    $child2_name = null;
    $child2_dob = null;
    $child2_relation = null;
    $child3_name = null;
    $child3_dob = null;
    $child3_relation = null;
    $child4_name = null;
    $child4_dob = null;
    $child4_relation = null;
    $status = 'pending_payment';
    $ref_code = 'test1234';
    $reference = '';
    
    $bind_result = $stmt->bind_param(
        "sssssssssssssssssssssssssssssss",
        $name, $email, $phone, $password, $aadhaar, $pan,
        $selectedCard, $ageGroup, $userType, $insurance,
        $aadhaarFront, $aadhaarBack, $panPhoto,
        $wife_name, $wife_dob, $wife_relation,
        $child1_name, $child1_dob, $child1_relation,
        $child2_name, $child2_dob, $child2_relation,
        $child3_name, $child3_dob, $child3_relation,
        $child4_name, $child4_dob, $child4_relation,
        $status, $ref_code, $reference
    );
    
    if ($bind_result === false) {
        echo "ERROR: " . $stmt->error . "\n";
    } else {
        echo "Parameters bound successfully\n";
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            echo "SUCCESS: Test data inserted with ID: $userId\n";
            
            // Verify the data
            $result = mysqli_query($con, "SELECT name, email, wife_name, wife_dob, child1_name, child1_dob FROM mydata WHERE id = $userId");
            if ($row = mysqli_fetch_assoc($result)) {
                echo "Verification:\n";
                echo "- Name: " . $row['name'] . "\n";
                echo "- Email: " . $row['email'] . "\n";
                echo "- Wife Name: " . $row['wife_name'] . "\n";
                echo "- Wife DOB: " . $row['wife_dob'] . "\n";
                echo "- Child Name: " . $row['child1_name'] . "\n";
                echo "- Child DOB: " . $row['child1_dob'] . "\n";
                echo "Family details saved correctly!\n";
                
                // Clean up
                mysqli_query($con, "DELETE FROM mydata WHERE id = $userId");
                echo "Test data cleaned up\n";
            }
        } else {
            echo "ERROR: " . $stmt->error . "\n";
        }
    }
    $stmt->close();
}

$con->close();
echo "\n=== Test Complete ===\n";
?>
