<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$servername = "localhost";
$username = "root";  // Adjust as per your DB configuration
$password = "";
$dbname = "bangketicketdb";  // Ensure this is your correct DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
