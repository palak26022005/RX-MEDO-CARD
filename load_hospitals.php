<?php
// ✅ Direct DB connection (since db.php file not available)
$con = mysqli_connect('localhost', 'root', '', 'medocard');


if (!$con) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// ✅ Get service type from request
$type = $_GET['type'] ?? '';

$mdCityFullName = 'MD City Hospital, Parthala Chowk, PKC-12, Sector-122 (Free OPD)';

// ✅ If Radiology selected → fetch Diagnostic hospitals with MD City first
if ($type === 'Radiology') {
    $sql = "SELECT * FROM service_providers 
            WHERE service_type = 'Diagnostic'
            GROUP BY name
            ORDER BY 
              CASE WHEN name LIKE '%MD City Hospital%' THEN 0 ELSE 1 END,
              name ASC";
} else {
    // ✅ For other service types → filter by exact service_type
    $safeType = mysqli_real_escape_string($con, $type);
    $sql = "SELECT * FROM service_providers 
            WHERE service_type = '$safeType'
            GROUP BY name
            ORDER BY 
              CASE WHEN name LIKE '%MD City Hospital%' THEN 0 ELSE 1 END,
              name ASC";
}

// ✅ Run query
$result = mysqli_query($con, $sql);
if (!$result) {
    die("❌ Query failed: " . mysqli_error($con));
}

// ✅ Avoid duplicates
$shown = [];

while ($row = mysqli_fetch_assoc($result)) {
    $name = $row['name'];

    // Normalize MD City Hospital name
    if (stripos($name, 'MD City Hospital') !== false) {
        if (in_array('mdcity', $shown)) continue; // avoid duplicate
        $name = $mdCityFullName;
        $shown[] = 'mdcity';
    } else {
        if (in_array($name, $shown)) continue; // avoid duplicate
        $shown[] = $name;
    }

    // ✅ Output each hospital option
    echo "<div data-id='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($name) . "</div>";
}
?>