<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    echo "<script>alert('Unauthorized access! Please login first.'); window.location.href='admin_login.html';</script>";
    exit;
}

// ✅ Database connection
$con = mysqli_connect('localhost', 'root', '', 'medocard');




if (!$con) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

// ✅ Fetch appointment + user details (JOIN both tables)
$query = "
    SELECT 
        sr.id,
        md.name,
        md.email,
        md.phone,
        sr.card_no,
        sr.service_type,
        sr.provider_id,
        sr.hospital_name,
        sr.appointment_date,
        sr.request_time,
        sr.prescription_image,
        sr.appointment_time
    FROM service_request sr
    LEFT JOIN mydata md ON sr.card_no = md.serial_rx_medo_card 
        OR sr.card_no = md.serial_rx_medo_family_card
        OR sr.card_no = md.serial_rx_medo_youngshield_card
        OR sr.card_no = md.serial_rx_medo_citizencare_card
        OR sr.card_no = md.serial_rx_medo_guardiancare_card
        OR sr.card_no = md.serial_rx_medo_seniorshield_card
        OR sr.card_no = md.serial_rx_medo_familysecure_card
        OR sr.card_no = md.serial_rx_medo_topup_card
        OR sr.card_no = md.serial_rx_medo_family_topup_card
    ORDER BY sr.id DESC
";

$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RX Medocard - Appointment Entries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table thead th {
            background-color: #198754;
            /* Bootstrap success green */
            color: white;
            font-weight: 600;
            vertical-align: middle;
        }
    </style>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">RX Medocard – Appointment Requests</h2>

        <div class="dropdown">
            <button
                class="btn btn-success btn-lg dropdown-toggle shadow"
                type="button"
                id="menuDropdown"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                Menu
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <a class="dropdown-item" href="admin_appointments.php">
                        Appointments
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="users.php">
                        Users
                    </a>
                </li>
                <!-- ✅ New Coupon Option -->
                <li> <a class="dropdown-item" href="admin_coupons.php">Coupons</a> </li>
            </ul>
        </div>
    </div>


    <script>
        function goToReferrals() {
            window.location.href = "referrals.php";
        }
    </script>



    <table class="table table-bordered table-hover table-striped">
        <thead class="table-success">
            <tr>
                <th>Sr.no</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Card No</th>
                <th>Service Type</th>
                <th>Provider ID</th>
                <th>Hospital Name</th>
                <th>Appointment Date</th>
                <th>Appointment Time</th>
                <th>Request Time</th>
                <th>Prescription Image Path</th>
                <th>Open</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $count = 1;

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {

                    $imageFile = $row['prescription_image'];
                    $imagePath = !empty($imageFile) ? $imageFile : "";

                    echo "
                    <tr>
                        <td>{$count}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['card_no']}</td>
                        <td>{$row['service_type']}</td>
                        <td>{$row['provider_id']}</td>
                        <td>{$row['hospital_name']}</td>
                        <td>{$row['appointment_date']}</td>
                        <td>{$row['appointment_time']}</td>
                        <td>{$row['request_time']}</td>

                        <!-- ✅ SHOW RAW PATH FROM DATABASE -->
                        <td>";
                    if (!empty($imageFile)) {
                        echo "<a href='{$imagePath}' target='_blank'>" . htmlspecialchars($imagePath) . "</a>";
                    } else {
                        echo "<span class='text-muted'>No image</span>";
                    }
                    echo "</td>

                        <!-- ✅ OPEN IMAGE IN NEW TAB -->
                        <td>";
                    if (!empty($imageFile)) {
                        echo "<a href='{$imagePath}' target='_blank' class='btn btn-success btn-sm'>Open</a>";
                    } else {
                        echo "<span class='text-muted'>—</span>";
                    }
                    echo "</td>
                    </tr>
                    ";

                    $count++;
                }
            } else {
                echo "<tr><td colspan='13' class='text-center text-muted'>No entries found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


</html>