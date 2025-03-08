<?php
require_once './auth.php';
requireLogin();

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

require_once './conexion.php';

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Function to sanitize input
function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

// Get form data
$code = sanitize($conn, $_POST['code'] ?? '');
$category = sanitize($conn, $_POST['category'] ?? '');
$make = sanitize($conn, $_POST['make'] ?? '');
$model = sanitize($conn, $_POST['model'] ?? '');
$year = sanitize($conn, $_POST['year'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);

// Validate required fields
if (empty($code) || empty($category) || empty($make) || empty($model) || $price <= 0 || $stock < 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
    exit;
}

// Handle image upload
$image_path = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "images/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $new_filename = "product_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file type
    $allowed_types = array("jpg", "jpeg", "png", "gif");
    if (!in_array($file_extension, $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos JPG, JPEG, PNG & GIF']);
        exit;
    }
    
    // Upload file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = $target_file;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
        exit;
    }
}

// Insert product into database
if (!empty($image_path)) {
    $stmt = $conn->prepare("INSERT INTO products (code, category, make, model, year, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdis", $code, $category, $make, $model, $year, $price, $stock, $image_path);
} else {
    $stmt = $conn->prepare("INSERT INTO products (code, category, make, model, year, price, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdi", $code, $category, $make, $model, $year, $price, $stock);
}

if ($stmt->execute()) {
    $product_id = $conn->insert_id;
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'product_id' => $product_id]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al crear el producto: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
