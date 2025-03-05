<?php
require_once '../conexion_delivery.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the linea parameter is set
    if (isset($_GET['linea'])) {
        $linea = trim($_GET['linea']); // Get the linea value from the request

        try {
            // Prepare the SQL statement to fetch products based on the linea
            $stmt = $conn->prepare("SELECT numero_parte, image, marca, modelo, ano, precio, existencia FROM productos WHERE linea = :linea");
            $stmt->bindParam(':linea', $linea, PDO::PARAM_STR);
            $stmt->execute();

            // Fetch the filtered product data
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Store the products in a session variable to access in inicio.php
            session_start();
            $_SESSION['filtered_products'] = $products;

            // Redirect to inicio.php
            header('Location: inicio.php');
            exit();
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Parámetro de filtro no válido.']);
    }
} else {
    echo json_encode(['error' => 'Método de solicitud no válido.']);
}

// Close the connection
$conn = null;
?>