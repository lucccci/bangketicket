<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

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
                $insert_sql = "INSERT INTO archive_vendors (vendorID, fname, mname, lname, suffix, gender, birthday, age, contactNo, province, municipality, barangay, houseNo, streetname, qrimage) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssssssissssssss", 
                    $vendor_data['vendorID'], 
                    $vendor_data['fname'], 
                    $vendor_data['mname'], 
                    $vendor_data['lname'], 
                    $vendor_data['suffix'], 
                    $vendor_data['gender'], 
                    $vendor_data['birthday'], 
                    $vendor_data['age'], 
                    $vendor_data['contactNo'], 
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
        $edit_province = sanitizeInput($_POST['edit_province']);
        $edit_municipality = sanitizeInput($_POST['edit_municipality']);
        $edit_barangay = sanitizeInput($_POST['edit_barangay']);
        $edit_houseNo = sanitizeInput($_POST['edit_houseNo']);
        $edit_streetname = sanitizeInput($_POST['edit_streetname']);

        // Update the vendor details in the database
        $update_sql = "UPDATE vendor_list SET fname=?, mname=?, lname=?, suffix=?, gender=?, birthday=?, age=?, contactNo=?, province=?, municipality=?, barangay=?, houseNo=?, streetname=? WHERE vendorID=?";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt === false) {
            echo "<script>alert('Error preparing update statement: " . $conn->error . "');</script>";
            exit();
        }

        $stmt->bind_param("ssssssisssssss", $edit_fName, $edit_mname, $edit_lName, $edit_suffix, $edit_gender, $edit_birthday, $edit_age, $edit_contactNo, $edit_province, $edit_municipality, $edit_barangay, $edit_houseNo, $edit_streetname, $vendor_id_to_edit);
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

.add-vendor-button:hover {
  background-color: #2A416F;
  color: #fff;
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
    <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
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
        <button class="export-button" onclick="location.href='export.php'">Export</button>
    </div>
    <button class="add-vendor-button" onclick="location.href='vendorform.php'">Add Vendor</button>  
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
                <th>Status</th>
                <th>Contact #</th>
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
                    <td><?php echo $customer['fname']; ?></td>
                    <td><?php echo $customer['mname']; ?></td>
                    <td><?php echo $customer['lname']; ?></td>
                      <td><?php echo !empty($customer['suffix']) ? $customer['suffix'] : 'N/A'; ?></td>
            <td><?php echo $customer['status']; ?></td>  <!-- Display the status (Paid or Unpaid) -->
                    <td><?php echo $customer['contactNo']; ?></td>
                    <td>
                        <button class="action-view" onclick="openQRModal('<?php echo $customer['vendorID']; ?>')">View QR</button>
                        <button class="action-edit" onclick="openEditModal('<?php echo $customer['vendorID']; ?>')">
    <i class="fa fa-edit"></i>
</button>

                        </button>
                        <form style="display: inline;" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to move Vendor ID <?php echo $customer['vendorID']; ?> - <?php echo $customer['fname']; ?> <?php echo $customer['lname']; ?> to the Archive Section?');">
    <input type="hidden" name="cust_id_to_delete" value="<?php echo $customer['vendorID']; ?>">
    <button type="submit" class="action-delete"><i class="fa fa-trash"></i></button>
</form>

                    </td>
                </tr>
                <!-- Hidden additional details row -->
                <tr class="additional-info">
                    <td colspan="9">
                        <div class="info-title">
                            <strong>Additional Details</strong>
                        </div>
                        <div class="info-row">
                            <strong>Gender:</strong> <?php echo $customer['gender']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Birthday:</strong> <?php echo $customer['birthday']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Age:</strong> <?php echo $customer['age']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Province:</strong> <?php echo $customer['province']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Municipality:</strong> <?php echo $customer['municipality']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Barangay:</strong> <?php echo $customer['barangay']; ?>
                        </div>
                        <div class="info-row">
                            <strong>House #:</strong> <?php echo $customer['houseNo']; ?>
                        </div>
                        <div class="info-row">
                            <strong>Street Name:</strong> <?php echo $customer['streetname']; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9">
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
        <!-- Hidden Logo Image -->
        <img id="malolosLogo" src="pics/malolos-logo.png" style="display: none;">
        <img id="hiddenLogo" src="pics/logo-bt.png" style="display: none;">

        <h2 style="text-align: center;">Vendor Basic Information</h2>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <input type="hidden" name="vendor_id_to_edit" id="edit_vendor_id" value="">
            <label for="edit_fName">First Name:</label>
            <input type="text" id="edit_fName" name="edit_fName" required><br>
            <label for="edit_mname">Middle Name:</label>
            <input type="text" id="edit_mname" name="edit_mname" required><br>
            <label for="edit_lname">Last Name:</label>
            <input type="text" id="edit_lName" name="edit_lName" required><br>

            <label for="edit_suffix">Suffix:</label>
            <select id="edit_suffix" name="edit_suffix">
                  <option value="">Select Suffix</option>
                  <option value="Jr.">Jr.</option>
                  <option value="V">Sr.</option>
                  <option value="II">II</option>
                  <option value="II">III</option>
                  <option value="II">IV</option>
                  <option value="II">V</option>
              </select><br><br>

          <label for="edit_gender">Gender:</label>
<select id="edit_gender" name="edit_gender" required>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
</select>
<br><br>

            <label for="edit_birthday">Birthday:</label>
            <input type="date" id="edit_birthday" name="edit_birthday" onchange="calculateAge()" required><br>

            <label for="edit_age">Age:</label>
            <input type="text" id="edit_age" name="edit_age" readonly><br>
              
            <label for="edit_contactNo">Contact Number:</label>
<div style="position: relative;">
    <img src="philippineflag.webp" alt="PH" width="20" height="auto" style="position: absolute; left: 5px; top: 8px;">
    <span style="position: absolute; left: 30px; top: 8px; color: #333;font-size: 14px;">+63</span>
    <input type="text" id="edit_contactNo" name="edit_contactNo" pattern="[0-9]{10}" maxlength="10" style="padding-left: 60px; width: 200px;" placeholder="XXXXXXXXXX" required>
</div>

<div class="form-container">
  <label for="edit_province">Province:</label>
  <select id="edit_province" name="edit_province" required onchange="updateCityMunicipality()">
    <option value="">Select Province</option>
    <option value="Aurora">Aurora</option>
    <option value="Bataan">Bataan</option>
    <option value="Bulacan">Bulacan</option>
    <option value="Nueva Ecija">Nueva Ecija</option>
    <option value="Pampanga">Pampanga</option>
    <option value="Tarlac">Tarlac</option>
    <option value="Zambales">Zambales</option>
  </select><br>

  <label for="edit_municipality">Municipality:</label>
  <select id="edit_municipality" name="edit_municipality" required onchange="updateBarangay()">
    <option value="">Select Municipality</option>
  </select><br>

  <label for="edit_barangay">Barangay:</label>
  <select id="edit_barangay" name="edit_barangay" required>
    <option value="">Select Barangay</option>
  </select><br>
</div>

            <label for="edit_houseNo">House Number:</label>
            <input type="text" id="edit_houseNo" name="edit_houseNo" required><br>
            <label for="edit_streetname">Street Name:</label>
            <input type="text" id="edit_streetname" name="edit_streetname" required><br>
            
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



const barangayData = {
    //Municipality of bulacan and barangays
    "Angat": ["Banaban", "Baybay", "Binagbag", "Donacion", "Encanto", "Laog", "Marungko", "Mercado", "Niugan", "Paltok", "Pulong Yantok", "San Roque", "Santa Cruz", "Sapang Pari", "Taboc", "Binagbag"],
    "Balagtas": ["Borol 1st", "Borol 2nd", "Dalig", "Longos", "Panginay", "Pulong Gubat", "San Juan", "Santol", "Wawa"],
    "Angeles": ["Agapito Del Rosario", "Anunas", "Balibago", "Capaya", "Claro M. Recto", "Cuayan", "Cutcut", "Cutud", "Lourdes Northwest", "Lourdes Sur", "Lourdes Sur East", "Malabanias", "Margot", "Marisol", "Mining", "Pampang", "Pandan", "Pulung Maragul", "Pulung Cacutud", "Pulung Bulu", "Salapungan", "San Jose", "San Nicolas", "Santa Teresita", "Santa Trinidad", "Santo Domingo", "Santo Rosario", "Sapalibutad", "Sapangbato", "Tabun", "Virgen Delos Remedios"],
    "Balagtas": ["Borol 1st", "Borol 2nd", "Dalig", "Longos", "Panginay", "Pulong Gubat", "San Juan", "Santol", "Wawa"],
    "Baliuag": ["Bagong Nayon", "Barangca", "Batia", "Calantipay", "Catulinan", "Concepcion", "Makinabang", "Matangtubig", "Paitan", "Poblacion", "Sabang", "San Jose", "San Roque", "Santa Barbara", "Santa Cruz", "Tangos", "Tiaong", "Tilapayong", "Virgen Delas Flores"],
    "Bocaue": ["Antipona", "Bagumbayan", "Bambang", "Batia", "Biñang 1st", "Biñang 2nd", "Bolacan", "Bundukan", "Bunlo", "Caingin", "Duhat", "Igulot", "Lolomboy", "Poblacion", "Sulucan", "Taal", "Tambubong", "Turo", "Wakas"],
    "Bulakan": ["Bagumbayan", "Balubad", "Bambang", "Matungao", "Maysantol", "Pitpitan", "Perez", "San Francisco", "San Jose", "San Nicolas", "Santa Ana", "Sapang", "Taliptip", "Tibig"],
    "Bustos": ["Bonga Mayor", "Bonga Menor", "Camachile", "Cambaog", "Catacte", "Malamig", "Mina", "Pagala", "Poblacion", "San Pedro", "Santor", "Talampas"],
    "Calumpit": ["Balungao", "Buguion", "Calizon", "Calumpang", "Corazon", "Frances", "Gatbuca", "Gugo", "Iba Este", "Iba Oeste", "Longos", "Lumbreras", "Mabolo", "Maysantol", "Palimbang", "Panginay", "Pio Cruzcosa", "Poblacion", "Pulo", "San Jose", "San Juan", "San Marcos", "Santa Catalina", "Santa Lucia", "Santo Cristo", "Sapang Bayan", "Sapang Putol", "Sucol", "Tabon"],
    "Doña Remedios Trinidad": ["Bagong Barrio", "Bakal I", "Bakal II", "Bayabas", "Camachin", "Camachile", "Kalawakan", "Kabayunan", "Pulong Sampalok", "Sapang Bulak"],
    "Guiguinto": ["Cutcut", "Daungan", "Ilang-ilang", "Malis", "Panginay", "Poblacion", "Pritil", "Pulong Gubat", "Santa Cruz", "Santa Rita","Tabang","Tabe","Tiaong","Tuktukan"],
    "Hagonoy": ["Abulalas", "Carillo", "Iba", "Iba-Iba", "Palapat", "Pugad", "San Agustin", "San Isidro", "San Jose", "San Juan", "San Miguel", "San Nicolas", "San Pablo", "San Pascual", "San Pedro", "San Roque", "Santa Elena", "Santa Monica", "Santo Niño", "Santo Rosario", "Tampok"],
    "Malolos": ["Anilao", "Atlag", "Babatnin", "Bagna", "Bagong Bayan", "Balayong", "Balite", "Bangkal", "Barihan", "Bungahan", "Caingin", "Calero", "Caliligawan", "Canalate", "Caniogan", "Catmon", "Cofradia", "Dakila", "Guinhawa", "Liang", "Ligas", "Longos", "Look 1st", "Look 2nd", "Lugam", "Mabolo", "Mambog", "Masile", "Matimbo", "Mojon", "Namayan", "Niugan", "Pamarawan", "Panasahan", "Pinagbakahan", "San Agustin", "San Gabriel", "San Juan", "San Pablo", "San Vicente", "Santiago", "Santisima Trinidad", "Santo Cristo", "Santo Niño", "Santo Rosario", "Santor", "Sumapang Bata", "Sumapang Matanda", "Taal", "Tikay"],
    "Marilao": ["Abangan Norte", "Abangan Sur", "Ibayo", "Lambakin", "Lias", "Loma de Gato", "Nagbalon", "Patubig", "Poblacion I", "Poblacion II", "Prenza I", "Prenza II", "Santa Rosa I", "Santa Rosa II", "Saog", "Tabing Ilog"],
    "Meycauayan": ["Bahay Pare", "Bancal", "Banga", "Batong Malake", "Bayugo", "Caingin", "Calvario", "Camalig", "Hulo", "Iba", "Langka", "Lawa", "Libtong", "Liputan", "Longos", "Malhacan", "Pajo", "Pandayan", "Pantoc", "Perez", "Poblacion", "Saint Francis", "Saluysoy", "Tugatog", "Ubihan", "Zamora"],  
    "Norzagaray": ["Bangkal", "Baraka", "Bigte", "Bitungol", "Friendship Village Resources", "Matictic", "Minuyan", "Partida", "Pinagtulayan", "Poblacion", "San Lorenzo", "San Mateo", "Santa Maria", "Tigbe"],
    "Obando": ["Binuangan", "Hulo", "Lawa", "Mabolo", "Pag-asa", "Paliwas", "Panghulo", "San Pascual", "Tawiran", "Ubihan", "Paco", "Salambao"],
    "Pandi": ["Bagong Barrio", "Bagong Pag-asa", "Baka-bakahan", "Bunsuran 1st", "Bunsuran 2nd", "Bunsuran 3rd", "Cacarong Bata", "Cacarong Matanda", "Cupang", "Malibong Bata", "Manatal", "Mapulang Lupa", "Masuso", "Masuso East", "Poblacion", "Real de Cacarong", "Santo Niño", "San Roque", "Siling Bata", "Siling Matanda"],
    "Paombong": ["Akle", "Bagong Barrio", "Balagtas", "Binakod", "Kapiti", "Malumot", "Pinalagdan", "Poblacion", "San Isidro I", "San Isidro II", "San Jose", "San Roque", "San Vicente", "Santa Cruz", "Santa Lucia", "Sapang Dalaga"],
    "Plaridel": ["Agnaya", "Bagong Silang", "Banga 1st", "Banga 2nd", "Bintog", "Bulihan", "Caniogan", "Dampol", "Lumang Bayan", "Parulan", "Poblacion", "Pulong Bayabas", "San Jose", "Santa Ines", "Santo Cristo", "Santo Niño", "Sapang Putol"],
    "Pulilan": ["Balatong A", "Balatong B", "Cutcot", "Dampol 1st", "Dampol 2nd", "Dulong Malabon", "Inaon", "Longos", "Lumbac", "Paltao", "Penabatan", "Poblacion", "Santa Peregrina", "San Francisco", "Tibag", "Tabon", "Tibag"],
    "San Ildefonso": ["Akling", "Alagao", "Anyatam", "Bagong Barrio", "Bagong Pag-asa", "Basuit", "Bubulong Malaki", "Calasag", "Calawitan", "Casalat", "Lapnit", "Malipampang", "Masile", "Matimbubong", "Paltao", "Pinaod", "Poblacion", "Pulong Tamo", "San Juan", "Sapang Dayap", "Sumandig", "Telepatio", "Upig", "Ulingao"],
    "San Miguel": ["Bagong Silang", "Balaong", "Bardias", "Baritan", "Biazon", "Bicas", "Buga", "Buliran", "Calumpang", "Cambita", "Camias", "Damas", "Ilog Bulo", "Kabaritan", "King Kabayo", "Lico", "Lomboy", "Magmarale", "Maligaya", "Mandile", "Manggahan", "Matimbubong", "Pacalag", "Paliwasan", "Poblacion", "Pulong Duhat", "Sacdalan", "Salangan", "San Agustin", "San Jose", "San Juan", "San Roque", "San Vicente", "Santa Lucia", "Santa Rita Bata", "Santa Rita Matanda", "Santo Cristo", "Sapang", "Sapang Dayap", "Sapang Putik", "Tandiyong Bakal", "Tibagan", "Tucdoc", "Tumana", "Tungkong Mangga", "Tungkong Munti", "Tungkong Silangan", "Tungkong Upper"],
    "San Rafael": ["Banca-Banca", "Caingin", "Coral na Bato", "Cruz na Daan", "Dagat-Dagatan", "Diliman I", "Diliman II", "Libis", "Lico", "Maasim", "Mabalas-Balas", "Mabini", "Malapad na Parang", "Maronguillo", "Pacalag", "Pagala", "Pantubig", "Pasong Bangkal", "Poblacion", "Pulong Bayabas", "Salapungan", "San Agustin", "San Roque", "Sapang Putik", "Talacsan", "Tambubong", "Tungkong Mangga", "Ulingao"],
    "Santa Maria": ["Bagbaguin", "Balasing", "Buenavista", "Bulac", "Camangyanan", "Catmon", "Cay Pombo", "Caysio", "Dulong Bayan", "Guyong", "Lalakhan", "Mag-asawang Sapa", "Mahabang Parang", "Manggahan", "Parada", "Poblacion", "Pulong Buhangin", "San Gabriel", "San Jose Patag", "San Vicente", "Santa Clara", "Santa Cruz", "Silangan", "Tabing Bakod", "Tumana"],   
    "San Jose del Monte": ["Bagong Buhay I", "Bagong Buhay II", "Bagong Buhay III", "Ciudad Real", "Dulong Bayan", "Fatima I", "Fatima II", "Fatima III", "Fatima IV", "Fatima V", "Francisco Homes-Guijo", "Francisco Homes-Mulawin", "Francisco Homes-Narra", "Francisco Homes-Yakal", "Gaya-Gaya", "Graceville", "Kaybanban", "Kaypian", "Lawang Pare", "Minuyan I", "Minuyan II", "Minuyan III", "Minuyan IV", "Minuyan Proper", "Poblacion", "Poblacion I", "Poblacion II", "Poblacion III", "San Isidro", "San Manuel", "San Martin I", "San Martin II", "San Martin III", "San Martin IV", "San Martin V", "San Pedro", "Santa Cruz", "Sapang Palay Proper", "Santo Cristo", "Tungkong Mangga"],

    //Municipality of aurora and barangays
    "Baler": ["Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Barangay IV (Poblacion)", "Buhangin", "Calabuanan", "Obligacion", "Pingit", "Reserva", "Sabang", "Suklayin", "Zabali"],
    "Casiguran": ["Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", "Barangay 7 (Poblacion)", "Barangay 8 (Poblacion)", "Calangcuasan", "Cozo", "Culat", "Dibacong", "Esperanza", "Lual", "San Ildefonso", "Tabas"],
    "Dilasag": ["Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Barangay 4 (Poblacion)", "Diniog", "Dicabasan", "Dilaguidi", "Esperanza", "Lawang", "Masagana"],
    "Dinalungan": ["Abuleg", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Dibaraybay", "Dimabuno", "Lipit", "Mapalad"],
    "Dingalan": ["Aplaya", "Butas na Bato", "Caragsacan", "Davildavilan", "Ibona", "Lagsing", "Maligaya", "Matawe", "Paltic", "Poblacion", "Tanawan", "Umiray", "White Beach"],
    "Dipaculao": ["Bacong", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Bani", "Borlongan", "Buenavista", "Calaocan", "Dibutunan", "Dinadiawan", "Diteki", "Gupa", "Lobbot", "Maligaya", "Mucdol"],
    "Maria Aurora": ["Alcala", "Bagtu", "Bayanihan", "Bazal", "Dialatnan", "Diaat", "Dibut", "Diarabasin", "Dimanayat", "Ditumabo", "Kadayacan", "Malasin", "Suguit", "Villa Aurora", "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", "Quirino"],
    "San Luis": ["Bacong", "Balete", "Dibalo", "Dibut", "Dimanayat", "Ditumabo", "L. Pimentel", "Nonong Senior", "Real", "San Isidro", "San Jose", "San Juan", "Zarah"],

    //Municipality of bataan and barangays
  "Abucay": ["Bangkal", "Calaylayan", "Capitangan", "Gabon", "Laon", "Mabatang", "Omboy", "Panibatuhan", "Salamague", "Wawa"],
  "Bagac": ["Atilano L. Ricardo", "Bagumbayan", "Binuangan", "Ibaba", "Ibis", "Parang", "Paysawan", "Quinawan", "Pag-Asa", "Banawang", "Binukawan"],
  "Balanga City": ["Bagong Silang", "Bagumbayan", "Cataning", "Cupang Proper", "Cupang West", "Dangcol", "Ibayo", "Malabia", "Poblacion", "San Jose", "San Juan", "Sibacan", "Talisay", "Tenejero", "Tortugas"],
  "Dinalupihan": ["Alis", "Colo", "Daang Bago", "Del Rosario", "Gen. Luna", "Happy Valley", "Katipunan", "Layac", "Luacan", "Mabini", "Magsaysay", "Maligaya", "Naparing", "New San Jose", "Old San Jose", "Padre Dandan", "Pag-Asa", "Pagalanggang", "Poblacion", "Roxas", "Saguing", "San Benito", "San Isidro", "San Pablo", "San Ramon", "Santa Isabel"],
  "Hermosa": ["A. Rivera", "Almacen", "Bacong", "Balsic", "Burgos", "Cataning", "Del Pilar", "Lamao", "Mabiga", "Mabuco", "Mandama", "Maite", "Palihan", "Pulo", "Saba", "Sawang", "Sumalo"],
  "Limay": ["Alangan", "Duale", "Kitang 1 and 2", "Lamao", "Landing", "Poblacion", "Reformista", "Saint Francis II", "San Francisco de Asis", "San Isidro", "Tuyo", "Wawa", "Kitang I"],
  "Mariveles": ["Alas-asin", "Balon-Anito", "Batangas II", "Baseco", "Camaya", "Iting", "Lamao", "Lucanin", "Malaya", "Maligaya", "Poblacion", "San Carlos", "San Isidro", "Santo Rosario", "Sisiman", "Townsite", "Wawa"],
  "Morong": ["Binaritan", "Mabayo","Nagbalayong","Sabang", "Poblacion"],
  "Orani": ["Apollo", "Bagong Paraiso", "Balut", "Bayan", "Calero", "Centro", "Doña", "Kapinpin", "Kolinlang", "Mulawin", "Pansacala", "Pociano", "Pantalan Luma", "Paraiso", "Santo Domingo", "Santo Rosario", "Wawa"],
  "Orion": ["Arellano", "Bagumbayan", "Balagtas", "Balut", "Bantan", "Calungusan", "Daan Bilolo", "Daang Parola", "Kapunitan", "Lati", "Lucanin", "Pandatung", "Puting Buhangin", "Sabatan", "San Vicente"],
  "Pilar": ["Bagumbayan", "Balut", "Barangay Pantingan", "Liyang", "Nagwaling", "Panilao", "Pita", "Saint Francis I", "Santa Rosa", "Wakas"],


   //Municipality of nueva ecija and barangays
    "Aliaga": ["Aliaga", "Baguio", "Banga", "Barangka", "Bitas", "Bongabong", "Bulan-bulan", "Calabuan", "Dela Paz", "Gapan", "Inbit", "Lubo", "Mabini", "Malinao", "Mambog", "Mandalag", "Mauway", "Minuli", "Nagcatumbalen", "San Vicente", "Santo Domingo"],
    "Bongabon": ["Bagong Sikat", "Basilang", "Bitulok", "Cuyapo", "Lumbang", "Malimba", "Malanday", "Manganay", "Mangalang", "Magsaysay", "Mabini", "Poblacion", "San Vicente", "Santa Maria", "Tagpos"],
    "Cabiao": ["Bañadero", "Biclat", "Bulaon", "Cabitang", "Dila-dila", "Longos", "Malabanan", "Maligaya", "Poblacion", "San Jose", "San Juan", "San Roque", "San Vicente"],
    "Cabanatuan": ["Bagong Sikat", "Banggain", "Buan", "Caalibangbangan", "Capas", "Canawan", "Carmen", "H. del Pilar", "Hulo", "Imelda", "Laurel", "Poblacion", "San Jose", "San Miguel", "San Pablo", "San Roque", "Santa Rita", "Santiago", "Taal", "Tungkong Mangga"],
    "Carranglan": ["Atut, Bataan", "Carmen", "Del Pilar", "Dingalan", "Labrador", "Lemon", "Magsaysay", "Maranon", "Poblacion", "San Felipe", "San Jose", "San Vicente", "Tala", "Urbiztondo"],
    "Cuyapo": ["Bagumbayan", "Bangal", "Bocaue", "Cuyapo", "Dawis", "Dolores", "Hidalgo", "Layog", "Magaspac", "Maligaya", "Mangat", "Mangga", "Mansaraysayan", "San Antonio", "San Felipe", "San Isidro", "San Juan", "San Vicente", "Santa Rosa", "Tondo"],
    "Gapan": ["Alvarez", "Bangued", "Bata", "Bocobo", "Bongabon", "Bunbungan", "Concepcion", "Duhat", "Hulog", "Jaen", "Mabini", "Poblacion", "San Isidro", "San Vicente", "San Jose", "Santo Domingo", "Santa Lucia"],
    "Gabaldon": ["Bayanan", "Bungahan", "Bunga", "Dela Paz", "La Torre", "Magsaysay", "Mangalang", "Maluya", "Mayantoc", "Poblacion", "San Isidro", "San Vicente", "Santa Maria"],
    "General Mamerto Natividad": ["A. Mendoza", "Bagong Sikat", "Bayanan", "Caguioa", "Camuin", "Dila-dila", "Doña Aurora", "Habul", "Labrador", "Maimpis", "Mawaca", "Poblacion", "San Felipe", "San Isidro"],
    "General Tinio": ["Alua", "Cangca", "Guimba", "Maguin", "Malabnang", "Manggahan", "Natividad", "Poblacion", "San Jose", "San Vicente"],
    "Guimba": ["Bagong Sikat", "Barangal", "Buliran", "Cayanga", "Gapan", "Magsaysay", "Malawig", "Mawaca", "Poblacion", "San Felipe", "San Isidro"],
    "Jaen": ["Bagong Sikat", "Baliwag", "Bungahan", "Bunbungan", "Cabaruan", "Cacutud", "Carmen", "Hulo", "Magsaysay", "Maligaya", "Poblacion", "San Antonio", "San Isidro", "San Vicente"],
    "Laur": ["Bagong Silang", "Bamban", "Bunbungan", "Labrador", "Magsaysay", "Malabnang", "Mansaraysayan", "Natividad", "Poblacion", "San Jose", "San Vicente"],
    "Licab": ["Bansalangin", "Cangcawayan", "Malimango", "Poblacion", "San Isidro", "San Vicente", "Santa Maria"],
    "Llanera": ["Bayanan", "Bitas", "Magsaysay", "Manggahan", "Milan", "Poblacion", "San Isidro", "San Jose", "San Vicente"],
    "Lupao": ["Bagong Silang", "Camascan", "Cayapa", "Cayanga", "Cuyapo", "Kangaro", "Langka", "San Jose", "San Vicente"],
    "Muñoz": ["Bagong Silang", "Balinag", "Bulaon", "Bunga", "Cabunian", "Cananay", "Casaloy", "Gabaldon", "Librad", "Luna", "San Jose", "Santa Cruz"],
    "Nampicuan": ["Bagong Sikat", "Bayo", "Bitas", "Calabuan", "Maligaya", "Nampicuan", "Poblacion", "San Antonio", "San Jose"],
    "Pantabangan": ["Bagumbayan", "Buan", "Cansuso", "Dapdap", "Del Pilar", "Imelda", "Poblacion", "San Jose"],
    "Peñaranda": ["Bansalangin", "Biclat", "Bubuyan", "Magtangola", "Magsaysay", "Maligaya", "Masilang", "Poblacion", "San Jose", "San Vicente"],
    "Quezon": ["Baguio", "Bambang", "Bubuy", "Cagayan", "Dapdap", "Imbang", "Poblacion", "San Jose"],
    "Rizal": ["Bucot", "Bulihan", "Canantong", "Dila-dila", "Elias Angeles", "Hampangan", "Hulo", "Kalabangan", "Poblacion", "San Vicente", "San Jose", "Santa Rosa"],
    "San Antonio": ["Bagong Sikat", "Bulaon", "Luneta", "Poblacion", "San Isidro"],
    "San Isidro": ["Bagong Silang", "Bayan", "Bulacan", "Cabaruan", "Magdalena", "Maligaya", "Manggahan", "Poblacion", "San Vicente"],
    "San Jose": ["Bungabon", "Concepcion", "Gapan", "Guimba", "La Paz", "Licab", "Mabini", "Magsaysay", "Poblacion", "San Vicente", "Santa Maria"],
    "San Leonardo": ["Bacala", "Bayan", "Bulong", "Cabuyao", "Cacanauan", "Concepcion", "Magsaysay", "Manggahan", "San Isidro", "San Vicente", "Santa Maria"],
    "Santa Rosa": ["Bagumbayan", "Baguio", "Balingcanaway", "Bongabong", "Dila-dila", "Magsaysay", "Maligaya", "Poblacion", "San Isidro"],
    "Santo Domingo": ["Bamban", "Baru-an", "Cabalintan", "Carmen", "Dela Paz", "Gulod", "Magsaysay", "Poblacion", "San Jose"],
    "Talavera": ["Aliaga", "Alvila", "Bagumbayan", "Baliwag", "Bulaon", "Canarail", "Cangca", "Carmen", "Gulod", "Hulo", "Labrador", "Malabnang", "Poblacion", "San Vicente"],
    "Talugtug": ["Bagumbayan", "Bataan", "Bulasan", "Cabalantian", "Canak", "Dapdap", "Malibay", "Poblacion", "San Vicente", "Santa Rosa"],
    "Zaragoza": ["Banuang", "Biga", "Bagumbayan", "Bongabon", "Poblacion", "San Vicente"],

    //Municipality of  pampanga and barangays
    "Angeles City": ["Agapito Del Rosario", "Anunas", "Balibago", "Bical", "Capaya", "Cutcut", "Del Rosario", "Duquit", "Epifanio", "Pulungbulu", "San Jose", "San Nicolas", "Santo Rosario", "Sapangbato", "Telebastagan"],
    "Apalit": ["Bamboo", "Banal", "Bata", "Bucal", "Cansinala", "Dila-Dila", "Janipaan", "Santo Cristo", "San Vicente", "Santo Tomas"],
    "Arayat": ["Bagong Sikat", "Banga", "Bañadero", "Bulu", "Caduang Tete", "Cutcut", "San Pedro", "San Juan", "Santa Lucia"],
    "Bacolor": ["Bacolor", "Bulaon", "Magsaysay", "San Vicente", "San Pablo", "Santo Niño"],
    "Candaba": ["Bacong", "Bambang", "Cabuyao", "Capalangan", "Dulong Baybay", "Mabilog", "Malusac", "San Francisco", "San Luis", "Santo Rosario"],
    "Floridablanca": ["Bamban", "Bayan", "Bocaue", "Bulaon", "Dela Paz", "Duquit", "Lusong", "San Jose", "San Pedro", "San Vicente"],
    "Guagua": ["Balayong", "Bayan", "Bulaon", "Cameron", "Del Carmen", "Magsaysay", "Malusac", "Poblacion", "San Pedro", "Santo Rosario"],
    "Lubao": ["Bañadero", "Bucal", "Dela Paz", "Mabalacat", "Malusac", "San Felipe", "San Miguel", "San Pablo", "San Pedro", "Santo Niño"],
    "Mabalacat": ["Bamban", "Bayan", "Capas", "Dela Paz", "Laguna", "San Jose", "San Martin", "San Vicente", "Santo Rosario"],
    "Macabebe": ["Bangan", "Burol", "Concepcion", "Dulong Baybay", "Malusac", "Mansilingan", "Poblacion", "San Isidro", "Santa Barbara", "Santa Lucia"],
    "Masantol": ["Bagang", "Bamboo", "Bucal", "Capalangan", "Dela Paz", "Dulong Baybay", "Hapag", "Malusac", "Mansilingan", "Masantol", "San Jose", "San Miguel", "San Pablo", "San Pedro", "San Vicente", "Santo Niño", "Santo Tomas", "Sapangbato", "Sawa", "Tabuyucan", "Talang", "Tinang", "Tuloy", "Wawa", "Bacao"],
    "Mexico": ["Bagong Bataan", "Balibago", "Dela Paz", "Poblacion", "San Antonio", "San Jose", "San Pedro", "Santo Rosario"],
    "Porac": ["Bayan", "Bical", "Camachiles", "Dapdap", "Lambat", "Mabalacat", "Manibaug", "Santo Niño", "San Pedro"],
    "San Fernando": ["Baliti", "Del Pilar", "Guadalupe", "Julius B. Villanueva", "Lourdes Sur", "Poblacion", "San Agustin", "San Isidro", "San Jose", "San Juan"],
    "San Luis": ["Bagumbayan", "Bulaon", "Cansinala", "Dela Paz", "Maligaya", "Marangal", "San Fernando", "San Jose", "Santo Rosario"],
    "San Simon": ["Baleg", "Balucuc", "San Jose", "Santo Tomas", "Santa Monica"],
    "Sasmuan": ["Bamboo", "Buan", "Bulaon", "Guinbalay", "Lambat", "Malusac", "San Jose", "Santa Rosa"],

    //tarlac
    "Anao": ["Anao", "Baguio", "Bagong Bataan", "Cabitang", "Cabaluyan", "Calapacuan", "Camachile", "Dawis", "Dela Paz", "Guimba", "Malibong", "San Antonio", "San Jose", "San Juan", "San Pedro", "Santa Lucia"],
    "Bamban": ["Bagumbayan", "Bamban", "Cabaluan", "Cacabe", "Cayanga", "Malacat", "San Jose", "San Nicolas", "San Pablo", "Santa Rosa"],
    "Capas": ["Bamban", "Capas", "Cutcut", "Maruglu", "Mabalacat", "Manukang Bayan", "San Antonio", "San Jose", "San Juan", "Santa Juliana"],
    "Concepcion": ["Bamban", "Concepcion", "Nambalan", "Poblacion", "San Jose", "San Juan", "San Pedro", "Santa Rita"],
    "La Paz": ["Bacani", "Dela Paz", "Gomez", "La Paz", "Manalang", "San Isidro", "Santa Lucia", "Santo Domingo"],
    "Mayantoc": ["Banga", "Banga", "Bebong", "Bitao", "Cacabe", "Gapan", "Lawang Bato", "Nampicuan", "San Francisco", "San Jose", "San Vicente"],
    "Moncada": ["Bagong Sikat", "Balayong", "Bamban", "Canukang", "Concepcion", "Laoang", "Poblacion", "San Jose", "San Manuel", "San Rafael"],
    "Paniqui": ["Aglipay", "Concepcion", "Lourdes", "Magsaysay", "Manat", "Paniqui", "San Antonio", "San Jose", "San Luis", "San Pedro"],
    "San Jose": ["Banga", "Bucal", "Caniogan", "Dela Paz", "Guadalupe", "Laoang", "Poblacion", "San Jose", "Santa Lucia", "Santo Domingo"],
    "San Manuel": ["Alua", "Bagong Sikat", "Concepcion", "Nambalan", "Poblacion", "San Felipe", "San Jose", "San Juan"],
    "San Rafael": ["Bucao", "Bucal", "Maligaya", "Poblacion", "San Jose", "San Pedro", "Santa Rita"],
    "Santa Ignacia": ["Balaoan", "Bani", "Banua", "Batang", "Bucal", "Dapdap", "Japad", "Maligaya", "Poblacion", "San Jose"],
    "Tarlac City": ["Aguinaldo", "Balayong", "Balingcanaway", "Bamban", "Bata", "Calibutbut", "Labrador", "Lourdes", "Magsaysay", "Poblacion", "San Jose", "San Vicente", "Santo Cristo"],
    "Victoria": ["Abonador", "Bagong Bait", "Balungao", "Buan", "Bulac", "Dapdap", "Laguerta", "Liberty", "Lipa", "Mabalacat", "San Jose", "San Vicente", "Santa Rosa"],

// zambales
    "Botolan": ["Bagalangit", "Balaybay", "Banga", "Bebes", "Biclat", "Bunga", "Capas", "Columban", "Culo", "Dapdap", "Dela Paz", "Gatpuno", "Mabini", "Magsaysay", "Maloma", "Mansalay", "Mansalay", "Masinloc", "Nangalisan", "Owa", "Poblacion", "San Isidro", "San Juan", "San Pedro", "Santo Rosario"],
    "Castillejos": ["Bagong Sikat", "Bago", "Bamban", "Bantay", "Bebes", "Gumain", "Malaki", "Magsaysay", "Maligaya", "Poblacion", "San Antonio", "San Felipe", "San Isidro", "San Marcelino", "San Pedro", "Santa Rosa"],
    "Iba": ["Aguinaldo", "Balayong", "Bamban", "Batasan", "Bulaon", "Burakan", "Cabaritan", "Casilagan", "Cruz", "Del Pilar", "La Paz", "Magsaysay", "Poblacion", "San Antonio", "San Isidro", "Santa Rita"],
    "Masinloc": ["Bacala", "Bacala", "Balayong", "Bato", "Binoclutan", "Bunga", "Dona Cecilia", "Maloma", "Magsaysay", "Poblacion", "San Agustin", "San Andres", "San Marcelino"],
    "Olongapo City": ["Barretto", "East Tapinac", "New Ilalim", "New Cabalan", "Old Cabalan", "Palanan", "San Antonio", "San Isidro", "San Marcelino", "Wawandue"],
    "San Antonio": ["Bagong Silang", "Bani", "Bamban", "Banao", "Bayanan", "Bucal", "Dalayap", "Gapan", "Malaguin", "Poblacion", "San Jose", "San Vicente"],
    "San Felipe": ["Anoling", "Baba", "Baca", "Balayong", "Baro", "Bayanan", "Biclat", "Cayabu", "Gatpuno", "Magsaysay", "Maligaya", "Nangalisan", "Poblacion", "San Vicente", "Santa Rita"],
    "San Marcelino": ["Bagong Silang", "Balaybay", "Bucal", "Cabangaan", "Dalisdis", "Dalit", "Malusac", "Magsaysay", "Poblacion", "San Jose", "San Vicente"],
    "San Narciso": ["Bamban", "Bulaon", "Bulaw", "Caguiat", "Culis", "Magsaysay", "Malaguin", "Poblacion", "San Jose", "San Vicente"],
    "Santa Cruz": ["Bagong Sikat", "Bacala", "Bamban", "Baro", "Cangay", "Del Pilar", "Dela Paz", "San Jose", "San Vicente"],
    "Subic": ["Alibangbang", "Balayong", "Bamban", "Batan", "Cabatangan", "Cruz", "Dela Paz", "Magsaysay", "Poblacion", "San Antonio", "San Isidro", "San Marcelino", "San Vicente"],
    "Zambales": ["Bagumbayan", "Camarine", "Linao", "Mangato", "Manuel A. Roxas", "Maguindanao", "Malayo", "Narciso", "Olongapo City", "Pangasinan", "Poblacion", "San Antonio", "San Felipe"], 

    // pag bubuoin lahat for now central luzon lang meron
  };

  const cityMunicipalityData = {
    "Aurora": ["Baler", "Casiguran", "Dilasag", "Dinalungan", "Dingalan", "Dipaculao", "Maria Aurora", "San Luis"],
    "Bataan": ["Balanga", "Abucay", "Bagac", "Dinalupihan", "Hermosa", "Limay", "Mariveles", "Morong", "Orani", "Orion", "Pilar", "Samal"],
    "Bulacan": ["Angat", "Balagtas", "Baliuag", "Bocaue", "Bulakan", "Bustos", "Calumpit", "Doña Remedios Trinidad", "Guiguinto", "Hagonoy", "Malolos", "Marilao", "Meycauayan", "Norzagaray", "Obando", "Pandi", "Paombong", "Plaridel", "Pulilan", "San Ildefonso", "San Jose del Monte", "San Miguel", "San Rafael", "Santa Maria"],
   "Nueva Ecija": ["Aliaga", "Bongabon", "Cabiao", "Cabanatuan", "Carranglan", "Cuyapo", "Gapan", "Gabaldon", "General Mamerto Natividad", "General Tinio", "Guimba", "Jaen", "Laur", "Licab", "Llanera", "Lupao", "Muñoz", "Nampicuan", "Pantabangan", "Peñaranda", "Quezon", "Rizal", "San Antonio", "San Isidro", "San Jose", "San Leonardo", "Santa Rosa", "Santo Domingo", "Talavera", "Talugtug", "Zaragoza"],
   "Pampanga": ["Angeles City", "Apalit", "Arayat", "Bacolor", "Candaba", "Floridablanca", "Guagua", "Lubao", "Mabalacat", "Macabebe","Masantol", "Mexico", "Porac", "San Fernando", "San Luis", "San Simon", "Sasmuan"],
   "Tarlac": ["Anao", "Bamban", "Capas", "Concepcion", "La Paz", "Mayantoc", "Moncada", "Paniqui", "San Jose", "San Manuel", "San Rafael", "Santa Ignacia", "Tarlac City", "Victoria"],
   "Zambales": ["Botolan", "Castillejos", "Iba", "Masinloc", "Olongapo City", "San Antonio", "San Felipe", "San Marcelino", "San Narciso", "Santa Cruz", "Subic", "Zambales"],
   "Abra": ["Bangued", "Boliney", "Bucay", "Bucloc", "Daguioman", "Danglas", "Dolores", "La Paz", "Lacub", "Lagangilang", "Lagayan", "Langiden", "Licuan-Baay", "Luba", "Malibcong", "Manabo", "Penarrubia", "Pidigan", "Pilar", "Sallapadan", "San Isidro", "San Juan", "San Quintin", "Tayum", "Tineg", "Tubo", "Villaviciosa"],
    "Benguet": ["Atok", "Baguio City", "Bakun", "Bokod", "Buguias", "Itogon", "Kabayan", "Kapangan", "Kibungan", "La Trinidad", "Mankayan", "Sablan", "Tuba", "Tublay"],
    "Ifugao": ["Aguinaldo", "Alfonso Lista", "Asipulo", "Banaue", "Hingyon", "Hungduan", "Kiangan", "Lagawe", "Lamut", "Mayoyao", "Tinoc"],
    "Ilocos Norte": ["Adams", "Bacarra", "Badoc", "Bangui", "Banna", "Batac City", "Burgos", "Carasi", "Currimao", "Dingras", "Dumalneg", "Laoag City", "Marcos", "Nueva Era", "Pagudpud", "Paoay", "Pasuquin", "Piddig", "Pinili", "San Nicolas", "Sarrat", "Solsona", "Vintar"],
    "Ilocos Sur": ["Alilem", "Banayoyo", "Bantay", "Burgos", "Cabugao", "Candon City", "Caoayan", "Cervantes", "Galimuyod", "Gregorio del Pilar", "Lidlidda", "Magsingal", "Nagbukel", "Narvacan", "Quirino", "Salcedo", "San Emilio", "San Esteban", "San Ildefonso", "San Juan", "San Vicente", "Santa", "Santa Catalina", "Santa Cruz", "Santa Lucia", "Santa Maria", "Santiago", "Santo Domingo", "Sigay", "Sinait", "Sugpon", "Suyo", "Tagudin", "Vigan City"],
    "Kalinga": ["Balbalan", "Lubuagan", "Pasil", "Pinukpuk", "Rizal", "Tabuk City", "Tanudan", "Tinglayan"],
    "La Union": ["Agoo", "Aringay", "Bacnotan", "Bagulin", "Balaoan", "Bangar", "Bauang", "Burgos", "Caba", "Luna", "Naguilian", "Pugo", "Rosario", "San Fernando City", "San Gabriel", "San Juan", "Santo Tomas", "Santol", "Sudipen", "Tubao"],
    "Mountain Province": ["Barlig", "Bauko", "Besao", "Bontoc", "Natonin", "Paracelis", "Sabangan", "Sadanga", "Sagada", "Tadian"],
    "Quezon": ["Agdangan", "Alabat", "Atimonan", "Buenavista", "Burdeos", "Calauag", "Candelaria", "Catanauan", "Dolores", "General Luna", "General Nakar", "Guinayangan", "Gumaca", "Infanta", "Jomalig", "Lopez", "Lucban", "Lucena City", "Macalelon", "Mauban", "Mulanay", "Padre Burgos", "Pagbilao", "Panukulan", "Patnanungan", "Perez", "Pitogo", "Plaridel", "Polillo", "Quezon", "Real", "Sampaloc", "San Andres", "San Antonio", "San Francisco", "San Narciso", "Sariaya", "Tagkawayan", "Tayabas City", "Tiaong", "Unisan"],
    "Rizal": ["Angono", "Antipolo City", "Baras", "Binangonan", "Cainta", "Cardona", "Jalajala", "Morong", "Pililla", "Rodriguez", "San Mateo", "Tanay", "Taytay", "Teresa"],
    "Camarines Norte": ["Basud", "Capalonga", "Daet", "Jose Panganiban", "Labo", "Mercedes", "Paracale", "San Lorenzo Ruiz", "San Vicente", "Santa Elena", "Talisay", "Vinzons"],
    "Camarines Sur": ["Baao", "Balatan", "Bato", "Bombon", "Buhi", "Bula", "Cabusao", "Calabanga", "Camaligan", "Canaman", "Caramoan", "Del Gallego", "Gainza", "Garchitorena", "Goa", "Iriga City", "Lagonoy", "Libmanan", "Lupi", "Magarao", "Milaor", "Minalabac", "Nabua", "Naga City", "Ocampo", "Pamplona", "Pasacao", "Presentacion", "Ragay", "Sagnay", "San Fernando", "San Jose", "Sipocot", "Siruma", "Tigaon", "Tinambac"],
    "Catanduanes": ["Bagamanoc", "Baras", "Bato", "Caramoran", "Gigmoto", "Pandan", "Panganiban", "San Andres", "San Miguel", "Viga", "Virac"],
    "Sorsogon": ["Barcelona", "Bulan", "Bulusan", "Casiguran", "Castilla", "Donsol", "Gubat", "Irosin", "Juban", "Magallanes", "Matnog", "Pilar", "Prieto Diaz", "Santa Magdalena", "Sorsogon City"],
};

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

// Open the edit modal
function openEditModal(vendorID) {
    var editModal = document.getElementById("editModal");
    editModal.style.display = "block";

    // Fetch vendor data by ID and populate form fields (existing logic)
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                var vendor = JSON.parse(xhr.responseText);

                if (vendor.error) {
                    console.error(vendor.error);
                    alert('Vendor not found');
                    closeEditModal();
                    return;
                }

                // Populate the form fields with the vendor information (existing logic)
                document.getElementById('edit_vendor_id').value = vendor.vendorID;
                document.getElementById('edit_fName').value = vendor.fname;
                document.getElementById('edit_mname').value = vendor.mname;
                document.getElementById('edit_lName').value = vendor.lname;
                document.getElementById('edit_suffix').value = vendor.suffix;
                document.getElementById('edit_gender').value = vendor.gender;
                document.getElementById('edit_birthday').value = vendor.birthday;
                document.getElementById('edit_age').value = vendor.age;
                document.getElementById('edit_contactNo').value = vendor.contactNo;
                document.getElementById('edit_province').value = vendor.province;

                // Call updateCityMunicipality and updateBarangay to set the correct values
                updateCityMunicipality();
                document.getElementById('edit_municipality').value = vendor.municipality;
                updateBarangay();
                document.getElementById('edit_barangay').value = vendor.barangay;

                document.getElementById('edit_houseNo').value = vendor.houseNo;
                document.getElementById('edit_streetname').value = vendor.streetname;
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
</script>
</body>
</html>