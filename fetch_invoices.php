<?php
include 'db_connect.php';

if (isset($_POST['customer_id'])) {
    $customerID = $_POST['customer_id'];
    $stmt = $conn->prepare("SELECT InvoiceID FROM invoices WHERE CustomerID = ? AND PaymentStatus = 'Unpaid'");
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoices = array();

    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }

    $stmt->close();
    echo json_encode($invoices);
}
?>