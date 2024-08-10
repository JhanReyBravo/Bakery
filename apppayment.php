<?php
include 'db_connect.php';

function fetchCustomerOptions($prefilledCustomerID = null)
{
    global $conn;

    $stmt = $conn->prepare("SELECT CustomerID, Name FROM customers ORDER BY Name ASC");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $selected = ($row['CustomerID'] == $prefilledCustomerID) ? ' selected' : '';
                echo '<option value="' . $row['CustomerID'] . '"' . $selected . '>' . $row['Name'] . '</option>';
            }
        } else {
            echo '<option value="">No customers available</option>';
        }
    } else {
        echo '<option value="">Error loading customers</option>';
    }
    $stmt->close();
}

$selectedCustomerID = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerID = $_POST['customer_id'];
    $invoiceID = $_POST['invoice_id'];
    $paymentDate = $_POST['payment_date'];
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert payment record
        $stmt_payment = $conn->prepare("INSERT INTO payments (CustomerID, InvoiceID, PaymentDate, Amount, PaymentMethod) VALUES (?, ?, ?, ?, ?)");
        $stmt_payment->bind_param("iisds", $customerID, $invoiceID, $paymentDate, $amount, $paymentMethod);
        $stmt_payment->execute();
        $paymentID = $stmt_payment->insert_id;

        // Update customer's account balance
        $stmt_balance = $conn->prepare("UPDATE customers SET AccountBalance = AccountBalance - ? WHERE CustomerID = ?");
        $stmt_balance->bind_param("di", $amount, $customerID);
        $stmt_balance->execute();

        // Insert payment application record
        $stmt_application = $conn->prepare("INSERT INTO payment_applications (PaymentID, InvoiceID, AmountApplied) VALUES (?, ?, ?)");
        $stmt_application->bind_param("iid", $paymentID, $invoiceID, $amount);
        $stmt_application->execute();

        // Update invoice details TotalAmount
        $stmt_update_invoice_details = $conn->prepare("UPDATE invoice_details SET TotalAmount = TotalAmount - ? WHERE InvoiceID = ?");
        $stmt_update_invoice_details->bind_param("di", $amount, $invoiceID);
        $stmt_update_invoice_details->execute();

        // Fetch current total amount of the invoice
        $stmt_fetch_invoice = $conn->prepare("SELECT TotalAmount FROM invoices WHERE InvoiceID = ?");
        $stmt_fetch_invoice->bind_param("i", $invoiceID);
        $stmt_fetch_invoice->execute();
        $result = $stmt_fetch_invoice->get_result();
        $invoice = $result->fetch_assoc();
        $newTotalAmount = $invoice['TotalAmount'] - $amount;

        // Determine new payment status
        $paymentStatus = ($newTotalAmount <= 0) ? 'Paid' : 'Unpaid';

        // Update invoice TotalAmount and PaymentStatus
        $stmt_update_invoice = $conn->prepare("UPDATE invoices SET TotalAmount = ?, PaymentStatus = ? WHERE InvoiceID = ?");
        $stmt_update_invoice->bind_param("dsi", $newTotalAmount, $paymentStatus, $invoiceID);
        $stmt_update_invoice->execute();

        // Commit transaction
        $conn->commit();

        echo '<script>alert("Payment recorded successfully."); window.location.href = "apppayment.php";</script>';
    } catch (Exception $e) {
        // Rollback transaction if there was an error
        $conn->rollback();
        echo "Error recording payment: " . $e->getMessage();
    }

    // Close statements
    $stmt_payment->close();
    $stmt_balance->close();
    $stmt_application->close();
    $stmt_update_invoice_details->close();
    $stmt_fetch_invoice->close();
    $stmt_update_invoice->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment</title>
    <link rel="stylesheet" href="apppayment.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#load_invoices').click(function () {
                var customerID = $('#customer_id').val();
                if (customerID != "") {
                    $.ajax({
                        url: "fetch_invoices.php",
                        method: "POST",
                        data: { customer_id: customerID },
                        dataType: "json",
                        success: function (data) {
                            var invoiceTable = $('#invoice_table tbody');
                            invoiceTable.empty();
                            $.each(data, function (key, value) {
                                invoiceTable.append('<tr><td>' + value.InvoiceID + '</td><td>' + value.TotalAmount + '</td><td><button type="button" class="select_invoice" data-invoice-id="' + value.InvoiceID + '" data-amount="' + value.TotalAmount + '">Select</button></td></tr>');
                            });
                            $('#invoice_modal').show();
                        }
                    });
                } else {
                    alert('Please select a customer first.');
                }
            });

            $(document).on('click', '.select_invoice', function () {
                var invoiceID = $(this).data('invoice-id');
                var amount = $(this).data('amount');
                $('#invoice_id').val(invoiceID);
                $('#amount').val(amount);
                $('#invoice_modal').hide();
            });

            $('#close_modal').click(function () {
                $('#invoice_modal').hide();
            });
        });
        function openInvoiceWindow() {
            var customerID = $('#customer_id').val();
            if (customerID != "") {
                var url = "fetch_invoices_window.php?customer_id=" + customerID;
                window.open(url, "InvoiceWindow", "width=600,height=400");
            } else {
                alert("Please select a customer first.");
            }
        }

        function setInvoice(invoiceID, totalAmount) {
            $('#invoice_id').val(invoiceID);
            $('#amount').val(totalAmount);
        }
    </script>
</head>

<body>
    <header>
        <img src="Alogo.png" alt="Website Logo" class="logo">
        <h1>THE A BAKERY BCD</h1>
    </header>
    <nav>
        <ul>
            <li><a href="home.html">Home</a></li>
        </ul>
    </nav>
    <h2>Record Payment</h2>
    <form action="apppayment.php" method="POST">
        <label for="customer_id">Customer:</label><br>
        <select id="customer_id" name="customer_id" required>
            <?php fetchCustomerOptions($selectedCustomerID); ?>
        </select><br><br>


        <label for="invoice_id">Invoice:</label><br>
        <input type="text" id="invoice_id" name="invoice_id" readonly required><br><br>

        <button type="button" class="Select" onclick="openInvoiceWindow()">Select Invoice</button><br><br>

        <label for="payment_date">Payment Date:</label><br>
        <input type="date" id="payment_date" name="payment_date" required><br><br>

        <label for="amount">Amount:</label><br>
        <input type="number" id="amount" name="amount" step="0.01" required><br><br>

        <label for="payment_method">Payment Method:</label><br>
        <select id="payment_method" name="payment_method" required>
            <option value="Cash">Cash</option>
            <option value="GCash">GCash</option>
            <option value="BankTransfer">Bank Transfer</option>
        </select><br><br>

        <input type="submit" class="submit" value="Record Payment">
        <a href="payment_list.php" class="home">Payment List</a>
        <a href="invoice_list.php" class="home">Invoice List</a>
        <a href="home.html" class="home">Back</a>
    </form>
</body>

</html>