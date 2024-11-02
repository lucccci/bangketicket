<?php
require_once 'config.php';

// Retrieve vendorID from the URL parameters
$vendorID = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// Check if a valid vendorID is provided
if (empty($vendorID)) {
    die("<p>No vendor ID provided.</p>");
}
// Query to fetch the vendor's full name based on the vendorID
$vendorQuery = "SELECT fname, lname, mname FROM vendor_list WHERE vendorID = '$vendorID'";
$vendorResult = $conn->query($vendorQuery);

// Check if a vendor was found
if ($vendorResult && $vendorResult->num_rows > 0) {
    $vendorRow = $vendorResult->fetch_assoc();
    $vendorFullName = htmlspecialchars($vendorRow['lname']) . ', ' . htmlspecialchars($vendorRow['fname']) . ' ' . htmlspecialchars($vendorRow['mname']);
} else {
    $vendorFullName = 'Unknown Vendor';
}

// Query to fetch transactions for the specific vendorID
$sql = "SELECT t.transactionID, t.date, t.amount, c.fname AS collector_fname, c.lname AS collector_lname
        FROM vendor_transaction t
        JOIN collectors c ON t.collector_id = c.collector_id
        WHERE t.vendorID = '$vendorID'
        ORDER BY t.date;";

$result = $conn->query($sql);

// Start output buffering to capture HTML
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/logo-bt.png">
    <title>Transactions History</title>
    <style>
        body {
            font-family: "Times New Roman", Georgia, serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        /* Header styling */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 85%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 10px 0;
        }
        
        /* Align text and logo closer together */
        .header-right {
            display: flex;
            align-items: center;
            gap: 5px; /* Reduced gap */
            text-align: right;
        }
        
        .gov-text {
            font-size: 18px; /* Smaller text */
            line-height: 1.1; /* Adjust line height */
        }

        .gov-text hr {
            margin: 5px 0;
            border: none;
            border-top: 1px solid #000;
        }

        .logo img {
            max-height: 160px; /* Increase size of the logo */
            max-width: 160px;
        }

        .vendor-title {
            text-align: left;
            font-size: 26px;
            font-weight: bold;
        }

        .vendor-name {
            font-weight: normal; /* Make the vendor name not bold */
        }

        .container {
            display: flex;
            justify-content: center;
            padding: 20px;
            margin: auto;
            width: 90%;
        }

        table {
            width: 85%;
            max-width: 1000px;
            border-collapse: collapse;
            background-color: white;
            font-size: 18px;
            margin: 0 auto;
            table-layout: fixed; /* Fix table layout for better spacing */
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 18px;
        }

        th {
            background-color: #031F4E;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:nth-child(odd) {
            background-color: #fff;
        }
        
        /* Media Queries for responsive behavior */
        @media only screen and (max-width: 1024px) {
            .vendor-title {
                font-size: 22px;
            }
        
            table {
                font-size: 15px;
                width: 90%;
            }
        
            th, td {
                padding: 10px;
            }
        
            .logo img {
                max-height: 130px;
                max-width: 130px;
            }
        
            .gov-text {
                font-size: 12px;
            }
        
            .table-header {
                width: 90%;
            }
        }

        @media only screen and (max-width: 768px) {
            .vendor-title {
                font-size: 16px;
            }
        
            table {
                font-size: 14px;
                width: 100%;
            }
        
            th, td {
                padding: 8px;
                font-size: 14px;
            }
        
            .logo img {
                max-height: 100px;
                max-width: 100px;
            }
        
            .table-header {
                width: 100%;
                padding: 0 10px;
            }
        
            .gov-text {
                display: none;
            }
        }

        /* Main adjustments for smaller screens */
        @media only screen and (max-width: 480px) {
            .vendor-title {
                font-size: 14px;
            }
        
            table {
                font-size: 12px;
                width: 100%;
            }
        
            th, td {
                padding: 6px;
                font-size: 12px; /* Smaller text */
            }
        
            .logo img {
                max-height: 100px;
                max-width: 100px;
            }
        
            .table-header {
                width: 100%;
                padding: 0 5px;
            }

            .gov-text {
                display: none;
            }
        }

        @media only screen and (max-width: 360px) {
            .vendor-title {
                font-size: 12px;
            }
        
            table {
                font-size: 11px;
            }
        
            th, td {
                padding: 5px;
                font-size: 11px;
            }
        
            .logo img {
                max-height: 100px;
                max-width: 100px;
            }
        }

    </style>
</head>
<body>

<!-- Table header with title, text, and logo aligned -->
<div class="table-header">
    <div class="vendor-title">
        Transaction History <br> Vendor: <span class="vendor-name"><?php echo htmlspecialchars($vendorFullName); ?></span>
    </div>
    <div class="header-right">
        <div class="gov-text">
            Republika ng Pilipinas <br>
            <hr>
            Pamahalaang Lungsod ng Malolos
        </div>
        <div class="logo">
            <img src="pics/malolos33.png" alt="Logo">
        </div>
    </div>
</div>

<div class="container">
    <table class="transactionTable">
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Transaction Date</th>
                <th>Amount</th>
                <th>Collector</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
               while ($row = $result->fetch_assoc()) {
                $collectorFullName = isset($row["collector_fname"], $row["collector_lname"]) 
                    ? htmlspecialchars($row["collector_fname"]) . ' ' . htmlspecialchars($row["collector_lname"])
                    : 'N/A'; 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["transactionID"]); ?></td>
                        <td><?php echo htmlspecialchars($row["date"]); ?></td>
                        <td>₱<?php echo number_format($row["amount"], 2); ?></td>
                        <td><?php echo $collectorFullName; ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="4">No transactions found for this vendor.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
// Get the HTML content
$html = ob_get_clean();

// Return the HTML content
echo $html;

// Close the connection
$conn->close();
?>
