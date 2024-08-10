<?php
// Include your database connection file
include 'db_connect.php';

// Initialize variables
$search_term = "";

// Check if search term is provided
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
}

// Prepare SQL query
if ($search_term) {
    $like_term = '%' . $search_term . '%';
    $sql = $conn->prepare("SELECT ExpenseID, ExpenseDate, Description, Amount FROM daily_expenses WHERE Description LIKE ?");
    $sql->bind_param("s", $like_term);
} else {
    $sql = $conn->prepare("SELECT ExpenseID, ExpenseDate, Description, Amount FROM daily_expenses");
}

// Execute SQL query
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    $expenses = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $expenses = [];
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expenses List</title>
    <link rel="stylesheet" href="expenses_list.css">
</head>

<body>
    <h2>Daily Expenses List</h2>
    <form action="expenses_list.php" method="GET">
        <input type="text" id="search_term" name="search_term" class="search"
            value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search Description">
        <input type="submit" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>
    <table>
        <thead>
            <tr>
                <th>Expense ID</th>
                <th>Expense Date</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($expenses) > 0): ?>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo $expense['ExpenseID']; ?></td>
                        <td><?php echo $expense['ExpenseDate']; ?></td>
                        <td><?php echo $expense['Description']; ?></td>
                        <td><?php echo $expense['Amount']; ?></td>
                        <td>
                            <a href="view_expense.php?expense_id=<?php echo $expense['ExpenseID']; ?>">View</a>
                            <a class="edit" href="edit_expense.php?expense_id=<?php echo $expense['ExpenseID']; ?>">Edit</a>
                            <a class="edit" href="expenses.php">Add New Expense</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No expenses found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>