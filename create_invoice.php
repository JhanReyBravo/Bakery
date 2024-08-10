<?php
// Include your database connection file
include 'db_connect.php';

function fetchCustomerOptions($prefilledCustomerID = null)
{
    global $conn;

    $stmt = $conn->prepare("SELECT CustomerID, Name FROM customers ORDER BY Name ASC");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $selected = ($row['CustomerID'] == $prefilledCustomerID) ? ' selected' : '';
                echo '<option value="' . $row['CustomerID'] . '"' . $selected . '>' . $row['Name'] . '</option>';
            }
        } else {
            echo '<option value="">No customers available</option>';
        }
    } else {
        echo '<option value="">Error loading customers</option>';
    }
    $stmt->close();
}

$invoiceID = null;
$totalSales = 0;
$selectedCustomerID = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerID = $_POST['customer_id'];
    $invoiceDate = $_POST['invoice_date'];
    $productNames = $_POST['products'];
    $quantitiesSold = $_POST['quantity_sold'];
    $types = $_POST['product_type'];
    $retailPrices = $_POST['retail_price'];
    $wholesalePrices = $_POST['wholesale_price'];
    $totalPrices = $_POST['total_price'];

    $totalAmount = 0;

    $conn->begin_transaction();

    try {
        // Prepare and execute the invoice insertion
        $insert_invoice_sql = "INSERT INTO invoices (CustomerID, InvoiceDate, TotalAmount, TotalSales, PaymentStatus) VALUES (?, ?, ?, ?, 'Unpaid')";
        $stmt_invoice = $conn->prepare($insert_invoice_sql);
        $stmt_invoice->bind_param("isdd", $customerID, $invoiceDate, $totalAmount, $totalSales);

        if ($stmt_invoice->execute()) {
            $invoiceID = $stmt_invoice->insert_id;

            // Prepare and execute the invoice details insertion
            $insert_details_sql = "INSERT INTO invoice_details (InvoiceID, ProductID, QuantitySold, Price, TotalAmount, ProductType) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_details = $conn->prepare($insert_details_sql);

            foreach ($productNames as $index => $productName) {
                // Fetch ProductID based on ProductName
                $productID_sql = "SELECT ProductID FROM products WHERE ProductName = ?";
                $stmt_productID = $conn->prepare($productID_sql);
                $stmt_productID->bind_param("s", $productName);
                $stmt_productID->execute();
                $stmt_productID->bind_result($productID);
                $stmt_productID->fetch();
                $stmt_productID->close();

                $quantitySold = $quantitiesSold[$index];
                $type = $types[$index];
                $retailPrice = floatval(str_replace('$', '', $retailPrices[$index]));
                $wholesalePrice = floatval(str_replace('$', '', $wholesalePrices[$index]));
                $totalDetailAmount = floatval(str_replace('$', '', $totalPrices[$index]));

                // Determine the price based on product type
                $price = $type === 'Retail' ? $retailPrice : $wholesalePrice;

                $totalAmount += $totalDetailAmount;
                $totalSales += $totalDetailAmount;

                // Correct type definition string and parameters
                $stmt_details->bind_param("iiidss", $invoiceID, $productID, $quantitySold, $price, $totalDetailAmount, $type);
                if (!$stmt_details->execute()) {
                    error_log("Error inserting into invoice_details table: " . $stmt_details->error);
                    throw new Exception("Error inserting into invoice_details table: " . $stmt_details->error);
                }

                // Update product quantity in products table
                $update_quantity_sql = "UPDATE products SET Quantity = Quantity - ? WHERE ProductID = ?";
                $stmt_update_quantity = $conn->prepare($update_quantity_sql);
                $stmt_update_quantity->bind_param("ii", $quantitySold, $productID);
                if (!$stmt_update_quantity->execute()) {
                    error_log("Error updating product quantity: " . $stmt_update_quantity->error);
                    throw new Exception("Error updating product quantity: " . $stmt_update_quantity->error);
                }
                $stmt_update_quantity->close();
            }

            // Update customer account balance
            $update_customer_sql = "UPDATE customers SET AccountBalance = AccountBalance + ? WHERE CustomerID = ?";
            $stmt_customer = $conn->prepare($update_customer_sql);
            $stmt_customer->bind_param("di", $totalAmount, $customerID);
            if (!$stmt_customer->execute()) {
                throw new Exception("Error updating customer account balance: " . $stmt_customer->error);
            }
            $stmt_customer->close();

            // Update total amount and total sales in the invoices table
            $update_invoice_sql = "UPDATE invoices SET TotalAmount = ?, TotalSales = ? WHERE InvoiceID = ?";
            $stmt_update_invoice = $conn->prepare($update_invoice_sql);
            $stmt_update_invoice->bind_param("ddi", $totalAmount, $totalSales, $invoiceID);
            if (!$stmt_update_invoice->execute()) {
                throw new Exception("Error updating total amount and total sales in invoices table: " . $stmt_update_invoice->error);
            }
            $stmt_update_invoice->close();

            $conn->commit();

            echo '<script>
            if (confirm("Invoice created successfully. Do you want to print the Invoice receipt?")) {
                window.location.href = "print_invoice.php?invoiceID=' . $invoiceID . '";
            } else {
                window.location.href = "create_invoice.php";
            }
        </script>';

        } else {
            throw new Exception("Error inserting into invoices table: " . $stmt_invoice->error);
        }

        $stmt_details->close();
        $stmt_invoice->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <link rel="stylesheet" href="create_invoice.css">
    <script>
        function checkStockAvailability(productName, quantity, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "check_stock.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);
                    callback(response);
                }
            };
            xhr.send("productName=" + encodeURIComponent(productName) + "&quantity=" + encodeURIComponent(quantity));
        }

        function validateStockAndSubmit(event) {
            event.preventDefault(); // Prevent form submission
            var rows = document.querySelectorAll("#invoice tbody tr");
            var allProductsInStock = true;
            var messages = [];

            rows.forEach(function (row, index) {
                var productName = row.querySelector('select[name="products[]"]').value;
                var quantity = row.querySelector('input[name="quantity_sold[]"]').value;

                if (productName && quantity) {
                    checkStockAvailability(productName, quantity, function (response) {
                        if (response.status === "error") {
                            allProductsInStock = false;
                            messages.push("Product: " + productName + " (Requested: " + quantity + ", Available: " + response.stock + ")");
                        }

                        if (index === rows.length - 1) {
                            if (allProductsInStock) {
                                document.querySelector("form").submit();
                            } else {
                                alert("Insufficient stock for the following products:\n" + messages.join("\n"));
                            }
                        }
                    });
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            document.querySelector("form").addEventListener("submit", validateStockAndSubmit);
        });

        function getProductPrice(select) {
            var productName = select.closest('tr').querySelector('select[name="products[]"]').value;
            var row = select.closest('tr');
            var retailPriceInput = row.querySelector('input[name="retail_price[]"]');
            var wholesalePriceInput = row.querySelector('input[name="wholesale_price[]"]');
            var typeSelect = row.querySelector('select[name="product_type[]"]');
            var retailPrice = 0;
            var wholesalePrice = 0;

            if (productName in productPrices) {
                var product = productPrices[productName];

                retailPrice = product.retail;
                wholesalePrice = product.wholesale;

                if (typeSelect.value === 'Retail') {
                    retailPriceInput.value = retailPrice.toFixed(2);
                    wholesalePriceInput.value = ''; // Clear wholesale price
                } else if (typeSelect.value === 'Wholesale') {
                    wholesalePriceInput.value = wholesalePrice.toFixed(2);
                    retailPriceInput.value = ''; // Clear retail price
                }

                // Calculate the total amount for the row
                calculateTotal(row);
            } else {
                retailPriceInput.value = '';
                wholesalePriceInput.value = '';
            }
        }

        function calculateTotal(row) {
            var quantityInput = row.querySelector('input[name="quantity_sold[]"]');
            var typeSelect = row.querySelector('select[name="product_type[]"]');
            var retailPriceInput = row.querySelector('input[name="retail_price[]"]');
            var wholesalePriceInput = row.querySelector('input[name="wholesale_price[]"]');
            var totalPriceInput = row.querySelector('input[name="total_price[]"]');
            var quantity = parseFloat(quantityInput.value) || 0;
            var price = 0;

            if (typeSelect.value === 'Retail') {
                price = parseFloat(retailPriceInput.value) || 0;
            } else if (typeSelect.value === 'Wholesale') {
                price = parseFloat(wholesalePriceInput.value) || 0;
            }

            var total = quantity * price;
            totalPriceInput.value = total.toFixed(2);
        }

        function addProduct() {
            var table = document.getElementById("invoice").getElementsByTagName('tbody')[0];
            var row = table.insertRow();
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);
            var cell5 = row.insertCell(4);
            var cell6 = row.insertCell(5);
            var cell7 = row.insertCell(6);

            var productOptions = '<?php
            $sql = "SELECT ProductName FROM products";
            $result = $conn->query($sql);
            $options = '';
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $options .= '<option value="' . addslashes($row["ProductName"]) . '">' . addslashes($row["ProductName"]) . '</option>';
                }
                $result->free_result();
            }
            echo addslashes($options);
            ?>';

            cell1.innerHTML = '<select name="products[]" required onchange="getProductPrice(this)"><option value="">Select Product</option>' + productOptions + '</select>';
            cell2.innerHTML = '<input type="number" name="quantity_sold[]" min="1" required onchange="calculateTotal(this.parentNode.parentNode)">';
            cell3.innerHTML = '<select name="product_type[]" required onchange="getProductPrice(this)"><option value="Retail">Retail</option><option value="Wholesale">Wholesale</option></select>';
            cell4.innerHTML = '<input type="text" name="retail_price[]" readonly>';
            cell5.innerHTML = '<input type="text" name="wholesale_price[]" readonly>';
            cell6.innerHTML = '<input type="text" name="total_price[]" readonly>';
            cell7.innerHTML = '<button type="button" class="remove" onclick="removeProduct(this)">Remove</button>';
        }

        function removeProduct(button) {
            // Remove the row containing the button that was clicked
            button.closest('tr').remove();
        }

        var productPrices = <?php
        $sql = "SELECT ProductName, RetailPrice, WholesalePrice FROM products";
        $result = $conn->query($sql);
        $prices = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $prices[$row['ProductName']] = [
                    'retail' => (float) $row['RetailPrice'],
                    'wholesale' => (float) $row['WholesalePrice']
                ];
            }
            $result->free_result();
        }
        echo json_encode($prices);
        ?>;
    </script>
</head>

<body>
    <header>
        <img src="Alogo.png" alt="Website Logo" class="logo">
        <h1>THE A BAKERY BCD</h1>
    </header>
    <nav>
        <ul>
            <li><a href="home.html">Home</a></li>
        </ul>
    </nav>

    <?php
    // Ensure you have a working connection to your database
    $conn = new mysqli("localhost", "root", "", "bakerybcd");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Your existing code for handling form submission
        // Example: Handle the form submission here (e.g., insert into database, etc.)
    } else {
        ?>
        <h2>Create Invoice</h2>
        <form action="create_invoice.php" method="post">
            <label for="customer_id">Customer:</label><br>
            <select id="customer_id" name="customer_id" required>
                <?php fetchCustomerOptions($selectedCustomerID); ?>

            </select><br><br>

            <label for="invoice_date">Invoice Date:</label><br>
            <input type="date" id="invoice_date" name="invoice_date" required><br><br>

            <table id="invoice">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity Sold</th>
                        <th>Type</th>
                        <th>Retail Price</th>
                        <th>Wholesale Price</th>
                        <th>Total Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="products[]" required onchange="getProductPrice(this)">
                                <option value="">Select Product</option>
                                <?php
                                $sql = "SELECT ProductName FROM products";
                                $result = $conn->query($sql);
                                if ($result) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<option value="' . $row["ProductName"] . '">' . $row["ProductName"] . '</option>';
                                    }
                                    $result->free_result();
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="number" name="quantity_sold[]" min="1" required
                                onchange="calculateTotal(this.parentNode.parentNode)"></td>
                        <td>
                            <select name="product_type[]" required onchange="getProductPrice(this)">
                                <option value="Retail">Retail</option>
                                <option value="Wholesale">Wholesale</option>
                            </select>
                        </td>
                        <td><input type="text" name="retail_price[]" readonly></td>
                        <td><input type="text" name="wholesale_price[]" readonly></td>
                        <td><input type="text" name="total_price[]" readonly></td>
                        <td><button type="button" class="remove" onclick="removeProduct(this)">Remove</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" onclick="addProduct()">Add Product</button><br><br>
            <input type="submit" class="submit" value="Create Invoice">
            <a href="invoice_list.php" class="home">Invoice List</a>
            <a href="product_list.php" class="home">Product List</a>
            <a href="home.html" class="home">Back</a>
        </form>
        <?php
    } // End of else block
    ?>
</body>

</html>