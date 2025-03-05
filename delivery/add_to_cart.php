<?php
session_start();
require_once '../conexion_delivery.php';

// Initialize response array
$response = array('success' => false, 'message' => '');

try {
    // Validate input
    if (!isset($_POST['product_id']) || !isset($_POST['cantidad']) || !isset($_POST['numero_parte'])) {
        throw new Exception('Datos incompletos');
    }

    $productId = $_POST['product_id'];
    $cantidad = intval($_POST['cantidad']);
    $numeroParte = $_POST['numero_parte'];

    // Validate quantity
    if ($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a 0');
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    // Add or update cart item
    $_SESSION['cart'][$productId] = array(
        'cantidad' => $cantidad,
        'numero_parte' => $numeroParte
    );

    $response['success'] = true;
    $response['message'] = 'Producto agregado al carrito exitosamente';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>