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

        /* Header Panel */
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
            height: 60px; /* Set a fixed height for the header */
            z-index: 1001; /* Stays above the sidebar */
        }

        .user-icon {
            width: 40px; /* Set a fixed width for the icon */
            height: 40px; /* Set a fixed height for the icon */
            border-radius: 50%; /* Makes the icon circular */
            margin-left: 55%;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for the hover effect */
        }

        .user-icon:hover {
            transform: scale(1.1); /* Slightly increase the size of the icon on hover */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); /* Adds a shadow effect on hover */
        }

        /* Main content */
        .main-content {
            position: absolute;
            top: 60px; /* Pushes the main content below the header */
            width: calc(100% - 260px);
            left: 260px;
            min-height: calc(100vh - 60px); /* Full height minus header */
            background: #F2F7FC;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Panel */
        .panel {
            margin-top: 20px;
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
        
        <a href="product.php" class="active">
            <span class="material-icons" style="vertical-align: middle; font-size: 18px;">payments</span>
            <span style="margin-left: 8px;">Market Fee</span>
        </a>

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
        <a href="contactus.php">
    <img src="pics/contact_icon.png" alt="Contact Us Icon" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 8px;">
    Contact Us
</a>

        
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
            <h2>Market Fee</h2>
            <div class="registered-vendors">
                <table class="usersTable">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Description/Item</th>
                            <th>Amount</th>
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
