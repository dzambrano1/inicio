<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Check if product_id is provided
if (!isset($_POST['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado']);
    exit();
}

$product_id = $_POST['product_id'];

// Check if cart exists and product is in cart
if (isset($_SESSION['cart']) && isset($_SESSION['cart'][$product_id])) {
    // Remove the product from cart
    unset($_SESSION['cart'][$product_id]);
    
    // If cart is empty, remove the cart session variable
    if (empty($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    
    echo json_encode(['success' => true, 'message' => 'Producto eliminado del carrito']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado en el carrito']);
}
?> 