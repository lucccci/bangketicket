<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

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

// Function to generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Use prepared statement to check if the email exists
    $stmt = $conn->prepare("SELECT admin_id, email FROM admin_account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $admin_id = $admin['admin_id'];

        // Generate OTP
        $otp = generateOTP();

        // Store OTP in the database
        $stmt = $conn->prepare("INSERT INTO admin_otp_verification (admin_id, otp_code, expiration_time) VALUES (?, ?, NOW() + INTERVAL 3 MINUTE)");
        $stmt->bind_param("ss", $admin_id, $otp);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to store OTP.']);
            exit;
        }

        // Send email with OTP
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'balondodavenorman304@gmail.com';
            $mail->Password = 'eqiq iinl znfl ynka'; // Use app-specific password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('balondodavenorman304@gmail.com', 'BangkeTicket');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your BangkeTicket OTP for Password Reset';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #1a73e8;'>Password Reset Request</h2>
                    <p>Dear Admin,</p>
                    <p>We have received a request to reset the password for your BangkeTicket account. 
                    To proceed with the password reset, please use the OTP (One-Time Password) provided below:</p>
                    <p style='font-size: 18px; font-weight: bold; color: #1a73e8;'>$otp</p>
                    <p>This OTP is valid for <strong>3 minutes</strong>. Please enter this code in the password reset page to continue.</p>
                    <p>If you did not request a password reset, please disregard this email. Your account will remain secure.</p>
                    <p>Thank you for using BangkeTicket!</p>
                </div>";

                $mail->send();
    
                // Ensure response is valid JSON with success
                $response = ['success' => true, 'message' => 'OTP has been sent to your email.'];
                echo json_encode($response);
                error_log("OTP sent successfully: " . json_encode($response)); // Log the successful response
            } catch (Exception $e) {
                error_log("OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                echo json_encode(['success' => false, 'message' => "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
            }
        }
    }