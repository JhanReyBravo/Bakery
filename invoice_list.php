<?php
include 'db_connect.php';
$search_term = "";
$invoices = [];
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
}
if ($search_term) {
    $like_term = '%' . $search_term . '%';
    $stmt = $conn->prepare("SELECT invoices.InvoiceID, customers.Name as CustomerName, invoices.InvoiceDate, invoices.TotalAmount, invoices.PaymentStatus
                            FROM invoices
                            JOIN customers ON invoices.CustomerID = customers.CustomerID
                            WHERE invoices.InvoiceID LIKE ? OR customers.Name LIKE ?");
    $stmt->bind_param("ss", $like_term, $like_term);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $invoices = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
} else {
    $sql = "SELECT invoices.InvoiceID, customers.Name as CustomerName, invoices.InvoiceDate, invoices.TotalAmount, invoices.PaymentStatus
            FROM invoices
            JOIN customers ON invoices.CustomerID = customers.CustomerID";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $invoices = $result->fetch_all(MYSQLI_ASSOC);
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice List</title>
    <link rel="stylesheet" href="invoice_list.css">
</head>

<body>
    <h2>Invoice List</h2>
    <form action="invoice_list.php" method="GET">
        <input type="text" id="search_term" name="search_term" class="search"
            value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search Invoice ID or Customer Name:">
        <input type="submit" class="search" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>
    <br>
    <table>
        <thead>
            <tr>
                <th>Invoice ID</th>
                <th>Customer Name</th>
                <th>Invoice Date</th>
                <th>Total Amount</th>
                <th>Payment Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($invoices) > 0): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo $invoice['InvoiceID']; ?></td>
                        <td><?php echo $invoice['CustomerName']; ?></td>
                        <td><?php echo $invoice['InvoiceDate']; ?></td>
                        <td><?php echo $invoice['TotalAmount']; ?></td>
                        <td><?php echo $invoice['PaymentStatus']; ?></td>
                        <td>
                            <a href="view_invoice.php?invoice_id=<?php echo $invoice['InvoiceID']; ?>">View</a>
                            <a class="edit" href="create_invoice.php">Create Invoice</a>
                            <a class="edit" href="apppayment.php">Apply Payment</a>
                            <a class='delete'
                                href="reprint_invoice.php?invoice_id=<?php echo $invoice['InvoiceID']; ?>">Reprint</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No invoices found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>