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

// SQL query to get data from the archive_collectors table
$sql = "SELECT collector_id, fname AS first_name, mname AS middle_name, lname AS last_name, suffix, birthday FROM archive_collectors";
$result = $conn->query($sql);

// Check if the query executed successfully
if (!$result) {
    die("Query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://kit.fontawesome.com/7d64038428.js" crossorigin="anonymous"></script>
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
            margin-top: 0;
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
    padding: 10px 20px;
    background-color: #031F4E;
    color: white;
    border-radius: 5px;
    text-decoration: none;
    font-size: 14px;
}

.export-btn i {
    margin-right: 5px;
}

.export-btn:hover {
    background-color: #2A416F; /* Change color on hover */
    color: #fff;
}

    </style>
</head>
<body>

<div class="header-panel">
    <div class="notification"><i class="fas fa-bell"></i></div>
    <div class="profile-container">
      <img src="pics/admin.jfif" alt="Profile Picture" class="profile-picture">
    </div>
</div>

<div id="sideMenu" class="side-menu">
    <div class="logo">
        <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="product.php"><i class="fas fa-box"></i> Product</a>

    <div class="dropdown">
        <a href="vendorlist.php" id="vendorDropdown" class="dropdown-toggle"><i class="fas fa-users"></i> Vendors</a>
        <div id="vendorDropdownContent" class="dropdown-content" style="display: none;">
            <a href="vendorlist.php" id="vendorListLink" class="active"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>

    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="#"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php" class="active"><i class="fas fa-archive"></i> Archive</a>

      <!-- Log Out Link -->
      <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

<div class="main">
<div class="panel">
    <div class="heading-with-button">
        <h2>Archive Collectors</h2>
        <!-- Export to CSV button aligned to the right -->
        <a href="export_collectors_csv.php" class="export-btn">
            <i class="fas fa-file-csv"></i> Export
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
                    echo "<tr>
                            <td>" . $row['collector_id'] . "</td>
                            <td>" . $row['first_name'] . "</td>
                            <td>" . $row['middle_name'] . "</td>
                            <td>" . $row['last_name'] . "</td>
                            <td>" . $row['suffix'] . "</td>
                            <td>" . $row['birthday'] . "</td>
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
    </div>
</div>

<?php
// Close connection
$conn->close();
?>

</body>
</html>
