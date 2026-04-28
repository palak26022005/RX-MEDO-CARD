<?php
// Database connection
$con = mysqli_connect('localhost', 'u107895813_utkarsh21', 'Kaushik001@123', 'u107895813_rxmedo');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data
$sql = "SELECT * FROM mydata";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RX Medocard – Users List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        h2 {
            font-weight: 600;
            color: #343a40;
        }

        .table thead th {
            background-color: #198754; /* Bootstrap success green */
            color: white;
            font-weight: 600;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #e2f0d9;
        }

        .btn-lg {
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
        }

        .status-active {
            color: #198754;
            font-weight: 600;
        }

        .status-pending {
            color: #6c757d;
            font-weight: 600;
        }

        /* Responsive table wrapper */
        .table-responsive {
            overflow-x: auto;
        }

        .dropdown-menu-end {
            min-width: 150px;
        }

        @media (max-width: 1200px) {
            h2 {
                font-size: 1.5rem;
            }

            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 0.95rem;
            }

            .table thead th,
            .table tbody td {
                font-size: 0.9rem;
                padding: 0.6rem;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <h2 class="mb-2 mb-md-0">RX Medocard – Users List</h2>

            <div class="d-flex gap-2">
                <a href="export_users.php" class="btn btn-success btn-lg shadow">
                    Download
                </a>

                <div class="dropdown">
                    <button class="btn btn-success btn-lg dropdown-toggle shadow" type="button" id="menuDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Menu
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="menuDropdown">
                        <li><a class="dropdown-item" href="admin_appointments.php">Appointments</a></li>
                        <li><a class="dropdown-item" href="users.php">Users</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="table-responsive shadow rounded bg-white">
            <table class="table table-bordered table-hover table-striped align-middle mb-0">
                <thead class="text-center">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Aadhaar No</th>
                        <th>PAN No</th>
                        <th>Card Type</th>
                        <th>Purchase Date</th>
                        <th>Payment Status</th>
                        <th>Ref Code</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $statusClass = ($row['status'] == 'Active') ? 'status-active' : 'status-pending';
                            echo "<tr class='text-center'>
                                <td>{$row['name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['aadhaar_card_no']}</td>
                                <td>{$row['pan_card_no']}</td>
                                <td>{$row['card_type']}</td>
                                <td>{$row['purchase_date']}</td>
                                <td class='{$statusClass}'>{$row['status']}</td>
                                <td>{$row['ref_code']}</td>
                                <td>{$row['reference']}</td>
                              </tr>";
                        }
                    } else {
                        echo "<tr>
                            <td colspan='9' class='text-center text-danger'>No records found</td>
                          </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$conn->close();
?>