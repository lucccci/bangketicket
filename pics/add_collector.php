<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "bangketicketdb"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstName = $_POST['first_name'];
    $MidName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];
    $suffix = $_POST['suffix'];
    $birthday = $_POST['birthday'];

    // Check if all required fields are filled
    if(!empty($firstName) && !empty($MidName) && !empty($lastName) && !empty($birthday)) {
        // Prepare SQL statement to insert the data
        $sql = "INSERT INTO collectors (first_name, middle_name, last_name, suffix, birthday)
                VALUES (?, ?, ?, ?, ?)";

        // Prepare and bind
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $firstName, $MidName, $lastName, $suffix, $birthday);

        // Execute the statement
        if ($stmt->execute()) {
            // Redirect to collector.php to see the updated table
            header("Location: collector.php?success=1");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Please fill in all required fields.";
    }

    // Close the connection
    $conn->close();
}
?>
