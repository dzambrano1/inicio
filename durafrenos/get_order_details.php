<?php
// Start session
session_start();

// Set header for JSON response
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
$user_role = $_SESSION["role"] ?? "customer";

// Check if order ID is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de pedido no proporcionado'
    ]);
    exit;
}

$order_id = intval($_GET['order_id']);

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
    // First verify the order belongs to this user (unless admin)
    if ($user_role !== "admin") {
        $check_query = "SELECT id FROM orders WHERE id = ? AND customer_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Pedido no encontrado o no autorizado'
            ]);
            exit;
        }
    }
    
    // Get order information
    $order_query = "SELECT id, customer_id, order_date, total FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'Pedido no encontrado'
        ]);
        exit;
    }
    
    // Format order data
    $order_data = [
        'id' => $order['id'],
        'customer_id' => $order['customer_id'],
        'date' => date('d/m/Y H:i', strtotime($order['order_date'])),
        'total' => $order['total']
    ];
    
    // Get order details
    $details_query = "SELECT id, product_id, quantity, code, make, model, year, price, image 
                     FROM order_details 
                     WHERE order_id = ?";
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'order' => $order_data,
        'details' => $details
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_close($conn);
}
?>
