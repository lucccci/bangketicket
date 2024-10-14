<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

// Retrieve vendorID from GET request
if(isset($_GET['vendorID'])) {
    $vendorID = $_GET['vendorID'];

    // Fetch vendor data from the database
    $sql = "SELECT * FROM vendorlists WHERE vendorID = $vendorID"; // Replace 'vendorlists' with the actual table name
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $vendor = $result->fetch_assoc();

        // Concatenate vendor data into a single string for the QR code
        $data = "First Name: ".$vendor['fName']."\n".
                 "Last Name: ".$vendor['lName']."\n".
                 "Age: ".$vendor['age']."\n".
                 "Birthday: ".$vendor['birthday']."\n".
                 "Address: ".$vendor['address']."\n".
                 "Contact No: ".$vendor['contactNo'];

        // Generate QR code
        $qrCodePath = 'images/'.$vendorID.'_qr.png';
        QRcode::png($data, $qrCodePath, 'H', 4, 4);

        // Output QR code image
        echo '<span class="close" onclick="closeQRModal()">&times;</span>';
        echo '<img src="'.$qrCodePath.'" alt="QR Code">';
        echo '<button class="printButton" onclick="printQRCode()">Print QR Code</button>';
    } else {
        echo "Vendor not found";
    }
} else {
    echo "VendorID not provided";
}
?>
