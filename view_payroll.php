<?php include 'db_connect.php'; ?>

<!DOCTYPE html>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll List</title>
    <link rel="stylesheet" href="view_payroll.css">
</head>

<body>
    <h2>Payroll Records</h2>

    <form method="post" action=""><input type="text" name="search_name" placeholder="Search Employee Name">
        <input type="submit" name="search" value="Search">
        <a href="home.html" class="home">Back</a>
    </form>

    <?php
    $search_name = '';
    if (isset($_POST['search']) && !empty($_POST['search_name'])) {
        $search_name = $_POST['search_name'];
        $sql = "SELECT p.PayrollID, e.FirstName, e.LastName, p.PayDate, p.GrossPay, p.Deductions, p.NetPay, p.CashAdvances
                FROM payroll p
                JOIN employees e ON p.EmployeeID = e.EmployeeID
                WHERE e.FirstName LIKE '%$search_name%' OR e.LastName LIKE '%$search_name%'";
    } else {
        $sql = "SELECT p.PayrollID, e.FirstName, e.LastName, p.PayDate, p.GrossPay, p.Deductions, p.NetPay, p.CashAdvances
                FROM payroll p
                JOIN employees e ON p.EmployeeID = e.EmployeeID";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Payroll ID</th>
                    <th>Employee Name</th>
                    <th>Pay Date</th>
                    <th>Gross Pay</th>
                    <th>Deductions</th>
                    <th>Cash Advances</th>
                    <th>Net Pay</th>
                     <th>Actions</th>
                    
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $row['PayrollID'] . "</td>
                    <td>" . $row['FirstName'] . " " . $row['LastName'] . "</td>
                    <td>" . $row['PayDate'] . "</td>
                    <td>" . $row['GrossPay'] . "</td>
                    <td>" . $row['Deductions'] . "</td>
                    <td>" . $row['CashAdvances'] . "</td>
                    <td>" . $row['NetPay'] . "</td>
                    <td> <a class='edit' href='process_payroll.php'>Add Payroll</a></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "No payroll records found";
    }

    echo "<h2>Cash Advances</h2>";

    $sql_advances = "SELECT a.AdvanceID, e.FirstName, e.LastName, a.AdvanceDate, a.Amount
                     FROM cash_advances a
                     JOIN employees e ON a.EmployeeID = e.EmployeeID";
    $result_advances = $conn->query($sql_advances);

    if ($result_advances->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Advance ID</th>
                    <th>Employee Name</th>
                    <th>Advance Date</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>";
        while ($row = $result_advances->fetch_assoc()) {    
            echo "<tr>
                    <td>" . $row['AdvanceID'] . "</td>
                    <td>" . $row['FirstName'] . " " . $row['LastName'] . "</td>
                    <td>" . $row['AdvanceDate'] . "</td>
                    <td>" . $row['Amount'] . "</td>
                    <td> <a class='edit' href='process_payroll.php'>Add Cash Advance</a></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "No cash advances found";
    }

    $conn->close();
    ?>
</body>

</html>