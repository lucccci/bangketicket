<?php
require_once 'config.php';

// Fetch archived vendor data from the database
$sql = "SELECT * FROM archive_vendors";
$result = $conn->query($sql);

// Check if there are any archived vendors
if ($result->num_rows > 0) {
    $archive_vendors = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $archive_vendors = array(); // Empty array if no archived vendors found
}

// Set headers to force download the file
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=archived_vendors.csv');

// Open PHP output stream as a file handle
$output = fopen('php://output', 'w');

// Write the column headers
fputcsv($output, ['Vendor ID', 'First Name', 'Middle Name', 'Last Name', 'Contact #', 'Gender', 'Birthday', 'Province', 'Municipality', 'Barangay', 'House #', 'Street Name']);

// Write the vendor data to the CSV file
foreach ($archive_vendors as $vendor) {
    fputcsv($output, [
        $vendor['vendorID'],
        $vendor['fname'],
        $vendor['mname'],
        $vendor['lname'],
        $vendor['contactNo'],
        $vendor['gender'],
        $vendor['birthday'],
        $vendor['province'],
        $vendor['municipality'],
        $vendor['barangay'],
        $vendor['houseNo'],
        $vendor['streetname']
    ]);
}

// Close the file handle
fclose($output);
exit();
?>
