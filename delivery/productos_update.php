<?php
require_once '../conexion_delivery.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate the product ID
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = $_POST['id'];

        // Validate required fields
        $numero_parte = isset($_POST['numero_parte']) ? trim($_POST['numero_parte']) : null;
        $marca = isset($_POST['marca']) ? trim($_POST['marca']) : null;
        $modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : null;
        $ano = isset($_POST['ano']) ? trim($_POST['ano']) : null;
        $precio = isset($_POST['precio']) ? trim($_POST['precio']) : null;
        $existencia = isset($_POST['existencia']) ? trim($_POST['existencia']) : null;

        // Check if any required field is empty
        if (empty($numero_parte) || empty($marca) || empty($modelo) || empty($ano) || empty($precio) || empty($existencia)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit; // Stop further execution
        }

        // Prepare the SQL statement
        $sql = "UPDATE productos SET numero_parte = :numero_parte, marca = :marca, modelo = :modelo, ano = :ano, precio = :precio, existencia = :existencia";

        // Check if a new image is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            // Handle image upload
            $imagePath = 'uploads/' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
            $sql .= ", image = :image"; // Add image to the update query
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':numero_parte', $numero_parte);
            $stmt->bindParam(':marca', $marca);
            $stmt->bindParam(':modelo', $modelo);
            $stmt->bindParam(':ano', $ano);
            $stmt->bindParam(':precio', $precio);
            $stmt->bindParam(':existencia', $existencia);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            // Bind the image parameter if a new image is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $stmt->bindParam(':image', $imagePath);
            }

            // Execute the statement
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error en la actualización: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de producto no válido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
}

// Close the connection
$conn = null;
?>