<?php include 'db_connect.php'; ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll List</title>
    <link rel="stylesheet" href="process_payroll.css">
    <header>
        <img src="Alogo.png" alt="Website Logo" class="logo">
        <h1>THE A BAKERY BCD</h1>
    </header>
    <nav>
        <ul>
            <li><a href="home.html">Home</a></li>
        </ul>
    </nav>
    <title>Process Payroll</title>
</head>

<body>

    <div class="container">
        <form class="form1" action="process_payroll.php" method="post">
            <h2> Payroll</h2>
            Employee:
            <select name="employee_id">
                <option value="">Select Employee</option>
                <?php
                $sql_employees = "SELECT EmployeeID, FirstName, LastName FROM employees";
                $result_employees = $conn->query($sql_employees);

                if ($result_employees->num_rows > 0) {
                    while ($row_employees = $result_employees->fetch_assoc()) {
                        echo "<option value='" . $row_employees['EmployeeID'] . "'>" . $row_employees['FirstName'] . " " . $row_employees['LastName'] . "</option>";
                    }
                }
                ?>
            </select><br><br><br>
            Pay Date: <input type="date" name="pay_date"><br><br>
            Deductions: <input type="text" name="deductions"><br>
            <input type="submit" name="submit" value="Process Payroll">
        </form>
        <form class="form2" action="process_payroll.php" method="post">
            <h2>Cash Advance</h2>
            Employee:
            <select name="advance_employee_id">
                <option value="">Select Employee</option>
                <?php
                $sql_employees = "SELECT EmployeeID, FirstName, LastName FROM employees";
                $result_employees = $conn->query($sql_employees);

                if ($result_employees->num_rows > 0) {
                    while ($row_employees = $result_employees->fetch_assoc()) {
                        echo "<option value='" . $row_employees['EmployeeID'] . "'>" . $row_employees['FirstName'] . " " . $row_employees['LastName'] . "</option>";
                    }
                }
                ?>
            </select><br><br><br>
            Advance Date: <input type="date" name="advance_date"><br><br>
            Amount: <input type="text" name="advance_amount"><br>
            <input type="submit" name="add_advance" value="Add Advance">
        </form>
    </div>
    <a href="view_payroll.php" class="home">View Payroll Lists</a>
    <a href="home.html" class="home">Back</a>
    <?php
    if (isset($_POST['submit'])) {
        $employeeID = $_POST['employee_id'];
        $payDate = $_POST['pay_date'];
        $deductions = $_POST['deductions'];

        if (!empty($employeeID)) {
            // Fetch the gross pay (salary) for the selected employee
            $sql_salary = "SELECT Salary FROM employees WHERE EmployeeID='$employeeID'";
            $result_salary = $conn->query($sql_salary);

            if ($result_salary->num_rows > 0) {
                $row_salary = $result_salary->fetch_assoc();
                $grossPay = $row_salary['Salary'];
            } else {
                echo "Employee not found.";
                exit();
            }
            // Calculate total cash advances for the employee
            $sql_advances = "SELECT SUM(Amount) AS total_advances FROM cash_advances WHERE EmployeeID='$employeeID'";
            $result_advances = $conn->query($sql_advances);

            if ($result_advances->num_rows > 0) {
                $row_advances = $result_advances->fetch_assoc();
                $total_advances = $row_advances['total_advances'];
            } else {
                $total_advances = 0;
            }
            // Cast values to numbers
            $grossPay = (float) $grossPay;
            $deductions = (float) $deductions;
            $total_advances = (float) $total_advances;

            // Calculate net pay
            $netPay = $grossPay - $deductions - $total_advances;

            // Insert payroll record
            $sql = "INSERT INTO payroll (EmployeeID, PayDate, GrossPay, Deductions, NetPay, CashAdvances) VALUES ('$employeeID', '$payDate', '$grossPay', '$deductions', '$netPay', '$total_advances')";

            if ($conn->query($sql) === TRUE) {
                echo "Payroll processed successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }

            // Clear advances after payroll processing
            $sql_clear_advances = "DELETE FROM cash_advances WHERE EmployeeID='$employeeID'";
            $conn->query($sql_clear_advances);

            $conn->close();
        } else {
            echo "Please select an employee.";
        }
    }
    if (isset($_POST['add_advance'])) {
        $employeeID = $_POST['advance_employee_id'];
        $advanceDate = $_POST['advance_date'];
        $amount = $_POST['advance_amount'];

        if (!empty($employeeID) && !empty($advanceDate) && !empty($amount)) {
            $amount = (float) $amount;
            $sql_advance = "INSERT INTO cash_advances (EmployeeID, AdvanceDate, Amount) VALUES ('$employeeID', '$advanceDate', '$amount')";
            if ($conn->query($sql_advance) === TRUE) {
                echo "Cash advance added successfully";
            } else {
                echo "Error: " . $sql_advance . "<br>" . $conn->error;
            }
            $conn->close();
        } else {
            echo "Please fill all fields for cash advance.";
        }
    }
    ?>
</body>

</html>