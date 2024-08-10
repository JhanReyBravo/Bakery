<?php
// Include your database connection file
include 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $description = $_POST['description'];
    $prod_date = $_POST['prod_date'];
    $products = $_POST['products'];
    $quantities_produced = $_POST['quantity_produced'];

    // Initialize total amount
    $total_amount = 0; // Adjust if you need to calculate total amount based on your requirements

    $enough_materials = true;
    $insufficient_materials = [];

    // Check if there are enough raw materials for each product
    for ($i = 0; $i < count($products); $i++) {
        $productID = $products[$i];
        $quantity_produced = $quantities_produced[$i];

        // Fetch materials and quantities required for the selected product
        $materials_sql = "SELECT pm.MatID, pm.QuantityRequired, rm.MaterialName FROM product_materials pm JOIN rawmat rm ON pm.MatID = rm.MatID WHERE pm.ProductID = ?";
        $stmt_materials = $mysqli->prepare($materials_sql);
        $stmt_materials->bind_param("i", $productID);
        $stmt_materials->execute();
        $result_materials = $stmt_materials->get_result();

        while ($row = $result_materials->fetch_assoc()) {
            $materialID = $row['MatID'];
            $quantity_required = $row['QuantityRequired'];
            $quantity_used = $quantity_produced * $quantity_required;
            $material_name = $row['MaterialName'];

            // Check available quantity of the material
            $check_quantity_sql = "SELECT Quantity FROM rawmat WHERE MatID = ?";
            $stmt_check_quantity = $mysqli->prepare($check_quantity_sql);
            $stmt_check_quantity->bind_param("i", $materialID);
            $stmt_check_quantity->execute();
            $result_check_quantity = $stmt_check_quantity->get_result();
            $available_quantity = $result_check_quantity->fetch_assoc()['Quantity'];

            if ($available_quantity < $quantity_used) {
                $enough_materials = false;
                $insufficient_materials[] = [
                    'MaterialName' => $material_name,
                    'RequiredQuantity' => $quantity_used,
                    'AvailableQuantity' => $available_quantity
                ];
            }

            $stmt_check_quantity->close();
        }
        $stmt_materials->close();
    }

    if (!$enough_materials) {
        $message = "Insufficient raw materials for production: ";
        foreach ($insufficient_materials as $material) {
            $message .= $material['MaterialName'] . " (Required: " . $material['RequiredQuantity'] . ", Available: " . $material['AvailableQuantity'] . ")";
        }
        echo '<script>';
        echo 'alert("' . addslashes($message) . '");';
        echo 'window.location.href = "production.php";';
        echo '</script>';
        exit;
    }


    // Insert into production table
    $insert_production_sql = "INSERT INTO production (Description, ProdDate, TotalAmount) VALUES (?, ?, ?)";
    $stmt_production = $mysqli->prepare($insert_production_sql);
    $stmt_production->bind_param("ssd", $description, $prod_date, $total_amount);

    // Execute production table insert
    if ($stmt_production->execute()) {
        $prodID = $stmt_production->insert_id; // Get the auto-generated ID from production table

        // Prepare and execute production_details inserts
        $insert_details_sql = "INSERT INTO production_details (ProdID, MaterialID, QuantityUsed, ProductID, QuantityProduced) VALUES (?, ?, ?, ?, ?)";
        $stmt_details = $mysqli->prepare($insert_details_sql);

        // Loop through arrays to insert details
        for ($i = 0; $i < count($products); $i++) {
            $productID = $products[$i];
            $quantity_produced = $quantities_produced[$i];

            // Fetch materials and quantities required for the selected product
            $materials_sql = "SELECT MatID, QuantityRequired FROM product_materials WHERE ProductID = ?";
            $stmt_materials = $mysqli->prepare($materials_sql);
            $stmt_materials->bind_param("i", $productID);
            $stmt_materials->execute();
            $result_materials = $stmt_materials->get_result();

            while ($row = $result_materials->fetch_assoc()) {
                $materialID = $row['MatID'];
                $quantity_required = $row['QuantityRequired'];
                $quantity_used = $quantity_produced * $quantity_required;

                $stmt_details->bind_param("iiiii", $prodID, $materialID, $quantity_used, $productID, $quantity_produced);
                $stmt_details->execute();

                // Update rawmat table
                $update_rawmat_sql = "UPDATE rawmat SET Quantity = Quantity - ? WHERE MatID = ?";
                $stmt_rawmat = $mysqli->prepare($update_rawmat_sql);
                $stmt_rawmat->bind_param("ii", $quantity_used, $materialID);
                $stmt_rawmat->execute();
                $stmt_rawmat->close();
            }

            // Update products table directly with quantity produced
            $update_products_sql = "UPDATE products SET Quantity = Quantity + ? WHERE ProductID = ?";
            $stmt_products = $mysqli->prepare($update_products_sql);
            $stmt_products->bind_param("ii", $quantity_produced, $productID);
            $stmt_products->execute();
            $stmt_products->close();
            $stmt_materials->close();
        }

        // Close statements
        $stmt_details->close();
        $stmt_production->close();

        // Redirect or display success message
        echo '<script>alert("Production recorded successfully."); window.location.href = "production.php";</script>';
    } else {
        echo "Error inserting into production table: " . $mysqli->error;
    }

    // Close database connection
    $mysqli->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="production.css">
    <title>Production</title>

    <script>
        function addProduction() {
            var table = document.getElementById("production").getElementsByTagName('tbody')[0];
            var row = table.insertRow();
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);

            var productOptions = '<?php
            $sql = "SELECT ProductID, ProductName FROM products";
            $result = $mysqli->query($sql);
            $options = "";
            while ($row = $result->fetch_assoc()) {
                $options .= '<option value="' . $row["ProductID"] . '">' . $row["ProductName"] . '</option>';
            }
            $result->free_result();
            echo $options;
            ?>';

            cell1.innerHTML = '<select name="products[]" required onchange="getMaterials(this)"><option value="">Select Product</option>' + productOptions + '</select>';
            cell2.innerHTML = '<input type="number" name="quantity_produced[]" min="1" required>';
            cell3.innerHTML = '<div></div>';
            cell4.innerHTML = '<button type="button" class="remove" onclick="removeProduction(this)">Remove</button>';
        }

        function removeProduction(button) {
            var row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }

        function getMaterials(select) {
            var productID = select.value;
            var row = select.parentNode.parentNode;
            var materialsDiv = row.cells[2].firstChild;
            var quantitiesDiv = row.cells[3].firstChild;

            // AJAX request to fetch materials based on selected product
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "getMaterials.php?productID=" + productID, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var materials = JSON.parse(xhr.responseText);

                    // Populate materials and quantities divs
                    materialsDiv.innerHTML = '';
                    quantitiesDiv.innerHTML = '';
                    materials.forEach(function (material) {
                        materialsDiv.innerHTML += '<div>' + material.MaterialName + '</div>';
                        quantitiesDiv.innerHTML += '<div>' + material.QuantityRequired + '</div>';
                    });
                }
            };
            xhr.send();
        }
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
    <h2>Production</h2>
    <form action="production.php" method="POST">
        <label for="description">Description:</label><br>
        <input type="text" id="description" name="description" required><br><br>

        <label for="prod_date">Production Date:</label><br>
        <input type="date" id="prod_date" name="prod_date" required><br><br>

        <table id="production">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity Produced</th>
                    <th>Materials</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="products[]" required onchange="getMaterials(this)">
                            <option value="">Select Product</option>
                            <?php
                            $sql = "SELECT ProductID, ProductName FROM products";
                            $result = $mysqli->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['ProductID'] . '">' . $row['ProductName'] . '</option>';
                            }
                            $result->free_result();
                            ?>
                        </select>
                    </td>
                    <td><input type="number" name="quantity_produced[]" min="1" required></td>
                    <td>
                        <div></div>
                    </td>
                    <td>
                        <button type="button" class="remove" onclick="removeProduction(this)">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="button" onclick="addProduction()">Add Production</button><br><br>

        <input type="submit" class="submit" value="Submit Production">
        <a href="product_list.php" class="home">Product List</a>
        <a href="rawmat_list.php" class="home">Rawmat List</a>
        <a href="home.html" class="home">Back</a>
    </form>
</body>

</html>