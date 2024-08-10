<?php
// Include your database connection file
include 'db_connect.php';

// Initialize search variable
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Fetch products data with search
$sql = "SELECT ProductID, ProductName, Quantity FROM products WHERE ProductName LIKE ?";
$stmt = $mysqli->prepare($sql);
$search_param = "%" . $search . "%";
$stmt->bind_param("s", $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="product_list.css">
    <title>Product List</title>
</head>

<body>
    <h2>Product List</h2>

    <!-- Search form -->
    <form method="GET" action="product_list.php">
        <input type="text" id="search" name="search" class="search" value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search Product Name">
        <input type="submit" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>
    <table border="1">
        <thead>
            <tr>
                <th>ProductID</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row["ProductID"] . "</td>
                            <td>" . $row["ProductName"] . "</td>
                            <td>" . $row["Quantity"] . "</td>
                            <td>
                                <a href='edit_product.php?product_id=" . $row["ProductID"] . "'>Edit</a>
                                <a class='edit' href='production.php'>Add Production</a>
                                <a class='edit' href='create_invoice.php?product_id=" . $row["ProductID"] . "'>Create Invoice</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No products found</td></tr>";
            }
            $result->free_result();
            $stmt->close();
            $mysqli->close();
            ?>
        </tbody>
    </table>

</body>

</html>