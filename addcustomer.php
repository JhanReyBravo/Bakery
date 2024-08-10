<?php
// Include your database connection file
include 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $balance = $_POST['balance'];

    // Check if customer already exists
    if (!isCustomerExists($name, $address, $phone, $email, $balance)) {
        // Add customer if not already exists
        addCustomer($name, $address, $phone, $email, $balance);
        echo '<script>alert("Customer added successfully"); window.location.href = "addcustomer.html";</script>';
    } else {
        echo '<script>alert("Customer already exists with the same details"); window.location.href = "addcustomer.html";</script>';
    }
}

function isCustomerExists($name, $address, $phone, $email, $balance)
{
    global $conn;

    // Prepare statement
    $stmt = $conn->prepare("SELECT * FROM customers WHERE Name = ? AND Address = ? AND Phone = ? AND Email = ? AND AccountBalance = ?");
    if (!$stmt) {
        die('Error preparing statement: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("sssss", $name, $address, $phone, $email, $balance);

    // Execute the statement
    if (!$stmt->execute()) {
        die('Error executing statement: ' . $stmt->error);
    }

    // Store result
    $stmt->store_result();

    // Check if customer exists
    $exists = $stmt->num_rows > 0;

    // Close the statement
    $stmt->close();

    return $exists;
}

// Function to add customer
function addCustomer($name, $address, $phone, $email, $balance)
{
    global $conn;

    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO customers (Name, Address, Phone, Email, AccountBalance) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die('Error preparing statement: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("ssssd", $name, $address, $phone, $email, $balance);

    // Execute the statement
    if (!$stmt->execute()) {
        die('Error executing statement: ' . $stmt->error);
    }

    // Close the statement
    $stmt->close();
}

// Close database connection
$conn->close();
?>