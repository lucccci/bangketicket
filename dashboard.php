<?php
    include 'config.php';
    include 'session_check.php';
    
    // Fetch admin details
    $sql = "SELECT profile_pic FROM admin_account LIMIT 1";
    $result = $conn->query($sql);
    $admin = $result->fetch_assoc();
    $defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path
    $adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;
    
    // Calculate last Monday and the following Saturday
    $lastMonday = date('Y-m-d', strtotime('last monday'));
    $nextSaturday = date('Y-m-d', strtotime('next saturday'));
    
    // Fetch weekly revenue data with formatted date for the specified range
    $revenueSql = "SELECT DATE_FORMAT(date, '%M %d, %Y') AS transaction_day, SUM(amount) AS total_revenue
                    FROM vendor_transaction
                    WHERE date >= '$lastMonday' AND date <= '$nextSaturday'
                    AND DAYOFWEEK(date) BETWEEN 2 AND 7  -- Monday to Saturday
                    GROUP BY DATE(date)
                    ORDER BY DATE(date)";
    
    $revenueResult = $conn->query($revenueSql);
    
    $dates = [];
    $revenues = [];
    
    while ($row = $revenueResult->fetch_assoc()) {
        $dates[] = $row['transaction_day'];
        $revenues[] = $row['total_revenue'];
    }
    
// Check if recent transactions are requested
if (isset($_GET['recent_transactions'])) {
    // Fetch vendors with their status and today's transaction IDs
    $sql = "SELECT vendor_list.*, 
            CONCAT(vendor_list.fname, ' ', vendor_list.mname, ' ', vendor_list.lname) AS fullname,
            GROUP_CONCAT(vendor_transaction.transactionID) AS transaction_ids,
            CASE 
                WHEN COUNT(vendor_transaction.vendorID) > 0 THEN 'Paid'
                ELSE 'Unpaid'
            END AS status
            FROM vendor_list 
            LEFT JOIN vendor_transaction 
            ON vendor_list.vendorID = vendor_transaction.vendorID
            AND DATE(vendor_transaction.date) = CURDATE() -- Only consider today's transactions
            WHERE vendor_list.archived_at IS NULL -- Exclude archived vendors
            GROUP BY vendor_list.vendorID
            HAVING status = 'Paid'"; // Only include vendors with 'Paid' status

    $vendorResult = $conn->query($sql);

    $vendors = [];
    if ($vendorResult->num_rows > 0) {
        while ($row = $vendorResult->fetch_assoc()) {
            $vendors[] = [
                'vendorID' => htmlspecialchars($row['vendorID']),
                'fullname' => htmlspecialchars($row['fullname']),
                'transaction_ids' => htmlspecialchars($row['transaction_ids']) ?: 'No Transactions',
                'status' => htmlspecialchars($row['status'])
            ];
        }
    }

    echo json_encode($vendors); // Return JSON response
    exit; // End the script to prevent the rest of the page from rendering
}

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <script src="https://kit.fontawesome.com/7d64038428.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link rel="icon" href="pics/logo-bt.png">
        <link rel="stylesheet" href="menuheaderDB.css">
        <link rel="stylesheet" href="logo.css">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
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
    
            .header-title {
                font-size: 24px; /* Adjust font size as needed */
                font-weight: 600; /* Bold text */
            }
    
            .user-icon {
                width: 40px; /* Set a fixed width for the icon */
                height: 40px; /* Set a fixed height for the icon */
                border-radius: 50%; /* Makes the icon circular */
                margin-left: 55%; /* Aligns the icon in the header */
                transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for the hover effect */
            }
    
            .user-icon:hover {
                transform: scale(1.1); /* Slightly increase the size of the icon on hover */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); /* Adds a shadow effect on hover */
            }
    
            .main {
                overflow-y: hidden;
                overflow-x: hidden;
                position: absolute;
                margin-top:2%;/* Pushes the main content below the header */
                left: 260px;
                width: calc(100% - 260px);
                background: #F2F7FC;
                min-height: calc(100vh - 60px); /* Full height minus header */
                padding-top: 20px; /* Add top padding */
                display: flex;
                flex-direction: column; /* Stack welcome message and cards vertically */
            }
            .welcome-message {
                margin-left:2%;
                margin-top:2%;
                font-size: 24px;
                overflow: hidden; /* Ensures the text doesn't overflow during the animation */
                border-right: 3px solid #031F4E; /* Cursor effect while typing */
                white-space: nowrap; /* Keeps the text on one line */
                animation:  typewriter 4s steps(22) 1s 1 normal both, /* Typewriting effect */
                hideCursor 0s 1s forwards; /* Hides the cursor once typing is done */
            }
            @keyframes typewriter {
                from { width: 0; }
                to { width: 100%; }
            }
            @keyframes hideCursor {
                to { border-right: none; } /* This will remove the cursor after typing is done */
            }
    
            .cards {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                grid-gap: 20px;
                padding: 20px 40px; /* Balanced padding for better alignment */
                margin-top: 0%; /* Remove the extra space at the top */
            }
    
            .cards .card {
                padding: 30px;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 7px 25px 0 rgba(0, 0, 0, 0.08);
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: background-color 0.3s ease;
                min-width: 220px;
                min-height: 120px; /* Increase minimum height */
                border: 1px solid #dcdcdc; /* Add a subtle border for structure */
                cursor: pointer;
            }
    
            .cards .card:hover {
                background-color: #031F4E;
            }
    
            .cards .card:hover .number, 
            .cards .card:hover .card-name, 
            .cards .card:hover .icon-box {
                color: #fff;
            }
    
            .number {
                font-size: 35px;
                font-weight: 500;
                color: #031F4E;
            }
    
            .card-name {
                color: #888;
                font-weight: 600;
            }
    
            .icon-box {
                font-size: 45px;
                color: #031F4E;
            }
    
            /* Responsive styles */
            @media (max-width: 1115px) {
                .side-menu {
                    width: 60px;
                }
                .main {
                    left: 60px;
                    width: calc(100% - 60px);
                }
                .header-panel {
                    left: 60px; /* Align header with the narrow sidebar */
                    width: calc(100% - 60px); /* Adjust header width */
                }
                .cards {
                    grid-template-columns: repeat(2, 1fr); /* Adjust grid layout on smaller screens */
                }
            }
    
            @media (max-width: 768px) {
                .cards {
                    grid-template-columns: 1fr; /* Single column layout for smaller screens */
                }
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
            .chart-container {
                width: 100%; /* Full width */
                max-width: 50%; /* Maximum width */
                margin: 20px 40px; /* Center align */
            }
    
    .vendor-table {
        max-width: 80%;
        max-height: 425px;
        margin-top: -13px;
        flex: 0 0 auto;
        border-collapse: collapse;
        font-family: 'Poppins', sans-serif;
    }
    
    .vendor-table h3 {
        margin-bottom: 10px;
        color: #031F4E; /* Header color */
    }
    
    .table-container {
        max-height: 100%; /* Set a maximum height */
        overflow-y: auto; /* Enable vertical scrolling */
    }
    
    .vendor-table table {
        width: 100%; /* Full width */
        border-collapse: collapse; /* Ensures borders collapse */
        background-color: #fff; /* Background color for the table */
    }
    
    .vendor-table th, .vendor-table td {
        border: 1px solid #ddd; /* Light gray border */
        padding: 10px; /* Padding inside cells */
        text-align: left; /* Align text to the left */
    }
    
    .table-container {
        max-height: 400px; /* Set a maximum height for scrolling */
        overflow-y: auto; /* Enable vertical scrolling */
        margin-top: 10px; /* Add space above the table to prevent overlap */
    }
    
    /* Header styles */
    .vendor-table th {
        position: sticky; /* Makes the header stick to the top */
        top: 0; /* Position it at the top of the container */
        background-color: #031F4E; /* Background color for the header */
        color: #fff; /* Text color for the header */
        z-index: 10; /* Ensure the header is above other content */
        font-size: .8rem;
    }
    
    /* Adding padding to the table data to avoid overlap with the header */
    .vendor-table td {
        padding: 10px; /* Padding inside cells */
        font-size: .8rem;
    }
    
    
    
    .vendor-table tr:hover {
        background-color: #d9e6ff; /* Highlight row on hover */
    }
    
    
        </style>
    </head>
    <body>
    
        <div id="sideMenu" class="side-menu">
            <div class="logo">
                <img src="pics/logo.png" alt="Logo">
            </div>
    
            <a href="dashboard.php" class="active">
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
            <a href="contactus.php">
    <i class="fas fa-envelope" style="vertical-align: middle; font-size: 18px;"></i>
    <span style="margin-left: 2px;">Contact Us</span>
</a>

        <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
        </div>
    
        <div class="header-panel">
            <div class="header-title"></div>
            <a href="admin_profile.php">
                <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
            </a>
        </div>
    
        <div class="main">
            <div class="welcome-message">
                <h2>Welcome, Admin!</h2>
            </div>
            <script>
                window.onload = function() {
                    const welcomeText = document.getElementById('welcome-text');
                
                    // Set the width to trigger the typewriter effect
                    welcomeText.style.width = '100%'; // Set width to 100% for the typewriter effect to show
                
                    // Set a timeout to remove the blinking cursor after typing effect
                    setTimeout(() => {
                        welcomeText.style.borderRight = 'none'; // Remove the cursor after 4 seconds
                    }, 4000); // Change this duration to match your typing animation time
                };
            </script>
    
            <div class="cards">
        <a href="vendorlist.php" style="text-decoration: none; color: inherit;">
            <div class="card" style="cursor: pointer;">
                <div class="card-content">
                    <div class="number" id="registered-vendors">0</div>
                    <div class="card-name">Registered Vendors</div>
                </div>
                <div class="icon-box">
                    <span class="material-icons" style="font-size: 45px;">how_to_reg</span>
                </div>
            </div>
        </a>
    
        <a href="collection.php" style="text-decoration: none; color: inherit;">
            <div class="card" style="cursor: pointer;">
                <div class="card-content">
                    <div class="number" id="active-vendors">0</div>
                    <div class="card-name">Active Vendors</div>
                </div>
                <div class="icon-box">
                    <span class="material-icons" style="font-size: 45px;">people</span>
                </div>
            </div>
        </a>
    
        <div class="card">
            <div class="card-content">
                <div class="number" id="avg-revenue">loading</div>
                <div class="card-name">Avg. Revenue / Day</div>
            </div>
            <div class="icon-box">
                <span class="material-icons" style="font-size: 45px;">account_balance_wallet</span>
            </div>
        </div>
    
        <!-- Updated Today's Revenue Card -->
        <a href="transaction.php" style="text-decoration: none; color: inherit;">
            <div class="card" style="cursor: pointer;">
                <div class="card-content">
                    <div class="number" id="todays-revenue">loading</div>
                    <div class="card-name">Today's Revenue</div>
                </div>
                <div class="icon-box">
                    <span class="material-icons" style="font-size: 45px;">wallet</span>
                </div>
            </div>
        </a>
    </div>
    <div class="content-container" style="display: flex; margin-top: 20px; margin-left: 40px;"> <!-- Added margin-left -->
    <!-- Vendor Status Table -->
    <div class="vendor-table" style="width: 45%; flex: 0 0 auto;">
        <h3>Recent Transactions</h3>
        <div class="table-container">
            <table style="border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="width: 25%;">Vendor ID</th>
                        <th style="width: 40%;">Vendor Name</th>
                        <th style="width: 20%;">Transaction ID</th> <!-- Added Transaction IDs Column -->
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($vendorResult->num_rows > 0): ?>
                        <?php while ($row = $vendorResult->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($row['vendorID']); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['transaction_ids']) ?: 'No Transactions'; ?></td> <!-- Show Transaction IDs -->
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No Paid Vendors Yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    
        <!-- Weekly Revenue Chart -->
        <div class="chart-container" style="height: 400px; background-color: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); flex: 1; margin-left: 20px;">
            <canvas id="weeklyRevenueChart"></canvas>
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
        // JavaScript for click animation
        const menuLinks = document.querySelectorAll('.side-menu a');
    
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                this.style.transition = 'transform 0.1s ease-in-out';  
                this.style.transform = 'scale(0.95)';  
                
                setTimeout(() => {
                    this.style.transform = 'scale(1)';  
                }, 100);  
            });
        });
    
        // Function to fetch vendor count and update the HTML
        function fetchVendorCount() {
            fetch('getVendorCount.php')  
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('registered-vendors').textContent = data;
                })
                .catch(error => console.error('Error fetching vendor count:', error));
        }
    
        // Function to fetch today's revenue
        function fetchTodaysRevenue() {
            fetch('getTodaysRevenue.php')  
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    let todaysRevenue = parseFloat(data);  
                    document.getElementById('todays-revenue').textContent = `₱${todaysRevenue.toFixed(2)}`;
                })
                .catch(error => {
                    console.error('Error fetching today\'s revenue:', error);
                    document.getElementById('todays-revenue').textContent = "Error loading data";
                });
        }
    
        // Function to fetch average revenue per day
        function fetchAvgRevenue() {
            fetch('getAvgRevenue.php')  
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    let avgRevenue = parseFloat(data);  
                    document.getElementById('avg-revenue').textContent = `₱${avgRevenue.toFixed(2)}`;  // Update average revenue card
                })
                .catch(error => {
                    console.error('Error fetching average revenue:', error);
                    document.getElementById('avg-revenue').textContent = "Error";
                });
        }
    
        // Ensure all functions run on page load
        window.onload = function() {
            fetchVendorCount();
            fetchTodaysRevenue();
            fetchAvgRevenue();  // Fetch the average revenue on page load
        };
    
        // Dropdown toggle logic
        document.getElementById('vendorDropdown').addEventListener('click', function(event) {
            var dropdownContent = document.getElementById('vendorDropdownContent');
            dropdownContent.style.display = dropdownContent.style.display === 'none' || dropdownContent.style.display === '' ? 'block' : 'none';
        });
        
// Function to fetch active vendor count
function fetchActiveVendorCount() {
    fetch('getActiveVendorCount.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            document.getElementById('active-vendors').textContent = data;
        })
        .catch(error => {
            console.error('Error fetching active vendor count:', error);
            document.getElementById('active-vendors').textContent = "Error";
        });
}
        window.onload = function() {
            fetchVendorCount();
            fetchTodaysRevenue();
            fetchAvgRevenue();
            fetchActiveVendorCount(); // Ensure this is called
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
                window.location.href = 'logout.php'; // Redirect to your logout page
            }
    
            // Ensure the logout modal closes when clicking outside of it
            window.onclick = function(event) {
                var logoutModal = document.getElementById("logoutModal");
                if (event.target == logoutModal) {
                    closeLogoutModal();
                }
            };
    
    // Create the weekly revenue chart using Chart.js
    const ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
    const weeklyRevenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Weekly Revenue',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#031F4E', // Use the existing dark blue color
                backgroundColor: 'rgba(3, 31, 78, 0.3)', // Lighter version for filling
                borderWidth: 2,
                pointRadius: 5, // Adjust point size
                pointBackgroundColor: '#fff', // White background for points
                pointBorderColor: '#031F4E', // Match border color with line color
                pointBorderWidth: 2,
                tension: 0.5, // Slightly more tension for smooth curves
                fill: true, // Fill under the line
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Weekly Revenue', // Title text
                    font: {
                        size: 20,
                        weight: 'bold'
                    },
                    color: '#031F4E' // Title color
                },
                legend: {
                    display: false, // Hide the legend
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#031F4E',
                    borderWidth: 1,
                    caretPadding: 10
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(200, 200, 200, 0.5)', // Lighter gridline color
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#333',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(200, 200, 200, 0.5)', // Lighter gridline color
                    },
                    title: {
                        display: true,
                        text: 'Revenue (₱)',
                        color: '#333',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    });
    
    
    // Function to fetch recent transactions and update the table
function fetchRecentTransactions() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "dashboard.php?recent_transactions=true", true); // Adjust the file name as needed
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const vendors = JSON.parse(xhr.responseText); // Parse the JSON response
            const tbody = document.querySelector('.vendor-table tbody');
            tbody.innerHTML = ''; // Clear existing table data

            if (vendors.length > 0) {
                vendors.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="font-weight: bold;">${row.vendorID}</td>
                        <td>${row.fullname}</td>
                        <td>${row.transaction_ids}</td>
                        <td style="font-weight: bold;">${row.status}</td>
                    `;
                    tbody.appendChild(tr); // Append new rows
                });
            } else {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td colspan="4" style="text-align: center;">No Paid Vendors Yet</td>`;
                tbody.appendChild(tr);
            }
        }
    };
    xhr.send();
}

// Fetch recent transactions every 5 seconds
setInterval(fetchRecentTransactions, 5000); // Adjust the interval as needed

// Initial fetch when the page loads
window.onload = function() {
    fetchVendorCount();
    fetchTodaysRevenue();
    fetchAvgRevenue();  // Fetch the average revenue on page load
    fetchRecentTransactions(); // Fetch initial data for recent transactions
    fetchActiveVendorCount(); // Ensure this is called
        };
    
    
    </script>
    
    
    
        </script>
    
    </body>
    </html>
