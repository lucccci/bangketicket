<?php
include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve the vendor ID from the request
$vendorID = $_GET['vendorID'];

// Sanitize the input
$vendorID = mysqli_real_escape_string($conn, $vendorID);

// Query to fetch vendor details (last name, first name, middle initial, and lotArea)
$sql = "SELECT lname, fname, SUBSTRING(mname, 1, 1) AS middle_initial, lotArea FROM vendor_list WHERE vendorID = '$vendorID'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the vendor details
    $row = $result->fetch_assoc();
    $full_name = $row['lname'] . ', ' . $row['fname'];
    $lotArea = $row['lotArea'];
    echo json_encode([
        'success' => true,
        'full_name' => $full_name,
        'lotArea' => $lotArea, // Include lotArea in the response
    ]);
} else {
    // If no match is found, return an error
    echo json_encode([
        'success' => false,
        'message' => 'Vendor not found'
    ]);
}

$conn->close();
?>
