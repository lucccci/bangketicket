<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
include 'config.php';

// Query to sum today's revenue
$sql = "SELECT SUM(amount) as totalRevenue FROM vendor_transaction WHERE DATE(date) = CURDATE()";
$result = $conn->query($sql);

// Initialize total revenue
$totalRevenue = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $totalRevenue = $row['totalRevenue'] ? $row['totalRevenue'] : 0; // Ensure it's not null
    echo number_format($totalRevenue, 2);  // Output as formatted number
} else {
    // Output the error message
    echo "Error in SQL query: " . $conn->error;
}

// Close connection
$conn->close();
?>
