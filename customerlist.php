<?php
// Include your database connection file
include 'db_connect.php';

function fetchAllCustomers($search_term = "")
{
    global $conn;

    if ($search_term) {
        // Prepare the query with search term
        $like_term = '%' . $search_term . '%';
        $stmt = $conn->prepare("SELECT CustomerID, Name, Address, Phone, Email, AccountBalance FROM customers WHERE Name LIKE ? OR Address LIKE ?");
        $stmt->bind_param("ss", $like_term, $like_term);
    } else {
        // Prepare the query to fetch all customers
        $stmt = $conn->prepare("SELECT CustomerID, Name, Address, Phone, Email, AccountBalance FROM customers ORDER BY Name ASC");
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo '<table border="1">';
            echo '<tr><th>Name</th><th>Address</th><th>Phone</th><th>Email</th><th>Account Balance</th><th>Actions</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row["Name"] . '</td>';
                echo '<td>' . $row["Address"] . '</td>';
                echo '<td>' . $row["Phone"] . '</td>';
                echo '<td>' . $row["Email"] . '</td>';
                echo '<td>' . $row["AccountBalance"] . '</td>';
                echo '<td>
                        <a href="view_customer.php?customer_id=' . $row["CustomerID"] . '">View</a>
                        <a class="edit" href="edit_customer.php?customer_id=' . $row["CustomerID"] . '">Edit</a>
                        <a class="edit" href="addcustomer.html">Add Customer</a>
                        <a class="edit" href="create_invoice.php?customer_id=' . $row["CustomerID"] . '">Create Invoice</a>
                        <a class="edit" href="apppayment.php?customer_id=' . $row["CustomerID"] . '">Apply Payment</a>
                        <a class="delete" href="delete_customer.php?customer_id=' . $row["CustomerID"] . '" onclick="return confirm(\'Are you sure you want to delete this customer?\');">Delete</a>
                      </td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo "No customers found<br>";
        }
    } else {
        echo "Error: " . $stmt->error . "<br>";
    }

    $stmt->close();
}

// Handle search term if provided
$search_term = "";
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer List</title>
    <link rel="stylesheet" type="text/css" href="customerlist.css">
</head>

<body>

    <h1>Customer List</h1>
    <form action="customerlist.php" method="GET">
        <input type="text" id="search_term" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>"
            placeholder="Search Customer Name or Address">
        <input type="submit" class="search" value="Search">
        <a class="home" href="home.html">Back</a>
    </form>
    <?php fetchAllCustomers($search_term); ?>
    <br>
</body>

</html>