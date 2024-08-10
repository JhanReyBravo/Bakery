<?php
// Include your database connection file
include 'db_connect.php';

// Check if expense_id is provided
if (isset($_GET['expense_id'])) {
    $expenseID = $_GET['expense_id'];

    // Delete expense from the database
    $sql = "DELETE FROM daily_expenses WHERE ExpenseID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $expenseID);

    if ($stmt->execute()) {
        echo '<script>alert("Expense deleted successfully"); window.location.href = "expenses_list.php";</script>';
    } else {
        echo "Error deleting expense: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
} else {
    echo "No expense ID provided";
}

// Close database connection
$conn->close();
?>