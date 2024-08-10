<?php
// Include your database connection file
include 'db_connect.php';

// Check if invoice_id is provided in the query string
if (isset($_GET['invoice_id'])) {
    $invoiceID = $_GET['invoice_id'];

    // Fetch invoice details
    $sql = "SELECT invoices.CustomerID, invoices.InvoiceDate, invoices.TotalAmount, invoices.PaymentStatus
            FROM invoices
            WHERE invoices.InvoiceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();

    // Fetch invoice items
    $sql_items = "SELECT invoice_details.ProductID, invoice_details.QuantitySold, invoice_details.Price
                  FROM invoice_details
                  WHERE invoice_details.InvoiceID = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $invoiceID);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    $invoice_items = $result_items->fetch_all(MYSQLI_ASSOC);

    // Fetch customers and products
    $sql_customers = "SELECT CustomerID, Name, AccountBalance FROM customers";
    $result_customers = $conn->query($sql_customers);
    $customers = $result_customers->fetch_all(MYSQLI_ASSOC);

    $sql_products = "SELECT ProductID, ProductName FROM products";
    $result_products = $conn->query($sql_products);
    $products = $result_products->fetch_all(MYSQLI_ASSOC);

    // Close statements
    $stmt->close();
    $stmt_items->close();
} else {
    die("Invoice ID not provided.");
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize form inputs
    $customerID = $_POST['customer_id'];
    $invoiceDate = $_POST['invoice_date'];
    $products = $_POST['products'];
    $quantitiesSold = $_POST['quantity_sold'];
    $prices = $_POST['price'];

    // Initialize total amount
    $totalAmount = 0;

    // Calculate total amount
    for ($i = 0; $i < count($products); $i++) {
        $totalAmount += $quantitiesSold[$i] * $prices[$i];
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch current customer balance and old invoice total amount
        $stmt_balance = $conn->prepare("SELECT AccountBalance FROM customers WHERE CustomerID = ?");
        $stmt_balance->bind_param("i", $customerID);
        $stmt_balance->execute();
        $result_balance = $stmt_balance->get_result();
        $customer = $result_balance->fetch_assoc();
        $currentBalance = $customer['AccountBalance'];
        $stmt_balance->close();

        $stmt_old_total = $conn->prepare("SELECT TotalAmount FROM invoices WHERE InvoiceID = ?");
        $stmt_old_total->bind_param("i", $invoiceID);
        $stmt_old_total->execute();
        $result_old_total = $stmt_old_total->get_result();
        $oldInvoice = $result_old_total->fetch_assoc();
        $oldTotalAmount = $oldInvoice['TotalAmount'];
        $stmt_old_total->close();

        // Calculate new balance
        $balanceDifference = $totalAmount - $oldTotalAmount;
        $newBalance = $currentBalance - $balanceDifference;

        // Update invoice details
        $update_invoice_sql = "UPDATE invoices SET CustomerID = ?, InvoiceDate = ?, TotalAmount = ?, PaymentStatus = 'Unpaid' WHERE InvoiceID = ?";
        $stmt_invoice = $conn->prepare($update_invoice_sql);
        $stmt_invoice->bind_param("isdi", $customerID, $invoiceDate, $totalAmount, $invoiceID);
        $stmt_invoice->execute();

        // Delete existing invoice items
        $delete_items_sql = "DELETE FROM invoice_details WHERE InvoiceID = ?";
        $stmt_delete = $conn->prepare($delete_items_sql);
        $stmt_delete->bind_param("i", $invoiceID);
        $stmt_delete->execute();

        // Insert updated invoice items
        $insert_details_sql = "INSERT INTO invoice_details (InvoiceID, ProductID, QuantitySold, Price, TotalAmount) VALUES (?, ?, ?, ?, ?)";
        $stmt_details = $conn->prepare($insert_details_sql);

        for ($i = 0; $i < count($products); $i++) {
            $productID = $products[$i];
            $quantitySold = $quantitiesSold[$i];
            $price = $prices[$i];
            $totalDetailAmount = $quantitySold * $price;

            $stmt_details->bind_param("iiidd", $invoiceID, $productID, $quantitySold, $price, $totalDetailAmount);
            $stmt_details->execute();
        }

        // Update customer balance
        $update_balance_sql = "UPDATE customers SET AccountBalance = ? WHERE CustomerID = ?";
        $stmt_update_balance = $conn->prepare($update_balance_sql);
        $stmt_update_balance->bind_param("di", $newBalance, $customerID);
        $stmt_update_balance->execute();

        // Commit transaction
        $conn->commit();

        // Close statements
        $stmt_invoice->close();
        $stmt_delete->close();
        $stmt_details->close();
        $stmt_update_balance->close();

        // Redirect to invoice list
        header("Location: invoice_list.php");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

    // Close database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice</title>
    <link rel="stylesheet" href="expenses_list.css">
</head>

<body>
    <h2>Edit Invoice</h2>
    <form action="edit_invoice.php?invoice_id=<?php echo $invoiceID; ?>" method="POST">
        <label for="customer_id">Customer:</label><br>
        <select id="customer_id" name="customer_id" required>
            <?php foreach ($customers as $customer): ?>
                <option value="<?php echo $customer['CustomerID']; ?>" <?php echo $customer['CustomerID'] == $invoice['CustomerID'] ? 'selected' : ''; ?>>
                    <?php echo $customer['Name']; ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="invoice_date">Invoice Date:</label><br>
        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice['InvoiceDate']; ?>"
            required><br><br>

        <table id="invoice">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice_items as $item): ?>
                    <tr>
                        <td>
                            <select name="products[]" required>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['ProductID']; ?>" <?php echo $product['ProductID'] == $item['ProductID'] ? 'selected' : ''; ?>>
                                        <?php echo $product['ProductName']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="quantity_sold[]" value="<?php echo $item['QuantitySold']; ?>" min="1"
                                required></td>
                        <td><input type="number" name="price[]" value="<?php echo $item['Price']; ?>" step="0.01" min="0"
                                required></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" onclick="addProduct()">Add Product</button><br><br>

        <input type="submit" class="submit" value="Update Invoice">
        <a href="invoice_list.php" class="home">Back</a>
    </form>

    <script>
        function addProduct() {
            var table = document.getElementById("invoice").getElementsByTagName('tbody')[0];
            var row = table.insertRow();
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);

            var productOptions = '<?php
            $options = "";
            foreach ($products as $product) {
                $options .= '<option value="' . $product["ProductID"] . '">' . $product["ProductName"] . '</option>';
            }
            echo $options;
            ?>';

            cell1.innerHTML = '<select name="products[]" required><option value="">Select Product</option>' + productOptions + '</select>';
            cell2.innerHTML = '<input type="number" name="quantity_sold[]" min="1" required>';
            cell3.innerHTML = '<input type="number" name="price[]" step="0.01" min="0" required>';
        }
    </script>
</body>

</html>