<?php
require_once '../conexion_delivery.php';
// Disable error reporting in output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $numero_parte = isset($_POST['numero_parte']) ? trim($_POST['numero_parte']) : null;
    $marca = isset($_POST['marca']) ? trim($_POST['marca']) : null;
    $modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : null;
    $ano = isset($_POST['ano']) ? trim($_POST['ano']) : null;
    $precio = isset($_POST['precio']) ? trim($_POST['precio']) : null;
    $existencia = isset($_POST['existencia']) ? trim($_POST['existencia']) : null;
    $linea = isset($_POST['linea']) ? trim($_POST['linea']) : null;

    // Check if any required field is empty
    if (empty($numero_parte) || empty($marca) || empty($modelo) || empty($ano) || empty($precio) || empty($existencia) || empty($linea)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit; // Stop further execution
    }

    try {
        $conn = new mysqli($hostname, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Get form data
        $image = $_POST['image'] ?? '';

        // Check if tagid already exists
        $check_stmt = $conn->prepare("SELECT numero_parte FROM productos WHERE numero_parte = ?");
        $check_stmt->bind_param("s", $numero_parte);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("El Numero de Parte ya existe en la base de datos");
        }
        $check_stmt->close();

        // Handle image upload if present
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            
            // Full path for file storage
            $targetPath = $uploadDir . $newFileName;

            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                throw new Exception("Error al subir la imagen");
            }

            // Store the complete path (including uploads/) in the database
            $image = 'uploads/' . $newFileName;  // Ensure uploads/ is included
        } else {
            $image = null;  // No image uploaded
        }

        // Prepare the insert query
        $sql = "INSERT INTO productos (numero_parte, linea, marca, modelo, ano, precio, existencia, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta: " . $conn->error);
        }

        $stmt->bind_param("sssssdis", 
            $numero_parte, 
            $linea, 
            $marca, 
            $modelo, 
            $ano, 
            $precio, 
            $existencia,
            $image
            );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar el registro: " . $stmt->error);
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();

        // Return success response
        echo json_encode([
            "success" => true,
            "message" => "Animal registrado exitosamente"
        ]);

    } catch (Exception $e) {
        // Log error to file instead of output
        error_log("Error in vacuno_create.php: " . $e->getMessage());
        
        // Return error response
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
}
?> 