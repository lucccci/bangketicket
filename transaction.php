<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="menuheaderDB.css">
    <link rel="stylesheet" href="logo.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction List</title>
    <style>
        body {
            background-color: #F2F7FC;
            font-family: 'Open Sans', sans-serif;
        }

        /* Sidebar */
  
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
  overflow-y: hidden;
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


.logout {
          color: #e74c3c; /* Log Out link color */
          padding: 15px 20px; /* Padding for Log Out link */
          margin-top: 115px; /* Add space above Log Out link */
          display: flex; /* Ensure the icon and text align properly */
          align-items: center; /* Center align the icon and text vertically */
          transition: background 0.3s, color 0.3s; /* Transition effects */
      }

      .logout:hover {
  background-color: #c0392b;
  color: #fff;
  transform: translateX(10px); /* Slide effect on hover for logout */
}



       /* Report Dropdown Menu */
       .report-dropdown-menu {
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

        .report-dropdown-menu button {
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

        .report-dropdown-menu button:hover {
            background-color: #2A416F;
            color: #fff;
        }

        .report-dropdown-menu button:last-child {
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

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Transaction List Styles */
        .container {
            margin-top: 70px;
            margin-left: 260px;
            padding: 20px;
            max-width: 78%;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: 2px;
        }

        th, td {
            padding: 10px;
            font-size: 14px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            padding: 15px;
            background-color: #031F4E;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
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
            position: relative;
            font-size: 13px;
            color: #ffff;
            border: 1px solid #031F4E;
            cursor: pointer;
            border-radius: 4px;
            height: 40px;
            width: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

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

        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
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

        .total-amount {
            margin: 20px 0;
            padding: 10px;
            font-size: 16px;
            font-weight: bold;
            background-color: #f0f0f0;
            border-radius: 5px;
        }

        .collector-dropdown-menu {
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

/* Style the export button */
.filter-form .export-button {
    background-color: #2A416F; /* Dark blue background */
    color: white; /* White text and icon */
    border: none;
    padding
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
  margin-right: 10px; 
}

.profile-icon:hover {
  opacity: 0.8; /* Change opacity on hover for a slight effect */
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
        <a href="javascript:void(0);" class="active hovered"><i class="fas fa-users"></i> Vendors</a>
        <div class="dropdown-content" style="display: block;">
            <a href="vendorlist.php"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php" class="active hovered"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>
    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>

     <!-- Log Out Link -->
     <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>



<?php
// Database configuration
include 'config.php';

// Handle filtering by vendor ID or vendor name
$filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : '';
$filter_value = isset($_POST['filter_value']) ? $_POST['filter_value'] : '';

// Prepare the base query without LIMIT and ORDER BY first
$sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount, t.collector_id
        FROM vendor_transaction t
        JOIN vendor_list v ON t.vendorID = v.vendorID
        WHERE DATE(t.date) = CURDATE()"; // Only show today's transactions

// Apply filter based on the selected filter type (vendorID, vendor name, or collector_id)
if (!empty($filter_type) && !empty($filter_value)) {
    if ($filter_type == 'vendor_id') {
        $sql .= " AND t.vendorID LIKE '%$filter_value%'";
    } else if ($filter_type == 'vendor_name') {
        $sql .= " AND (v.lname LIKE '%$filter_value%' OR v.fname LIKE '%$filter_value%' OR v.mname LIKE '%$filter_value%')";
    } else if ($filter_type == 'collector_id') {
        $sql .= " AND t.collector_id LIKE '%$filter_value%'";
    }
}

// Fetch total rows before applying ORDER BY and LIMIT for pagination
$totalRowsResult = $conn->query($sql);
$totalRows = $totalRowsResult->num_rows;

// Fetch collectors who have transactions today
$collectorQuery = "SELECT DISTINCT c.collector_id, c.fname, c.lname 
                   FROM vendor_transaction t
                   JOIN collectors c ON t.collector_id = c.collector_id
                   WHERE DATE(t.date) = CURDATE()"; // Only select collectors with transactions today
$collectorResult = $conn->query($collectorQuery);

if (!$collectorResult) {
    die("Error fetching collectors: " . $conn->error);
}

// Set a default value for rows per page
$rowsPerPage = 6; // Number of records to show per page

// Ensure currentPage is always defined and valid
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Make sure currentPage is at least 1 (no negative or zero pages)
if ($currentPage < 1) {
    $currentPage = 1;
}

// Pagination logic
$totalPages = ceil($totalRows / $rowsPerPage);

// Ensure currentPage does not exceed totalPages
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

// Calculate the start index for the records
$startIndex = ($currentPage - 1) * $rowsPerPage;

// Ensure $startIndex is never negative
if ($startIndex < 0) {
    $startIndex = 0;
}

// Now apply ORDER BY and LIMIT for pagination
$sql .= " ORDER BY t.date ASC LIMIT $startIndex, $rowsPerPage"; // Sort by date and limit records
$result = $conn->query($sql); // Execute the updated query

// Handle SQL query error
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Calculate total amount for today's transactions
$totalAmountResult = $conn->query("SELECT SUM(amount) as totalAmount FROM vendor_transaction WHERE DATE(date) = CURDATE()");
$totalAmountRow = $totalAmountResult->fetch_assoc();
$totalAmount = $totalAmountRow['totalAmount'] ? $totalAmountRow['totalAmount'] : 0; // Ensure a value is set
?>
<div class="container">
    <h2>Transaction List</h2>

    <!-- Filter Form -->
    <div class="filter-form">
        <form method="POST" action="">
            <select name="filter_type">
                <option value="">Select Filter</option>
                <option value="vendor_id" <?php echo ($filter_type === 'vendor_id') ? 'selected' : ''; ?>>Vendor ID</option>
                <option value="vendor_name" <?php echo ($filter_type === 'vendor_name') ? 'selected' : ''; ?>>Vendor Name</option>
                <option value="collector_id" <?php echo ($filter_type === 'collector_id') ? 'selected' : ''; ?>>Collector ID</option>
            </select>
            <input type="text" name="filter_value" placeholder="Enter value" value="<?php echo htmlspecialchars($filter_value); ?>">
            <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
            <button type="button" class="export-button" onclick="toggleReportDropdown()">
  Generate Report &nbsp;
  <img src="pics/icons8-analyze-40.png" alt="Analyze Icon" style="vertical-align: middle; width: 20px; height: 20px;">
</button>

        </form>
    </div>

    <!-- Transactions Table -->
    <table>
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
                while ($row = $result->fetch_assoc()) {
                    $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
                    echo "<tr>
                            <td><strong>{$row['transactionID']}</strong></td>
                            <td>{$row['vendorID']}</td>
                            <td>{$vendorFullName}</td>
                            <td>{$row['date']}</td>
                            <td>₱" . number_format($row['amount'], 2) . "</td>
                            <td>{$row['collector_id']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align: center;'>No transactions found for today.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <hr>

    <!-- Total Amount Row -->
    <div class="total-amount">
        Total Amount Collected Today: ₱<?php echo number_format($totalAmount, 2); ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
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
    </div>
</div>

<!-- Report Dropdown Menu -->
<div id="report-dropdown" class="report-dropdown-menu">
    <button onclick="location.href='generate_report_transaction.php?report_type=by_date'">By Date</button>
    <button onclick="toggleCollectorDropdown()">By Collector</button>
    <button onclick="location.href='generate_report_transaction.php?report_type=summary_per_day'">Summary Per Day</button>
    <button onclick="toggleReportDropdown()">Cancel</button>
</div>

<!-- Collector Dropdown Menu -->
<div id="collector-dropdown" class="collector-dropdown-menu">
    <?php
    if ($collectorResult->num_rows > 0) {
        while ($collectorRow = $collectorResult->fetch_assoc()) {
            $collectorName = "{$collectorRow['fname']} {$collectorRow['lname']}";
            echo "<button onclick=\"location.href='generate_report_transaction.php?report_type=by_collector&collector_id={$collectorRow['collector_id']}'\">$collectorName</button>";
        }
    } else {
        echo "<button disabled>No collectors available</button>";
    }
    ?>
    <button onclick="toggleCollectorDropdown()">Cancel</button>
</div>
    
<script>
    function toggleDropdown() {
        var profileContainer = document.querySelector(".profile-container");
        var dropdownMenu = document.querySelector(".dropdown-menu");
        var overlay = document.querySelector(".overlay");

        profileContainer.classList.toggle("clicked");
        dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        overlay.style.display = overlay.style.display === "block" ? "none" : "block";
    }

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
        var collectorDropdown = document.getElementById("collector-dropdown");
        var exportButton = document.querySelector(".export-button");

        var buttonRect = exportButton.getBoundingClientRect();
        collectorDropdown.style.position = "absolute";
        collectorDropdown.style.left = (buttonRect.left + 240) + "px";
        collectorDropdown.style.top = (buttonRect.bottom + 5) + "px";
        collectorDropdown.style.display = (collectorDropdown.style.display === "block") ? "none" : "block";
    }
</script>

</body>
</html>
