<?php
// Suppress any warnings or notices
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

require_once './auth.php';
requireLogin();

require_once './conexion.php';

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

if (empty($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'The cart is empty'
    ]);
    exit;
}

try {
    // Debug cart contents
    error_log("Cart contents: " . print_r($_SESSION['cart'], true));

    $conn = new PDO("mysql:host=localhost;dbname=durafrenos", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    // Start transaction 
    $conn->beginTransaction();

    // Generate order ID
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(order_id, 4) AS UNSIGNED)) as max_id FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_id = $result['max_id'] ? $result['max_id'] + 1 : 1;
    $order_id = 'ORD' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    // Calculate total amount
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Debug order data
    error_log("Order ID: " . $order_id);
    error_log("Customer ID: " . $_SESSION['customer_id']);
    error_log("Total Amount: " . $total_amount);

    // Insert order header
    $stmt = $conn->prepare("INSERT INTO orders (order_id, customer_id, order_date, status, total) VALUES (?, ?, NOW(), 'pending', ?)");
    $stmt->execute([$order_id, $_SESSION['customer_id'], $total_amount]);

    // Insert order details
    $stmt = $conn->prepare("INSERT INTO order_details (order_id, code, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        // Debug order detail data
        error_log("Processing cart item: " . print_r($item, true));
        
        // Validate required fields
        if (!isset($item['code']) || !isset($item['quantity']) || !isset($item['price'])) {
            error_log("Missing required fields in cart item: " . print_r($item, true));
            throw new Exception('Datos incompletos en el carrito');
        }
        
        try {
            // Log the values being inserted
            error_log("Inserting order detail with values: order_id=" . $order_id . 
                     ", code=" . $item['code'] . 
                     ", quantity=" . $item['quantity'] . 
                     ", price=" . $item['price']);
            
            $stmt->execute([$order_id, $item['code'], $item['quantity'], $item['price']]);
            
            // Verify the insertion
            $verify_stmt = $conn->prepare("SELECT * FROM order_details WHERE order_id = ? AND code = ?");
            $verify_stmt->execute([$order_id, $item['code']]);
            $inserted = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Verification of inserted detail: " . print_r($inserted, true));
            
        } catch (PDOException $e) {
            error_log("Error inserting order detail: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            throw $e;
        }
        
        // Update product stock
        $update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE code = ?");
        $update_stock->execute([$item['quantity'], $item['code']]);
    }

    // Commit transaction
    $conn->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $order_id
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    // Log the error details
    error_log("Database Error: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage(),
        'debug' => [
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine()
        ]
    ]);
} catch (Exception $e) {
    // Handle any other type of error
    error_log("General Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'General error processing order: ' . $e->getMessage()
    ]);
}
?>
