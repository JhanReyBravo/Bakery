<?php
include 'db_connect.php';

if (isset($_GET['productID'])) {
    $productID = intval($_GET['productID']);
    $sql = "SELECT pm.MatID, rm.MaterialName, pm.QuantityRequired 
            FROM product_materials pm 
            JOIN rawmat rm ON pm.MatID = rm.MatID 
            WHERE pm.ProductID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    $materials = [];
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }

    $stmt->close();
    $mysqli->close();

    echo json_encode($materials);
}
?>