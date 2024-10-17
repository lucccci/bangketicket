<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bangketicketdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    // Get the username and password from the request
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // Prepare and execute the query to fetch the user details
    $stmt = $conn->prepare("SELECT * FROM admin_account WHERE username = ?");
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the username exists
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        // Compare the entered password with the stored password
        if ($input_password === $admin['password']) {
            // Successful login
            session_start();
            $_SESSION['admin_id'] = $admin['admin_id']; // Store admin_id in session
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            // Incorrect password
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        }
    } else {
        // Username does not exist
        echo json_encode(['success' => false, 'message' => 'Username not found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
