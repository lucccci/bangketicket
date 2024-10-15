<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendorID'])) {
    $vendorID = $_POST['vendorID'];

    // Begin transaction for consistency
    $conn->begin_transaction();

    try {
        // Update vendor_list table, set archived_at to NULL
        $update_sql = "UPDATE vendor_list SET archived_at = NULL WHERE vendorID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $vendorID);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Delete the vendor from archive_vendors
            $delete_sql = "DELETE FROM archive_vendors WHERE vendorID = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("s", $vendorID);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Commit the transaction
                $conn->commit();
                echo 'success';
            } else {
                // Rollback if the delete fails
                $conn->rollback();
                echo 'Failed to remove vendor from archive.';
            }
        } else {
            // Rollback if the update fails
            $conn->rollback();
            echo 'Failed to restore vendor in vendor_list.';
        }
    } catch (Exception $e) {
        // Rollback the transaction on any exception
        $conn->rollback();
        echo 'Error: ' . $e->getMessage();
    }

    $stmt->close();
    $conn->close();
} else {
    echo 'Invalid request';
}
