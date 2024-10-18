<?php
// Database configuration
include 'config.php';

// Get the report type from the URL
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : '';
$collector_id = isset($_GET['collector_id']) ? $_GET['collector_id'] : '';

// Initialize variables for collector full name
$collectorFullName = '';

if ($reportType == 'by_collector') {
    // Check if a specific collector is selected
    if ($collector_id !== 'all') {
        // Fetch collector's full name
        $collectorSql = "SELECT fname, lname FROM collectors WHERE collector_id = '$collector_id'";
        $collectorResult = $conn->query($collectorSql);
        
        if ($collectorResult && $collectorResult->num_rows > 0) {
            $collectorRow = $collectorResult->fetch_assoc();
            $collectorFullName = "{$collectorRow['fname']}_{$collectorRow['lname']}";
        } else {
            die("Collector not found.");
        }

        // Query for transactions by the specific collector, filtered for today
        $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount
                FROM vendor_transaction t
                JOIN vendor_list v ON t.vendorID = v.vendorID
                WHERE t.collector_id = '$collector_id' AND DATE(t.date) = CURDATE()
                ORDER BY t.date ASC";
        $filename = "Transactions_{$collectorFullName}_" . date('Ymd') . ".csv"; // Format the filename as collector_fullname_date.csv
    }

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for the collector(s)
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Output column headings (without collector)
        fputcsv($output, ['Transaction ID', 'Vendor ID', 'Vendor Name', 'Date', 'Amount']);

        // Write data rows
        $totalAmount = 0;
        while ($row = $result->fetch_assoc()) {
            $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
            fputcsv($output, [
                $row['transactionID'],
                $row['vendorID'],
                $vendorFullName,
                $row['date'],
                $row['amount']
            ]);
            $totalAmount += $row['amount'];
        }

        // Add a total row at the end of the report
        fputcsv($output, ['Total', '', '', '', $totalAmount]);

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found for the selected collector today.";
    }
    exit();
} elseif ($reportType == 'by_date') {
    // Generate a report for today's transactions
    $date = date('Y-m-d'); // Current date
    $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.amount
            FROM vendor_transaction t
            JOIN vendor_list v ON t.vendorID = v.vendorID
            WHERE DATE(t.date) = '$date'";
    $filename = "{$date}_Transactions.csv"; // File name for today's transactions

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for today's transactions
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Output column headings
        fputcsv($output, ['Transaction ID', 'Vendor ID', 'Vendor Name', 'Amount']);
        while ($row = $result->fetch_assoc()) {
            $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
            fputcsv($output, [$row['transactionID'], $row['vendorID'], $vendorFullName, $row['amount']]);
        }

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found for today.";
    }
    exit();
} elseif ($reportType == 'summary_per_day') {
    // Generate a summary report for all transactions today, including full details
    $date = date('Y-m-d'); // Current date
    $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount
            FROM vendor_transaction t
            JOIN vendor_list v ON t.vendorID = v.vendorID
            WHERE DATE(t.date) = '$date'";
    $filename = "Summary_of_Transactions_" . $date . ".csv"; // Summary file name

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for today's summary
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Output column headings
        fputcsv($output, ['Transaction ID', 'Vendor ID', 'Vendor Name', 'Date', 'Amount']);
        $totalAmount = 0; // Initialize total amount variable
        while ($row = $result->fetch_assoc()) {
            $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
            fputcsv($output, [$row['transactionID'], $row['vendorID'], $vendorFullName, $row['date'], $row['amount']]);
            $totalAmount += $row['amount']; // Accumulate the total amount
        }
        // Add a total row at the end
        fputcsv($output, ['Total', '', '', '', $totalAmount]);

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found for today.";
    }
    exit();
} else {
    die("Invalid report type selected.");
}
?>
