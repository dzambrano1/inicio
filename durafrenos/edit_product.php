<?php
// Start session
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin") {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado. Solo administradores pueden actualizar productos.'
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
    
    try {
        // Get form data and sanitize
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $code = isset($_POST['code']) ? mysqli_real_escape_string($conn, trim($_POST['code'])) : '';
        $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, trim($_POST['category'])) : '';
        $make = isset($_POST['make']) ? mysqli_real_escape_string($conn, trim($_POST['make'])) : '';
        $model = isset($_POST['model']) ? mysqli_real_escape_string($conn, trim($_POST['model'])) : '';
        $year = isset($_POST['year']) ? mysqli_real_escape_string($conn, trim($_POST['year'])) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        
        // Basic validation
        if (empty($id)) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de producto inválido o faltante'
            ]);
            exit;
        }
        
        if (empty($code) || empty($category) || empty($make) || empty($model) || $price <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Por favor complete todos los campos requeridos'
            ]);
            exit;
        }
        
        // Check if product exists
        $check_query = "SELECT id FROM products WHERE id = $id";
        $result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($result) === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El producto no existe'
            ]);
            exit;
        }
        
        // Handle image upload if a new image is provided
        $image_path = null;
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
        
        // Prepare the UPDATE statement
        if ($image_path) {
            // Update with new image
            $sql = "UPDATE products SET 
                    code = ?, 
                    category = ?, 
                    make = ?, 
                    model = ?, 
                    year = ?, 
                    price = ?, 
                    stock = ?, 
                    image = ?
                    WHERE id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssddsi", $code, $category, $make, $model, $year, $price, $stock, $image_path, $id);
        } else {
            // Update without changing the image
            $sql = "UPDATE products SET 
                    code = ?, 
                    category = ?, 
                    make = ?, 
                    model = ?, 
                    year = ?, 
                    price = ?, 
                    stock = ? 
                    WHERE id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssidi", $code, $category, $make, $model, $year, $price, $stock, $id);
        }
        
        // Execute the update
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar el producto: ' . $stmt->error
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
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
