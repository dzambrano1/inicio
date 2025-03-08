<?php
require_once './conexion.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the category parameter is set
    if (isset($_GET['category'])) {
        $category = trim($_GET['category']); // Get the category value from the request

        try {
            // Prepare the SQL statement to fetch products based on the category
            $stmt = $conn->prepare("SELECT code, image, make, model, year, price, stock FROM products WHERE category = :category");
            $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            $stmt->execute();

            // Fetch the filtered product data
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Store the products in a session variable to access in Home.php
            session_start();
            $_SESSION['filtered_products'] = $products;

            // Redirect to home.php
            header('Location: ./home.php');
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