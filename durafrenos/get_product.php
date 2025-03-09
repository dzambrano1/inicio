<?php
// Set header for JSON response
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Get product by ID
$id = intval($_GET['id']);
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Product not found']);
    $stmt->close();
    mysqli_close($conn);
    exit;
}

// Fetch product data
$product = $result->fetch_assoc();

// Return product as JSON
echo json_encode($product);

// Close statement and connection
$stmt->close();
mysqli_close($conn);
?> 