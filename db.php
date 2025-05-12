<?php
$servername = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$database = "users"; // Change as needed

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Attempt to connect
    $conn = new mysqli($servername, $username, $password, $database);
    $conn->set_charset("utf8mb4"); // Set character encoding
} catch (Exception $e) {
    // If connection fails, provide error message
    die("Database Connection Failed: " . $e->getMessage());
}

// Optionally, you could check if there was an error after connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// After finishing your database operations, don't forget to close the connection
// $conn->close();
?>
