<?php
// getActiveVendorCount.php

// Database connection
include 'config.php';

// Query to count vendors who have at least one transaction
$sql = "SELECT COUNT(DISTINCT vendorID) AS active_vendors
        FROM vendor_transaction
        WHERE vendorID IS NOT NULL"; // Only consider vendors with transactions

$result = $conn->query($sql);

if (!$result) {
    echo "SQL Error: " . $conn->error; // This will show SQL errors
} else {
    $row = $result->fetch_assoc();
    if ($row) {
        echo $row['active_vendors']; // Output the active vendor count
    } else {
        echo "0"; // Default output if no active vendors are found
    }
}

$conn->close();
?>
