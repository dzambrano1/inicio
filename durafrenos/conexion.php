<?php

// Check if this is an AJAX request 
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
           || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
           || strpos($_SERVER['SCRIPT_NAME'], 'process_payments.php') !== false;

// For your local version (conexion.php on your local machine):

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "durafrenos";

// If not an AJAX request, use standard PDO connection with die()
if (!$is_ajax) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8");
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
} 
// For AJAX requests, don't connect here - let the calling script handle connections
// to ensure proper JSON error handling

?>