<?php
include 'db_connect.php';

if (isset($_POST['invoice_id'])) {
    $invoiceID = $_POST['invoice_id'];
    $stmt = $conn->prepare("SELECT TotalAmount FROM invoices WHERE InvoiceID = ?");
    $stmt->bind_param("i", $invoiceID);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();

    $stmt->close();
    echo json_encode($invoice);
}
?>