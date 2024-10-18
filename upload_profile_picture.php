<?php
// Make sure to check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Define the target directory for uploads
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);

    // Attempt to move the uploaded file
    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
        // Save the file path to the database (You would have to connect to your DB and update the user's profile)
        // Example:
        // $sql = "UPDATE admin_account SET profile_picture = '$targetFile' WHERE admin_id = 'your_admin_id'";
        // mysqli_query($conn, $sql);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
