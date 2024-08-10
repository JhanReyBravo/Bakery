<?php
// Include your database connection file
include 'db_connect.php';

// Check if invoice_id is provided in the query string
if (isset($_GET['invoice_id'])) {
    $invoiceID = $_GET['invoice_id'];

    // Fetch invoice details
    $sql = "SELECT invoices.InvoiceID, customers.Name as CustomerName, invoices.InvoiceDate, invoices.TotalAmount, invoices.PaymentStatus
            FROM invoices
            JOIN customers ON invoices.CustomerID = customers.CustomerID
            WHERE invoices.InvoiceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();

    // Fetch invoice items
    $sql_items = "SELECT products.ProductName, invoice_details.QuantitySold, invoice_details.Price, invoice_details.TotalAmount
                  FROM invoice_details
                  JOIN products ON invoice_details.ProductID = products.ProductID
                  WHERE invoice_details.InvoiceID = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $invoiceID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $invoice_items = $result_items->fetch_all(MYSQLI_ASSOC);

    // Close statements and connection
    $stmt->close();
    $stmt_items->close();
    $conn->close();
} else {
    die("Invoice ID not provided.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Invoice</title>
    <link rel="stylesheet" href="view_invoice.css">
</head>

<body>
    <h2>Invoice Details</h2>
    <p><strong>Invoice ID:</strong> <?php echo $invoice['InvoiceID']; ?></p>
    <p><strong>Customer Name:</strong> <?php echo $invoice['CustomerName']; ?></p>
    <p><strong>Invoice Date:</strong> <?php echo $invoice['InvoiceDate']; ?></p>
    <p><strong>Total Amount:</strong> <?php echo $invoice['TotalAmount']; ?></p>
    <p><strong>Payment Status:</strong> <?php echo $invoice['PaymentStatus']; ?></p>

    <h3>Invoice Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity Sold</th>
                <th>Price</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice_items as $item): ?>
                <tr>
                    <td><?php echo $item['ProductName']; ?></td>
                    <td><?php echo $item['QuantitySold']; ?></td>
                    <td><?php echo $item['Price']; ?></td>
                    <td><?php echo $item['TotalAmount']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="invoice_list.php">Back to Invoice List</a>
</body>

</html>