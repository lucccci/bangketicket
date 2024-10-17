<?php
session_start(); // Start the session

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

// Handle OTP verification request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $otp = $_POST['otp'];

    // Check if OTP exists in the database and is not used
    $stmt = $conn->prepare("SELECT admin_id FROM admin_otp_verification WHERE otp_code = ? AND is_used = 0 AND expiration_time > NOW()");
    $stmt->bind_param("s", $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // OTP is valid
        $admin = $result->fetch_assoc();
        $admin_id = $admin['admin_id'];

        // Mark OTP as used
        $stmt = $conn->prepare("UPDATE admin_otp_verification SET is_used = 1 WHERE otp_code = ?");
        $stmt->bind_param("s", $otp);
        $stmt->execute();

        // Set the admin_id in the session
        $_SESSION['admin_id'] = $admin_id;

        echo json_encode(['success' => true, 'message' => 'OTP verified successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
