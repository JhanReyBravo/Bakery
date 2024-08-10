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
    <title>Raw Material Details</title>
    <link rel="stylesheet" href="view.css">
</head>

<body>
    <div class="container">
        <?php
        // Check if mat_id is set
        if (isset($_GET['mat_id'])) {
            $mat_id = $_GET['mat_id'];

            // Prepare the query
            $stmt = $mysqli->prepare("SELECT MatID, MaterialName, Quantity, Unit FROM rawmat WHERE MatID = ?");
            $stmt->bind_param("i", $mat_id);

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $material = $result->fetch_assoc();
                    echo "<h2>Raw Material Details</h2>";
                    echo "<ul class='details'>";
                    echo "<li><strong>Material Name:</strong> " . htmlspecialchars($material['MaterialName']) . "</li>";
                    echo "<li><strong>Quantity:</strong> " . $material['Quantity'] . "</li>";
                    echo "<li><strong>Unit:</strong> " . $material['Unit'] . "</li>";
                    echo "</ul>";
                } else {
                    echo "<p>Material not found.</p>";
                }
            } else {
                echo "<p>Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            echo "<p>Invalid request.</p>";
        }

        // Close database connection
        $mysqli->close();
        ?>
        <a class="back-link" href="rawmat_list.php">Back to Raw Materials List</a>
    </div>
</body>

</html>