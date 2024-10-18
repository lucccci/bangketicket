<?php
// Database connection
include 'config.php';

// SQL query to get data from the archive_collectors table
$sql = "SELECT collector_id, fname AS first_name, mname AS middle_name, lname AS last_name, suffix, birthday FROM archive_collectors";
$result = $conn->query($sql);

// Check if the query executed successfully
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Pagination setup
$rowsPerPage = 6; // Define the number of records per page
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1

// Fetch total number of collectors for pagination calculation
$totalRowsResult = $conn->query("SELECT COUNT(*) as total FROM archive_collectors");
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
$archivedCollectorsResult = $conn->query("SELECT * FROM archive_collectors ORDER BY collector_id ASC LIMIT $startIndex, $rowsPerPage");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://kit.fontawesome.com/7d64038428.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="menuheaderDB.css">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="logo.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Collectors</title>
    <style>
       
      /* Sidebar */
.side-menu {
    font-family: 'poppins', sans-serif;
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


        .main {
            font-family: 'poppins', sans-serif;
            position: absolute;
            top: 60px;
            width: calc(100% - 300px);
            left: 260px;
            min-height: calc(100vh - 100px);
            background: #F2F7FC;
            padding: 20px;
        }

        /* Panel for the Collector Table */
        .panel {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .panel h2 {
            margin-top: 50;
            color: black;
        }

        .collector-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        }

        .collector-table thead tr {
            background-color: #031F4E;
            color: white;
            text-align: left;
            font-weight: bold;
        }

        .collector-table th, .collector-table td {
            padding: 12px 15px;
        }

        .collector-table tbody tr {
            background-color: #f9f9f9;
            border-bottom: 1px solid #dddddd;
        }

        .collector-table tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }

        .collector-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        /* Action buttons */
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
            color: #031F4E;
            display: inline-block;
        }

        .edit-btn i, .delete-btn i {
            margin-right: 5px;
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
        .heading-with-button {
    display: flex;
    justify-content: space-between; /* Align items: heading on the left, button on the right */
    align-items: center;
    margin-bottom: 20px;
}

.export-btn {
    display: inline-flex; /* Use flexbox for alignment */
    align-items: center; /* Center the icon and text vertically */
    background-color: #2A416F; /* Background color */
    color: #fff; /* Text color */
    border: 1px solid #031F4E; /* Border color */
    font-weight: bold; /* Bold text */
    cursor: pointer; /* Pointer cursor on hover */
    padding: 10px 15px; /* Padding around the button */
    border-radius: 5px; /* Rounded corners */
    text-decoration: none; /* Remove underline from the link */
    font-size: 14px; /* Font size */
    transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
}

.export-btn img {
    width: 20px; /* Set a consistent width for the icon */
    height: 20px; /* Set a consistent height for the icon */
    margin-left: 8px; /* Space between text and icon */
}

.export-btn:hover {
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
.back-button {
   
    color: black; /* White text */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* No border */
    border-radius: 5px; /* Rounded corners */
    text-decoration: none; /* Remove underline */
    cursor: pointer; /* Pointer cursor */
    transition: background-color 0.3s ease; /* Smooth transition */
    display: flex; /* Use flexbox for alignment */
    align-items: center; /* Center items vertically */
}

.back-button i {
    margin-right: 8px; /* Space between icon and text */
}

.back-button:hover {
    background-color: #6B8CAE; /* Darker grey on hover */
}


    </style>
</head>
<body>

<div class="header-panel">
        <a href="admin_profile.php">
            <img src="pics/icons8-test-account-100.png" alt="Profile Icon" class="profile-icon">
        </a>
    </div>

<div id="sideMenu" class="side-menu">
    <div class="logo">
        <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.html">
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
            <a href="vendorlist.php" id="vendorListLink" class="active"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>

    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php" class="active"><i class="fas fa-archive"></i> Archive</a>

      <!-- Log Out Link -->
      <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>


<div class="main">
    <div class="panel">
        <div class="heading-with-button">
        <a href="archive.php" class="back-button">
                <i class="fas fa-arrow-left"></i> <!-- Back arrow icon -->
                
            </a> 
            <h2>Archive Collectors</h2>
            <!-- Export to CSV button aligned to the right -->
            <a href="export_collectors_csv.php" class="export-btn">
                Export
                <img src="pics/icons8-export-csv-80.png" alt="Export CSV Icon" style="width: 20px; height: 20px; margin-left: 8px;">
            </a>
        </div>




    <table class="collector-table">
    <thead>
        <tr>
            <th>Collector ID</th>
            <th>First Name</th>
            <th>Middle Name</th>
            <th>Last Name</th>
            <th>Suffix</th>
            <th>Birthday</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            // Output data of each row
            while ($row = $result->fetch_assoc()) {
                // Check for empty fields and display 'N/A' if necessary
                $firstName = !empty($row['first_name']) ? $row['first_name'] : 'N/A';
                $middleName = !empty($row['middle_name']) ? $row['middle_name'] : 'N/A';
                $lastName = !empty($row['last_name']) ? $row['last_name'] : 'N/A';
                $suffix = !empty($row['suffix']) ? $row['suffix'] : 'N/A';
                $birthday = !empty($row['birthday']) ? $row['birthday'] : 'N/A';

                echo "<tr>
                     <td><strong>" . $row['collector_id'] . "</strong></td>
                        <td>" . $firstName . "</td>
                        <td>" . $middleName . "</td>
                        <td>" . $lastName . "</td>
                        <td>" . $suffix . "</td>
                        <td>" . $birthday . "</td>
                        <td>
                            <a href='restore_collector.php?id=" . $row['collector_id'] . "' class='restore-btn' onclick='return confirm(\"Are you sure you want to restore this collector?\")'><i class='fas fa-undo'></i></a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No records found</td></tr>";
        }
        ?>
    </tbody>
</table>

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

<?php
// Close connection
$conn->close();
?>

</body>
</html>
