<?php
// Include your database connection file
include 'db_connect.php';

// Get the POST data
$productName = isset($_POST['productName']) ? $_POST['productName'] : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

// Prepare a response array
$response = array('status' => 'error', 'stock' => 0);

// Check if productName and quantity are valid
if (!empty($productName) && $quantity > 0) {
    // Prepare and execute the query
    $sql = "SELECT Quantity FROM products WHERE ProductName = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $productName);
        $stmt->execute();
        $stmt->bind_result($stockQuantity);
        $stmt->fetch();
        $stmt->close();

        // Check stock availability
        if ($stockQuantity !== null) {
            if ($quantity <= $stockQuantity) {
                $response['status'] = 'success';
            } else {
                $response['status'] = 'error';
                $response['stock'] = $stockQuantity;
            }
        }
    }
}

// Output response as JSON
header('Content-Type: application/json');
echo json_encode($response);

// Close database connection
$conn->close();
?>