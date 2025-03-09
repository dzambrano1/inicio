<?php
// Start session for authentication
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== 1 || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Include database connection
require_once './conexion.php';

// Connect to database
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Get product ID from POST or GET
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

// Delete product
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$result = $stmt->execute();

if ($result) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el producto: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 