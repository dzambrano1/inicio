<?php
session_start();
require_once './conexion.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = $_POST['id'];
$quantity = (int)$_POST['quantity'];
$customer_id = $_SESSION['customer_id'];

try {
    // Connect to database
    $conn = new PDO("mysql:host=localhost;dbname=durafrenos", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");

    // Check if product exists and has enough stock
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
        exit;
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add or update product in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $id,
            'quantity' => $quantity,
            'price' => $product['price'],
            'make' => $product['make'],
            'model' => $product['model'],
            'code' => $product['code']
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => count($_SESSION['cart'])
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
} finally {
    $conn = null;
}
?>