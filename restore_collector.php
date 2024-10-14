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

// Get the collector ID from the URL
$collector_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($collector_id) {
    // Step 1: Check if the collector already exists in the collectors table
    $sql_check_existing = "SELECT collector_id FROM collectors WHERE collector_id = ?";
    $stmt_check_existing = $conn->prepare($sql_check_existing);
    
    if ($stmt_check_existing === false) {
        die("Error preparing the check statement: " . $conn->error);
    }

    $stmt_check_existing->bind_param("s", $collector_id);
    $stmt_check_existing->execute();
    $stmt_check_existing->store_result();

    if ($stmt_check_existing->num_rows > 0) {
        // Collector already exists in the collectors table
        echo "Collector with ID $collector_id already exists in the collectors table.";
    } else {
        // Step 2: Get the collector's data from the archive_collectors table
        $sql_get_archived = "SELECT * FROM archive_collectors WHERE collector_id = ?";
        $stmt_get_archived = $conn->prepare($sql_get_archived);
    
        // Check if the statement was prepared successfully
        if ($stmt_get_archived === false) {
            die("Error preparing the statement: " . $conn->error);
        }

        $stmt_get_archived->bind_param("s", $collector_id);
        $stmt_get_archived->execute();
        $result = $stmt_get_archived->get_result();

        if ($result->num_rows > 0) {
            // Collector exists in the archive, fetch the data
            $collector_data = $result->fetch_assoc();

            // Step 3: Insert the collector data back into the collectors table
            $sql_restore = "INSERT INTO collectors (collector_id, fname, mname, lname, suffix, birthday) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_restore = $conn->prepare($sql_restore);

            // Check if the statement was prepared successfully
            if ($stmt_restore === false) {
                die("Error preparing the restore statement: " . $conn->error);
            }

            $stmt_restore->bind_param(
                "ssssss",
                $collector_data['collector_id'],
                $collector_data['fname'],      // Correct column name for first name
                $collector_data['mname'],      // Correct column name for middle name
                $collector_data['lname'],      // Correct column name for last name
                $collector_data['suffix'],
                $collector_data['birthday']
            );

            if ($stmt_restore->execute()) {
                // Step 4: Remove the collector from the archive_collectors table
                $sql_delete_archived = "DELETE FROM archive_collectors WHERE collector_id = ?";
                $stmt_delete_archived = $conn->prepare($sql_delete_archived);

                // Check if the statement was prepared successfully
                if ($stmt_delete_archived === false) {
                    die("Error preparing the delete statement: " . $conn->error);
                }

                $stmt_delete_archived->bind_param("s", $collector_id);
                $stmt_delete_archived->execute();

                // Step 5: Redirect back to the collectors table or show a success message
                header("Location: collector.php?message=Collector restored successfully");
                exit();
            } else {
                echo "Error restoring collector: " . $stmt_restore->error;
            }
        } else {
            echo "No collector found with this ID in the archive.";
        }

        // Close the statements
        $stmt_get_archived->close();
        $stmt_restore->close();
        $stmt_delete_archived->close();
    }

    // Close the check statement
    $stmt_check_existing->close();
} else {
    echo "Invalid collector ID.";
}

// Close the connection
$conn->close();
?>
