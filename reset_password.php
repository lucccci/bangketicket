<?php
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

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    // Get the new password from the request
    $new_password = $_POST['new_password'];

    // Optional: Validate password (e.g., length, complexity)
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        exit;
    }

    // Retrieve the user ID from the session or other tracking method
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired or invalid user.']);
        exit;
    }
    $admin_id = $_SESSION['admin_id'];

    // Update the password in the database without hashing
    $stmt = $conn->prepare("UPDATE admin_account SET password = ? WHERE admin_id = ?");
    $stmt->bind_param("ss", $new_password, $admin_id);

    if ($stmt->execute()) {
        // Clear any existing OTP for security
        $clear_otp_stmt = $conn->prepare("UPDATE admin_otp_verification SET is_used = 1 WHERE admin_id = ?");
        $clear_otp_stmt->bind_param("s", $admin_id);
        $clear_otp_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Password reset successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
