<?php
include 'config.php';

// Fetch admin details
$sql = "SELECT admin_id, username, email, password, profile_pic FROM admin_account LIMIT 1";
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$adminId = $admin['admin_id'];
$adminName = $admin['username'];
$adminEmail = $admin['email'];
$adminPassword = $admin['password'];
$defaultProfilePic = 'uploads/9131529.png'; // Default profile picture path

// If the admin's profile picture is not set or empty, use the default
$adminProfilePic = !empty($admin['profile_pic']) ? $admin['profile_pic'] : $defaultProfilePic;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['saveChanges'])) {
        // Update username, email, and password
        $newUsername = $_POST['username'];
        $newEmail = $_POST['email'];
        $newPassword = $_POST['password'];

        $stmt = $conn->prepare("UPDATE admin_account SET username = ?, email = ?, password = ? WHERE admin_id = ?");
        $stmt->bind_param("sssi", $newUsername, $newEmail, $newPassword, $adminId);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_profile.php?saved=1");
        exit();
    } elseif (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $uploadDir = 'uploads/';
        $fileName = basename($_FILES['profilePic']['name']);
        $targetFilePath = $uploadDir . $fileName;

        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        if (in_array($fileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            if (move_uploaded_file($_FILES['profilePic']['tmp_name'], $targetFilePath)) {
                if ($adminProfilePic != $defaultProfilePic && file_exists($adminProfilePic)) {
                    unlink($adminProfilePic);
                }

                $stmt = $conn->prepare("UPDATE admin_account SET profile_pic = ? WHERE admin_id = ?");
                $stmt->bind_param("si", $targetFilePath, $adminId);
                $stmt->execute();
                $stmt->close();
                
                header("Location: admin_profile.php?saved=1");
                exit();
            }
        } else {
            echo "Invalid file type. Please upload an image file.";
        }
    } elseif (isset($_POST['cameraUpload']) && isset($_POST['imageData'])) {
        $data = $_POST['imageData'];
        $imageName = "uploads/captured_" . time() . ".png";
        
        $imageData = explode(",", $data)[1];
        $decodedData = base64_decode($imageData);
        file_put_contents($imageName, $decodedData);
        
        if ($adminProfilePic != $defaultProfilePic && file_exists($adminProfilePic)) {
            unlink($adminProfilePic);
        }

        $stmt = $conn->prepare("UPDATE admin_account SET profile_pic = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $imageName, $adminId);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin_profile.php?saved=1");
        exit();
    } elseif (isset($_POST['deletePic'])) {
        // Handle delete profile picture
        if ($adminProfilePic != $defaultProfilePic && file_exists($adminProfilePic)) {
            unlink($adminProfilePic); // Remove the file from the filesystem
        }

        // Update the database to set the default profile picture path
        $stmt = $conn->prepare("UPDATE admin_account SET profile_pic = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $defaultProfilePic, $adminId);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin_profile.php?saved=1");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Admin Profile</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
          * {
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

       
        body {
            font-family: 'Poppins', sans-serif;
                background-color: #F2F7FC;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow-y:hidden;
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
                margin-top:8%;
                margin-bottom:20%;
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
        .profile-container {
            margin-left:13%;
            color: #031F4E;
            background: #fff;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 500px;
            text-align: center;
        }
.profile-pic {
    
    border-radius: 50%; /* Make the image circular */
    width: 100px; /* Set width */
    height: 100px; /* Set height */
    object-fit: cover; /* Ensure the image covers the circle without stretching */
    margin-bottom: 10px;
    cursor: pointer;
    border: 2px solid #031F4E;
    transition: transform 0.3s, box-shadow 0.3s; /* Add transition for smooth effect */
    background-color: #f0f0f0; /* Light gray background */
    background-image: url('uploads/9131529.png'); /* Default profile image */
    background-size: cover; /* Ensure background covers the circle */
    background-position: center; /* Center the image */
    display: flex;
    align-items: center;
    justify-content: center;
    color: #031F4E;
    font-size: 14px;
       margin-left:37%;
        font-size: 0; /* Hide alt text */
}

/* Style when no image is loaded */
.profile-pic img {
    display: none; /* Hide broken image icon */
}

.profile-pic:hover {
    transform: scale(1.1); /* Slightly increase the size */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Add shadow for depth */
}
/* Styles for the dropdown menu */
.dropdown-menu {
    display: none;
    position: absolute;
    top: 140px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #ffffff; /* White background */
    border: 1px solid #ccc; /* Light gray border */
    border-radius: 5px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
    width: 150px; /* Width of the dropdown */
    z-index: 1000;
    overflow: hidden;
}

/* Show the dropdown when active */
.dropdown-menu.show {
    display: block;
}

/* Style each button in the dropdown */
.dropdown-menu button {
    background: none;
    border: none;
    padding: 10px;
    width: 100%;
    text-align: left;
    cursor: pointer;
    font-size: 14px;
    color: #333; /* Dark text */
    transition: background-color 0.2s, color 0.2s; /* Smooth transitions */
}

/* Hover effect for buttons */
.dropdown-menu button:hover {
    background-color: #f0f0f0; /* Light gray background on hover */
    color: #031F4E; /* Dark blue text on hover */
}

/* Ensure dropdown items are separated */
.dropdown-menu button + button {
    border-top: 1px solid #e0e0e0; /* Light divider between items */
}

        .hidden-input {
            display: none;
        }
       /* Styles for the video element */
video {
    display: none; /* Initially hidden */
    margin-top: 15px; /* Space above the video */
    border-radius: 5px; /* Rounded corners */
    width: 100%; /* Full width */
}

/* Styles for the canvas element used for capturing the photo */
canvas {
    display: none; /* Initially hidden */
    margin-top: 15px; /* Space above the canvas */
    border-radius: 5px; /* Rounded corners */
    width: 100%; /* Full width */
}
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
       
        button {
    background-color: #031F4E; /* Default button color */
    color: white;
    padding: 10px 15px; /* Padding for button */
    border: none;
    border-radius: 5px;
    cursor: pointer;
    height: 50px; /* Fixed height */
}
        button:hover {
            background-color: #0056b3;
        }
        .capture-btn {
            height: 50px; /* Fixed height */
            background-color: #031F4E; /* Default button color */
    color: white; /* White text */
    padding: 10px 20px; /* Padding for the button */
    border: none; /* No border */
    border-radius: 5px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor */
    display: none; /* Initially hidden */
    margin-top: 15px; /* Space above the button */
    margin-left:33%;
    transition: background-color 0.3s ease; /* Smooth background transition */
}

.capture-btn:hover {
    background-color: #218838; /* Darker green on hover */
}

.capture-btn:active {
    background-color: #1e7e34; /* Even darker green on click */
}
.upload-btn {
    height: 50px; /* Fixed height, same as capture button */
    background-color: #031F4E; /* Default button color, same as capture button */
    color: white; /* White text */
    padding: 10px 20px; /* Consistent padding */
    border: none; /* No border */
    border-radius: 5px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor */
    display: none; /* Initially hidden */
    margin-top: 15px; /* Space above the button */
    margin-left: 33%; /* Align similarly to capture button */
    transition: background-color 0.3s ease; /* Smooth background transition */
}

.upload-btn:hover {
    background-color: #218838; /* Darker green on hover, consistent with capture button */
}

.upload-btn:active {
    background-color: #1e7e34; /* Even darker green on click, consistent with capture button */
}

        .delete-btn {
            background-color: red;
        }
      /* Modal styles */
      .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.4); 
    }
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; 
        padding: 20px;
        border: 1px solid #888;
        width: 80%; 
        max-width: 300px;
        text-align: center;
        border-radius: 5px;
        position: relative;
    }
    .close {
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 10px;
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
        input[type="password"] {
    width: 100%; /* Leave space for the icon */
    padding: 10px;
    margin: 5px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
    position: relative; /* Positioning context for the icon */
}

.password-icon {
    position: absolute;
    right: 5px; /* Adjust positioning */
    top: 65%; /* Center vertically */
    transform: translateY(-50%); /* Adjust to center */
    color: #007BFF; /* Default icon color */
    cursor: pointer; /* Change cursor to pointer */
    transition: color 0.3s ease; /* Smooth transition for color change */
}


.password-icon:hover {
    color: #0056b3; /* Change color on hover */

}
.home-icon {
    width: 30px; /* Adjust the width as needed */
    height: 30px; /* Adjust the height as needed */
    display: block; /* Ensures it's a block element */
    margin: 0 auto 15px; /* Centers the icon and adds space below */
}
   /* Modal styles */
   .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 300px;
            text-align: center;
            border-radius: 5px;
            position: relative;
        }
        .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 10px;
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
        
        <!-- Log Out Link -->
    <a href="#" class="logout" onclick="openLogoutModal()"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>

  <div class="header-panel">
    <div class="header-title"></div>
    <a href="admin_profile.php">
        <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="User Icon" class="user-icon" onerror="this.src='uploads/9131529.png'">
    </a>
</div>
    
    
<div class="profile-container">
    <a href="dashboard.php">
        <img src="pics/icons8-home.gif" alt="Home Icon" class="home-icon">
    </a>
    <h2>Admin Profile</h2>
    <form method="POST" enctype="multipart/form-data" id="profileForm">
    <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" class="profile-pic" id="profilePic" alt="Profile Picture">


        <div class="dropdown-menu" id="dropdownMenu">
            <button type="button" onclick="document.getElementById('fileInput').click();">Upload Picture</button>
            <button type="button" onclick="openCamera()">Take a Photo</button>
        </div>
        <input type="file" name="profilePic" id="fileInput" accept="image/*" class="hidden-input" onchange="document.getElementById('profileForm').submit();">
        <input type="hidden" name="imageData" id="imageData">
        <input type="hidden" name="cameraUpload" value="1">
        <video id="video" autoplay></video>
        <canvas id="canvas" style="display: none;"></canvas>
        <button type="button" class="capture-btn" id="captureBtn" onclick="capturePhoto()">Capture Photo</button><br>
<button type="submit" id="uploadBtn" class="upload-btn">Upload Photo</button>


        <div>
            <strong>Username:</strong>
            <input type="text" name="username" value="<?php echo htmlspecialchars($adminName); ?>">
        </div>
        <div>
            <strong>Email:</strong>
            <input type="email" name="email" value="<?php echo htmlspecialchars($adminEmail); ?>">
        </div>
        <div style="position: relative;">
            <strong>Password:</strong>
            <input type="password" name="password" value="<?php echo htmlspecialchars($adminPassword); ?>" id="passwordInput">
            <span class="material-icons password-icon" onclick="togglePasswordVisibility()">visibility_off</span>
        </div>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
            <button type="submit" name="saveChanges" style="flex: 1;">Save Changes</button>
            <button type="submit" name="deletePic" class="delete-btn" style="flex: 1;">Remove Picture</button>
        </div>
    </form>
</div>

<!-- Modal for success message -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <img src="pics/checkk.gif" alt="Success" style="width: 50px; height: 50px; margin-bottom: 10px;">
        <p>Changes saved successfully!</p>
    </div>
</div>



<script>
window.onload = function() {
    // Check if the URL contains '?saved=1'
    if (window.location.search.includes('saved=1')) {
        const modal = document.getElementById('successModal');
        modal.style.display = 'block';

        // Automatically close the modal after 3 seconds
        setTimeout(() => {
            modal.style.display = 'none';
        }, 3000);

        // Close the modal when the user clicks the close button
        const closeButton = document.querySelector('.close');
        closeButton.onclick = function() {
            modal.style.display = 'none';
        };

        // Close the modal when the user clicks outside of the modal
        window.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdownMenu');
            if (event.target == modal) {
                modal.style.display = 'none';
            } else if (!event.target.matches('#profilePic')) {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }
};
    // Toggle dropdown visibility
    document.getElementById('profilePic').onclick = function() {
        const dropdown = document.getElementById('dropdownMenu');
        dropdown.classList.toggle('show');
    };

    // Close dropdown when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('#profilePic')) {
            const dropdown = document.getElementById('dropdownMenu');
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }
    };

    function openCamera() {
        const video = document.getElementById('video');
        const captureBtn = document.getElementById('captureBtn');
        
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                video.style.display = 'block';
                captureBtn.style.display = 'block';
            })
            .catch(err => {
                alert('Unable to access the camera: ' + err);
            });
    }

    function capturePhoto() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const imageData = document.getElementById('imageData');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataURL = canvas.toDataURL('image/png');
        imageData.value = dataURL;
        document.getElementById('profileForm').submit();
    }

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById("passwordInput");
        const passwordIcon = document.getElementById("passwordIcon");
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            passwordIcon.textContent = "visibility";
        } else {
            passwordInput.type = "password";
            passwordIcon.textContent = "visibility_off";
        }
    }

</script>

</body>
</html>