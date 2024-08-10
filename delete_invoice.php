<?php
// Include your database connection file
include 'db_connect.php';

// Check if invoice_id is provided in the query string
if (isset($_GET['invoice_id'])) {
    $invoiceID = $_GET['invoice_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete invoice items
        $delete_items_sql = "DELETE FROM invoice_details WHERE InvoiceID = ?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $invoiceID);
        $stmt_delete_items->execute();
        $stmt_delete_items->close();

        // Delete invoice
        $delete_invoice_sql = "DELETE FROM invoices WHERE InvoiceID = ?";
        $stmt_delete_invoice = $conn->prepare($delete_invoice_sql);
        $stmt_delete_invoice->bind_param("i", $invoiceID);
        $stmt_delete_invoice->execute();
        $stmt_delete_invoice->close();

        // Commit transaction
        $conn->commit();

        // Redirect to invoice list
        header("Location: invoice_list.php");
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

    // Close database connection
    $conn->close();
} else {
    die("Invoice ID not provided.");
}
?>