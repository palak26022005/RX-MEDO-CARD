<?php
// Test script to verify signup functionality
session_start();

// Simulate POST data for testing
$_POST = [
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'phone' => '9876543210',
    'password' => 'test123',
    'aadhaar_card_no' => '123456789012',
    'pan_card_no' => 'ABCDE1234F',
    'type' => 'family',
    'age' => '36-45',
    'benefits' => 'basic',
    'card_type' => 'RX Medo Family Card',
    'reference' => '',
    'coupon_code' => '',
    'wife_name' => 'Test Wife',
    'wife_dob' => '1990-01-01',
    'wife_relation' => 'Wife',
    'child1_name' => 'Test Child 1',
    'child1_dob' => '2015-01-01',
    'child1_relation' => 'Child'
];

echo "<h2>Testing Signup Process</h2>";
echo "<pre>POST Data: " . print_r($_POST, true) . "</pre>";

// Database connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Collect user info (same as signup.php)
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

$reference = $_POST['reference'] ?? '';
$ref_code = strtolower(str_replace(' ', '', $name)) . rand(1000,9999);
$coupon_code = $_POST['coupon_code'] ?? '';

// Family info
$wife_name     = $_POST['wife_name'] ?? null;
$wife_dob      = $_POST['wife_dob'] ?? null;
$wife_relation = $_POST['wife_relation'] ?? null;

$child1_name     = $_POST['child1_name'] ?? null;
$child1_dob      = $_POST['child1_dob'] ?? null;
$child1_relation = $_POST['child1_relation'] ?? 'Child';

echo "<h3>Collected Family Data:</h3>";
echo "Wife Name: $wife_name<br>";
echo "Wife DOB: $wife_dob<br>";
echo "Wife Relation: $wife_relation<br>";
echo "Child 1 Name: $child1_name<br>";
echo "Child 1 DOB: $child1_dob<br>";
echo "Child 1 Relation: $child1_relation<br>";

// Check if email exists
$check = $con->prepare("SELECT id FROM mydata WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "<p style='color:orange;'>Email already exists (this is normal for testing)</p>";
} else {
    // Test the INSERT statement structure
    echo "<h3>Testing SQL INSERT Structure:</h3>";
    
    $status = "pending_payment";
    
    // Create the same INSERT statement as signup.php
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
        echo "<p style='color:red;'>SQL Prepare Error: " . mysqli_error($con) . "</p>";
    } else {
        echo "<p style='color:green;'>SQL Prepare Success - Statement structure is valid</p>";
        
        // Test bind parameters
        $aadhaarFront = null;
        $aadhaarBack = null;
        $panPhoto = null;
        $child2_name = null;
        $child2_dob = null;
        $child2_relation = null;
        $child3_name = null;
        $child3_dob = null;
        $child3_relation = null;
        $child4_name = null;
        $child4_dob = null;
        $child4_relation = null;
        
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
            echo "<p style='color:red;'>Bind Param Error: " . $stmt->error . "</p>";
        } else {
            echo "<p style='color:green;'>Bind Param Success - All parameters bound correctly</p>";
            
            // Actually execute the insert for testing
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                echo "<p style='color:green;'>SUCCESS! Data inserted with ID: $userId</p>";
                
                // Verify the data was inserted correctly
                $verify = $con->query("SELECT * FROM mydata WHERE id = $userId");
                if ($row = $verify->fetch_assoc()) {
                    echo "<h3>Verification - Inserted Data:</h3>";
                    echo "<table border='1'>";
                    echo "<tr><td>Name</td><td>{$row['name']}</td></tr>";
                    echo "<tr><td>Email</td><td>{$row['email']}</td></tr>";
                    echo "<tr><td>Wife Name</td><td>{$row['wife_name']}</td></tr>";
                    echo "<tr><td>Wife DOB</td><td>{$row['wife_dob']}</td></tr>";
                    echo "<tr><td>Child 1 Name</td><td>{$row['child1_name']}</td></tr>";
                    echo "<tr><td>Child 1 DOB</td><td>{$row['child1_dob']}</td></tr>";
                    echo "</table>";
                    
                    // Clean up test data
                    $con->query("DELETE FROM mydata WHERE id = $userId");
                    echo "<p style='color:blue;'>Test data cleaned up</p>";
                }
            } else {
                echo "<p style='color:red;'>Execute Error: " . $stmt->error . "</p>";
            }
        }
        $stmt->close();
    }
}

$check->close();
$con->close();

echo "<p><a href='signup.html'>Go to Signup Form</a></p>";
?>
