<?php
require_once 'config.php';

if (isset($_GET['vendorID'])) {
    $vendorID = $_GET['vendorID'];
    $sql = "SELECT * FROM vendor_list WHERE vendorID = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $vendorID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $vendor = $result->fetch_assoc();
            echo json_encode($vendor);
        } else {
            echo json_encode(["error" => "Vendor not found"]);
        }
    } else {
        echo json_encode(["error" => "Failed to prepare SQL statement"]);
    }
} else {
    echo json_encode(["error" => "vendorID not set"]);
}
