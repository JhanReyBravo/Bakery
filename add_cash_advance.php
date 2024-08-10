<?php include 'db_connect.php'; ?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Cash Advance</title>
</head>

<body>
    <h2>Add Cash Advance</h2>
    <form action="add_cash_advance.php" method="post">
        Employee ID: <input type="text" name="employee_id"><br>
        Advance Date: <input type="date" name="advance_date"><br>
        Amount: <input type="text" name="amount"><br>
        <input type="submit" name="submit" value="Add Advance">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $employeeID = $_POST['employee_id'];
        $advanceDate = $_POST['advance_date'];
        $amount = $_POST['amount'];

        $sql = "INSERT INTO cash_advances (EmployeeID, AdvanceDate, Amount) VALUES ('$employeeID', '$advanceDate', '$amount')";

        if ($conn->query($sql) === TRUE) {
            echo "Cash advance added successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        $conn->close();
    }
    ?>
</body>

</html>