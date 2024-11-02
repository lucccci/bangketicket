<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not authenticated
    header("Location: index.html");
    exit();
}
?>
