<?php
// Start output buffering immediately
ob_start();

// Set error reporting
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Set content type header for JSON
header('Content-Type: application/json');

session_start();

// AJAX-friendly authentication check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Check if user is admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Now load database connection since auth is handled
require_once './conexion.php';

// Debug: log received POST data to a file
$debug_data = "POST data received: " . print_r($_POST, true);
file_put_contents('payment_debug.log', $debug_data . PHP_EOL, FILE_APPEND);

if (!isset($_POST['order_id']) || !isset($_POST['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data: ' . (isset($_POST['order_id']) ? 'order_id present' : 'order_id missing') . ', ' . (isset($_POST['payment_method']) ? 'payment_method present' : 'payment_method missing')]);
    exit;
}

// Database connection - using try/catch to handle errors as JSON
try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    $order_id = $_POST['order_id'];
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Verify the order exists
    $stmt = $conn->prepare("SELECT o.total, o.customer_id FROM orders o WHERE o.order_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    $order = $result->fetch_assoc();
    $total_amount = $order['total'];
    $customer_id = $order['customer_id'];

    // If not admin, check if the order belongs to the current user
    if (!$is_admin && $customer_id != $_SESSION['customer_id']) {
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para procesar este pago']);
        exit;
    }

    // Calculate remaining amount to pay
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment_data = $result->fetch_assoc();
    $total_paid = $payment_data['total_paid'];
    $remaining_amount = $total_amount - $total_paid;

    if ($remaining_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'This order is already paid']);
        exit;
    }

    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, reference_number, notes) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("idsss", $order_id, $remaining_amount, $payment_method, $reference_number, $notes);

    if ($stmt->execute()) {
        // Update order status if fully paid
        if ($remaining_amount >= $total_amount) {
            $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
    } else {
        throw new Exception('Error processing payment: ' . $stmt->error);
    }

    $conn->close();

} catch (Exception $e) {
    // Ensure we return JSON even for exceptions
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // End output and exit
    ob_end_flush();
    exit;
}
