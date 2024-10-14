<?php
// vendorDetails.php

// Include database connection
require_once 'db_connection.php';

// Get vendor ID from URL
$vendorID = isset($_GET['vendorID']) ? $_GET['vendorID'] : '';

// Fetch vendor details from the database
$query = "SELECT * FROM vendors WHERE vendorID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$vendorID]);
$vendor = $stmt->fetch();

// Fetch vendor transactions
$transactionQuery = "SELECT * FROM transactions WHERE vendorID = ?";
$transactionStmt = $pdo->prepare($transactionQuery);
$transactionStmt->execute([$vendorID]);
$transactions = $transactionStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Details</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Vendor Details</h1>
    <p><strong>Vendor ID:</strong> <?php echo htmlspecialchars($vendor['vendorID']); ?></p>
    <p><strong>First Name:</strong> <?php echo htmlspecialchars($vendor['fname']); ?></p>
    <p><strong>Last Name:</strong> <?php echo htmlspecialchars($vendor['lname']); ?></p>
    <!-- Add other vendor details here -->

    <h2>Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Date</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['transactionID']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="vendorlist.php">Back to Vendor List</a>
</body>
</html>
