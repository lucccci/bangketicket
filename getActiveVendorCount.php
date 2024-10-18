<?php
// getActiveVendorCount.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
include 'config.php';

// Query to count active vendors without filtering by date
$sql = "SELECT COUNT(DISTINCT vendorID) AS active_vendors 
        FROM vendor_transaction";

$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo $row['active_vendors'];
} else {
    echo "Error: " . $conn->error; // This will help in debugging
}

$conn->close();
?>
