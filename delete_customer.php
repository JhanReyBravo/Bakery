<?php
// Include your database connection file
include 'db_connect.php';

// Check if customer_id is set
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];

    // Prepare the query to delete the customer
    $stmt = $conn->prepare("DELETE FROM customers WHERE CustomerID = ?");
    $stmt->bind_param("i", $customer_id);

    if ($stmt->execute()) {
        echo "Customer deleted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

// Close database connection
$conn->close();
?>

<a href="customerlist.php">Back to Customer List</a>