<?php
include 'config.php'; // Ensure this path is correct

if (isset($_POST['vendorID']) && isset($_POST['date']) && isset($_POST['amount']) && isset($_POST['collector_id'])) {
    // Sanitize and validate input values
    $vendorID = $conn->real_escape_string($_POST['vendorID']);
    $date = $conn->real_escape_string($_POST['date']);
    $amount = intval($_POST['amount']); // Ensure amount is treated as an integer
    $collector_id = $conn->real_escape_string($_POST['collector_id']); // Sanitize collector_id

    // Get the current date in the format YYYY-MM-DD (consistent with MySQL date format)
    $currentDate = date('Y-m-d');

    // Check if there's already a transaction for this vendor on the same date
    $checkQuery = "SELECT transactionID FROM vendor_transaction WHERE vendorID = '$vendorID' AND DATE(date) = '$currentDate' LIMIT 1";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult && $checkResult->num_rows > 0) {
        // Transaction already exists for this vendor on the current date
        echo json_encode(['status' => 'error', 'message' => 'Transaction already exists for this vendor today']);
    } else {
        // No transaction exists for this vendor today; proceed with creating a new transaction ID

        // Format transaction ID as "YYYYMMDD-XXX"
        $dateQuery = "SELECT DATE_FORMAT(CURDATE(), '%Y%m%d') AS currentDate";
        $dateResult = $conn->query($dateQuery);
        $currentDateRow = $dateResult->fetch_assoc();
        $currentDateFormatted = $currentDateRow['currentDate']; // Example: 20241027

        // Check for the last transaction on the same date (starting with current date YYYYMMDD)
        $query = "SELECT transactionID FROM vendor_transaction WHERE transactionID LIKE '$currentDateFormatted-%' ORDER BY transactionID DESC LIMIT 1";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            $lastTransactionID = $result->fetch_assoc()['transactionID'];
            
            // Extract the numeric part (after the last '-') and increment it
            $numericPart = intval(substr($lastTransactionID, 9)) + 1; // Start increment from position 9 (YYYYMMDD-XXX)
            $transactionID = $currentDateFormatted . '-' . str_pad($numericPart, 3, '0', STR_PAD_LEFT); // Format as "YYYYMMDD-XXX"
        } else {
            // No previous transactions for today, start from "YYYYMMDD-001"
            $transactionID = $currentDateFormatted . '-001';
        }

        // Insert data into the vendor_transaction table
        $insertQuery = "INSERT INTO vendor_transaction (transactionID, vendorID, date, amount, collector_id) VALUES ('$transactionID', '$vendorID', '$date', $amount, '$collector_id')";

        if ($conn->query($insertQuery) === TRUE) {
            echo json_encode(['status' => 'success', 'message' => 'Transaction inserted successfully', 'transactionID' => $transactionID]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error inserting transaction: ' . $conn->error]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
}

$conn->close();
?>
