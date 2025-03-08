<?php
require_once './auth.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_POST['index']) || !isset($_POST['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Incomplete data'
    ]);
    exit;
}

$index = intval($_POST['index']);
$quantity = intval($_POST['quantity']);

if (!isset($_SESSION['cart'][$index])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found in cart'
    ]);
    exit;
}

// Validate quantity 
if ($quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'The quantity must be greater than 0'
    ]);
    exit;
}

// Check stock availability
require_once './conexion.php';

try {
    $conn = new PDO("mysql:host=localhost;dbname=durafrenos", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    $stmt = $conn->prepare("SELECT stock FROM products WHERE code = ?");
    $stmt->execute([$_SESSION['cart'][$index]['code']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found in database'
        ]);
        exit;
    }

    if ($quantity > $product['stock']) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock available'
        ]);
        exit;
    }

    // Update quantity in cart
    $_SESSION['cart'][$index]['quantity'] = $quantity;

    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking stock: ' . $e->getMessage()
    ]);
} 