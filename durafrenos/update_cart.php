<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION["id"];

// Check if we have the necessary data
if (!isset($_POST['item_id']) || !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

$item_id = intval($_POST['item_id']);
$action = $_POST['action'];

// Verify that this cart item belongs to the current user
$check_query = "SELECT id FROM cart_items WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Item not found in your cart']);
    exit;
}

// Handle different actions
switch ($action) {
    case 'increase':
        $sql = "UPDATE cart_items SET quantity = quantity + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $success = $stmt->execute();
        $stmt->close();
        break;
        
    case 'decrease':
        // First check current quantity
        $query = "SELECT quantity FROM cart_items WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_qty = $result->fetch_assoc()['quantity'];
        $stmt->close();
        
        if ($current_qty <= 1) {
            // Remove item if quantity would be zero
            $sql = "DELETE FROM cart_items WHERE id = ?";
        } else {
            // Decrease quantity
            $sql = "UPDATE cart_items SET quantity = quantity - 1 WHERE id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $success = $stmt->execute();
        $stmt->close();
        break;
        
    case 'remove':
        $sql = "DELETE FROM cart_items WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $success = $stmt->execute();
        $stmt->close();
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

mysqli_close($conn);

// Return success
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Cart updated successfully' : 'Failed to update cart'
]);
?> 