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
    <title>Contact Us</title>
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
   /* Main content */
   .main-content {
            position: absolute;
            top: 60px;
            width: calc(100% - 260px);
            left: 260px;
            min-height: calc(100vh - 60px);
            background: #F2F7FC;
            padding: 20px;
            box-sizing: border-box;
            margin-top:-10px;
            overflow-y:hidden;
            overflow-x:hidden;
        }

        /* White Panel for Team Section */
        .team-panel {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-top: 20px;
          
        }

        .team-panel h2 {
            color: #031F4E;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        /* Centered Logo */
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            width: 200px;
            height: auto;
        }

        .team-panel {
    max-width: 1200px; /* Maximum width for the panel */
    margin: 0 auto; /* Center the panel horizontally */
    padding: 20px; /* Add padding around the panel */
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* Three columns */
    gap: 20px; /* Space between grid items */
}

.team-member {
    text-align: center; /* Center-align the content */
    padding: 10px; /* Space inside each team member box */
    background-color: #f9f9f9; /* Light background color */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

.team-member img {
    width: 100px; /* Set a fixed width for images */
    height: 100px; /* Set a fixed height for images */
    border-radius: 50%; /* Make images circular */
    margin-bottom: 10px; /* Space between image and name */
}

.team-member h3 {
    margin: 5px 0; /* Space around the name */
    font-size: 1.2em; /* Larger font size for names */
}

.role {
    font-style: italic; /* Italicize the role */
    color: #555; /* Grey color for the role text */
    font-size: 0.9em; /* Slightly smaller font size for roles */
}

p {
    font-size: 1em; /* Standard font size */
    color: #555; /* Medium grey color for readability */
    text-align: center; /* Center-align the text */
    margin-top: 20px; /* Space above the paragraph */
    margin-bottom: 20px; /* Space below the paragraph */
    line-height: 1.5; /* Increase line height for readability */
    font-style: italic; /* Italicize the text for emphasis */
    padding: 10px; /* Padding around the text */
    background-color: #f9f9f9; /* Light background color */
    border-radius: 5px; /* Rounded corners for a softer look */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
}


/* Responsive adjustments */
@media (max-width: 768px) {
    .team-grid {
        grid-template-columns: repeat(2, 1fr); /* Two columns on smaller screens */
    }
}

@media (max-width: 480px) {
    .team-grid {
        grid-template-columns: 1fr; /* Single column on very small screens */
    }
}

        .contact-email {
            margin-top:15px;
    display: inline-block;
    padding: 10px 20px;
    background-color: #031F4E; /* Blue background color */
    color: #FFFFFF; /* White text color for the text */
    text-decoration: none; /* Remove underline */
    border-radius: 5px; /* Rounded corners */
    font-weight: bold; /* Bold text */
    transition: background-color 0.3s ease, transform 0.3s ease; /* Smooth transitions */
}

.contact-email:hover {
    background-color: #0056b3; /* Darker blue on hover */
    transform: translateY(-2px); /* Slight lift effect */
}

.contact-email:active {
    background-color: #004494; /* Even darker blue when clicked */
    transform: translateY(0); /* Reset lift effect */
}

.email-address {
    color:  #6A85BB; /* Gold color for the email address */
    font-weight: bold; /* Make the email address bold */
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
        <a href="contactus.php" class="active">
    <i class="fas fa-envelope" style="vertical-align: middle; font-size: 18px;"></i>
    <span style="margin-left: 2px;">Contact Us</span>
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
      
        <div class="team-panel">
              <!-- Logo Section -->
        <div class="logo-container">
            <img src="pics/innovatrix.png" alt="Company Logo">
        </div>
    <h2>Meet Our Team</h2>
    <div class="team-grid">
        <!-- Team Member 1 -->
        <div class="team-member">
            <img src="developer/balondo.jpg" alt="Team Member 1">
            <h3>Balondo, Dave Norman</h3>
            <p class="role">Full-Stack Developer</p>
        </div>

        <!-- Team Member 2 -->
        <div class="team-member">
            <img src="developer/reyes.png" alt="Team Member 2">
            <h3>Reyes, Angelica</h3>
            <p class="role">Full-Stack Developer</p>
        </div>

        <!-- Team Member 3 -->
        <div class="team-member">
            <img src="developer/torres.png" alt="Team Member 3">
            <h3>Torres, Minette</h3>
            <p class="role">UI/UX</p>
        </div>

        <!-- Team Member 4 -->
        <div class="team-member">
            <img src="developer/mangubat.jpg" alt="Team Member 4">
            <h3>Mangubat, Juliane</h3>
            <p class="role">Project Manager</p>
        </div>

        <!-- Team Member 5 -->
        <div class="team-member">
            <img src="developer/hachero.jpg" alt="Team Member 5">
            <h3>Hachero, Carla Mae</h3>
            <p class="role">System Analysis</p>
        </div>

        <!-- Team Member 6 -->
        <div class="team-member">
            <img src="developer/marc.jpeg" alt="Team Member 6">
            <h3>Macelino, Kyla Rose</h3>
            <p class="role">Document Specialist</p>
        </div>
    </div>

    <p>For further assistance, please reach out to the support team</p>
    <!-- Centralized Email -->
<a href="https://mail.google.com/mail/?view=cm&fs=1&to=innovatrix10@gmail.com" target="_blank" class="contact-email">
    Contact us at <span class="email-address">innovatrix10@gmail.com</span>
</a>
</div>





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

    </div>

    <script>
      
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
