<?php
// Database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

// Handle filtering by vendor ID, vendor name, or collector ID
$filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : '';
$filter_value = isset($_POST['filter_value']) ? $_POST['filter_value'] : '';

// Pagination setup
$rowsPerPage = 8; // Number of records per page (Set to 8)
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1
$startIndex = ($currentPage - 1) * $rowsPerPage;
$startIndex = max(0, $startIndex); // Ensure start index is not negative

// Prepare the base query without LIMIT and ORDER BY first
$sql = "SELECT vt.transactionID, vt.vendorID, 
               CONCAT(vl.fname, ' ', vl.mname, ' ', vl.lname) AS vendor_name, 
               vt.date, vt.amount, vt.collector_id 
        FROM vendor_transaction vt
        JOIN vendor_list vl ON vt.vendorID = vl.vendorID";

// Apply filter based on the selected filter type
if (!empty($filter_type) && !empty($filter_value)) {
    if ($filter_type == 'vendor_id') {
        $sql .= " WHERE vt.vendorID LIKE '%$filter_value%'";
    } else if ($filter_type == 'vendor_name') {
        $sql .= " WHERE (vl.fname LIKE '%$filter_value%' OR vl.mname LIKE '%$filter_value%' OR vl.lname LIKE '%$filter_value%')";
    } else if ($filter_type == 'collector_id') {
        $sql .= " WHERE vt.collector_id LIKE '%$filter_value%'";
    } else if ($filter_type == 'date') {
        // Convert the user-friendly date to 'Y-m-d' format for the SQL query
        $formattedDate = date('Y-m-d', strtotime($filter_value));
        $sql .= " WHERE DATE(vt.date) = '$formattedDate'";
    }
}

$sql .= " ORDER BY vt.transactionID ASC LIMIT $startIndex, $rowsPerPage";
$result = $conn->query($sql);

// Fetch total number of transactions for pagination calculation
$totalRowsResult = $conn->query("SELECT COUNT(*) as total 
                                FROM vendor_transaction vt
                                JOIN vendor_list vl ON vt.vendorID = vl.vendorID");
$totalRows = $totalRowsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $rowsPerPage);

// Fetch all collectors for report generation dropdown
$collectorQuery = "SELECT DISTINCT c.collector_id, c.fname, c.lname 
                   FROM vendor_transaction vt
                   JOIN collectors c ON vt.collector_id = c.collector_id";
$collectorResult = $conn->query($collectorQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://kit.fontawesome.com/7d64038428.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">    
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="menuheaderDB.css">
    <link rel="stylesheet" href="logo.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection</title>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
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

        .main {
            font-family: 'Poppins', sans-serif;
            position: absolute;
            top: 50px;
            width: 80%;
            left: 260px;
            min-height: 86%;
            background: #F2F7FC;
            padding: 20px;
                overflow-x: hidden;
             overflow-y: hidden;
        }
        .panel {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
              overflow-x: hidden;
             overflow-y: hidden;
            
            width: auto;
        }
        .collector-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 1em;
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
            background-color: #fff;
            border-bottom: 1px solid #dddddd;
        }
        .collector-table tbody tr:nth-of-type(even) {
            background-color: #FFFF;
        }
        .collector-table tbody tr:hover {
            background-color: #f9f9f9;
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
            background-color: #c0392b; /* Hover effect for Log Out link */
            color: #fff; /* Change text color on hover */
        }

        .filter-form {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .filter-form select, .filter-form input, .filter-form button {
            padding: 8px;
            font-size: 14px;
            height: 40px;
            box-sizing: border-box;
            margin-right: 10px;
        }

        .filter-form select, .filter-form input {
            width: 150px;
            border-radius: 5px;
            border: 1px solid;
        }

        .filter-form button {
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
            width: 40px;
            transform: translateY(0px);
        }

        .filter-form button:hover {
            background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
        }

        .filter-form button i {
            font-size: 15px;
        }

        .filter-form .export-button {
            font-weight: bold;
            background-color: #2A416F;
            font-size: 13px;
            color: #fff;
            border: 1px solid #031F4E;
            cursor: pointer;
            border-radius: 4px;
            height: 40px;
            width: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        /* Dropdown styles */
        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            right: 0;
        }

        .dropdown-menu button {
            width: 100%;
            padding: 10px;
            border: none;
            background-color: #031F4E;
            color: white;
            text-align: left;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .dropdown-menu button:hover {
            background-color: #2A416F;
        }

         /* Report Dropdown Menu */
       .report-menu {
            display: none;
            position: fixed;
            background-color: #fff;
            border: 1px solid #031F4E;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 0;
            min-width: 160px;
            top: 60px; /* Position from the top of the screen */
            left: calc(280px + 20px); /* Position to the right of the sidebar */
        }

        .report-menu button {
            width: 100%;
            padding: 8px 15px;
            background-color: transparent;
            color: #031F4E;
            text-align: left;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            transition: background 0.3s;
        }

        .report-menu button:hover {
            background-color: #2A416F;
            color: #fff;
        }

        .report-menu button:last-child {
            border-bottom: none;
        }

        /* Responsive */
        @media (max-width: 1115px) {
            .side-menu {
                width: 60px;
            }

            .report-dropdown-menu {
                left: 80px; /* Adjusted for small sidebar */
            }
        }

.collector-dropdown-menu {
    margin-top:13.3%;
    margin-left:60%;
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #031F4E;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 0;
            min-width: 200px;
        }

        .collector-dropdown-menu button {
            width: 100%;
            padding: 10px 15px;
            background-color: transparent;
            color: #031F4E;
            text-align: left;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            transition: background 0.3s;
        }

        .collector-dropdown-menu button:hover {
            background-color: #2A416F;
            color: #fff;
        }

        .collector-dropdown-menu button:last-child {
            border-bottom: none;
        }

                /* Style the filter form buttons */
.filter-form .search-button {
    background-color: #2A416F; /* Dark blue background */
    color: white; /* White icon color */
    border: none; /* Remove border */
    padding: 10px; /* Add padding for spacing */
    border-radius: 5px; /* Rounded corners */
    cursor: pointer; /* Show pointer cursor on hover */
    transition: background-color 0.3s ease; /* Smooth transition for hover effect */
}

.filter-form .search-button i {
    font-size: 16px; /* Icon size */
}

.filter-form .search-button:hover {
    background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
}

.filter-form .search-button:focus {
    outline: none; /* Remove focus outline */
}

.filter-form .search-button:active {
    background-color: #16283f; /* Even darker color when clicked */
}

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
              height: 40px; /* Set a fixed height for the header */
              z-index: 1001; /* Stays above the sidebar */
}

.profile-icon {
    width: 40px; /* Set the width of the icon */
    height: 40px; /* Set the height of the icon */
    cursor: pointer; /* Change cursor to pointer on hover */
    margin-left: 1170px; /* Space between the icon and the edge */
}

.profile-icon:hover {
    opacity: 0.8; /* Change opacity on hover for a slight effect */
}

        .user-icon {
    width: 40px; /* Set a fixed width for the icon */
    height: 40px; /* Set a fixed height for the icon */
    border-radius: 50%; /* Makes the icon circular */
    margin-left: -145%; /* Aligns the icon in the header */
    transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for the hover effect */
}

.user-icon:hover {
    transform: scale(1.1); /* Slightly increase the size of the icon on hover */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); /* Adds a shadow effect on hover */
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

<!-- Side Menu -->
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
            <a href="vendorlist.php" id="vendorListLink" class="active"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>

    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php" class="active"><i class="fa fa-table"></i> Collection</a>
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

<!-- Collector Table -->
<div class="main">
    <div class="panel">
        <h2>Collection of Transactions</h2>

        <!-- Filter Form -->
        <div class="filter-form">
            <form method="POST" action="">
                <select name="filter_type">
                    <option value="">Select Filter</option>
                    <option value="vendor_id" <?php echo ($filter_type === 'vendor_id') ? 'selected' : ''; ?>>Vendor ID</option>
                    <option value="vendor_name" <?php echo ($filter_type === 'vendor_name') ? 'selected' : ''; ?>>Vendor Name</option>
                    <option value="collector_id" <?php echo ($filter_type === 'collector_id') ? 'selected' : ''; ?>>Collector ID</option>
                    <option value="date" <?php echo ($filter_type === 'date') ? 'selected' : ''; ?>>Date</option>
                </select>
                </select>
                <input type="text" name="filter_value" placeholder="Enter value" value="<?php echo htmlspecialchars($filter_value); ?>">
                <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                <button type="button" class="export-button" onclick="toggleReportDropdown()">
  Generate Report &nbsp;
  <img src="pics/icons8-analyze-40.png" alt="Analyze Icon" style="vertical-align: middle; width: 20px; height: 20px;">
</button>
    
            </form>
        </div>

        <table class="collector-table">
            <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Vendor ID</th>
                <th>Vendor Name</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Collector ID</th>
            </tr>
            </thead>
            <tbody>
            <?php
          if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['transactionID'] . "</td>";
                    echo "<td><strong>" . $row['vendorID'] . "</strong></td>"; // Bold the Vendor ID
                    echo "<td>" . $row['vendor_name'] . "</td>";
                    echo "<td>" . $row['date'] . "</td>";
                    echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                     // Change the color and bold the Collector ID
        echo "<td><span style='color: #2A416F; font-weight: bold;text-decoration: underline;'>" . $row['collector_id'] . "</span></td>"; 
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No transactions found</td></tr>";
            }
            ?>
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

<!-- Report Dropdown Menu -->
<div id="report-dropdown" class="report-menu">
    <button onclick="toggleCollectorDropdown()">By Collector</button>
    <button onclick="location.href='generate_report_collection.php?report_type=by_vendor'">By Vendor</button>
    <button onclick="location.href='generate_report_collection.php?report_type=transaction_summary'">Transactions Summary</button>
    <button onclick="toggleReportDropdown()">Cancel</button>
</div>

<!-- Collector Dropdown Menu -->
<div id="collector-dropdown" class="collector-dropdown-menu">
    <?php
    if ($collectorResult->num_rows > 0) {
        while ($collectorRow = $collectorResult->fetch_assoc()) {
            $collectorName = "{$collectorRow['fname']} {$collectorRow['lname']}";
            echo "<button onclick=\"location.href='generate_report_collection.php?report_type=by_collector&collector_id={$collectorRow['collector_id']}'\">$collectorName</button>";
        }
    } else {
        echo "<button disabled>No collectors available</button>";
    }
    ?>
    <button onclick="location.href='generate_report_collection.php?report_type=all_collectors'">All Collectors</button>
    <button onclick="toggleCollectorDropdown()">Cancel</button>
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
      function toggleReportDropdown() {
        var dropdown = document.getElementById("report-dropdown");
        var exportButton = document.querySelector(".export-button");

        var buttonRect = exportButton.getBoundingClientRect();

        dropdown.style.position = "absolute";
        dropdown.style.left = (buttonRect.right + 10) + "px";
        dropdown.style.top = buttonRect.top + "px";

        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }

    function toggleCollectorDropdown() {
        var dropdown = document.getElementById("collector-dropdown");
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
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

<?php
// Close the database connection
$conn->close();
?>
