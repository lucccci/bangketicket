<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

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
            width: 260px;
            height: 100vh;
            background-color: #fff;
            color: #031F4E;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            overflow-x:hidden;
            transition: width 0.3s;
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
            transition: background 0.3s ease, color 0.3s ease, transform 0.2s ease-in-out; /* Smooth transitions for hover */
        }

        .side-menu a:hover {
            background-color: #2A416F;
            color: #fff;
            
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
    font-size: 24px;        /* Adjust to fit within the container */
    margin: 0;              /* Remove default margins */
    padding: 10px 0;        /* Control padding, reduce if necessary */
    text-align: left;       /* Align text to the left */
    white-space: nowrap;    /* Prevent the text from wrapping to a new line */
    overflow: hidden;       /* Hide overflow if it's too long */
    text-overflow: ellipsis; /* Add ellipsis (...) if the text is too long for the container */
    
}

/* Ensure the main content has the correct spacing */
.main-content {
    background-color: #F2F7FC; /* Light overall background */
    padding: 20px;
    margin-left: 260px; /* Adjusted for sidebar width */
    min-height: 100vh; /* Ensure full height */
    box-sizing: border-box;
    overflow-x: hidden; /* Prevent horizontal scrolling */
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
    justify-content: space-between; /* Adjusts the space between title and button */
}

/* Ensuring some space between title and button */
.heading-with-button h2 {
    font-size:29px;
   
}

/* Unique styles for the custom dropdown */
.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-btn {
    margin-right:910px;
    color: black;
    padding: 10px;
    font-size: 16px;
    border: none;
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
.search-button i{
  font-size: 15px;
}

.search-button:hover {
  background-color: #2A416F;
    color: #fff;
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
.additional-info .info-row {
    padding-left: 10px;
    border-left: 3px solid #031F4E; /* Blue left border */
    margin-bottom: 10px;
    padding-bottom: 5px;
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
    </style>
</head>
<body>

<div class="header-panel"></div>

<div id="sideMenu" class="side-menu">
    <div class="logo">
        <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="product.php"><i class="fas fa-box"></i> Product</a>

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
    <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

<div class="main-content">
    <div class="panel">
        <div class="heading-with-button">
            <h2>Archive Records</h2>

            <!-- Add dropdown button here -->
            <div class="custom-dropdown">
                <button class="custom-dropdown-btn">
                    <i class="far fa-caret-square-down"></i>
                </button>
                <div class="custom-dropdown-container">
                    <a href="archive-collector.php">Archive Collectors</a>
                </div>
            </div>
        </div>
        <div class="filter-container">
    <!-- Add other existing filters and buttons -->
    <div class="filter-options">
  
        <select id="filterDropdown">
            <option value="">Select Filter</option>
            <option value="vendorID">Vendor ID</option>
            <option value="lname">Last Name</option>
        </select>
        <input type="text" id="filterInput" placeholder="Enter filter value">
        <button class="search-button" onclick="filterTable()">
            <i class="fas fa-search"></i> <!-- Font Awesome Search Icon -->
        </button>
        <!-- Export button -->
        <button class="export-button" onclick="exportToCSV()">Export</button>
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
                    <td><?php echo htmlspecialchars($vendor['suffix']); ?></td> <!-- Display Suffix -->
                    <td><?php echo htmlspecialchars($vendor['contactNo']); ?></td>
                    <td><td>
                        <button class="action-view" onclick="openQRModal('<?php echo htmlspecialchars($vendor['vendorID']); ?>')">View QR</button>
                        <button class="action-restore" onclick="restoreVendor('<?php echo htmlspecialchars($vendor['vendorID']); ?>')">
                            <i class="fas fa-undo"></i>
                        </button>
                    </td>
                </tr>
                <!-- Hidden additional details row with the title -->
                <tr class="additional-info" id="details-<?php echo htmlspecialchars($vendor['vendorID']); ?>" style="display:none;">
                    <td colspan="9">
                        <div class="info-title">
                            <strong>Additional Details</strong>
                        </div>
                        <!-- Each row of details wrapped in a div with the class 'info-row' -->
                        <div class="info-row">Gender: <?php echo htmlspecialchars($vendor['gender']); ?></div>
                        <div class="info-row">Birthday: <?php echo htmlspecialchars($vendor['birthday']); ?></div>
                        <div class="info-row">Age: <?php echo htmlspecialchars($vendor['age']); ?></div>
                        <div class="info-row">Province: <?php echo htmlspecialchars($vendor['province']); ?></div>
                        <div class="info-row">Municipality: <?php echo htmlspecialchars($vendor['municipality']); ?></div>
                        <div class="info-row">Barangay: <?php echo htmlspecialchars($vendor['barangay']); ?></div>
                        <div class="info-row">House #: <?php echo htmlspecialchars($vendor['houseNo']); ?></div>
                        <div class="info-row">Street Name: <?php echo htmlspecialchars($vendor['streetname']); ?></div>
                    </td>
                </tr>

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

<script>

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

function restoreVendor(vendorID) {
    if (confirm('Are you sure you want to restore this vendor?')) {
        $.ajax({
            url: 'restore_vendor.php',
            type: 'POST',
            data: { vendorID: vendorID },
            success: function (response) {
                if (response.trim() === 'success') {
                    // Select and remove the vendor row and the details row
                    const vendorRow = document.querySelector(`tr.vendor-row[data-vendor-id='${vendorID}']`);
                    const detailsRow = document.getElementById('details-' + vendorID);

                    if (vendorRow) {
                        vendorRow.remove(); // Remove the main vendor row from the table
                    }

                    if (detailsRow) {
                        detailsRow.remove(); // Remove the additional details row from the table
                    }

                    // Check if there are any remaining rows (except the table header and footer)
                    const remainingRows = document.querySelectorAll('.usersTable tbody tr.vendor-row');
                    if (remainingRows.length === 0) {
                        // If no more rows are left, display the "No records found" message
                        const tbody = document.querySelector('.usersTable tbody');
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="9" style="text-align: left;">No records found</td>
                            </tr>
                        `;
                    }

                    alert('Vendor restored successfully.');
                } else {
                    // Handle failure (display the error message from PHP)
                    alert('Error: ' + response);
                }
            },
            error: function (xhr, status, error) {
                console.log('Error:', error); // Log error for debugging
                alert('An error occurred while restoring the vendor.');
            }
        });
    }
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

</script>

</body>
</html>