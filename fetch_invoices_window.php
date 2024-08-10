<?php
include 'db_connect.php';

$customerID = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

if ($customerID) {
    $stmt = $conn->prepare("
        SELECT i.InvoiceID, i.TotalAmount, c.Name, 
               GROUP_CONCAT(p.ProductName SEPARATOR ', ') AS ProductNames,
               GROUP_CONCAT(id.QuantitySold SEPARATOR ', ') AS QuantitiesSold
        FROM invoices i
        JOIN customers c ON i.CustomerID = c.CustomerID
        JOIN invoice_details id ON i.InvoiceID = id.InvoiceID
        JOIN products p ON id.ProductID = p.ProductID
        WHERE i.CustomerID = ? AND i.PaymentStatus = 'Unpaid'
        GROUP BY i.InvoiceID, i.TotalAmount, c.Name
    ");
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Select Invoice</title>
        <script>
            function selectInvoice(invoiceID, totalAmount) {
                window.opener.setInvoice(invoiceID, totalAmount);
                window.close();
            }
        </script>
    </head>

    <body>
        <h2>Select Invoice</h2>
        <?php
        if ($result->num_rows > 0) {
            echo '<table border="1">';
            echo '<tr><th>Invoice ID</th><th>Customer Name</th><th>Quantities Sold</th><th>Product Names</th><th>Total Amount</th><th>Action</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['Name'] . '</td>';
                echo '<td>' . $row['InvoiceID'] . '</td>';
                echo '<td>' . $row['TotalAmount'] . '</td>';
                echo '<td>' . $row['ProductNames'] . '</td>';
                echo '<td>' . $row['QuantitiesSold'] . '</td>';
                echo '<td><button onclick="selectInvoice(' . $row['InvoiceID'] . ', ' . $row['TotalAmount'] . ')">Select</button></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>No unpaid invoices found for this customer.</p>';
        }
        $stmt->close();
        $conn->close();
        ?>
    </body>

    </html>
    <?php
} else {
    echo '<p>Invalid customer ID.</p>';
}
?>