<?php
// get_transactions.php
include 'bangketicketdb.php'; // Include your database connection

if (isset($_POST['vendorID'])) {
    $vendorID = $_POST['vendorID'];
    $query = "SELECT date, time, status FROM transactions WHERE vendorID = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$vendorID]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($transactions);
}
?>
