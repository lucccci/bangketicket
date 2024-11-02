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

        // Output column headings
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
            // Accumulate total amount for the specific collector only
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
}
elseif ($reportType == 'all_collectors') {
    // Query to get all transactions grouped by collector for today
    $sql = "SELECT vt.transactionID, vt.vendorID, 
                   CONCAT(vl.fname, ' ', vl.mname, ' ', vl.lname) AS vendor_name, 
                   vt.date, vt.amount, vt.collector_id, 
                   CONCAT(c.fname, ' ', c.lname) AS collector_name
            FROM vendor_transaction vt
            JOIN vendor_list vl ON vt.vendorID = vl.vendorID
            JOIN collectors c ON vt.collector_id = c.collector_id
            WHERE DATE(vt.date) = CURDATE() 
            ORDER BY c.collector_id, vt.date ASC"; // Only get today's transactions
            
    $filename = "AllCollectorsTransactions_For" . date('Y-m-d') . ".csv";

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for all collectors' transactions
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Output column headings
        fputcsv($output, ['Collectors Collection for ' . date('Y-m-d')]);

        $currentCollector = ''; // Track the current collector
        $collectorTotal = 0; // Initialize the total for the current collector

        while ($row = $result->fetch_assoc()) {
            if ($currentCollector !== $row['collector_name']) {
                // If not the first collector, add the total for the previous collector before switching to the new one
                if (!empty($currentCollector)) {
                    // Write the total for the previous collector
                    fputcsv($output, ['Total for ' . $currentCollector, '', '', '', '', $collectorTotal]);
                    fputcsv($output, []); // Empty row to separate collector sections
                }
                // Write the collector name as a heading
                fputcsv($output, ["Transactions for " . $row['collector_name']]);
                // Write the column headers for the transactions under this collector
                fputcsv($output, ['Transaction ID', 'Vendor ID', 'Vendor Name', 'Date', 'Amount']);
                $currentCollector = $row['collector_name']; // Update current collector
                $collectorTotal = 0; // Reset the total for the new collector
            }

            // Write the transaction data
            fputcsv($output, [
                $row['transactionID'],
                $row['vendorID'],
                $row['vendor_name'],
                $row['date'],
                $row['amount']
            ]);

            // Accumulate the total amount for the current collector
            $collectorTotal += $row['amount'];
        }

        // Write the total for the last collector
        if (!empty($currentCollector)) {
            fputcsv($output, ['Total for ' . $currentCollector, '', '', '', '', $collectorTotal]);
        }

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found for any collector today.";
    }
    exit();
}

 elseif ($reportType == 'by_date') {
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
