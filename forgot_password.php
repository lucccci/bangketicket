<?php
session_start();

// Include PHPMailer for sending emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path_to_phpmailer/PHPMailer.php';
require 'path_to_phpmailer/SMTP.php';
require 'path_to_phpmailer/Exception.php';

// Database connection
$servername = "localhost";
$db_username = "root"; // Replace with your DB username
$db_password = ""; // Replace with your DB password
$dbname = "bangketicketdb"; // Replace with your DB name

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Check if email exists in the database
    $sql = "SELECT * FROM admin_account WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Email exists, generate OTP
        $otp = rand(100000, 999999); // 6-digit OTP
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes")); // OTP valid for 10 minutes

        // Save OTP to database
        $sql_update = "UPDATE admin_account SET otp = '$otp', otp_expiry = '$otp_expiry' WHERE email = '$email'";
        if ($conn->query($sql_update) === TRUE) {
            // Send OTP to the email
            $mail = new PHPMailer(true);
            try {
                // SMTP server configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.example.com'; // Your SMTP host (e.g., smtp.gmail.com)
                $mail->SMTPAuth   = true;
                $mail->Username   = 'your_email@example.com'; // Your email address
                $mail->Password   = 'your_password'; // Your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipient and email content
                $mail->setFrom('your_email@example.com', 'Your App Name');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Password Reset';
                $mail->Body    = "Your OTP is: <b>$otp</b>. This OTP is valid for 10 minutes.";

                $mail->send();
                echo "<script>alert('OTP sent to your email. Please check your inbox.'); window.location.href = 'verify_otp.html';</script>";
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error updating OTP: " . $conn->error;
        }
    } else {
        // Email doesn't exist
        echo "<script>alert('Email not found. Please check and try again.'); window.location.href = 'forgot_password.html';</script>";
    }
}

$conn->close();
?>
