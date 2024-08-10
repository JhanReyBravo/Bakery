<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prevent SQL injection
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);

    // Use password_verify() to check hashed password
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Password is correct, create a session
            $_SESSION['username'] = $username;
            // Redirect to home.html
            header("Location: home.html");
            exit(); // Make sure to exit after redirection
        } else {
            // Password is incorrect
            echo "<script>alert('Invalid username or password.');
                  window.location='Login.html';</script>";
        }
    } else {
        // User not found
        echo "<script>alert('Invalid username or password.');
              window.location='Login.html';</script>";
    }
}
?>