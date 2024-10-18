<?php
include 'config.php';

// Fetch admin details
$sql = "SELECT admin_id, username, email, password, profile_pic FROM admin_account LIMIT 1"; // Adjust as necessary
$result = $conn->query($sql);
$admin = $result->fetch_assoc();
$adminId = $admin['admin_id'];
$adminName = $admin['username'];
$adminEmail = $admin['email'];
$adminPassword = $admin['password'];
$adminProfilePic = $admin['profile_pic'] ? $admin['profile_pic'] : 'uploads/9131529.png'; // Use default profile picture if none

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profilePic'])) {
    $targetDir = "uploads/"; // Ensure this directory exists
    $targetFile = $targetDir . basename($_FILES['profilePic']['name']);
    
    // Check if the upload is successful
    if (move_uploaded_file($_FILES['profilePic']['tmp_name'], $targetFile)) {
        // Update the profile picture path in the database
        $stmt = $conn->prepare("UPDATE admin_account SET profile_pic = ? WHERE admin_id = ?");
        $stmt->bind_param("si", $targetFile, $adminId);
        $stmt->execute();
        $stmt->close();
        
        // Reload the page to reflect the new profile picture
        header("Location: admin_profile.php");
        exit();
    } else {
        $error = "Sorry, there was an error uploading your file.";
    }
}

// Handle profile update
$successMessage = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['saveChanges'])) {
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password'];

    // Update the admin's information in the database
    $stmt = $conn->prepare("UPDATE admin_account SET username = ?, email = ?, password = ? WHERE admin_id = ?");
    $stmt->bind_param("sssi", $newUsername, $newEmail, $newPassword, $adminId);
    if ($stmt->execute()) {
        $successMessage = "Profile updated successfully!";
    }
    $stmt->close();

    // Reload the page to reflect the changes after 3 seconds
    header("refresh:3;url=admin_profile.php");
}

// Handle delete profile picture
if (isset($_POST['deletePic'])) {
    // Remove the profile picture from the filesystem
    if (file_exists($adminProfilePic) && $adminProfilePic != 'uploads/default_profile.webp') {
        unlink($adminProfilePic);
    }

    // Update the database to remove the profile picture
    $stmt = $conn->prepare("UPDATE admin_account SET profile_pic = NULL WHERE admin_id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $stmt->close();
    
    // Reload the page to reflect the changes
    header("Location: admin_profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .profile-container {
            color: #031F4E;
            background: #fff;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }
        .profile-pic {
    border-radius: 50%;
    width: 100px;
    height: 100px;
    object-fit: cover;
    margin-bottom: 10px;
    cursor: pointer;
    border: 2px solid #031F4E;
    transition: transform 0.3s, box-shadow 0.3s; /* Add transition for smooth effect */
}

.profile-pic:hover {
    transform: scale(1.1); /* Slightly increase the size */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Add shadow for depth */
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
    right: -10px; /* Adjust positioning */
    top: 65%; /* Center vertically */
    transform: translateY(-50%); /* Adjust to center */
    color: #007BFF; /* Default icon color */
    cursor: pointer; /* Change cursor to pointer */
    transition: color 0.3s ease; /* Smooth transition for color change */
}


.password-icon:hover {
    color: #0056b3; /* Change color on hover */
}.home-icon {
    width: 30px; /* Adjust the width as needed */
    height: 30px; /* Adjust the height as needed */
    display: block; /* Ensures it's a block element */
    margin: 0 auto 15px; /* Centers the icon and adds space below */
}



    </style>
</head>
<body>

<div class="profile-container">
    <a href="dashboard.html"> <!-- Wrap the icon in an anchor tag -->
        <img src="pics/icons8-home.gif" alt="Home Icon" class="home-icon">
    </a>
    <h2>Admin Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <img src="<?php echo $adminProfilePic; ?>" class="profile-pic" id="profilePic" alt="Profile Picture" onclick="document.getElementById('fileInput').click();">
        <input type="file" name="profilePic" id="fileInput" style="display: none;" onchange="this.form.submit();">
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
            <span class="password-icon" onclick="togglePasswordVisibility()">
                <span class="material-icons" id="passwordIcon">visibility_off</span>
            </span>
        </div>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
            <button type="submit" name="saveChanges" style="flex: 1; display: flex; align-items: center; justify-content: center;">
                <img src="pics/icons8-save-48.png" alt="Save Icon" style="width: 20px; height: 20px; margin-right: 5px;">
                Save Changes
            </button>
            <button type="submit" name="deletePic" class="delete-btn" style="flex: 1; display: flex; align-items: center; justify-content: center;">
                <img src="pics/icons8-remove-24.png" alt="Delete Icon" style="width: 20px; height: 20px; margin-right: 5px;">
                Remove Picture
            </button>
        </div>
    </form>
</div>






    </form>
</div>

<!-- Modal for success message -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <img src="pics/check.gif" alt="Success" style="width: 50px; height: 50px;">
        <p><?php echo $successMessage; ?></p>
    </div>
</div>

<script>
    // Show the modal if there is a success message
    <?php if (!empty($successMessage)) : ?>
        document.getElementById('successModal').style.display = "block";
    <?php endif; ?>

    // Close modal function
    function closeModal() {
        document.getElementById('successModal').style.display = "none";
    }

    // Close modal when the user clicks outside of the modal
    window.onclick = function(event) {
        const modal = document.getElementById('successModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function togglePasswordVisibility() {
    const passwordInput = document.getElementById("passwordInput");
    const passwordIcon = document.getElementById("passwordIcon");
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        passwordIcon.innerText = "visibility"; // Change icon to eye open
    } else {
        passwordInput.type = "password";
        passwordIcon.innerText = "visibility_off"; // Change icon to eye closed
    }
}

</script>

</body>
</html>
