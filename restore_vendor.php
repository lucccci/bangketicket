<?php
require_once 'config.php'; // Include database connection

// Define the directories for QR code images
$archivePath = 'archivedqr/';
$imagesPath = 'images/';

if (isset($_POST['vendorID'])) {
    $vendorID = $_POST['vendorID'];

    // Fetch vendor data from archive_vendors
    $sqlFetch = "SELECT * FROM archive_vendors WHERE vendorID = ?";
    $stmtFetch = $conn->prepare($sqlFetch);
    $stmtFetch->bind_param('s', $vendorID);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();

    if ($result->num_rows > 0) {
        $vendor = $result->fetch_assoc();

        // Step 1: Insert the vendor data back into vendor_list
        $sqlInsert = "INSERT INTO vendor_list (vendorID, fname, mname, lname, contactNo, suffix, gender, birthday, age, province, municipality, barangay, houseNo, streetname, qrimage)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param(
            'sssssssssssssss',
            $vendor['vendorID'],
            $vendor['fname'],
            $vendor['mname'],
            $vendor['lname'],
            $vendor['contactNo'],
            $vendor['suffix'],
            $vendor['gender'],
            $vendor['birthday'],
            $vendor['age'],
            $vendor['province'],
            $vendor['municipality'],
            $vendor['barangay'],
            $vendor['houseNo'],
            $vendor['streetname'],
            $vendor['qrimage']
        );

        if ($stmtInsert->execute()) {
            // Step 2: Move the QR image from archivedqr to images folder
            $qrFile = $archivePath . $vendor['qrimage'];
            $newQrFile = $imagesPath . $vendor['qrimage'];

            // Check if the QR code file exists in the archive folder
            if (file_exists($qrFile)) {
                // Move the file to the images folder
                if (!rename($qrFile, $newQrFile)) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to move QR code file.']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'QR code file not found in the archive folder.']);
                exit();
            }

            // Step 3: Remove the vendor from archive_vendors
            $sqlDelete = "DELETE FROM archive_vendors WHERE vendorID = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param('s', $vendorID);
            $stmtDelete->execute();

            // Return an empty response on success
            echo "";
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to restore vendor.']);
        }
    }
    $stmtFetch->close();
    $stmtInsert->close();
    $stmtDelete->close();
}

$conn->close();
?>
