<?php
require_once 'config.php';
require_once 'phpqrcode/qrlib.php';

$path = 'images/';
$vendor_list = $path . time() . ".png";
$qrimage = time() . ".png";

if (isset($_REQUEST['sbt-btn'])) {
  // Get form data and replace empty fields with "N/A"
  $fName = $_REQUEST['firstName'];
  $mname = !empty($_REQUEST['MidName']) ? $_REQUEST['MidName'] : 'N/A';
  $lName = $_REQUEST['lastName'];
  $suffix = !empty($_REQUEST['suffix']) ? $_REQUEST['suffix'] : 'N/A';
  $gender = $_REQUEST['gender'];
  $birthday = $_REQUEST['birthday'];
  $age = !empty($_REQUEST['age']) ? $_REQUEST['age'] : 'N/A';
  $contactNo = !empty($_REQUEST['contactNo']) ? $_REQUEST['contactNo'] : 'N/A';
  $lotArea = $_REQUEST['lotArea'];
    // Get the text values instead of the codes
    $province = $_REQUEST['provinceText'];
    $municipality = $_REQUEST['cityText'];
    $barangay = $_REQUEST['barangayText'];
  $houseNo = $_REQUEST['houseNumber'];
  $streetname = $_REQUEST['streetName'];

  // Fetch the current last used ID from the sequence table
  $result = mysqli_query($conn, "SELECT last_used_id FROM vendor_id_sequence LIMIT 1");
  $row = mysqli_fetch_assoc($result);

  if ($row) {
      // Increment the last used ID
      $newID = (int)$row['last_used_id'] + 1;

      // Update the sequence table with the new last used ID
      mysqli_query($conn, "UPDATE vendor_id_sequence SET last_used_id = $newID");
  } else {
      // Start with 1 if there are no existing IDs
      $newID = 1;
      mysqli_query($conn, "INSERT INTO vendor_id_sequence (last_used_id) VALUES (1)");
  }

  // Format the new vendorID with leading zeros (e.g., BTV-001, BTV-002)
  $formattedID = 'BTV-' . str_pad($newID, 3, '0', STR_PAD_LEFT);

  // Check if the generated ID already exists in the vendor_list
  $checkIDQuery = mysqli_query($conn, "SELECT vendorID FROM vendor_list WHERE vendorID = '$formattedID'");
    
  // If the ID exists, increment until a unique ID is found
  while (mysqli_num_rows($checkIDQuery) > 0) {
      $newID++;
      $formattedID = 'BTV-' . str_pad($newID, 3, '0', STR_PAD_LEFT);
      $checkIDQuery = mysqli_query($conn, "SELECT vendorID FROM vendor_list WHERE vendorID = '$formattedID'");
  }

  // Insert the new vendor record with the formatted vendorID
  $query = mysqli_query($conn, "INSERT INTO vendor_list SET 
                                vendorID='$formattedID', 
                                fName='$fName', 
                                mname='$mname', 
                                lName='$lName', 
                                suffix='$suffix', 
                                gender='$gender', 
                                birthday='$birthday', 
                                age='$age', 
                                contactNo='$contactNo', 
                                lotArea='$lotArea',
                                province='$province', 
                                municipality='$municipality', 
                                barangay='$barangay', 
                                houseNo='$houseNo', 
                                streetname='$streetname'");

  if ($query) {
      // Update the QR code generation to use the new vendorID
      $data = "Vendor ID: $formattedID\nTransactions: https://bangketicket.online/vendortransactions.php?id=$formattedID";
      $updateQuery = mysqli_query($conn, "UPDATE vendor_list SET qrimage='$qrimage' WHERE vendorID='$formattedID'");

      QRcode::png($data, $vendor_list, 'H', 4, 4);

       // Pass a success flag as a query parameter
       header("Location: vendorform.php?success=1");
       exit(); // Ensure the script stops executing after the redirection
   }
}
// Fetch admin details
$sql = "SELECT profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="icon" href="pics/logo-bt.png">
  <link rel="stylesheet" href="menuheader.css">
  <link rel="stylesheet" href="logo.css">
  
  

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Vendor</title>
  <style>
            * {
                padding: 0;
                margin: 0;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
            }
    
            body {
                margin: 0;
                font-family: 'Poppins', sans-serif;
                background-color: #F2F7FC;
                position: relative;
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
  margin-left: 20px; /* Space between the icon and the edge */
}

.profile-icon:hover {
  opacity: 0.8; /* Change opacity on hover for a slight effect */
}
/* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #F2F7FC; /* Light background for contrast */
    margin: 0;
    padding: 0;
}

/* Main Content Styles */
.main-content {
    margin-left: 260px; /* Space for the sidebar */
    padding: 20px; /* General padding for main content */
}

/* Form Styles */
.panel {
    background-color: white; /* Ensure the panel has a white background */
    border-radius: 8px; /* Rounded corners for aesthetics */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional: add a shadow for depth */
    padding: 20px; /* Add padding to prevent content from touching edges */
    margin: 20px auto; /* Center the panel and add vertical spacing */
    width: 90%; /* Adjust the width (e.g., 90% of the parent container) */
    display: flex; /* Use flexbox for layout */
    flex-direction: column; /* Stack elements vertically */
    margin-top: 50px;
}



.header-container {
    display: flex; /* Use flexbox for alignment */
    align-items: center; /* Center items vertically */
    justify-content: space-between; /* Space items evenly */
    margin-bottom: 20px; /* Space below the header */
}

.back-button {
    margin-right: 20px; /* Space to the right of the button */
}

/* Optional: Style for titles if you want to align them nicely */
.header-titles {
    flex-grow: 1; /* Allows the titles to take up remaining space */
}

.header-titles h2,
.header-titles h1 {
    margin: 0; /* Remove default margins */
}

.header-titles p{
  font-weight: 300; 
  font-size: .62rem;
  color:red;
}

label {
    margin-top: 20px;
    font-weight: bold; /* Bold labels for clarity */
}

.address-heading {
    padding: 2rem 0 0 0 ;
}

/* Specific Adjustments for Select Fields */
select {
    height: 42px; /* Ensure select fields have the same height */
}

input[type="submit"] {
    background-color: #031F4E; /* Button color */
    color: white;
    border: none;
    padding: 10px 20px;
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

/* Ensure consistent styles on focus */
input:focus,
select:focus {
    border: 1px solid #031F4E; /* Change border color on focus */
    outline: none; /* Remove default outline */
}

/* Phone Input Container Styles */
.phone-input-container {
    display: flex; /* Use flexbox for layout */
    align-items: center; /* Center items vertically */
    border: 1px solid #ccc; /* Light border */
    border-radius: 5px; /* Rounded corners */
    overflow: hidden; /* Prevent overflow */
    width: calc(100% - 20px); /* Match other input widths */
}

.phone-input-container .country-code {
    background-color: #f8f8f8; /* Background color for the flag area */
    padding: 10px; /* Padding around the flag */
    border-right: 1px solid #ccc; /* Divider between flag and input */
    display: flex; /* Align items in the span */
    align-items: center; /* Center align the flag and text */
}

.phone-input-container img {
    width: 20px; /* Adjust the size of the flag */
    height: 20px; /* Adjust the size of the flag */
    margin-right: 5px; /* Space between flag and country code */
}

.phone-input-container input {
    border: none; /* Remove border from input */
    padding: 10px; /* Consistent padding */
    font-size: 14px; /* Consistent font size */
    width: 100%; /* Full width for the input field */
}

/* Focus Style for Phone Input */
.phone-input-container input:focus {
    border: none; /* Keep border none */
    outline: none; /* Remove default outline */
}

.generate-qr-button {
    display: block; /* Change to block to enable margin auto centering */
    margin: 20px auto; /* Add vertical space and center horizontally */
    padding: 10px 20px; /* Add padding for aesthetics */
    background-color: #031F4E; /* Button background color */
    color: white; /* Text color */
    border: none; /* Remove border */
    border-radius: 5px; /* Rounded corners */
    font-size: 16px; /* Font size */
    cursor: pointer; /* Pointer cursor on hover */
    transition: background-color 0.3s ease; /* Smooth transition for hover */
}

.generate-qr-button:hover {
    background-color: #2A416F; /* Darker shade on hover */
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

/* Style for form labels */
#userInfoForm label {
    margin-bottom: 8px; /* Space between label and input */
    font-weight: bold; /* Bold labels for clarity */
    color: #333; /* Dark color for better readability */
}

/* Responsive Styles */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0; /* Remove left margin on smaller screens */
        padding: 10px; /* Reduce padding */
    }

    .phone-input-container {
        max-width: 100%; /* Allow phone input to be full width on small screens */
    }
}

        .user-icon {
    width: 40px; /* Set a fixed width for the icon */
    height: 40px; /* Set a fixed height for the icon */
    border-radius: 50%; /* Makes the icon circular */
   margin-left: -55%; /* Aligns the icon in the header */
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
/* Success Modal */
.success-modal {
    display: flex;
    justify-content: center;
    align-items: center; /* Center the modal vertically */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
}

/* Style the modal content */
.success-modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    width: 300px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    margin-top:20%; /* Ensure the modal content is centered */
    margin-left:43%;
}

.success-modal-content img {
    width: 50px; /* Set width for the check gif */
    height: 50px;
}

.success-modal-content h2 {
    margin-top: 10px;
    font-size: 1.2rem;
    color: #031F4E;
}
    .required {
        color: red;
    }



  </style>
</head>
<body>

  <div class="header-panel">
    <div class="header-title"></div>
    <a href="admin_profile.php">
        <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
    </a>
</div>


<div class="overlay"></div>




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
    <div class="dropdown active">
    <a href="#" class="active"><i class="fas fa-users"></i> Vendors</a>
    <div class="dropdown-content" style="display: block;">
    <a href="vendorlist.php" class="active"><i class="fas fa-list"></i> Vendor List</a>
        <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a> <!-- Highlighted -->
    </div>
</div>
<a href="#"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>

    <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>



<div class="main-content">



  <div class="panel">
    <br>
    <div class="header-container">
    <button class="back-button" onclick="history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>
    
    <div class="header-titles">
        <h2>Vendor Registration Form</h2>
        <p>Please fill up the form to register and get QR Code for Vendors</p>
    </div>
</div>

    <br>
    
    <form id="userInfoForm" action="vendorform.php" method="POST">
    <h1 class="personalinfo-heading">Personal Details</h1>
    
    <label for="firstName">First Name: <span class="required">*</span></label>
    <input type="text" id="firstName" name="firstName" placeholder="Enter First Name" required>

<label for="MidName">Middle Name:</label>
<input type="text" id="MidName" name="MidName" placeholder="Enter Middle Name">

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

<label for="gender">Gender:</label>
<select id="gender" name="gender">
  <option value="">Select Gender</option>
  <option value="male">Male</option>
  <option value="female">Female</option>
</select>

<label for="birthday">Birthday: <span class="required">*</span></label>
<input type="date" id="birthday" name="birthday" required>

<label for="age">Age: <span class="required">*</span></label>
<input type="number" id="age" name="age" placeholder="Enter Age" required>

<label for="contactNo">Contact Number: <span class="required">*</span></label>
<div class="phone-input-container">
  <span class="country-code">
      <img src="philippineflag.webp" alt="Philippine Flag"> +63
  </span>
  <input type="text" id="contactNo" name="contactNo" pattern="\d{10}" placeholder="XXXXXXXXXX" maxlength="10" required>
</div>

<label for="lotArea">Lot Area: <span class="required">*</span></label>
<select id="lotArea" name="lotArea" required>
  <option value="">Select Area Size</option>
  <option value="1 sq. m">1 sq. m</option>
  <option value="2 sq. m">2 sq. m</option>
  <option value="3 sq. m">3 sq. m</option>
  <option value="4 sq. m">4 sq. m</option>
  <option value="5 sq. m">5 sq. m</option>
</select>


<h1 class="address-heading">Address</h1> 

<br>  

<label for="houseNumber">House No (Lot/Blk): <span class="required">*</span></label>
<input type="text" id="houseNumber" name="houseNumber" placeholder="Enter House No (Lot/Blk)" required>

<label for="streetName">Street Name: <span class="required">*</span></label>
<input type="text" id="streetName" name="streetName" placeholder="Enter Street Name" required>

<label for="province">Province: <span class="required">*</span></label>
<select id="province" name="province" required>
    <option value="">Select Province</option>
</select>
<input type="hidden" id="provinceText" name="provinceText">

<label for="city">City/Municipality: <span class="required">*</span></label>
<select id="city" name="city" required>
    <option value="">Select City/Municipality</option>
</select>
<input type="hidden" id="cityText" name="cityText">

<label for="barangay">Barangay: <span class="required">*</span></label>
<select id="barangay" name="barangay" required>
    <option value="">Select Barangay</option>
</select>
<input type="hidden" id="barangayText" name="barangayText">



    
        <input type="submit" value="Generate QR Code" name="sbt-btn" class="generate-qr-button">

    </form>
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

    <!-- Success Modal -->
<div id="successModal" class="success-modal" style="display: none;">
    <div class="success-modal-content">
        <img src="pics/checkk.gif" alt="Success Check" style="width: 50px; height: 50px;">
        <h2>Vendor successfully registered!</h2>
    </div>
</div>

<script>

 // Function to show the success modal
 function showSuccessModal() {
        var successModal = document.getElementById("successModal");
        successModal.style.display = "block";
        
        // Hide the modal after 3 seconds and redirect to vendorlist.php
        setTimeout(function () {
            successModal.style.display = "none";
            window.location.href = 'vendorlist.php';
        }, 3000);
    }

    // Check if the URL contains the success parameter
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            showSuccessModal();
        }
    };
  //CONTACT

  document.getElementById('contactNo').addEventListener('input', function (e) {
    // Remove any non-digit characters
    this.value = this.value.replace(/\D/g, '');
});

document.addEventListener('DOMContentLoaded', function () {
    // Get all input fields
    var inputs = document.querySelectorAll('input[type="text"]');

    // Add event listener for each input field
    inputs.forEach(function(input) {
      input.addEventListener('input', function() {
        // Capitalize the first letter
        var value = input.value;
        if (value.length > 0) {
          input.value = value.charAt(0).toUpperCase() + value.slice(1);
        }
      });
    });
  });

  //bdayy
  function calculateAge() {
  var birthday = document.getElementById("birthday").value;
  var today = new Date();
  var birthDate = new Date(birthday);
  var age = today.getFullYear() - birthDate.getFullYear();
  var m = today.getMonth() - birthDate.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  document.getElementById("age").value = age;
}

// Attach calculateAge function to the change event of birthday input
document.getElementById("birthday").addEventListener("change", calculateAge);

// Initial call to calculate age based on default value of birthday input
calculateAge();

//address

// Load CSV data and parse it for provinces, municipalities, and barangays
async function loadAddressData() {
  const response = await fetch('Philippine_Address_Data.csv');
  const data = await response.text();

  // Parse CSV data into an array of objects and handle empty rows
  const rows = data.split('\n').slice(1).filter(row => row.trim() !== '');
  const addresses = rows.map(row => {
    const [level, name, code] = row.split(',').map(item => item ? item.trim() : ''); // Ensure no undefined properties
    return { level, name, code };
  });

  return addresses;
}
// Sort addresses alphabetically by name
function sortAddresses(addresses) {
    return addresses.sort((a, b) => a.name.localeCompare(b.name));
}

// Populate the Province dropdown
async function populateProvinces() {
    const addresses = await loadAddressData();
    const provinces = sortAddresses(addresses.filter(address => address.level === 'Prov'));
    const provinceDropdown = document.getElementById('province');

    provinces.forEach(province => {
        let option = document.createElement('option');
        option.value = province.code;
        option.textContent = province.name;
        provinceDropdown.appendChild(option);
    });
}

// Event listener for Province selection and dropdown initialization
document.addEventListener('DOMContentLoaded', function () {
    populateProvinces();

    document.getElementById('province').addEventListener('change', async function () {
        const provinceCode = this.value;
        const provinceText = this.options[this.selectedIndex].text; // Get the selected province text
        document.getElementById('provinceText').value = provinceText; // Set hidden input value

        const addresses = await loadAddressData();

        // Include both 'Mun' and 'City' levels in the municipalities dropdown
        const municipalitiesAndCities = sortAddresses(addresses.filter(
            address => (address.level === 'Mun' || address.level === 'City') && address.code.startsWith(provinceCode.slice(0, 4))
        ));

        const cityDropdown = document.getElementById('city');
        cityDropdown.innerHTML = '<option value="">Select City/Municipality</option>';

        municipalitiesAndCities.forEach(cityOrMunicipality => {
            let option = document.createElement('option');
            option.value = cityOrMunicipality.code;
            option.textContent = cityOrMunicipality.name;
            cityDropdown.appendChild(option);
        });

        // Clear barangay dropdown when province changes
        document.getElementById('barangay').innerHTML = '<option value="">Select Barangay</option>';
        document.getElementById('barangayText').value = ''; // Clear hidden barangay text
    });

    // Event listener for City/Municipality selection
    document.getElementById('city').addEventListener('change', async function () {
        const municipalityCode = this.value;
        const municipalityText = this.options[this.selectedIndex].text; // Get the selected municipality text
        document.getElementById('cityText').value = municipalityText; // Set hidden input value

        const addresses = await loadAddressData();

        // Filter barangays based on selected city/municipality code
        const barangays = sortAddresses(addresses.filter(
            address => address.level === 'Bgy' && address.code.startsWith(municipalityCode.slice(0, 6))
        ));

        const barangayDropdown = document.getElementById('barangay');
        barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';

        barangays.forEach(barangay => {
            let option = document.createElement('option');
            option.value = barangay.code;
            option.textContent = barangay.name;
            barangayDropdown.appendChild(option);
        });
    });

    // Event listener for Barangay selection
    document.getElementById('barangay').addEventListener('change', function () {
        const barangayText = this.options[this.selectedIndex].text; // Get the selected barangay text
        document.getElementById('barangayText').value = barangayText; // Set hidden input value
    });
});

// Initialize provinces on page load
populateProvinces();


    
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
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }

  var modal = document.getElementById("myModal");
    var closeButton = document.querySelector(".close");

    // When the page loads, display the modal if QR code is generated
    window.onload = function () {
        <?php if(isset($_REQUEST['sbt-btn'])) { ?>
            modal.style.display = "block";
        <?php } ?>
    };

    // Close the modal when the close button is clicked
    closeButton.onclick = function () {
        modal.style.display = "none";
    };

    // Close the modal when clicking outside the modal
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

     // Function to print QR Code
function printQR() {
    var logoSrc = 'pics/bangketicket.png'; // Path to your logo image
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print QR Code</title></head><body style="text-align:center;">');

    // Modify the size of the logo using inline CSS styles
    printWindow.document.write('<img src="' + logoSrc + '" alt="Logo" style="display:block; margin: 20px auto; max-width: 200px; width: 100%;">');

    // Optional: Add any other content you want to print, but exclude the QR Code
    printWindow.document.write('<h2>Vendor Registration Details</h2>');
    printWindow.document.write('<p>Thank you for registering as a vendor. You will receive your QR code separately.</p>');

    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Auto capitalize first letter of first and last names
document.getElementById("firstName").addEventListener("input", function() {
  this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
});

document.getElementById("lastName").addEventListener("input", function() {
  this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
});
document.getElementById("address").addEventListener("input", function() {
  var words = this.value.split(" ");
  for (var i = 0; i < words.length; i++) {
    words[i] = words[i].charAt(0).toUpperCase() + words[i].slice(1).toLowerCase();
  }
  this.value = words.join(" ");
});

// Validate contact number
document.getElementById("userInfoForm").addEventListener("submit", function(event) {
  var contactNo = document.getElementById("contactNo").value;
  if (contactNo.length !== 11) {
    alert("Contact number must be exactly 11 digits.");
    event.preventDefault();
  }
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
