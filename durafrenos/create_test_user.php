<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './conexion.php';

echo "Attempting to connect to database...<br>";
echo "Server: " . $servername . "<br>";
echo "Database: " . $dbname . "<br>";
echo "Username: " . $username . "<br>";

try {
    // Create connection 
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Connected successfully!<br>";

    // Create test user data
    $customer_id = 'CUST001';
    $plain_password = 'test123';
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    $name = 'Test Customer';
    $email = 'test@example.com';

    // First, delete existing test user if exists
    $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $stmt->close();

    // Insert new test user
    $stmt = $conn->prepare("INSERT INTO customers (customer_id, password, name, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $customer_id, $hashed_password, $name, $email);
    
    if ($stmt->execute()) {
        echo "Test user created successfully!<br>";
        echo "Customer ID: " . $customer_id . "<br>";
        echo "Password: " . $plain_password . "<br>";
        echo "Hashed Password: " . $hashed_password . "<br>";
    } else {
        echo "Error creating test user: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 