<?php
// Include your database connection file
include 'db_connect.php';

// Check if mat_id is set
if (isset($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];

    // Delete raw material record
    $stmt = $mysqli->prepare("DELETE FROM rawmat WHERE MatID = ?");
    $stmt->bind_param("i", $mat_id);

    if ($stmt->execute()) {
        echo "<script>alert('Raw material deleted successfully.'); window.location.href = 'rawmat_list.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

// Close database connection
$mysqli->close();
?>