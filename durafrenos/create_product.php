<?php
// Start session
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin") {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado. Solo administradores pueden crear productos.'
    ]);
    exit;
}

// Include database connection
require_once "./conexion.php";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . mysqli_connect_error()
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set content type for response
    header('Content-Type: application/json');
    
    // Get form data and sanitize
    $code = isset($_POST['code']) ? mysqli_real_escape_string($conn, trim($_POST['code'])) : '';
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, trim($_POST['category'])) : '';
    $make = isset($_POST['make']) ? mysqli_real_escape_string($conn, trim($_POST['make'])) : '';
    $model = isset($_POST['model']) ? mysqli_real_escape_string($conn, trim($_POST['model'])) : '';
    $year = isset($_POST['year']) ? mysqli_real_escape_string($conn, trim($_POST['year'])) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
    
    // Basic validation
    if (empty($code) || empty($category) || empty($make) || empty($model) || $price <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor complete todos los campos requeridos'
        ]);
        exit;
    }
    
    // Check if product code already exists
    $check_query = "SELECT id FROM products WHERE code = '$code'";
    $result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($result) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya existe un producto con este código. Por favor, use otro código.'
        ]);
        exit;
    }
    
    // Handle image upload
    $image_path = '';
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "images/products/";
        
        // Create the directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = "product_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is valid
        $valid_extensions = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_extension, $valid_extensions)) {
            echo json_encode([
                'success' => false,
                'message' => 'Solo se permiten archivos JPG, JPEG, PNG & GIF'
            ]);
            exit;
        }
        
        // Try to upload the file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al subir la imagen'
            ]);
            exit;
        }
    }
    
    // Prepare the INSERT statement
    $sql = "INSERT INTO products (code, category, make, model, year, price, stock, image, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssids", $code, $category, $make, $model, $year, $price, $stock, $image_path);
    
    // Execute the insert
    if ($stmt->execute()) {
        $new_product_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado correctamente',
            'product_id' => $new_product_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al agregar el producto: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} else {
    // Not a POST request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no válido'
    ]);
}

// Close the database connection
mysqli_close($conn);
?>