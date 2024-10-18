<?php
// Database connection
include 'config.php';

// Get the collector ID from the URL
$collector_id = $_GET['id'];

// Step 1: Restore the collector by setting `archived_at` to NULL
$sql_restore = "UPDATE collectors SET archived_at = NULL WHERE collector_id = '$collector_id'";

// Step 2: Remove the collector from the `archive_collectors` table
$sql_delete_archive = "DELETE FROM archive_collectors WHERE collector_id = '$collector_id'";

// Execute both queries in a transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Restore the collector
    if ($conn->query($sql_restore) === TRUE) {
        // Remove the collector from the archive table
        if ($conn->query($sql_delete_archive) === TRUE) {
            // Commit the transaction if both queries are successful
            $conn->commit();
            echo "Collector restored successfully.";
            // Redirect back to the archive page after restoration
            header("Location: archive.php");
            exit();
        } else {
            throw new Exception("Error removing collector from archive: " . $conn->error);
        }
    } else {
        throw new Exception("Error restoring collector: " . $conn->error);
    }
} catch (Exception $e) {
    // Roll back the transaction in case of any error
    $conn->rollback();
    echo $e->getMessage();
}

// Close the connection
$conn->close();
?>
