<?php
session_start();
require_once '../conexion_delivery.php';

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $customer_id = sanitize_input($_POST['customer_id']);
    $user_password = sanitize_input($_POST['password']);
    
    // Validate input
    if (empty($customer_id) || empty($user_password)) {
        $_SESSION['error'] = "Por favor complete todos los campos.";
        header("Location: carrito.php");
        exit();
    }

    try {
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Debug log
        error_log("Attempting login for customer_id: " . $customer_id);

        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, password FROM customers WHERE customer_id = ?");
        $stmt->bind_param("s", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

                        
            // Debug log with more details
            error_log("Found user with ID: " . $row['id']);
            error_log("Input password: " . $user_password);
            error_log("Stored hash from DB: " . $row['password']);
            error_log("Hash of input password: " . password_hash($user_password, PASSWORD_DEFAULT));
            
            // Verify password
            $verification_result = password_verify($user_password, $row['password']);
            error_log("Password verification result: " . ($verification_result ? "true" : "false"));
            
            if ($verification_result) {
              error_log("Password verified successfully");
                
                // Password is correct, set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['logged_in'] = true;
                
                // Redirect to main page
                header("Location: ./carrito.php");
                exit();
            } else {
              error_log("Password verification failed");
                // Invalid password
                $_SESSION['error'] = "Contraseña incorrecta.";
                header("Location: ./carrito.php");
                exit();
            }
        } else {
          error_log("No user found with customer_id: " . $customer_id);
            // User not found
            $_SESSION['error'] = "ID de Cliente Incorrecto.";
            header("Location: ./carrito.php");
            exit();
        }

    } catch (Exception $e) {
      error_log("Login error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        $_SESSION['error'] = "Error en el servidor. Por favor intente más tarde.";
        header("Location: ./carrito.php");
        exit();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    // If someone tries to access this file directly without POST data
    header("Location: ./login.php");
    exit();
}