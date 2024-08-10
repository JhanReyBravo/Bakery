<?php
include 'db_connect.php';

// Check if payment_id is set
if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    // Prepare the query
    $stmt = $conn->prepare("SELECT p.PaymentID, c.Name AS CustomerName, p.InvoiceID, p.PaymentDate, p.Amount, p.PaymentMethod 
                            FROM payments p 
                            JOIN customers c ON p.CustomerID = c.CustomerID 
                            WHERE p.PaymentID = ?");
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $payment = $result->fetch_assoc();
        } else {
            echo "Payment not found.";
            exit;
        }
    } else {
        echo "Error: " . $stmt->error;
        exit;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
    exit;
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payment</title>
    <link rel="stylesheet" href="view_invoice.css">
</head>

<body>
    <h2>Payment Details</h2>
    <p>Payment ID: <?php echo htmlspecialchars($payment['PaymentID']); ?></p>
    <p>Customer Name: <?php echo htmlspecialchars($payment['CustomerName']); ?></p>
    <p>Invoice ID: <?php echo htmlspecialchars($payment['InvoiceID']); ?></p>
    <p>Payment Date: <?php echo htmlspecialchars($payment['PaymentDate']); ?></p>
    <p>Amount: <?php echo htmlspecialchars($payment['Amount']); ?></p>
    <p>Payment Method: <?php echo htmlspecialchars($payment['PaymentMethod']); ?></p>
    <a href="payment_list.php">Back to Payment List</a>
</body>

</html>