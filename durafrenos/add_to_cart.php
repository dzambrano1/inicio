<?php
// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no ha iniciado sesión'
    ]);
    exit;
}

$user_id = $_SESSION["id"];

// Check if we have the required data
if (!isset($_POST['product_id']) || !isset($_POST['add_to_cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos requeridos faltantes'
    ]);
    exit;
}

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . mysqli_connect_error()
    ]);
    exit;
}

try {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Ensure quantity is at least 1
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // First, get product details
    $product_query = "SELECT id, code, make, model, price, image FROM products WHERE id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit;
    }
    
    // Check if this product is already in the user's cart
    $check_query = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_item = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing_item) {
        // Product already in cart, update quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        
        $update_query = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $new_quantity, $existing_item['id']);
        $success = $stmt->execute();
        $stmt->close();
        
        if (!$success) {
            throw new Exception('Error al actualizar la cantidad: ' . $conn->error);
        }
    } else {
        // Product not in cart, insert new item
        $insert_query = "INSERT INTO cart_items (user_id, product_id, quantity, code, make, model, price, image) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiisssds", 
            $user_id, 
            $product_id, 
            $quantity, 
            $product['code'], 
            $product['make'], 
            $product['model'], 
            $product['price'], 
            $product['image']
        );
        $success = $stmt->execute();
        $stmt->close();
        
        if (!$success) {
            throw new Exception('Error al agregar el producto al carrito: ' . $conn->error);
        }
    }
    
    // Get updated cart count
    $count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count_row = $result->fetch_assoc();
    $cart_count = $count_row ? $count_row['count'] : 0;
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Producto agregado al carrito correctamente',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close the database connection
    mysqli_close($conn);
}
?>