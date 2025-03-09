<?php
// Set header for JSON response
header('Content-Type: application/json');

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Build query with filters
$sql = "SELECT * FROM products WHERE 1=1";

// Apply filters if provided
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = mysqli_real_escape_string($conn, $_GET['category']);
    $sql .= " AND category = '$category'";
}

if (isset($_GET['make']) && !empty($_GET['make'])) {
    $make = mysqli_real_escape_string($conn, $_GET['make']);
    $sql .= " AND make = '$make'";
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $max_price = floatval($_GET['max_price']);
    $sql .= " AND price <= $max_price";
}

if (isset($_GET['min_stock']) && !empty($_GET['min_stock'])) {
    $min_stock = intval($_GET['min_stock']);
    $sql .= " AND stock >= $min_stock";
}

// Order by ID descending (newest first)
$sql .= " ORDER BY id DESC";

// Execute query
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
    exit;
}

// Fetch all products
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Return products as JSON
echo json_encode($products);

// Close connection
mysqli_close($conn);
?> 