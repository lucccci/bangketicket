<?php
include "config.php";

// Fetch user data from the database
$sql = "SELECT * FROM products"; 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $cust = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $cust = array(); 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="pics/logo-bt.png">
    <link rel="stylesheet" href="menuheaderDB.css">
  <link rel="stylesheet" href="logo.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product</title>
  <style>

* {
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    margin: 0;
    font-family: 'Open Sans', sans-serif;
    background-color: #F2F7FC;
    position: relative;
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
    overflow-y: auto;
    transition: width 0.3s;
    padding: 2px;
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




/* Main content */
.main-content {
    position: absolute;
    top: 0;
    width: calc(100% - 260px);
    left: 260px;
    min-height: 100vh;
    background: #F2F7FC;
    padding: 20px;
    box-sizing: border-box;
}

/* Responsive */
@media (max-width: 1115px) {
    .side-menu {
        width: 60px;
    }
    .main-content {
        left: 60px;
        width: calc(100% - 60px);
    }
}

/* Dropdown Menu */
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

/* Overlay */
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



/* Panel */
.panel {
    margin-top: 80px;
    width: 100%;
    height: 100%;
    background-color: #ffffff;
    padding: 5px 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

/* Table */
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
    white-space: nowrap;
}

.usersTable th,
.usersTable td {
    padding: 12px 15px;
    white-space: nowrap;
}

.usersTable tbody tr {
    border-bottom: 2px solid #dddddd;
    background-color: #ffffff;
}

/* Hover effect on rows */
.usersTable tbody tr:hover {
    background-color: #f2f2f2;
}


  </style>
</head>
<body>

<div class="header-panel">
  </div>

  <div id="sideMenu" class="side-menu">
    <div class="logo">
      <img src="pics/logo.png" alt="Logo">
    </div>
    <a href="dashboard.html"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="product.php"class="active"><i class="fas fa-box"></i> Product</a>
    
    <!-- Dropdown for Vendors -->
    <div class="dropdown">
      <a href="vendorlist.php" id="vendorDropdown" class="dropdown-toggle"><i class="fas fa-users"></i> Vendors</a>
      <div id="vendorDropdownContent" class="dropdown-content" style="display: none;">
        <a href="vendorlist.php" id="vendorListLink" class="active"><i class="fas fa-list"></i> Vendor List</a>
        <a href="transaction.php"><i class="fas fa-dollar-sign"></i> Transactions</a>
      </div>
    </div>

    <a href="collector.php"><i class="fa fa-user-circle"></i> Collector</a>
    <a href="collection.php"><i class="fa fa-table"></i> Collection</a>
    <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
    
    <!-- Log Out Link -->
    <a href="index.html" class="logout"><i class="fas fa-sign-out-alt"></i> Log Out</a>
</div>


<div class="main-content">
  <div class="panel">
    <h2>Products</h2>
    <div class="registered-vendors">
      <table class="usersTable">
        <thead>
          <tr>
            <th>Product ID</th>
            <th>Product Type</th>
            <th>Market Fee Occupancy</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cust as $customer) : ?>
            <tr>
              <td><?php echo $customer['productID']; ?></td>
              <td><?php echo $customer['product']; ?></td>
              <td><?php echo $customer['marketFee']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <hr>
  </div>
</div>

<script>
  function toggleMenu() {
    var sideMenu = document.getElementById("sideMenu");
    var overlay = document.querySelector(".overlay");

    if (sideMenu.style.width === "260px") {
      sideMenu.style.width = "0";
      overlay.style.display = "none";
    } else {
      sideMenu.style.width = "260px";
      overlay.style.display = "block";
    }
  }

  function toggleDropdown() {
    var profileContainer = document.querySelector(".profile-container");
    var dropdownMenu = document.querySelector(".dropdown-menu");
    var overlay = document.querySelector(".overlay");

    profileContainer.classList.toggle("clicked");
    dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
    overlay.style.display = overlay.style.display === "block" ? "none" : "block";
  }
</script>

</body>
</html>