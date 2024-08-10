<?php
include 'db_connect.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$customerName = '';

// Fetch search criteria from GET request
if (isset($_GET['customer_name'])) {
    $customerName = $_GET['customer_name'];
}

// Build the SQL query with search criteria
$sql = "SELECT p.PaymentID, c.Name AS CustomerName, p.InvoiceID, p.PaymentDate, p.Amount, p.PaymentMethod 
        FROM payments p 
        JOIN customers c ON p.CustomerID = c.CustomerID 
        WHERE c.Name LIKE ?
        ORDER BY p.PaymentDate DESC";

$stmt = $conn->prepare($sql);
$customerNameParam = '%' . $customerName . '%';
$stmt->bind_param("s", $customerNameParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment List</title>
    <link rel="stylesheet" href="payment_list.css">
</head>

<body>
    <h2>Payment List</h2>
    <form method="GET" action="payment_list.php">
        <input type="text" id="customer_name" name="customer_name" class="search"
            value="<?php echo htmlspecialchars($customerName); ?>" placeholder="Search Customer Name">
        <input type="submit" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>
    <br>
    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Customer Name</th>
                <th>Invoice ID</th>
                <th>Payment Date</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['PaymentID']}</td>
                            <td>{$row['CustomerName']}</td>
                            <td>{$row['InvoiceID']}</td>
                            <td>{$row['PaymentDate']}</td>
                            <td>{$row['Amount']}</td>
                            <td>{$row['PaymentMethod']}</td>
                            <td>
                                <a href='view_payment.php?payment_id={$row['PaymentID']}'>View</a>
                                <a class='edit' href='create_invoice.php'>Create Invoice</a>
                                <a class='edit' href='receiving.php'>Apply payment</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='7'>No payments found</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>

</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>