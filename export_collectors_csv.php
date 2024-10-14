<?php
// Database connection
$servername = "localhost"; // Change if needed
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "bangketicketdb"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to get data from the archive_collectors table
$sql = "SELECT collector_id, fname AS first_name, mname AS middle_name, lname AS last_name, suffix, birthday FROM archive_collectors";
$result = $conn->query($sql);

// Check if the query executed successfully
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Prepare CSV file header
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=archive_collectors.csv');

// Output the column names
$output = fopen('php://output', 'w');
fputcsv($output, array('Collector ID', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 'Birthday'));

// Output data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

// Close the output stream
fclose($output);

// Close the database connection
$conn->close();
exit();
?>
