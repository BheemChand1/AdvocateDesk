<?php
require_once 'includes/connection.php';

// Check what case types exist
$query = "SELECT DISTINCT case_type FROM cases";
$result = mysqli_query($conn, $query);
echo "Case types in database:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['case_type'] . "\n";
}

// Check NI/PASSA table structure
$query = "DESCRIBE case_ni_passa_details";
$result = mysqli_query($conn, $query);
echo "\n\nNI/PASSA table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . "\n";
}

// Check if there's any data
$query = "SELECT COUNT(*) as cnt FROM case_ni_passa_details";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
echo "\nTotal NI/PASSA records: " . $row['cnt'] . "\n";

// Check Criminal
$query = "DESCRIBE case_criminal_details";
$result = mysqli_query($conn, $query);
echo "\n\nCriminal table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . "\n";
}

// Check if there's any data
$query = "SELECT COUNT(*) as cnt FROM case_criminal_details";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
echo "\nTotal Criminal records: " . $row['cnt'] . "\n";

// Check Consumer/Civil
$query = "DESCRIBE case_consumer_civil_details";
$result = mysqli_query($conn, $query);
echo "\n\nConsumer/Civil table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . "\n";
}

// Check EP/Arbitration
$query = "DESCRIBE case_ep_arbitration_details";
$result = mysqli_query($conn, $query);
echo "\n\nEP/Arbitration table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . "\n";
}

// Check Arbitration Other
$query = "DESCRIBE case_arbitration_other_details";
$result = mysqli_query($conn, $query);
echo "\n\nArbitration Other table columns:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "- " . $row['Field'] . "\n";
}
?>
