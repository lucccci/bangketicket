<?php
include 'config.php';

// Query to calculate the average revenue per day
$sql = "SELECT AVG(daily_revenue) AS avg_revenue 
        FROM (SELECT SUM(amount) AS daily_revenue
              FROM vendor_transaction
              GROUP BY DATE(date)) AS daily_totals";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo number_format($row['avg_revenue'], 2);  // Return the average revenue
} else {
    echo "0.00";  // Default value if no data found
}

$conn->close();
?>
