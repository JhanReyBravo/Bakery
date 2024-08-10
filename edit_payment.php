<?php
include 'db_connect.php';

// Check if payment_id is set
if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update payment record
        $payment_date = $_POST['payment_date'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];

        $stmt = $conn->prepare("UPDATE payments SET PaymentDate = ?, Amount = ?, PaymentMethod = ? WHERE PaymentID = ?");
        $stmt->bind_param("sdsi", $payment_date, $amount, $payment_method, $payment_id);

        if ($stmt->execute()) {
            echo "<script>alert('Payment updated successfully.'); window.location.href = 'payment_list.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Fetch payment record to edit
        $stmt = $conn->prepare("SELECT PaymentID, PaymentDate, Amount, PaymentMethod FROM payments WHERE PaymentID = ?");
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
    }
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
    <title>Edit Payment</title>
    <link rel="stylesheet" href="rawmat_list.css">
</head>

<body>
    <h2>Edit Payment</h2>
    <form method="POST" action="edit_payment.php?payment_id=<?php echo htmlspecialchars($payment_id); ?>">
        <label for="payment_date">Payment Date:</label>
        <input type="date" id="payment_date" name="payment_date"
            value="<?php echo htmlspecialchars($payment['PaymentDate']); ?>" required><br>
        <label for="amount">Amount:</label>
        <input type="number" step="0.01" id="amount" name="amount"
            value="<?php echo htmlspecialchars($payment['Amount']); ?>" required><br>
        <label for="payment_method">Payment Method:</label>
        <input type="text" id="payment_method" name="payment_method"
            value="<?php echo htmlspecialchars($payment['PaymentMethod']); ?>" required><br>
        <input type="submit" value="Update">
    </form>
    <style>
        input[type="number"] {
            width: 20%;
            padding: 10px;
            margin-bottom: 15px;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            border-color: #ff4d4d;
        }

        input[type="date"] {
            width: 20%;
            padding: 10px;
            margin-bottom: 15px;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            border-color: #ff4d4d;
        }
    </style>
    <a href="payment_list.php">Back to Payment List</a>
</body>

</html>