<?php
// Include your database connection file
include 'db_connect.php';

// Check if mat_id is set
if (isset($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Update raw material record
        $material_name = $_POST['material_name'];
        $quantity = $_POST['quantity'];
        $unit = $_POST['unit'];

        $stmt = $mysqli->prepare("UPDATE rawmat SET MaterialName = ?, Quantity = ?, Unit = ? WHERE MatID = ?");
        $stmt->bind_param("sdsi", $material_name, $quantity, $unit, $mat_id);

        if ($stmt->execute()) {
            echo "<script>alert('Raw material updated successfully.'); window.location.href = 'rawmat_list.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Fetch raw material record to edit
        $stmt = $mysqli->prepare("SELECT MatID, MaterialName, Quantity, Unit FROM rawmat WHERE MatID = ?");
        $stmt->bind_param("i", $mat_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $material = $result->fetch_assoc();
            } else {
                echo "Material not found.";
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
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Raw Material</title>
    <link rel="stylesheet" href="rawmat_list.css">
</head>

<body>
    <h2>Edit Raw Material</h2>
    <form method="POST" action="edit_rawmat.php?mat_id=<?php echo htmlspecialchars($mat_id); ?>">
        <label for="material_name">Material Name:</label>
        <input type="text" id="material_name" name="material_name"
            value="<?php echo htmlspecialchars($material['MaterialName']); ?>" required><br>
        <label for="quantity">Quantity:</label>
        <input type="number" step="0.01" id="quantity" name="quantity"
            value="<?php echo htmlspecialchars($material['Quantity']); ?>" required><br>
        <label for="unit">Unit:</label>
        <input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($material['Unit']); ?>"
            required><br>
        <input type="submit" value="Update">
    </form>
    <a href="rawmat_list.php">Back to Raw Materials List</a>
</body>

</html>