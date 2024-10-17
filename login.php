<?php
session_start();

// Database connection settings
$servername = "localhost"; // Usually 'localhost' for local environments like XAMPP
$db_username = "root"; // Your MySQL username (default for XAMPP is 'root')
$db_password = ""; // Your MySQL password (default for XAMPP is usually empty)
$dbname = "bangketicketdb"; // Your database name as shown in the image

// Create connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sanitize inputs to prevent SQL injection
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);

    // Query to check if the username and password match in the database
    $sql = "SELECT * FROM admin_account WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Login successful, store session variables and redirect to dashboard
        $_SESSION['username'] = $username;
        header("Location: dashboard.html");
        exit();
    } else {
        // Invalid login, show error message
        echo "<script>alert('Incorrect username or password. Please try again.'); window.location.href = 'index.html';</script>";
    }
}

$conn->close();
?>
