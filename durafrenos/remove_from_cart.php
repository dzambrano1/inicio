<?php
require_once './auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_POST['index'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Index not provided'
    ]);
    exit;
}

$index = intval($_POST['index']);

if (!isset($_SESSION['cart'][$index])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found in cart'
    ]);
    exit;
}

// Remove item from cart 
unset($_SESSION['cart'][$index]);
$_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array

echo json_encode([
    'success' => true,
    'message' => 'Product removed from cart'
]);
?> 