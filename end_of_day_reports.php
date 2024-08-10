<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a report date is selected
    $reportDate = isset($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Calculate BeginningBalance
        $stmt_beginning_balance = $conn->prepare("SELECT SUM(Amount) AS BeginningBalance FROM payments WHERE PaymentDate < ?");
        $stmt_beginning_balance->bind_param("s", $reportDate);
        $stmt_beginning_balance->execute();
        $result_beginning_balance = $stmt_beginning_balance->get_result();
        $beginningBalance = $result_beginning_balance->fetch_assoc()['BeginningBalance'];
        $beginningBalance = $beginningBalance ?: 0;

        // Calculate Total_Sales
        $stmt_total_sales = $conn->prepare("SELECT SUM(TotalAmount) AS TotalSales FROM invoices WHERE InvoiceDate = ?");
        $stmt_total_sales->bind_param("s", $reportDate);
        $stmt_total_sales->execute();
        $result_total_sales = $stmt_total_sales->get_result();
        $totalSales = $result_total_sales->fetch_assoc()['TotalSales'];
        $totalSales = $totalSales ?: 0;

        // Calculate Total_Payments
        $stmt_total_payments = $conn->prepare("SELECT SUM(Amount) AS TotalPayments FROM payments WHERE PaymentDate = ?");
        $stmt_total_payments->bind_param("s", $reportDate);
        $stmt_total_payments->execute();
        $result_total_payments = $stmt_total_payments->get_result();
        $totalPayments = $result_total_payments->fetch_assoc()['TotalPayments'];
        $totalPayments = $totalPayments ?: 0;

        // Calculate Cash, Gcash, and Bank Transfer payments
        $stmt_cash_payments = $conn->prepare("SELECT SUM(Amount) AS Cash FROM payments WHERE PaymentDate = ? AND PaymentMethod = 'Cash'");
        $stmt_cash_payments->bind_param("s", $reportDate);
        $stmt_cash_payments->execute();
        $result_cash_payments = $stmt_cash_payments->get_result();
        $cash = $result_cash_payments->fetch_assoc()['Cash'];
        $cash = $cash ?: 0;

        $stmt_gcash_payments = $conn->prepare("SELECT SUM(Amount) AS Gcash FROM payments WHERE PaymentDate = ? AND PaymentMethod = 'Gcash'");
        $stmt_gcash_payments->bind_param("s", $reportDate);
        $stmt_gcash_payments->execute();
        $result_gcash_payments = $stmt_gcash_payments->get_result();
        $gcash = $result_gcash_payments->fetch_assoc()['Gcash'];
        $gcash = $gcash ?: 0;

        $stmt_bank_transfer_payments = $conn->prepare("SELECT SUM(Amount) AS BankTransfer FROM payments WHERE PaymentDate = ? AND PaymentMethod = 'Bank Transfer'");
        $stmt_bank_transfer_payments->bind_param("s", $reportDate);
        $stmt_bank_transfer_payments->execute();
        $result_bank_transfer_payments = $stmt_bank_transfer_payments->get_result();
        $bankTransfer = $result_bank_transfer_payments->fetch_assoc()['BankTransfer'];
        $bankTransfer = $bankTransfer ?: 0;

        // Calculate Customer_Outstanding
        $stmt_customer_outstanding = $conn->prepare("SELECT SUM(TotalAmount) AS CustomerOutstanding FROM invoices WHERE PaymentStatus = 'Unpaid'");
        $stmt_customer_outstanding->execute();
        $result_customer_outstanding = $stmt_customer_outstanding->get_result();
        $customerOutstanding = $result_customer_outstanding->fetch_assoc()['CustomerOutstanding'];
        $customerOutstanding = $customerOutstanding ?: 0;

        // Calculate Total_Expenses
        $stmt_total_expenses = $conn->prepare("SELECT SUM(Amount) AS TotalExpenses FROM expenses WHERE ExpenseDate = ?");
        $stmt_total_expenses->bind_param("s", $reportDate);
        $stmt_total_expenses->execute();
        $result_total_expenses = $stmt_total_expenses->get_result();
        $totalExpenses = $result_total_expenses->fetch_assoc()['TotalExpenses'];
        $totalExpenses = $totalExpenses ?: 0;

        // Calculate Receiving Amount
        $stmt_receiving = $conn->prepare("SELECT SUM(TotalAmount) AS TotalReceiving FROM receiving WHERE RecDate = ?");
        $stmt_receiving->bind_param("s", $reportDate);
        $stmt_receiving->execute();
        $result_receiving = $stmt_receiving->get_result();
        $receivingAmount = $result_receiving->fetch_assoc()['TotalReceiving'];
        $receivingAmount = $receivingAmount ?: 0;

        // Calculate Payroll Amount
        $stmt_payroll = $conn->prepare("SELECT SUM(NetPay) AS TotalPayroll FROM payroll WHERE PayDate = ?");
        $stmt_payroll->bind_param("s", $reportDate);
        $stmt_payroll->execute();
        $result_payroll = $stmt_payroll->get_result();
        $payrollAmount = $result_payroll->fetch_assoc()['TotalPayroll'];
        $payrollAmount = $payrollAmount ?: 0;

        // Calculate Cash Advances
        $stmt_cash_advance = $conn->prepare("SELECT SUM(Amount) AS TotalCashAdvance FROM cash_advances WHERE AdvanceDate = ?");
        $stmt_cash_advance->bind_param("s", $reportDate);
        $stmt_cash_advance->execute();
        $result_cash_advance = $stmt_cash_advance->get_result();
        $cashAdvanceAmount = $result_cash_advance->fetch_assoc()['TotalCashAdvance'];
        $cashAdvanceAmount = $cashAdvanceAmount ?: 0;

        // Add receiving amount, payroll amount, and cash advance amount to total expenses
        $totalExpenses += $receivingAmount + $payrollAmount + $cashAdvanceAmount;

        // Insert data into end_of_day_reports table
        $stmt_insert_report = $conn->prepare("INSERT INTO end_of_day_reports (Report_Date, BeginningBalance, Total_Sales, Total_Payments, Cash, Gcash, BankTransfer, Customer_Outstanding, Total_Expenses) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert_report->bind_param("sdddddddd", $reportDate, $beginningBalance, $totalSales, $totalPayments, $cash, $gcash, $bankTransfer, $customerOutstanding, $totalExpenses);
        $stmt_insert_report->execute();

        // Commit transaction
        $conn->commit();

        echo "End-of-day report generated successfully.<br>";
    } catch (Exception $e) {
        // Rollback transaction if there was an error
        $conn->rollback();
        echo "Error generating report: " . $e->getMessage() . "<br>";
    }

    // Close statements and connection
    $stmt_beginning_balance->close();
    $stmt_total_sales->close();
    $stmt_total_payments->close();
    $stmt_cash_payments->close();
    $stmt_gcash_payments->close();
    $stmt_bank_transfer_payments->close();
    $stmt_customer_outstanding->close();
    $stmt_total_expenses->close();
    $stmt_receiving->close();
    $stmt_payroll->close();
    $stmt_cash_advance->close();
    $stmt_insert_report->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="apppayment.css">
    <title>Generate End-of-Day Report</title>
</head>

<body>
    <h2>Generate End-of-Day Report</h2>
    <form method="POST" action="end_of_day_reports.php">
        <label for="report_date">Select Report Date:</label>
        <input type="date" id="report_date" class="report" name="report_date" required>
        <input type="submit" value="Generate Report">
    </form>
    <br>
    <a href="home.html" class="home">Back</a>
    <a href="end_of_day_report_list.php" class="home">View Report List</a>
</body>

</html>