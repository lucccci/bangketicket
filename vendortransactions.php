<?php
require_once 'config.php';

// Retrieve vendorID from the URL parameters
$vendorID = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// Check if a valid vendorID is provided
if (empty($vendorID)) {
    die("<p>No vendor ID provided.</p>");
}

// Query to fetch transactions for the specific vendorID
$sql = "SELECT transactionID, date, amount 
        FROM vendor_transaction 
        WHERE vendorID = '$vendorID' 
        ORDER BY date;";

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
    <title>BangkeTicket</title>
    <style>
        body {
            font-family: "Times New Roman", Georgia, serif; 
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .table-header {
            display: flex;
            justify-content: space-between; /* Align title and logo on opposite sides */
            align-items: center;
            width: 85%; /* Same width as table for consistency */
            max-width: 1000px;
            margin: 0 auto; /* Center the header */
            padding: 10px 0; /* Padding above and below the header */
        }

        .vendor-title {
            text-align: left;
            font-size: 26px; /* Larger title size for high-res screens */
            font-weight: bold;
            margin-left: 20px; /* Added margin to prevent overlap */
        }

        .logo {
            text-align: right;
        }

        .logo img {
            max-height: 90px; /* Slightly larger logo for high-res screens */
            max-width: 90px;
        }

        .container {
            display: flex;
            justify-content: center;
            padding: 20px;
            width: 100%;
        }

        table {
            width: 85%; /* Wider table for desktop */
            max-width: 1000px; /* Adjust max width for desktop */
            border-collapse: collapse;
            background-color: white;
            font-size: 18px; /* Larger font size for readability on high-res screens */
            margin: 0 auto;
        }

        th, td {
            padding: 12px; /* Increased padding for desktop */
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 18px; /* Match table font size */
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

        /* Media Queries for responsiveness */
        @media only screen and (max-width: 1280px) { /* For larger devices like tablets */
            .vendor-title {
                font-size: 24px;
                margin-left: 15px;
            }

            table {
                font-size: 16px;
            }

            th, td {
                padding: 10px;
            }

            .logo img {
                max-height: 80px;
                max-width: 80px;
            }
        }

        @media only screen and (max-width: 1024px) {
            .vendor-title {
                font-size: 22px;
                margin-left: 10px; /* Adjust margin for smaller screens */
            }

            table {
                font-size: 15px;
                width: 90%;
            }

            th, td {
                padding: 9px;
            }

            .logo img {
                max-height: 70px;
                max-width: 70px;
            }

            .table-header {
                width: 90%;
            }
        }

        @media only screen and (max-width: 768px) { /* For newer smartphones and medium-sized devices */
            .vendor-title {
                font-size: 20px;
                margin-left: 10px; /* Adjust margin for smaller screens */
            }

            table {
                font-size: 14px;
                width: 100%;
            }

            th, td {
                padding: 8px;
            }

            .logo img {
                max-height: 60px;
                max-width: 60px;
            }

            .table-header {
                width: 100%;
                padding: 0 10px;
            }
        }

        @media only screen and (max-width: 480px) { /* For smaller smartphones */
            .vendor-title {
                font-size: 18px;
                margin-left: 5px; /* Adjust margin for small screens */
            }

            table {
                font-size: 13px;
                width: 100%;
            }

            th, td {
                padding: 7px;
            }

            .logo img {
                max-height: 50px;
                max-width: 50px;
            }

            .table-header {
                width: 100%;
                padding: 0 5px;
            }

            .container {
                padding: 10px;
            }
        }

        @media only screen and (max-width: 360px) { /* For very small screens */
            .vendor-title {
                font-size: 16px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 6px;
            }

            .logo img {
                max-height: 40px;
                max-width: 40px;
            }
        }
    </style>
</head>
<body>

<!-- Table header with title and logo aligned to edges -->
<div class="table-header">
    <div class="vendor-title">
        Transaction History <br> Vendor ID: <?php echo htmlspecialchars($vendorID); ?>
    </div>
    <div class="logo">
        <!-- Replace with the actual logo image -->
        <img src="pics/malolos-logo.png" alt="Logo">
    </div>
</div>

<div class="container">
    <table class="transactionTable">
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>Transaction Date</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row["transactionID"]); ?></td>
                        <td><?php echo htmlspecialchars($row["date"]); ?></td>
                        <td>₱<?php echo number_format($row["amount"], 2); ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="3">No transactions found for this vendor.</td>
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
