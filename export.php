<?php
require_once 'config.php';

// Fetch vendor data from the database
$sql = "SELECT * FROM vendor_list"; // Replace 'vendor_list' with the actual table name
$result = $conn->query($sql);

// Check if there are any results
if ($result->num_rows > 0) {
    // Set headers for CSV file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="vendors.csv"');

    $output = fopen('php://output', 'w');

    // Output the column headings
    fputcsv($output, ['Vendor ID', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 'Gender', 'Birthday', 'Age', 'Contact No', 'Province', 'Municipality', 'Barangay', 'House No', 'Street Name']);

    // Fetch and output the data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
} else {
    echo "No data available.";
}
?>