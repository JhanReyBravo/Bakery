<?php
include 'db_connect.php'; // Ensure database connection

if (isset($_GET['invoiceID'])) {
    $invoiceID = $_GET['invoiceID'];

    // Fetch the invoice data from the database, including the customer's name
    $sql = "SELECT invoices.*, customers.Name, customers.Address 
            FROM invoices 
            JOIN customers ON invoices.CustomerID = customers.CustomerID 
            WHERE invoices.InvoiceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch the invoice details, including the product name
    $sql = "SELECT invoice_details.*, products.ProductName 
            FROM invoice_details 
            JOIN products ON invoice_details.ProductID = products.ProductID 
            WHERE invoice_details.InvoiceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calculate the Grand Total
    $grandTotal = 0;
    foreach ($details as $detail) {
        $grandTotal += $detail['TotalAmount'];
    }
} else {
    echo 'No invoice ID provided.';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Receipt</title>
    <link rel="stylesheet" href="receipt.css">
    <style>
        .invoice-id {
            color: red;
        }

        @media print {

            .print-button,
            .home {
                display: none;
            }
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .left-side,
        .right-side {
            flex: 1;
        }

        .left-side {
            margin-right: 20px;
        }

        .right-side {
            margin-right: 380px;
        }

        .print-info {
            font-size: 12px;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <h1>THE A BAKERY BCD</h1>
    <p><strong>Invoice No: </strong><span class="invoice-id"><?php echo $invoice['InvoiceID']; ?></span></p>
    <div class="invoice-info">
        <div class="left-side">
            <p><strong>Customer Name:</strong> <?php echo $invoice['Name']; ?></p>
            <p><strong>Address:</strong> <?php echo $invoice['Address']; ?></p>
        </div>
        <div class="right-side">
            <p><strong>Date:</strong> <?php echo $invoice['InvoiceDate']; ?></p>
        </div>
    </div>
    <div class="invoice-table">
        <h2>Invoice Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $detail) { ?>
                    <tr>
                        <td><?php echo $detail['QuantitySold']; ?></td>
                        <td><?php echo $detail['ProductName']; ?></td>
                        <td><?php echo number_format($detail['Price'], 2); ?></td>
                        <td><?php echo number_format($detail['TotalAmount'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p><strong>Grand Total:</strong> <?php echo number_format($grandTotal, 2); ?></p>
    </div>
    <div class="print-button">
        <button onclick="window.print()">Print Receipt</button>
    </div>
    <a href="home.html" class="home">Back</a>
</body>

</html>