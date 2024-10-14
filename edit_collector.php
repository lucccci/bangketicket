<?php
// Database connection
$servername = "localhost";
$username = "root"; // Adjust according to your database username
$password = "";     // Adjust according to your database password
$dbname = "bangketicketdb"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $collectorId = $_POST['collector_id'];
    $firstName = $_POST['firstName'];
    $midName = $_POST['MidName'];
    $lastName = $_POST['lastName'];
    $suffix = $_POST['suffix'];
    $birthday = $_POST['birthday'];

    // Prepare the SQL query to update the collector's information
    $sql = "UPDATE collectors SET fname = '$firstName', mname = '$midName', lname = '$lastName', suffix = '$suffix', birthday = '$birthday' WHERE collector_id = '$collectorId'";

    // Execute the query and check for success
    if ($conn->query($sql) === TRUE) {
        echo "Collector updated successfully.";
    } else {
        echo "Error: " . $conn->error;
    }

    // Redirect back to the collector page
    header("Location: collector.php");
    exit();
}

// Close the database connection
$conn->close();
?>
