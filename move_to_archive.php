<?php
// Database connection
$servername = "localhost"; // Change if needed
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "bangketicketdb"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if collector_id is passed
if (isset($_GET['id'])) {
    $collector_id = trim($_GET['id']); // Trim to remove any extra spaces

    // Check if the collector_id already exists in the archive_collectors table
    $sql_check = "SELECT collector_id FROM archive_collectors WHERE collector_id = ?";
    $stmt_check = $conn->prepare($sql_check);

    if ($stmt_check === false) {
        die("Error in SQL query (check archive): " . $conn->error);
    }

    $stmt_check->bind_param("s", $collector_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Collector already exists in archive, skip the insertion or update as needed
        echo "<script>alert('Collector with ID: $collector_id is already archived.'); window.location.href='collector.php';</script>";
    } else {
        // SQL query to copy data from collectors to archive_collectors
        $sql_copy = "INSERT INTO archive_collectors (collector_id, fname, mname, lname, suffix, birthday)
                     SELECT collector_id, fname, mname, lname, suffix, birthday
                     FROM collectors WHERE collector_id = ?";

        // Prepare and bind
        $stmt_copy = $conn->prepare($sql_copy);

        if ($stmt_copy === false) {
            die("Error in SQL query (copy to archive): " . $conn->error);
        }

        $stmt_copy->bind_param("s", $collector_id);

        if ($stmt_copy->execute()) {
            // After copying to archive, update the collector's archived_at timestamp
            $sql_update = "UPDATE collectors SET archived_at = NOW() WHERE collector_id = ?";
            $stmt_update = $conn->prepare($sql_update);

            if ($stmt_update === false) {
                die("Error in SQL query (update collector status): " . $conn->error);
            }

            $stmt_update->bind_param("s", $collector_id);

            if ($stmt_update->execute()) {
                // Check if any rows were affected by the update
                if ($stmt_update->affected_rows > 0) {
                    // Redirect back to the main page or wherever necessary
                    echo "<script>alert('Collector successfully archived.'); window.location.href='collector.php';</script>";
                    exit();
                } else {
                    echo "No rows updated. The collector ID may not match exactly.";
                }
            } else {
                echo "Error updating collector status: " . $stmt_update->error;
            }

            $stmt_update->close();
        } else {
            echo "Error moving data to archive: " . $stmt_copy->error;
        }

        $stmt_copy->close();
    }

    $stmt_check->close();
} else {
    echo "No collector ID provided";
}

// Close connection
$conn->close();
?>