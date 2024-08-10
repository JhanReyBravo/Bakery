<?php
// Include your database connection file
include 'db_connect.php';

// Initialize variables
$invoice = null;
$invoice_items = [];
$grandTotal = 0.00;

// Check if invoice_id is provided in the query string
if (isset($_GET['invoice_id'])) {
    $invoiceID = $_GET['invoice_id'];

    // Fetch invoice details
    $sql = "SELECT invoices.InvoiceID, invoices.InvoiceDate, invoices.TotalAmount, invoices.PaymentStatus,
                   customers.Name as CustomerName, customers.Address
            FROM invoices
            JOIN customers ON invoices.CustomerID = customers.CustomerID
            WHERE invoices.InvoiceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();

    // Fetch invoice items
    $sql_items = "SELECT invoice_details.ProductID, invoice_details.QuantitySold, invoice_details.Price,
                         products.ProductName
                  FROM invoice_details
                  JOIN products ON invoice_details.ProductID = products.ProductID
                  WHERE invoice_details.InvoiceID = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $invoiceID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $invoice_items = $result_items->fetch_all(MYSQLI_ASSOC);

    // Calculate the Grand Total
    foreach ($invoice_items as $item) {
        $grandTotal += $item['QuantitySold'] * $item['Price'];
    }

    // Close statements
    $stmt->close();
    $stmt_items->close();
} else {
    die("Invoice ID not provided.");
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Receipt</title>
    <link rel="stylesheet" href="invoice_print.css">
    <style>
        .invoice-id {
            color: red;
        }

        @media print {

            .print-button,
            .back-button {
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
    <p><strong>Invoice No: </strong><span
            class="invoice-id"><?php echo htmlspecialchars($invoice['InvoiceID']); ?></span></p>
    <div class="invoice-info">
        <div class="left-side">
            <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($invoice['CustomerName']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['Address']); ?></p>
        </div>
        <div class="right-side">
            <p><strong>Date:</strong> <?php echo htmlspecialchars($invoice['InvoiceDate']); ?></p>
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
                <?php foreach ($invoice_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['QuantitySold']); ?></td>
                        <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                        <td><?php echo number_format($item['Price'], 2); ?></td>
                        <td><?php echo number_format($item['QuantitySold'] * $item['Price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="footer">
        <p><strong>Grand Total:</strong> <?php echo number_format($grandTotal, 2); ?></p>
    </div>
    <div class="print-button">
        <button onclick="window.print()">Print Receipt</button>
    </div>
    <div class="back-button">
        <a href="home.html">Back</a>
    </div>
</body>

</html>