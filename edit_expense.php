<?php
// Include your database connection file
include 'db_connect.php';

// Initialize variables
$expenseID = $expenseDate = $description = $amount = "";

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
        $expenseDate = $expense['ExpenseDate'];
        $description = $expense['Description'];
        $amount = $expense['Amount'];
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

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expenseID = $_POST['expense_id'];
    $expenseDate = $_POST['expense_date'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    // Update expense in the database
    $sql = "UPDATE daily_expenses SET ExpenseDate = ?, Description = ?, Amount = ? WHERE ExpenseID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $expenseDate, $description, $amount, $expenseID);

    if ($stmt->execute()) {
        echo '<script>alert("Expense updated successfully"); window.location.href = "expenses_list.php";</script>';
    } else {
        echo "Error updating expense: " . $stmt->error;
    }

    // Close statement
    $stmt->close();

    // Close database connection
    $conn->close();

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense</title>
    <link rel="stylesheet" href="expenses_list.css">
</head>

<body>
    <h2>Edit Expense</h2>
    <form action="edit_expense.php" method="POST">
        <input type="hidden" name="expense_id" value="<?php echo $expenseID; ?>">
        <label for="expense_date">Expense Date:</label><br>
        <input type="date" id="expense_date" name="expense_date" value="<?php echo $expenseDate; ?>" required><br><br>
        <label for="description">Description:</label><br>
        <input type="text" id="description" name="description" value="<?php echo $description; ?>" required><br><br>
        <label for="amount">Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" value="<?php echo $amount; ?>" required><br><br>
        <input type="submit" value="Update Expense">
    </form>
    <a href="expenses_list.php">Back to Expenses List</a>
</body>

</html>