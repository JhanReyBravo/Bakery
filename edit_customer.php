<?php
// Include your database connection file
include 'db_connect.php';

$customer = null;

// Check if customer_id is set
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update customer record
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $account_balance = $_POST['account_balance'];

        $stmt = $conn->prepare("UPDATE customers SET Name = ?, Address = ?, Phone = ?, Email = ?, AccountBalance = ? WHERE CustomerID = ?");
        $stmt->bind_param("ssssdi", $name, $address, $phone, $email, $account_balance, $customer_id);

        if ($stmt->execute()) {
            echo "Customer updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Fetch customer record to edit
        $stmt = $conn->prepare("SELECT CustomerID, Name, Address, Phone, Email, AccountBalance FROM customers WHERE CustomerID = ?");
        $stmt->bind_param("i", $customer_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
            } else {
                echo "Customer not found.";
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
    <title>Edit Customer</title>
    <link rel="stylesheet" href="customerlist.css">
</head>

<body>
    <h2>Edit Customer</h2>
    <?php if ($customer): ?>
        <form action="edit_customer.php?customer_id=<?php echo $customer_id; ?>" method="POST">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['Name']); ?>"
                required><br><br>
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($customer['Address']); ?>"
                required><br><br>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['Phone']); ?>"
                required><br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['Email']); ?>"
                required><br><br>
            <label for="account_balance">Account Balance:</label>
            <input type="number" id="account_balance" name="account_balance"
                value="<?php echo htmlspecialchars($customer['AccountBalance']); ?>" step="0.01" required><br><br>
            <input type="submit" value="Update">
        </form>
    <?php else: ?>
        <p></p>
    <?php endif; ?>
    <a href="customerlist.php">Back to Customer List</a>
</body>

</html>