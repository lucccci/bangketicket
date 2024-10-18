<?php
// Enable error reporting to catch any issues
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
include 'config.php';
// Query to get the count of registered vendors
$sql = "SELECT COUNT(*) as vendorCount FROM vendor_list";  // Ensure table name is correct
$result = $conn->query($sql);

// Check and return the count
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['vendorCount'];  // Output the count
} else {
    echo "0";  // Return 0 if no vendors found or query fails
}

$conn->close();
?>
