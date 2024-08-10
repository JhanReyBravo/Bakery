<?php
include 'db_connect.php'; // Make sure this file contains your database connection code

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expenseDate = $_POST['expense_date'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];

    // Insert the new daily expense
    $stmt = $conn->prepare("INSERT INTO daily_expenses (ExpenseDate, Description, Amount) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $expenseDate, $description, $amount);

    if ($stmt->execute()) {
        // Get the latest balance from the last expense entry
        $result = $conn->query("SELECT Balance FROM expenses ORDER BY ExpenseID DESC LIMIT 1");
        $row = $result->fetch_assoc();
        $previousBalance = $row ? $row['Balance'] : 0.00;

        // Calculate the new balance
        $newBalance = $previousBalance - $amount;

        // Insert the balance update into the expenses table
        $stmt_expenses = $conn->prepare("INSERT INTO expenses (ExpenseDate, Description, Amount, Balance) VALUES (?, ?, ?, ?)");
        $stmt_expenses->bind_param("ssdd", $expenseDate, $description, $amount, $newBalance);

        if ($stmt_expenses->execute()) {
            echo '<script>alert("New daily expense recorded sucessfully"); window.location.href = "expenses.php";</script>';
        } else {
            echo '<script>alert("Error updating expenses table"); window.location.href = "expenses.php";</script>';
        }

        $stmt_expenses->close();
    } else {
        echo "Error recording daily expense: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <header>
        <img src="Alogo.png" alt="Website Logo" class="logo">
        <h1>THE A BAKERY BCD</h1>
    </header>
    <nav>
        <ul>
            <li><a href="home.html">Home</a></li>
        </ul>
    </nav>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Expenses</title>
    <link rel="stylesheet" href="expenses.css"> <!-- Link to your CSS file -->
</head>

<body>
    <h2>Add Daily Expense</h2>
    <form action="expenses.php" method="POST">
        <label for="description">Description:</label><br>
        <input type="text" id="description" name="description" required><br><br>

        <label for="expense_date">Expense Date:</label><br>
        <input type="date" id="expense_date" name="expense_date" required><br><br>

        <label for="amount">Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" min="0" required><br><br>

        <input type="submit" class="submit" value="Add Expense">
        <a href="expenses_list.php" class="home">Expenses List</a><br>
        <a href="home.html" class="home">Back</a>
    </form>
</body>

</html>