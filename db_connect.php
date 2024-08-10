<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bakerybcd";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$mysqli = new mysqli("localhost", "root", "", "bakerybcd");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>