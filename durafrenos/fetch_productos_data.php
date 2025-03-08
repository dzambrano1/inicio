<?php
require_once './conexion.php'; // Database connection 

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validate the product ID
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        try {
            // Prepare the SQL statement
            $stmt = $conn->prepare("SELECT code, image, make, model, year, price, stock FROM products WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Fetch the product data
            $product = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results

            if (!empty($product)) {
                $product = $product[0]; // Get the first product
                // Return the product data as JSON
                echo json_encode($product);
            } else {
                echo json_encode(['error' => 'Producto no encontrado.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'ID de producto no válido.']);
    }
} else {
    echo json_encode(['error' => 'Método de solicitud no válido.']);
}

// Close the connection
$conn = null;
?>