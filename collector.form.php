<?php
// collector.php
include 'config.php';

// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize variables
$newId = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data from $_POST
    $firstName = $_POST['firstName'];
    $midName = !empty($_POST['MidName']) ? $_POST['MidName'] : null;
    $lastName = $_POST['lastName'];
    $suffix = !empty($_POST['suffix']) ? $_POST['suffix'] : null;
    $email = $_POST['email'];
    $birthday = $_POST['birthday'];

    // Retrieve and update last used ID from collector_id_sequence
    $conn->begin_transaction();
    try {
        $result = $conn->query("SELECT last_used_id FROM collector_id_sequence FOR UPDATE");
        $row = $result->fetch_assoc();
        $lastUsedId = $row['last_used_id'];

        // Increment last used ID and format it with prefix
        $newLastUsedId = $lastUsedId + 1;
        $newId = 'BTC-' . str_pad($newLastUsedId, 3, '0', STR_PAD_LEFT);

        // Update last_used_id in collector_id_sequence
        $conn->query("UPDATE collector_id_sequence SET last_used_id = $newLastUsedId");

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error generating collector ID: " . $e->getMessage();
        exit;
    }

    // Insert collector details without username and password
    $stmt = $conn->prepare("INSERT INTO collectors (collector_id, first_name, middle_name, last_name, suffix, email, birthday) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $newId, $firstName, $midName, $lastName, $suffix, $email, $birthday);

    // Execute the query
    if ($stmt->execute()) {
        echo "<script>showModal();</script>";
    } else {
        echo "Error inserting collector details: " . $stmt->error;
        error_log("Error inserting collector details: " . $stmt->error);
    }

    // Close the initial insert statement
    $stmt->close();
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="icon" href="pics/logo-bt.png">

  <link rel="stylesheet" href="logo.css">
  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Collector</title>
  <style>
       body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #F2F7FC;
            position: relative;
        }

    /* Panel for the form */

      .panel {
    background-color: #ffffff;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 90%; 
    margin-top:3%;
    margin-left:5%;
    border-radius: 10px;
    box-sizing: border-box;
    overflow-x:hidden;
    overflow-y:hidden;
 

}

   /* Form Styling */
form {
    display: flex;
    flex-direction: column;
    gap: 15px; /* Space between form elements */
    align-items: flex-start; /* Aligns form elements to the left */
    
}

#userInfoForm {
    background-color: white; /* White background for the form */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    padding: 20px; /* Padding inside the form */
    margin: -15px auto 20px auto; /* Center the form with margin */
    width: 90%; /* Full width with a max limit */
    max-width: 600px; /* Optional: limit maximum width for larger screens */
    display: flex; /* Use flexbox for layout */
    flex-direction: column; /* Stack elements vertically */
    
}


form label {
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #031F4E;
    margin-bottom: 8px; /* Space between label and input */
    font-weight: bold; /* Bold labels for clarity */
    color: #333; /* Dark color for better readability */
}
input[type="submit"] {
  margin-left:35%;
    background-color: #031F4E; /* Button color */
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    font-size: 14px; /* Decrease font size */
}

input[type="submit"]:hover {
    background-color: #2A416F; /* Darker button color on hover */
}

/* General Input Styles */
input[type="text"],
input[type="number"],
input[type="date"],
select {
    width: calc(100% - 20px); /* Full width minus padding */
    padding: 10px; /* Consistent padding */
    margin-top: 5px; /* Space above inputs */
    border: 1px solid #ccc; /* Light border for inputs */
    border-radius: 5px; /* Rounded corners */
    font-size: 14px; /* Font size for readability */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
}
/* Button for going back */
.back-button {
   background-color:white;
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

/* Modal for success message */
.modal-success {
    display: none; /* Hidden by default */
    position: fixed; 
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    background-color: rgba(0, 0, 0, 0.4); /* Black background with transparency */
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Modal content */
.modal-content {
    background-color: #fefefe;
    padding: 20px;
    border: 1px solid #888;
    width: 90%; /* Responsive width */
    max-width: 400px; /* Set a max width */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    text-align: center;
    border-radius: 10px;
}

/* Close button */
.close-btn {
    color: #aaa;
    float: right;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
}   .header-panel {
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

    .main-content {
      padding: 20px;
      margin-left: 260px; /* Adjust based on your sidebar width */
       background-color: #F2F7FC;
       height:auto;
       width: 80%;
       overflow-x:hidden;
       overflow-y:hidden;
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
    
            .logout {
                color: #e74c3c; 
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

    /* Modal for success message */
    .modal-success {
      display: none; /* Hidden by default */
      position: fixed; 
      z-index: 1000; /* Sit on top */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%; /* Full height */
      background-color: rgba(0, 0, 0, 0.4); /* Black background with transparency */
    }

    /* Modal content */
    .modal-content {
   
      background-color: #fefefe;
      margin: 25% auto; /* 15% from the top and centered */
      padding: 20px;
      border: 1px solid #888;
      width: 30%; /* Could be more or less depending on screen size */
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
      text-align: center;
    }

    /* Close button */
    .close-btn {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;

    }

    .close-btn:hover,
    .close-btn:focus {
      color: #000;
      text-decoration: none;
      cursor: pointer;
    }
            .user-icon {
    width: 40px; /* Set a fixed width for the icon */
    height: 40px; /* Set a fixed height for the icon */
    border-radius: 50%; /* Makes the icon circular */
    margin-left: -110%; /* Aligns the icon in the header */
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
/* Optional: Style for titles if you want to align them nicely */
.header-titles {
    flex-grow: 1; /* Allows the titles to take up remaining space */
}

.header-titles h2,
.header-titles h5 {
    margin: 0; /* Remove default margins */
}

.header-titles p{
  font-weight: 300; 
  font-size: 10px;
  color:red;
}
.required {
        color: red;
    }


  </style>
  <script>
    // JavaScript to auto-capitalize the first letter of each word
    function capitalizeFirstLetter(input) {
      const words = input.value.split(' ');
      for (let i = 0; i < words.length; i++) {
        words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1);
      }
      input.value = words.join(' ');
    }

    // Function to show the modal after form submission
    function showModal() {
      const modal = document.getElementById("successModal");
      const closeBtn = document.querySelector(".close-btn");

      // Show the modal
      modal.style.display = "block";

      // Close the modal when the user clicks the 'x' button
      closeBtn.onclick = function() {
        modal.style.display = "none";
        window.location.href = "collector.php"; // Redirect to collector.php after closing modal
      }

      // Close the modal if the user clicks outside of the modal
      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = "none";
          window.location.href = "collector.php"; // Redirect to collector.php after closing modal
        }
      }
    }

    // Add event listeners to inputs
    document.addEventListener("DOMContentLoaded", function() {
      const firstNameInput = document.getElementById("firstName");
      const midNameInput = document.getElementById("MidName");
      const lastNameInput = document.getElementById("lastName");

      firstNameInput.addEventListener("input", function() {
        capitalizeFirstLetter(firstNameInput);
      });

      midNameInput.addEventListener("input", function() {
        capitalizeFirstLetter(midNameInput);
      });

      lastNameInput.addEventListener("input", function() {
        capitalizeFirstLetter(lastNameInput);
      });
    });
  </script>
</head>
<body>

<!-- Sidebar -->
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

  <!-- Changed Vendors dropdown to a direct link -->
  <a href="vendorlist.php"><i class="fas fa-users"></i> Vendors</a>

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

<div class="main-content">
    <div class="panel">
      <button class="back-button" onclick="window.location.href='collector.php'">
        <i class="fas fa-arrow-left"></i> 
      </button>
      <br>

      <div class="header-titles">
        <h2>Collector form</h2>
        <p>Please fill up the form to register a collector.</p>
    </div>

<br><br>
      <!-- Form sends data to collector.php -->
    <form id="userInfoForm" action="collector.php" method="POST">
    <label for="firstName">First Name: <span class="required">*</span></label>
    <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" required>

    <label for="MidName">Middle Name:</label>
    <input type="text" id="MidName" name="MidName" placeholder="Enter Middle Name"> <!-- Middle name is now optional -->

      <label for="lastName">Last Name: <span class="required">*</span></label>
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

  <label for="email">Email: <span class="required">*</span></label>
    <input type="text" id="email" name="email" placeholder="Enter Email" required>

    <label for="birthday">Birthday: <span class="required">*</span></label>
    <input type="date" id="birthday" name="birthday" required>

    <input type="submit" value="Register Collector" name="sbt-btn">
</form>
 
    </div>
</div>

<!-- Modal Structure -->
<div id="successModal" class="modal-success">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <p>Collector Successfully Added!</p>
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
