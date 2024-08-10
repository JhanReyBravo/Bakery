<?php
// Include your database connection file
include 'db_connect.php';

// Initialize product variable
$product = null;

// Check if product_id is set
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update product record
        $product_name = $_POST['product_name'];
        $quantity = $_POST['quantity'];

        $stmt = $conn->prepare("UPDATE products SET ProductName = ?, Quantity = ? WHERE ProductID = ?");
        $stmt->bind_param("sii", $product_name, $quantity, $product_id);

        if ($stmt->execute()) {
            echo "Product updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Fetch product record to edit
        $stmt = $conn->prepare("SELECT ProductID, ProductName, Quantity FROM products WHERE ProductID = ?");
        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
            } else {
                echo "Product not found.";
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
    <title>Edit Product</title>
    <link rel="stylesheet" href="rawmat_list.css">
</head>

<body>
    <h2>Edit Product</h2>
    <?php if ($product): ?>
        <form action="edit_product.php?product_id=<?php echo $product_id; ?>" method="POST">
            <label for="product_name">Product Name:</label>
            <input type="text" id="product_name" name="product_name"
                value="<?php echo htmlspecialchars($product['ProductName']); ?>" required><br><br>
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="<?php echo htmlspecialchars($product['Quantity']); ?>"
                required><br><br>
            <input type="submit" value="Update">
        </form>
    <?php else: ?>
        <p>No product data available to edit.</p>
    <?php endif; ?>
    <a href="product_list.php">Back to Product List</a>
</body>

</html>