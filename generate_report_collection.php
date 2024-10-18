<?php
// Database configuration
include 'config.php';

// Get the report type from the URL
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : '';
$collector_id = isset($_GET['collector_id']) ? $_GET['collector_id'] : '';

if ($reportType == 'by_collector') {
    // Existing code for by_collector (no changes needed)
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

        // Query for transactions by the specific collector
        $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount
                FROM vendor_transaction t
                JOIN vendor_list v ON t.vendorID = v.vendorID
                WHERE t.collector_id = '$collector_id'
                ORDER BY t.date ASC";
        $filename = "Transactions_{$collectorFullName}_" . date('Y-m-d') . ".csv"; // Format the filename as collector_fullname_date.csv
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
        echo "No transactions found for the selected collector.";
    }
    exit();
} 
elseif ($reportType == 'transaction_summary') {
    // Existing code for transaction_summary (no changes needed)
    $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount, t.collector_id
            FROM vendor_transaction t
            JOIN vendor_list v ON t.vendorID = v.vendorID
            ORDER BY t.date ASC";
    $filename = "SummaryOfTransactions_" . date('Y-m-d') . ".csv";

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for the summary of all transactions
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Output column headings
        fputcsv($output, ['Transaction ID', 'Vendor ID', 'Vendor Name', 'Date', 'Amount', 'Collector ID']);
        
        $totalAmount = 0; // Initialize total amount variable
        while ($row = $result->fetch_assoc()) {
            $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
            fputcsv($output, [
                $row['transactionID'], 
                $row['vendorID'], 
                $vendorFullName, 
                $row['date'], 
                $row['amount'], 
                $row['collector_id'], 
            ]);
            $totalAmount += $row['amount']; // Accumulate the total amount
        }
        // Add a total row at the end
        fputcsv($output, ['Total', '', '', '', $totalAmount]);

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found.";
    }
    exit();
} 
elseif ($reportType == 'by_vendor') {
    // New code for generating the report by vendors, with collector info
    $sql = "SELECT t.transactionID, t.vendorID, v.lname, v.fname, v.mname, t.date, t.amount, c.fname AS collector_fname, c.lname AS collector_lname
            FROM vendor_transaction t
            JOIN vendor_list v ON t.vendorID = v.vendorID
            LEFT JOIN collectors c ON t.collector_id = c.collector_id
            ORDER BY v.vendorID, t.date ASC"; // Order by vendorID, then by date
    $filename = "VendorTransactions_" . date('Y-m-d') . ".csv";

    // Execute the query
    $result = $conn->query($sql);

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        // Create a CSV file for vendor transactions
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        $currentVendor = ''; // Initialize variable to track current vendor
        $totalAmount = 0; // Initialize total amount variable for all vendors
        while ($row = $result->fetch_assoc()) {
            $vendorFullName = "{$row['lname']}, {$row['fname']} {$row['mname']}";
            $collectorFullName = "{$row['collector_fname']} {$row['collector_lname']}";

            // If this is a new vendor, write the vendor heading
            if ($vendorFullName !== $currentVendor) {
                // If not the first vendor, add a line to separate vendors
                if (!empty($currentVendor)) {
                    fputcsv($output, []); // Empty row to separate vendor sections
                }
                // Write the vendor heading
                fputcsv($output, ["Vendor: $vendorFullName (Vendor ID: {$row['vendorID']})"]);
                // Write column headers for the transactions under this vendor
                fputcsv($output, ['Transaction ID', 'Date', 'Amount', 'Collector']);
                $currentVendor = $vendorFullName; // Update current vendor
            }

            // Write the transaction data, including the collector name
            fputcsv($output, [
                $row['transactionID'],
                $row['date'],
                $row['amount'],
                $collectorFullName
            ]);

            $totalAmount += $row['amount']; // Accumulate total amount
        }

        // Add a total row at the end of the report
        fputcsv($output, []);
        fputcsv($output, ['Total Amount for All Vendors', '', $totalAmount]);

        // Close output stream
        fclose($output);
    } else {
        echo "No transactions found.";
    }
    exit();
} else {
    die("Invalid report type selected.");
}
?>
