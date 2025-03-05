<?php
session_start();
require_once '../conexion_delivery.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Check if cart data is received
if (!isset($_POST['cart']) || empty($_POST['cart'])) {
    echo json_encode(['success' => false, 'message' => 'No hay productos en el carrito']);
    exit();
}

try {
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    // Get customer ID from session
    $customer_id = $_SESSION['customer_id'];

    // Insert order header with auto-incrementing order_id
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, status, total) VALUES (?, NOW(), 'pending', 0.00)");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    
    // Get the auto-generated order_id
    $order_id = $conn->insert_id;

    // Process each item in the cart
    $cart = json_decode($_POST['cart'], true);
    
    // Debug logging
    error_log("Cart data received: " . print_r($_POST['cart'], true));
    error_log("Decoded cart: " . print_r($cart, true));

    // Insert order details
    foreach ($cart as $item) {
        // Get product information
        $stmt = $conn->prepare("SELECT id, precio FROM productos WHERE numero_parte = ?");
        $stmt->bind_param("s", $item['numero_parte']);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            $product_id = $product['id'];
            $quantity = $item['cantidad'];
            $price = $product['precio'];
            $subtotal = $price * $quantity;

            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiids", $order_id, $product_id, $quantity, $price, $subtotal);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting order details: " . $stmt->error);
            }
        } else {
            throw new Exception("Producto no encontrado: " . $item['numero_parte']);
        }
    }

    // Calculate total
    $stmt = $conn->prepare("SELECT SUM(subtotal) as total FROM order_details WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];

    // Update order total
    $stmt = $conn->prepare("UPDATE orders SET total = ? WHERE order_id = ?");
    $stmt->bind_param("di", $total, $order_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Clear the cart from session
    unset($_SESSION['cart']);

    echo json_encode(['success' => true, 'message' => 'Pedido procesado exitosamente', 'order_id' => $order_id]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error al procesar el pedido: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
