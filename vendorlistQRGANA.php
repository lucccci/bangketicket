<?php
include "config.php";

// Fetch user data from the database
$sql = "SELECT * FROM vendorlists"; // Replace 'vendorlists' with the actual table name
$result = $conn->query($sql);

// Check if there are any users
if ($result->num_rows > 0) {
    $cust = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $cust = array(); 
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['submit_edit'])) {
        $edit_vendor_id = $_POST['vendor_id_to_edit'];
        $edit_fName = $_POST['edit_fName'];
        $edit_lName = $_POST['edit_lName'];
        $edit_age = $_POST['edit_age'];
        $edit_birthday = $_POST['edit_birthday'];
        $edit_address = $_POST['edit_address'];
        $edit_contactNo = $_POST['edit_contactNo'];

        // Update the vendor details in the database
        $update_sql = "UPDATE vendorlists SET fName='$edit_fName', lName='$edit_lName', age='$edit_age', birthday='$edit_birthday', address='$edit_address', contactNo='$edit_contactNo' WHERE vendorID=$edit_vendor_id";
        $update_result = $conn->query($update_sql);

        if ($update_result === TRUE) {
            // Generate a new QR code for the updated vendor
            include "generate_qr.php";
            generateQR($edit_vendor_id); // Ensure this function generates and saves the new QR code

            echo "<script>alert('Vendor details and QR code updated successfully.');</script>";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        } else {
            echo "<script>alert('Error updating vendor details: " . $conn->error . "');</script>";
        }
    } elseif (isset($_POST['cust_id_to_delete'])) {
        $cust_id_to_delete = $_POST['cust_id_to_delete'];
        $delete_sql = "DELETE FROM vendorlists WHERE vendorID = $cust_id_to_delete";
        $delete_result = $conn->query($delete_sql);

        if ($delete_result === TRUE) {
            echo "<script>alert('Vendor deleted successfully.');</script>";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        } else {
            echo "<script>alert('Error deleting user: " . $conn->error . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="menuheader.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Vendor</title>
  <style>
    body {
      background-color: #F2F7FC;
    }
    .dropdown-content {
      display: none;
      background-color: #fefcfc;
      position: relative;
    }
    .dropdown-content a {
      padding-left: 30px;
    }
    .dropdown:hover .dropdown-content {
      display: block;
    }
    /* CSS for edit modal */
    .modal {
      display: none; /* Hidden by default */
      position: fixed; /* Stay in place */
      z-index: 1000; /* Sit on top */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%; /* Full height */
      overflow: auto; /* Enable scroll if needed */
      background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto; /* 15% from the top and centered */
      padding: 20px;
      border: 1px solid #888;
      width: 80%; /* Could be more or less, depending on screen size */
      max-width: 400px;
      border-radius: 10px;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }
    .modal-content h2 {
      margin-bottom: 20px;
    }
    .modal-content form label {
      display: block;
      margin-bottom: 5px;
    }
    .modal-content form input[type="text"],
    .modal-content form input[type="number"],
    .modal-content form input[type="date"] {
      width: calc(100% - 12px); /* Adjusted for padding */
      padding: 8px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 3px;
    }
    .modal-content form input[type="submit"] {
      background-color: #031F4E;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
    }
    .modal-content form input[type="submit"]:hover {
      background-color: #0056b3;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }
    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }
    .main-content {
      background-color: #F2F7FC;
      padding: 20px;
      margin-left: 250px;
      height: 100vh;
      box-sizing: border-box;
    }
    .panel {
      margin-top: 80px;
      background-color: #ffffff;
      padding: 20px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .usersTable {
      border-collapse: collapse;
      margin: 25px 0;
      font-size: 0.9em;
      width: 100%;
      border-radius: 5px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    }
    .usersTable thead tr {
      background-color: #031F4E;
      color: #ffffff;
      text-align: left;
      font-weight: bold;
      white-space: nowrap; /* Ensure the text is in a single line */
    }
    .usersTable th,
    .usersTable td {
      padding: 15px 15px;
      white-space: nowrap; /* Ensure the text is in a single line */
    }
    .usersTable tbody tr {
      border-bottom: 2px solid #dddddd;
      background-color: #ffffff;
    }
    .action-view, .action-edit, .action-delete {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1rem;
      color: #007bff;
      margin-right: 5px;
    }
    .action-view:hover, .action-edit:hover, .action-delete:hover {
      color: #0056b3;
    }
    .action-view {
      background: none;
      border: 1px solid #031F4E; /* Border color */
      cursor: pointer;
      font-size: 12px;
      color: #007bff; /* Text color */
      padding: 8px 16px; /* Adjust padding as needed */
      border-radius: 5px; /* Rounded corners */
      transition: background-color 0.3s, color 0.3s, border-color 0.3s; /* Smooth transition */
    }
    .action-view:hover {
      background-color: #031F4E; /* Background color on hover */
      color: #fff; /* Text color on hover */
    }
    #qrModalContent {
      max-width: 15%; /* Set the maximum width */
      margin: 50 auto; /* Center the content horizontally */
      padding: 50px; /* Add padding for better appearance */
    }
    /* Adjust the size of the QR code image */
    #qrModalContent img {
      max-width: 100%; /* Ensure the image fits within the container */
      height: auto; /* Maintain aspect ratio */
    }
  </style>
</head>
<body>

<div class="header-panel">
  <div class="notification"><i class="fas fa-bell"></i></div>
  <div class="profile-container" onclick="toggleDropdown()">
    <img src="pics/admin.jfif" alt="Profile Picture" class="profile-picture">
    <!-- Dropdown menu for profile picture -->
    <div class="dropdown-menu">
      <a href="#">View Profile</a>
      <a href="index.html">Logout</a>
    </div>
  </div>
</div>

<div class="overlay" onclick="toggleDropdown()"></div>

<div class="menu-toggle" onclick="toggleMenu()">☰</div>

<div id="sideMenu" class="side-menu">
  <div class="logo">
    <img src="pics/logo.png" alt="Logo">
  </div>
  <a href="dashboard.php">
    <i class="fas fa-home"></i> Dashboard
  </a>
  <a href="add_vendor.php">
    <i class="fas fa-user-plus"></i> Add Vendor
  </a>
  <a href="vendorlist.php">
    <i class="fas fa-users"></i> Vendor List
  </a>
  <a href="logout.php">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</div>

<div class="main-content">
  <div class="panel">
    <h2>Vendor List</h2>
    <table class="usersTable">
      <thead>
        <tr>
          <th>Vendor ID</th>
          <th>First Name</th>
          <th>Last Name</th>
          <th>Age</th>
          <th>Birthday</th>
          <th>Address</th>
          <th>Contact No.</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cust as $vendor) : ?>
          <tr>
            <td><?php echo $vendor['vendorID']; ?></td>
            <td><?php echo $vendor['fName']; ?></td>
            <td><?php echo $vendor['lName']; ?></td>
            <td><?php echo $vendor['age']; ?></td>
            <td><?php echo $vendor['birthday']; ?></td>
            <td><?php echo $vendor['address']; ?></td>
            <td><?php echo $vendor['contactNo']; ?></td>
            <td>
              <button class="action-view" onclick="openQRModal(<?php echo $vendor['vendorID']; ?>)">View QR</button>
              <button class="action-edit" onclick="openEditModal(<?php echo $vendor['vendorID']; ?>)">
                  <i class="fas fa-edit"></i>
              </button>
              <form method="POST" style="display:inline;">
                  <input type="hidden" name="cust_id_to_delete" value="<?php echo $vendor['vendorID']; ?>">
                  <button class="action-delete" type="submit">
                      <i class="fas fa-archive"></i>
                  </button>
              </form>
          </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Vendor</h2>
    <form method="POST">
      <input type="hidden" name="vendor_id_to_edit" id="edit_vendor_id">
      <label for="edit_fName">First Name:</label>
      <input type="text" name="edit_fName" id="edit_fName" required>
      <label for="edit_lName">Last Name:</label>
      <input type="text" name="edit_lName" id="edit_lName" required>
      <label for="edit_age">Age:</label>
      <input type="number" name="edit_age" id="edit_age" required>
      <label for="edit_birthday">Birthday:</label>
      <input type="date" name="edit_birthday" id="edit_birthday" required>
      <label for="edit_address">Address:</label>
      <input type="text" name="edit_address" id="edit_address" required>
      <label for="edit_contactNo">Contact No.:</label>
      <input type="text" name="edit_contactNo" id="edit_contactNo" required>
      <input type="submit" name="submit_edit" value="Save Changes">
    </form>
  </div>
</div>

<div id="qrModal" class="modal">
  <div class="modal-content" id="qrModalContent">
  </div>
</div>

<script>


function toggleDropdown() {
    var dropdownMenu = document.querySelector(".dropdown-menu");
    var overlay = document.querySelector(".overlay");
    dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
    overlay.style.display = dropdownMenu.style.display === "block" ? "block" : "none";
}

function openEditModal(vendorID) {
    var modal = document.getElementById("editModal");
    modal.style.display = "block";

    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                var vendor = JSON.parse(xhr.responseText);
                document.getElementById('edit_vendor_id').value = vendor['vendorID'];
                document.getElementById('edit_fName').value = vendor['fName'];
                document.getElementById('edit_lName').value = vendor['lName'];
                document.getElementById('edit_age').value = vendor['age'];
                document.getElementById('edit_birthday').value = vendor['birthday'];
                document.getElementById('edit_address').value = vendor['address'];
                document.getElementById('edit_contactNo').value = vendor['contactNo'];
            } else {
                console.error(xhr.statusText);
            }
        }
    };
    xhr.open("GET", "get_vendor.php?vendorID=" + vendorID, true);
    xhr.send();
}

function closeEditModal() {
    var modal = document.getElementById("editModal");
    modal.style.display = "none";
}

function openQRModal(vendorID) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                var modalContent = document.getElementById("qrModalContent");
                modalContent.innerHTML = xhr.responseText;
                var qrModal = document.getElementById("qrModal");
                qrModal.style.display = "block";
                var viewQRButton = document.querySelector('.action-view[data-id="' + vendorID + '"]');
                if (viewQRButton) {
                    viewQRButton.style.display = "none";
                }
                window.onclick = function(event) {
                    if (event.target == qrModal) {
                        qrModal.style.display = "none";
                        if (viewQRButton) {
                            viewQRButton.style.display = "block";
                        }
                    }
                };
            } else {
                console.error(xhr.statusText);
            }
        }
    };
    xhr.open("GET", "generate_qr.php?vendorID=" + vendorID, true);
    xhr.send();
}
</script>

</body>
</html>
