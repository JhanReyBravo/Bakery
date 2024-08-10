<?php
// Include your database connection file
include 'db_connect.php';

// Initialize search variable
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Fetch raw materials data with search
$sql = "SELECT MatID, MaterialName, Quantity, Unit FROM rawmat WHERE MaterialName LIKE ?";
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
    <link rel="stylesheet" href="rawmat_list.css">
    <title>Raw Materials List</title>
</head>

<body>
    <h2>Raw Materials List</h2>

    <!-- Search form -->
    <form method="GET" action="rawmat_list.php">
        <input type="text" id="search" name="search" class="search" value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search Material Name:">
        <input type="submit" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>MatID</th>
                <th>Material Name</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row["MatID"] . "</td>
                            <td>" . $row["MaterialName"] . "</td>
                            <td>" . $row["Quantity"] . "</td>
                            <td>" . $row["Unit"] . "</td>
                            <td>
                                <a href='view_rawmat.php?mat_id=" . $row["MatID"] . "'>View</a>
                                <a class='edit' href='production.php?mat_id=" . $row["MatID"] . "'>Add Production</a>
                                    <a class='edit' href='receiving.php'>Add Receiving</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No raw materials found</td></tr>";
            }
            $result->free_result();
            $stmt->close();
            $mysqli->close();
            ?>
        </tbody>
    </table>
</body>

</html>