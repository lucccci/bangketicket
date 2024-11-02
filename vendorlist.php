<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';


// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$path = 'images/';
$archivePath = 'archivedqr/';

// Check if the directory exists; if not, create it
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

// Pagination setup
$rowsPerPage = 6; // Number of records per page
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1

// Fetch total number of vendors for pagination calculation
$totalRowsResult = $conn->query("SELECT COUNT(*) as total FROM vendor_list");
$totalRows = $totalRowsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);
$startIndex = ($currentPage - 1) * $rowsPerPage;
$startIndex = max(0, $startIndex); // Ensure start index is not negative


// Fetch vendors with their status (Paid or Unpaid) based on today's transactions
$sql = "SELECT vendor_list.*, 
        CASE 
            WHEN COUNT(vendor_transaction.vendorID) > 0 THEN 'Paid'
            ELSE 'Unpaid'
        END AS status
        FROM vendor_list 
        LEFT JOIN vendor_transaction 
        ON vendor_list.vendorID = vendor_transaction.vendorID
        AND DATE(vendor_transaction.date) = CURDATE() -- Only consider today's transactions
        WHERE vendor_list.archived_at IS NULL -- Exclude archived vendors
        GROUP BY vendor_list.vendorID
        LIMIT $startIndex, $rowsPerPage";

$result = $conn->query($sql);


// Check if there are any vendors
if ($result->num_rows > 0) {
    $cust = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $cust = array(); 
}

// Handle POST requests for archiving vendors
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['cust_id_to_delete'])) {
        $cust_id_to_delete = sanitizeInput($_POST['cust_id_to_delete']);

        // Fetch vendor data to move the QR image to the archive folder
        $select_sql = "SELECT * FROM vendor_list WHERE vendorID=?";
        $stmt = $conn->prepare($select_sql);
        $stmt->bind_param("s", $cust_id_to_delete);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $vendor_data = $result->fetch_assoc();
            $old_qrimage = $vendor_data['qrimage'];

            // Ensure the archive directory exists
            if (!is_dir($archivePath)) {
                mkdir($archivePath, 0777, true);
            }

            // Archive the vendor's QR image
            if ($old_qrimage && file_exists($path . $old_qrimage)) {
                $new_qrimage_path = $archivePath . $old_qrimage;
                rename($path . $old_qrimage, $new_qrimage_path); // Move the file
            }

            // Update the vendor's archived_at field in the vendor_list table
            $update_sql = "UPDATE vendor_list SET archived_at=NOW() WHERE vendorID=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("s", $cust_id_to_delete);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Insert the archived vendor data, including the QR image, into the archive_vendors table
                $insert_sql = "INSERT INTO archive_vendors (vendorID, fname, mname, lname, suffix, gender, birthday, age, contactNo, lotArea, province, municipality, barangay, houseNo, streetname, qrimage) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssssssisssssssss", 
                    $vendor_data['vendorID'], 
                    $vendor_data['fname'], 
                    $vendor_data['mname'], 
                    $vendor_data['lname'], 
                    $vendor_data['suffix'], 
                    $vendor_data['gender'], 
                    $vendor_data['birthday'], 
                    $vendor_data['age'], 
                    $vendor_data['contactNo'],
                    $vendor_data['lotArea'],                    
                    $vendor_data['province'], 
                    $vendor_data['municipality'], 
                    $vendor_data['barangay'], 
                    $vendor_data['houseNo'], 
                    $vendor_data['streetname'], 
                    $old_qrimage // Include the archived QR image
                );
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    echo "<script>alert('Vendor archived successfully.');</script>";
                    header("Location: {$_SERVER['PHP_SELF']}");
                    exit();
                } else {
                    echo "<script>alert('Error archiving vendor: " . $conn->error . "');</script>";
                }
            } else {
                echo "<script>alert('Error updating vendor to archived status: " . $conn->error . "');</script>";
            }
        } else {
            echo "<script>alert('Vendor not found.');</script>";
        }
    }


    

// Check if the edit form was submitted
if (isset($_POST['submit_edit'])) {
    $vendor_id_to_edit = sanitizeInput($_POST['vendor_id_to_edit']);
    $edit_fName = sanitizeInput($_POST['edit_fName']);
    $edit_mname = sanitizeInput($_POST['edit_mname']);
    $edit_lName = sanitizeInput($_POST['edit_lName']);
    $edit_suffix = sanitizeInput($_POST['edit_suffix']);
    $edit_gender = sanitizeInput($_POST['edit_gender']);
    $edit_birthday = sanitizeInput($_POST['edit_birthday']);
    $edit_age = sanitizeInput($_POST['edit_age']);
    $edit_contactNo = sanitizeInput($_POST['edit_contactNo']);
    $edit_lotArea = sanitizeInput($_POST['edit_lotArea']);
    $edit_houseNo = sanitizeInput($_POST['edit_houseNo']);
    $edit_streetname = sanitizeInput($_POST['edit_streetname']);
    
    // Only update address if new values are provided
    $edit_province = !empty($_POST['edit_province']) ? sanitizeInput($_POST['edit_province']) : null;
    $edit_municipality = !empty($_POST['edit_municipality']) ? sanitizeInput($_POST['edit_municipality']) : null;
    $edit_barangay = !empty($_POST['edit_barangay']) ? sanitizeInput($_POST['edit_barangay']) : null;

    // Prepare the SQL statement with conditions
    $update_sql = "UPDATE vendor_list SET 
                    fname=?, 
                    mname=?, 
                    lname=?, 
                    suffix=?, 
                    gender=?, 
                    birthday=?, 
                    age=?, 
                    contactNo=?, 
                    lotArea=?, 
                    houseNo=?, 
                    streetname=?";
    
    // Append address fields only if new values are provided
    if ($edit_province) {
        $update_sql .= ", province=?";
    }
    if ($edit_municipality) {
        $update_sql .= ", municipality=?";
    }
    if ($edit_barangay) {
        $update_sql .= ", barangay=?";
    }
    
    $update_sql .= " WHERE vendorID=?";

    $stmt = $conn->prepare($update_sql);

    // Check if the statement was prepared correctly
    if ($stmt === false) {
        echo "<script>alert('Error preparing update statement: " . $conn->error . "');</script>";
        exit();
    }

    // Bind the parameters dynamically based on provided values
    $params = [$edit_fName, $edit_mname, $edit_lName, $edit_suffix, $edit_gender, $edit_birthday, $edit_age, $edit_contactNo, $edit_lotArea, $edit_houseNo, $edit_streetname];
    
    if ($edit_province) {
        $params[] = $edit_province;
    }
    if ($edit_municipality) {
        $params[] = $edit_municipality;
    }
    if ($edit_barangay) {
        $params[] = $edit_barangay;
    }
    
    $params[] = $vendor_id_to_edit;
    
    // Use call_user_func_array to bind the parameters
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('Vendor details updated successfully.');</script>";
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    } else {
        echo "<script>alert('Error updating vendor details: " . $conn->error . "');</script>";
    }
}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>   
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="icon" href="pics/logo-bt.png">
  <link rel="stylesheet" href="menuheader.css">
  <link rel="stylesheet" href="sidemenu.css">
  <link rel="stylesheet" href="logo.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor List</title>
  <style>

body {
    height: 100%; /* Ensures full height */
    margin: 0; /* Remove default margin */
    overflow: auto; /* Prevent any scrolling */
}

            /* Sidebar */
            .side-menu {
                display: flex;
                flex-direction: column;
                width: 260px;
                height: 100vh;
                background-color: #fff;
                color: #031F4E;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1000;
                overflow-y: hidden;
                overflow-x: hidden;
                padding: 2px;
            }
    
            .side-menu .logo {
                text-align: center;
                padding: 20px;
            }
    
            .side-menu .logo img {
                max-width: 100%;
                height: auto;
            }
    
            .side-menu a {
                display: flex;
                align-items: center;
                padding: 15px 20px;
                color: #031F4E;
                text-decoration: none;
                transition: background 0.3s ease, color 0.3s ease, transform 0.2s ease-in-out;
            }
    
            .side-menu a:hover {
                background-color: #2A416F;
                color: #fff;
                transform: translateX(10px); /* Slide to the right on hover */
            }
    
            .side-menu a i {
                margin-right: 10px;
            }
    
            .side-menu a.active {
                background-color: #031F4E;
                color: #fff;
            }
    
            .side-menu a.active i {
                color: #fff;
            }
    
            .side-menu a:hover:not(.active) {
                background-color: #2A416F;
                color: #fff;
            }
    
            .side-menu .logout {
                padding: 15px 20px;
                margin-top: 215px;
                display: flex;
                align-items: center;
                transition: background 0.3s, color 0.3s;
                transition: background 0.3s, color 0.3s;
                margin-top: auto; /* Ensures logout stays at the bottom */
            }
    
            .logout:hover {
                background-color: #c0392b;
                color: #fff;
            }
/* Set a fixed height for the dropdown and enable internal scrolling */
.dropdown-content {
  display: none;
  background-color: #fefcfc;
  position: relative;
  max-height: 150px; /* Set a fixed height for the dropdown */
  overflow-y: auto; /* Enable internal scrolling if content exceeds the height */
  padding-left: 20px; /* Keep padding to make it look nice */
  padding-right: 20px;
  border-left: 3px solid #031F4E;
}

/* Pagination styles */
.pagination {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-top: 10px;
    padding-right: 10px;
    width: 100%;
}


.pagination-button {
    text-decoration: none;
    padding: 8px 12px;
    margin: 0 5px;
    border: 1px solid #031F4E;
    background-color: transparent;
    color: #031F4E;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.3s, color 0.3s;
}

.pagination-button.active {
    background-color: #031F4E;
    color: white;
    border-color: #031F4E;
}

.pagination-button:hover {
    background-color: #2A416F;
    color: white;
}

.additional-info {
    display: none; /* Hide additional details initially */
    transition: all 0.5s ease-out; /* Smooth transition effect */
}

.expand-collapse-btn {
    background: none;
    border: none;
    cursor: pointer;
    outline: none;
    color: #031F4E;
    transition: transform 0.3s ease;
}

.expand-collapse-btn i {
    font-size: 16px;
}

.vendor-row .expanded .expand-collapse-btn i {
    transform: rotate(90deg); /* Rotate icon when expanded */
}

    .usersTable {
      width: 100%;
    border-collapse: separate;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    border-spacing: 0 10px;
    font-size: 0.9em;
    margin: 25px 0;
}

.usersTable {
  width: 100%;
  border-collapse: separate; /* Change to separate for spacing to work */
  border-spacing: 0 10px; /* Adjust vertical spacing between rows */
  margin-top:2px;
}

.usersTable th {
  padding: 15px; /* Increase padding for more space inside cells */
  text-align: left;
  border-bottom: 1px solid #ddd;
}
.usersTable td
{
  padding: 10px; /* Increase padding for more space inside cells */
  text-align: left;
  border-bottom: 1px solid #ddd;
}
.usersTable thead th {
  background-color: #031F4E; /* Light background for the header */
  position: sticky;
  top: 0; /* Keeps the header fixed at the top */
}

.usersTable tbody tr {
  background-color: #fff; /* Default background color for rows */
  border-radius: 4px; /* Optional: Add rounded corners */
  overflow: hidden; /* Ensure background color is contained within rounded corners */
}

.usersTable tbody tr:hover {
  background-color: #f5f5f5; /* Light grey background on hover */
}

.additional-info {
  display: none; /* Hide additional details initially */
}

.expanded .additional-info {
  display: table-row; /* Show additional details when expanded */
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: hidden; /* Prevents the whole modal from scrolling */
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 40%;
    max-width: 400px;
    max-height: 80vh; /* Adjust based on available viewport height */
    position: relative;
    border-radius: 8px;
    overflow-y: auto; /* Enables vertical scrolling when content overflows */
    overflow-x: hidden; /* Hides horizontal scrolling */
    padding-top: 0;
}


.close-modal {
    position: absolute;
    top: 2%;
    left: 93%;
    font-size: 22px; /* Adjust size for better visibility */
    cursor: pointer;
    z-index: 1001;
    color: #333333; /* Dark grey for a subtle yet visible color */
    background-color: transparent;
    border: none;
    width: auto;
    height: auto;
    padding: 0;
    overflow: hidden;
    transition: color 0.3s ease; /* Smooth transition for hover effect */
}

.close-modal:hover {
    color: #f44336; /* Vibrant red to indicate it's an actionable button */
}



.printButton {
    margin: 0; /* Remove any margin */
    padding: 8px 12px; /* Adjust padding for better appearance */
    cursor: pointer;
    font-size: 14px;
    font-weight: 550;
    border: 1px solid #031F4E;
    color: #031F4E; /* Dark blue text for visibility */
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: transparent; /* Transparent background */
    transition: all 0.3s ease; /* Smooth transition for hover effect */
}

.printButton:hover {
    background-color: #031F4E; /* Dark blue background on hover */
    color: #ffffff; /* White text on hover for contrast */
    border-color: #031F4E; /* Maintain border color on hover */
}

/* Style for the info-title */
.info-title {
    font-size: 16px;
    font-weight: bold;
    color: #031F4E;
    margin-bottom: 10px;
}

/* Apply similar styling to the .info-row class */
.additional-info .info-row {
    margin: 5px 0; /* Adjust margin as needed */
    padding-left: 10px; /* Adjust padding to align the content with the title */
    border-left: 3px solid #031F4E; /* Adjust border width and color as needed */
    position: relative; /* Ensure the border aligns correctly */
}

/* Style for the strong tags inside .additional-info */
.additional-info strong {
    display: inline-block;
    width: 150px; /* Adjust width if needed to align text */
    text-align: left; /* Align text to the left */
    font-weight: bold;
    margin-right: 10px; /* Space between label and value */
}

/* Ensure text alignment for the .additional-info container */
.additional-info {
    text-align: left; /* Ensure text is aligned to the left */
}

.filter-container {
    display: flex;
    justify-content: space-between; /* Space between filter options and add vendor button */
    align-items: center; /* Vertically center the content */
    margin-top: 10px;
}

.filter-options {
    display: flex;
    gap: 12px; /* Space between dropdown, input, and export button */
}

#filterDropdown, #filterInput {
    padding: 11px;
    font-size: 14px;
    height: 40px;
    border: 1px solid; /* Custom border color and weight */
    border-radius: 5px; /* Optional: Rounded corners */
    box-sizing: border-box; /* Ensures padding and border are included in the element's total width and height */
}

#filterDropdown:focus, #filterInput:focus {
    outline: none; /* Removes the default focus outline */
    border-color: #2A416F; /* Changes border color when focused */
}

.export-button {
  background-color: #2A416F;
            color: #fff;
            border: 1px solid #031F4E;
            font-weight: bold;
            cursor: pointer;
            padding: 0 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px; 
           
}

.export-button:hover {
    background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
}

.add-vendor-button {
    font-weight: bold;
  background-color: #2A416F;
            color: #fff;
            border: 1px solid #031F4E;
            cursor: pointer;
            padding: 0 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px; 
            transform: translateY(-5px);
        
           
          
}

.add-vendor-button:hover {
    background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
}




.search-button {
            
    font-weight: bold;
    background-color: #2A416F;
            color: #fff;
            border: 1px solid #031F4E;
            cursor: pointer;
            padding: 0 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px; 
            
}
.search-button i{
  font-size: 15px;
}

.search-button:hover {
    background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
}


.logout {
  color: #e74c3c; /* Log Out link color */
  padding: 15px 20px; /* Padding for Log Out link */
  margin-top: 120px; /* Add space above Log Out link */
  display: flex; /* Ensure the icon and text align properly */
  align-items: center; /* Center align the icon and text vertically */
  transition: background 0.3s, color 0.3s; /* Transition effects */
}

.logout:hover {
  background-color: #c0392b; /* Hover effect for Log Out link */
  color: #fff; /* Change text color on hover */
}

  /* Header Panel */
        .header-panel {
            display: flex;
            justify-content: space-between; /* Aligns title and icon on opposite sides */
            align-items: center; /* Centers items vertically */
            padding: 10px 40px; /* Adds padding for aesthetics */
            background-color: #031F4E; /* Background color for the header */
            color: #fff; /* Text color */
            position: fixed; /* Fixes the header at the top */
            top: 0; /* Aligns the header with the top of the viewport */
            left: 260px; /* Aligns header with the main content */
            width: calc(100% - 260px); /* Full width minus the sidebar */
            height: 60px; /* Set a fixed height for the header */
            z-index: 1001; /* Stays above the sidebar */
        }

        .user-icon {
            width: 40px; /* Set a fixed width for the icon */
            height: 40px; /* Set a fixed height for the icon */
            border-radius: 50%; /* Makes the icon circular */
            margin-left: 55%;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for the hover effect */
        }

        .user-icon:hover {
            transform: scale(1.1); /* Slightly increase the size of the icon on hover */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); /* Adds a shadow effect on hover */
        }
        
        .delete-confirmation {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 50%; /* Center horizontally */
    top: 60px; /* Position at the top */
    transform: translateX(-50%); /* Adjust horizontal position to center */
    width: 100%; /* Responsive width */
    max-width: 450px; /* Max width for the modal */
    background-color: #fff; /* White background */
    padding: 15px; /* Padding around the content */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    text-align: center; /* Center the text */
}
button[type="submit"] {
    background-color: #4CAF50; /* Green background */
    color: white; /* White text */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* Remove default border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
}

button[type="submit"]:hover {
    background-color:  #031F4E; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

button[type="submit"]:active {
    transform: scale(0.95); /* Slightly shrink on click */
}

.close {
    color: #aaa;
    font-size: 24px;
    float: right;
    cursor: pointer;
    margin-top:-5%;
}

.close:hover {
    color: #000; /* Change color on hover */
}


button[type="submit"] {
    background-color: #2A416F;; /* Green background for submit */
    color: white; /* White text */
}

button[type="button"] {
    background-color: transparent; /* No background color */
    border: 2px solid #2A416F; /* Blue border */
    color: #2A416F; /* Text color matching the border */
    padding: 8px 12px; /* Padding for buttons */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, color 0.3s; /* Smooth transition */
}
button[type="button"]:hover {
    background-color:  #031F4E; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}


}

button[type="submit"]:hover {
    background-color: # #6A85BB;; /* Darker green on hover */
}

button[type="button"]:hover {
    background-color: #d32f2f; /* Darker red on hover */
    color:#ffff;
}

/* Style for the Logout Modal */
.logout-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    overflow: hidden;
    animation: fadeIn 0.3s ease-out; /* Animation for the background */
}

.logout-modal-content {
    background-color: white;
    margin: 5% auto; /* Consistent margin to position it higher */
    padding: 20px;
    border: 1px solid #888;
    width: 30%;
    max-width: 400px;
    border-radius: 8px;
    position: relative;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: all 0.3s ease;
    animation: slideDown 0.3s ease-out; /* Animation for the modal content */
}

.logout-modal h2 {
    margin-top: 0;
    font-size: 1.5rem;
    color: #031F4E; /* Match the theme color */
}

.logout-modal p {
    font-size: 1rem;
    color: #333;
    margin: 10px 0 20px;
}

.logout-modal .modal-actions button {
    padding: 10px 20px;
    margin: 0 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
}

.logout-modal .modal-actions button:first-child {
    background-color: #031F4E;
    color: #fff;
}

.logout-modal .modal-actions button:first-child:hover {
    background-color: #2A416F;
}

.logout-modal .modal-actions button:last-child {
    background-color: #ddd;
    color: #333;
}

.logout-modal .modal-actions button:last-child:hover {
    background-color: #bbb;
}

.logout-modal .close-logout-modal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 18px;
    cursor: pointer;
    color: #333;
    background-color: transparent;
    border: none;
}
.logout-modal .close-logout-modal:hover {
    color: #f44336;
}


/* Keyframe animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px); /* Start slightly above */
    }
    to {
        opacity: 1;
        transform: translateY(0); /* Slide into place */
    }
}




  </style>
</head>
<body>






  <div id="sideMenu" class="side-menu">
    <div class="logo">
        <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.php">
        <span class="material-icons" style="vertical-align: middle; font-size: 18px;">dashboard</span>
        <span style="margin-left: 8px;">Dashboard</span>
    </a>
    
    <a href="product.php">
    <span class="material-icons" style="vertical-align: middle; font-size: 18px;">payments</span>
    <span style="margin-left: 8px;">Market Fee</span>
</a>

    <div class="dropdown active">
        <a href="#" class="active"><i class="fas fa-users"></i> Vendors</a>
        <div class="dropdown-content" style="display: block;">
            <a href="vendorlist.php" class="active"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a> <!-- Highlighted -->
        </div>
    </div>
    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
    
    <!-- Log Out Link -->
    <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

<div class="header-panel">
    <div class="header-title"></div>
    <a href="admin_profile.php">
        <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
    </a>
</div>



<div class="main-content">
  <div class="panel" style="padding: 10px 20px;">
    
  <div class="vendor-header">
    <h2>Registered Vendor</h2>
</div>


<div class="filter-container">
    <div class="filter-options">
        <select id="filterDropdown">
            <option value="">Select Filter</option>
            <option value="vendorID">Vendor ID</option>
            <option value="lname">Last Name</option>
            <option value="paid">Paid</option>
            <option value="unpaid">Unpaid</option>
        </select>
        <input type="text" id="filterInput" placeholder="Enter filter value">
        <button class="search-button" onclick="filterTable()">
            <i class="fas fa-search"></i> <!-- Font Awesome Search Icon -->
        </button>
        <button class="export-button" onclick="location.href='export.php'">
    Export
    <img src="pics/icons8-export-csv-80.png" alt="Export CSV Icon" style="width: 20px; height: 20px; margin-left: 8px;">
</button>

    </div>
    <button class="add-vendor-button" onclick="location.href='vendorform.php'">
        <span class="material-icons" style="margin-left: 2px;">add</span>
    Add Vendor
    
</button>
  
</div>





<div class="registered-vendors">
    <table class="usersTable">
        <thead>
            <tr>
                <th></th> <!-- For the expand/collapse icon -->
                <th>Vendor ID</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Suffix</th>
                <th>Contact #</th>
                <th>Lot Area</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
   <?php foreach ($cust as $customer) : ?>
    <tr class="vendor-row">
        <td>
            <button class="expand-collapse-btn" onclick="toggleDetails(this)">
                <i class="fas fa-chevron-right"></i>
            </button>
        </td>
        <td><b><?php echo $customer['vendorID']; ?></b></td>
        <td><?php echo !empty($customer['fname']) ? $customer['fname'] : 'N/A'; ?></td>
        <td><?php echo !empty($customer['mname']) ? $customer['mname'] : 'N/A'; ?></td>
        <td><?php echo !empty($customer['lname']) ? $customer['lname'] : 'N/A'; ?></td>
        <td><?php echo !empty($customer['suffix']) ? $customer['suffix'] : 'N/A'; ?></td>
        <td><?php echo !empty($customer['contactNo']) ? $customer['contactNo'] : 'N/A'; ?></td>
        <td><?php echo !empty($customer['lotArea']) ? $customer['lotArea'] : 'N/A'; ?></td>
        <td><?php echo $customer['status']; ?></td>
        <td>
            <button class="action-view" onclick="openQRModal('<?php echo $customer['vendorID']; ?>')">View QR</button>
            <button class="action-edit" onclick="openEditModal('<?php echo $customer['vendorID']; ?>')">
                <i class="fa fa-edit"></i>
            </button>
            <button class="action-delete" onclick="openDeleteConfirmation('<?php echo $customer['vendorID']; ?>', '<?php echo $customer['fname']; ?>', '<?php echo $customer['lname']; ?>')">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
    <!-- Hidden additional details row -->
    <tr class="additional-info">
        <td colspan="9">
            <div class="info-title">
                <strong>Additional Details</strong>
            </div>
            <div class="info-row">
                <strong>Gender:</strong> <?php echo !empty($customer['gender']) ? $customer['gender'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Birthday:</strong> <?php echo !empty($customer['birthday']) ? $customer['birthday'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Age:</strong> <?php echo !empty($customer['age']) ? $customer['age'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Province:</strong> <?php echo !empty($customer['province']) ? $customer['province'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Municipality:</strong> <?php echo !empty($customer['municipality']) ? $customer['municipality'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Barangay:</strong> <?php echo !empty($customer['barangay']) ? $customer['barangay'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>House #:</strong> <?php echo !empty($customer['houseNo']) ? $customer['houseNo'] : 'N/A'; ?>
            </div>
            <div class="info-row">
                <strong>Street Name:</strong> <?php echo !empty($customer['streetname']) ? $customer['streetname'] : 'N/A'; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

<!-- Delete Confirmation -->
<div id="deleteConfirmation" class="delete-confirmation">
    <div class="delete-confirmation-content">
        <span class="close" onclick="closeDeleteConfirmation()">&times;</span>
     <p>Are you sure you want to move Vendor ID <strong><span id="modalVendorId"></span></strong> - <strong><span id="modalVendorName"></span></strong> to the Archive Section?</p>

        <form id="deleteForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <input type="hidden" name="cust_id_to_delete" id="cust_id_to_delete">
            <button type="submit">Confirm</button>
            <button type="button" onclick="closeDeleteConfirmation()">Cancel</button>
        </form>
    </div>
</div>

</tbody>

        <tfoot>
            <tr>
                <td colspan="10">
                <hr>
                    <div class="pagination">
                        <?php if ($totalPages > 1 || $totalRows > 0): ?>
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?>" class="pagination-button">Previous</a>
                            <?php endif; ?>

                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);

                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <button class="pagination-button <?php echo ($i === $currentPage) ? 'active' : ''; ?>" onclick="window.location.href='?page=<?php echo $i; ?>'"><?php echo $i; ?></button>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?>" class="pagination-button">Next</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

   
  </div>
</div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content" style="overflow: auto;">
        <span class="close-modal" onclick="closeEditModal()">&times;</span>
        <h2 style="text-align: center;">Vendor Basic Information</h2>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <input type="hidden" name="vendor_id_to_edit" id="edit_vendor_id" value="">

            <label for="edit_fName">First Name:</label>
            <input type="text" id="edit_fName" name="edit_fName" required><br>

            <label for="edit_mname">Middle Name:</label>
            <input type="text" id="edit_mname" name="edit_mname"><br>

            <label for="edit_lName">Last Name:</label>
            <input type="text" id="edit_lName" name="edit_lName" required><br>



<label for="edit_suffix">Suffix:</label>
<select id="edit_suffix" name="edit_suffix">
  <option value="">Select Suffix</option>
  <option value="Jr.">Jr.</option>
  <option value="Sr.">Sr.</option>
  <option value="II">II</option>
  <option value="III">III</option>
  <option value="IV">IV</option>
  <option value="V">V</option>
</select>

         <br>
            
                        
            <label for="edit_gender">Gender:</label>
            <select id="edit_gender" name="edit_gender">
              <option value="">Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>

         <br>

            <label for="edit_birthday">Birthday:</label>
            <input type="date" id="edit_birthday" name="edit_birthday" onchange="calculateAge()" required><br>

            <label for="edit_age">Age:</label>
            <input type="text" id="edit_age" name="edit_age" readonly><br>

            <label for="edit_contactNo">Contact Number:</label>
            <div style="position: relative;">
                <img src="philippineflag.webp" alt="PH" width="20" height="auto" style="position: absolute; left: 5px; top: 8px;">
                <span style="position: absolute; left: 30px; top: 8px; color: #333;font-size: 14px;">+63</span>
                <input type="text" id="edit_contactNo" name="edit_contactNo" pattern="[0-9]{10}" maxlength="10" style="padding-left: 60px; width: 200px;" placeholder="XXXXXXXXXX" required>
            </div><br>

            <label for="edit_lotArea">Lot Area:</label>
            <select id="edit_lotArea" name="edit_lotArea" required>
                <option value="">Select Area Size</option>
                <option value="1 sq. m">1 sq. m</option>
                <option value="2 sq. m">2 sq. m</option>
                <option value="3 sq. m">3 sq. m</option>
                <option value="4 sq. m">4 sq. m</option>
                <option value="5 sq. m">5 sq. m</option>
                <option value="custom">Custom...</option>
            </select><br>


            <label for="edit_houseNo">House Number:</label>
            <input type="text" id="edit_houseNo" name="edit_houseNo" required><br>

            <label for="edit_streetname">Street Name:</label>
            <input type="text" id="edit_streetname" name="edit_streetname" required><br>

     <label for="edit_province">Province:</label>
        <select id="edit_province" name="edit_province">
            <option value="">Select New Province</option>
        </select>
        <input type="hidden" id="provinceText" name="provinceText">
        
            <label for="edit_municipality">City/Municipality:</label>
    <select id="edit_municipality" name="edit_municipality">
        <option value="">Select New City/Municipality</option>
    </select>
    <input type="hidden" id="cityText" name="cityText">

        
    <label for="edit_barangay">Barangay:</label>
    <select id="edit_barangay" name="edit_barangay">
        <option value="">Select New Barangay</option>
    </select>
    <input type="hidden" id="barangayText" name="barangayText">



            <input type="submit" name="submit_edit" value="Save Changes">
        </form>
    </div>
</div>


<div id="qrModal" class="modal">
    <div class="modal-content" id="qrModalContent">
        <div class="close-modal"><i class="fas fa-times"></i></div>
        <!-- QR code content will be dynamically generated here -->
    </div>
</div>
    <!-- Logout Modal -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <span class="close-logout-modal" onclick="closeLogoutModal()">&times;</span>
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to log out?</p>
        <div class="modal-actions">
            <button onclick="confirmLogout()">Yes, Log Out</button>
            <button onclick="closeLogoutModal()">Cancel</button>
        </div>
    </div>
</div>

  <script>
  
  
// JavaScript for click animation
const menuLinks = document.querySelectorAll('.side-menu a');

menuLinks.forEach(link => {
    link.addEventListener('click', function() {
        this.style.transition = 'transform 0.1s ease-in-out';  // Apply scaling effect
        this.style.transform = 'scale(0.95)';  // Shrink on click
        
        setTimeout(() => {
            this.style.transform = 'scale(1)';  // Return to normal size after a short delay
        }, 100);  // Delay to restore to normal size (in milliseconds)
    });
});
function filterTable() {
    const filterType = document.getElementById("filterDropdown").value; // Get selected filter type
    const filterValue = document.getElementById("filterInput").value.toLowerCase(); // Get entered filter value
    const table = document.querySelector(".usersTable"); // Get the table
    const rows = table.getElementsByTagName("tr"); // Get all rows in the table

    for (let i = 1; i < rows.length; i++) { // Start from index 1 to skip the header row
        const cells = rows[i].getElementsByTagName("td");
        let isVisible = true; // Flag for row visibility

        if (filterType === "vendorID" && cells[1]) { // Check vendor ID (second column)
            isVisible = cells[1].textContent.toLowerCase().includes(filterValue);
        } else if (filterType === "lname" && cells[4]) { // Check last name (fifth column)
            isVisible = cells[4].textContent.toLowerCase().includes(filterValue);
        }

        // Show or hide the row based on the filter match
        rows[i].style.display = isVisible ? "" : "none";
    }
}

function toggleDetails(button) {
    var row = button.closest('tr');
    var nextRow = row.nextElementSibling;

    // Close all other expanded rows before expanding the current one
    document.querySelectorAll('.additional-info').forEach(function(otherRow) {
        if (otherRow !== nextRow) {
            otherRow.style.display = 'none';
            // Reset the expand/collapse button icon
            otherRow.previousElementSibling.querySelector('.expand-collapse-btn i').classList.remove('fa-chevron-down');
            otherRow.previousElementSibling.querySelector('.expand-collapse-btn i').classList.add('fa-chevron-right');
        }
    });

    // Toggle the current row
    if (nextRow && nextRow.classList.contains('additional-info')) {
        if (nextRow.style.display === 'none' || nextRow.style.display === '') {
            nextRow.style.display = 'table-row';
            button.querySelector('i').classList.remove('fa-chevron-right');
            button.querySelector('i').classList.add('fa-chevron-down');
        } else {
            nextRow.style.display = 'none';
            button.querySelector('i').classList.remove('fa-chevron-down');
            button.querySelector('i').classList.add('fa-chevron-right');
        }
    }
}


function openDeleteConfirmation(vendorId, firstName, lastName) {
    document.getElementById('modalVendorId').textContent = vendorId;
    document.getElementById('modalVendorName').textContent = firstName + ' ' + lastName;
    document.getElementById('cust_id_to_delete').value = vendorId;
    document.getElementById('deleteConfirmation').style.display = "block";
}

function closeDeleteConfirmation() {
    document.getElementById('deleteConfirmation').style.display = "none";
}

// Close confirmation when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('deleteConfirmation');
    if (event.target == modal) {
        closeDeleteConfirmation();
    }
}
// Load CSV data and parse it for provinces, municipalities, and barangays
async function loadAddressData() {
    const response = await fetch('Philippine_Address_Data.csv');
    const data = await response.text();

    // Parse CSV data into an array of objects and handle empty rows
    const rows = data.split('\n').slice(1).filter(row => row.trim() !== '');
    const addresses = rows.map(row => {
        const [level, name, code] = row.split(',').map(item => item ? item.trim() : ''); // Ensure no undefined properties
        return { level, name, code };
    });

    return addresses;
}

// Sort addresses alphabetically by name
function sortAddresses(addresses) {
    return addresses.sort((a, b) => a.name.localeCompare(b.name));
}

// Populate the Province dropdown
async function populateProvinces() {
    const addresses = await loadAddressData();
    const provinces = sortAddresses(addresses.filter(address => address.level === 'Prov'));
    const provinceDropdown = document.getElementById('edit_province');

    provinces.forEach(province => {
        let option = document.createElement('option');
        option.value = province.code;
        option.textContent = province.name;
        provinceDropdown.appendChild(option);
    });
}

// Event listener for Province selection
document.addEventListener('DOMContentLoaded', function () {
    populateProvinces();

    document.getElementById('edit_province').addEventListener('change', async function () {
        const provinceCode = this.value;
        const addresses = await loadAddressData();

        // Include both 'Mun' and 'City' levels in the municipalities dropdown
        const municipalitiesAndCities = sortAddresses(addresses.filter(
            address => (address.level === 'Mun' || address.level === 'City') && address.code.startsWith(provinceCode.slice(0, 4))
        ));

        const cityDropdown = document.getElementById('edit_municipality');
        cityDropdown.innerHTML = '<option value="">Select Municipality</option>';

        municipalitiesAndCities.forEach(cityOrMunicipality => {
            let option = document.createElement('option');
            option.value = cityOrMunicipality.code;
            option.textContent = cityOrMunicipality.name;
            cityDropdown.appendChild(option);
        });

        // Clear barangay dropdown when province changes
        document.getElementById('edit_barangay').innerHTML = '<option value="">Select Barangay</option>';
    });

    // Event listener for City/Municipality selection
    document.getElementById('edit_municipality').addEventListener('change', async function () {
        const municipalityCode = this.value;
        const addresses = await loadAddressData();

        // Filter barangays based on selected city/municipality code
        const barangays = sortAddresses(addresses.filter(
            address => address.level === 'Bgy' && address.code.startsWith(municipalityCode.slice(0, 6))
        ));

        const barangayDropdown = document.getElementById('edit_barangay');
        barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';

        barangays.forEach(barangay => {
            let option = document.createElement('option');
            option.value = barangay.code;
            option.textContent = barangay.name;
            barangayDropdown.appendChild(option);
        });
    });
});

    // for birthday auto calculate

    function calculateAge() {
    var birthday = new Date(document.getElementById('edit_birthday').value);
    var today = new Date();
    var age = today.getFullYear() - birthday.getFullYear();

    // Adjust age if birthday has not occurred this year
    if (today.getMonth() < birthday.getMonth() || (today.getMonth() == birthday.getMonth() && today.getDate() < birthday.getDate())) {
        age--;
    }

    document.getElementById('edit_age').value = age;
}


function updateCityMunicipality() {
  const provinceSelect = document.getElementById("edit_province");
  const citySelect = document.getElementById("edit_municipality");
  const barangaySelect = document.getElementById("edit_barangay");
  const selectedProvince = provinceSelect.value;

  // Clear existing options in the city dropdown
  citySelect.innerHTML = '<option value="">Select Municipality</option>';
  barangaySelect.innerHTML = '<option value="">Select Barangay</option>'; // Clear barangay dropdown

  if (selectedProvince && cityMunicipalityData[selectedProvince]) {
    cityMunicipalityData[selectedProvince].forEach(city => {
      const option = document.createElement("option");
      option.value = city;
      option.textContent = city;
      citySelect.appendChild(option);
    });
  }
}

function updateBarangay() {
  const citySelect = document.getElementById("edit_municipality");
  const barangaySelect = document.getElementById("edit_barangay");
  const selectedCity = citySelect.value;

  // Clear existing options in the barangay dropdown
  barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

  if (selectedCity && barangayData[selectedCity]) {
    barangayData[selectedCity].forEach(barangay => {
      const option = document.createElement("option");
      option.value = barangay;
      option.textContent = barangay;
      barangaySelect.appendChild(option);
    });
  }
}

  function toggleMenu() {
      var sideMenu = document.getElementById("sideMenu");
      var overlay = document.querySelector(".overlay");
      var notificationRectangle = document.getElementById("notificationRectangle");

      if (sideMenu.style.width === "250px") {
        sideMenu.style.width = "0";
        overlay.style.zIndex = 0;
        notificationRectangle.style.left = "0";
      } else {
        sideMenu.style.width = "250px";
        overlay.style.zIndex = -1;
        notificationRectangle.style.left = "250px";
      }
    }

    var modal = document.getElementById("myModal");
    var span = document.getElementsByClassName("close")[0];

    document.querySelector('.notification-rectangle .fa-bell').addEventListener('click', function() {
      modal.style.display = "block";
    });

    span.onclick = function() {
      modal.style.display = "none";
    }

    window.onclick = function(event) {
      var editModal = document.getElementById("editModal");
      if (event.target == editModal) {
          editModal.style.display = "none";
      }
    }

    //profile
    function toggleVendorDropdown(show) {
  var dropdownContent = document.querySelector(".dropdown-content");

  if (show) {
    dropdownContent.style.display = "block";
  } else {
    dropdownContent.style.display = "none";
  }
}
  function toggleDropdown() {
    var profileContainer = document.querySelector(".profile-container");
    var dropdownMenu = document.querySelector(".dropdown-menu");
    var overlay = document.querySelector(".overlay");

    profileContainer.classList.toggle("clicked");
    dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
    overlay.style.display = overlay.style.display === "block" ? "none" : "block";
  }

  // Close the modal when the close button (x) is clicked
function closeEditModal() {
    var editModal = document.getElementById("editModal");
    editModal.style.display = "none";
}

// Close the modal when clicking outside of it
window.onclick = function(event) {
    var editModal = document.getElementById("editModal");
    if (event.target == editModal) {
        closeEditModal(); // Call the closeEditModal function
    }
};

function openEditModal(vendorID) {
    var editModal = document.getElementById("editModal");
    editModal.style.display = "block";

    // Fetch vendor data by ID and populate form fields
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                var vendor = JSON.parse(xhr.responseText);

                // Populate the form fields with the vendor information
                document.getElementById('edit_vendor_id').value = vendor.vendorID;
                document.getElementById('edit_fName').value = vendor.fname;
                document.getElementById('edit_mname').value = vendor.mname;
                document.getElementById('edit_lName').value = vendor.lname;
                document.getElementById('edit_suffix').value = vendor.suffix;
                document.getElementById('edit_gender').value = vendor.gender;
                document.getElementById('edit_birthday').value = vendor.birthday;
                document.getElementById('edit_age').value = vendor.age;
                document.getElementById('edit_contactNo').value = vendor.contactNo;
                document.getElementById('edit_lotArea').value = vendor.lotArea;
                document.getElementById('edit_houseNo').value = vendor.houseNo;
                document.getElementById('edit_streetname').value = vendor.streetname;

                // Load Provinces
                loadProvinces(function() {
                    // Set existing province
                    document.getElementById('edit_province').value = vendor.province;
                    
                    // Load Municipalities for the selected province
                    loadMunicipalities(vendor.province, function() {
                        // Set existing municipality
                        document.getElementById('edit_municipality').value = vendor.municipality;

                        // Load Barangays for the selected municipality
                        loadBarangays(vendor.municipality, function() {
                            // Set existing barangay
                            document.getElementById('edit_barangay').value = vendor.barangay;
                        });
                    });
                });

            } else {
                console.error(xhr.statusText);
                alert('Error fetching vendor data');
            }
        }
    };
    xhr.open("GET", "get_vendor.php?vendorID=" + vendorID, true);
    xhr.send();
}



// Event listener for close button
var closeModalBtn = document.querySelector('.close-modal');
if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeEditModal); // Ensure this calls the closeEditModal function
}
function openQRModal(vendorID) {
    var qrModalContent = document.getElementById("qrModalContent");

    // Parse the JSON-encoded vendor data
    var vendorData = JSON.parse('<?php echo json_encode($cust); ?>');

    // Find the vendor with the matching ID
    var vendor = vendorData.find(function(item) {
        return item.vendorID == vendorID;
    });

    // Concatenate vendor data into a single string for the QR code
    var data = "First Name: " + vendor.fname + "\n" +
               "Middle Name: " + vendor.mname + "\n" +
               "Last Name: " + vendor.lname + "\n" +
               "Suffix: " + vendor.suffix + "\n" +
               "Gender: " + vendor.gender + "\n" +
               "Birthday: " + vendor.birthday + "\n" +
               "Age: " + vendor.age + "\n" +
               "Contact No: " + vendor.contactNo + "\n" +
               "Lot Area: " + vendor.lotArea + "\n" +
               "Province: " + vendor.province + "\n" +
               "Municipality: " + vendor.municipality + "\n" +
               "Barangay: " + vendor.barangay + "\n" +
               "House No: " + vendor.houseNo + "\n" +
               "Street Name: " + vendor.streetname;
        
    // Generate QR code
    var qrCodePath = 'images/' + vendor.qrimage;
    var img = document.createElement('img');
    img.src = qrCodePath;
    qrModalContent.innerHTML = ''; // Clear existing content
    qrModalContent.appendChild(img);

    // Append the Print QR Code button
    var printButton = document.createElement('button');
    printButton.className = 'printButton';
    printButton.textContent = 'Print QR Code';
    printButton.onclick = function() { printQRCode(qrCodePath, vendor.fname + ' ' + vendor.lname); };  // Updated to include vendor name
    qrModalContent.appendChild(printButton);

    // Append the Close button
    var closeButton = document.createElement('span');
    closeButton.className = 'close-modal';
    closeButton.innerHTML = '&times;';
    closeButton.onclick = function() {
        var qrModal = document.getElementById("qrModal");
        qrModal.style.display = "none";
    };
    qrModalContent.appendChild(closeButton);

    // Display the QR modal
    var qrModal = document.getElementById("qrModal");
    qrModal.style.display = "block";
}



// Function to print the QR code
function printQRCode(qrCodePath, vendorName) {
    // Get the paths for both logos from the hidden images
    var malolosLogo = document.getElementById('malolosLogo').src;
    var logoImg = document.getElementById('hiddenLogo').src;

    // Open a new print window
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print QR Code</title>
            <style>
             @media print {
            @page {
                margin: 0; /* Removes the default margin */
            }
            body {
                margin: 0;
            }
            /* Hide any print controls or buttons */
            .no-print {
                display: none;
            }
        }
                body {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                    padding: 15px;
                    font-family: Arial, sans-serif;
                    text-align: center;
                }
                .header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    width: 100%;
                    max-width: 270px;
                }
                .logo-left, .logo-right {
                    flex: 0 0 auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0;
                }
                .text-header {
                    font-size: 9px;
                    font-weight: bold;
                    text-align: center;
                }
                .underline {
                    border-top: 1px solid black;
                    width: 100%;
                    margin-top: 2px;
                }
                .qr-code-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }
                .qr-code {
                    max-width: 300px;
                    height: 300px;
                }
                .vendor-name {
                    font-size: px;
                    text-align: center;
                    max-width: 300px;
                }
                span {
                    font-weight: bold;  
                }
            </style>
        </head>
        <body>
            <div class="header">
             <div class="logo-left">
                    <img src="` + malolosLogo + `" alt="Malolos Logo" style="max-width: 50px; height: auto;">
                </div>
                <div class="text-header">
                    Republika ng Pilipinas<br>
                    <div class="underline"></div>
                    Pamahalaang Lungsod ng Malolos
                </div>
               <div class="logo-right">
                    <img src="` + logoImg + `" alt="Bangketicket Logo" style="max-width: 50px; height: auto;">
                </div>
            </div>
            <div class="qr-code-container">
                <img src="` + qrCodePath + `" alt="QR Code" class="qr-code">
            </div>
            <div class="vendor-name">Vendor:<span> ` + vendorName + `</span></div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for the content to load before printing
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close(); // Optional: close the print window after printing
    };
}

// Close QR modal when clicking outside of it
window.onclick = function(event) {
  var qrModal = document.getElementById("qrModal");
  if (event.target == qrModal) {
    qrModal.style.display = "none";
  }
};

        // Function to open the logout modal
        function openLogoutModal() {
            var logoutModal = document.getElementById("logoutModal");
            logoutModal.style.display = "block";
        }

        // Function to close the logout modal
        function closeLogoutModal() {
            var logoutModal = document.getElementById("logoutModal");
            logoutModal.style.display = "none";
        }

        // Function to confirm the logout
        function confirmLogout() {
            window.location.href = 'index.html'; // Redirect to your logout page
        }

        // Ensure the logout modal closes when clicking outside of it
        window.onclick = function(event) {
            var logoutModal = document.getElementById("logoutModal");
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        };
</script>
</body>
</html>