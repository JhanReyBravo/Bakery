<?php
// Include your database connection file
include 'db_connect.php';

// Check if expense_id is provided
if (isset($_GET['expense_id'])) {
    $expenseID = $_GET['expense_id'];

    // Fetch expense details from the database
    $sql = "SELECT ExpenseID, ExpenseDate, Description, Amount FROM daily_expenses WHERE ExpenseID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $expenseID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $expense = $result->fetch_assoc();
    } else {
        echo "Expense not found";
        exit;
    }

    // Close statement
    $stmt->close();
} else {
    echo "No expense ID provided";
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
    <title>View Expense</title>
    <link rel="stylesheet" href="expenses_list.css">
</head>

<body>
    <h2>View Expense</h2>
    <table>
        <tr>
            <th>Expense ID</th>
            <td><?php echo $expense['ExpenseID']; ?></td>
        </tr>
        <tr>
            <th>Expense Date</th>
            <td><?php echo $expense['ExpenseDate']; ?></td>
        </tr>
        <tr>
            <th>Description</th>
            <td><?php echo $expense['Description']; ?></td>
        </tr>
        <tr>
            <th>Amount</th>
            <td><?php echo $expense['Amount']; ?></td>
        </tr>
    </table>
    <a href="expenses_list.php">Back to Expenses List</a>
</body>

</html>