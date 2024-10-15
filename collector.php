<?php
// collector.php

// Database connection parameters
$servername = "localhost";
$username = "root"; // Adjust according to your database username
$password = "";     // Adjust according to your database password
$dbname = "bangketicketdb"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data from $_POST
    $firstName = $_POST['firstName'];
    $midName = $_POST['MidName'];
    $lastName = $_POST['lastName'];
    $suffix = $_POST['suffix'];
    $birthday = $_POST['birthday'];

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
    $sql = "INSERT INTO collectors (collector_id, fname, mname, lname, suffix, birthday)
            VALUES ('$newId', '$firstName', '$midName', '$lastName', '$suffix', '$birthday')";

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
            // Display the generated username and password
            echo "New collector registered successfully with ID: $newId<br>";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="menuheaderDB.css">
    <link rel="stylesheet" href="logo.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collectors</title>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
        }

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
            transition: width 0.3s;
            padding: 2px;
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

        .side-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #031F4E;
            text-decoration: none;
            transition: background 0.3s, color 0.3s;
        }

        .side-menu a i {
            margin-right: 10px;
        }

        .side-menu a.active {
            background-color: #031F4E;
            color: #fff;
        }

        .side-menu a:hover:not(.active) {
            background-color: #031F4E;
            color: #fff;
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
            font-size: 29px;
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
    max-height: 90vh; /* Ensure modal content fits in viewport */
    overflow: auto; /* Allow content overflow without scrolling the entire modal */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow */
    animation: fadeIn 0.3s ease-in-out; /* Add fade-in animation */
    position: fixed; /* Keeps the modal in the center without scrolling */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* Center modal */
    
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
            <a href="vendorlist.php" id="vendorListLink" class="active"><i class="fas fa-list"></i> Vendor List</a>
            <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
        </div>
    </div>

    <a href="collector.php" class="active"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>

     <!-- Log Out Link -->
     <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

<div class="main">
    <div class="panel">
        <div class="title-button-container">
            <h2>Collector Table</h2>
            <a href="collector.form.php" id="addCollectorBtn">Add Collector</a>
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
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            // Use $collectorsResult for fetching and displaying collectors
            if ($collectorsResult && $collectorsResult->num_rows > 0) {  
                while ($row = $collectorsResult->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row['collector_id'] . "</td>
                            <td>" . $row['fname'] . "</td>
                            <td>" . $row['mname'] . "</td>
                            <td>" . $row['lname'] . "</td>
                            <td>" . $row['suffix'] . "</td>
                            <td>" . $row['birthday'] . "</td>
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
                                   class='delete-btn' 
                                   onclick='return confirm(\"Are you sure you want to archive and delete this collector?\")'>
                                   <i class='fas fa-trash'></i>
                                </a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No records found</td></tr>";  // Handle the case where no records are found
            }
            ?>
            </tbody>
        </table>
        <!-- End of collector table -->
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Collector</h2>
        <form id="editCollectorForm" method="POST" action="edit_collector.php">
            <input type="hidden" id="editCollectorId" name="collector_id">

            <label for="editFirstName">First Name:</label>
            <input type="text" id="editFirstName" name="firstName" required><br><br>

            <label for="editMidName">Middle Name:</label>
            <input type="text" id="editMidName" name="MidName" required><br><br>

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
        </select><br><br>

            <label for="editBirthday">Birthday:</label>
            <input type="date" id="editBirthday" name="birthday" required><br><br>

            <input type="submit" value="Save Changes">
        </form>
    </div>
</div>

            </tbody>
        </table>
    </div>
</div>

<?php
// Close the database connection
$conn->close();
?>

<script>
    // Get modal element and close button
    var modal = document.getElementById("editModal");
    var closeBtn = document.getElementsByClassName("close")[0];

    // When the user clicks on the close button, close the modal
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when the user clicks outside of the modal
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Add event listeners to edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Open the modal
            modal.style.display = "block";

            // Populate the modal with the collector's information
            document.getElementById("editCollectorId").value = this.getAttribute("data-id");
            document.getElementById("editFirstName").value = this.getAttribute("data-firstname");
            document.getElementById("editMidName").value = this.getAttribute("data-middlename");
            document.getElementById("editLastName").value = this.getAttribute("data-lastname");
            document.getElementById("editSuffix").value = this.getAttribute("data-suffix");
            document.getElementById("editBirthday").value = this.getAttribute("data-birthday");
        });
    });
</script>

</body>
</html>
