<?php
include 'db_connect.php';

// Function to fetch suppliers and return options
function getSupplierOptions($mysqli)
{
    $sql = "SELECT SupplierID, SupplierName FROM suppliers";
    $result = $mysqli->query($sql);
    $options = '';

    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['SupplierID'] . '">' . $row['SupplierName'] . '</option>';
    }

    $result->free_result();
    return $options;
}

// Function to fetch materials and return options and default quantities
function getMaterialOptions($mysqli)
{
    $sql = "SELECT MatID, MaterialName, DefaultQuantity FROM rawmat";
    $result = $mysqli->query($sql);
    $options = '';
    $materialQuantities = array();

    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . $row['MatID'] . '">' . $row['MaterialName'] . '</option>';
        $materialQuantities[$row['MatID']] = $row['DefaultQuantity'];
    }

    $result->free_result();
    return array($options, $materialQuantities);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $supplier = $_POST['supplier'];
    $new_supplier = $_POST['new_supplier'];
    $date = $_POST['date'];
    $materials = $_POST['materials'];
    $quantities = $_POST['quantity'];
    $amounts = $_POST['amount'];

    // Handle new supplier
    if ($new_supplier) {
        // Insert new supplier into database
        $insert_supplier_sql = "INSERT INTO suppliers (SupplierName) VALUES (?)";
        $stmt_supplier = $mysqli->prepare($insert_supplier_sql);
        $stmt_supplier->bind_param("s", $new_supplier);
        $stmt_supplier->execute();
        $supplier = $stmt_supplier->insert_id; // Use the ID of the new supplier
        $stmt_supplier->close();
    }

    // Initialize total amount
    $total_amount = array_sum($amounts);

    // Insert into receiving table
    $insert_receiving_sql = "INSERT INTO receiving (SupplierID, RecDate, TotalAmount) VALUES (?, ?, ?)";
    $stmt_receiving = $mysqli->prepare($insert_receiving_sql);
    $stmt_receiving->bind_param("iss", $supplier, $date, $total_amount);

    // Execute receiving table insert
    if ($stmt_receiving->execute()) {
        $recID = $stmt_receiving->insert_id; // Get the auto-generated ID from receiving table

        // Prepare and execute receiving_details inserts
        $insert_details_sql = "INSERT INTO receiving_details (RecID, MaterialID, Quantity, Amount) VALUES (?, ?, ?, ?)";
        $stmt_details = $mysqli->prepare($insert_details_sql);

        // Loop through materials array to insert details
        for ($i = 0; $i < count($materials); $i++) {
            $materialID = $materials[$i];
            $quantity = $quantities[$i];
            $amount = $amounts[$i];

            $stmt_details->bind_param("iiid", $recID, $materialID, $quantity, $amount);
            $stmt_details->execute();

            // Update rawmat table
            $update_rawmat_sql = "UPDATE rawmat SET Quantity = Quantity + ? WHERE MatID = ?";
            $stmt_rawmat = $mysqli->prepare($update_rawmat_sql);
            $stmt_rawmat->bind_param("ii", $quantity, $materialID);
            $stmt_rawmat->execute();
            $stmt_rawmat->close();
        }

        // Close statements
        $stmt_details->close();
        $stmt_receiving->close();

        // Redirect or display success message
        echo '<script>alert("Materials received successfully."); window.location.href = "receiving.php";</script>';
    } else {
        echo "Error inserting into receiving table: " . $mysqli->error;
    }
}

// Get options for the dropdowns
$supplierOptions = getSupplierOptions($mysqli);
list($materialDropdownOptions, $materialQuantities) = getMaterialOptions($mysqli);

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Raw Materials</title>
    <link rel="stylesheet" href="receiving.css">
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
    <h2>Receive Raw Materials</h2>
    <form action="receiving.php" method="POST">
        <label for="supplier">Select Supplier:</label><br>
        <select id="supplier" class="exist" name="supplier">
            <option value="">Select Supplier</option>
            <?php echo $supplierOptions; ?>
        </select><br>
        <label class="add" for="new_supplier">Add New Supplier:</label><br>
        <input type="text" id="new_supplier" name="new_supplier" placeholder="New Supplier Name"><br><br>

        <label for="date">Received Date:</label><br>
        <input type="date" id="date" name="date" required><br><br>

        <table id="materials">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="materials[]" onchange="setDefaultQuantity(this)" required>
                            <option value="">Select Material</option>
                            <?php echo $materialDropdownOptions; ?>
                        </select>
                    </td>
                    <td><input type="number" name="quantity[]" min="1" required></td>
                    <td><input type="text" name="amount[]" required></td>
                    <td><button type="button" class="remove" onclick="removeMaterial(this)">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="add" onclick="addMaterial()">Add Another Material</button><br><br>

        <input type="submit" class="submit" value="Receive Materials">
        <a href="rawmat_list.php" class="home">Rawmat List</a>
        <a href="home.html" class="home">Back</a>
    </form>

    <script>
        var materialQuantities = <?php echo json_encode($materialQuantities); ?>;

        // Function to add new rows for additional materials
        function addMaterial() {
            var table = document.getElementById("materials").getElementsByTagName('tbody')[0];
            var row = table.insertRow();
            var cell1 = row.insertCell(0);
            var cell2 = row.insertCell(1);
            var cell3 = row.insertCell(2);
            var cell4 = row.insertCell(3);

            var materialOptions = `<?php echo $materialDropdownOptions; ?>`;

            cell1.innerHTML = '<select name="materials[]" onchange="setDefaultQuantity(this)" required><option value="">Select Material</option>' + materialOptions + '</select>';
            cell2.innerHTML = '<input type="number" name="quantity[]" min="1" required>';
            cell3.innerHTML = '<input type="text" name="amount[]" required>';
            cell4.innerHTML = '<button type="button" class="remove" onclick="removeMaterial(this)">Remove</button>';
        }

        // Function to remove a material row
        function removeMaterial(button) {
            var row = button.parentNode.parentNode;
            row.parentNode.removeChild(row);
        }

        // Function to set default quantity based on selected material
        function setDefaultQuantity(selectElement) {
            var selectedMaterialID = selectElement.value;
            var quantityField = selectElement.parentElement.nextElementSibling.firstElementChild;

            if (materialQuantities[selectedMaterialID]) {
                var currentQuantity = parseInt(quantityField.value) || 0;
                quantityField.value = materialQuantities[selectedMaterialID];
            } else {
                quantityField.value = '';
            }
        }
    </script>
</body>

</html>