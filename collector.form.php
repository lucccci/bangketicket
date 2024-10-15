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

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize variables
$newId = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data from $_POST
    $firstName = $_POST['firstName'];
    $midName = $_POST['MidName'];
    $lastName = $_POST['lastName'];
    $suffix = $_POST['suffix'];
    $birthday = $_POST['birthday'];

    // Fetch the last collector_id from the database in descending order
    $result = $conn->query("SELECT collector_id FROM collectors ORDER BY collector_id DESC LIMIT 1");
    if ($result->num_rows > 0) {
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

    // First insert collector details WITHOUT username and password
    $stmt = $conn->prepare("INSERT INTO collectors (collector_id, first_name, middle_name, last_name, suffix, birthday) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $newId, $firstName, $midName, $lastName, $suffix, $birthday);

    // Execute the query
    if ($stmt->execute()) {
        echo "Collector details inserted successfully.<br>"; // Add this for debugging

        // Close the update statement
        $stmt_update->close();
    } else {
        echo "Error inserting collector details: " . $stmt->error;
        error_log("Error inserting collector details: " . $stmt->error);
    }

    // Close the initial insert statement
    $stmt->close();
    
    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="icon" href="pics/logo-bt.png">
  <link rel="stylesheet" href="menuheader.css">
  <link rel="stylesheet" href="vendorform.css">
  <link rel="stylesheet" href="logo.css">
  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Collector</title>
  <style>
    /* Panel for the form */
    .panel {
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 100%; 
      height: auto;
      margin-left: auto;
      margin-right: auto;
      box-sizing: border-box;
    }

    /* Form Styling */
    form {
      display: flex;
      flex-direction: column;
      gap: 15px; /* Space between form elements */
    }

    form label {
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      font-weight: 500;
      color: #031F4E;
    }

    form input[type="text"],
    form input[type="date"],
    form select {
      padding: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 5px;
      width: 100%;
    }

    /* Submit Button Styling */
    form input[type="submit"] {
      width: 20%;
      margin-top: 20px;
      margin-left:40%; /* Align to the right */
      padding: 15px 40px;
      background-color: #031F4E;
      color: #fff;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      font-size: 16px;
      font-family: 'Poppins', sans-serif;
      transition: background-color 0.3s ease;
    }

    form input[type="submit"]:hover {
      background-color: #246ba9;
    }

    /* Button for going back */
    .back-button {
    
      color: #031F4E;
      border: 1px solid #031F4E;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 5px;
      font-family: 'Poppins', sans-serif;
      display: inline-flex;
      align-items: center;
      text-decoration: none;
    }

    .back-button:hover {
      background-color: #246ba9;
    }

    .back-button i {
      margin-right: 8px;
    }

    .header-panel {
      display: flex;
      justify-content: flex-end;
      padding: 10px;
      background-color: #031F4E;
    }

    
    .main-content {
      padding: 20px;
      margin-left: 260px; /* Adjust based on your sidebar width */
    }

    h2 {
      color: #031F4E;
      font-family: 'Poppins', sans-serif;
      font-size: 24px;
      margin-bottom: 10px;
    }

    h5 {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      color: #666;
    }

    /* Sidebar */
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
    transition: background 0.3s, color 0.3s;
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
            background-color: #c0392b; /* Hover effect for Log Out link */
            color: #fff; /* Change text color on hover */
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



  </style>
   <script>
    // JavaScript to auto-generate username and password based on input fields
    function generateCredentials() {
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const birthday = document.getElementById('birthday').value;

        if (firstName && lastName && birthday) {
            // Generate username (first name + first letter of last name)
            const username = firstName.toLowerCase() + lastName.charAt(0).toLowerCase();

            // Generate password (first name + birthday in YYYYMMDD format)
            const formattedBirthday = birthday.replace(/-/g, '');
            const password = firstName.toLowerCase() + formattedBirthday;

            // Set the generated values in hidden fields
            document.getElementById('generatedUsername').value = username;
            document.getElementById('generatedPassword').value = password;
        }
    }
  </script>
</head>
<body>
<div class="header-panel"></div>
<!-- Sidebar -->
<div id="sideMenu" class="side-menu">
  <div class="logo">
    <img src="pics/logo.png" alt="Logo">
  </div>
  <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
  <a href="product.php"><i class="fas fa-box"></i> Product</a>

  <!-- Changed Vendors dropdown to a direct link -->
  <a href="vendorlist.php"><i class="fas fa-users"></i> Vendors</a>

  <a href="collector.php" class="active"><i class="fa fa-user-circle"></i> Collector</a>
  <a href="#"><i class="fa fa-table"></i> Collection</a>
  <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>

  <!-- Log Out Link -->
  <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>

  <div class="main-content">
    <div class="panel">
      <button class="back-button" onclick="window.location.href='collector.php'">
        <i class="fas fa-arrow-left"></i> 
      </button>

      <h2>Collector Registration</h2>
      <h5 class="personalinfo-heading">Personal Details</h5>

      <!-- Form sends data to collector.php -->
      <form id="userInfoForm" action="collector.php" method="POST" onsubmit="generateCredentials()">
        <label for="firstName">First Name:</label>
        <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" required>

        <label for="MidName">Middle Name:</label>
        <input type="text" id="MidName" name="MidName" placeholder="Enter Middle Name" required>

        <label for="lastName">Last Name:</label>
        <input type="text" id="lastName" name="lastName" placeholder="Enter Last Name" required>

        <label for="suffix">Suffix:</label>
        <select id="suffix" name="suffix">
          <option value="">Select Suffix</option>
          <option value="Jr.">Jr.</option>
          <option value="Sr.">Sr.</option>
          <option value="II">II</option>
          <option value="III">III</option>
          <option value="IV">IV</option>
          <option value="V">V</option>
        </select>

        <label for="birthday">Birthday:</label>
        <input type="date" id="birthday" name="birthday" required>

        <!-- Hidden fields for storing auto-generated username and password -->
        <input type="hidden" id="generatedUsername" name="username">
        <input type="hidden" id="generatedPassword" name="password">

        <input type="submit" value="Register Collector" name="sbt-btn">
      </form>
    </div>
</div>
<script>
document.querySelector('.dropdown a').addEventListener('click', function(event) {
  event.preventDefault();
  this.parentElement.classList.toggle('active');
});

  </script>
</body>
</html>