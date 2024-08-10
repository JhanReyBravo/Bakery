<?php
include 'db_connect.php';

// Check if payment_id is set
if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    // Delete payment record
    $stmt = $conn->prepare("DELETE FROM payments WHERE PaymentID = ?");
    $stmt->bind_param("i", $payment_id);

    if ($stmt->execute()) {
        echo "<script>alert('Payment deleted successfully.'); window.location.href = 'payment_list.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

// Close database connection
$conn->close();
?>