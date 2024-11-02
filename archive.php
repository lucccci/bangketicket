<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

$archivePath = 'archivedqr/'; // Path for archived QR codes
if (!is_dir($archivePath)) {
    mkdir($archivePath, 0777, true); // Create directory if it doesn't exist
}

// Fetch archived vendor data from the database
$sql = "SELECT * FROM archive_vendors";
$result = $conn->query($sql);

// Check if there are any archived vendors
if ($result->num_rows > 0) {
    $archive_vendors = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $archive_vendors = array(); // Empty array if no archived vendors found
}
// Pagination setup
$rowsPerPage = 6; // Define the number of records per page
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1

// Fetch total number of collectors for pagination calculation
$totalRowsResult = $conn->query("SELECT COUNT(*) as total FROM archive_vendors");
if ($totalRowsResult) {
    $totalRows = $totalRowsResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $rowsPerPage); // Calculate total pages
} else {
    $totalRows = 0; // Fallback in case of failure
    $totalPages = 1; // Default to at least one page
}

// Calculate the starting index for the current page
$startIndex = ($currentPage - 1) * $rowsPerPage;
$startIndex = max(0, $startIndex); // Ensure the start index is not negative

// Fetch the collectors for the current page with LIMIT
$archivedVendorsResult = $conn->query("SELECT * FROM archive_vendors ORDER BY vendorID ASC LIMIT $startIndex, $rowsPerPage");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="menuheader.css"> 
   <link rel="stylesheet" href="logo.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Vendors</title>
    <style>
        


        body {
            background-color: #F2F7FC;
            font-family: 'poppins', sans-serif;
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
        
.logout {
            color: #e74c3c; /* Log Out link color */
            padding: 15px 20px; /* Padding for Log Out link */
            margin-top: 215px; /* Add space above Log Out link */
            display: flex; /* Ensure the icon and text align properly */
            align-items: center; /* Center align the icon and text vertically */
            transition: background 0.3s, color 0.3s; /* Transition effects */
        }

        .logout:hover {
    background-color: #c0392b;
    color: #fff;
    transform: translateX(10px); /* Slide effect on hover for logout */
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

       /* Add white background to the panel */
/* White background for the entire panel */
.panel {
    margin-top: 80px;
    background-color: #ffffff; /* White background */
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Optional: Shadow for depth */
    border-radius: 8px; /* Optional: Rounded corners */
    border: 1px solid #ddd; /* Optional: Light border */
    width: 100%;
    overflow-x: hidden; /* Ensure no horizontal scroll */
}
.panel h2 {
    font-size: 24px; /* Adjust to fit the design */
    padding: 0; /* Remove padding */
    white-space: nowrap; /* Prevent the text from wrapping */
    overflow: visible; /* Ensure the text does not get cut off */
    width: auto; /* Allow the heading to take as much width as needed */
    flex-grow: 1; /* Make the heading take available space */
}


/* Ensure the main content has the correct spacing */
.main-content {
    background-color: #F2F7FC; /* Light overall background */
    padding: 20px;
    margin-left: 260px; /* Adjusted for sidebar width */
    min-height: 100%; /* Ensure full height */
    box-sizing: border-box;
    overflow-x: hidden; /* Prevent horizontal scrolling */
    overflow-y: hidden;
}

/* Style for the heading section */
.heading-with-button h2 {
    font-size: 24px;  /* Adjust to the desired size */
    margin: 0;        /* Remove any extra margin */
    padding-bottom: 10px; /* Optional: Adjust space below the title */
    text-align: left;  /* Align the title to the left (change to center if needed) */
}

/* Filter container (dropdowns and filter inputs) */
.filter-container {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    background-color: #ffffff; /* White background for filters */
    padding: 10px 0; /* Add space around the filters */
}

/* Style the table */
.registered-vendors {
    margin-top: 20px;  /* Reduce the space between the title and table */
    background-color: #ffffff; /* White background for the table */
    padding: 10px;     /* Adjust padding around the table content */
    border-radius: 8px; /* Optional: Rounded corners */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); /* Optional: Light shadow */
}

.usersTable {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Optional: Shadow for table */
    margin-top: 10px;  /* Reduce space between table and title */
    
}

.usersTable th, .usersTable td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    background-color: #ffffff; /* White background for table rows */
}

.usersTable th {
    background-color: #031F4E; /* Header color */
    color: #ffffff; /* White text */
}

.usersTable tbody tr:hover {
    background-color: #f9f9f9; /* Light background on hover */
}


       

        .filter-container {
            margin-bottom: 20px;
        }

        .filter-container select, .filter-container input {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 200px;
        }

        /* Unique styles for the custom dropdown */
/* Flexbox to align the title and button horizontally */
.heading-with-button {
    display: flex;
    align-items: center;
    justify-content: space-between; /* Ensure spacing between heading and dropdown */
    width: 100%; /* Full width of the container */
    padding: 10px 20px; /* Add padding for spacing */
    box-sizing: border-box; /* Include padding within the width */
}

@media screen and (max-width: 768px) {
    .heading-with-button h2 {
        font-size: 20px; /* Slightly smaller font size for small screens */
        white-space: normal; /* Allow text to wrap */
    }
}


/* Unique styles for the custom dropdown */
.custom-dropdown-btn {
    background-color: transparent; /* Initial background color */
    border: none; /* Remove border */
    cursor: pointer; /* Change cursor to pointer on hover */
    transition: background-color 0.3s ease, transform 0.3s ease; /* Smooth transition for hover effects */
}

.custom-dropdown-btn:hover {
    background-color: rgba(3, 31, 78, 0.1); /* Light background color on hover */
    transform: scale(1.05); /* Slightly enlarge the button */
}

.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-btn {
    margin-right:910px;
    color: black;
    padding: 10px;
    font-size: 16px;

    cursor: pointer;
    border-radius: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 30px;
    height: 30px;
}

.custom-dropdown-btn i {
    font-size: 16px;
}


.custom-dropdown-container {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    border-radius: 5px;
    top: 100%;
    left: 0;
}

.custom-dropdown-container a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.custom-dropdown-container a:hover {
    background-color: #f1f1f1;
}

.custom-dropdown.show .custom-dropdown-container {
    display: block;
}
.filter-container {
    display: flex;
    justify-content: space-between; /* Space between filter options and add vendor button */
    align-items: center; /* Vertically center the content */
    padding: 15px 0;
    margin-top: 10px;
}

.filter-options {
    display: flex;
    gap: 12px; /* Space between dropdown, input, and export button */
}

#filterDropdown, #filterInput {
    padding: 11px;
    font-size: 14px;
    border: 1px solid; /* Custom border color and weight */
    border-radius: 5px; /* Optional: Rounded corners */
    box-sizing: border-box; /* Ensures padding and border are included in the element's total width and height */
}

#filterDropdown:focus, #filterInput:focus {
    outline: none; /* Removes the default focus outline */
    border-color: #2A416F; /* Changes border color when focused */
}

.export-button {
  background-color: transparent;
            color: #031F4E;
            border: 1px solid #031F4E;
            cursor: pointer;
            padding: 0 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px; 
           
}

.export-button:hover {
  background-color: #2A416F;
  color: #fff;
}

.add-vendor-button {

  background-color: transparent;
            color: #031F4E;
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

.logout {
            color: #e74c3c; /* Log Out link color */
            padding: 15px 20px; /* Padding for Log Out link */
            margin-top: 214px; /* Add space above Log Out link */
            display: flex; /* Ensure the icon and text align properly */
            align-items: center; /* Center align the icon and text vertically */
            transition: background 0.3s, color 0.3s; /* Transition effects */
        }

        .logout:hover {
            background-color: #c0392b; /* Hover effect for Log Out link */
            color: #fff; /* Change text color on hover */
        }


        .action-view {
        background: none;
        border: 1px solid #031F4E; /* Border color */
        cursor: pointer;
        font-size: 12px;
        color: #FFFFFF; /* Text color */
        background-color: #023A6B;
        padding: 8px 16px; /* Adjust padding as needed */
        border-radius: 5px; /* Rounded corners */
        transition: background-color 0.3s, color 0.3s, border-color 0.3s; /* Smooth transition */
      }

      .action-view:hover {
        background-color: #7faad9; /* Background color on hover */
       
      }
/* Modal Styling */
#qrModalContent {
            background-color: #fefefe;
           margin-top: 150px;
            padding: 5px;
            border: 1px solid #888;
            max-width: 300px;
            text-align: center;
            position: relative;
            border-radius: 5px;
        }
#qrModal {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.4); 
}

/* Modal Content */
.modal-content {
    background-color: #fefefe;
    margin: 15% auto; 
    padding: 20px;
    border: 1px solid #888;
    width: 100%;
    max-width: 300px;
    text-align: center;
    border-radius: 5px;
}

.close-qrcode {
    position: absolute;
    top: 2%;
    right: 3.5%;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-qrcode:hover,
.close-qrcode:focus {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}
.action-restore {
    background: none;
    border: 1px solid #031F4E;
    cursor: pointer;
    font-size: 12px;
    color: #ffff; /* White text */
    background-color: #023A6B;
    padding: 8px 16px;
    border-radius: 5px;
    transition: background-color 0.3s, color 0.3s, border-color 0.3s;
}

.action-restore:hover {
    background-color: #7faad9; /* Background color on hover */
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
/* Style for the info-title */
/* Style for the info-title */
.info-title {
    font-size: 16px;
    font-weight: bold;
    color: #031F4E;
    margin-bottom: 10px;
}

/* Apply styling for the rows with additional information */
/* Additional info section styling */
.additional-info .info-row {
    padding-left: 10px;
    border-left: 3px solid #031F4E; /* Blue left border */
    margin-bottom: 10px;
    padding-bottom: 5px;
}

/* Bold the labels inside .additional-info */
.additional-info strong {
    font-weight: bold; /* Make the label bold */
  
    width: 150px; /* Controls the width of the label section to create space between label and value */
    text-align: left; /* Aligns label text to the left */

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
.header-panel {
    display: flex; /* Use flexbox for easy alignment */
    justify-content: flex-end; /* Align items to the right */
    align-items: center; /* Center vertically */
    padding: 0px; /* Add some padding */
    background-color: #031F4E;
}

.profile-icon {
    width: 40px; /* Set the width of the icon */
    height: 40px; /* Set the height of the icon */
    cursor: pointer; /* Change cursor to pointer on hover */
    margin-right: 10px; /* Space between the icon and the edge */
}

.profile-icon:hover {
    opacity: 0.8; /* Change opacity on hover for a slight effect */
}
        .user-icon {
    width: 40px; /* Set a fixed width for the icon */
    height: 40px; /* Set a fixed height for the icon */
    border-radius: 50%; /* Makes the icon circular */
    margin-left: -45%; /* Aligns the icon in the header */
    transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for the hover effect */
}

.user-icon:hover {
    transform: scale(1.1); /* Slightly increase the size of the icon on hover */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); /* Adds a shadow effect on hover */
}

/* Add this CSS block for responsive styling */
@media screen and (max-width: 768px) {
    /* For tablets and mobile screens */
    .heading-with-button {
        flex-direction: column; /* Stack the elements vertically */
        align-items: flex-start; /* Align items to the start */
    }

    .custom-dropdown {
        margin-top: 10px; /* Add some margin above the dropdown */
    }
}

@media screen and (min-width: 769px) {
    /* For larger screens */
    .heading-with-button {
        flex-direction: row; /* Keep the elements in a row */
        justify-content: space-between; /* Space between the heading and dropdown */
        align-items: center;
    }

    .custom-dropdown {
        margin-left: auto; /* Push the dropdown to the right */
    }
}

@media screen and (max-width: 768px) {
    .heading-with-button {
        flex-direction: column; /* Stack elements vertically on smaller screens */
        align-items: flex-start; /* Align items to the start */
    }

    .panel h2 {
        font-size: 20px; /* Slightly reduce font size for smaller screens */
        white-space: normal; /* Allow wrapping if necessary */
    }
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

.delete-confirmation {
     display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 38%;
    background-color: rgba(0, 0, 0, 0);
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

.delete-confirmation-content {
     position: relative;
    background-color: #fff;
    width: 90%;
    max-width: 400px;
    margin: 9vh auto;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transform: scale(0.7);
    opacity: 0;
    animation: scaleIn 0.3s ease-in-out forwards;
}

.close {
    cursor: pointer; /* Pointer cursor on hover */
    font-size: 28px; /* Font size for close icon */
    position: absolute; /* Position close button */
    margin-left:88%; /* Align to the right */
    margin-top: -4% /* Align to the top */
}

button {
    margin: 3px; /* Space between buttons */
    padding: 10px 15px; /* Padding for buttons */
    border: none; /* Remove default border */
    border-radius: 5px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, color 0.3s; /* Smooth transition */
    
}

#confirmRestoreButton {
    margin-left:30%;
    background-color: #2A416F;; 
    color: white; /* White text */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* Remove default border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
    
}

#confirmRestoreButton:hover {
  background-color:  #031F4E; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

button[type="button"] {
    background-color: transparent; /* No background color */
    border: 2px solid #2A416F; /* Blue border */
    color: #2A416F; /* Text color matching the border */
    padding: 8px 12px; /* Padding for buttons */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, color 0.3s; /* Smooth transition */
    justify-content: center; /* Center items horizontally */
}
button[type="button"]:hover {
     background-color: #d32f2f; /* Darker red on hover */
    color:#ffff;
}

.success-message {
    color: green; /* Text color for success messages */
    font-size: 16px; /* Font size */
    margin-top: 15px; /* Space above the message */
    font-weight: bold; /* Make the text bold */
    text-align: center; /* Center the text */
}


.error-message {
    color: red; /* Text color for error messages */
    font-size: 16px; /* Font size */
    margin-top: 15px; /* Space above the message */
    font-weight: bold; /* Make the text bold */
    text-align: center; /* Center the text */
}

.custom-modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.5); /* Black w/ opacity */
}

.custom-modal-content {
    background-color: #fefefe;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
    max-width: 500px; /* Maximum width for the modal */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Box shadow for depth */
}

.custom-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.custom-close:hover,
.custom-close:focus {
    color: black;
    text-decoration: none;
}

.custom-modal-actions {
    display: flex;
    
    justify-content: space-between;
    margin-top: 20px;
}

.custom-modal-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    
}




.message-div {
    position: fixed;
    top: 10px;
    right: 10px;
    padding: 10px 20px;
    background-color: #4CAF50; /* Green background */
    color: white; /* White text */
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    z-index: 1001; /* Above the modal */
    display: none; /* Hidden by default */
}

.success-message {
    background-color: #4CAF50; /* Green for success */
}

.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

.custom-dropdown-btn:hover {
    transform: scale(1.1);
}

.custom-dropdown-container {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    min-width: 120px;  /* Reduced from 180px to 120px */
    width: fit-content; /* This ensures the dropdown only takes the space it needs */
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border-radius: 4px;
    z-index: 1000;
}

.custom-dropdown-container a {
    color: #333;
    padding: 8px 12px;  /* Reduced padding */
    text-decoration: none;
    display: block;
    transition: background-color 0.3s ease;
    font-size: 14px;    /* Added smaller font size */
    white-space: nowrap; /* Prevents text from wrapping */
}

.custom-dropdown-container a:hover {
    background-color: #f5f5f5;
}

.custom-dropdown-container.show {
    display: block;
}


/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes scaleIn {
    from {
        transform: scale(0.7);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
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


  <div class="header-panel">
    <div class="header-title"></div>
    <a href="admin_profile.php">
        <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
    </a>
</div>

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


    <div class="dropdown">
        <a href="vendorlist.php" id="vendorDropdown" class="dropdown-toggle"><i class="fas fa-users"></i> Vendors</a>
        <div id="vendorDropdownContent" class="dropdown-content" style="display: none;">
            <a href="vendorlist.php" id="vendorListLink"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>

    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"class="active"><i class="fas fa-archive"></i> Archive</a>

    <!-- Log Out Link -->
    <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

<div class="main-content">
    <div class="panel">
        <div class="heading-with-button">
            <h2>Archive Records</h2>
            <!-- Custom Dropdown -->
            <div class="custom-dropdown">
                <button type="button" class="custom-dropdown-btn" onclick="toggleDropdown(event)">
                    <img src="pics/icons8-dropdown-48.png" alt="Dropdown Icon" style="width: 20px; height: 20px; pointer-events: none;">
                </button>
                <div class="custom-dropdown-container">
                    <a href="archive-collector.php">Archive Collectors</a>
                </div>
                
            </div>
            
        </div>
         <!-- Filter and Export Buttons -->
            <div class="filter-container">
                <div class="filter-options">
                    <select id="filterDropdown">
                        <option value="">Select Filter</option>
                        <option value="vendorID">Vendor ID</option>
                        <option value="lname">Last Name</option>
                    </select>
                    <input type="text" id="filterInput" placeholder="Enter filter value">
                    <button class="search-button" onclick="filterTable()">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="export-button" onclick="exportToCSV()">
                        Export
                        <img src="pics/icons8-export-csv-80.png" alt="Export CSV Icon" style="width: 20px; height: 20px; margin-left: 8px;">
                    </button>
                </div>
            </div>
  



        <!-- Start of collector table -->
        <div class="registered-vendors">
        <table class="usersTable">
    <thead>
        <tr>
            <th></th> <!-- Chevron column -->
            <th>Vendor ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Suffix</th> <!-- Add Suffix column -->
            <th>Contact #</th>
            <th>Lot Area</th>
            <th></th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($archive_vendors)) : ?>
            <tr>
                <td colspan="9" style="text-align: left;">No records found</td>
            </tr>
        <?php else : ?>
            <?php foreach ($archive_vendors as $vendor) : ?>
                <tr class="vendor-row" data-vendor-id="<?php echo htmlspecialchars($vendor['vendorID']); ?>">
                    <td>
                        <button class="expand-collapse-btn" onclick="toggleDetails(this)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </td>
                  <td><b><?php echo htmlspecialchars($vendor['vendorID']); ?></b></td>
                        <td><?php echo htmlspecialchars($vendor['fname']); ?></td>
                        <td><?php echo htmlspecialchars($vendor['mname']); ?></td>
                        <td><?php echo htmlspecialchars($vendor['lname']); ?></td>
                        <td><?php echo !empty($vendor['suffix']) ? htmlspecialchars($vendor['suffix']) : 'N/A'; ?></td> <!-- Display Suffix or N/A -->
                        <td><?php echo htmlspecialchars($vendor['contactNo']); ?></td>
                        <td><?php echo htmlspecialchars($vendor['lotArea']); ?></td>

                    <td><td>
                        <button class="action-view" onclick="openQRModal('<?php echo htmlspecialchars($vendor['vendorID']); ?>')">View QR</button>
                      
                          <button class="action-restore" onclick="openRestoreConfirmation('<?php echo htmlspecialchars($vendor['vendorID']); ?>', '<?php echo htmlspecialchars($vendor['vendorName']); ?>')">
    <i class="fas fa-undo"></i> <!-- Replace with the appropriate icon -->
</button>

                        </button>
                    </td>
                </tr>
              
                <tr class="additional-info" id="details-<?php echo htmlspecialchars($vendor['vendorID']); ?>" style="display:none;">
    <td colspan="9">
        <div class="info-title">
            <strong>Additional Details</strong>
        </div>
        <!-- Each row of details wrapped in a div with the class 'info-row' -->
        <div class="info-row"><strong>Gender:</strong> <?php echo htmlspecialchars($vendor['gender']); ?></div>
        <div class="info-row"><strong>Birthday:</strong> <?php echo htmlspecialchars($vendor['birthday']); ?></div>
        <div class="info-row"><strong>Age:</strong> <?php echo htmlspecialchars($vendor['age']); ?></div>
        <div class="info-row"><strong>Province:</strong> <?php echo htmlspecialchars($vendor['province']); ?></div>
        <div class="info-row"><strong>Municipality:</strong> <?php echo htmlspecialchars($vendor['municipality']); ?></div>
        <div class="info-row"><strong>Barangay:</strong> <?php echo htmlspecialchars($vendor['barangay']); ?></div>
        <div class="info-row"><strong>House #:</strong> <?php echo htmlspecialchars($vendor['houseNo']); ?></div>
        <div class="info-row"><strong>Street Name:</strong> <?php echo htmlspecialchars($vendor['streetname']); ?></div>
    </td>
</tr>

<!-- Global Success Message -->
<div id="messageDiv" class="message-div" style="display: none;"></div>

<!-- Restore Vendor Confirmation Modal -->
<div id="restoreConfirmation" class="delete-confirmation" style="display:none;">
    <div class="delete-confirmation-content">
        <span class="close" onclick="closeRestoreConfirmation()">&times;</span>
        <p>Are you sure you want to restore Vendor ID <strong><span id="restoreVendorId"></span></strong> - <strong><span id="restoreVendorName"></span></strong>?</p>
        <button id="confirmRestoreButton" onclick="confirmRestore()">Confirm</button>
        <button type="button" onclick="closeRestoreConfirmation()">Cancel</button>
        <div id="restoreMessage" style="margin-top: 10px;"></div>
    </div>
</div>




            <?php endforeach; ?>
        <?php endif; ?>
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



<!-- QR Modal -->
<div id="qrModal" class="modal">
    <div class="modal-content" id="qrModalContent">
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
function toggleDropdown(event) {
    event.stopPropagation();
    const dropdownContainer = event.currentTarget.nextElementSibling;
    
    // Close all other open dropdowns
    document.querySelectorAll('.custom-dropdown-container').forEach(container => {
        if (container !== dropdownContainer) {
            container.classList.remove('show');
        }
    });

    // Toggle current dropdown
    dropdownContainer.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.custom-dropdown')) {
        document.querySelectorAll('.custom-dropdown-container').forEach(container => {
            container.classList.remove('show');
        });
    }
});

// Prevent dropdown from closing when clicking inside it
document.querySelectorAll('.custom-dropdown-container').forEach(container => {
    container.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});

    //search icon for filtr
    function filterTable() {
    const filterType = document.getElementById("filterDropdown").value; // Get selected filter type
    const filterValue = document.getElementById("filterInput").value.toLowerCase(); // Get entered filter value
    const table = document.querySelector(".usersTable"); // Get the table
    const rows = table.getElementsByTagName("tr"); // Get all rows in the table

    for (let i = 1; i < rows.length; i++) { // Start from index 1 to skip the header row
        const cells = rows[i].getElementsByTagName("td");

        // Skip hidden detail rows (those that don't have the expected number of columns)
        if (cells.length < 6) {
            continue;
        }

        let isVisible = true; // Flag for row visibility

        if (filterType === "vendorID" && cells[0]) { // Check vendor ID (first column)
            isVisible = cells[0].textContent.toLowerCase().includes(filterValue);
        } else if (filterType === "lname" && cells[3]) { // Check last name (fourth column)
            isVisible = cells[3].textContent.toLowerCase().includes(filterValue);
        } 

        // Show or hide the row based on the filter match
        rows[i].style.display = isVisible ? "" : "none";
    }
}

// Call this function when the user clicks the restore button
function initiateRestore(vendorID, vendorName) {
    openRestoreConfirmation(vendorID, vendorName); // Open modal with vendor info
}

function openRestoreConfirmation(vendorID, vendorName) {
    document.getElementById('restoreVendorId').textContent = vendorID;
    document.getElementById('restoreVendorName').textContent = vendorName;
    document.getElementById('restoreMessage').textContent = ''; // Clear previous messages
    document.getElementById('restoreConfirmation').style.display = "flex"; // Show the modal
}

function closeRestoreConfirmation() {
    document.getElementById('restoreConfirmation').style.display = "none"; // Hide the modal
}

document.getElementById('confirmRestoreButton').onclick = function() {
    const vendorID = document.getElementById('restoreVendorId').textContent;
    restoreVendor(vendorID);
};

function showRestoreConfirmation(vendorID, vendorName) {
    const modal = document.getElementById('restoreConfirmation');
    const vendorIdSpan = document.getElementById('restoreVendorId');
    const vendorNameSpan = document.getElementById('restoreVendorName');

    vendorIdSpan.textContent = vendorID;
    vendorNameSpan.textContent = vendorName;

    modal.style.display = 'block'; // Show the modal
    modal.classList.remove('fade-out');
    modal.classList.add('fade-in'); // Add fade-in animation
}

function closeRestoreConfirmation() {
    const modal = document.getElementById('restoreConfirmation');
    modal.classList.remove('fade-in');
    modal.classList.add('fade-out'); // Add fade-out animation

    // Wait for the animation to complete before hiding the modal
    setTimeout(() => {
        modal.style.display = 'none';
    }, 500); // Match the duration of the fade-out animation
}



function restoreVendor(vendorID) {
    $.ajax({
        url: 'restore_vendor.php',
        type: 'POST',
        data: { vendorID: vendorID },
        success: function (response) {
            const messageDiv = document.getElementById('restoreMessage');
            if (response.trim() === 'success') {
                // Handle successful restore
                const vendorRow = document.querySelector(`tr.vendor-row[data-vendor-id='${vendorID}']`);
                const detailsRow = document.getElementById('details-' + vendorID);

                if (vendorRow) {
                    vendorRow.remove(); // Remove the main vendor row from the table
                }

                if (detailsRow) {
                    detailsRow.remove(); // Remove the additional details row from the table
                }

                const remainingRows = document.querySelectorAll('.usersTable tbody tr.vendor-row');
                if (remainingRows.length === 0) {
                    const tbody = document.querySelector('.usersTable tbody');
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="9" style="text-align: left;">No records found</td>
                        </tr>
                    `;
                }

                // Apply the success message style
                messageDiv.textContent = 'Vendor restored successfully.';
                messageDiv.style.display = 'block'; // Show the message
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.padding = '15px';
                messageDiv.style.margin = '20px 0';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.style.borderRadius = '4px';
                messageDiv.style.textAlign = 'center';
                messageDiv.style.fontWeight = 'bold';

                setTimeout(() => {
                    messageDiv.style.display = 'none'; // Hide the message
                    closeRestoreConfirmation(); // Close the modal
                }, 2000);

            } else {
                // Handle failure
                messageDiv.textContent = 'Error: ' + response;
                messageDiv.style.backgroundColor = '#f8d7da'; // Light red background for error
                messageDiv.style.color = '#721c24'; // Dark red text for error
                messageDiv.style.display = 'block';
                messageDiv.style.padding = '15px';
                messageDiv.style.margin = '20px 0';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.style.borderRadius = '4px';
                messageDiv.style.textAlign = 'center';
                messageDiv.style.fontWeight = 'bold';
            }
        },
        error: function (xhr, status, error) {
            const messageDiv = document.getElementById('restoreMessage');
            messageDiv.textContent = 'An error occurred while restoring the vendor.';
            messageDiv.style.backgroundColor = '#f8d7da'; // Light red background for error
            messageDiv.style.color = '#721c24'; // Dark red text for error
            messageDiv.style.display = 'block';
            messageDiv.style.padding = '15px';
            messageDiv.style.margin = '20px 0';
            messageDiv.style.border = '1px solid #f5c6cb';
            messageDiv.style.borderRadius = '4px';
            messageDiv.style.textAlign = 'center';
            messageDiv.style.fontWeight = 'bold';
            console.log('Error:', error); // Log error for debugging
        }
    });
}

function confirmRestore() {
    const vendorID = document.getElementById('restoreVendorId').textContent;
    restoreVendor(vendorID);
}


// Example function to close the modal
function closeModal() {
    const modal = document.getElementById('yourModalId'); // Replace with your modal's ID
    modal.style.display = 'none'; // Hide the modal
    // Or use a library-specific method to close the modal
    // e.g., $('#yourModalId').modal('hide'); for Bootstrap
}



function exportToCSV() {
    window.location.href = 'export_csv.php';
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
// Toggle the custom dropdown when the button is clicked
document.querySelector('.custom-dropdown-btn').addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent event from affecting other elements like the side menu
    document.querySelector('.custom-dropdown').classList.toggle('show');
});

// Close the dropdown if clicked outside of it
window.onclick = function(event) {
    if (!event.target.matches('.custom-dropdown-btn')) {
        var dropdowns = document.getElementsByClassName("custom-dropdown-container");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.parentNode.classList.contains('show')) {
                openDropdown.parentNode.classList.remove('show');
            }
        }
    }
};
function openQRModal(vendorID) {
    const qrModalContent = document.getElementById("qrModalContent");
    const vendors = <?php echo json_encode($archive_vendors); ?>;
    const vendor = vendors.find(function(item) {
        return item.vendorID == vendorID;
    });

    if (vendor) {
        const qrCodePath = '<?php echo $archivePath ?>' + vendor.qrimage;

        const img = document.createElement('img');
        img.src = qrCodePath;
        img.style.maxWidth = '100%';
        qrModalContent.innerHTML = ''; // Clear existing content
        qrModalContent.appendChild(img);

        const closeButton = document.createElement('span');
        closeButton.className = 'close-qrcode';
        closeButton.innerHTML = '&times;';
        closeButton.onclick = function () {
            closeQRModal();
        };
        qrModalContent.appendChild(closeButton);

        const qrModal = document.getElementById("qrModal");
        qrModal.style.display = "block";
    } else {
        alert("Vendor not found!");
    }
}

function closeQRModal() {
    const qrModal = document.getElementById("qrModal");
    qrModal.style.display = "none";
}

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

</script>

</body>
</html>