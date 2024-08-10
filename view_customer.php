<?php
// Include your database connection file
include 'db_connect.php';

// Start HTML document
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details</title>
    <link rel="stylesheet" href="view.css">
</head>

<body>
    <div class="container">
        <?php
        // Check if customer_id is set
        if (isset($_GET['customer_id'])) {
            $customer_id = $_GET['customer_id'];

            // Prepare the query
            $stmt = $conn->prepare("SELECT CustomerID, Name, Address, Phone, Email, AccountBalance FROM customers WHERE CustomerID = ?");
            $stmt->bind_param("i", $customer_id);

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $customer = $result->fetch_assoc();
                    echo "<h2>Customer Details</h2>";
                    echo "<ul class='details'>";
                    echo "<li><strong>Name:</strong> " . $customer['Name'] . "</li>";
                    echo "<li><strong>Address:</strong> " . $customer['Address'] . "</li>";
                    echo "<li><strong>Phone:</strong> " . $customer['Phone'] . "</li>";
                    echo "<li><strong>Email:</strong> " . $customer['Email'] . "</li>";
                    echo "<li><strong>Account Balance:</strong> ₱" . number_format($customer['AccountBalance'], 2) . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p>Customer not found.</p>";
                }
            } else {
                echo "<p>Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            echo "<p>Invalid request.</p>";
        }

        // Close database connection
        $conn->close();
        ?>
        <a class="back-link" href="customerlist.php">Back to Customer List</a>
    </div>
</body>

</html>