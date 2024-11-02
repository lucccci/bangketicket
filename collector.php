<?php
// collector.php
include 'config.php';

// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

// Pagination setup
$rowsPerPage = 6; // Define the number of records per page
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1

// Fetch total number of collectors for pagination calculation
$totalRowsResult = $conn->query("SELECT COUNT(*) as total FROM collectors WHERE archived_at IS NULL");
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
$collectorsResult = $conn->query("SELECT * FROM collectors WHERE archived_at IS NULL ORDER BY collector_id ASC LIMIT $startIndex, $rowsPerPage");

// Initialize a variable to hold the success message
$successMessage = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data from $_POST
    $firstName = $_POST['firstName'];
    $midName = $_POST['MidName'];
    $lastName = $_POST['lastName'];
    $suffix = $_POST['suffix'];
    $birthday = $_POST['birthday'];
    $email = $_POST['email'];

    // Fetch the last collector_id from the database
    $result = $conn->query("SELECT collector_id FROM collectors ORDER BY collector_id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        // Get the last inserted ID and increment it
        $row = $result->fetch_assoc();
        $lastId = $row['collector_id'];

        // Extract numeric part and increment it
        $num = (int)substr($lastId, 4); // Assuming the format is BTC-###
        $num++;
        $newId = 'BTC-' . str_pad($num, 3, '0', STR_PAD_LEFT); // New ID in format BTC-###
    } else {
        // No records yet, start with BTC-001
        $newId = 'BTC-001';
    }

    // Prepare SQL query to insert data into the database
    $sql = "INSERT INTO collectors (collector_id, fname, mname, lname, suffix, birthday, email)
            VALUES ('$newId', '$firstName', '$midName', '$lastName', '$suffix', '$birthday','$email')";

    // Execute query and check for success
    if ($conn->query($sql) === TRUE) {
        // Generate username and password
        $generatedUsername = strtolower(preg_replace('/\s+/', '', $firstName)); 
        $formattedBirthday = date('Ymd', strtotime($birthday)); 
        $generatedPasswordPlain = strtolower($lastName) . $formattedBirthday;

        // Update the database with generated credentials
        $stmt_update = $conn->prepare("UPDATE collectors SET username = ?, password = ? WHERE collector_id = ?");
        if (!$stmt_update) {
            echo "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            exit();
        }
        $stmt_update->bind_param("sss", $generatedUsername, $generatedPasswordPlain, $newId);

        if ($stmt_update->execute()) {
            // Set the success message
            $successMessage = "New collector registered successfully with ID: $newId";
        } else {
            echo "Error updating credentials: " . $stmt_update->error;
        }
        // Close the update statement
        $stmt_update->close();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch only non-archived collectors (archived_at IS NULL) from the database for display
$collectorsResult = $conn->query("SELECT * FROM collectors WHERE archived_at IS NULL ORDER BY collector_id ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://kit.fontawesome.com/7d64038428.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="logo.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collectors</title>
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
            font-family: 'poppins', sans-serif;
            position: absolute;
            top: 60px;
            width: 80%;
            left: 260px;
            min-height: 86%;
            background: #F2F7FC;
            padding: 20px;
        }

        .panel {
            background-color: #ffffff;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: auto;
        }

        .panel h2 {
            margin-top: 30px;
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
            background-color: #fff;
            border-bottom: 1px solid #dddddd;
        }

        .collector-table tbody tr:nth-of-type(even) {
            background-color: #fff;
        }

        .collector-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .edit-btn {
            background-color: #ffff;
            border: none;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
            color: #031F4E;
            display: inline-block;
        }
        .edit-btn:hover, .delete-btn:hover {
            background-color: #2A416F;
            color: #fff;
}
        .title-button-container {
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
}

#addCollectorBtn {
    margin-top:-1%;
    color: #031F4E;
    border: 1px solid #031F4E;
    padding: 10px 13px;
    text-decoration: none;
    border-radius: 5px;
}

#addCollectorBtn:hover {
    background-color: #2A416F;
    color: white;
    
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
        
   /* Modal styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    display: flex; /* Use flexbox for centering */
    justify-content: center; /* Center horizontally */
    align-items: center; /* Center vertically */
    overflow-y: auto; /* Allow scrolling if content exceeds height */
}

.modal-content {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    width: 90%; /* Responsive width */
    max-width: 500px; /* Max width for larger screens */
    max-height: 80vh; /* Limit the height to 80% of the viewport height */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
    animation: fadeIn 0.3s ease-in-out; /* Add fade-in animation */
    overflow-y: auto; /* Enable internal scrolling within the modal */
}

.modal-content form {
    display: flex;
    flex-direction: column;
}

.modal-content label {
    font-weight: bold;
    margin-bottom: 5px;
}

.modal-content input[type="text"],
.modal-content input[type="date"],
.modal-content select {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    font-size: 16px;
    background-color: #fff;
    box-sizing: border-box;
}

.modal-content input[type="text"]:focus,
.modal-content input[type="date"]:focus,
.modal-content select:focus {
    border-color: #031F4E;
    outline: none;
    box-shadow: 0 0 5px rgba(3, 31, 78, 0.5);
}

.modal-content input[type="submit"] {
    background-color: #031F4E;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}

.modal-content input[type="submit"]:hover {
    background-color: #2A416F;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: transparent;
    border: none;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
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


@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(10px);
    }
}


/* Close button styles */
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Form input and select styles inside the modal */
.modal-content form {
    display: flex;
    flex-direction: column;
}

.modal-content label {
    font-weight: bold;
    margin-bottom: 5px;
}

.modal-content input[type="text"],
.modal-content input[type="date"],
.modal-content select {
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    box-sizing: border-box;
    font-size: 16px;
    background-color: #fff;
}

/* Focus states for all form elements */
.modal-content select:focus,
.modal-content input[type="text"]:focus,
.modal-content input[type="date"]:focus {
    border-color: #031F4E;
    outline: none;
    box-shadow: 0 0 5px rgba(3, 31, 78, 0.5);
}

/* Submit button styles */
.modal-content input[type="submit"] {
    background-color: #031F4E;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

/* Hover state for submit button */
.modal-content input[type="submit"]:hover {
    background-color: #2A416F;
}


.collector-table th:nth-child(1),
.collector-table td:nth-child(1) {
    font-weight: bold;
}.title-button-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.add-collector-button {
    background-color: #2A416F; /* Adjust color as needed */
    font-weight: bold;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: background-color 0.3s ease;
}

.add-collector-button:hover {
  background-color: #6A85BB; /* Change background on hover */
    color: #fff; /* Ensure text color remains white */
    transform: scale(1.05); /* Slightly enlarge the button on hover */
}

.add-collector-button:focus {
    outline: none;
}

.add-collector-button:active {
    background-color: #16283f;
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



        /* Success Modal */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: hidden; /* Prevent scrolling inside the modal */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto; /* Center the modal */
            padding: 20px;
            border: 1px solid #888;
            width: 50%; /* Set width of modal */
            max-width: 600px; /* Ensure it doesn’t exceed screen size */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
            position: fixed; /* Keeps the modal in the center without scrolling */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Center modal */
        }

        /* Close button styles */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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


/*modal for delete */
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
.delete-confirmation h4 {
    font-size: 20px; /* Adjust the font size */
    font-weight: bold; /* Make the text bold */
    margin-top: 0; /* Remove the default top margin */
    margin-bottom: 15px; /* Space below the heading */
    color: #333; /* Dark text color */
    text-align: center; /* Center the text */
}

button[type="submit"] {
    background-color: #2A416F; /* Green background for submit */
    color: white; /* White text */
    padding: 10px 15px; /* Padding for the button */
    border: none; /* Remove default border */
    border-radius: 4px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s, transform 0.3s; /* Smooth transition */
}

button[type="submit"]:hover {
    background-color: #6A85BB; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

button[type="submit"]:active {
    transform: scale(0.95); /* Slightly shrink on click */
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
    background-color: #d32f2f; /* Darker red on hover */
    color: #fff;
    transform: scale(1.05); /* Slightly enlarge on hover */
}

.close {
    color: #aaa;
    font-size: 24px;
    float: right;
    cursor: pointer;
    margin-top: -1%;
}

.close:hover {
    color: #000; /* Change color on hover */
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

    <a href="collector.php" class="active"><i class="fa fa-user-circle"></i> Collector</a>
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

<div class="main">
    <div class="panel">
        <div class="title-button-container">
            <h2>Collector Table</h2>
            <button class="add-collector-button" onclick="location.href='collector.form.php'">
                <span class="material-icons" style="margin-left: 2px;">add</span>
                Add Collector

            </button>
        </div>

        <!-- Start of collector table -->
        <table class="collector-table">
            <thead>
            <tr>
                <th>Collector ID</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Suffix</th>
                <th>Birthday</th>
                <th>Age</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
           <tbody>
<?php
// Use $collectorsResult for fetching and displaying collectors
if ($collectorsResult && $collectorsResult->num_rows > 0) {  
    while ($row = $collectorsResult->fetch_assoc()) {
        // Use 'N/A' if fields are empty, only for relevant fields
        $firstName = !empty($row['fname']) ? $row['fname'] : 'N/A';
        $midName = !empty($row['mname']) ? $row['mname'] : 'N/A';
        $lastName = !empty($row['lname']) ? $row['lname'] : 'N/A';
        $suffix = !empty($row['suffix']) ? $row['suffix'] : 'N/A';
        $birthday = !empty($row['birthday']) ? $row['birthday'] : 'N/A';

        // Calculate age if birthday is available
        $age = 'N/A';
        if (!empty($row['birthday'])) {
            $birthDate = new DateTime($row['birthday']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
        }

        echo "<tr>
            <td>" . $row['collector_id'] . "</td>
            <td>" . $firstName . "</td>
            <td>" . $midName . "</td>
            <td>" . $lastName . "</td>
            <td>" . $suffix . "</td>
            <td>" . $birthday . "</td>
            <td>" . $age . "</td>
            <td>
                <button class='edit-btn' data-id='" . $row['collector_id'] . "' 
                        data-firstname='" . $row['fname'] . "' 
                        data-middlename='" . $row['mname'] . "' 
                        data-lastname='" . $row['lname'] . "' 
                        data-suffix='" . $row['suffix'] . "' 
                        data-birthday='" . $row['birthday'] . "'>
                    <i class='fas fa-edit'></i>
                </button>
                
                <a href='move_to_archive.php?id=" . $row['collector_id'] . "' 
                   class='delete-btn'>
                   <i class='fas fa-trash'></i>
                </a>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No records found</td></tr>";  // Adjusted colspan for age column
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
        <!-- End of collector table -->
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content" style="text-align: center;">
        <span class="close">&times;</span>
        <img src="pics/checkk.gif" alt="Success Check" style="width: 50px; height: 50px; margin-bottom: 20px;">
        <h2>Registration Successful</h2>
        <p id="successMessage"><?php echo $successMessage; ?></p>
    </div>
</div>


<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Collector</h2>
        <form id="editCollectorForm" method="POST" action="edit_collector.php">
            <input type="hidden" id="editCollectorId" name="collector_id">
            <label for="editFirstName">First Name:</label>
            <input type="text" id="editFirstName" name="firstName" required><br><br>
            
            <label for="editMidName">Middle Name:</label>
            <input type="text" id="editMidName" name="MidName"><br><br> <!-- Removed 'required' -->
            
            <label for="editLastName">Last Name:</label>
            <input type="text" id="editLastName" name="lastName" required><br><br>
            
            <label for="editSuffix">Suffix:</label>
            <select id="editSuffix" name="suffix">
                <option value="">Select Suffix</option>
                <option value="Jr.">Jr.</option>
                <option value="Sr.">Sr.</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
            </select><br><br> <!-- Removed 'required' -->
            
            <label for="editBirthday">Birthday:</label>
            <input type="date" id="editBirthday" name="birthday" required><br><br>
            
            <input type="submit" value="Save Changes">
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="delete-confirmation">
    <h4>Confirm Action</h4>
    <p>Are you sure you want to archive and delete this collector?</p>
    <div class="modal-actions">
        <button type="submit" id="confirmBtn">Yes</button>
        <button type="button" id="cancelBtn">No</button>
    </div>
</div>



<?php
// Close the database connection
$conn->close();
?>

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
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const modal = document.getElementById('confirmModal');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const closeBtn = document.querySelector('.close'); // Select the close button
    let currentHref = '';

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default link behavior
            currentHref = this.href; // Store the href to use on confirmation
            modal.style.display = 'block'; // Show the modal
        });
    });

    confirmBtn.addEventListener('click', function () {
        modal.style.display = 'none'; // Hide the modal
        window.location.href = currentHref; // Redirect to the stored href
    });

    cancelBtn.addEventListener('click', function () {
        modal.style.display = 'none'; // Hide the modal
    });

    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none'; // Hide the modal when the close button is clicked
    });

    // Close the modal when clicking outside of it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
});



    // Get modal elements
    var editModal = document.getElementById("editModal");
    var successModal = document.getElementById("successModal");
    var closeEditBtn = document.getElementsByClassName("close")[0];

    // Close edit modal
    closeEditBtn.onclick = function() {
        editModal.style.display = "none";
    }

    // Close success modal
    window.onclick = function(event) {
        if (event.target == editModal) {
            editModal.style.display = "none";
        } else if (event.target == successModal) {
            successModal.style.display = "none";
        }
    }

    // Add event listeners to edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Open the modal
            editModal.style.display = "block";

            // Populate the modal with the collector's information
            document.getElementById("editCollectorId").value = this.getAttribute("data-id");
            document.getElementById("editFirstName").value = this.getAttribute("data-firstname");
            document.getElementById("editMidName").value = this.getAttribute("data-middlename");
            document.getElementById("editLastName").value = this.getAttribute("data-lastname");
            document.getElementById("editSuffix").value = this.getAttribute("data-suffix");
            document.getElementById("editBirthday").value = this.getAttribute("data-birthday");
        });
    });

    // Show success modal if there is a success message
    if (document.getElementById('successMessage').innerText !== '') {
        successModal.style.display = 'block';
    }
    
    // Close modals when the close button is clicked
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
        // Close the respective modal
        this.closest('.modal').style.display = 'none'; // Close the modal containing this button
    });
});

// Close modals when clicking outside of them
window.onclick = function(event) {
    const successModal = document.getElementById("successModal");
    const editModal = document.getElementById("editModal");
    
    if (event.target === successModal) {
        successModal.style.display = 'none';
    } else if (event.target === editModal) {
        editModal.style.display = 'none';
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
