<?php
// Database connection
include 'config.php';

// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

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
        body {
                margin: 0;
                font-family: 'Poppins', sans-serif;
                background-color: #F2F7FC;
                position: relative;
            }
            /* Sidebar */
            .side-menu {
                font-family: 'Poppins', sans-serif;
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
h2 {
    margin-right:65%;
}
        .user-icon {
    width: 40px; /* Set a fixed width for the icon */
    height: 40px; /* Set a fixed height for the icon */
    border-radius: 50%; /* Makes the icon circular */
    margin-left: -144%; /* Aligns the icon in the header */
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
    font-family: 'Poppins', sans-serif; /* Match overall theme */
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
    margin: auto;
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

/* Message Div Styling */

h4 {
    font-size: 24px; /* Set the font size */
    font-weight: bold; /* Make the text bold */
    margin-top: 0; /* Remove the default top margin */
    margin-bottom: 15px; /* Add space below the heading */
    color: #black; /* Set a dark text color */
    text-align: center; /* Center the text */
}

.success-message {
    display: none; /* Initially hidden */
    background-color: #d4edda; /* Light green background */
    color: #155724; /* Dark green text */
    padding: 15px; /* Some padding around the text */
    margin: 20px 0; /* Margin to separate from other elements */
    border: 1px solid #c3e6cb; /* Border to match the background */
    border-radius: 4px; /* Rounded corners */
    text-align: center; /* Center the text */
    font-weight: bold; /* Bold text for emphasis */
}

/* Modal Styling */
.restore-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0);
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

.restore-content {
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


/* Close Button */
.custom-close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    transition: color 0.2s ease;
}

.custom-close:hover {
    color: #ff4444;
}

/* Modal Header */
.restore-content h4 {
    margin: 0 0 20px 0;
    color: black;
    font-size: 1.5em;
    font-weight: 600;
}

/* Modal Paragraph */
.restore-content p {
    color: #666; /* Set the text color to a medium gray */
    margin-bottom: 25px; /* Add space below the paragraph */
    line-height: 1.5; /* Set line height for better readability */
    text-align: center; /* Center the text within the paragraph */
}


/* Action Buttons Container */
.restore-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

/* Button Styling */
.restore-actions button {
    padding: 10px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

#confirmRestoreCollectorBtn {
    background-color: #2A416F;; /* Green background for submit */
    color: white; /* White text */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* Remove default border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
}

#confirmRestoreCollectorBtn:hover {
   background-color:  #031F4E; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

#cancelRestoreCollectorBtn {
       background-color: transparent; /* No background color */
    border: 2px solid #2A416F; /* Blue border */
    color: #2A416F; /* Text color matching the border */
    padding: 8px 12px; /* Padding for buttons */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, color 0.3s; /* Smooth transition */
}

#cancelRestoreCollectorBtn:hover {
    background-color: #d32f2f; /* Darker red on hover */
    color:#ffff;
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

/* Responsive Design */
@media (max-width: 480px) {
    .restore-content {
        width: 95%;
        margin: 10vh auto;
        padding: 20px;
    }
    
    .restore-actions {
        flex-direction: column;
    }
    
    .restore-actions button {
        width: 100%;
        margin: 5px 0;
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
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php" class="active"><i class="fas fa-archive"></i> Archive</a>

      <!-- Log Out Link -->
    <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>


  <div class="header-panel">
    <div class="header-title"></div>
    <a href="admin_profile.php">
        <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
    </a>
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
                    <a href='#' class='custom-restore-btn' data-href='restore_collector.php?id=" . $row['collector_id'] . "'><i class='fas fa-undo'></i></a>
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

<div id="restoreCollectorModal" class="restore-modal">
    <div class="restore-content">
        <span class="custom-close">&times;</span>
        <h4>Confirm Action</h4>
        <p>Are you sure you want to restore this collector?</p>
        <div id="messageDiv" class="success-message" style="display: none;"></div> <!-- Success message container -->
        <div class="restore-actions">
            <button id="confirmRestoreCollectorBtn">Confirm</button>
            <button id="cancelRestoreCollectorBtn">Cancel</button>
        </div>
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
document.addEventListener('DOMContentLoaded', function () {
    const restoreButtons = document.querySelectorAll('.custom-restore-btn');
    const modal = document.getElementById('restoreCollectorModal');
    const confirmBtn = document.getElementById('confirmRestoreCollectorBtn');
    const cancelBtn = document.getElementById('cancelRestoreCollectorBtn');
    const closeBtn = document.querySelector('.custom-close');
    const messageDiv = document.getElementById('messageDiv');
    let currentHref = '';

    restoreButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default link behavior
            currentHref = this.getAttribute('data-href'); // Get the href from data attribute
            modal.style.display = 'block'; // Show the modal
        });
    });

    confirmBtn.addEventListener('click', function () {
        // Show success message
        messageDiv.className = 'success-message'; // Add success message class
        messageDiv.textContent = 'Collector restored successfully.';
        messageDiv.style.display = 'block'; // Show the message

        // Hide the buttons and other content
        confirmBtn.style.display = 'none';
        cancelBtn.style.display = 'none';

        // Redirect after showing the message for 2 seconds
        setTimeout(() => {
            window.location.href = 'collector.php'; // Redirect to collector.php
        }, 2000); // Wait for 2 seconds before redirecting
    });

    cancelBtn.addEventListener('click', function () {
        modal.style.display = 'none'; // Hide the modal
    });

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none'; // Hide the modal
    });

    // Close the modal when clicking outside of it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
});


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
