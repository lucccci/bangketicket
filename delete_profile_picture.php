<?php
// Assume you have a way to retrieve the path of the current profile picture from the database
$currentProfilePicture = 'path/to/current/profile/picture.jpg';

// Delete the file from the server
if (file_exists($currentProfilePicture)) {
    unlink($currentProfilePicture);
    // Update the database to remove the profile picture path
    // $sql = "UPDATE admin_account SET profile_picture = NULL WHERE admin_id = 'your_admin_id'";
    // mysqli_query($conn, $sql);
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
?>
