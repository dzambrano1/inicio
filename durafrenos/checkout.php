<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

$user_id = $_SESSION["id"];
$user_name = $_SESSION["name"] ?? "Usuario";

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // 1. Check if cart has items
    $check_cart_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($check_cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($cart_count == 0) {
        throw new Exception("El carrito está vacío");
    }
    
    // 2. Calculate order total
    $total_query = "SELECT SUM(quantity * price) as total FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    
    if ($total <= 0) {
        throw new Exception("El total del pedido es inválido");
    }
    
    // 3. Create new order in orders table
    $insert_order_query = "INSERT INTO orders (customer_id, order_date, total) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($insert_order_query);
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();
    
    if (!$order_id) {
        throw new Exception("Error al crear el pedido");
    }
    
    // 4. Copy cart items to order_details
    $copy_items_query = "INSERT INTO order_details 
                        (order_id, product_id, quantity, code, make, model, year, price, image)
                        SELECT ?, product_id, quantity, code, make, model, year, price, image
                        FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($copy_items_query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected_rows <= 0) {
        throw new Exception("Error al copiar los items del pedido");
    }
    
    // 5. Clear the cart
    $clear_cart_query = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($clear_cart_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // If everything is OK, commit the transaction
    mysqli_commit($conn);
    
    // Redirect to the orders page with success message
    header("Location: orders.php?success=1&order_id=" . $order_id);
    exit;
    
} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    mysqli_rollback($conn);
    
    // Redirect back to cart with error message
    header("Location: cart.php?error=" . urlencode($e->getMessage()));
    exit;
} finally {
    mysqli_close($conn);
}
